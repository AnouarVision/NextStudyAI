<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Save form data to the session
    $_SESSION['quiz_request'] = $_POST;

    header("Location: quiz_loading.php");
    exit;
?>