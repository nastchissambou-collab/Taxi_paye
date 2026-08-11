<?php
require __DIR__ . '/sql/config.php';

$selectedAnnee = (int)($_GET['annee'] ?? date('Y'));
$selectedMois = (int)($_GET['mois'] ?? date('n'));

$nextMonth = $selectedMois == 12 ? 1 : $selectedMois + 1;
$nextYear = $selectedMois == 12 ? $selectedAnnee + 1 : $selectedAnnee;

$periodStart = date('Y-m-d', mktime(0, 0, 0, $selectedMois, 5, $selectedAnnee));
$periodEnd = date('Y-m-d', mktime(0, 0, 0, $nextMonth, 4, $nextYear));
$periodPayment = date('Y-m-d', mktime(0, 0, 0, $nextMonth, 5, $nextYear));
$exportUrl = 'export_csv.php?annee=' . urlencode((string)$selectedAnnee) . '&mois=' . urlencode((string)$selectedMois);

$chauffeursResult = $conn->query('SELECT id, nom, prenom FROM chauffeur ORDER BY id ASC LIMIT 1');
$chauffeur = $chauffeursResult ? $chauffeursResult->fetch_assoc() : null;

$entries = [];
$depenses = [];
$summary = [
    'entries' => 0,
    'expenses' => 0,
    'net' => 0,
    'salary' => 0,
];

if ($chauffeur) {
    $entriesQuery = $conn->prepare('SELECT date_entree, montant FROM entree_journaliere WHERE chauffeur_id = ? AND date_entree BETWEEN ? AND ? ORDER BY date_entree ASC');
    $entriesQuery->bind_param('iss', $chauffeur['id'], $periodStart, $periodEnd);
    $entriesQuery->execute();
    $entriesResult = $entriesQuery->get_result();
    $entries = $entriesResult ? $entriesResult->fetch_all(MYSQLI_ASSOC) : [];
    $entriesQuery->close();

    $depensesQuery = $conn->prepare('SELECT date_depense, montant, motif FROM depense WHERE chauffeur_id = ? AND date_depense BETWEEN ? AND ? ORDER BY date_depense DESC, id DESC');
    $depensesQuery->bind_param('iss', $chauffeur['id'], $periodStart, $periodEnd);
    $depensesQuery->execute();
    $depensesResult = $depensesQuery->get_result();
    $depenses = $depensesResult ? $depensesResult->fetch_all(MYSQLI_ASSOC) : [];
    $depensesQuery->close();

    $totalEntries = 0;
    foreach ($entries as $entry) {
        $totalEntries += (float)$entry['montant'];
    }

    $totalExpenses = 0;
    foreach ($depenses as $depense) {
        $totalExpenses += (float)$depense['montant'];
    }

    $summary['entries'] = $totalEntries;
    $summary['expenses'] = $totalExpenses;
    $summary['net'] = $totalEntries - $totalExpenses;
    $summary['salary'] = round($totalEntries * 0.25, 2);
}

$comparisonDelta = $summary['entries'] - $summary['expenses'];
$comparisonLabel = $comparisonDelta >= 0 ? 'Excédent' : 'Déficit';
$comparisonPercent = $summary['expenses'] > 0 ? round(($comparisonDelta / $summary['expenses']) * 100, 1) : 0;

$months = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Consultation mensuelle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Consultation</p>
                <h1>Récapitulatif mensuel</h1>
            </div>
            <nav class="topnav">
                <a href="index.php">Accueil</a>
                <a href="consultation.php">Consultation mensuelle</a>
            </nav>
        </header>

        <section class="panel">
            <h2>Filtrer par mois de paiement</h2>
            <form method="get" class="form-grid">
                <label>
                    Année
                    <select name="annee" required>
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === $selectedAnnee ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </label>

                <label>
                    Mois de paiement
                    <select name="mois" required>
                        <?php foreach ($months as $num => $label): ?>
                            <option value="<?= $num ?>" <?= $num === $selectedMois ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <button type="submit">Afficher la période</button>
            </form>
        </section>

        <section class="panel summary-panel">
            <div class="section-head">
                <h2>Période : du <?= htmlspecialchars($periodStart, ENT_QUOTES, 'UTF-8') ?> au <?= htmlspecialchars($periodEnd, ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="action-row">
                    <a class="btn secondary" href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>">Exporter CSV</a>
                    <button type="button" class="btn secondary" onclick="window.print()">Imprimer le résumé</button>
                </div>
            </div>
            <div class="info-chip">Paiement prévu le <?= htmlspecialchars($periodPayment, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="summary-grid">
                <div class="summary-card">
                    <small>Total entrées</small>
                    <strong><?= number_format($summary['entries'], 2, ',', ' ') ?></strong>
                </div>
                <div class="summary-card">
                    <small>Total dépenses</small>
                    <strong><?= number_format($summary['expenses'], 2, ',', ' ') ?></strong>
                </div>
                <div class="summary-card">
                    <small>Net</small>
                    <strong><?= number_format($summary['net'], 2, ',', ' ') ?></strong>
                </div>
                <div class="summary-card accent">
                    <small>Paye chauffeur</small>
                    <strong><?= number_format($summary['salary'], 2, ',', ' ') ?></strong>
                </div>
            </div>
            <div class="comparison-box">
                <strong><?= htmlspecialchars($comparisonLabel, ENT_QUOTES, 'UTF-8') ?> : <?= number_format(abs($comparisonDelta), 2, ',', ' ') ?> <?= $comparisonDelta >= 0 ? 'd’entrées en surplus' : 'de dépenses en excès' ?></strong>
                <p>Comparaison entrées / dépenses : <?= number_format($comparisonPercent, 1, ',', ' ') ?>% <?= $comparisonDelta >= 0 ? 'de marge' : 'de dépassement' ?></p>
            </div>
        </section>

        <section class="panel summary-panel">
            <h2>Détail des entrées de la période</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Chauffeur</th>
                            <th>Date</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entries)): ?>
                            <tr>
                                <td colspan="3">Aucune entrée enregistrée pour cette période.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= htmlspecialchars($chauffeur['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($entry['date_entree'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float)$entry['montant'], 2, ',', ' ') ?></td>
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
