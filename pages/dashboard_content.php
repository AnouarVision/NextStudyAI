<?php
    $messageTitle = '';
    $messageDesc = '';
    $showContinue = false;
    $iconType = 'doc';
    /*require_once __DIR__ . '/../vendor/Smalot/PdfParser/alt_autoload.php';
    use Smalot\PdfParser\Parser;*/

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    require_once __DIR__ . '/config/env.php';

    loadEnv(__DIR__ . '/.env');

    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    $dbName = getenv('DB_NAME');

    require_once 'DbConnector.php';
    $db = new DbConnector($dbHost, $dbUser, $dbPass, $dbName);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $messageTitle = 'Token CSRF non valido';
            $messageDesc = 'Ricarica la pagina e riprova.';
            $iconType = 'error';
        }

        // Check if a file was uploaded correctly
        elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $messageTitle = 'File non valido';
            $messageDesc = 'Seleziona un file corretto.';
            $iconType = 'error';
        } else {
            $file = $_FILES['document_file'];
            $allowed_ext = ['txt','md','csv'];
            $max_size = 10 * 1024 * 1024;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Validate file extension
            if (!in_array($ext, $allowed_ext)) {
                $messageTitle = 'Formato non supportato';
                $messageDesc = 'Carica un file TXT, MD o CSV.';
                $iconType = 'error';

            // Validate file size
            } elseif ($file['size'] > $max_size) {
                $messageTitle = 'File troppo grande';
                $messageDesc = 'Il file supera i 10 MB. Riducilo e riprova.';
                $iconType = 'error';
            } else {
                // Create secure upload directory if it doesn't exist
                $tmpPath = $file['tmp_name'];
                $hash = hash_file('sha256', $tmpPath);
                $mime = mime_content_type($tmpPath);
                $userId = $_SESSION['user_id'] ?? null;

                if (!$userId) {
                    $messageTitle = 'Utente non autenticato';
                    $messageDesc = 'Effettua il login e riprova.';
                    $iconType = 'error';
                } else {
                    $uploadDir = __DIR__ . '/../uploads_secure/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0750, true);
                    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
                    $storedPath = $uploadDir . $storedName;

                    if (!move_uploaded_file($tmpPath, $storedPath)) {
                        $messageTitle = 'Errore upload';
                        $messageDesc = 'Impossibile salvare il file.';
                        $iconType = 'error';
                    }
                    else
                    {
                        // Extract text safely from supported file formats
                        $meta = [];
                        $extractedText = extract_file_text_secure($storedPath, $file['name'], $meta);

                        try {
                            $stmt = $db->prepare("INSERT INTO document
                                (user_id, filename, original_name, file_type, file_size, hash_sha256, mime_type, status, threat_info)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $userId,
                                $storedName,
                                $file['name'],
                                $ext,
                                $file['size'],
                                $hash,
                                $mime,
                                'safe',
                                null,
                            ]);

                            // Save last uploaded document ID in session
                            $lastDocumentId = $db->prepare("SELECT LAST_INSERT_ID() AS id");
                            $lastDocumentId->execute();
                            $idRow = $lastDocumentId->fetch(PDO::FETCH_ASSOC);

                            if ($idRow && isset($idRow['id'])) {
                            $_SESSION['last_uploaded_document_id'] = intval($idRow['id']);
                            }

                            $messageTitle = 'File caricato con successo!';
                            $messageDesc = $file['name'] . ' (' . number_format($file['size']/1024/1024, 2) . ' MB)';
                            $iconType = 'success';
                            $showContinue = true;

                        } catch (PDOException $e) {
                            $messageTitle = 'Errore database';
                            $messageDesc = 'Impossibile salvare il file: '. $e->getMessage();
                            $iconType = 'error';
                        }
                    }
                }
            }
        }
    }

    //Safely extracts text from TXT, MD, CSV files
    function extract_file_text_secure($tmpPath, $originalName, &$meta = []) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $meta = ['ext' => $ext];

        if (in_array($ext, ['txt','md','csv'])) {
            $txt = file_get_contents($tmpPath);
            return sanitize_extracted_text($txt);
        }

        /*if (in_array($ext, ['docx','pptx'])) {
            return extract_from_openxml_or_zip($tmpPath, $ext, $meta);
        }

        if ($ext === 'pdf') {
            return extract_pdf_text_smalot($tmpPath, $meta);
        }*/

        return false;
    }

    // Function PDF
    /*function extract_pdf_text_smalot($path, &$meta = []) {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $meta['pdf_pages'] = count($pdf->getPages());
            return sanitize_extracted_text($pdf->getText());
        } catch (\Exception $e) {
            // fallback se PdfParser fallisce
            $bin = file_get_contents($path);
            preg_match_all('/[\x20-\x7E\xA0-\xFF]{4,}/u', $bin, $m);
            return sanitize_extracted_text(implode("\n", $m[0]));
        }
    }*/

    /*function extract_from_openxml_or_zip($tmpPath, $ext, &$meta) {
        if (!class_exists('ZipArchive')) return false;
        $zip = new ZipArchive;
        if ($zip->open($tmpPath) !== true) return false;

        $textPieces = [];

        if ($ext === 'docx') {
            $index = $zip->locateName('word/document.xml');
            if ($index !== false) {
                $contents = $zip->getFromIndex($index);
                $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
                if ($xml) {
                    $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $nodes = $xml->xpath('//w:t');
                    foreach ($nodes as $node) {
                        $textPieces[] = (string)$node;
                    }
                }
            }
        }
        elseif ($ext === 'pptx') {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#ppt/slides/slide\d+\.xml$#', $name)) {
                    $contents = $zip->getFromIndex($i);
                    if ($contents === false) continue;

                    $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA);
                    if (!$xml) continue;

                    $namespaces = $xml->getNamespaces(true);
                    $xml->registerXPathNamespace('a', $namespaces['a'] ?? 'http://schemas.openxmlformats.org/drawingml/2006/main');
                    $xml->registerXPathNamespace('p', $namespaces['p'] ?? 'http://schemas.openxmlformats.org/presentationml/2006/main');

                    $nodes = $xml->xpath('//a:t | //p:txBody//a:t');
                    if ($nodes) {
                        foreach ($nodes as $node) {
                            $textPieces[] = (string)$node;
                        }
                    }
                }
            }
        }

        $zip->close();
        return sanitize_extracted_text(implode("\n", $textPieces));
    }*/


    //Strips HTML tags and trims extra whitespace
    function sanitize_extracted_text($txt) {
        $clean = html_entity_decode(strip_tags($txt), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $clean = preg_replace('/\s{2,}/u', ' ', $clean);
        return trim($clean);
    }
?>

<header class="dashboard-header">
    <h1>Benvenuto, <?= htmlspecialchars($user['username']); ?>👋</h1>
    <p class="subtitle">Crea quiz intelligenti dai tuoi documenti in pochi secondi</p>
</header>

<!-- Upload section -->
<section class="upload-section">
    <div class="upload-card">
        <img src="assets/images/logo_document.png" alt="File Icon" class="upload-icon <?= $iconType === 'doc' ? '' : 'hidden' ?>" id="uploadDocIcon">
        <img src="assets/images/upload_success.png" alt="Success Icon" class="upload-icon <?= $iconType === 'success' ? '' : 'hidden' ?>" id="uploadSuccessIcon">
        <img src="assets/images/upload_error.png" alt="Error Icon" class="upload-icon <?= $iconType === 'error' ? '' : 'hidden' ?>" id="uploadErrorIcon">

        <h2><?= htmlspecialchars($messageTitle ?: 'Carica il tuo documento'); ?></h2>
        <p><?= htmlspecialchars($messageDesc ?: 'Supportiamo i formati di documento più comuni fino a 10 MB.'); ?></p>

        <form id="uploadForm" enctype="multipart/form-data" method="POST">
            <input type="file" id="file-input" name="document_file" class="hidden" accept=".txt,.md,.csv">
            <button type="button" class="upload-btn <?= $showContinue ? 'hidden' : '' ?>" id="chooseFileBtn">Scegli un file</button>
            <button type="button" class="upload-btn <?= $showContinue ? '' : 'hidden' ?>" id="continueBtn">Continua →</button>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
        </form>

    </div>
</section>

<!-- Quiz configuration section (initially hidden) -->
<div class="config-section hidden" id="configSection">
    <h2><span>⚙️</span> Configura il tuo Quiz</h2>

    <form id="quizConfigForm" method="POST" action="quiz_generate.php">
        <div class="config-grid">
            <!-- Number of Questions input -->
            <div class="form-group">
                <label for="numQuestions">Numero di Domande</label>
                <input
                    type="number"
                    id="numQuestions"
                    name="num_questions"
                    value="10"
                    min="5"
                    max="50"
                    required
                >
            </div>

            <!-- Difficulty selector -->
            <div class="form-group">
                <label for="difficulty">Difficoltà</label>
                <select id="difficulty" name="difficulty_level" required>
                    <option value="facile">Facile</option>
                    <option value="medio" selected>Medio</option>
                    <option value="difficile">Difficile</option>
                </select>
            </div>

            <!-- Time limit input (disabled by default) -->
            <div class="form-group">
                <label for="timeLimitInput">Tempo Limite (minuti)</label>
                <input
                    type="number"
                    id="timeLimitInput"
                    name="time_limit"
                    value="30"
                    min="5"
                    max="120"
                    disabled
                >
            </div>
        </div>

        <!-- Toggle for timed quiz -->
        <div class="toggle-group">
            <label class="toggle">
                <input
                    type="checkbox"
                    id="timeLimitCheckbox"
                    name="enable_timer"
                    value="1"
                >
                <span class="toggle-slider"></span>
            </label>

            <div class="toggle-text">
                <div class="toggle-title">Quiz a Tempo</div>
                <div class="toggle-desc">Aggiungi un limite di tempo</div>
            </div>
        </div>

        <!-- Quiz type selection buttons -->
        <h3 class="quiz-type-title">Tipo di Quiz</h3>

        <div class="quiz-types">
            <input type="hidden" name="quiz_type" id="quizType" value="multipla">

            <div class="quiz-type-btn active" data-type="multipla">
                <div class="quiz-type-icon">✅</div>
                <div class="quiz-type-label">Multipla</div>
            </div>

            <div class="quiz-type-btn" data-type="vero_falso">
                <div class="quiz-type-icon">✓✗</div>
                <div class="quiz-type-label">Vero/Falso</div>
            </div>

            <div class="quiz-type-btn" data-type="risposta_breve">
                <div class="quiz-type-icon">✍️</div>
                <div class="quiz-type-label">Risposta Breve</div>
            </div>

            <div class="quiz-type-btn" data-type="completamento">
                <div class="quiz-type-icon">📝</div>
                <div class="quiz-type-label">Completamento</div>
            </div>

            <div class="quiz-type-btn" data-type="misto">
                <div class="quiz-type-icon">🎲</div>
                <div class="quiz-type-label">Misto</div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit">
                <span>✨</span> Genera Quiz con AI
            </button>
        </div>
    </form>
</div>

<!-- Recent quizzes section -->
<section class="recent-quizzes">
    <h2>Quiz recenti</h2>

    <?php
        $userId = $_SESSION['user_id'];

        $stmt = $db->prepare("
            SELECT id, title, difficulty, quiz_type, num_questions, time_limit, created_at
            FROM quiz
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$userId]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$quizzes) {
            echo "<p>Nessun quiz recente trovato.</p>";
        } else {
            foreach ($quizzes as $quiz) {
                $difficultyClass = match (strtolower($quiz['difficulty'])) {
                    'facile' => 'easy',
                    'medio'  => 'medium',
                    'difficile' => 'hard',
                    default => 'medium'
                };

                // Calculate relative time since creation (e.g., "2 days ago")
                $createdTime = strtotime($quiz['created_at']);
                $diff = time() - $createdTime;
                if ($diff < 3600) $timeAgo = floor($diff / 60) . " min fa";
                elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . " ore fa";
                else $timeAgo = floor($diff / 86400) . " giorni fa";

                // Display quiz card with title, type, number of questions, time limit and link to quiz
                echo "
                <div class='quiz-card'>
                    <div class='quiz-top'>
                        <span class='quiz-difficulty $difficultyClass'>" . strtoupper($quiz['difficulty']) . "</span>
                        <span class='quiz-time'>$timeAgo</span>
                    </div>

                    <div class='quiz-info'>
                        <h3>" . htmlspecialchars($quiz['title'], ENT_QUOTES) . "</h3>
                        <p>Quiz " . htmlspecialchars($quiz['quiz_type'], ENT_QUOTES) . "</p>
                    </div>

                    <div class='quiz-stats'>
                        <div>📄 {$quiz['num_questions']} domande</div>
                        <div>⏱️ " . ($quiz['time_limit'] ? $quiz['time_limit'] . " minuti" : "Senza limite") . "</div>
                        <div><a href='quiz.php?id={$quiz['id']}' class='btn-small'>Vai al quiz</a></div>
                    </div>
                </div>
                ";
            }
        }
    ?>
</section>

