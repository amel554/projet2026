const form = document.getElementById("loginForm");
const successBar = document.getElementById("successBar");

function setError(input, errorId, message) {
    input.classList.add("error-input");
    input.classList.remove("success-input");
    document.getElementById(errorId).textContent = message;
}

function setSuccess(input, errorId) {
    input.classList.remove("error-input");
    input.classList.add("success-input");
    document.getElementById(errorId).textContent = "";
}

function isValidLogin(login) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const ninRegex = /^[0-9]{18}$/;
    return emailRegex.test(login) || ninRegex.test(login);
}

form.addEventListener("submit", function (e) {
    e.preventDefault();

    let isFormValid = true;

    const login = document.getElementById("login");
    const password = document.getElementById("password");

    if (login.value.trim() === "") {
        setError(login, "loginError", "L'email ou le NIN est obligatoire.");
        isFormValid = false;
    } else if (!isValidLogin(login.value.trim())) {
        setError(login, "loginError", "Veuillez entrer un email valide ou un NIN de 18 chiffres.");
        isFormValid = false;
    } else {
        setSuccess(login, "loginError");
    }

    if (password.value.trim() === "") {
        setError(password, "passwordError", "Le mot de passe est obligatoire.");
        isFormValid = false;
    } else if (password.value.trim().length < 6) {
        setError(password, "passwordError", "Le mot de passe doit contenir au moins 6 caractÃ¨res.");
        isFormValid = false;
    } else {
        setSuccess(password, "passwordError");
    }

    if (isFormValid) {
        if (successBar) {
            successBar.style.display = "block";

            setTimeout(() => {
                successBar.style.display = "none";
                form.submit();
            }, 1500);
        } else {
            form.submit();
        }
    }
});
