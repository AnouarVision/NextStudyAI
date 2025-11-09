<?php
    session_start();

    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <title>NextStudy AI</title>
</head>
<body>
    <header>
        <div class="logo-title">
            <div><image src="assets/images/logo_nextstudy.png" alt="Logo NextStudy" class="logo"></div>
            <h1>NextStudy AI</h1>
        </div>
        <p class="subtitle">
            Trasforma i tuoi documenti in quiz interattivi e
            intelligenti, generati in un istante dalla nostra intelligenza
            artificiale.
        </p>
    </header>

    <section class="features">
        <div class="feature">
            <h2>Carica un file</h2>
            <p>Supportiamo TXT, MD e CSV. Fino a 10MB.</p>
        </div>
        <div class="feature">
            <h2>Generazione AI</h2>
            <p>Domande personalizzate in pochi secondi.</p>
        </div>
        <div class="feature">
            <h2>Rivedi e Condividi</h2>
            <p>Modifica, salva e condividi i tuoi quiz con chi vuoi.</p>
        </div>
    </section>

    <div class="cta">
        <button onclick="showAuthForm()">Inizia ora &rarr;</button>
    </div>

    <script>
        function showAuthForm() {
            window.location.href = "register.php";
        }
    </script>
</body>
</html>