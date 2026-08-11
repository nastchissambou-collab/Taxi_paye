<?php
require __DIR__ . '/sql/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');

    if ($nom === '' || $prenom === '') {
        $error = 'Le nom et le prénom sont obligatoires.';
    } else {
        $stmt = $conn->prepare('INSERT INTO chauffeur (nom, prenom, telephone) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $nom, $prenom, $telephone);
        if ($stmt->execute()) {
            $message = 'Chauffeur enregistré avec succès.';
        } else {
            $error = 'Erreur lors de l’enregistrement du chauffeur.';
        }
        $stmt->close();
    }
}

$chauffeursResult = $conn->query('SELECT id, nom, prenom, telephone FROM chauffeur ORDER BY id ASC');
$chauffeurs = $chauffeursResult ? $chauffeursResult->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Créer chauffeur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Chauffeur</p>
                <h1>Créer ou gérer le chauffeur</h1>
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
            <h2>Nouveau chauffeur</h2>
            <form method="post" class="form-grid">
                <label>Nom
                    <input type="text" name="nom" required>
                </label>
                <label>Prénom
                    <input type="text" name="prenom" required>
                </label>
                <label>Téléphone
                    <input type="text" name="telephone">
                </label>
                <button type="submit">Enregistrer</button>
            </form>
        </section>

        <?php if (!empty($chauffeurs)): ?>
            <section class="panel summary-panel">
                <h2>Chauffeurs enregistrés</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Nom</th><th>Prénom</th><th>Téléphone</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chauffeurs as $chauffeur): ?>
                                <tr>
                                    <td><?= htmlspecialchars($chauffeur['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($chauffeur['prenom'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($chauffeur['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
