async function loadBottomNav() {

  const nav_container =
    document.getElementById("bottom_nav_container");

  if (!nav_container) return;

  try {

    const response = await fetch(
      "components/bottom-nav.html"
    );

    const html = await response.text();

    nav_container.innerHTML = html;

    setActiveNavigation();

  } catch (error) {

    console.error(
      "Bottom navigation could not be loaded:",
      error
    );
  }
}


function setActiveNavigation() {

  const current_page =
    window.location.pathname.split("/").pop();

  const nav_links =
    document.querySelectorAll(".nav_link");

  nav_links.forEach(link => {

    const href =
      link.getAttribute("href");

    if (href === current_page) {

      link.classList.add("is_active");
    }
  });
}


loadBottomNav();