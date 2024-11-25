<?php

$host ='renardserveur.freeboxos.fr';
$dbname = 'DBTom';
$user ='tom';
$password = 'I^&b#vLg*5aLsGz#*91j';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion à la base de données réussie !";

} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}