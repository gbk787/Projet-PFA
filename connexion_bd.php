<?php
// Connexion à la base de données MySQL
$conn = new mysqli("127.0.0.1", "root", "", "tinysteps");

if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>