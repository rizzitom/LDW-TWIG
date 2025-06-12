<?php

function clientControleur($twig, $db) {
    // Cette fonction dispatche vers les bonnes fonctions en fonction de la page demandée
    
    // Vérifier que l'utilisateur est connecté avec notre nouvelle fonction
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session à chaque accès à l'espace client
    refreshSession();
    
    // Déterminer quelle fonction appeler en fonction de la page
    $page = isset($_GET['page']) ? $_GET['page'] : 'mon_compte';
    
    switch ($page) {
        case 'commande':
            return commande($twig, $db);
        case 'commandes':
            return commandes($twig, $db);
        case 'adresses':
            return adresses($twig, $db);
        case 'messages':
            return messages($twig, $db);
        case 'securite':
            return securite($twig, $db);
        case 'mon_compte':
        default:
            // Par défaut, on affiche le tableau de bord du compte
            return mon_compte($twig, $db);
    }
}

function mon_compte($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Récupération des données de l'utilisateur
    $utilisateur = new utilisateurs($db);
    $client = $utilisateur->getById($_SESSION['id']);
    
    // Récupération des dernières commandes
    $commandes = new Commande($db);
    $dernieres_commandes = $commandes->getByUtilisateur($_SESSION['id']);
    
    // Récupérer l'adresse principale
    $adresses = new adresses($db);
    $adresse_principale = $adresses->getAdressePrincipale($_SESSION['id']);
    
    // Statistiques du client
    $stats = [
        'commandes' => count($dernieres_commandes),
        'depenses' => array_sum(array_column($dernieres_commandes, 'montant_total')),
        'points' => isset($client['points_fidelite']) ? $client['points_fidelite'] : 0,
        'devis' => 0 // À compléter si fonctionnalité de devis active
    ];
    
    // Traitement des formulaires
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_preferences'])) {
            // Mise à jour des préférences de communication
            $newsletter = isset($_POST['newsletter']) ? 1 : 0;
            $promo_email = isset($_POST['promo_email']) ? 1 : 0;
            $order_updates = isset($_POST['order_updates']) ? 1 : 0;
            $sms_alerts = isset($_POST['sms_alerts']) ? 1 : 0;
            
            $utilisateur->updatePreferences($_SESSION['id'], $newsletter, $promo_email, $order_updates, $sms_alerts);
            $success_message = "Vos préférences ont été mises à jour avec succès.";
        }
        
        if (isset($_POST['update_infos'])) {
            // Mise à jour des informations personnelles
            $nom = htmlspecialchars($_POST['nom']);
            $prenom = htmlspecialchars($_POST['prenom']);
            $email = htmlspecialchars($_POST['email']);
            $telephone = htmlspecialchars($_POST['telephone']);
            
            $utilisateur->updateInfos($_SESSION['id'], $nom, $prenom, $email, $telephone);
            $success_message = "Vos informations ont été mises à jour avec succès.";
            
            // Mettre à jour les données en session
            $_SESSION['login'] = $email;
        }
        
        if (isset($_POST['update_picture']) && isset($_FILES['profile_picture'])) {
            // Traitement de l'upload de la photo de profil
            $upload = new upload($db);
            $result = $upload->uploadProfilePicture($_FILES['profile_picture'], $_SESSION['id']);
            
            if ($result['success']) {
                $success_message = "Votre photo de profil a été mise à jour avec succès.";
            } else {
                $error_message = $result['message'];
            }
        }
        
        if (isset($_POST['delete_picture'])) {
            // Traitement de la suppression de la photo de profil
            $utilisateur = new utilisateurs($db);
            if ($utilisateur->deleteProfilePicture($_SESSION['id'])) {
                $success_message = "Votre photo de profil a été supprimée avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la suppression de votre photo de profil.";
            }
        }
    }
    
    echo $twig->render('client/mon-compte.twig', [
        'page' => 'mon_compte',
        'client_page' => 'dashboard',
        'client' => $client,
        'dernieres_commandes' => array_slice($dernieres_commandes, 0, 3), // Limiter à 3 commandes
        'adresse_principale' => $adresse_principale,
        'stats' => $stats,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}

function commandes($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Récupération des commandes
    $commandes = new Commande($db);
    $liste_commandes = $commandes->getByUtilisateur($_SESSION['id']);
    
    // Filtrage par recherche si demandé
    $recherche = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : '';
    if (!empty($recherche)) {
        $liste_commandes = array_filter($liste_commandes, function($commande) use ($recherche) {
            return (stripos($commande['reference'], $recherche) !== false || 
                    stripos($commande['statut'], $recherche) !== false ||
                    stripos($commande['date_commande'], $recherche) !== false);
        });
    }
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['annuler_commande']) && isset($_POST['id_commande'])) {
            $id_commande = intval($_POST['id_commande']);
            $motif = isset($_POST['motif_annulation']) ? htmlspecialchars($_POST['motif_annulation']) : '';
            $commentaire = isset($_POST['commentaire_annulation']) ? htmlspecialchars($_POST['commentaire_annulation']) : '';
            
            // Vérifier que la commande appartient à l'utilisateur
            $commande_details = $commandes->getById($id_commande);
            if ($commande_details && $commande_details['id_utilisateur'] == $_SESSION['id']) {
                if ($commandes->updateStatut($id_commande, 'annule')) {
                    // Enregistrer le motif et commentaire
                    $commandes->addMotifAnnulation($id_commande, $motif, $commentaire);
                    $success_message = "La commande a été annulée avec succès.";
                    
                    // Recharger la liste des commandes
                    $liste_commandes = $commandes->getByUtilisateur($_SESSION['id']);
                } else {
                    $error_message = "Une erreur est survenue lors de l'annulation de la commande.";
                }
            } else {
                $error_message = "Vous n'êtes pas autorisé à annuler cette commande.";
            }
        }
    }
    
    // Récupération des données de l'utilisateur pour l'affichage de la photo de profil
    $utilisateur = new utilisateurs($db);
    $client = $utilisateur->getById($_SESSION['id']);
    
    echo $twig->render('client/commandes.twig', [
        'page' => 'commandes',
        'client_page' => 'commandes',
        'commandes' => $liste_commandes,
        'client' => $client,
        'recherche' => $recherche,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}

function commande($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Vérification que l'ID de commande est fourni
    if (!isset($_GET['id'])) {
        header('Location: index.php?page=commandes');
        exit;
    }
    
    $id_commande = intval($_GET['id']);
    
    // Récupération des détails de la commande
    $commandes = new Commande($db);
    $commande = $commandes->getById($id_commande);
    
    // Vérifier que la commande existe et appartient à l'utilisateur
    if (!$commande || $commande['id_utilisateur'] != $_SESSION['id']) {
        header('Location: index.php?page=commandes');
        exit;
    }
    
    // Récupération des données de l'utilisateur
    $utilisateur = new utilisateurs($db);
    $client = $utilisateur->getById($_SESSION['id']);
    
    // Récupération de l'historique de la commande
    $historique_commande = $commandes->getHistorique($id_commande);
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['annuler_commande']) && isset($_POST['id_commande'])) {
            $motif = isset($_POST['motif_annulation']) ? htmlspecialchars($_POST['motif_annulation']) : '';
            $commentaire = isset($_POST['commentaire_annulation']) ? htmlspecialchars($_POST['commentaire_annulation']) : '';
            
            if ($commandes->updateStatut($id_commande, 'annule')) {
                $commandes->addMotifAnnulation($id_commande, $motif, $commentaire);
                $success_message = "La commande a été annulée avec succès.";
                
                // Recharger les détails de la commande
                $commande = $commandes->getById($id_commande);
                $historique_commande = $commandes->getHistorique($id_commande);
            } else {
                $error_message = "Une erreur est survenue lors de l'annulation de la commande.";
            }
        }
        
        if (isset($_POST['soumettre_avis'])) {
            $note_globale = isset($_POST['note_globale']) ? intval($_POST['note_globale']) : 0;
            $commentaire_avis = isset($_POST['commentaire_avis']) ? htmlspecialchars($_POST['commentaire_avis']) : '';
            
            // Enregistrer l'avis global sur la commande
            $commandes->addAvis($id_commande, $note_globale, $commentaire_avis);
            
            // Traiter les avis sur les produits
            if (isset($_POST['note_produit']) && is_array($_POST['note_produit'])) {
                $produits = new produit($db);
                foreach ($_POST['note_produit'] as $id_produit => $note) {
                    $commentaire_produit = isset($_POST['commentaire_produit'][$id_produit]) ? 
                                          htmlspecialchars($_POST['commentaire_produit'][$id_produit]) : '';
                    
                    $produits->addAvis($id_produit, $_SESSION['id'], intval($note), $commentaire_produit);
                }
            }
            
            $success_message = "Votre avis a été enregistré avec succès. Merci pour votre feedback!";
        }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'facture') {
        // Appel à la fonction de génération et téléchargement de facture 'ptit prob'
        genererEtTelechargerFacture($db, $commande);
        exit;
    }
    
    echo $twig->render('client/commande-detail.twig', [
        'page' => 'commandes',
        'client_page' => 'commandes',
        'commande' => $commande,
        'client' => $client,
        'historique_commande' => $historique_commande,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}

function adresses($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Récupération des adresses
    $adresses = new adresses($db);
    $liste_adresses = $adresses->getByUtilisateur($_SESSION['id']);
    
    // Traitement de la requête Ajax pour récupérer une adresse spécifique
    if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id']) && isset($_GET['ajax'])) {
        $id_adresse = (int) $_GET['id'];
        $adresse_data = $adresses->getById($id_adresse);
        
        // Vérifier que l'adresse appartient à l'utilisateur
        if ($adresse_data && $adresse_data['id_utilisateur'] == $_SESSION['id']) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'adresse' => $adresse_data
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Adresse non trouvée ou accès non autorisé'
            ]);
        }
        exit;
    }
    
    // Récupération des données de l'utilisateur
    $utilisateur = new utilisateurs($db);
    $client = $utilisateur->getById($_SESSION['id']);
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['ajouter_adresse'])) {
            // Récupération des données du formulaire
            $nom_adresse = htmlspecialchars($_POST['nom_adresse']);
            $type_adresse = htmlspecialchars($_POST['type_adresse']);
            $nom_complet = htmlspecialchars($_POST['nom_complet']);
            $rue = htmlspecialchars($_POST['rue']);
            $complement = isset($_POST['complement']) ? htmlspecialchars($_POST['complement']) : null;
            $code_postal = htmlspecialchars($_POST['code_postal']);
            $ville = htmlspecialchars($_POST['ville']);
            $pays = htmlspecialchars($_POST['pays']);
            $telephone = htmlspecialchars($_POST['telephone']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;
            
            // Ajout de l'adresse
            $adresses->add($_SESSION['id'], $nom_adresse, $type_adresse, $nom_complet, $rue, $complement, 
                           $code_postal, $ville, $pays, $telephone, $is_default);
            
            $success_message = "L'adresse a été ajoutée avec succès.";
            
            // Recharger la liste des adresses
            $liste_adresses = $adresses->getByUtilisateur($_SESSION['id']);
        }
        
        if (isset($_POST['modifier_adresse']) && isset($_POST['id_adresse'])) {
            $id_adresse = intval($_POST['id_adresse']);
            
            // Vérifier que l'adresse appartient à l'utilisateur
            $adresse_details = $adresses->getById($id_adresse);
            if ($adresse_details && $adresse_details['id_utilisateur'] == $_SESSION['id']) {
                // Récupération des données du formulaire
                $nom_adresse = htmlspecialchars($_POST['nom_adresse']);
                $type_adresse = htmlspecialchars($_POST['type_adresse']);
                $nom_complet = htmlspecialchars($_POST['nom_complet']);
                $rue = htmlspecialchars($_POST['rue']);
                $complement = isset($_POST['complement']) ? htmlspecialchars($_POST['complement']) : null;
                $code_postal = htmlspecialchars($_POST['code_postal']);
                $ville = htmlspecialchars($_POST['ville']);
                $pays = htmlspecialchars($_POST['pays']);
                $telephone = htmlspecialchars($_POST['telephone']);
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                // Mise à jour de l'adresse
                $adresses->update($id_adresse, $nom_adresse, $type_adresse, $nom_complet, $rue, $complement,
                               $code_postal, $ville, $pays, $telephone, $is_default);
                
                $success_message = "L'adresse a été modifiée avec succès.";
                
                // Recharger la liste des adresses
                $liste_adresses = $adresses->getByUtilisateur($_SESSION['id']);
            } else {
                $error_message = "Vous n'êtes pas autorisé à modifier cette adresse.";
            }
        }
        
        if (isset($_POST['supprimer_adresse']) && isset($_POST['id_adresse'])) {
            $id_adresse = intval($_POST['id_adresse']);
            
            // Vérifier que l'adresse appartient à l'utilisateur
            $adresse_details = $adresses->getById($id_adresse);
            if ($adresse_details && $adresse_details['id_utilisateur'] == $_SESSION['id']) {
                $adresses->delete($id_adresse);
                $success_message = "L'adresse a été supprimée avec succès.";
                
                // Recharger la liste des adresses
                $liste_adresses = $adresses->getByUtilisateur($_SESSION['id']);
            } else {
                $error_message = "Vous n'êtes pas autorisé à supprimer cette adresse.";
            }
        }
        
        if (isset($_POST['definir_defaut']) && isset($_POST['id_adresse'])) {
            $id_adresse = intval($_POST['id_adresse']);
            
            // Vérifier que l'adresse appartient à l'utilisateur
            $adresse_details = $adresses->getById($id_adresse);
            if ($adresse_details && $adresse_details['id_utilisateur'] == $_SESSION['id']) {
                $adresses->setDefault($id_adresse, $_SESSION['id']);
                $success_message = "L'adresse a été définie comme adresse par défaut.";
                
                // Recharger la liste des adresses
                $liste_adresses = $adresses->getByUtilisateur($_SESSION['id']);
            } else {
                $error_message = "Vous n'êtes pas autorisé à modifier cette adresse.";
            }
        }
    }
    
    echo $twig->render('client/adresses.twig', [
        'page' => 'adresses',
        'client_page' => 'adresses',
        'adresses' => $liste_adresses,
        'client' => $client,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}

function messages($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Récupération des conversations
    $messages = new messages($db);
    $conversations = $messages->getConversationsByUtilisateur($_SESSION['id']);
    
    // Récupération des données de l'utilisateur
    $utilisateur = new utilisateurs($db);
    $client = $utilisateur->getById($_SESSION['id']);
    
    // Récupération des commandes pour le formulaire de nouveau message
    $commandes = new Commande($db);
    $dernieres_commandes = $commandes->getByUtilisateur($_SESSION['id']);
    
    // Préparation des variables pour le formulaire précomplété
    $sujet_prefilled = '';
    $type_prefilled = 'general';
    $commande_prefilled = '';
    
    // Si une conversation est sélectionnée, récupérer ses messages
    $conversation_active = null;
    if (isset($_GET['id'])) {
        $id_conversation = intval($_GET['id']);
        $conversation_active = $messages->getConversationById($id_conversation);
        
        // Vérifier que la conversation appartient à l'utilisateur
        if (!$conversation_active || $conversation_active['id_utilisateur'] != $_SESSION['id']) {
            header('Location: index.php?page=messages');
            exit;
        }
        
        // Marquer les messages comme lus
        $messages->markAsRead($id_conversation, $_SESSION['id']);
        
        // Recharger les conversations pour mettre à jour le statut de lecture
        $conversations = $messages->getConversationsByUtilisateur($_SESSION['id']);
    }
    
    // Préremplir le formulaire de nouveau message si on vient d'une commande ou d'une page spécifique
    if (isset($_GET['sujet'])) {
        $sujet_prefilled = htmlspecialchars($_GET['sujet']);
        
        // Détecter si on vient d'une commande
        if (strpos($sujet_prefilled, 'commande-') === 0) {
            $type_prefilled = 'commande';
            $commande_prefilled = substr($sujet_prefilled, 9); // Enlever "commande-"
            $sujet_prefilled = "Question concernant la commande $commande_prefilled";
        }
    }
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['nouvelle_conversation'])) {
            // Création d'une nouvelle conversation
            $sujet = htmlspecialchars($_POST['sujet']);
            $type = htmlspecialchars($_POST['type']);
            $message = htmlspecialchars($_POST['message']);
            $reference_commande = isset($_POST['reference_commande']) ? htmlspecialchars($_POST['reference_commande']) : null;
            
            // Création de la conversation
            $id_conversation = $messages->createConversation($_SESSION['id'], $sujet, $type, $reference_commande);
            
            // Ajout du premier message
            $messages->addMessage($id_conversation, $_SESSION['id'], $message, true);
            
            // Traitement de la pièce jointe si présente
            if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
                $upload = new upload($db);
                $result = $upload->uploadMessageAttachment($_FILES['piece_jointe'], $id_conversation);
                
                if ($result['success']) {
                    $messages->addAttachment($id_conversation, $messages->getLastMessageId($id_conversation), $result['filename'], $result['filepath']);
                }
            }
            
            $success_message = "Votre message a été envoyé avec succès.";
            
            header('Location: index.php?page=messages&id=' . $id_conversation);
            exit;
        }
        
        if (isset($_POST['envoyer_message']) && isset($_POST['id_conversation'])) {
            $id_conversation = intval($_POST['id_conversation']);
            $message_text = htmlspecialchars($_POST['message']);
            
            $conv_details = $messages->getConversationById($id_conversation);
            if ($conv_details && $conv_details['id_utilisateur'] == $_SESSION['id']) {
                $messages->addMessage($id_conversation, $_SESSION['id'], $message_text, true);
                
                if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
                    $upload = new upload($db);
                    $result = $upload->uploadMessageAttachment($_FILES['piece_jointe'], $id_conversation);
                    
                    if ($result['success']) {
                        $messages->addAttachment($id_conversation, $messages->getLastMessageId($id_conversation), $result['filename'], $result['filepath']);
                    }
                }
                
                // Mettre à jour le statut de la conversation si elle était résolue
                if ($conv_details['statut'] === 'resolu') {
                    $messages->updateConversationStatus($id_conversation, 'ouvert');
                }
                
                $success_message = "Votre message a été envoyé avec succès.";
                
                // Recharger les détails de la conversation
                $conversation_active = $messages->getConversationById($id_conversation);
            } else {
                $error_message = "Vous n'êtes pas autorisé à envoyer un message dans cette conversation.";
            }
        }
        
        if (isset($_POST['marquer_resolu']) && isset($_POST['id_conversation'])) {
            $id_conversation = intval($_POST['id_conversation']);
            
            $conv_details = $messages->getConversationById($id_conversation);
            if ($conv_details && $conv_details['id_utilisateur'] == $_SESSION['id']) {
                $messages->updateConversationStatus($id_conversation, 'resolu');
                $success_message = "La conversation a été marquée comme résolue.";
                
                $conversation_active = $messages->getConversationById($id_conversation);
            } else {
                $error_message = "Vous n'êtes pas autorisé à modifier cette conversation.";
            }
        }
        
        if (isset($_POST['reouvrir']) && isset($_POST['id_conversation'])) {
            $id_conversation = intval($_POST['id_conversation']);
            
            // Vérifier que la conversation appartient à l'utilisateur
            $conv_details = $messages->getConversationById($id_conversation);
            if ($conv_details && $conv_details['id_utilisateur'] == $_SESSION['id']) {
                $messages->updateConversationStatus($id_conversation, 'ouvert');
                $success_message = "La conversation a été rouverte.";
                
                $conversation_active = $messages->getConversationById($id_conversation);
            } else {
                $error_message = "Vous n'êtes pas autorisé à modifier cette conversation.";
            }
        }
    }
    
    echo $twig->render('client/messages.twig', [
        'page' => 'messages',
        'client_page' => 'messages',
        'conversations' => $conversations,
        'conversation_active' => $conversation_active,
        'client' => $client,
        'dernieres_commandes' => $dernieres_commandes,
        'sujet_prefilled' => $sujet_prefilled,
        'type_prefilled' => $type_prefilled,
        'commande_prefilled' => $commande_prefilled,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}

/**
 * Génère et télécharge la facture d'une commande
 * 
 * @param PDO 
 * @param array 
 * @return void
 */
function genererEtTelechargerFacture($db, $commande) {
    $factureModel = new Facture($db);
    
    $facture = $factureModel->getByCommande($commande['id']);
    
    // Si la facture n'existe pas, la générer
    if (!$facture) {
        $result = $factureModel->genererFacture($commande['id']);
        
        if (!$result['success']) {
            // En cas d'erreur, rediriger vers la page de la commande avec un message d'erreur
            header('Location: index.php?page=commande&id=' . $commande['id'] . '&error=facture');
            exit;
        }
        
        $facture = $factureModel->getByCommande($commande['id']);
    }
    
    // Chemin vers le fichier HTML de la facture
    $filepath = __DIR__ . '/../../public/factures/facture_' . $facture['numero_facture'] . '.html';
    
    // Vérifier que le fichier existe
    if (!file_exists($filepath)) {
        // Si le fichier n'existe pas, le regénérer
        $factureModel->genererPDF($facture['id']);
        
        // Vérifier à nouveau l'existence du fichier
        if (!file_exists($filepath)) {
            header('Location: index.php?page=commande&id=' . $commande['id'] . '&error=facture_file');
            exit;
        }
    }
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="Facture_' . $facture['numero_facture'] . '.html"');
    header('Content-Length: ' . filesize($filepath));
    
    // Lire et envoyer le fichier
    readfile($filepath);
    exit;
}

function securite($twig, $db) {
    // Vérification que l'utilisateur est connecté
    if (!isAuthenticated()) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Rafraîchir la session
    refreshSession();
    
    // Récupération des données de l'utilisateur
    $utilisateur = new utilisateurs($db);
    $user = $utilisateur->getById($_SESSION['id']);
    
    // Récupération de l'historique de connexion
    $securite = new securite($db);
    $login_history = $securite->getLoginHistory($_SESSION['id']);
    
    // Variables pour la 2FA
    $qr_code_url = null;
    $backup_code = null;
    
    // Si l'utilisateur n'a pas encore configuré la 2FA, générer les données de configuration
    if (!$user['two_factor_enabled'] && !isset($_POST['disable_two_factor'])) {
        $auth_data = $securite->generate2FASetupData($_SESSION['id']);
        $qr_code_url = $auth_data['qr_code_url'];
        $backup_code = $auth_data['backup_code'];
    }
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['modifier_mot_de_passe'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Vérifier que le mot de passe actuel est correct
            if (!password_verify($current_password, $user['password'])) {
                $error_message = "Le mot de passe actuel est incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Les nouveaux mots de passe ne correspondent pas.";
            } elseif (strlen($new_password) < 8) {
                $error_message = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            } else {
                // Hachage du nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Mise à jour du mot de passe
                $utilisateur->updatePassword($_SESSION['id'], $hashed_password);
                
                // Enregistrer l'action dans l'historique
                $securite->logPasswordChange($_SESSION['id']);
                
                $success_message = "Votre mot de passe a été mis à jour avec succès.";
            }
        }
        
        if (isset($_POST['update_security_options'])) {
            // Mise à jour des options de sécurité
            $notif_new_connexion = isset($_POST['notif_new_connexion']) ? 1 : 0;
            $notif_password_change = isset($_POST['notif_password_change']) ? 1 : 0;
            $notif_failed_attempts = isset($_POST['notif_failed_attempts']) ? 1 : 0;
            $remember_devices = isset($_POST['remember_devices']) ? 1 : 0;
            $extended_session = isset($_POST['extended_session']) ? 1 : 0;
            $confirm_order_by_email = isset($_POST['confirm_order_by_email']) ? 1 : 0;
            
            $utilisateur->updateSecurityOptions($_SESSION['id'], [
                'notif_new_connexion' => $notif_new_connexion,
                'notif_password_change' => $notif_password_change,
                'notif_failed_attempts' => $notif_failed_attempts,
                'remember_devices' => $remember_devices,
                'extended_session' => $extended_session,
                'confirm_order_by_email' => $confirm_order_by_email
            ]);
            
            $success_message = "Vos préférences de sécurité ont été mises à jour avec succès.";
            
            // Mettre à jour les données de l'utilisateur
            $user = $utilisateur->getById($_SESSION['id']);
        }
        
        if (isset($_POST['enable_two_factor'])) {
            // Vérification du code à 6 chiffres
            $verification_code = $_POST['verification_code'];
            
            // Vérifier que le code est valide
            $result = $securite->validate2FACode($_SESSION['id'], $verification_code);
            
            if ($result) {
                $utilisateur->enable2FA($_SESSION['id']);
                $success_message = "L'authentification à deux facteurs a été activée avec succès.";
                
                // Mettre à jour les données de l'utilisateur
                $user = $utilisateur->getById($_SESSION['id']);
            } else {
                $error_message = "Le code entré est invalide. Veuillez réessayer.";
                
                $auth_data = $securite->generate2FASetupData($_SESSION['id']);
                $qr_code_url = $auth_data['qr_code_url'];
                $backup_code = $auth_data['backup_code'];
            }
        }
        
        if (isset($_POST['disable_two_factor'])) {
            // Vérification du code à 6 chiffres
            $verification_code = $_POST['verification_code'];
            
            // Vérifier que le code est valide
            $result = $securite->validate2FACode($_SESSION['id'], $verification_code);
            
            if ($result) {
                $utilisateur->disable2FA($_SESSION['id']);
                $success_message = "L'authentification à deux facteurs a été désactivée avec succès.";
                
                // Mettre à jour les données de l'utilisateur
                $user = $utilisateur->getById($_SESSION['id']);
            } else {
                $error_message = "Le code entré est invalide. Veuillez réessayer.";
            }
        }
        
        if (isset($_POST['logout_all_devices'])) {
            // Déconnexion de tous les appareils
            $securite->logoutAllDevices($_SESSION['id']);
            
            // Générer un nouveau token de session pour l'utilisateur actuel
            $new_session_token = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $new_session_token;
            $utilisateur->updateSessionToken($_SESSION['id'], $new_session_token);
            
            $success_message = "Vous avez été déconnecté de tous les autres appareils.";
        }
    }
    
    echo $twig->render('client/securite.twig', [
        'page' => 'securite',
        'client_page' => 'securite',
        'user' => $user,
        'client' => $user, // Ajout de client pour la cohérence avec base-client.twig
        'login_history' => $login_history,
        'qr_code_url' => $qr_code_url,
        'backup_code' => $backup_code,
        'success_message' => isset($success_message) ? $success_message : null,
        'error_message' => isset($error_message) ? $error_message : null
    ]);
}
