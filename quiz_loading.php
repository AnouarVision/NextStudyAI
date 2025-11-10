<?php
    session_start();
    if(!isset($_SESSION['user_id'])){
        header("Location: login.php");
        exit;
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generazione quiz...</title>
    <link rel="icon" type="image/png" href="./assets/images/logo_nextstudy.png">
    <style>
        body { font-family: Arial; text-align: center; margin-top: 100px; }
        .loader { border: 8px solid #f3f3f3; border-top: 8px solid #3498db; border-radius: 50%; width: 80px; height: 80px; animation: spin 1s linear infinite; margin: auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .message { margin-top: 20px; color: #333; }
    </style>
</head>
<body>
    <h2>Generazione del quiz in corso...</h2>
    <div class="loader"></div>
    <div class="message" id="status"></div>

    <script>
        const statusEl = document.getElementById('status');

        // Fetch quiz generation asynchronously
        fetch('quiz_generate_async.php')
        .then(response => response.json())
        .then(data => {
            if(data.success){
                window.location.href = 'quiz.php?id=' + data.quizId;
            } else {
                statusEl.textContent = 'Errore: Impossibile generare il quiz al momento';
            }
        })
        .catch(err => {
            statusEl.textContent = 'Errore di connessione. Riprova più tardi.';
        });
    </script>
</body>
</html>
