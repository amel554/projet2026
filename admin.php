<?php
session_start();
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/fonctions.php";

$conn = db_connect_or_fail();

if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

if ((int)($_SESSION["role"] ?? 0) !== 1) {
    header("Location: user.php");
    exit();
}

$message_tirage = "";

$create_notifications_sql = "CREATE TABLE IF NOT EXISTS notifications (
    id_notification INT PRIMARY KEY AUTO_INCREMENT,
    nin VARCHAR(18) NOT NULL,
    message TEXT NOT NULL,
    etat_notification TINYINT NOT NULL DEFAULT 1,
    date_notification DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_notifications_sql);

$required_notification_columns = [
    "nin" => "ALTER TABLE notifications ADD COLUMN nin VARCHAR(18) NOT NULL DEFAULT ''",
    "message" => "ALTER TABLE notifications ADD COLUMN message TEXT NOT NULL",
    "etat_notification" => "ALTER TABLE notifications ADD COLUMN etat_notification TINYINT NOT NULL DEFAULT 1",
    "date_notification" => "ALTER TABLE notifications ADD COLUMN date_notification DATETIME DEFAULT CURRENT_TIMESTAMP"
];

foreach ($required_notification_columns as $column_name => $alter_sql) {
    $column_check_sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'notifications'
                         AND COLUMN_NAME = '" . mysqli_real_escape_string($conn, $column_name) . "'
                         LIMIT 1";
    $column_check_result = mysqli_query($conn, $column_check_sql);

    if (!$column_check_result || mysqli_num_rows($column_check_result) === 0) {
        mysqli_query($conn, $alter_sql);
    }
}

if (isset($_GET["action"]) && isset($_GET["nin"])) {
    $nin = $_GET["nin"];

    if ($_GET["action"] == "valider") {
        $sql = "UPDATE user SET etat_compte = 1 WHERE nin = ?";
    } elseif ($_GET["action"] == "bloquer") {
        $sql = "UPDATE user SET etat_compte = 2 WHERE nin = ?";
    } elseif ($_GET["action"] == "supprimer") {
        $sql = "UPDATE user SET etat_compte = 4 WHERE nin = ?";
    }

    if (isset($sql)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $nin);
        mysqli_stmt_execute($stmt);
    }

    header("Location: admin.php");
    exit();
}

$message_tirage_exec = "";

if (isset($_POST["lancer_tirage"])) {

    $tirage_sql = "SELECT * FROM tirage ORDER BY id_tirage DESC LIMIT 1";
    $tirage_result = mysqli_query($conn, $tirage_sql);

    if ($tirage_result && mysqli_num_rows($tirage_result) > 0) {

        $tirage = mysqli_fetch_assoc($tirage_result);
        $id_tirage = $tirage["id_tirage"];
        $nbr_gagnants = $tirage["nbr_gagnants"];

        $inscrits_sql = "SELECT nin FROM inscrits WHERE id_tirage = ?";
        $stmt = mysqli_prepare($conn, $inscrits_sql);
        mysqli_stmt_bind_param($stmt, "i", $id_tirage);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $participants = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $participants[] = $row["nin"];
        }

        if (count($participants) == 0) {
            $message_tirage_exec = "Aucun inscrit pour ce tirage.";
        } else {

            shuffle($participants);
            $gagnants = array_slice($participants, 0, $nbr_gagnants);

            foreach ($gagnants as $nin) {

                $check_sql = "SELECT * FROM resultats WHERE nin = ? AND id_tirage = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $nin, $id_tirage);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($check_result) == 0) {
                    $insert_sql = "INSERT INTO resultats (nin, id_tirage) VALUES (?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($insert_stmt, "si", $nin, $id_tirage);
                    mysqli_stmt_execute($insert_stmt);
                }
            }

            mysqli_query($conn, "UPDATE tirage SET etat_tirage = 2 WHERE id_tirage = $id_tirage");

            // notification automatique
            $message = "Les résultats du tirage sont maintenant disponibles.";
            $users = mysqli_query($conn, "SELECT nin FROM user WHERE role = 2");

            while ($u = mysqli_fetch_assoc($users)) {
                $sql = "INSERT INTO notifications (nin, message, etat_notification) VALUES (?, ?, 1)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $u["nin"], $message);
                mysqli_stmt_execute($stmt);
            }

            $message_tirage_exec = "Tirage effectué avec succès.";
        }

    } else {
        $message_tirage_exec = "Aucun tirage trouvé.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["creer_tirage"])) {
    $nbr_gagnants = (int) $_POST["nbr_gagnants"];
    $date_tirage = $_POST["date_tirage"];
    $date_ouverture = $_POST["date_ouverture"];
    $date_fermeture = $_POST["date_fermeture"];

    if ($nbr_gagnants <= 0) {
        $message_tirage = "Le nombre de gagnants doit être supérieur à 0.";
    } elseif (empty($date_tirage) || empty($date_ouverture) || empty($date_fermeture)) {
        $message_tirage = "Tous les champs sont obligatoires.";
    } elseif ($date_ouverture >= $date_fermeture) {
        $message_tirage = "La date d'ouverture doit être avant la date de fermeture.";
    } elseif ($date_fermeture >= $date_tirage) {
        $message_tirage = "La date de fermeture doit être avant la date du tirage.";
    } else {
        $sql = "INSERT INTO tirage 
                (date_ouverture_insc, date_cloture_insc, date_tirage, nbr_gagnants, etat_tirage)
                VALUES (?, ?, ?, ?, 1)";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $date_ouverture, $date_fermeture, $date_tirage, $nbr_gagnants);

            if (mysqli_stmt_execute($stmt)) {
                $message_tirage = "Tirage créé avec succès.";

                $message1 = "Les inscriptions sont ouvertes pour le tirage du Hadj.";
                envoyer_notification_tous($conn, $message1);

                $message2 = "La date du tirage au sort est fixée au " . date("d/m/Y", strtotime($date_tirage)) . ".";
                envoyer_notification_tous($conn, $message2);
            } else {
                $message_tirage = "Erreur d'insertion : " . mysqli_error($conn);
            }
        } else {
            $message_tirage = "Erreur SQL : " . mysqli_error($conn);
        }
    }
}

$count_attente = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE etat_compte = 3 AND role = 2"))["total"];
$count_actifs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE etat_compte = 1 AND role = 2"))["total"];
$count_bloques = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE etat_compte = 2 AND role = 2"))["total"];

$users = mysqli_query($conn, "SELECT * FROM user WHERE role = 2 ORDER BY nom ASC");
$tirage_result = mysqli_query($conn, "SELECT * FROM tirage ORDER BY id_tirage DESC LIMIT 1");
$tirage = mysqli_fetch_assoc($tirage_result);

$notifications_result = mysqli_query($conn, "SELECT message, date_notification FROM notifications ORDER BY id_notification DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Administrateur</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<header class="topbar">
    <div class="brand">
        <img src="logo.png" alt="Logo Hadj">
        <div class="brand-text">
            <h1>Plateforme du Tirage au Sort du Hadj</h1>
            <p>Espace administrateur</p>
        </div>
    </div>

    <nav class="nav">
        <a href="#utilisateurs">Utilisateurs</a>
        <a href="#parametrage">Paramétrage</a>
        <a href="#tirage">Tirage</a>
        <a href="#resultats">Résultats</a>
        <a href="#notifications">Notifications</a>
        <a href="logout.php" class="logout-nav">Déconnexion</a>
    </nav>
</header>

<main class="container">

    <section class="welcome-card">
        <div class="welcome-text">
            <h2>Bienvenue, Administrateur</h2>
            <p class="subtitle">Tableau de bord de gestion</p>
            <p>
                Cet espace permet de gérer les comptes utilisateurs, configurer le tirage,
                lancer le tirage au sort, consulter les résultats et suivre les notifications.
            </p>
        </div>

        <div class="draw-date">
            <span>Date du tirage</span>
            <strong>
                <?php echo $tirage ? date("d/m/Y", strtotime($tirage["date_tirage"])) : "À configurer"; ?>
            </strong>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stat-card">
            <h3>Comptes en attente</h3>
            <p><?php echo $count_attente; ?></p>
        </div>

        <div class="stat-card">
            <h3>Comptes validés</h3>
            <p><?php echo $count_actifs; ?></p>
        </div>

        <div class="stat-card">
            <h3>Comptes bloqués</h3>
            <p><?php echo $count_bloques; ?></p>
        </div>

        <div class="stat-card">
            <h3>Gagnants prévus</h3>
            <p><?php echo $tirage ? htmlspecialchars($tirage["nbr_gagnants"]) : "0"; ?></p>
        </div>
    </section>

    <section class="dashboard-grid">

        <div class="card" id="utilisateurs">
            <h3>Gestion des comptes utilisateurs</h3>

            <?php if ($users && mysqli_num_rows($users) > 0): ?>
                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <div class="message-box">
                        <div class="info-list">
                            <p><span>NIN :</span> <?php echo htmlspecialchars($u["nin"]); ?></p>
                            <p><span>Nom :</span> <?php echo htmlspecialchars($u["nom"] . " " . $u["prenom"]); ?></p>
                            <p><span>Email :</span> <?php echo htmlspecialchars($u["email"]); ?></p>
                            <p><span>État :</span> <?php echo htmlspecialchars(account_status_label($u["etat_compte"])); ?></p>
                        </div>

                        <div class="action-group">
                            <a class="btn btn-gold" href="admin.php?action=valider&nin=<?php echo urlencode($u["nin"]); ?>">Valider</a>
                            <a class="btn btn-light" href="admin.php?action=bloquer&nin=<?php echo urlencode($u["nin"]); ?>">Bloquer</a>
                            <a class="btn btn-danger" href="admin.php?action=supprimer&nin=<?php echo urlencode($u["nin"]); ?>">Supprimer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="message-box">Aucun utilisateur trouvé.</div>
            <?php endif; ?>
        </div>

        <div class="card" id="parametrage">
            <h3>Paramétrage des tirages</h3>

            <?php if (!empty($message_tirage)): ?>
                <div class="message-box">
                    <?php echo htmlspecialchars($message_tirage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin.php" class="tirage-form">
                <div class="input-group">
                    <label>Nombre de gagnants</label>
                    <input type="number" name="nbr_gagnants" min="1" required>
                </div>

                <div class="input-group">
                    <label>Date du tirage</label>
                    <input type="date" name="date_tirage" required>
                </div>

                <div class="input-group">
                    <label>Date d'ouverture des inscriptions</label>
                    <input type="date" name="date_ouverture" required>
                </div>

                <div class="input-group">
                    <label>Date de fermeture des inscriptions</label>
                    <input type="date" name="date_fermeture" required>
                </div>

                <button type="submit" name="creer_tirage" class="btn btn-gold">
                    Créer le tirage
                </button>
            </form>

            <div class="status success">
                Ordre logique : nombre de gagnants → date du tirage → dates d’inscription.
            </div>
        </div>

        <div class="card" id="tirage">
            <h3>Lancement du tirage au sort</h3>

            <div class="info-list">
                <p><span>Nombre de gagnants :</span> <?php echo $tirage ? htmlspecialchars($tirage["nbr_gagnants"]) : "Non configuré"; ?></p>
                <p><span>Date du tirage :</span> <?php echo $tirage ? date("d/m/Y", strtotime($tirage["date_tirage"])) : "Non configurée"; ?></p>
                <p><span>Ouverture :</span> <?php echo $tirage ? date("d/m/Y", strtotime($tirage["date_ouverture_insc"])) : "Non configurée"; ?></p>
                <p><span>Fermeture :</span> <?php echo $tirage ? date("d/m/Y", strtotime($tirage["date_cloture_insc"])) : "Non configurée"; ?></p>
                <p><span>État :</span> <?php echo $tirage ? htmlspecialchars(tirage_status_label($tirage["etat_tirage"])) : "Non configuré"; ?></p>
            </div>

            <?php if (!empty($message_tirage_exec)): ?>
                <div class="message-box">
                    <?php echo htmlspecialchars($message_tirage_exec); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin.php">
                <button type="submit" name="lancer_tirage" class="btn btn-gold">
                    Lancer le tirage
                </button>
            </form>
        </div>

        <div class="card" id="resultats">
            <h3>Consultation des résultats</h3>
            <div class="info-list">
                <p><span>Dernier tirage :</span> Hadj 2026</p>
                <p><span>Publication :</span> Prévue après le tirage</p>
                <p><span>Liste des gagnants :</span> Non publiée</p>
            </div>

            <a href="#" class="btn btn-light">Voir les résultats</a>
        </div>

        <div class="card" id="notifications">
            <h3>Notifications</h3>
            <?php if ($notifications_result && mysqli_num_rows($notifications_result) > 0): ?>
                <div class="info-list">
                    <?php while ($notif = mysqli_fetch_assoc($notifications_result)): ?>
                        <div class="message-box" style="margin-bottom:10px;">
                            <p><span>Notification système</span></p>
                            <p><?php echo htmlspecialchars($notif["message"]); ?></p>
                            <p style="font-size:12px;opacity:0.8;">
                                Envoyé le <?php echo date("d/m/Y H:i", strtotime($notif["date_notification"])); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="message-box">
                    Aucune notification envoyée pour le moment.
                </div>
            <?php endif; ?>
        </div>

    </section>

</main>

</body>
</html>
