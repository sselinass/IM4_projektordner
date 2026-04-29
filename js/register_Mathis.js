console.log("Registering Mathis...");

document.getElementById("registerformv2").addEventListener("submit", async (e) => {

    e.preventDefault();
    console.log("Submit!!");

    const email = document.getElementById("email").value;
    value.trim();

    const password = document.getElementById("password").value.trim();

    console.log(email + " " + password);


});