<?php
require __DIR__ . '/sql/config.php';

$successMessages = [];
$errorMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');

    if ($nom === '' || $prenom === '') {
        $errorMessages[] = 'Le nom et le prénom du chauffeur sont obligatoires.';
    } else {
        $stmt = $conn->prepare('INSERT INTO chauffeur (nom, prenom, telephone) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $nom, $prenom, $telephone);
        if ($stmt->execute()) {
            $successMessages[] = 'Chauffeur créé avec succès.';
        } else {
            $errorMessages[] = 'Erreur lors de la création du chauffeur : ' . $stmt->error;
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
    <title>Créer chauffeur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div>
                <p class="eyebrow">Accueil</p>
                <h1>Créer un chauffeur</h1>
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
                    Nom
                    <input type="text" name="nom" placeholder="" required>
                </label>
                <label>
                    Prénom
                    <input type="text" name="prenom" placeholder="" required>
                </label>
                <label>
                    Téléphone
                    <input type="text" name="telephone" placeholder="">
                </label>
                <button type="submit">Créer le chauffeur</button>
            </form>
        </section>
    </div>
</body>
</html>
<?php
$conn->close();
?>
