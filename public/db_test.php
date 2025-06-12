<?php
require_once __DIR__ . '/../config/parametres.php';
require_once __DIR__ . '/../config/connexion.php';

try {
    $db = connect($config);
    echo "Connexion DB réussie!";
} catch (PDOException $e) {
    echo "ERREUR DB: " . $e->getMessage();
}
