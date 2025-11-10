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

    $article_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // Fetch main article content and metadata
    $query = "
        SELECT a.id, a.title, a.content, a.summary, a.category, a.created_at, a.views, u.username, u.id as user_id
        FROM articles a
        INNER JOIN users u ON a.user_id = u.id
        LEFT JOIN profile p ON u.id = p.user_id
        WHERE a.id = :id AND a.visibility = 'public'
    ";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $article_id, PDO::PARAM_INT);
    $stmt->execute();
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    // Increment view counter safely
    $update_query = "UPDATE articles SET views = views + 1 WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindValue(':id', $article_id, PDO::PARAM_INT);
    $update_stmt->execute();

    // Retrieve related articles from the same category
    $related_query = "
        SELECT a.id, a.title, a.summary, a.category, a.created_at, u.username
        FROM articles a
        INNER JOIN users u ON a.user_id = u.id
        WHERE a.category = :category AND a.visibility = 'public' AND a.id != :id
        ORDER BY a.created_at DESC
        LIMIT 3
    ";

    $related_stmt = $db->prepare($related_query);
    $related_stmt->bindValue(':category', $article['category'], PDO::PARAM_STR);
    $related_stmt->bindValue(':id', $article_id, PDO::PARAM_INT);
    $related_stmt->execute();
    $related_articles = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - NextStudy</title>
    <link rel="stylesheet" href="assets/css/article.css">
</head>
<body>
    <div class="article-container">
        <!-- Article Header Section -->
        <header class="article-header">
            <div class="article-breadcrumb">
                <a href="./dashboard.php?page=resource">Risorse</a>
                <span>›</span>
                <span><?= ucfirst($article['category']) ?></span>
            </div>

            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
            <p class="article-summary"><?= htmlspecialchars($article['summary']) ?></p>

            <!-- Article Metadata -->
            <div class="article-meta-header">
                <div class="author-info">
                    <div class="author-details">
                        <span class="author-name"><?= htmlspecialchars($article['username']) ?></span>
                        <span class="meta-date">📅 <?= date("d M Y", strtotime($article['created_at'])) ?></span>
                    </div>
                </div>
                <div class="article-stats">
                    <span class="stat">👁️ <?= number_format($article['views']) ?> visualizzazioni</span>
                    <span class="stat category-badge"><?= ucfirst($article['category']) ?></span>
                </div>
            </div>
        </header>

        <div class="article-wrapper">
            <!-- Main Content Section -->
            <main class="article-main">
                <article class="article-body">
                    <?= nl2br(htmlspecialchars($article['content'])) ?>
                </article>

                <!-- Social Share Buttons -->
                <div class="article-share">
                    <h3>Condividi articolo</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                           target="_blank" class="share-btn share-facebook" title="Condividi su Facebook">
                            f
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                           target="_blank" class="share-btn share-twitter" title="Condividi su Twitter">
                            𝕏
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                           target="_blank" class="share-btn share-linkedin" title="Condividi su LinkedIn">
                            in
                        </a>
                        <button class="share-btn share-copy" onclick="copyToClipboard()" title="Copia link">
                            🔗
                        </button>
                    </div>
                </div>

                <!-- Return to resource list -->
                <div class="article-back">
                    <a href="./dashboard.php?page=resource" class="back-button">← Torna alle risorse</a>
                </div>
            </main>

            <!-- Sidebar Section -->
            <aside class="article-sidebar">
                <!-- Category card -->
                <div class="sidebar-card">
                    <h3>Categoria</h3>
                    <p class="category-info"><?= ucfirst($article['category']) ?></p>
                </div>

                <!-- Related Articles Section -->
                <?php if ($related_articles): ?>
                    <div class="sidebar-card">
                        <h3>Articoli Correlati</h3>
                        <div class="related-articles">
                            <?php foreach ($related_articles as $rel): ?>
                                <a href="article.php?id=<?= $rel['id'] ?>" class="related-item">
                                    <h4><?= htmlspecialchars($rel['title']) ?></h4>
                                    <span class="related-date">📅 <?= date("d M Y", strtotime($rel['created_at'])) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Author Information -->
                <div class="sidebar-card author-card">
                    <p class="author-role">Amministratore Contenuti</p>
                    <p class="author-bio">Selezionato per la qualità e la profondità dei contenuti didattici.</p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copiato negli appunti!');
            }).catch(() => {
                alert('Errore nel copiare il link');
            });
        }
    </script>
</body>
</html>