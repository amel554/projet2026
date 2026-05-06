<?php
function clean($data) {
    return trim(htmlspecialchars($data));
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_phone($tel) {
    return preg_match("/^(0)(5|6|7)[0-9]{8}$/", $tel);
}

function is_valid_nin($nin) {
    return preg_match("/^[0-9]{18}$/", $nin);
}

function is_valid_name($name) {
    return preg_match("/^[A-Za-zÀ-ÿ\s'-]+$/", $name);
}

function is_adult($date_naiss) {
    $birth = new DateTime($date_naiss);
    $today = new DateTime();
    return $today->diff($birth)->y >= 18;
}

function normalize_role($role) {
    $value = strtolower(trim((string)$role));

    if ($value === '1' || $value === 'admin' || $value === 'administrator' || $value === 'administrateur') {
        return 'admin';
    }

    return 'user';
}

function password_matches($inputPassword, $storedPassword) {
    $storedPassword = (string)$storedPassword;
    $inputPassword = (string)$inputPassword;

    if ($storedPassword === '') {
        return false;
    }

    if (preg_match('/^\$(2y|argon2id|argon2i|argon2)\$/', $storedPassword)) {
        return password_verify($inputPassword, $storedPassword);
    }

    return hash_equals($storedPassword, trim($inputPassword));
}

function account_status_label($status) {
    switch ((int)$status) {
        case 1:
            return 'Actif';
        case 2:
            return 'Bloqué';
        case 3:
            return 'En attente';
        case 4:
            return 'Supprimé';
        default:
            return 'Inconnu';
    }
}

function tirage_status_label($status) {
    switch ((int)$status) {
        case 1:
            return 'Planifié';
        case 2:
            return 'Effectué';
        case 3:
            return 'Inscriptions ouvertes';
        case 4:
            return 'Inscriptions fermées';
        default:
            return 'Inconnu';
    }
}

function envoyer_notification_tous($conn, $message) {
    $users = mysqli_query($conn, "SELECT nin FROM user WHERE role = 2 AND etat_compte = 1");

    while ($u = mysqli_fetch_assoc($users)) {
        $sql = "INSERT INTO notifications (nin, message, etat_notification) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $u["nin"], $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>