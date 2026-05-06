<?php
require_once __DIR__ . "/includes/db.php";

$conn = db_connect_or_fail();

$create_table_sql = "CREATE TABLE IF NOT EXISTS draw_configuration (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_gagnants INT NOT NULL DEFAULT 50,
    date_tirage DATE,
    date_ouverture DATE,
    date_fermeture DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_table_sql)) {
    echo "Table 'draw_configuration' creee avec succes !<br>";

    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM draw_configuration");
    $result = mysqli_fetch_assoc($check);

    if ($result["count"] == 0) {
        $default_date = date("Y-m-d", strtotime("+47 days"));
        $default_open = date("Y-m-d", strtotime("+17 days"));
        $default_close = date("Y-m-d", strtotime("+32 days"));

        $insert_sql = "INSERT INTO draw_configuration (nombre_gagnants, date_tirage, date_ouverture, date_fermeture)
                       VALUES (50, '$default_date', '$default_open', '$default_close')";

        if (mysqli_query($conn, $insert_sql)) {
            echo "Configuration par defaut inseree !<br>";
        }
    }

    echo "<br><strong>La base de donnees est prete. Vous pouvez maintenant utiliser la page admin.php</strong>";
} else {
    echo "Erreur lors de la creation de la table : " . mysqli_error($conn);
}

mysqli_close($conn);
?>
