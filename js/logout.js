// js/logout.js

async function logoutUser(redirectTarget) {
  try {
    const response =
      await fetch("api/logout.php", {
        method: "GET",
        credentials: "include"
      });

    const result =
      await response.json();

    if (result.status === "success") {
      window.location.href = redirectTarget;
      return;
    }

    alert("Logout konnte nicht durchgeführt werden. Bitte versuche es erneut.");

  } catch (error) {
    console.error("Logout error:", error);
    alert("Beim Logout ist ein Fehler aufgetreten.");
  }
}

document.addEventListener("click", function (event) {
  const logoutButton =
    event.target.closest("#logout_button");

  const changeFamilyButton =
    event.target.closest("#change_family_button");

  if (logoutButton) {
    event.preventDefault();
    logoutUser("login.html");
    return;
  }

  if (changeFamilyButton) {
    event.preventDefault();
    logoutUser("index.html");
    return;
  }
});