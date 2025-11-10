<?php
    session_start();
    require_once 'DbConnector.php';

    require_once __DIR__ . '/config/env.php';

    loadEnv(__DIR__ . '/.env');

    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');

    $page = 'dashboard';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    $stmt = $db->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['ticket_id'], $_POST['status'])) {
            $ticket_id = (int)$_POST['ticket_id'];
            $status = $_POST['status'];
            if (in_array($status, ['open', 'in_progress', 'resolved'])) {
                $stmt = $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                $stmt->execute([$status, $ticket_id]);
            }
        }

        if (isset($_POST['user_id'], $_POST['account_status'])) {
            $user_id = (int)$_POST['user_id'];
            $new_status = $_POST['account_status'];
            if (in_array($new_status, ['active', 'suspended', 'deleted'])) {
                if ($new_status === 'deleted') {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET account_status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $user_id]);
                }
            }
        }
    }

    $tickets_stmt = $db->prepare("
        SELECT s.id, s.subject, s.message, s.status, s.created_at,
            u.username, u.email
        FROM support_tickets s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at DESC
    ");
    $tickets_stmt->execute();
    $tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

    $search_username = isset($_GET['search_username']) ? trim($_GET['search_username']) : '';
    $params = [];
    $userQuery = "
        SELECT u.id, u.username, u.email, u.role, u.account_status, u.last_login, p.role AS profile_role
        FROM users u
        LEFT JOIN profile p ON u.id = p.user_id
    ";
    if ($search_username !== '') {
        $userQuery .= " WHERE u.username LIKE ?";
        $params[] = "%$search_username%";
    }
    $userQuery .= " ORDER BY u.created_at DESC";
    $users_stmt = $db->prepare($userQuery);
    $users_stmt->execute($params);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    $perPage = 3;

    $totalTickets = count($tickets);
    $totalTicketPages = ceil($totalTickets / $perPage);
    $currentTicketPage = isset($_GET['ticket_page']) ? (int)$_GET['ticket_page'] : 1;
    $currentTicketPage = max(1, min($currentTicketPage, $totalTicketPages));
    $ticketOffset = ($currentTicketPage - 1) * $perPage;
    $ticketsPage = array_slice($tickets, $ticketOffset, $perPage);

    $totalUsers = count($users);
    $totalUserPages = ceil($totalUsers / $perPage);
    $currentUserPage = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
    $currentUserPage = max(1, min($currentUserPage, $totalUserPages));
    $userOffset = ($currentUserPage - 1) * $perPage;
    $usersPage = array_slice($users, $userOffset, $perPage);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NextStudy</title>
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body class="dashboard-body">

<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo_nextstudy.png" alt="Logo" class="sidebar-logo">
        <h2 class="sidebar-title">NextStudy</h2>
    </div>
    <div class="sidebar-menu">
        <a href="admin_dashboard.php" class="menu-btn <?= $page=='dashboard'?'active':'' ?>">📊 Dashboard Admin</a>
        <a href="pages/admin_resource.php" class="menu-btn <?= $page=='resources'?'active':'' ?>">📄 Risorse</a>
        <a href="settings.php" class="menu-btn <?= $page=='settings'?'active':'' ?>">⚙️ Impostazioni</a>
    </div>
    <div class="sidebar-user">
        <?php
        $initials = '';
        $parts = explode(' ', $user['username']);
        foreach ($parts as $p) $initials .= strtoupper($p[0]);
        $bgColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        ?>
        <div class="user-info">
            <div class="user-avatar-circle" style="background-color: <?= $bgColor; ?>;"><?= $initials; ?></div>
            <div class="user-text">
                <p class="user-name"><?= htmlspecialchars($user['username']); ?></p>
                <p class="user-role">Amministratore</p>
            </div>
        </div>
    </div>
</div>

<div class="admin-container">
    <h1>Segnalazioni utenti</h1>
    <div class="section">
        <table>
            <tr>
                <th>ID</th><th>Utente</th><th>Email</th><th>Oggetto</th>
                <th>Messaggio</th><th>Stato</th><th>Data</th><th>Azioni</th>
            </tr>
            <?php foreach ($ticketsPage as $t): ?>
            <tr>
                <td><?= $t['id']; ?></td>
                <td><?= htmlspecialchars($t['username']); ?></td>
                <td><?= htmlspecialchars($t['email']); ?></td>
                <td><?= htmlspecialchars($t['subject']); ?></td>
                <td><?= nl2br(htmlspecialchars($t['message'])); ?></td>
                <td class="status-<?= htmlspecialchars($t['status']); ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])); ?></td>
                <td><?= $t['created_at']; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="ticket_id" value="<?= $t['id']; ?>">
                        <select name="status">
                            <option value="open" <?= $t['status']=='open'?'selected':''; ?>>Aperta</option>
                            <option value="in_progress" <?= $t['status']=='in_progress'?'selected':''; ?>>In corso</option>
                            <option value="resolved" <?= $t['status']=='resolved'?'selected':''; ?>>Risolta</option>
                        </select>
                        <button type="submit">Aggiorna</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="pagination">
            <a href="?ticket_page=<?= max(1,$currentTicketPage-1) ?>&user_page=<?= $currentUserPage ?>&search_username=<?= urlencode($search_username) ?>" class="<?= $currentTicketPage==1?'disabled':'' ?>">« Indietro</a>
            <span>Pagina <?= $currentTicketPage ?> di <?= $totalTicketPages ?></span>
            <a href="?ticket_page=<?= min($totalTicketPages,$currentTicketPage+1) ?>&user_page=<?= $currentUserPage ?>&search_username=<?= urlencode($search_username) ?>" class="<?= $currentTicketPage==$totalTicketPages?'disabled':'' ?>">Avanti »</a>
        </div>
    </div>

    <h1>Utenti registrati</h1>
    <div class="section">
        <form method="GET" style="text-align:center; margin-bottom:15px;">
            <input type="text" name="search_username" placeholder="Cerca username..." value="<?= htmlspecialchars($search_username); ?>" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc;">
            <button type="submit" style="padding:6px 12px; border-radius:6px; border:none; background-color:#3b82f6; color:white; cursor:pointer;">Cerca</button>
        </form>
        <table>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Ruolo</th>
                <th>Profilo</th><th>Stato account</th><th>Ultimo accesso</th><th>Azioni</th>
            </tr>
            <?php foreach ($usersPage as $u): ?>
            <tr>
                <td><?= $u['id']; ?></td>
                <td><?= htmlspecialchars($u['username']); ?></td>
                <td><?= htmlspecialchars($u['email']); ?></td>
                <td><?= ucfirst($u['role']); ?></td>
                <td><?= htmlspecialchars($u['profile_role'] ?? '-'); ?></td>
                <td><?= ucfirst($u['account_status']); ?></td>
                <td><?= $u['last_login'] ? $u['last_login'] : 'Mai'; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                        <select name="account_status">
                            <option value="active" <?= $u['account_status']=='active'?'selected':''; ?>>Active</option>
                            <option value="suspended" <?= $u['account_status']=='suspended'?'selected':''; ?>>Suspended</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <button type="submit">Aggiorna</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="pagination">
            <a href="?user_page=<?= max(1,$currentUserPage-1) ?>&ticket_page=<?= $currentTicketPage ?>&search_username=<?= urlencode($search_username) ?>" class="<?= $currentUserPage==1?'disabled':'' ?>">« Indietro</a>
            <span>Pagina <?= $currentUserPage ?> di <?= $totalUserPages ?></span>
            <a href="?user_page=<?= min($totalUserPages,$currentUserPage+1) ?>&ticket_page=<?= $currentTicketPage ?>&search_username=<?= urlencode($search_username) ?>" class="<?= $currentUserPage==$totalUserPages?'disabled':'' ?>">Avanti »</a>
        </div>
    </div>
</div>

</body>
</html>