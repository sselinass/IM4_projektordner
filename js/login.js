// login.js: Logik für die Anmeldung bestehender Benutzer
const login_form = document.getElementById("login_form");
const form_message = document.getElementById("form_message");

login_form.addEventListener("submit", async function (event) {
  event.preventDefault();

  form_message.textContent = "";

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  if (email === "" || password === "") {
    form_message.textContent = "Bitte Email und Passwort eingeben.";
    return;
  }

  try {
    const response = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        email: email,
        password: password
      })
    });

    const result = await response.json();

    if (result.status === "success") {
      window.location.href = "home.html";
    } else {
      form_message.textContent = result.message || "Login fehlgeschlagen.";
    }

  } catch (error) {
    form_message.textContent = "Verbindung zum Server fehlgeschlagen.";
    console.error(error);
  }
});