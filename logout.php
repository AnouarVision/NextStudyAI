<?php
    session_start();

    require_once 'Auth.php';

    $auth = new Auth();

    // Update last login if the user was logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $auth->getDb()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            echo "Attenzione: impossibile aggiornare l'ultimo accesso. Riprova più tardi.";
        }
    }

    $auth->logout();

    header("Location: login.php");
    exit;
?>
