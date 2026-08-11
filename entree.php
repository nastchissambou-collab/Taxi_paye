<?php
require __DIR__ . '/sql/config.php';

$message = '';
$error = '';

$chauffeurResult = $conn->query('SELECT id, prenom FROM chauffeur ORDER BY id ASC LIMIT 1');
$chauffeur = $chauffeurResult ? $chauffeurResult->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$chauffeur) {
        $error = 'Aucun chauffeur n’est encore enregistré.';
    } else {
        $dateEntree = $_POST['date_entree'] ?? date('Y-m-d');
        $montant = (float)($_POST['montant'] ?? 0);

        if ($montant <= 0) {
            $error = 'Le montant doit être supérieur à 0.';
        } else {
            $stmt = $conn->prepare('INSERT INTO entree_journaliere (chauffeur_id, date_entree, montant) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $chauffeur['id'], $dateEntree, $montant);
            if ($stmt->execute()) {
                $message = 'Entrée enregistrée avec succès.';
            } else {
                $error = 'Erreur lors de l’enregistrement de l’entrée.';
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Entrée journalière</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Entrée</p>
                <h1>Enregistrer une entrée</h1>
            </div>
            <nav class="topnav">
                <a href="index.php">Accueil</a>
                <a href="consultation.php">Consultation</a>
            </nav>
        </header>

        <?php if ($message !== ''): ?>
            <div class="alert success"><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert error"><p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p></div>
        <?php endif; ?>

        <section class="panel">
            <h2>Nouvelle entrée</h2>
            <form method="post" class="form-grid">
                <label>Date
                    <input type="date" name="date_entree" value="<?= date('Y-m-d') ?>" required>
                </label>
                <label>Montant
                    <input type="number" step="0.01" name="montant" required>
                </label>
                <button type="submit">Enregistrer</button>
            </form>
        </section>
    </div>
</body>
</html>
