<?php
    session_start();

    require_once 'DbConnector.php';
    require_once __DIR__ . '/config/env.php';

    loadEnv(__DIR__ . '/.env');

    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $message = "";
    $success = false;

    $rateLimitSeconds = 60;
    $canSubmit = true;

    $stmt = $db->prepare("SELECT created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $lastTicket = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastTicket) {
        $lastTime = strtotime($lastTicket['created_at']);
        if (time() - $lastTime < $rateLimitSeconds) {
            $remaining = $rateLimitSeconds - (time() - $lastTime);
            $message = "Attendi ancora $remaining secondi prima di inviare un'altra segnalazione.";
            $success = false;
            $canSubmit = false;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["csrf_token"]) &&
        hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"]) &&
        $canSubmit) {

        $subject = substr(trim($_POST["subject"] ?? ""), 0, 100);
        $content = substr(trim($_POST["message"] ?? ""), 0, 1000);
        $posted_username = trim($_POST["username"] ?? '');

        if ($posted_username !== $user['username']) {
            $message = "Errore: utente non valido.";
            $success = false;
        } elseif ($subject === "" || $content === "") {
            $message = "Per favore, compila tutti i campi.";
            $success = false;
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION["user_id"], $subject, $content]);
                $success = true;
                $message = "Segnalazione inviata con successo! Un amministratore ti risponderà al più presto.";

                header("Location: dashboard.php");
                exit;
            } catch (PDOException $e) {
                $message = "Errore durante l'invio: " . htmlspecialchars($e->getMessage());
                $success = false;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assistenza - NextStudy</title>
<link rel="stylesheet" href="assets/css/help.css">
<link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
</head>
<body>
    <div class="help-wrapper">
        <div class="help-container">
            <h2>Richiedi assistenza</h2>

            <?php if ($message): ?>
                <div class="message <?= $success ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <label for="username">Utente</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" readonly>

                <label for="subject">Oggetto</label>
                <input type="text" id="subject" name="subject" placeholder="Es: Problema con la dashboard" required>

                <label for="message">Descrizione del problema</label>
                <textarea id="message" name="message" placeholder="Descrivi il problema nel dettaglio..." required></textarea>

                <button type="submit">Invia segnalazione</button>
            </form>
        </div>
    </div>
</body>
</html>
