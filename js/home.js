let timerInterval = null;
let timerStartedAt = null;

const activeGoalArea = document.getElementById("active_goal_area");
const startRoundBtn = document.getElementById("start_round_btn");
const timerCard = document.getElementById("timer_card");
const timerDisplay = document.getElementById("timer_display");
const timerFill = document.getElementById("timer_fill");
const resetTimerBtn = document.getElementById("reset_timer_btn");
const buzzerPanel = document.getElementById("buzzer_panel");
const buzzerGrid = document.getElementById("buzzer_grid");

document.addEventListener("DOMContentLoaded", async function () {
  const user = await requireAuth();

  if (!user) {
    return;
  }

  await loadActiveGoal();
  await loadMembers();
});

function renderGoalCard(goal) {
  if (!goal) {
    return `
      <section class="empty_state">
        <h2>No active goal</h2>
        <p>Please create a goal first.</p>
      </section>
    `;
  }

  const current = Number(goal.points_current);
  const required = Number(goal.points_required);
  const percentage = required > 0 ? Math.min((current / required) * 100, 100) : 0;

  return `
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
}

async function loadActiveGoal() {
  try {
    const response = await fetch("api/get_active_goal.php", {
      credentials: "include"
    });
    const result = await response.json();

    if (result.status !== "success") {
      activeGoalArea.innerHTML = `<section class="empty_state"><p>Goal konnte nicht geladen werden.</p></section>`;
      return;
    }

    activeGoalArea.innerHTML = renderGoalCard(result.goal);
    startRoundBtn.disabled = !result.goal;
  } catch (error) {
    console.error(error);
    activeGoalArea.innerHTML = `<section class="empty_state"><p>Verbindung zum Server fehlgeschlagen.</p></section>`;
  }
}

async function loadMembers() {
  try {
    const response = await fetch("api/get_members.php", {
      credentials: "include"
    });

    const result = await response.json();

    if (result.status !== "success") {
      buzzerGrid.innerHTML = `<p>Family Members konnten nicht geladen werden.</p>`;
      return;
    }

    renderMemberButtons(result.members);
  } catch (error) {
    console.error(error);
    buzzerGrid.innerHTML = `<p>Verbindung zum Server fehlgeschlagen.</p>`;
  }
}

function renderMemberButtons(members) {
  if (!members || members.length === 0) {
    buzzerGrid.innerHTML = `<p>No active family members found.</p>`;
    return;
  }

  buzzerGrid.innerHTML = members.map(function (member) {
    return `
      <button
  class="buzzer_button"
  type="button"
  data-member-id="${member.ID}"
  data-clicked="0"
  disabled
>
        <span class="nav_icon">${escapeHtml(member.icon || "●")}</span>
        <span>${escapeHtml(member.name)}</span>
      </button>
    `;
  }).join("");

  buzzerGrid.querySelectorAll(".buzzer_button").forEach(function (button) {
    button.addEventListener("click", handleBuzzerClick);
  });
}

async function handleBuzzerClick(event) {
  const button = event.currentTarget;
  const memberId = Number(button.dataset.memberId);

  if (!memberId) {
    return;
  }

  button.disabled = true;

  try {
    const response = await fetch("api/create_buzzer_event.php", {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        member_id: memberId
      })
    });

    const result = await response.json();

    if (result.status !== "success") {
      alert(result.message || "Buzzer konnte nicht gespeichert werden.");
      button.disabled = false;
      return;
    }

    button.dataset.clicked = "1";
button.classList.add("is_clicked");

button.innerHTML += `
  <small class="buzzer_result">${result.event.points} points</small>
`;

    await loadActiveGoal();

    if (result.round_completed) {
      stopTimer();
    }

  } catch (error) {
    console.error(error);
    alert("Buzzer konnte nicht gespeichert werden.");
    button.disabled = false;
  }
}

function setBuzzerButtonsEnabled(isEnabled) {
  buzzerGrid.querySelectorAll(".buzzer_button").forEach(function (button) {
    if (button.dataset.clicked !== "1") {
      button.disabled = !isEnabled;
    }
  });
}

function resetBuzzerButtonStates() {
  buzzerGrid.querySelectorAll(".buzzer_button").forEach(function (button) {
    button.dataset.clicked = "0";
    button.disabled = false;
    button.classList.remove("is_clicked");

    button.querySelectorAll(".buzzer_result").forEach(function (resultElement) {
      resultElement.remove();
    });
  });
}

startRoundBtn.addEventListener("click", async function () {
  try {
    const response = await fetch("api/start_round.php", {
      method: "POST",
      credentials: "include"
    });

    const raw = await response.text();
console.log("Antwort von start_round.php:", raw);

let result;

try {
  result = JSON.parse(raw);
} catch (error) {
  alert("start_round.php liefert kein gültiges JSON. Siehe Console.");
  console.error("Keine JSON-Antwort:", raw);
  return;
}

    if (result.status === "success") {
  resetBuzzerButtonStates();
  startTimer(new Date(result.round.starttime.replace(" ", "T")).getTime());
  return;
}

    if (result.active_round) {
      startTimer(Date.now() - result.active_round.seconds_elapsed * 1000);
      return;
    }

    alert(result.message || "Runde konnte nicht gestartet werden.");
  } catch (error) {
    console.error(error);
    alert("Verbindung zum Server fehlgeschlagen.");
  }
});

resetTimerBtn.addEventListener("click", async function () {
  try {
    const response = await fetch("api/cancel_round.php", {
      method: "POST",
      credentials: "include"
    });

    const result = await response.json();

    if (result.status !== "success") {
      alert(result.message || "Runde konnte nicht zurückgesetzt werden.");
      return;
    }

    stopTimer();

  } catch (error) {
    console.error(error);
    alert("Reset fehlgeschlagen.");
  }
});

function startTimer(startTimestamp) {
  timerStartedAt = startTimestamp;
  startRoundBtn.style.display = "none";
  timerCard.classList.add("is_visible");
  buzzerPanel.classList.add("is_visible");

  updateTimer();

  clearInterval(timerInterval);
  timerInterval = setInterval(updateTimer, 50);

  setBuzzerButtonsEnabled(true);
}

function stopTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  timerStartedAt = null;

  timerDisplay.textContent = "0:00.00";
  timerFill.style.width = "0%";
  timerCard.classList.remove("is_visible");
  buzzerPanel.classList.remove("is_visible");
  startRoundBtn.style.display = "flex";

  setBuzzerButtonsEnabled(false);
}

function updateTimer() {
  if (!timerStartedAt) {
    return;
  }

  const elapsedMs = Math.max(Date.now() - timerStartedAt, 0);
  const elapsedSeconds = elapsedMs / 1000;

  timerDisplay.textContent = formatTimer(elapsedMs);

  const progress = Math.min((elapsedSeconds / 300) * 100, 100);
  timerFill.style.width = `${progress}%`;

  if (elapsedSeconds >= 300) {
  stopTimer();
}
}

function formatTimer(milliseconds) {
  const totalCentiseconds = Math.floor(milliseconds / 10);
  const centiseconds = totalCentiseconds % 100;
  const totalSeconds = Math.floor(totalCentiseconds / 100);
  const seconds = totalSeconds % 60;
  const minutes = Math.floor(totalSeconds / 60);

  return `${minutes}:${String(seconds).padStart(2, "0")}.${String(centiseconds).padStart(2, "0")}`;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
