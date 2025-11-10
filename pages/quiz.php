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

    $userId = $_SESSION['user_id'];

    $db = new DbConnector($dbHost, 'root', '', 'nextstudy_db');

    // Fetch user quiz results
    $stmt = $db->prepare("
        SELECT
            q.id AS quiz_id,
            q.title,
            q.num_questions,
            q.difficulty,
            q.quiz_type,
            qr.score,
            qr.time_taken,
            qr.completed_at
        FROM quiz_results qr
        INNER JOIN quiz q ON qr.quiz_id = q.id
        WHERE qr.user_id = ?
        ORDER BY qr.completed_at DESC
    ");
    $stmt->execute([$userId]);
    $allQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $perPage = 6;
    $totalQuizzes = count($allQuizzes);
    $totalPages = ceil($totalQuizzes / $perPage);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Quiz Recenti</title>
    <link rel="stylesheet" href="assets/css/quiz_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>I tuoi quiz</h1>

        <?php if (empty($allQuizzes)): ?>
            <p>Non hai completato alcun quiz recentemente.</p>
        <?php else: ?>
            <section class="recent-quizzes">
                <?php foreach ($allQuizzes as $index => $quiz): ?>
                    <div class="quiz-card"
                         data-index="<?= $index ?>"
                         data-quiz-id="<?= $quiz['quiz_id'] ?>">

                        <div class="quiz-top">
                            <span class="quiz-difficulty <?= htmlspecialchars($quiz['difficulty']); ?>">
                                <?= strtoupper(htmlspecialchars($quiz['difficulty'])); ?>
                            </span>
                            <span class="quiz-time">
                                <?= date('d/m/Y H:i', strtotime($quiz['completed_at'])); ?>
                            </span>
                        </div>

                        <div class="quiz-info">
                            <h3><?= htmlspecialchars($quiz['title']); ?></h3>
                            <p>
                                <?php
                                switch ($quiz['quiz_type']) {
                                    case 'vero_falso':
                                        echo "Quiz vero/falso";
                                        break;
                                    case 'multipla':
                                        echo "Quiz a scelta multipla";
                                        break;
                                    case 'risposta_breve':
                                        echo "Quiz a risposta breve";
                                        break;
                                    default:
                                        echo ucfirst($quiz['quiz_type']);
                                        break;
                                }
                                ?>
                            </p>
                        </div>

                        <div class="quiz-stats">
                            <div>📄 <?= intval($quiz['num_questions']); ?> domande</div>
                            <div>⏱️ <?= $quiz['time_taken'] ? intval($quiz['time_taken'] / 60) . " minuti" : "-" ?></div>
                            <div>📈 <?= $quiz['score'] !== null ? intval($quiz['score']) . "%" : "-" ?></div>
                            <div>
                                <a href="quiz.php?id=<?= $quiz['quiz_id'] ?>" class="btn-small">
                                    &raquo; Vai al quiz
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <div class="pagination-modern">
                    <button id="prevPage">&laquo; Precedente</button>
                    <span id="pageIndicator">Pagina 1 di <?= $totalPages ?></span>
                    <button id="nextPage">Successiva &raquo;</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        const perPage = <?= $perPage ?>;
        const totalPages = <?= $totalPages ?>;
        let currentPage = 1;

        function showPage(page) {
            currentPage = page;
            const cards = document.querySelectorAll('.quiz-card');

            cards.forEach((card, index) => {
                card.style.display = (index >= (page - 1) * perPage && index < page * perPage)
                    ? 'block' : 'none';
            });

            document.getElementById('pageIndicator').textContent =
                `Pagina ${currentPage} di ${totalPages}`;
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
        }

        document.getElementById('prevPage').addEventListener('click', () => showPage(currentPage - 1));
        document.getElementById('nextPage').addEventListener('click', () => showPage(currentPage + 1));

        showPage(1);
    </script>
</body>
</html>
