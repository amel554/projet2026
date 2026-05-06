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

    $identifier = trim($_POST["login"] ?? $_POST["email"] ?? "");
    $password = trim($_POST["password"]);

    if (empty($identifier)) {
        $errors[] = "Email ou NIN obligatoire.";
    }

    if (empty($password)) {
        $errors[] = "Mot de passe obligatoire.";
    }

    if (empty($errors)) {
        $sql = "SELECT * FROM user WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) OR TRIM(nin) = TRIM(?) LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_matches($password, $user["psswd"])) {

                if ($user["etat_compte"] == 2) {
                    $errors[] = "Votre compte est bloquÃ©.";
                } elseif ($user["etat_compte"] == 3) {
                    $errors[] = "Votre compte est en attente de validation.";
                } elseif ($user["etat_compte"] == 4) {
                    $errors[] = "Votre compte est supprimÃ©.";
                } else {
                    $_SESSION["nin"] = $user["nin"];
                    $_SESSION["nom"] = $user["nom"];
                    $_SESSION["prenom"] = $user["prenom"];
                    $_SESSION["prenom_pere"] = $user["prenom_pere"];
                    $_SESSION["nom_mere"] = $user["nom_mere"];
                    $_SESSION["prenom_mere"] = $user["prenom_mere"];
                    $_SESSION["date_naiss"] = $user["date_naiss"];
                    $_SESSION["adresse"] = $user["adresse"];
                    $_SESSION["tel"] = $user["tel"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["etat_compte"] = $user["etat_compte"];
                    $_SESSION["role"] = (int)$user["role"];
                    $_SESSION["is_admin"] = ((int)$user["role"] === 1);

                    if ((int)$user["role"] === 1) {
                        header("Location: admin.php");
                        exit();
                    } else {
                        header("Location: user.php");
                        exit();
                    }
                }

            } else {
                $errors[] = "Identifiant ou mot de passe incorrect.";
            }
        } else {
            $errors[] = "Identifiant ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="page">
        <div class="login-card">

            <div class="top-text">
                <p>RÃ©publique AlgÃ©rienne DÃ©mocratique et Populaire</p>
                <p>Plateforme officielle du tirage au sort</p>
            </div>

            <div class="logo-box">
                <img src="logo.png" alt="Logo Hadj">
            </div>

            <h1>Connexion</h1>
            <p class="subtitle">AccÃ©dez Ã  votre espace utilisateur avec votre email ou votre NIN</p>

            <?php if (!empty($errors)): ?>
                <div class="php-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login.php">

                <div class="input-group">
                    <label for="login">Email ou NIN</label>
                    <input type="text" id="login" name="login" placeholder="Entrez votre email ou votre NIN">
                    <span id="loginError" class="error"></span>
                </div>

                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe">
                    <span id="passwordError" class="error"></span>
                </div>

                <button type="submit">Se connecter</button>
            </form>

            <div class="bottom-links">
                <a href="index.html">Retour Ã  l'accueil</a>
                <a href="signup.php">CrÃ©er un compte</a>
            </div>

        </div>
    </div>

    <script src="script/login.js"></script>
</body>
</html>
