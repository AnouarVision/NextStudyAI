<?php
session_start();

require_once __DIR__ . '/config/env.php';
require_once 'DbConnector.php';

loadEnv(__DIR__ . '/.env');

$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbName = getenv('DB_NAME');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF non valido.");
    }

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo "Tutti i campi sono obbligatori.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Formato email non valido.";
        exit;
    }

    if (strlen($password) < 8) {
        echo "La password deve contenere almeno 8 caratteri.";
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        echo "L'username deve contenere solo lettere, numeri e underscore (3-20 caratteri).";
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8",$dbUser,$dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo "Questa email è già registrata!";
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            echo "Questo username è già in uso!";
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Transaction ensures consistency between 'users' and 'profile' tables
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hashedPassword]);

        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO profile (user_id) VALUES (?)');
        $stmt->execute([$user_id]);

        $pdo->commit();

        $_SESSION['user_id'] = $user_id;
        header("Location: dashboard.php");
        exit;

    }
    catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Si è verificato un errore imprevisto. Riprova più tardi.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <title>Registrati</title>
</head>
<body>
    <form action="register.php" method="post">
        <header class="form-header">
            <h1 id="title-form">Crea il tuo account</h1>
            <p>Inserisci le tue credenziali o accedi.</p>
        </header>

        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="MarioRossi" required>
        </div>

        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="mario.rossi@esempio.it" required>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="input-group checkbox-group">
            <input type="checkbox" id="privacy" name="privacy" required>
            <label for="privacy">
                Accetto la <a href="privacy.html" target="_blank">Privacy Policy</a>
            </label>
        </div>

        <button type="submit" class="submit-btn">Registrati</button>
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div id="error-message"></div>

        <footer>
            <p>Hai già un account? <a href="login.php">Accedi</a></p>
        </footer>
    </form>
</body>
</html>
