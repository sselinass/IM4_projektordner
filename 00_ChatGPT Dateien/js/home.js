let timerInterval = null;
let timerStartedAt = null;

const activeGoalArea = document.getElementById("active_goal_area");
const startRoundBtn = document.getElementById("start_round_btn");
const timerCard = document.getElementById("timer_card");
const timerDisplay = document.getElementById("timer_display");
const timerFill = document.getElementById("timer_fill");
const resetTimerBtn = document.getElementById("reset_timer_btn");
const buzzerPanel = document.getElementById("buzzer_panel");

document.addEventListener("DOMContentLoaded", async function () {
  const user = await requireAuth();

  if (!user) {
    return;
  }

  await loadActiveGoal();
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
        <span>🏆</span>
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

startRoundBtn.addEventListener("click", async function () {
  try {
    const response = await fetch("api/start_round.php", {
      method: "POST",
      credentials: "include"
    });

    const result = await response.json();

    if (result.status === "success") {
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

resetTimerBtn.addEventListener("click", function () {
  stopTimer();
});

function startTimer(startTimestamp) {
  timerStartedAt = startTimestamp;
  startRoundBtn.style.display = "none";
  timerCard.classList.add("is_visible");
  buzzerPanel.classList.add("is_visible");

  updateTimer();

  clearInterval(timerInterval);
  timerInterval = setInterval(updateTimer, 50);
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
    clearInterval(timerInterval);
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
