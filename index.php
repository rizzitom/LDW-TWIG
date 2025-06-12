<?php
/**
 * Point d'entrée principal du site
 * Ce fichier sert de point d'entrée sécurisé pour toute l'application
 */

// Définir les chemins importants
define('ROOT_PATH', __DIR__);
define('SRC_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Vérifier si nous sommes dans une boucle de redirection
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, '/index.php/index.php') !== false) {
    // Rediriger vers la page d'accueil pour briser la boucle
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://ledesignduweb.com/');
    exit();
}

// Inclure directement le fichier public/index.php au lieu de rediriger
require_once __DIR__ . '/public/index.php';
