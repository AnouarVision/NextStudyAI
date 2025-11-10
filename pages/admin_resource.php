<?php
    session_start();
    require_once '../DbConnector.php';
    require_once __DIR__ . '/config/env.php';

    loadEnv(__DIR__ . '/.env');

    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');

    $page = 'resources';

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Create new article
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $title = $_POST['title'] ?? '';
            $summary = $_POST['summary'] ?? '';
            $content = $_POST['content'] ?? '';
            $category = $_POST['category'] ?? 'altro';
            $visibility = $_POST['visibility'] ?? 'public';

            if ($title && $content) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                $stmt = $db->prepare("
                    INSERT INTO articles (user_id, title, slug, summary, content, category, visibility)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $title,
                    $slug,
                    $summary,
                    $content,
                    $category,
                    $visibility
                ]);
            }
        }

        // Update existing article
        if (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['article_id'])) {
            $article_id = (int)$_POST['article_id'];
            $title = $_POST['title'] ?? '';
            $summary = $_POST['summary'] ?? '';
            $content = $_POST['content'] ?? '';
            $category = $_POST['category'] ?? 'altro';
            $visibility = $_POST['visibility'] ?? 'public';

            if ($title && $content) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                $stmt = $db->prepare("
                    UPDATE articles
                    SET title = ?, slug = ?, summary = ?, content = ?, category = ?, visibility = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $summary,
                    $content,
                    $category,
                    $visibility,
                    $article_id
                ]);
            }
        }

        // Delete article
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['article_id'])) {
            $article_id = (int)$_POST['article_id'];
            $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
            $stmt->execute([$article_id]);
        }

        // Reload page to refresh data
        header("Location: admin_resource.php");
        exit;
    }

    // Fetch all resources with author info
    $resources_stmt = $db->prepare("
        SELECT a.id, a.title, a.summary, a.category, a.visibility, a.views, a.created_at, u.username AS author
        FROM articles a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
    ");
    $resources_stmt->execute();
    $resources = $resources_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Risorse - NextStudy</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_nextstudy.png">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_resource.css">
</head>

<body class="dashboard-body">

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo_nextstudy.png" alt="Logo" class="sidebar-logo">
        <h2 class="sidebar-title">NextStudy</h2>
    </div>

    <div class="sidebar-menu">
        <a href="../admin_dashboard.php" class="menu-btn <?= $page === 'dashboard' ? 'active' : '' ?>">📊 Dashboard Admin</a>
        <a href="admin_resource.php" class="menu-btn <?= $page === 'resource' ? 'active' : '' ?>">📄 Risorse</a>
        <a href="../settings.php" class="menu-btn <?= $page === 'settings' ? 'active' : '' ?>">⚙️ Impostazioni</a>
    </div>

    <!-- User info in sidebar -->
    <div class="sidebar-user">
        <?php
        $initials = '';
        foreach (explode(' ', $user['username']) as $p) {
            $initials .= strtoupper($p[0]);
        }
        $bgColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        ?>
        <div class="user-info">
            <div class="user-avatar-circle" style="background-color: <?= $bgColor; ?>;">
                <?= $initials; ?>
            </div>
            <div class="user-text">
                <p class="user-name"><?= htmlspecialchars($user['username']); ?></p>
                <p class="user-role">Amministratore</p>
            </div>
        </div>
    </div>
</div>

<!-- Main content area -->
<div class="admin-container">
    <h1>Gestione Risorse</h1>

    <!-- Create new article form -->
    <div class="section">
        <h2>Aggiungi Nuovo Articolo</h2>
        <form method="POST" class="form-inline">
            <input type="hidden" name="action" value="create">
            <input type="text" name="title" placeholder="Titolo" required>
            <textarea name="summary" placeholder="Breve descrizione"></textarea>
            <textarea name="content" placeholder="Contenuto completo" required></textarea>

            <select name="category">
                <option value="informatica">Informatica</option>
                <option value="logica">Logica</option>
                <option value="matematica">Matematica</option>
                <option value="filosofia">Filosofia</option>
                <option value="altro">Altro</option>
            </select>

            <select name="visibility">
                <option value="public">Pubblico</option>
                <option value="private">Privato</option>
                <option value="draft">Bozza</option>
            </select>

            <button type="submit">Aggiungi</button>
        </form>
    </div>

    <!-- Articles list -->
    <div class="section">
        <h2>Articoli Esistenti</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Titolo</th>
                <th>Autore</th>
                <th>Categoria</th>
                <th>Visibilità</th>
                <th>Views</th>
                <th>Data Creazione</th>
                <th>Azioni</th>
            </tr>

            <?php foreach ($resources as $r): ?>
                <tr>
                    <td><?= $r['id']; ?></td>
                    <td><?= htmlspecialchars($r['title']); ?></td>
                    <td><?= htmlspecialchars($r['author']); ?></td>
                    <td><?= ucfirst($r['category']); ?></td>
                    <td><?= ucfirst($r['visibility']); ?></td>
                    <td><?= $r['views']; ?></td>
                    <td><?= $r['created_at']; ?></td>

                    <td>
                        <!-- Update form -->
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="article_id" value="<?= $r['id']; ?>">

                            <input type="text" name="title" value="<?= htmlspecialchars($r['title']); ?>" required>
                            <textarea name="summary"><?= htmlspecialchars($r['summary']); ?></textarea>
                            <textarea name="content"><?= htmlspecialchars($r['content'] ?? ''); ?></textarea>

                            <select name="category">
                                <option value="informatica" <?= $r['category'] === 'informatica' ? 'selected' : ''; ?>>Informatica</option>
                                <option value="logica" <?= $r['category'] === 'logica' ? 'selected' : ''; ?>>Logica</option>
                                <option value="matematica" <?= $r['category'] === 'matematica' ? 'selected' : ''; ?>>Matematica</option>
                                <option value="filosofia" <?= $r['category'] === 'filosofia' ? 'selected' : ''; ?>>Filosofia</option>
                                <option value="altro" <?= $r['category'] === 'altro' ? 'selected' : ''; ?>>Altro</option>
                            </select>

                            <select name="visibility">
                                <option value="public" <?= $r['visibility'] === 'public' ? 'selected' : ''; ?>>Pubblico</option>
                                <option value="private" <?= $r['visibility'] === 'private' ? 'selected' : ''; ?>>Privato</option>
                                <option value="draft" <?= $r['visibility'] === 'draft' ? 'selected' : ''; ?>>Bozza</option>
                            </select>

                            <button type="submit">Salva</button>
                        </form>

                        <!-- Delete form -->
                        <form method="POST" style="margin-top: 5px;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="article_id" value="<?= $r['id']; ?>">
                            <button type="submit" style="background: #ef4444;">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
