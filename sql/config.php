<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'taxi_paye';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die('Connexion impossible à la base de données : ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
