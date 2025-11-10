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

    if (!isset($_GET['id'])) {
        die("ID del quiz non specificato!");
    }

    $quizId = intval($_GET['id']);
    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    $stmt = $db->prepare("SELECT * FROM quiz WHERE id = ? AND user_id = ?");
    $stmt->execute([$quizId, $_SESSION['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quiz) die("Quiz non trovato.");

    $quizData = json_decode($quiz['quiz_data'], true);
    $questions = is_array($quizData) ? ($quizData['questions'] ?? []) : [];

    // PHP function to compare answers for fill-in-the-blank / short answer questions
    function isAnswerClose($userAns, $correctAnswer, $keywords = []) {
        $userAnsNorm = strtolower(trim($userAns));
        $correctAnsNorm = strtolower(trim($correctAnswer));

        // remove common articles (e.g., "the", "a", "an", italian articles) from a string
        $articles = '/\b(il|lo|la|i|gli|le|di|a|da|in|su|a|an|the)\b/';
        $userAnsNorm = preg_replace($articles, '', $userAnsNorm);
        $correctAnsNorm = preg_replace($articles, '', $correctAnsNorm);

        // mark as correct if the keyword is present
        if (strpos($userAnsNorm, $correctAnsNorm) !== false) return true;

        // Levenshtein distance comparison
        $distance = levenshtein($userAnsNorm, $correctAnsNorm);
        $length = max(strlen($userAnsNorm), strlen($correctAnsNorm), 1);
        $similarity = 1 - ($distance / $length);

        // check keywords
        $keywordMatches = 0;
        foreach ($keywords as $kw) {
            if (stripos($userAnsNorm, $kw) !== false) $keywordMatches++;
        }
        $keywordScore = $keywords ? $keywordMatches / count($keywords) : 0;

        $score = ($similarity + $keywordScore) / 2;
        return $score >= 0.6;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $answers = [];
        $score = 0;

        foreach ($questions as $index => $q) {
            $qid = 'q' . $index;
            $userAns = $_POST[$qid] ?? '';

            $decoded = json_decode($userAns, true);
            $answers[$qid] = $decoded !== null ? $decoded : $userAns;

            $correct = false;
            $feedback = $q['feedback'] ?? '';

            switch($q['type'] ?? 'multipla') {
                case 'risposta_breve':
                case 'completamento':
                    if (isAnswerClose($userAns, $q['answer'] ?? '', $q['keywords'] ?? [])) {
                        $correct = true;
                    } else {
                        $correct = false;
                        $feedback .= "<br>La tua risposta non contiene abbastanza concetti chiave o è troppo distante dalla soluzione.";
                    }
                    break;
                case 'collegamento':
                    if (is_array($answers[$qid])) {
                        $correct = json_encode($answers[$qid]) === json_encode($q['answer'] ?? []);
                    }
                    break;
                default:
                    $userVal = is_array($answers[$qid]) ? json_encode($answers[$qid]) : $answers[$qid];
                    $correct = trim(strtolower($userVal)) === trim(strtolower($q['answer'] ?? ''));
            }

            if ($correct) $score++;
            $answers[$qid] = ['user'=>$userAns, 'correct'=>$correct, 'feedback'=>$feedback];
        }

        $scorePercent = count($questions) ? min(($score / count($questions)) * 100, 100) : 0;

        $stmt = $db->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, answers) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $quizId, $scorePercent, json_encode($answers, JSON_UNESCAPED_UNICODE)]);

        header("Location: dashboard.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title']); ?></title>
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <link rel="stylesheet" href="assets/css/quiz.css">
</head>
<body>
<div class="quiz-container">
    <h1><?= htmlspecialchars($quiz['title']); ?></h1>

    <?php if($quiz['is_timed']): ?>
        <div id="timer">Tempo rimasto: <span id="timeRemaining"><?= intval($quiz['time_limit']) ?>:00</span></div>
    <?php endif; ?>

    <div class="progress-wrapper">
        <p id="progressText" class="progress-text">Domanda 1 di <?= count($questions) ?></p>
        <div class="progress-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <form method="post" id="quizForm">
        <div id="questionArea"></div>
        <div id="hiddenAnswers"></div>
        <div class="nav-buttons">
            <button type="button" id="prevBtn">⬅ Precedente</button>
            <button type="button" id="nextBtn">Successiva ➡</button>
            <button type="submit" id="submitBtn">Termina Quiz</button>
        </div>
    </form>
</div>

<script>
    const questions = <?= json_encode($questions, JSON_UNESCAPED_UNICODE) ?>;
    const isTimed = <?= $quiz['is_timed'] ? 'true' : 'false' ?>;
    const timeLimit = <?= intval($quiz['time_limit']) ?>;
</script>
<script src="assets/js/quiz.js"></script>
</body>
</html>
