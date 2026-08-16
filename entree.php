<?php
require __DIR__ . '/sql/config.php';

$successMessages = [];
$errorMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chauffeurId = 0;
    $chauffeurSingle = $conn->query('SELECT id FROM chauffeur ORDER BY id ASC LIMIT 1');
    if ($chauffeurSingle && $chauffeurSingle->num_rows > 0) {
        $chauffeurData = $chauffeurSingle->fetch_assoc();
        $chauffeurId = (int)$chauffeurData['id'];
    }

    $dateEntree = trim($_POST['date_entree'] ?? '');
    $montant = (float)($_POST['montant'] ?? 0);

    if ($chauffeurId <= 0 || $dateEntree === '' || $montant < 0) {
        $errorMessages[] = 'Créez d’abord un chauffeur puis saisissez une date et un montant valide.';
    } else {
        $stmt = $conn->prepare('INSERT INTO entree_journaliere (chauffeur_id, date_entree, montant) VALUES (?, ?, ?)');
        $stmt->bind_param('isd', $chauffeurId, $dateEntree, $montant);
        if ($stmt->execute()) {
            $successMessages[] = 'Entrée journalière enregistrée avec succès.';
        } else {
            $errorMessages[] = 'Erreur lors de l’enregistrement : ' . $stmt->error;
        }
        $stmt->close();
    }
}
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
                <p class="eyebrow">Accueil</p>
                <h1>Entrée journalière</h1>
            </div>
            <nav class="topnav">
                <a href="index.php">Accueil</a>
                <a href="consultation.php">Consultation</a>
            </nav>
        </header>

        <?php if (!empty($successMessages)): ?>
            <div class="alert success">
                <?php foreach ($successMessages as $msg): ?>
                    <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessages)): ?>
            <div class="alert error">
                <?php foreach ($errorMessages as $msg): ?>
                    <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="panel">
            <form method="post" class="form-grid">
                <label>
                    Date
                    <input type="date" name="date_entree" required>
                </label>
                <label>
                    Montant
                    <input type="number" name="montant" step="0.01" min="0" placeholder="Ex. 15000" required>
                </label>
                <button type="submit">Enregistrer l’entrée</button>
            </form>
        </section>
    </div>
</body>
</html>
<?php
$conn->close();
?>
