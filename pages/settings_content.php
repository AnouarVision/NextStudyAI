<?php
    $stmt = $db->prepare("SELECT * FROM profile WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bio = trim($_POST['bio'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $timezone = trim($_POST['timezone'] ?? 'Europe/Rome');
        $language = trim($_POST['language'] ?? 'it');
        $theme = trim($_POST['theme'] ?? 'light');
        $role = trim($_POST['role'] ?? 'Studente');
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;

        if (!empty($date_of_birth)) {
            $timestamp = strtotime($date_of_birth);

            if ($timestamp === false) {
                $error = "La data di nascita non è valida.";
            } else {
                $today = strtotime(date('Y-m-d'));
                $minDate = strtotime('1900-01-01');

                if ($timestamp > $today) {
                    $error = "La data di nascita non può essere nel futuro.";
                } elseif ($timestamp < $minDate) {
                    $error = "La data di nascita non può essere precedente al 1900.";
                }
            }
        }

        if (empty($error)) {
            $stmt = $db->prepare("CALL UpdateUserProfile(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], $bio, $phone, $country, $timezone,
                $language, $theme, $role, $date_of_birth
            ]);

            $stmt = $db->prepare("SELECT * FROM profile WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            $success = "Impostazioni aggiornate con successo!";
        }
    }

    $timezones = [
        'Europe' => ['Rome','London','Paris','Berlin','Madrid'],
        'America' => ['New_York','Los_Angeles','Chicago'],
        'Asia' => ['Tokyo','Shanghai','Seoul','Singapore'],
        'Africa' => ['Cairo','Johannesburg'],
        'Australia' => ['Sydney','Melbourne']
    ];
?>

<div class="settings-card">
    <h1>Le tue impostazioni</h1>
    <?php if (!empty($success)): ?>
        <p class="success-message"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Bio:</label>
        <textarea name="bio" rows="3" class="form-input"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>

        <label>Telefono:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" class="form-input">

        <label>Paese:</label>
        <input type="text" name="country" value="<?= htmlspecialchars($profile['country'] ?? '') ?>" class="form-input">

        <label>Fuso orario:</label>
        <select name="timezone" class="form-input">
            <?php
                foreach ($timezones as $continent => $cities) {
                    echo "<optgroup label=\"$continent\">";
                    foreach ($cities as $city) {
                        $tzValue = "$continent/$city";
                        $selected = ($profile['timezone'] === $tzValue) ? 'selected' : '';
                        echo "<option value=\"$tzValue\" $selected>$city ($tzValue)</option>";
                    }
                    echo "</optgroup>";
                }
            ?>
        </select>

        <label>Lingua:</label>
        <select name="language" class="form-input">
            <option value="it" <?= ($profile['language'] == 'it') ? 'selected' : '' ?>>Italiano</option>
            <option value="en" <?= ($profile['language'] == 'en') ? 'selected' : '' ?>>Inglese</option>
        </select>

        <label>Ruolo:</label>
        <select name="role" class="form-input">
            <?php
                $roles = [
                    'Studente', 'Insegnante', 'Genitore', 'Tutor', 'Ricercatore',
                    'Coordinatore', 'Bibliotecario', 'Amministrativo', 'Ospite', 'Altro'
                ];
                foreach ($roles as $r) {
                    $selected = ($profile['role'] === $r) ? 'selected' : '';
                    echo "<option value=\"$r\" $selected>$r</option>";
                }
            ?>
        </select>

        <label>Data di nascita:</label>
        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>" class="form-input">

        <label>Tema:</label>
        <select name="theme" class="form-input">
            <option value="light" <?= ($profile['theme'] == 'light') ? 'selected' : '' ?>>Chiaro</option>
            <option value="dark" <?= ($profile['theme'] == 'dark') ? 'selected' : '' ?>>Scuro</option>
            <option value="auto" <?= ($profile['theme'] == 'auto') ? 'selected' : '' ?>>Automatico</option>
        </select>

        <button type="submit">
            Salva impostazioni
        </button>
    </form>
</div>
