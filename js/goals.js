const activeGoalArea = document.getElementById("active_goal_area");
const collectGoalBtn = document.getElementById("collect_goal_btn");
const futureGoalsList = document.getElementById("future_goals_list");
const showGoalFormBtn = document.getElementById("show_goal_form_btn");
const goalFormCard = document.getElementById("goal_form_card");
const goalForm = document.getElementById("goal_form");
const cancelGoalFormBtn = document.getElementById("cancel_goal_form_btn");
const goalsMessage = document.getElementById("goals_message");

let activeGoal = null;

document.addEventListener("DOMContentLoaded", async function () {
  const user = await requireAuth();

  if (!user) {
    return;
  }

  await loadGoals();
});

showGoalFormBtn.addEventListener("click", function () {
  goalFormCard.classList.add("is_visible");
  showGoalFormBtn.style.display = "none";
});

cancelGoalFormBtn.addEventListener("click", function () {
  goalForm.reset();
  goalFormCard.classList.remove("is_visible");
  showGoalFormBtn.style.display = "flex";
  goalsMessage.textContent = "";
});

goalForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  const title = document.getElementById("goal_title").value.trim();
  const pointsRequired = Number(document.getElementById("goal_points_required").value);

  if (title === "" || pointsRequired <= 0) {
    goalsMessage.textContent = "Bitte Goal-Titel und gültige Punktezahl eingeben.";
    return;
  }

  try {
    const response = await fetch("api/create_goal.php", {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        goal: title,
        points_required: pointsRequired
      })
    });

    const result = await response.json();

    if (result.status !== "success") {
      goalsMessage.textContent = result.message || "Goal konnte nicht gespeichert werden.";
      return;
    }

    goalForm.reset();
    goalFormCard.classList.remove("is_visible");
    showGoalFormBtn.style.display = "flex";
    goalsMessage.textContent = "";

    await loadGoals();
  } catch (error) {
    console.error(error);
    goalsMessage.textContent = "Verbindung zum Server fehlgeschlagen.";
  }
});

collectGoalBtn.addEventListener("click", async function () {
  if (!activeGoal) {
    return;
  }

  try {
    const response = await fetch("api/collect_goal.php", {
      method: "POST",
      credentials: "include"
    });

    const result = await response.json();

    if (result.status !== "success") {
      goalsMessage.textContent = result.message || "Goal konnte nicht eingelöst werden.";
      return;
    }

    goalsMessage.textContent = "";
    await loadGoals();
  } catch (error) {
    console.error(error);
    goalsMessage.textContent = "Verbindung zum Server fehlgeschlagen.";
  }
});

async function loadGoals() {
  try {
    const response = await fetch("api/get_goals.php", {
      credentials: "include"
    });
    const result = await response.json();

    if (result.status !== "success") {
      goalsMessage.textContent = result.message || "Goals konnten nicht geladen werden.";
      return;
    }

    activeGoal = result.active_goal;
    renderActiveGoal(result.active_goal);
    renderFutureGoals(result.future_goals);
  } catch (error) {
    console.error(error);
    goalsMessage.textContent = "Verbindung zum Server fehlgeschlagen.";
  }
}

function renderActiveGoal(goal) {
  if (!goal) {
    activeGoalArea.innerHTML = `
      <section class="empty_state">
        <h2>No active goal</h2>
        <p>Add a new goal to get started.</p>
      </section>
    `;
    collectGoalBtn.classList.remove("is_visible");
    return;
  }

  const current = Number(goal.points_current);
  const required = Number(goal.points_required);
  const percentage = required > 0 ? Math.min((current / required) * 100, 100) : 0;

  activeGoalArea.innerHTML = `
    <section class="goal_card">
      <div class="goal_badge">
        <img src="../resources/assets/icons/cup.svg" alt="cup icon">
        <span>Current Goal</span>
      </div>

      <img
        src="resources/assets/DinnerBell.svg"
        alt="Rennende Essensglocke"
        class="goal_image"
      >

      <h2 class="goal_title">${escapeHtml(goal.goal)}</h2>
      <p class="goal_points">${current} / ${required} points</p>

      <div class="progress_bar">
        <div class="progress_fill" style="width: ${percentage}%"></div>
      </div>
    </section>
  `;

  if (current >= required) {
    collectGoalBtn.classList.add("is_visible");
  } else {
    collectGoalBtn.classList.remove("is_visible");
  }
}

function renderFutureGoals(goals) {
  if (!goals || goals.length === 0) {
    futureGoalsList.innerHTML = `
      <section class="empty_state">
        <p>No future goals yet.</p>
      </section>
    `;
    return;
  }

  futureGoalsList.innerHTML = goals.map(function (goal) {
    const current = Number(goal.points_current);
    const required = Number(goal.points_required);

    return `
      <article class="goal_list_item">
        <div>
          <h3>${escapeHtml(goal.goal)}</h3>
          <p>${current} / ${required} points</p>
        </div>

        <button
          class="icon_button"
          type="button"
          aria-label="Set active goal"
          data-action="activate"
          data-goal-id="${goal.ID}"
        >○</button>

        <button
          class="icon_button"
          type="button"
          aria-label="Delete goal"
          data-action="delete"
          data-goal-id="${goal.ID}"
        >⌫</button>
      </article>
    `;
  }).join("");

  futureGoalsList.querySelectorAll("button[data-action]").forEach(function (button) {
    button.addEventListener("click", handleGoalAction);
  });
}

async function handleGoalAction(event) {
  const button = event.currentTarget;
  const action = button.dataset.action;
  const goalId = Number(button.dataset.goalId);

  if (!goalId) {
    return;
  }

  if (action === "activate") {
    await setActiveGoal(goalId);
  }

  if (action === "delete") {
    await deleteGoal(goalId);
  }
}

async function setActiveGoal(goalId) {
  try {
    const response = await fetch("api/set_active_goal.php", {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        goal_id: goalId
      })
    });

    const result = await response.json();

    if (result.status !== "success") {
      goalsMessage.textContent = result.message || "Goal konnte nicht aktiviert werden.";
      return;
    }

    goalsMessage.textContent = "";
    await loadGoals();
  } catch (error) {
    console.error(error);
    goalsMessage.textContent = "Verbindung zum Server fehlgeschlagen.";
  }
}

async function deleteGoal(goalId) {
  try {
    const response = await fetch("api/delete_goal.php", {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        goal_id: goalId
      })
    });

    const result = await response.json();

    if (result.status !== "success") {
      goalsMessage.textContent = result.message || "Goal konnte nicht gelöscht werden.";
      return;
    }

    goalsMessage.textContent = "";
    await loadGoals();
  } catch (error) {
    console.error(error);
    goalsMessage.textContent = "Verbindung zum Server fehlgeschlagen.";
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
