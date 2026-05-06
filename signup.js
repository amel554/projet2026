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

function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function isValidPhone(phone) {
    const regex = /^(0)(5|6|7)[0-9]{8}$/;
    return regex.test(phone);
}

function isValidNIN(nin) {
    const regex = /^[0-9]{18}$/;
    return regex.test(nin);
}

function isValidName(value) {
    const regex = /^[A-Za-zÀ-ÿ\s'-]+$/;
    return regex.test(value);
}

function isAdult(dateValue) {
    const birthDate = new Date(dateValue);
    const today = new Date();

    if (isNaN(birthDate.getTime())) return false;

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age >= 18;
}

// Initialisation quand le DOM est prêt
function initSignupForm() {
    const form = document.getElementById("signupForm");
    const successBar = document.getElementById("successBar");

    if (!form) {
        console.log("Formulaire d'inscription non trouvé");
        return;
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        let isFormValid = true;

        const nin = document.getElementById("nin");
        const nom = document.getElementById("nom");
        const prenom = document.getElementById("prenom");
        const date = document.getElementById("date");
        const pere = document.getElementById("pere");
        const grandpere = document.getElementById("grandpere");
        const nomMere = document.getElementById("nomMere");
        const prenomMere = document.getElementById("prenomMere");
        const adresse = document.getElementById("adresse");
        const email = document.getElementById("email");
        const telephone = document.getElementById("telephone");
        const password = document.getElementById("password");

        const allFields = [
            { input: nin, error: "ninError", label: "Le NIN" },
            { input: nom, error: "nomError", label: "Le nom" },
            { input: prenom, error: "prenomError", label: "Le prénom" },
            { input: date, error: "dateError", label: "La date de naissance" },
            { input: pere, error: "pereError", label: "Le prénom du père" },
            { input: grandpere, error: "grandpereError", label: "Le prénom du grand-père" },
            { input: nomMere, error: "nomMereError", label: "Le nom de la mère" },
            { input: prenomMere, error: "prenomMereError", label: "Le prénom de la mère" },
            { input: adresse, error: "adresseError", label: "L'adresse" },
            { input: email, error: "emailError", label: "L'email" },
            { input: telephone, error: "telephoneError", label: "Le téléphone" },
            { input: password, error: "passwordError", label: "Le mot de passe" }
        ];

        allFields.forEach(field => {
            const value = field.input.value.trim();

            if (value === "") {
                setError(field.input, field.error, `${field.label} est obligatoire.`);
                isFormValid = false;
            } else {
                setSuccess(field.input, field.error);
            }
        });

        if (nin.value && !isValidNIN(nin.value)) {
            setError(nin, "ninError", "Le NIN doit contenir 18 chiffres.");
            isFormValid = false;
        }

        const nameFields = [
            { input: nom, error: "nomError", label: "Le nom" },
            { input: prenom, error: "prenomError", label: "Le prénom" },
            { input: pere, error: "pereError", label: "Le prénom du père" },
            { input: grandpere, error: "grandpereError", label: "Le prénom du grand-père" },
            { input: nomMere, error: "nomMereError", label: "Le nom de la mère" },
            { input: prenomMere, error: "prenomMereError", label: "Le prénom de la mère" }
        ];

        nameFields.forEach(field => {
            if (field.input.value && !isValidName(field.input.value)) {
                setError(field.input, field.error, `${field.label} ne doit contenir que des lettres.`);
                isFormValid = false;
            }
        });

        if (email.value && !isValidEmail(email.value)) {
            setError(email, "emailError", "Email invalide.");
            isFormValid = false;
        }

        if (telephone.value && !isValidPhone(telephone.value)) {
            setError(telephone, "telephoneError", "Numéro invalide.");
            isFormValid = false;
        }

        if (date.value && !isAdult(date.value)) {
            setError(date, "dateError", "Âge minimum 18 ans.");
            isFormValid = false;
        }

        if (password.value && password.value.length < 6) {
            setError(password, "passwordError", "Minimum 6 caractères.");
            isFormValid = false;
        }

        if (isFormValid) {
            if (successBar) {
                successBar.style.display = "block";

                setTimeout(() => {
                    successBar.style.display = "none";
                    form.submit();
                }, 800);
            } else {
                form.submit();
            }
        }
    });
}

// Attendre que le DOM soit complètement chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSignupForm);
} else {
    initSignupForm();
}