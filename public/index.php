<?php
// http://localhost:/public/index.php

// public/index.php

// Include centralized session management
require_once dirname(__DIR__) . '/src/middleware/session_manager.php';
initSession(); // Initialize session once at the beginning

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$rootDir = dirname(__DIR__);
require_once $rootDir . '/lib/vendor/autoload.php';
require_once $rootDir . '/src/controleur/_controleurs.php';
require_once $rootDir . '/config/parametres.php';
require_once $rootDir . '/config/connexion.php';
require_once $rootDir . '/src/modele/_classes.php';
include $rootDir . '/config/routes.php';

// Include our custom Twig extension
require_once $rootDir . '/src/twig/AssetExtension.php';

$loader = new \Twig\Loader\FilesystemLoader($rootDir . '/src/vue');
$twig = new \Twig\Environment($loader, []);
$twig->addGlobal('session', $_SESSION);
// Register the asset extension
$twig->addExtension(new \App\Twig\AssetExtension());
$db = connect($config); 
$contenu = getPage($db); // $contenu is the string name of the controller, e.g., "pcSurMesureControleur"

// New dispatch logic to handle namespaced class controllers, non-namespaced class controllers, and old function-based controllers
$controllerClassName = "App\\Controleur\\" . ucfirst($contenu);
$controllerClassNameWithoutNamespace = $contenu;

if (class_exists($controllerClassName)) {
    // This is a new, namespaced, class-based controller
    try {
        // Assumes AbstractControleur (and thus its children) takes $twig and $db in constructor
        $controllerInstance = new $controllerClassName($twig, $db); 
        
        // Determine action: for now, 'index' is the default for new controllers.
        $action = 'index'; 

        if (method_exists($controllerInstance, $action)) {
            $controllerInstance->$action();
        } else {
            error_log("Action '$action' not found in controller '$controllerClassName'");
            echo "Error: Controller action not found.";
        }
    } catch (Throwable $e) {
        error_log("Error dispatching to controller '$controllerClassName': " . $e->getMessage());
        echo "An unexpected error occurred. Please try again later.";
    }
} elseif (class_exists($controllerClassNameWithoutNamespace)) {
    // This is a non-namespaced, class-based controller
    try {
        $controllerInstance = new $controllerClassNameWithoutNamespace($twig, $db); 
        
        // Determine action: for now, 'index' is the default for new controllers.
        $action = 'index'; 

        if (method_exists($controllerInstance, $action)) {
            $controllerInstance->$action();
        } else {
            error_log("Action '$action' not found in controller '$controllerClassNameWithoutNamespace'");
            echo "Error: Controller action not found.";
        }
    } catch (Throwable $e) {
        error_log("Error dispatching to controller '$controllerClassNameWithoutNamespace': " . $e->getMessage());
        echo "An unexpected error occurred. Please try again later.";
    }
} else {
    // This is an old, function-based controller
    if (function_exists($contenu)) {
        if (isset($_GET['page']) && in_array($_GET['page'], ['legal', 'cgv', 'politique-de-confidentialite', 'mentions-legales', 'cookies'])) {
            // Specific call for legal pages that take an additional parameter
            $contenu($twig, $db, $_GET['page']);
        } else {
            $contenu($twig, $db);
        }
    } else {
        // Controller function doesn't exist
        error_log("Controller function '$contenu' not found.");
        // Fallback to a generic error or 404 display
        echo "Error: Page controller not found.";
    }
}

?>
