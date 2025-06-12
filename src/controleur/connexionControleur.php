<?php

/**
 * Contrôleur de connexion utilisateur
 * Gère la connexion des utilisateurs avec validation et sécurité
 */
function connexionControleur($twig, $db) {
    // Inclure les fonctions de sécurité
    require_once __DIR__ . '/../middleware/security.php';
    
    $form = array();
    // Générer un token CSRF pour le formulaire
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $form['csrf_token'] = generateCsrfToken();
    
    // Message d'erreur personnalisé en fonction du type d'erreur
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'unauthorized':
                $form['message'] = 'Vous devez être connecté en tant qu\'administrateur pour accéder à cette page.';
                break;
            case 'session_expired':
                $form['message'] = 'Votre session a expiré, veuillez vous reconnecter.';
                break;
            case 'ip_not_allowed':
                $form['message'] = 'Votre adresse IP n\'est pas autorisée à accéder à l\'administration.';
                break;
            default:
                // Pas de message par défaut
                break;
        }
        $form['valide'] = false;
    }

    if (isset($_POST['btConnecter'])) {
        $form['valide'] = true;
        
        // Vérification du token CSRF
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            $form['valide'] = false;
            $form['message'] = 'Erreur de sécurité. Veuillez réessayer.';
            logSecurityEvent('CSRF token validation failed on login');
        } else {
            // Nettoyage et validation des entrées
            $inputEmail = filter_var($_POST['inputEmail'], FILTER_SANITIZE_EMAIL);
            $inputPassword = trim($_POST['inputPassword']);
            
            if (empty($inputEmail) || empty($inputPassword)) {
                $form['valide'] = false;
                $form['message'] = 'Veuillez remplir tous les champs.';
            } else {
                $utilisateurs = new utilisateurs($db);
                $unutilisateurs = $utilisateurs->connect($inputEmail);
                
                if ($unutilisateurs != null) {
                    if (!password_verify($inputPassword, $unutilisateurs['password'])) {
                        $form['valide'] = false;
                        $form['message_title'] = 'Échec de connexion';
                        $form['message'] = 'Le mot de passe que vous avez saisi ne correspond pas à cet identifiant. Veuillez vérifier vos informations et réessayer.';
                        $form['message_help'] = 'Avez-vous oublié votre mot de passe ? Utilisez le lien ci-dessous pour le réinitialiser.';
                        
                        // Journalisation des tentatives de connexion échouées
                        if (defined('LOG_FAILED_LOGINS') && LOG_FAILED_LOGINS) {
                            logSecurityEvent("Failed login attempt for user: {$inputEmail}");
                        }
                    } else {
                        // Connexion réussie
                        if (session_status() == PHP_SESSION_NONE) {
                            session_start();
                        }
                        
                        // Stockage des informations utilisateur en session
                        $_SESSION['login'] = $inputEmail;
                        $_SESSION['role'] = $unutilisateurs['idRole'];
                        $_SESSION['id'] = $unutilisateurs['id'];
                        $_SESSION['prenom'] = $unutilisateurs['prenom'];
                        $_SESSION['username'] = $unutilisateurs['username'];
                        $_SESSION['profile_picture'] = $unutilisateurs['profile_picture'];
                        
                        // Sécurité de session
                        $_SESSION['ip_address'] = getUserIp();
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['last_activity'] = time();
                        
                        // Log de connexion réussie pour les administrateurs
                        if ($unutilisateurs['idRole'] == 1) {
                            logAdminActivity('Connexion réussie');
                        }
                        
                        // Vérifier s'il existe un paramètre de redirection (POST prioritaire, sinon GET)
                        $redirect = null;
                        $type = null;
                        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                            $redirect = filter_var($_POST['redirect'], FILTER_SANITIZE_STRING);
                        } elseif (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                            $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_STRING);
                        }
                        if (isset($_POST['type']) && !empty($_POST['type'])) {
                            $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
                        } elseif (isset($_GET['type']) && !empty($_GET['type'])) {
                            $type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);
                        }
                        // Préparer une notification de succès avec le prénom si disponible
                        $welcomeMessage = "";
                        if (!empty($unutilisateurs['prenom'])) {
                            $welcomeMessage = "Bienvenue " . htmlspecialchars($unutilisateurs['prenom']) . " ! Vous êtes maintenant connecté.";
                        } else {
                            $welcomeMessage = "Vous êtes maintenant connecté à votre compte.";
                        }
                        $welcomeMessage = urlencode($welcomeMessage);

                        if ($redirect) {
                            $redirectUrl = "index.php?page=" . $redirect . "&auth_success=" . $welcomeMessage;
                            if ($type) {
                                $redirectUrl .= "&type=" . $type;
                            }
                            header("Location: " . $redirectUrl);
                        } else {
                            // Redirection vers la page d'accueil par défaut avec notification
                            header("Location: index.php?auth_success=" . $welcomeMessage);
                        }
                        exit;
                    }
                } else {
                    $form['valide'] = false;
                    $form['message_title'] = 'Compte introuvable';
                    $form['message'] = 'Aucun compte n\'est associé à cette adresse email. Vérifiez votre saisie ou créez un compte.';
                    $form['message_help'] = 'Vous pouvez créer un compte gratuitement en quelques instants.';
                    
                    // Journalisation des tentatives de connexion échouées
                    if (defined('LOG_FAILED_LOGINS') && LOG_FAILED_LOGINS) {
                        logSecurityEvent("Failed login attempt for non-existent user: {$inputEmail}");
                    }
                }
            }
        }
    }
    
    echo $twig->render('connexion.twig', array('form' => $form));
}

?>
