<?php
session_start();
require_once "includes/db.php";

$conn = db_connect_or_fail();

if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

function display_value($value) {
    return $value !== null && $value !== "" ? htmlspecialchars($value) : "Non renseigné";
}

function account_status_label($etat) {
    if ($etat == 1) return "Compte actif";
    if ($etat == 2) return "Compte bloqué";
    if ($etat == 3) return "Compte en attente de validation";
    if ($etat == 4) return "Compte supprimé";
    return "État inconnu";
}

$email = $_SESSION["email"];

$sql = "SELECT nin, nom, prenom, prenom_pere, nom_mere, prenom_mere, date_naiss, adresse, email, tel, etat_compte, role 
        FROM user 
        WHERE email = ? 
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

$message_inscription = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["inscrire_tirage"])) {
    $nin = $user["nin"];

    $tirage_sql = "SELECT * FROM tirage ORDER BY id_tirage DESC LIMIT 1";

    $tirage_result = mysqli_query($conn, $tirage_sql);
 
    if ($tirage_result && mysqli_num_rows($tirage_result) > 0) {
        $tirage = mysqli_fetch_assoc($tirage_result);
        $id_tirage = $tirage["id_tirage"];

        $check_sql = "SELECT * FROM inscrits WHERE nin = ? AND id_tirage = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $nin, $id_tirage);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $message_inscription = "Vous êtes déjà inscrit à ce tirage.";
        } else {
            $insert_sql = "INSERT INTO inscrits (nin, id_tirage, date_inscription)
                           VALUES (?, ?, CURDATE())";

            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "si", $nin, $id_tirage);

            if (mysqli_stmt_execute($insert_stmt)) {
                $message_inscription = "Inscription au tirage effectuée avec succès.";
            } else {
                $message_inscription = "Erreur insertion : " . mysqli_error($conn);
            }
        }
    } else {
        $message_inscription = "Aucun tirage trouvé. L'administrateur doit d'abord créer un tirage.";
    }
}

$sql_notif = "SELECT * FROM notifications WHERE nin = ? ORDER BY id_notification DESC";
$stmt_notif = mysqli_prepare($conn, $sql_notif);
mysqli_stmt_bind_param($stmt_notif, "s", $user["nin"]);
mysqli_stmt_execute($stmt_notif);
$notifications = mysqli_stmt_get_result($stmt_notif);

$fullName = trim($user["prenom"] . " " . $user["nom"]);
$birthDate = !empty($user["date_naiss"]) ? date("d/m/Y", strtotime($user["date_naiss"])) : "Non renseignée";
$accountState = (int)$user["etat_compte"];
$accountMessage = account_status_label($accountState);
$canRegister = $accountState === 1;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Utilisateur</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>

<header class="topbar">
    <div class="brand">
        <img src="logo.png" alt="Logo Hadj">
        <div class="brand-text">
            <h1>Plateforme du Tirage au Sort du Hadj</h1>
            <p>Espace utilisateur</p>
        </div>
    </div>

    <nav class="nav">
        <a href="#profil">Profil</a>
        <a href="#notifications">Notifications</a>
        <a href="#tirage">Tirage</a>
        <a href="logout.php" class="logout-nav">Déconnexion</a>
    </nav>
</header>

<main class="container">

    <section class="welcome-card">
        <div class="welcome-text">
            <h2>Bienvenue, <?php echo htmlspecialchars($fullName); ?></h2>
            <p class="subtitle">Espace personnel utilisateur</p>
            <p>
                Cet espace permet de consulter votre profil, recevoir les notifications
                importantes et vous inscrire au tirage actif si les conditions sont remplies.
            </p>
        </div>
    </section>

    <section class="dashboard-grid">

        <div class="card" id="profil">
            <h3>Mon profil</h3>
            <div class="info-list">
                <p><span>NIN :</span> <?php echo display_value($user["nin"]); ?></p>
                <p><span>Nom :</span> <?php echo display_value($user["nom"]); ?></p>
                <p><span>Prénom :</span> <?php echo display_value($user["prenom"]); ?></p>
                <p><span>Prénom du père :</span> <?php echo display_value($user["prenom_pere"]); ?></p>
                <p><span>Nom de la mère :</span> <?php echo display_value($user["nom_mere"]); ?></p>
                <p><span>Prénom de la mère :</span> <?php echo display_value($user["prenom_mere"]); ?></p>
                <p><span>Date de naissance :</span> <?php echo display_value($birthDate); ?></p>
                <p><span>Adresse :</span> <?php echo display_value($user["adresse"]); ?></p>
                <p><span>Email :</span> <?php echo display_value($user["email"]); ?></p>
                <p><span>Téléphone :</span> <?php echo display_value($user["tel"]); ?></p>
                <p><span>État du compte :</span> <?php echo htmlspecialchars($accountMessage); ?></p>
            </div>
        </div>

        <div class="card" id="notifications">
            <h3>Notifications</h3>

            <div class="notification-list">
                <?php if ($notifications && mysqli_num_rows($notifications) > 0): ?>
                    <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
                        <div class="notification-item">
                            <span class="notification-title">Notification système</span>
                            <p><?php echo htmlspecialchars($n["message"]); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-item">
                        <p>Aucune notification pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" id="tirage">
            <h3>Tirage actif</h3>

            <div class="info-list">
                <p><span>Saison :</span> Hadj 2026</p>
                <p><span>État des inscriptions :</span> Ouvertes</p>
                <p><span>Condition du compte :</span> <?php echo htmlspecialchars($accountMessage); ?></p>
            </div>

            <?php if ($canRegister): ?>
                <div class="status success">
                    Vous pouvez participer au tirage actif.
                </div>
                <?php if (!empty($message_inscription)): ?>
                    <div class="message-box">
                        <?php echo htmlspecialchars($message_inscription); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="user.php">
                    <button type="submit" name="inscrire_tirage" class="btn btn-gold">
                        S’inscrire au tirage
                    </button>
                </form>
            <?php else: ?>
                <div class="status warning">
                    Votre compte doit être validé par l’administrateur avant l’inscription au tirage.
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Message important</h3>
            <div class="message-box">
                Veuillez consulter régulièrement votre espace utilisateur.
                Toute mise à jour concernant le tirage ou votre dossier sera affichée dans les notifications.
            </div>
        </div>

    </section>

</main>

</body>
</html>
