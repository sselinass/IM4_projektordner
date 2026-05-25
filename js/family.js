// js/family.js

const icons = [
  "character_baby",
  "character_bird",
  "character_bunny",
  "character_cat",
  "character_crown",
  "character_fish",
  "character_flower",
  "character_heart",
  "character_lightning",
  "character_moon",
  "character_person",
  "character_personcircle",
  "character_smiley",
  "character_star",
  "character_tree"
];

let editing_member_id = null;
let selected_icon = null;
let selected_color = null;
let family_members = [];

const family_members_container =
  document.getElementById("family_members");

const add_member_button =
  document.getElementById("add_member_button");

const member_modal =
  document.getElementById("member_modal");

const cancel_member_button =
  document.getElementById("cancel_member_button");

const member_form =
  document.getElementById("member_form");

const icon_selection =
  document.getElementById("icon_selection");

const delete_member_modal =
  document.getElementById("delete_member_modal");

const delete_member_text =
  document.getElementById("delete_member_text");

const cancel_delete_member_button =
  document.getElementById("cancel_delete_member_button");

const confirm_delete_member_button =
  document.getElementById("confirm_delete_member_button");

let deleting_member_id = null;


async function initFamilyPage() {
  const user = await requireAuth();

  if (!user) {
    return;
  }

  createIconSelection();
  setupEventListeners();
  await loadFamilyMembers();
}


function setupEventListeners() {
  add_member_button.addEventListener(
    "click",
    openModal
  );

  cancel_member_button.addEventListener(
    "click",
    closeModal
  );

  member_form.addEventListener(
    "submit",
    saveFamilyMember
  );

  cancel_delete_member_button.addEventListener(
    "click",
    closeDeleteModal
  );

  confirm_delete_member_button.addEventListener(
    "click",
    async function () {
      if (!deleting_member_id) {
        return;
      }

      await deleteFamilyMember(deleting_member_id);
    }
  );
}


function createIconSelection() {
  icons.forEach(icon => {
    const button = document.createElement("button");

    button.type = "button";
    button.className = "icon_button";

    button.innerHTML = `
      <img
        src="resources/assets/icons/${icon}.svg"
        alt="${icon}">
    `;

    button.addEventListener("click", () => {
      document
        .querySelectorAll(".icon_button")
        .forEach(button => {
          button.classList.remove("is_selected");
        });

      button.classList.add("is_selected");
      selected_icon = icon;
    });

    icon_selection.appendChild(button);
  });


  document
    .querySelectorAll(".color_button")
    .forEach(button => {
      button.addEventListener("click", () => {
        if (button.disabled) return;

        document
          .querySelectorAll(".color_button")
          .forEach(button => {
            button.classList.remove("is_selected");
          });

        button.classList.add("is_selected");
        selected_color = button.dataset.color;
      });
    });
}


async function loadFamilyMembers() {
  try {
    const response =
      await fetch("api/get_family_members.php");

    const result =
      await response.json();

    if (result.status !== "success") {
      console.error(result.message);
      return;
    }

    family_members =
      result.members || [];

    renderFamilyMembers();

  } catch (error) {
    console.error("Could not load family members:", error);
  }
}


function renderFamilyMembers() {
  family_members_container.innerHTML = "";

  family_members.forEach(member => {
    const card = document.createElement("article");

    card.className =
      `family_member_card ${member.buzzer}`;

    card.innerHTML = `
      <div class="family_member_left">

        <div class="member_icon_wrapper">
          <img
            class="member_icon"
            src="resources/assets/icons/${member.icon}.svg"
            alt="${member.name}">
        </div>

        <div>
          <div class="member_name">
           <h3>${member.name}</h3>
          </div>

          <div class="member_points" title="${Number(member.total_points || 0)} total points">
           <p>${formatPoints(member.total_points)} total points</p>
          </div>
        </div>

      </div>

      <div class="member_actions">

        <button
          class="member_action_button edit"
          data-id="${member.ID}">
        <img src="../resources/assets/icons/pen.svg" alt="edit">
        </button>

        <button
          class="member_action_button delete"
          data-id="${member.ID}">
          <img src="../resources/assets/icons/bin.svg" alt="Delete">
        </button>

      </div>
    `;

    family_members_container.appendChild(card);
  });

  setupMemberActionButtons();

  updateAddButton();
}

//ACTION BUTTONS (EDIT/DELETE) 
function setupMemberActionButtons() {

  document
  .querySelectorAll(".member_action_button.delete")
  .forEach(button => {
    button.addEventListener("click", () => {
      const memberId = Number(button.dataset.id);

      const member =
        family_members.find(member => Number(member.ID) === memberId);

      if (!member) {
        return;
      }

      openDeleteModal(member);
    });
  });


  document
    .querySelectorAll(".member_action_button.edit")
    .forEach(button => {
      button.addEventListener("click", () => {
        const memberId = Number(button.dataset.id);

        const member =
          family_members.find(member => Number(member.ID) === memberId);

        if (!member) return;

        openModal(member);
      });
    });
}

function openDeleteModal(member) {
  deleting_member_id = member.ID;

  delete_member_text.textContent =
    `Do you really want to delete ${member.name}?`;

  delete_member_modal.classList.remove("is_hidden");
}


function closeDeleteModal() {
  deleting_member_id = null;

  delete_member_text.textContent =
    "Do you really want to delete this family member?";

  delete_member_modal.classList.add("is_hidden");
}


async function deleteFamilyMember(memberId) {
  try {
    const response =
      await fetch("api/delete_family_member.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          id: memberId
        })
      });

    const result =
      await response.json();

    if (result.status !== "success") {
      alert(result.message);
      return;
    }

    closeDeleteModal();

    await loadFamilyMembers();

  } catch (error) {
    console.error("Could not delete family member:", error);
    alert("Family Member konnte nicht gelöscht werden.");
  }
}



function updateAddButton() {
  if (family_members.length >= 4) {
    add_member_button.style.display = "none";
  } else {
    add_member_button.style.display = "flex";
  }

  disableUsedColors();
}


function disableUsedColors(currentColor = null) {
  const used_colors =
    family_members.map(member => member.buzzer);

  document
    .querySelectorAll(".color_button")
    .forEach(button => {
      const color = button.dataset.color;

      button.disabled =
        used_colors.includes(color) &&
        color !== currentColor;
    });
}


function openModal(member = null) {
  disableUsedColors(member?.buzzer || null);

  member_modal.classList.remove("is_hidden");

  if (member) {
    editing_member_id = member.ID;

    document.querySelector(".member_modal_header h2").textContent =
      "Edit Family Member";

    document.getElementById("member_name").value =
      member.name;

    selected_icon = member.icon;
    selected_color = member.buzzer;

    document
      .querySelectorAll(".icon_button")
      .forEach(button => {
        const img = button.querySelector("img");
        const isSelected =
          img.src.includes(`${member.icon}.svg`);

        button.classList.toggle("is_selected", isSelected);
      });

    document
      .querySelectorAll(".color_button")
      .forEach(button => {
        button.classList.toggle(
          "is_selected",
          button.dataset.color === member.buzzer
        );
      });

  } else {
    editing_member_id = null;

    document.querySelector(".member_modal_header h2").textContent =
      "New Family Member";
  }
}

function closeModal() {
  member_modal.classList.add("is_hidden");

  member_form.reset();

  selected_icon = null;
  selected_color = null;

  document
    .querySelectorAll(".icon_button")
    .forEach(button => {
      button.classList.remove("is_selected");
    });

  document
    .querySelectorAll(".color_button")
    .forEach(button => {
      button.classList.remove("is_selected");
    });

  editing_member_id = null;
  document.querySelector(".member_modal_header h2").textContent = "New Family Member";
}


async function saveFamilyMember(event) {
  event.preventDefault();

  const name =
    document.getElementById("member_name").value.trim();

  if (name === "") {
    alert("Please enter a name.");
    return;
  }

  if (!selected_icon || !selected_color) {
    alert("Please select icon and color.");
    return;
  }

  const url =
    editing_member_id
      ? "api/update_family_member.php"
      : "api/create_family_member.php";

  const payload =
    editing_member_id
      ? {
        id: editing_member_id,
        name: name,
        icon: selected_icon,
        buzzer: selected_color
      }
      : {
        name: name,
        icon: selected_icon,
        buzzer: selected_color
      };

  try {
    const response =
      await fetch(url, {
        method: "POST",

        headers: {
          "Content-Type": "application/json"
        },

        body: JSON.stringify(payload)
      });

    const result =
      await response.json();

    if (result.status !== "success") {
      alert(result.message);
      return;
    }

    closeModal();

    await loadFamilyMembers();

  } catch (error) {
    console.error("Could not save family member:", error);
  }
}

function formatPoints(points) {
  const value = Number(points || 0);

  if (value < 1000) {
    return String(value);
  }

  if (value < 10000) {
    const shortValue = value / 1000;

    if (Number.isInteger(shortValue)) {
      return `${shortValue}k`;
    }

    return `${shortValue.toFixed(1)}k`;
  }

  return `${Math.round(value / 1000)}k`;
}

initFamilyPage();