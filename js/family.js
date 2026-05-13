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


async function initFamilyPage() {

  createIconSelection();

  await loadFamilyMembers();

  setupEventListeners();
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
}


function createIconSelection() {

  icons.forEach(icon => {

    const button =
      document.createElement("button");

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

        selected_color =
          button.dataset.color;
      });
    });
}


async function loadFamilyMembers() {

  try {

    const response =
      await fetch(
        "api/get_family_members.php"
      );

    family_members =
      await response.json();

    renderFamilyMembers();

  } catch (error) {

    console.error(error);
  }
}


function renderFamilyMembers() {

  family_members_container.innerHTML = "";

  family_members.forEach(member => {

    family_members_container.innerHTML += `
      <article class="family_member_card">

        <div class="family_member_left">

          <div class="member_icon_wrapper">

            <img
              class="member_icon"
              src="resources/assets/icons/${member.icon}.svg"
              alt="${member.name}">

          </div>

          <div>

            <div class="member_name">
              ${member.name}
            </div>

            <div class="member_points">
              0 total points
            </div>

          </div>

        </div>

        <div
          class="member_color ${member.buzzer}">
        </div>

      </article>
    `;
  });


  updateAddButton();
}


function updateAddButton() {

  if (family_members.length >= 4) {

    add_member_button.style.display = "none";

  } else {

    add_member_button.style.display = "block";
  }


  disableUsedColors();
}


function disableUsedColors() {

  const used_colors =
    family_members.map(member => member.buzzer);

  document
    .querySelectorAll(".color_button")
    .forEach(button => {

      const color =
        button.dataset.color;

      button.disabled =
        used_colors.includes(color);
    });
}


function openModal() {

  member_modal.classList.remove("is_hidden");
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
}


async function saveFamilyMember(event) {

  event.preventDefault();

  const name =
    document.getElementById("member_name").value;

  if (!selected_icon || !selected_color) {

    alert("Please select icon and color.");

    return;
  }

  try {

    const response =
      await fetch(
        "api/create_family_member.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            name,
            icon: selected_icon,
            buzzer: selected_color
          })
        }
      );

    const result =
      await response.json();

    if (!result.success) {

      alert(result.message);

      return;
    }

    closeModal();

    await loadFamilyMembers();

  } catch (error) {

    console.error(error);
  }
}


initFamilyPage();