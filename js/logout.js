// js/logout.js

const changeFamilyButton =
  document.getElementById("change_family_button");

if (changeFamilyButton) {
  changeFamilyButton.addEventListener("click", async function (event) {
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

      alert("Family konnte nicht gewechselt werden. Bitte versuche es erneut.");

    } catch (error) {
      console.error("Change family error:", error);
      alert("Beim Wechseln der Family ist ein Fehler aufgetreten.");
    }
  });
}