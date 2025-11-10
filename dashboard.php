<?php
    session_start();

    require_once __DIR__ . '/config/env.php';
    require_once 'DbConnector.php';

    loadEnv(__DIR__ . '/.env');

    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');

    $page = $_GET['page'] ?? 'dashboard';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    require_once 'DbConnector.php';
    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    $stmt = $db->prepare('SELECT * FROM profile WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the profile does not exist, create it and fetch it immediately afterward
    if (!$profile) {
        $stmt = $db->prepare('INSERT INTO profile (user_id) VALUES (?)');
        $stmt->execute([$_SESSION['user_id']]);
        $stmt = $db->prepare('SELECT * FROM profile WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Avatar handling: if no image is set, generate initials and a random background color
    $avatar = $profile['avatar_url'] ?? null;
    $initials = '';
    $bgColor = '';
    if (!$avatar) {
        $parts = explode(' ', $user['username']);
        foreach ($parts as $p) $initials .= strtoupper($p[0]);
        $bgColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextStudy AI - Dashboard</title>
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <?php if($page == 'settings'): ?>
        <link rel="stylesheet" href="assets/css/settings_content.css">
    <?php endif; ?>
    <?php if($page == 'resource'): ?>
        <link rel="stylesheet" href="assets/css/resource.css">
    <?php endif; ?>
</head>
<body class="dashboard-body">
    <!-- Sidebar and navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo_nextstudy.png" alt="Logo NextStudy" class="sidebar-logo">
            <h2 class="sidebar-title">NextStudy AI</h2>
        </div>

        <!-- Navigation menu -->
        <nav class="sidebar-menu">
            <a href="?page=dashboard" class="menu-btn <?= ($page=='dashboard') ? 'active' : '' ?>" style="text-decoration: none;">🏠 Dashboard</a>
            <a href="?page=quiz" class="menu-btn <?= ($page=='quiz') ? 'active' : '' ?>" style="text-decoration: none;">🧠 Quiz</a>
            <a href="?page=resource" class="menu-btn <?= ($page=='resource') ? 'active' : '' ?>" style="text-decoration: none;">📄 Risorse</a>
            <a href="?page=stats" class="menu-btn <?= ($page=='stats') ? 'active' : '' ?>" style="text-decoration: none;">📊 Statistiche</a>
            <a href="?page=settings" class="menu-btn <?= ($page=='settings') ? 'active' : '' ?>" style="text-decoration: none;">⚙️ Impostazioni</a>
        </nav>

        <!-- User area: avatar + dropdown -->
        <div class="sidebar-user" onclick="toggleUserDropdown()">
            <?php if ($avatar): ?>
                <img src="<?= htmlspecialchars($avatar); ?>" alt="User Avatar" class="user-avatar">
            <?php else: ?>
            <div class="user-avatar-circle" style="background-color: <?= $bgColor; ?>;">
                <?= $initials; ?>
            </div>
            <?php endif; ?>
                <div>
                    <p class="user-name"><?= htmlspecialchars($user['username']); ?></p>
                    <p class="user-role"><?= htmlspecialchars($profile['role']); ?></p>
                </div>
                <span class="dropdown-arrow">&#9662;</span>
        </div>

        <ul id="user-dropdown-menu" class="user-dropdown-menu">
            <li><a href="settings.php">Impostazioni</a></li>
            <li><a href="help.php">Ottieni aiuto</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="guide.php">Guida</a></li>
            <li><a href="logout.php">Esci</a></li>
        </ul>

    </aside>

    <!-- Main content -->
    <main class="main-content">
        <?php
            switch($page) {
                case 'dashboard':
                    include 'pages/dashboard_content.php';
                    break;
                case 'quiz':
                    include 'pages/quiz.php';
                    break;
                case 'resource':
                    include 'pages/resource.php';
                    break;
                case 'stats':
                    include 'pages/stats.php';
                    break;
                case 'settings':
                    include 'pages/settings_content.php';
                    break;
                default:
                    echo "<h1>Pagina non trovata</h1>";
            }
        ?>
    </main>

    <?php if ($page == 'dashboard'): ?>
        <script src="assets/js/dashboard_content.js"></script>
    <?php endif; ?>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
