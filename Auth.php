<?php
require_once 'DbConnector.php';
require_once __DIR__ . '/config/env.php';

define('MAX_LOGIN_ATTEMPTS', 5);
define('BASE_LOCKOUT_SECONDS', 60);

loadEnv(__DIR__ . '/.env');

$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbName = getenv('DB_NAME');
$apiKey = getenv('API_KEY');


class Auth {
    private DbConnector $db;

    public function __construct() {
        global $dbHost, $dbUser, $dbPass, $dbName;
        $this->db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);
    }

    // Attempt user login
    public function login(string $email, string $password): array|false {
        $stmt = $this->db->prepare('SELECT id, password, role FROM users WHERE email = ? AND account_status = "active"');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $this->resetLoginAttempts();
            return $user;
        }

        $this->incrementLoginAttempts();
        return false;
    }

    // Increase login attempts counter
    public function incrementLoginAttempts(): void {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
    }

    // Reset login attempts counter
    public function resetLoginAttempts(): void {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = null;
    }

    // Calculate lockout duration based on failed attempts
    private function getLockoutTime(): int {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        if ($attempts < MAX_LOGIN_ATTEMPTS) return 0;
        $lockout = BASE_LOCKOUT_SECONDS * pow(2, $attempts - MAX_LOGIN_ATTEMPTS);
        return min($lockout, 900);
    }

    // Check if the user is temporarily locked out
    public function isLockedOut(): bool {
        if (!isset($_SESSION['login_attempts'])) return false;
        $lockout = $this->getLockoutTime();
        if ($lockout <= 0) return false;

        $diff = time() - ($_SESSION['last_attempt_time'] ?? 0);
        if ($diff < $lockout) return true;

        $this->resetLoginAttempts();
        return false;
    }

    // Logout user and destroy session
    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    // Access the database connector
    public function getDb(): DbConnector {
        return $this->db;
    }
}
