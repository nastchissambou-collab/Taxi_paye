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

    $dateDepense = trim($_POST['date_depense'] ?? '');
    $montant = (float)($_POST['dep_montant'] ?? 0);
    $motif = trim($_POST['motif'] ?? '');

    if ($chauffeurId <= 0 || $dateDepense === '' || $montant <= 0) {
        $errorMessages[] = 'Créez d’abord un chauffeur puis saisissez une date et un montant valide.';
    } else {
        $stmt = $conn->prepare('INSERT INTO depense (chauffeur_id, date_depense, montant, motif) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isds', $chauffeurId, $dateDepense, $montant, $motif);
        if ($stmt->execute()) {
            $successMessages[] = 'Dépense enregistrée avec succès.';
        } else {
            $errorMessages[] = 'Erreur lors de l’enregistrement : ' . $stmt->error;
        }
        $stmt->close();
    }
}

$depensesResult = $conn->query('SELECT date_depense, montant, motif FROM depense ORDER BY date_depense DESC, id DESC');
$depenses = $depensesResult ? $depensesResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dépense</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Accueil</p>
                <h1>Dépense</h1>
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
                    <input type="date" name="date_depense" required>
                </label>
                <label>
                    Montant
                    <input type="number" name="dep_montant" step="0.01" min="0" placeholder="Ex. 10000" required>
                </label>
                <label>
                    Motif
                    <input type="text" name="motif" placeholder="Essence, maintenance...">
                </label>
                <button type="submit">Enregistrer la dépense</button>
            </form>
        </section>

        <section class="panel summary-panel">
            <h2>Détail des dépenses enregistrées</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Motif</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($depenses)): ?>
                            <tr>
                                <td colspan="3">Aucune dépense enregistrée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($depenses as $depense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($depense['date_depense'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($depense['motif'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float)$depense['montant'], 2, ',', ' ') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
<?php
$conn->close();
?>
