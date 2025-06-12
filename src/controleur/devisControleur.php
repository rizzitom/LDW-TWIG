<?php
/**
 * Contrôleur pour la gestion des demandes de devis
 */

/**
 * Fonction principale du contrôleur de devis
 * 
 * @param object 
 * @param object 
 */
function devisControleur($twig, $db) {
    // Définir la constante _BASE_URL_ pour les redirections
    if (!defined('_BASE_URL_')) {
        define('_BASE_URL_', 'index.php?page=');
    }
    
    // (Suppression de la vérification de connexion globale ici)
    
    // Traiter les actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'envoyer':
                envoyerDevis();
                break;
            default:
                // Action par défaut : afficher le formulaire
                if (isset($_GET['type'])) {
                    afficherFormulaireDevis($twig, $_GET['type']);
                } else {
                    // Afficher la page de sélection de devis
                    afficherPageSelectionDevis($twig);
                }
        }
    } else {
        // Afficher le formulaire si un type est spécifié
        if (isset($_GET['type'])) {
            afficherFormulaireDevis($twig, $_GET['type']);
        } else {
            // Afficher la page de sélection de devis
            afficherPageSelectionDevis($twig);
        }
    }
}

/**
 * Affiche la page de sélection de devis
 * 
 * @param object $twig Instance de Twig
 */
function afficherPageSelectionDevis($twig) {
    // Préparer les données pour le template
    $data = [];
    
    // S'assurer que la session est démarrée
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vérifier si l'utilisateur est connecté
    $data['utilisateur_connecte'] = isset($_SESSION['id']);
    
    echo $twig->render('devis.twig', $data);
}

/**
 * Affiche le formulaire de devis adapté au type de service
 * 
 * @param object $twig Instance de Twig
 * @param string $type_service Type de service demandé
 */
function afficherFormulaireDevis($twig, $type_service = null) {
    // Vérifier le type de service demandé
    $types_valides = ['developpement', 'maintenance', 'montage', 'montage-pc', 'developpement-web', 'copywriting', 'seo'];
    
    if (!in_array($type_service, $types_valides)) {
        // Rediriger vers la page des services si le type n'est pas valide
        header('Location: ' . _BASE_URL_ . 'devis');
        exit;
    }

    // S'assurer que la session est démarrée
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Vérifier que l'utilisateur est connecté, sinon rediriger vers la connexion
    if (!isset($_SESSION['id'])) {
        $redirectUrl = _BASE_URL_ . 'connexion?redirect=devis&type=' . $type_service;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Vérifier si le template du formulaire existe
    $template_path = 'formulaires/devis-' . $type_service . '.twig';
    $template_full_path = __DIR__ . '/../vue/' . $template_path;
    if (file_exists($template_full_path)) {
        // Afficher le formulaire spécifique au type de service
        echo $twig->render($template_path, ['type' => $type_service]);
    } else {
        // Fallback : afficher la page de sélection
        afficherPageSelectionDevis($twig);
    }
}

/**
 * Traite l'envoi d'une demande de devis
 */
function envoyerDevis() {
    global $db, $twig;
    
    // Vérifier que la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Rediriger vers la page des devis
        header('Location: ' . _BASE_URL_ . 'devis');
        exit;
    }

    // S'assurer que la session est démarrée
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Vérifier que l'utilisateur est connecté, sinon rediriger vers la connexion
    if (!isset($_SESSION['id'])) {
        $type_service = isset($_POST['type_service']) ? htmlspecialchars($_POST['type_service']) : '';
        $redirectUrl = _BASE_URL_ . 'connexion?redirect=devis&type=' . $type_service;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Récupérer le type de service
    $type_service = isset($_POST['type_service']) ? htmlspecialchars($_POST['type_service']) : '';
    
    if (empty($type_service)) {
        // Afficher un message d'erreur
        echo $twig->render('devis.twig', [
            'error' => 'Type de service non spécifié',
            'post_data' => $_POST
        ]);
        return;
    }
    
    // Validation des champs obligatoires communs
    $champs_obligatoires = ['nom', 'prenom', 'email', 'conditions'];
    
    // Ajouter des champs obligatoires spécifiques selon le type de service
    switch ($type_service) {
        case 'developpement-web':
            $champs_obligatoires = array_merge($champs_obligatoires, ['titre_projet', 'type_projet', 'description_projet']);
            break;
        case 'developpement':
            $champs_obligatoires = array_merge($champs_obligatoires, ['titre_projet', 'type_projet', 'description_projet']);
            break;
        case 'maintenance':
            $champs_obligatoires = array_merge($champs_obligatoires, ['type_client', 'type_maintenance', 'frequence', 'description_probleme']);
            break;
        case 'montage':
        case 'montage-pc':
            $champs_obligatoires = array_merge($champs_obligatoires, ['type_utilisation', 'budget']);
            break;
        default:
            // Afficher un message d'erreur
            echo $twig->render('devis.twig', [
                'error' => 'Type de service non reconnu',
                'post_data' => $_POST
            ]);
            return;
    }
    
    // Vérifier que tous les champs obligatoires sont présents
    $erreurs = [];
    foreach ($champs_obligatoires as $champ) {
        if (!isset($_POST[$champ]) || empty($_POST[$champ])) {
            $erreurs[] = 'Le champ ' . $champ . ' est obligatoire';
        }
    }
    
    // Valider l'email
    if (isset($_POST['email']) && !empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Adresse email invalide';
    }
    
    // S'il y a des erreurs, afficher le formulaire avec les erreurs
    if (!empty($erreurs)) {
        echo $twig->render('devis.twig', [
            'type' => $type_service,
            'erreurs' => $erreurs,
            'post_data' => $_POST
        ]);
        return;
    }
    
    // Récupérer l'ID du service correspondant
    $service_id = getServiceIdByType($type_service);
    if (!$service_id) {
        echo $twig->render('devis.twig', [
            'error' => 'Service non trouvé',
            'post_data' => $_POST
        ]);
        return;
    }
    
    // Récupérer l'ID de l'utilisateur connecté
    $id_utilisateur = $_SESSION['id'];
    
    // Vérification supplémentaire (en cas d'utilisation de l'API)
    if (!$id_utilisateur) {
        echo $twig->render('devis.twig', [
            'error' => 'Vous devez être connecté pour envoyer une demande de devis',
            'post_data' => $_POST
        ]);
        return;
    }
    
    // Préparer la description de la demande
    $description_demande = preparerDescriptionDemande($_POST, $type_service);
    
    // Enregistrer la demande de devis
    $devis = new Devis($db);
    $id_devis = $devis->creer($id_utilisateur, $service_id, $description_demande);
    
    if (!$id_devis) {
        echo $twig->render('devis.twig', [
            'error' => 'Erreur lors de l\'enregistrement de la demande',
            'post_data' => $_POST
        ]);
        return;
    }
    
    // Envoyer un email de confirmation au client
    envoyerEmailConfirmationClient($_POST['email'], $_POST['nom'], $_POST['prenom'], $type_service);
    
    // Envoyer une notification à l'administrateur
    envoyerNotificationAdmin($id_devis, $type_service, $_POST);
    
    // Générer une référence pour le devis
    $reference = 'DEV-' . date('Ymd') . '-' . str_pad($id_devis, 4, '0', STR_PAD_LEFT);
    
    // Afficher la page de succès
    echo $twig->render('devis.twig', [
        'confirmation' => "Votre demande de devis a bien été envoyée. Nous vous contacterons rapidement.",
        'reference' => $reference,
        'id_devis' => $id_devis
    ]);
}

/**
 * Récupère l'ID du service correspondant au type de service
 * 
 * @param string $type_service Type de service
 * @return int|false ID du service ou false si non trouvé
 */
function getServiceIdByType($type_service) {
    global $db;
    
    // Mapping des types de service vers les noms de services dans la base de données
    $mapping = [
        'developpement' => 'Développement Web',
        'developpement-web' => 'Développement Web',
        'copywriting' => 'CopyWriting',
        'seo' => 'SEO/SEA',
        'montage' => 'Montage PC sur mesure',
        'montage-pc' => 'Montage PC sur mesure'
    ];
    
    if (!isset($mapping[$type_service])) {
        return false;
    }
    
    $nom_service = $mapping[$type_service];
    
    $stmt = $db->prepare("SELECT id FROM services WHERE nom = :nom AND est_actif = 1");
    $stmt->execute([':nom' => $nom_service]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['id'] : false;
}

/**
 * Crée un utilisateur temporaire pour les demandes de devis sans compte
 * 
 * @param array $data Données du formulaire
 * @return int|false ID de l'utilisateur créé ou false en cas d'erreur
 */
function creerUtilisateurTemporaire($data) {
    global $db;
    
    // Vérifier si l'utilisateur existe déjà avec cet email
    $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = :email");
    $stmt->execute([':email' => $data['email']]);
    
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($utilisateur) {
        return $utilisateur['id'];
    }
    
    // Créer un nouvel utilisateur temporaire
    $stmt = $db->prepare("INSERT INTO utilisateurs (email, username, password, nom, prenom, idRole, date_inscription) 
                         VALUES (:email, :username, :password, :nom, :prenom, 2, NOW())");
    
    $username = strtolower($data['prenom'] . '.' . $data['nom']);
    $password = password_hash(uniqid(), PASSWORD_DEFAULT); // Mot de passe aléatoire
    
    $success = $stmt->execute([
        ':email' => $data['email'],
        ':username' => $username,
        ':password' => $password,
        ':nom' => $data['nom'],
        ':prenom' => $data['prenom']
    ]);
    
    return $success ? $db->lastInsertId() : false;
}

/**
 * Prépare la description de la demande de devis à partir des données du formulaire
 * 
 * @param array $data Données du formulaire
 * @param string $type_service Type de service
 * @return string Description formatée
 */
function preparerDescriptionDemande($data, $type_service) {
    $description = '';
    
    // Ajouter les informations communes
    $description .= "Nom: " . $data['nom'] . "\n";
    $description .= "Prénom: " . $data['prenom'] . "\n";
    $description .= "Email: " . $data['email'] . "\n";
    
    if (isset($data['telephone']) && !empty($data['telephone'])) {
        $description .= "Téléphone: " . $data['telephone'] . "\n";
    }
    
    $description .= "\n--- Détails de la demande ---\n\n";
    
    // Ajouter les informations spécifiques selon le type de service
    switch ($type_service) {
        case 'developpement-web':
        case 'developpement':
            $description .= "Titre du projet: " . $data['titre_projet'] . "\n";
            $description .= "Type de projet: " . $data['type_projet'] . "\n";
            $description .= "Description du projet: " . $data['description_projet'] . "\n";
            
            if (isset($data['fonctionnalites']) && is_array($data['fonctionnalites'])) {
                $description .= "Fonctionnalités souhaitées: " . implode(", ", $data['fonctionnalites']) . "\n";
            }
            
            if (isset($data['budget']) && !empty($data['budget'])) {
                $description .= "Budget: " . $data['budget'] . "\n";
            }
            
            if (isset($data['delai']) && !empty($data['delai'])) {
                $description .= "Délai souhaité: " . $data['delai'] . "\n";
            }
            break;
            
        case 'maintenance':
            $description .= "Type de client: " . $data['type_client'] . "\n";
            $description .= "Type de maintenance: " . $data['type_maintenance'] . "\n";
            $description .= "Fréquence: " . $data['frequence'] . "\n";
            $description .= "Description du problème: " . $data['description_probleme'] . "\n";
            
            if (isset($data['equipements']) && is_array($data['equipements'])) {
                $description .= "Équipements concernés: " . implode(", ", $data['equipements']) . "\n";
            }
            
            if (isset($data['urgence']) && !empty($data['urgence'])) {
                $description .= "Niveau d'urgence: " . $data['urgence'] . "\n";
            }
            
            if (isset($data['mode_intervention']) && !empty($data['mode_intervention'])) {
                $description .= "Mode d'intervention préféré: " . $data['mode_intervention'] . "\n";
            }
            break;
            
        case 'montage':
            $description .= "Utilisation principale: " . $data['type_utilisation'] . "\n";
            $description .= "Budget: " . $data['budget'] . "\n";
            
            if (isset($data['precision_utilisation']) && !empty($data['precision_utilisation'])) {
                $description .= "Précisions sur l'utilisation: " . $data['precision_utilisation'] . "\n";
            }
            
            if (isset($data['niveau_performance']) && !empty($data['niveau_performance'])) {
                $description .= "Niveau de performance souhaité: " . $data['niveau_performance'] . "/5\n";
            }
            
            // Ajouter les préférences de composants
            $description .= "\nPréférences de composants:\n";
            
            if (isset($data['cpu_marque']) && !empty($data['cpu_marque'])) {
                $description .= "- CPU: " . $data['cpu_marque'];
                if (isset($data['cpu_modele']) && !empty($data['cpu_modele'])) {
                    $description .= " (" . $data['cpu_modele'] . ")";
                }
                $description .= "\n";
            }
            
            if (isset($data['gpu_marque']) && !empty($data['gpu_marque'])) {
                $description .= "- GPU: " . $data['gpu_marque'];
                if (isset($data['gpu_modele']) && !empty($data['gpu_modele'])) {
                    $description .= " (" . $data['gpu_modele'] . ")";
                }
                $description .= "\n";
            }
            
            if (isset($data['ram_capacite']) && !empty($data['ram_capacite'])) {
                $description .= "- RAM: " . $data['ram_capacite'] . " Go";
                if (isset($data['ram_frequence']) && !empty($data['ram_frequence'])) {
                    $description .= " (" . $data['ram_frequence'] . " MHz)";
                }
                $description .= "\n";
            }
            
            if (isset($data['stockage_type']) && !empty($data['stockage_type'])) {
                $description .= "- Stockage: " . $data['stockage_type'];
                if (isset($data['stockage_capacite']) && !empty($data['stockage_capacite'])) {
                    $description .= " (" . $data['stockage_capacite'] . ")";
                }
                $description .= "\n";
            }
            
            if (isset($data['refroidissement_type']) && !empty($data['refroidissement_type'])) {
                $description .= "- Refroidissement: " . $data['refroidissement_type'] . "\n";
            }
            
            if (isset($data['boitier_format']) && !empty($data['boitier_format'])) {
                $description .= "- Boîtier: " . $data['boitier_format'];
                if (isset($data['boitier_preference']) && !empty($data['boitier_preference'])) {
                    $description .= " (" . $data['boitier_preference'] . ")";
                }
                $description .= "\n";
            }
            
            // Périphériques
            if (isset($data['peripheriques']) && is_array($data['peripheriques'])) {
                $description .= "\nPériphériques souhaités: " . implode(", ", $data['peripheriques']) . "\n";
                
                if (isset($data['periph_details']) && !empty($data['periph_details'])) {
                    $description .= "Détails périphériques: " . $data['periph_details'] . "\n";
                }
            }
            
            if (isset($data['os_preference']) && !empty($data['os_preference'])) {
                $description .= "Système d'exploitation: " . $data['os_preference'] . "\n";
            }
            break;
    }
    
    // Ajouter les commentaires supplémentaires
    if (isset($data['commentaires']) && !empty($data['commentaires'])) {
        $description .= "\nCommentaires supplémentaires: " . $data['commentaires'] . "\n";
    }
    
    // Ajouter la source
    if (isset($data['source']) && !empty($data['source'])) {
        $description .= "\nSource: " . $data['source'] . "\n";
    }
    
    return $description;
}

/**
 * Envoie un email de confirmation au client
 * 
 * @param string $email Email du client
 * @param string $nom Nom du client
 * @param string $prenom Prénom du client
 * @param string $type_service Type de service demandé
 * @return bool Succès de l'envoi
 */
function envoyerEmailConfirmationClient($email, $nom, $prenom, $type_service) {
    // Mapping des types de service vers des noms plus lisibles
    $services_noms = [
        'developpement' => 'Développement Web',
        'developpement-web' => 'Développement Web',
        'copywriting' => 'CopyWriting',
        'seo' => 'SEO/SEA',
        'montage' => 'PC sur Mesure',
        'montage-pc' => 'PC sur Mesure'
    ];
    
    $service_nom = isset($services_noms[$type_service]) ? $services_noms[$type_service] : 'Service';
    
    $sujet = "Confirmation de votre demande de devis - " . $service_nom;
    
    $message = "Bonjour " . $prenom . " " . $nom . ",\n\n";
    $message .= "Nous avons bien reçu votre demande de devis pour notre service de " . $service_nom . ".\n\n";
    $message .= "Notre équipe va étudier votre demande et vous contactera dans les plus brefs délais pour vous proposer un devis personnalisé.\n\n";
    $message .= "Si vous avez des questions ou des informations complémentaires à nous communiquer, n'hésitez pas à nous contacter.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe LDW";
    
    // Utilisation de mail() pour l'envoi d'email
    $headers = "From: Le Design Du Web <contact@ledesignduweb.com>\r\n";
    $headers .= "Reply-To: contact@ledesignduweb.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Tentative d'envoi d'email
    $mail_sent = mail($email, $sujet, $message, $headers);
    
    // Log de l'envoi (pour debug)
    error_log("Tentative d'envoi d'email à $email pour devis $type_service: " . ($mail_sent ? "Succès" : "Échec"));
    
    return $mail_sent;
}

/**
 * Envoie une notification à l'administrateur pour une nouvelle demande de devis
 * 
 * @param int $id_devis ID du devis
 * @param string $type_service Type de service
 * @param array $data Données du formulaire
 * @return bool Succès de l'envoi
 */
function envoyerNotificationAdmin($id_devis, $type_service, $data) {
    // Mapping des types de service vers des noms plus lisibles
    $services_noms = [
        'developpement' => 'Développement Web',
        'developpement-web' => 'Développement Web',
        'copywriting' => 'CopyWriting',
        'seo' => 'SEO/SEA',
        'montage' => 'PC sur Mesure',
        'montage-pc' => 'PC sur Mesure'
    ];
    
    $service_nom = isset($services_noms[$type_service]) ? $services_noms[$type_service] : 'Service';
    $reference = 'DEV-' . date('Ymd') . '-' . str_pad($id_devis, 4, '0', STR_PAD_LEFT);
    
    $sujet = "Nouvelle demande de devis #$reference - " . $service_nom;
    
    $message = "Une nouvelle demande de devis a été reçue.\n\n";
    $message .= "Référence: " . $reference . "\n";
    $message .= "ID du devis: " . $id_devis . "\n";
    $message .= "Service: " . $service_nom . "\n";
    $message .= "Client: " . $data['prenom'] . " " . $data['nom'] . "\n";
    $message .= "Email: " . $data['email'] . "\n";
    
    if (isset($data['telephone']) && !empty($data['telephone'])) {
        $message .= "Téléphone: " . $data['telephone'] . "\n";
    }
    
    if (isset($data['societe']) && !empty($data['societe'])) {
        $message .= "Société: " . $data['societe'] . "\n";
    }
    
    if ($type_service == 'developpement-web' && isset($data['titre_projet'])) {
        $message .= "\nProjet: " . $data['titre_projet'] . "\n";
        if (isset($data['type_projet']) && !empty($data['type_projet'])) {
            $message .= "Type: " . $data['type_projet'] . "\n";
        }
    }
    
    $message .= "\nVous pouvez consulter les détails complets de cette demande dans votre tableau de bord administrateur: ";
    $message .= "http://ledesignduweb.com/admin/devis?id=" . $id_devis;
    
    // Envoi de l'email à l'admin
    $admin_email = "admin@ldw.fr"; // À configurer dans les paramètres
    $headers = "From: Le Design Du Web <system@ldw.fr>\r\n";
    $headers .= "Reply-To: " . $data['email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Tentative d'envoi d'email
    $mail_sent = mail($admin_email, $sujet, $message, $headers);
    
    // Log de l'envoi
    error_log("Notification admin pour devis #$reference: " . ($mail_sent ? "Succès" : "Échec"));
    
    return $mail_sent;
}

/**
 * Répond avec un JSON formaté
 * 
 * @param array $data Données à encoder en JSON
 */
function repondreJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
