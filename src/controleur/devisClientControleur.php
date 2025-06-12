<?php
/**
 * Contrôleur pour la gestion des devis côté client
 */

/**
 * Fonction principale du contrôleur de devis client
 * 
 * @param object $twig Instance de Twig
 * @param object $db Instance de PDO
 */
function devisClientControleur($twig, $db) {
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['id'])) {
        header('Location: index.php?page=connexion&redirect=devis');
        exit;
    }
    
    $id_utilisateur = $_SESSION['id'];
    
    // Traiter les actions
    if (isset($_POST['annuler_devis'])) {
        annulerDevis($db, $_POST['id_devis'], $id_utilisateur, $_POST);
        header('Location: index.php?page=devis&annule=1');
        exit;
    }
    
    // Récupérer les devis de l'utilisateur
    $devis = new Devis($db);
    $liste_devis = $devis->getByUtilisateur($id_utilisateur);
    
    // Paramètres pour la recherche
    $recherche = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : '';
    
    // Filtrer les résultats si une recherche est effectuée
    if (!empty($recherche)) {
        $liste_devis = array_filter($liste_devis, function($d) use ($recherche) {
            return (
                stripos($d['service_nom'], $recherche) !== false ||
                stripos($d['id'], $recherche) !== false ||
                stripos($d['statut'], $recherche) !== false
            );
        });
    }
    
    // Afficher la liste des devis
    echo $twig->render('client/devis.twig', [
        'client_page' => 'devis',
        'devis' => $liste_devis,
        'recherche' => $recherche,
        'message' => isset($_GET['annule']) ? ['type' => 'success', 'text' => 'Votre demande de devis a été annulée avec succès.'] : null
    ]);
}

/**
 * Fonction pour afficher le détail d'un devis
 * 
 * @param object 
 * @param object 
 */
function devisDetailControleur($twig, $db) {
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['id'])) {
        header('Location: index.php?page=connexion&redirect=devis');
        exit;
    }
    
    $id_utilisateur = $_SESSION['id'];
    $id_devis = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_devis) {
        header('Location: index.php?page=devis');
        exit;
    }
    
    // Récupérer les informations du devis
    $devis = new Devis($db);
    $info_devis = $devis->getById($id_devis);
    
    // Vérifier que le devis appartient bien à l'utilisateur
    if (!$info_devis || $info_devis['id_utilisateur'] != $id_utilisateur) {
        header('Location: index.php?page=devis');
        exit;
    }
    
    $message = null;
    
    // Traiter les actions
    if (isset($_GET['action']) && $_GET['action'] == 'accepter' && $info_devis['statut'] == 'termine') {
        // Accepter le devis
        accepterDevis($db, $id_devis, $id_utilisateur);
        $message = ['type' => 'success', 'text' => 'Vous avez accepté ce devis. Nous vous contacterons prochainement pour planifier la suite.'];
        
        // Rafraîchir les informations du devis
        $info_devis = $devis->getById($id_devis);
    } elseif (isset($_POST['annuler_devis'])) {
        // Annuler le devis
        annulerDevis($db, $id_devis, $id_utilisateur, $_POST);
        header('Location: index.php?page=devis&annule=1');
        exit;
    } elseif (isset($_POST['envoyer_message'])) {
        // Envoyer un message
        envoyerMessage($db, $id_devis, $id_utilisateur, $_POST, $_FILES);
        $message = ['type' => 'success', 'text' => 'Votre message a été envoyé avec succès.'];
    } elseif (isset($_POST['contact_support'])) {
        // Contacter le support
        contacterSupport($db, $id_devis, $id_utilisateur, $_POST, $_FILES);
        $message = ['type' => 'success', 'text' => 'Votre message a été envoyé à notre équipe. Nous vous répondrons dans les plus brefs délais.'];
    }
    
    // Récupérer les messages associés au devis
    $messages = [];
    try {
        $messages_obj = new messages($db);
        $conversation = $messages_obj->getConversationByDevis($id_devis);
        
        if ($conversation) {
            $messages = $conversation['messages'];
            
            // Marquer les messages comme lus
            $messages_obj->markAsRead($conversation['id'], $id_utilisateur);
        }
    } catch (PDOException $e) {
        // Log l'erreur mais ne pas l'afficher à l'utilisateur
        error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
        // Si les tables nécessaires n'existent pas, ajouter un message au log
        if (strpos($e->getMessage(), "Table 'DBTom.conversations' doesn't exist") !== false) {
            error_log("La table 'conversations' n'existe pas. Veuillez exécuter le script add_missing_tables.sql.");
        }
    }
    
    // Afficher le détail du devis
    echo $twig->render('client/devis-detail.twig', [
        'client_page' => 'devis',
        'devis' => $info_devis,
        'messages' => $messages,
        'message' => $message
    ]);
}

/**
 * Annuler un devis
 * 
 * @param object 
 * @param int 
 * @param int 
 * @param array 
 * @return bool 
 */
function annulerDevis($db, $id_devis, $id_utilisateur, $data) {
    // Vérifier que le devis existe et appartient à l'utilisateur
    $devis = new Devis($db);
    $info_devis = $devis->getById($id_devis);
    
    if (!$info_devis || $info_devis['id_utilisateur'] != $id_utilisateur || $info_devis['statut'] != 'en_attente') {
        return false;
    }
    
    // Mettre à jour le statut du devis
    $success = $devis->updateStatut($id_devis, 'rejete');
    
    if ($success) {
        // Ajouter une note sur le motif d'annulation
        $motif = isset($data['motif_annulation']) ? $data['motif_annulation'] : 'non_specifie';
        $commentaire = isset($data['commentaire_annulation']) ? $data['commentaire_annulation'] : '';
        
        $notes = "Devis annulé par le client.\n";
        $notes .= "Motif: " . $motif . "\n";
        if (!empty($commentaire)) {
            $notes .= "Commentaire: " . $commentaire . "\n";
        }
        
        $devis->updateNotes($id_devis, $notes);
        
        // Notifier l'administrateur
        notifierAdminAnnulationDevis($info_devis, $motif, $commentaire);
    }
    
    return $success;
}

/**
 * Accepter un devis
 * 
 * @param object 
 * @param int 
 * @param int 
 * @return bool 
 */
function accepterDevis($db, $id_devis, $id_utilisateur) {
    // Vérifier que le devis existe et appartient à l'utilisateur
    $devis = new Devis($db);
    $info_devis = $devis->getById($id_devis);
    
    if (!$info_devis || $info_devis['id_utilisateur'] != $id_utilisateur || $info_devis['statut'] != 'termine') {
        return false;
    }
    
    // Mettre à jour le statut du devis
    $success = $devis->updateStatut($id_devis, 'accepte');
    
    if ($success) {
        // Ajouter une note
        $notes = $info_devis['notes_admin'] ?? '';
        $notes .= "\n\nDevis accepté par le client le " . date('d/m/Y à H:i') . ".";
        
        $devis->updateNotes($id_devis, $notes);
        
        // Notifier l'administrateur
        notifierAdminAcceptationDevis($info_devis);
        
        // Créer une conversation si elle n'existe pas déjà
        $messages_obj = new messages($db);
        $conversation = $messages_obj->getConversationByDevis($id_devis);
        
        if (!$conversation) {
            $id_conversation = $messages_obj->createConversation(
                $id_utilisateur,
                "Devis #" . $id_devis . " - " . $info_devis['service_nom'],
                'devis',
                $id_devis
            );
            
            if ($id_conversation) {
                // Ajouter un message automatique
                $messages_obj->addMessage(
                    $id_conversation,
                    0, // ID utilisateur 0 pour le système
                    "Votre devis a été accepté. Notre équipe va vous contacter prochainement pour planifier la suite des opérations.",
                    false // Message admin
                );
            }
        }
    }
    
    return $success;
}

/**
 * Envoyer un message dans la conversation du devis
 * 
 * @param object 
 * @param int 
 * @param int 
 * @param array 
 * @param array
 * @return bool 
 */
function envoyerMessage($db, $id_devis, $id_utilisateur, $data, $files) {
    if (empty($data['message'])) {
        return false;
    }
    
    try {
        $messages_obj = new messages($db);
        
        // Vérifier si une conversation existe déjà pour ce devis
        $conversation = $messages_obj->getConversationByDevis($id_devis);
    } catch (PDOException $e) {
        // Log l'erreur mais continuer à créer les tables nécessaires
        error_log("Erreur lors de la vérification des conversations: " . $e->getMessage());
        
        // Vérifier si les tables n'existent pas et suggérer d'exécuter le script
        if (strpos($e->getMessage(), "Table 'DBTom.conversations' doesn't exist") !== false) {
            error_log("La table 'conversations' n'existe pas. Essayez d'exécuter add_missing_tables.sql.");
            return false;
        }
        
        // Pour les autres erreurs, réessayer avec une nouvelle instance
        $messages_obj = new messages($db);
        $conversation = false;
    }
    
    if (!$conversation) {
        // Récupérer les informations du devis pour le titre de la conversation
        $devis = new Devis($db);
        $info_devis = $devis->getById($id_devis);
        
        // Créer une nouvelle conversation
        $id_conversation = $messages_obj->createConversation(
            $id_utilisateur,
            "Devis #" . $id_devis . " - " . $info_devis['service_nom'],
            'devis',
            $id_devis
        );
    } else {
        $id_conversation = $conversation['id'];
    }
    
    if (!$id_conversation) {
        return false;
    }
    
    // Ajouter le message
    $id_message = $messages_obj->addMessage(
        $id_conversation,
        $id_utilisateur,
        $data['message'],
        true // Message client
    );
    
    // Traiter la pièce jointe si présente
    if ($id_message && isset($files['attachment']) && $files['attachment']['error'] == 0) {
        $file = $files['attachment'];
        
        // Vérifier le type de fichier
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        $file_type = $file['type'];
        
        if (in_array($file_type, $allowed_types) && $file['size'] <= 5 * 1024 * 1024) { // 5 Mo max
            // Créer le dossier de destination s'il n'existe pas
            $upload_dir = 'public/uploads/devis/' . $id_devis . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $filename = $file['name'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
            $unique_filename = uniqid('attachment_') . '.' . $file_ext;
            $filepath = $upload_dir . $unique_filename;
            
            // Déplacer le fichier
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Enregistrer la pièce jointe dans la base de données
                $messages_obj->addAttachment($id_conversation, $id_message, $filename, $filepath);
            }
        }
    }
    
    // Notifier l'administrateur
    notifierAdminNouveauMessage($id_devis, $id_utilisateur, $data['message']);
    
    return true;
}

/**
 * Contacter le support à propos d'un devis
 * 
 * @param object
 * @param int 
 * @param int 
 * @param array 
 * @param array 
 * @return bool 
 */
function contacterSupport($db, $id_devis, $id_utilisateur, $data, $files) {
    if (empty($data['contact_message'])) {
        return false;
    }
    
    try {
        $messages_obj = new messages($db);
        
        // Vérifier si une conversation existe déjà pour ce devis
        $conversation = $messages_obj->getConversationByDevis($id_devis);
    } catch (PDOException $e) {
        // Log l'erreur mais continuer à créer les tables nécessaires
        error_log("Erreur lors de la vérification des conversations: " . $e->getMessage());
        
        // Vérifier si les tables n'existent pas et suggérer d'exécuter le script
        if (strpos($e->getMessage(), "Table 'DBTom.conversations' doesn't exist") !== false) {
            error_log("La table 'conversations' n'existe pas. Essayez d'exécuter add_missing_tables.sql.");
            return false;
        }
        
        // Pour les autres erreurs, réessayer avec une nouvelle instance
        $messages_obj = new messages($db);
        $conversation = false;
    }
    
    if (!$conversation) {
        // Récupérer les informations du devis pour le titre de la conversation
        $devis = new Devis($db);
        $info_devis = $devis->getById($id_devis);
        
        // Créer une nouvelle conversation
        $id_conversation = $messages_obj->createConversation(
            $id_utilisateur,
            "Devis #" . $id_devis . " - " . $info_devis['service_nom'],
            'devis',
            $id_devis
        );
    } else {
        $id_conversation = $conversation['id'];
    }
    
    if (!$id_conversation) {
        return false;
    }
    
    // Préparer le message avec le sujet
    $sujet = isset($data['contact_sujet']) ? $data['contact_sujet'] : 'question';
    $message = "[" . ucfirst(str_replace('_', ' ', $sujet)) . "]\n\n" . $data['contact_message'];
    
    // Ajouter le message
    $id_message = $messages_obj->addMessage(
        $id_conversation,
        $id_utilisateur,
        $message,
        true // Message client
    );
    
    // Traiter la pièce jointe si présente
    if ($id_message && isset($files['contact_attachment']) && $files['contact_attachment']['error'] == 0) {
        $file = $files['contact_attachment'];
        
        // Vérifier le type de fichier
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        $file_type = $file['type'];
        
        if (in_array($file_type, $allowed_types) && $file['size'] <= 5 * 1024 * 1024) { // 5 Mo max
            // Créer le dossier de destination s'il n'existe pas
            $upload_dir = 'public/uploads/devis/' . $id_devis . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $filename = $file['name'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
            $unique_filename = uniqid('attachment_') . '.' . $file_ext;
            $filepath = $upload_dir . $unique_filename;
            
            // Déplacer le fichier
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Enregistrer la pièce jointe dans la base de données
                $messages_obj->addAttachment($id_conversation, $id_message, $filename, $filepath);
            }
        }
    }
    
    // Notifier l'administrateur
    notifierAdminNouveauMessage($id_devis, $id_utilisateur, $message, $sujet);
    
    return true;
}

/**
 * Notifier l'administrateur de l'annulation d'un devis
 * 
 * @param array $devis Informations du devis
 * @param string $motif Motif d'annulation
 * @param string $commentaire Commentaire d'annulation
 * @return bool Succès de l'envoi
 */
function notifierAdminAnnulationDevis($devis, $motif, $commentaire) {
    // Implémentation à compléter ** à revenir dessus
    // Pour l'instant, simule un envoi réussi
    return true;
}

/**
 * Notifier l'administrateur de l'acceptation d'un devis
 * 
 * @param array $devis Informations du devis
 * @return bool Succès de l'envoi
 */
function notifierAdminAcceptationDevis($devis) {
    // à compléter
    // simule un envoi réussi avec true
    return true;
}

/**
 * Notifier l'administrateur d'un nouveau message
 * 
 * @param int $id_devis ID du devis
 * @param int $id_utilisateur ID de l'utilisateur
 * @param string $message Contenu du message
 * @param string $sujet Sujet du message (optionnel)
 * @return bool Succès de l'envoi
 */
function notifierAdminNouveauMessage($id_devis, $id_utilisateur, $message, $sujet = null) {
    // same
    return true;
}
