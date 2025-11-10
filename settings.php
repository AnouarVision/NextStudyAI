<?php
    session_start();

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

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

    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = true;
    }

    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    try {
        $stmt = $db->prepare("SELECT username, email, account_status, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new Exception("Utente non trovato.");
    } catch (Exception $e) {
        die("Errore nel caricamento dati utente.");
    }

    $message = '';
    $message_type = 'success';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($username === '' || $email === '') {
            $message = "Tutti i campi obbligatori devono essere compilati.";
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Inserisci un'email valida.";
            $message_type = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $message = "Il nome utente deve contenere 3-50 caratteri alfanumerici o underscore.";
            $message_type = 'error';
        } elseif ($new_password !== '' && $new_password !== $confirm_password) {
            $message = "Le password non coincidono.";
            $message_type = 'error';
        } elseif ($new_password !== '' && !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $new_password)) {
            $message = "La password deve essere almeno 8 caratteri e contenere lettere e numeri.";
            $message_type = 'error';
        } else {
            try {
                if ($new_password !== '') {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $hashed_password, $_SESSION['user_id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $_SESSION['user_id']]);
                }
                $message = "Impostazioni aggiornate con successo!";
                $message_type = 'success';
                header("Location: dashboard.php");
            } catch (PDOException $e) {
                $message = "Errore durante l'aggiornamento, riprova più tardi.";
                $message_type = 'error';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni account - NextStudy</title>
    <link rel="stylesheet" href="assets/css/settings.css">
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
</head>
<body>
<div class="settings-container">
<h2>Impostazioni Account</h2>
<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
    <label for="username">Nome utente</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
    <label for="new_password">Nuova password (lascia vuoto per non cambiare)</label>
    <input type="password" id="new_password" name="new_password" autocomplete="new-password">
    <label for="confirm_password">Conferma nuova password</label>
    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
    <button type="submit">Salva modifiche</button>
</form>
</div>

<?php if ($message): ?>
    <div class="toast-message <?= $message_type === 'success' ? 'toast-success' : 'toast-error' ?>" id="toastMessage">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<script src="assets/js/settings.js"></script>
</body>
</html>
