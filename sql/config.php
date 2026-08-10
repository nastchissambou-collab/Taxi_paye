<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taxi_paye";
$port = 3307; // IMPORTANT

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Vérification connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

?>