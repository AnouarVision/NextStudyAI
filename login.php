<?php
    session_start();
    require_once 'Auth.php';

    $auth = new Auth();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];

    function redirectUser(string $role) {
        header($role === 'admin' ? "Location: admin_dashboard.php" : "Location: dashboard.php");
        exit;
    }

    if (isset($_SESSION['user_id'], $_SESSION['role'])) {
        redirectUser($_SESSION['role']);
    }

    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die("Token CSRF non valido.");
        }

        // Temporarily locks the account after too many failed login attempts
        if ($auth->isLockedOut()) {
            $error_message = "Troppi tentativi falliti. Riprova tra qualche minuto.";
        }
        else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error_message = "Inserisci email e password.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Formato email non valido.";
            }
            else
            {
                try
                {
                    $user = $auth->login($email, $password);

                    if (!$user) {
                        $auth->incrementLoginAttempts();
                        $error_message = "Email o password errati.";
                    }
                    else {
                        // Checks the account status before allowing access
                        if ($user['account_status'] === 'suspended') {
                            $error_message = "Il tuo account è stato sospeso. Contatta l'amministratore per assistenza.";
                        } elseif ($user['account_status'] === 'deleted') {
                            $error_message = "Questo account non esiste più.";
                        }
                        else {
                            $auth->resetLoginAttempts();

                            // Regenerate the session ID to prevent session fixation
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['role'] = $user['role'];

                            redirectUser($user['role']);
                        }
                    }

                } catch (PDOException $e) {
                    $error_message = "Servizio temporaneamente non disponibile. Riprova più tardi.";
                }
            }
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
<title>Accedi - NextStudy AI</title>
</head>
<body>
    <form action="login.php" method="post">
        <header class="form-header">
            <h1 id="title-form">Accedi al tuo account</h1>
            <p>Accedi con le tue credenziali o registrati.</p>
        </header>
        <?php if (!empty($error_message)): ?>
            <div id="error-message" style="color: red; margin-bottom: 3px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="mario.rossi@esempio.it" required>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

        <button type="submit" class="submit-btn">Accedi</button>

        <footer>
            <p>Non hai un account? <a href="register.php">Registrati</a></p>
        </footer>
    </form>
</body>
</html>
