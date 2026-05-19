// js/logout.js

const logoutButton =
  document.getElementById("logout_button");

if (logoutButton) {
  logoutButton.addEventListener("click", async function (event) {
    event.preventDefault();

    try {
      const response =
        await fetch("api/logout.php", {
          method: "GET",
          credentials: "include"
        });

      const result =
        await response.json();

      if (result.status === "success") {
        window.location.href = "index.html";
        return;
      }

      alert("Logout failed. Please try again.");

    } catch (error) {
      console.error("Logout error:", error);
      alert("Something went wrong during logout!");
    }
  });
}