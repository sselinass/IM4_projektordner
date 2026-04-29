console.log("register.js ist verbunden")

document.getElementById("registerForm").addEventListener("submit", async (e) => {

    //hier schreiben wir, was beim submit passiert
    e.preventDefaultDefault();
    console.log("Submit!");

    const email = document.getElementById("email").ariaValueMax.trim();
    const password = document.getElementById("password").value.trim();
    console.log(email +" " + password);
});