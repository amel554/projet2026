<?php
mysqli_report(MYSQLI_REPORT_OFF);

function db_connect() {
    static $conn = null;
    static $connectionAttempted = false;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    if ($connectionAttempted) {
        return false;
    }

    $connectionAttempted = true;

    $probe = @fsockopen("127.0.0.1", 3306, $errno, $errstr, 1);

    if ($probe === false) {
        return false;
    }

    fclose($probe);

    $conn = mysqli_init();

    if ($conn === false) {
        return false;
    }

    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

    $connected = @mysqli_real_connect(
        $conn,
        "127.0.0.1",
        "root",
        "",
        "hadj_platform",
        3306
    );

    if (!$connected) {
        return false;
    }

    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

function db_connect_or_fail() {
    $conn = db_connect();

    if (!$conn) {
        die("Erreur de connexion a la base de donnees : " . mysqli_connect_error());
    }

    return $conn;
}
?>
