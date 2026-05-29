// top-nav.js: Dynamisches Laden der Top-Navigation und Hervorheben des aktiven Links

async function loadTopNavigation() {
  const container =
    document.getElementById("top_nav_container");

  if (!container) return;

  try {
    const response =
      await fetch("components/top-nav.html");

    const html =
      await response.text();

    container.innerHTML = html;

    setupActiveDesktopLink();

  } catch (error) {
    console.error(
      "Could not load top navigation:",
      error
    );
  }
}

function setupActiveDesktopLink() {
  const currentPage =
    window.location.pathname
      .split("/")
      .pop();

  document
    .querySelectorAll(".desktop_nav_link")
    .forEach(link => {
      const href =
        link.getAttribute("href");

      if (href === currentPage) {
        link.classList.add("is_active");
      }
    });
}

loadTopNavigation();