<?php
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

    $query = "
        SELECT a.id, a.title, a.summary, a.category, a.created_at, u.username
        FROM articles a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.visibility = 'public' AND u.role = 'admin'
        ORDER BY a.created_at DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="resources-section">
    <div class="resources-header">
        <h1>Risorse e Approfondimenti</h1>
        <p>In questa sezione trovi articoli selezionati dagli amministratori su informatica, logica e altre discipline.</p>
    </div>

    <div class="resources-grid" id="resources-grid">
        <?php foreach ($articles as $row): ?>
            <article class="resource-card">
                <div class="card-badge"><?= ucfirst($row['category']) ?></div>
                <div class="resource-content">
                    <h2><?= htmlspecialchars($row['title']) ?></h2>
                    <p class="summary"><?= htmlspecialchars($row['summary']) ?></p>
                </div>
                <div class="resource-footer">
                    <div class="meta">
                        <span class="author">👤 <?= htmlspecialchars($row['username']) ?></span>
                        <span class="date">📅 <?= date("d/m/Y", strtotime($row['created_at'])) ?></span>
                    </div>
                    <a href="article.php?id=<?= $row['id'] ?>" class="read-more">Leggi →</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="pagination" id="pagination"></div>
</section>

<script src="assets/js/resource.js"></script>
