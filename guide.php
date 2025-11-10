<?php
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guida Utente - NextStudyAI</title>
    <link rel="stylesheet" href="assets/css/guide.css">
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <div class="logo"><a href="dashboard.php" style="text-decoration: none; color: inherit;">NextStudy<span> AI</span></a></div>
    <nav>
        <a href="#introduzione">Introduzione</a>
        <a href="#account">Account</a>
        <a href="#dashboard">Dashboard</a>
        <a href="#quiz">Quiz</a>
        <a href="#risultati">Risultati</a>
        <a href="#supporto">Supporto</a>
    </nav>
</header>

<main>
    <section id="introduzione" class="content">
        <h2>1. Introduzione</h2>
        <p><strong>NextStudyAI</strong> è una piattaforma educativa che utilizza l’intelligenza artificiale per aiutarti a studiare in modo più efficace. Puoi caricare i tuoi documenti e generare automaticamente quiz personalizzati basati sui contenuti principali, concentrandoti su ciò che conta davvero.</p>
    </section>

    <section id="account" class="content">
        <h2>2. Creazione e gestione dell’account</h2>
        <ol>
            <li>Registrati tramite la sezione <em>“Sign Up”</em> inserendo le informazioni richieste.</li>
            <li>Accedi con le tue credenziali tramite la sezione <em>“Log in”</em>.</li>
        </ol>
        <p>Consiglio: scegli una password sicura e non condividere le tue credenziali.</p>
    </section>

    <section id="dashboard" class="content">
        <h2>3. Navigazione della Dashboard</h2>
        <p>La <strong>dashboard</strong> è il punto di partenza per tutte le tue attività:</p>
        <ul>
            <li>Caricare documenti per creare quiz personalizzati</li>
            <li>Visualizzare statistiche dettagliate sui tuoi progressi</li>
            <li>Accedere rapidamente ai quiz generati</li>
        </ul>
        <p>È progettata per offrire una panoramica chiara e immediata dei tuoi risultati e attività.</p>
    </section>

    <section id="quiz" class="content">
        <h2>4. Creazione e svolgimento dei quiz</h2>
        <p>Per iniziare un quiz:</p>
        <ol>
            <li>Seleziona il documento caricato.</li>
            <li>Personalizza parametri come numero di domande e livello di difficoltà.</li>
            <li>Avvia il quiz e rispondi alle domande. Puoi abilitare il timer se desideri simulare una prova reale.</li>
        </ol>
        <p>I quiz sono progettati per testare le tue conoscenze in modo efficace e immediato.</p>
    </section>

    <section id="risultati" class="content">
        <h2>5. Visualizzazione dei risultati</h2>
        <p>Dopo aver completato un quiz, la piattaforma:</p>
        <ul>
            <li>Calcola automaticamente il punteggio</li>
            <li>Salva i risultati nel tuo profilo</li>
            <li>Mostra statistiche dettagliate per monitorare i tuoi progressi</li>
        </ul>
        <p>In questo modo puoi identificare facilmente gli argomenti da approfondire.</p>
    </section>

    <section id="supporto" class="content">
        <h2>6. Supporto e Assistenza</h2>
        <p>Se hai bisogno di aiuto o vuoi inviare suggerimenti:</p>
            <p>&nbsp;&nbsp;&nbsp;&nbsp;Contattaci tramite la sezione <a href="help.php">Richiedi assistenza</a></p>
        </br>
        <p>Siamo disponibili per aiutarti a ottenere il massimo dal tuo studio con NextStudy AI.</p>
    </section>
</main>
</body>
</html>
