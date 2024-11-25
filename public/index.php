<?php
// http://localhost:8000/public/index.php

// public/index.php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../lib/vendor/autoload.php';
require_once '../src/controleur/_controleurs.php';
require_once '../config/parametres.php';
require_once '../config/connexion.php';
require_once '../src/modele/_classes.php';
include '../config/routes.php';

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/vue');
$twig = new \Twig\Environment($loader, ['cache' => false, ]);
$db = connect($config); 
$contenu = getPage($db);
$contenu($twig, $db);

?>
