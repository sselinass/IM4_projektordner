// stats.js: Logik für die Anzeige der Statistiken auf der Stats-Seite
let statsData = null;
let statsMode = "total";

const statsMembersList =
  document.getElementById("stats_members_list");

const statsToggleButton =
  document.getElementById("stats_toggle_button");

const weeklyChart =
  document.getElementById("weekly_chart");

document.addEventListener("DOMContentLoaded", async function () {
  const user = await requireAuth();

  if (!user) {
    return;
  }

  await loadStats();

  statsToggleButton.addEventListener("click", function () {
    statsMode = statsMode === "total" ? "average" : "total";

    renderStatsMembers();
    renderWeeklyChart();
  });
});

async function loadStats() {
  try {
    const response = await fetch("api/get_stats.php", {
      credentials: "include"
    });

    const result = await response.json();

    if (result.status !== "success") {
      statsMembersList.innerHTML = `<p>Statistiken konnten nicht geladen werden.</p>`;
      weeklyChart.innerHTML = `<p>Diagramm konnte nicht geladen werden.</p>`;
      return;
    }

    statsData = result;

    renderStatsMembers();
    renderWeeklyChart();

  } catch (error) {
    console.error(error);

    statsMembersList.innerHTML = `<p>Verbindung zum Server fehlgeschlagen.</p>`;
    weeklyChart.innerHTML = `<p>Verbindung zum Server fehlgeschlagen.</p>`;
  }
}

function renderStatsMembers() {
  if (!statsData || !statsData.members || statsData.members.length === 0) {
    statsMembersList.innerHTML = `<p>No active family members found.</p>`;
    return;
  }

  statsToggleButton.textContent =
    statsMode === "total" ? "Show Average" : "Show Total";

  statsMembersList.innerHTML = statsData.members.map(function (member) {
    const value =
      statsMode === "total"
        ? Number(member.total_points)
        : Number(member.average_points);

    const formattedValue =
      statsMode === "total"
        ? String(Math.round(value))
        : value.toFixed(1);

    const dinnerText =
      Number(member.dinner_count) === 1
        ? "1 dinner"
        : `${member.dinner_count} dinners`;

    return `
      <article class="stats_member_card ${escapeHtml(member.buzzer)}">
        <div class="stats_member_left">
          <div class="stats_icon_wrapper">
            <img
              src="resources/assets/icons/${escapeHtml(member.icon)}.svg"
              alt="${escapeHtml(member.name)}"
            >
          </div>

          <div>
            <h3>${escapeHtml(member.name)}</h3>
            <p>${dinnerText}</p>
          </div>
        </div>

        <div class="stats_points">
          <h3>${formattedValue}</h3>
          <p>points</p>
        </div>
      </article>
    `;
  }).join("");
}

function renderWeeklyChart() {
  if (!statsData || !statsData.weeks || !statsData.members) {
    weeklyChart.innerHTML = `<p>No chart data available.</p>`;
    return;
  }

  const weeks = statsData.weeks;
  const members = statsData.members;

  const values = [];

  members.forEach(function (member) {
    member.trend_points.forEach(function (points) {
      values.push(Number(points));
    });
  });

  const maxValue = Math.max(...values, 100);
  const chartMax = getNiceChartMax(maxValue);

  const svgWidth = 320;
  const svgHeight = 260;

  const plotX = 52;
  const plotY = 18;
  const plotWidth = 230;
  const plotHeight = 170;

  const groupWidth = plotWidth / weeks.length;
  const barGap = 4;
  const barWidth = Math.min(16, (groupWidth - 16) / members.length);

  let gridHtml = "";
  const gridSteps = [1, 0.75, 0.5, 0.25, 0];

  gridSteps.forEach(function (step) {
    const y = plotY + plotHeight - (plotHeight * step);
    const label = Math.round(chartMax * step);

    gridHtml += `
      <line
        class="chart_grid_line"
        x1="${plotX}"
        y1="${y}"
        x2="${plotX + plotWidth}"
        y2="${y}"
      ></line>

      <text
        class="chart_axis_label"
        x="${plotX - 10}"
        y="${y + 4}"
        text-anchor="end"
      >${label}</text>
    `;
  });

  let barsHtml = "";
  let weekLabelsHtml = "";

  weeks.forEach(function (week, weekIndex) {
    const groupX = plotX + (weekIndex * groupWidth);
    const barsTotalWidth = (members.length * barWidth) + ((members.length - 1) * barGap);
    const firstBarX = groupX + ((groupWidth - barsTotalWidth) / 2);

    weekLabelsHtml += `
      <text
        class="chart_week_label"
        x="${groupX + (groupWidth / 2)}"
        y="${plotY + plotHeight + 24}"
        text-anchor="middle"
      >${escapeHtml(week.label)}</text>
    `;

    members.forEach(function (member, memberIndex) {
      const points = Number(member.trend_points[weekIndex] || 0);
      const barHeight = points > 0 ? (points / chartMax) * plotHeight : 0;

      const x = firstBarX + (memberIndex * (barWidth + barGap));
      const y = plotY + plotHeight - barHeight;

      barsHtml += `
        <rect
          class="chart_bar ${escapeHtml(member.buzzer)}"
          x="${x}"
          y="${y}"
          width="${barWidth}"
          height="${barHeight}"
          rx="1"
        ></rect>
      `;
    });
  });

  const legendHtml = members.map(function (member) {
    return `
      <span class="chart_legend_item">
        <span class="chart_legend_dot ${escapeHtml(member.buzzer)}"></span>
        ${escapeHtml(member.name)}
      </span>
    `;
  }).join("");

  weeklyChart.innerHTML = `
    <svg
      class="weekly_chart_svg"
      viewBox="0 0 ${svgWidth} ${svgHeight}"
      role="img"
      aria-label="Weekly trends chart"
    >
      ${gridHtml}

      <line
        class="chart_axis_line"
        x1="${plotX}"
        y1="${plotY}"
        x2="${plotX}"
        y2="${plotY + plotHeight}"
      ></line>

      <line
        class="chart_axis_line"
        x1="${plotX}"
        y1="${plotY + plotHeight}"
        x2="${plotX + plotWidth}"
        y2="${plotY + plotHeight}"
      ></line>

      ${barsHtml}
      ${weekLabelsHtml}
    </svg>

    <div class="chart_legend">
      ${legendHtml}
    </div>
  `;
}

function getNiceChartMax(value) {
  if (value <= 100) {
    return 100;
  }

  if (value <= 300) {
    return Math.ceil(value / 75) * 75;
  }

  return Math.ceil(value / 100) * 100;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}