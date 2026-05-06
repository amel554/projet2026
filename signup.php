<?php
session_start();
require_once "includes/fonctions.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once "includes/db.php";
    $conn = db_connect();

    if (!$conn) {
        $errors[] = "Connexion a la base de donnees impossible.";
    }

    $nin = clean($_POST["nin"]);
    $nom = clean($_POST["nom"]);
    $prenom = clean($_POST["prenom"]);
    $date = clean($_POST["date"]);
    $pere = clean($_POST["pere"]);
    $grandpere = clean($_POST["grandpere"]);
    $nomMere = clean($_POST["nomMere"]);
    $prenomMere = clean($_POST["prenomMere"]);
    $adresse = clean($_POST["adresse"]);
    $email = clean($_POST["email"]);
    $telephone = clean($_POST["telephone"]);
    $password = clean($_POST["password"]);

    if (empty($nin) || !preg_match('/^[0-9]{18}$/', $nin)) {
        $errors[] = "Le NIN doit contenir 18 chiffres.";
    }

    if (empty($nom) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $nom)) {
        $errors[] = "Le nom est invalide.";
    }

    if (empty($prenom) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $prenom)) {
        $errors[] = "Le prénom est invalide.";
    }

    if (empty($date)) {
        $errors[] = "La date de naissance est obligatoire.";
    } else {
        $birthDate = new DateTime($date);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        if ($age < 18) {
            $errors[] = "Âge minimum 18 ans.";
        }
    }

    if (empty($pere) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $pere)) {
        $errors[] = "Le prénom du père est invalide.";
    }

    if (empty($grandpere) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $grandpere)) {
        $errors[] = "Le prénom du grand-père est invalide.";
    }

    if (empty($nomMere) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $nomMere)) {
        $errors[] = "Le nom de la mère est invalide.";
    }

    if (empty($prenomMere) || !preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/u', $prenomMere)) {
        $errors[] = "Le prénom de la mère est invalide.";
    }

    if (empty($adresse)) {
        $errors[] = "L'adresse est obligatoire.";
    }

    if (empty($email) || !is_valid_email($email)) {
        $errors[] = "Email invalide.";
    } else {
        $sql = "SELECT email FROM user WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($telephone) || !preg_match('/^(0)(5|6|7)[0-9]{8}$/', $telephone)) {
        $errors[] = "Numéro invalide.";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Minimum 6 caractères.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 2;
        $etat_compte = 3;

        $sql = "INSERT INTO user (nin, nom, prenom, prenom_pere, nom_mere, prenom_mere, date_naiss, adresse, email, tel, psswd, etat_compte, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssssssii", $nin, $nom, $prenom, $pere, $nomMere, $prenomMere, $date, $adresse, $email, $telephone, $hashed_password, $etat_compte, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Erreur lors de l'inscription.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte</title>
    <link rel="stylesheet" href="signup.css">
</head>
<body>

    <div class="page">
        <div class="signup-card">

            <div class="top-text">
                <p>République Algérienne Démocratique et Populaire</p>
                <p>Plateforme officielle du tirage au sort</p>
            </div>

            <div class="logo-box">
                <img src="logo.png" alt="Logo Hadj">
            </div>

            <h1>Créer un compte</h1>
            <p class="subtitle">Inscription à la plateforme</p>

            <?php if (!empty($errors)): ?>
                <div class="php-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="signupForm" method="POST" action="signup.php">

    <div class="input-group">
        <label for="nin">Numéro d’identification national (NIN)</label>
        <input type="text" id="nin" name="nin" placeholder="Votre NIN">
        <span id="ninError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" placeholder="Votre nom">
        <span id="nomError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Votre prénom">
        <span id="prenomError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="date">Date de naissance</label>
        <input type="date" id="date" name="date">
        <span id="dateError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="pere">Prénom du père</label>
        <input type="text" id="pere" name="pere" placeholder="Prénom du père">
        <span id="pereError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="grandpere">Prénom du grand-père</label>
        <input type="text" id="grandpere" name="grandpere" placeholder="Prénom du grand-père">
        <span id="grandpereError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="nomMere">Nom de la mère</label>
        <input type="text" id="nomMere" name="nomMere" placeholder="Nom de la mère">
        <span id="nomMereError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="prenomMere">Prénom de la mère</label>
        <input type="text" id="prenomMere" name="prenomMere" placeholder="Prénom de la mère">
        <span id="prenomMereError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="adresse">Adresse physique</label>
        <input type="text" id="adresse" name="adresse" placeholder="Votre adresse">
        <span id="adresseError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Votre email">
        <span id="emailError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="telephone">Numéro de téléphone</label>
        <input type="text" id="telephone" name="telephone" placeholder="Numéro de téléphone">
        <span id="telephoneError" class="error"></span>
    </div>

    <div class="input-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="Mot de passe">
        <span id="passwordError" class="error"></span>
    </div>

    <button type="submit">S'inscrire</button>

            </form>

            <div class="bottom-links">
                <a href="index.html">Retour à l'accueil</a>
                <a href="login.php">Se connecter</a>
            </div>

        </div>
    </div>

    <script src="script/signup.js"></script>
</body>
</html>
