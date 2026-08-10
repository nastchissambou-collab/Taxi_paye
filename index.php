<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Taxi Paye</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Application locale</p>
                <h1>Gestion de paie chauffeur</h1>
            </div>
        </header>

        <main class="actions-grid">
            <a class="action-card" href="chauffeur.php">
                <span class="action-icon">👤</span>
                <strong>Créer chauffeur</strong>
                <small>Créer ou préparer le chauffeur principal</small>
            </a>

            <a class="action-card" href="entree.php">
                <span class="action-icon">💰</span>
                <strong>Entrée journalière</strong>
                <small>Ajouter les recettes du jour</small>
            </a>

            <a class="action-card" href="depense.php">
                <span class="action-icon">🧾</span>
                <strong>Dépense</strong>
                <small>Ajouter une dépense avec motif</small>
            </a>

            <a class="action-card" href="consultation.php">
                <span class="action-icon">📊</span>
                <strong>Consultation mensuelle</strong>
                <small>Voir le total du mois et la paie</small>
            </a>
        </main>
    </div>
</body>
</html>
