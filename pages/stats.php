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

    try {
        $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Basic user information
        $stmtUser = $db->prepare("
            SELECT u.username, p.role
            FROM users u
            LEFT JOIN profile p ON u.id = p.user_id
            WHERE u.id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        // General statistics
        $stmtGeneral = $db->prepare("
            SELECT
                COUNT(qr.id) AS total_attempts,
                COUNT(DISTINCT qr.quiz_id) AS quizzes_taken,
                ROUND(AVG(qr.score), 2) AS avg_score,
                ROUND(AVG(qr.time_taken), 0) AS avg_time
            FROM quiz_results qr
            WHERE qr.user_id = ?
        ");
        $stmtGeneral->execute([$userId]);
        $generalStats = $stmtGeneral->fetch(PDO::FETCH_ASSOC);

        // Statistics by quiz type
        $stmtType = $db->prepare("
            SELECT q.quiz_type,
                COUNT(qr.id) AS attempts,
                ROUND(AVG(qr.score), 2) AS avg_score
            FROM quiz_results qr
            INNER JOIN quiz q ON qr.quiz_id = q.id
            WHERE qr.user_id = ?
            GROUP BY q.quiz_type
        ");
        $stmtType->execute([$userId]);
        $typeStats = $stmtType->fetchAll(PDO::FETCH_ASSOC);

        // Statistics by difficulty
        $stmtDiff = $db->prepare("
            SELECT q.difficulty,
                COUNT(qr.id) AS attempts,
                ROUND(AVG(qr.score), 2) AS avg_score
            FROM quiz_results qr
            INNER JOIN quiz q ON qr.quiz_id = q.id
            WHERE qr.user_id = ?
            GROUP BY q.difficulty
        ");
        $stmtDiff->execute([$userId]);
        $difficultyStats = $stmtDiff->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Si è verificato un errore temporaneo. Riprova più tardi.";
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Quiz</title>
    <link rel="stylesheet" href="assets/css/stats.css">
</head>
<body>

<div class="stats-container">
    <header class="stats-header">
        <div>
            <h1>Statistiche di <?= htmlspecialchars($user['username']) ?></h1>
            <p>Ruolo: <?= htmlspecialchars($user['role'] ?? 'Studente') ?></p>
        </div>
    </header>

    <section class="overview">
        <h2>Panoramica Generale</h2>
        <div class="overview-cards">
            <div class="card"><strong>Quiz svolti:</strong> <?= $generalStats['quizzes_taken'] ?? 0 ?></div>
            <div class="card"><strong>Tentativi totali:</strong> <?= $generalStats['total_attempts'] ?? 0 ?></div>
            <div class="card"><strong>Punteggio medio:</strong> <?= $generalStats['avg_score'] ?? 0 ?>%</div>
            <div class="card"><strong>Tempo medio:</strong> <?= $generalStats['avg_time'] ?? 0 ?> sec</div>
        </div>
    </section>

    <section class="by-type">
        <h2>Statistiche per tipo di quiz</h2>
        <table>
            <thead>
                <tr><th>Tipo</th><th>Tentativi</th><th>Punteggio medio</th></tr>
            </thead>
            <tbody>
                <?php if ($typeStats): ?>
                    <?php foreach ($typeStats as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($row['quiz_type'])) ?></td>
                            <td><?= $row['attempts'] ?></td>
                            <td><?= $row['avg_score'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Nessun quiz completato</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="by-difficulty">
        <h2>Statistiche per difficoltà</h2>
        <table>
            <thead>
                <tr><th>Difficoltà</th><th>Tentativi</th><th>Punteggio medio</th></tr>
            </thead>
            <tbody>
                <?php if ($difficultyStats): ?>
                    <?php foreach ($difficultyStats as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($row['difficulty'])) ?></td>
                            <td><?= $row['attempts'] ?></td>
                            <td><?= $row['avg_score'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Nessun quiz completato</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <footer>
        <a href="dashboard.php" class="btn-back">← Torna alla Dashboard</a>
    </footer>
</div>

</body>
</html>
