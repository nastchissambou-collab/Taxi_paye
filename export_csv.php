<?php
require __DIR__ . '/sql/config.php';

$selectedAnnee = (int)($_GET['annee'] ?? date('Y'));
$selectedMois = (int)($_GET['mois'] ?? date('n'));

$nextMonth = $selectedMois == 12 ? 1 : $selectedMois + 1;
$nextYear = $selectedMois == 12 ? $selectedAnnee + 1 : $selectedAnnee;

$periodStart = date('Y-m-d', mktime(0, 0, 0, $selectedMois, 5, $selectedAnnee));
$periodEnd = date('Y-m-d', mktime(0, 0, 0, $nextMonth, 4, $nextYear));

$chauffeursResult = $conn->query('SELECT id, nom, prenom FROM chauffeur ORDER BY id ASC LIMIT 1');
$chauffeur = $chauffeursResult ? $chauffeursResult->fetch_assoc() : null;

if (!$chauffeur) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export-paie.csv"');
    echo "Aucun chauffeur disponible\n";
    exit;
}

$entriesQuery = $conn->prepare('SELECT date_entree, montant FROM entree_journaliere WHERE chauffeur_id = ? AND date_entree BETWEEN ? AND ? ORDER BY date_entree ASC');
$entriesQuery->bind_param('iss', $chauffeur['id'], $periodStart, $periodEnd);
$entriesQuery->execute();
$entries = $entriesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$entriesQuery->close();

$depensesQuery = $conn->prepare('SELECT date_depense, montant, motif FROM depense WHERE chauffeur_id = ? AND date_depense BETWEEN ? AND ? ORDER BY date_depense ASC');
$depensesQuery->bind_param('iss', $chauffeur['id'], $periodStart, $periodEnd);
$depensesQuery->execute();
$depenses = $depensesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$depensesQuery->close();

$totalEntries = 0;
foreach ($entries as $entry) {
    $totalEntries += (float)$entry['montant'];
}

$totalExpenses = 0;
foreach ($depenses as $depense) {
    $totalExpenses += (float)$depense['montant'];
}

$salary = round($totalEntries * 0.25, 2);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export-paie-' . $selectedAnnee . '-' . $selectedMois . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Type', 'Date', 'Montant', 'Motif', 'Chauffeur']);

foreach ($entries as $entry) {
    fputcsv($output, ['Entree', $entry['date_entree'], number_format((float)$entry['montant'], 2, '.', ''), '', $chauffeur['prenom']]);
}

foreach ($depenses as $depense) {
    fputcsv($output, ['Depense', $depense['date_depense'], number_format((float)$depense['montant'], 2, '.', ''), $depense['motif'] ?? '', $chauffeur['prenom']]);
}

fputcsv($output, []);
fputcsv($output, ['Resume', '', '', '', '']);
fputcsv($output, ['Total entrees', '', number_format($totalEntries, 2, '.', ''), '', '']);
fputcsv($output, ['Total depenses', '', number_format($totalExpenses, 2, '.', ''), '', '']);
fputcsv($output, ['Net', '', number_format($totalEntries - $totalExpenses, 2, '.', ''), '', '']);
fputcsv($output, ['Paye chauffeur', '', number_format($salary, 2, '.', ''), '', '']);

fclose($output);
$conn->close();
exit;
