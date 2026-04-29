console.log("register.js ist verbunden")

document.getElementById("registerForm").addEventListener("submit", async (e) => {

    //hier schreiben wir, was beim submit passiert
    e.preventDefaultDefault();
    console.log("Submit!")
});