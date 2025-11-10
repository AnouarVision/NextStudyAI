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
<title>FAQ - NextStudy AI</title>
<link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
<link rel="stylesheet" href="assets/css/faq.css">
</head>
<body>
<div class="faq-wrapper">
    <div class="faq-container">
        <h1>Domande Frequenti (FAQ)</h1>

        <div class="faq-item">
            <div class="faq-question">
                Cos'è NextStudy AI? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                NextStudy AI è una piattaforma educativa online che sfrutta l'intelligenza artificiale per generare automaticamente quiz personalizzati. Gli utenti possono caricare i propri documenti e trasformarli in esercizi mirati per facilitare l'apprendimento.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Come funziona la generazione dei quiz? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                Il sistema analizza il contenuto dei documenti caricati e crea domande multiple-choice o a risposta aperta basate sui concetti principali. È possibile scegliere il numero di domande e il livello di difficoltà per adattare i quiz alle proprie esigenze di studio.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Che tipi di documenti posso caricare? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                NextStudy AI supporta documenti in formati TXT, CSV e MD (prossimamente anche altri). Il sistema estrae automaticamente il contenuto e lo utilizza per generare i quiz.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                I miei dati sono al sicuro? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                La sicurezza dei dati è una nostra priorità. Tutti i documenti caricati e i quiz generati sono protetti e accessibili solo dall'account dell'utente. Non condividiamo i tuoi dati con terze parti.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Posso utilizzare NextStudy AI gratuitamente? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                Sì, è possibile utilizzare la piattaforma gratuitamente con alcune limitazioni sul numero di quiz generabili. Per funzionalità avanzate e maggiore capacità di elaborazione, sarà disponibile prossimamente un piano premium.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Come posso contattare l'assistenza? <i class="arrow"></i>
            </div>
            <div class="faq-answer">
                Se hai bisogno di supporto o vuoi inviare suggerimenti, puoi utilizzare la sezione <a href="help.php">Richiedi assistenza</a> presente nel tuo account.
            </div>
        </div>

    </div>
</div>
<script src="assets/js/faq.js"></script>
</body>
</html>
