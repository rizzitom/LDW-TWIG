<?php

// Configuration globale de l'application - Paramètres critiques et accès aux services externes
$config['serveur']='localhost';

$config['bd'] = 'ldw';

$config['login'] ='root';

$config['password'] = '';





// Durées des sessions utilisateurs
define('SESSION_TIMEOUT', 3600);
define('CLIENT_SESSION_TIMEOUT', 86400);


// Journalisation des événements système
define('LOG_ADMIN_ACTIONS', true);
define('LOG_FAILED_LOGINS', true);


// Configuration des uploads utilisateurs
define('PROFILE_PICTURE_WIDTH', 250);
define('PROFILE_PICTURE_HEIGHT', 250);
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024);


// Chemins des dossiers d'upload (relatifs à la racine du site)

define('UPLOAD_BASE_DIR', '/public/images/uploads/');

define('PROFILE_PICTURES_DIR', UPLOAD_BASE_DIR . 'profile_pictures/');

define('PRODUCTS_IMAGES_DIR', '/public/images/produits/');

define('MESSAGE_ATTACHMENTS_DIR', UPLOAD_BASE_DIR . 'messages/');



// ... reste de la configuration

?>
