<?php

function panierControleur($twig, $db) {
    // Instanciation des classes
    $panierModel = new Panier($db);
    $produitModel = new Produit($db);
    
    // Initialiser le panier si nécessaire
    $panierModel->initialiser();
    
    // Traitement des actions
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $message = null;
    $success = false;
    
    switch ($action) {
        case 'ajouter':
            // Récupération des paramètres
            $id_produit = isset($_POST['id_produit']) ? (int)$_POST['id_produit'] : 0;
            $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 1;
            
            if ($id_produit > 0 && $quantite > 0) {
                $resultat = $panierModel->ajouter($id_produit, $quantite);
                
                if ($resultat['success']) {
                    $message = $resultat['message'];
                    $success = true;
                    
                    // Redirection vers la fiche produit avec message de succès
                    if (!isset($_GET['ajax'])) {
                        header('Location: index.php?page=produit&id=' . $id_produit . '&ajout=success');
                        exit;
                    }
                } else {
                    $message = $resultat['message'];
                    
                    // Redirection vers la fiche produit avec message d'erreur
                    if (!isset($_GET['ajax'])) {
                        header('Location: index.php?page=produit&id=' . $id_produit . '&ajout=error&message=' . urlencode($message));
                        exit;
                    }
                }
            }
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            break;
            
        case 'mettre-a-jour':
            $id_produit = isset($_POST['id_produit']) ? (int)$_POST['id_produit'] : 0;
            $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;
            
            if ($id_produit > 0) {
                $resultat = $panierModel->mettreAJour($id_produit, $quantite);
                $message = $resultat['message'];
                $success = $resultat['success'];
            }
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            
            // Redirection vers la page panier
            header('Location: index.php?page=panier');
            exit;
            break;
            
        case 'supprimer':
            $id_produit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id_produit > 0) {
                $resultat = $panierModel->supprimer($id_produit);
                $message = $resultat['message'];
                $success = $resultat['success'];
            }
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            
            // Redirection vers la page panier
            header('Location: index.php?page=panier');
            exit;
            break;
        
        case 'pays-livraison':
            // Récupérer le pays
            $pays = isset($_POST['pays']) ? $_POST['pays'] : 'France';
            
            // Mettre à jour le pays de livraison
            $panierModel->setPaysLivraison($pays);
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Pays de livraison mis à jour',
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            
            // Redirection vers la page panier
            header('Location: index.php?page=panier');
            exit;
            break;
            
        case 'methode-livraison':
            // Récupérer la méthode de livraison
            $methode = isset($_POST['methode']) ? $_POST['methode'] : 'domicile';
            $option_id = isset($_POST['option_id']) ? $_POST['option_id'] : null;
            
            // Mettre à jour la méthode de livraison
            $panierModel->setMethodeLivraison($methode, $option_id);
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Méthode de livraison mise à jour',
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            
            // Redirection vers la page checkout si on est en étape livraison
            if (isset($_GET['etape']) && $_GET['etape'] == 'livraison') {
                header('Location: index.php?page=panier&action=commander&etape=paiement');
                exit;
            }
            
            // Sinon redirection vers la page panier
            header('Location: index.php?page=panier');
            exit;
            break;
            
        case 'etape':
            // Gestion des étapes du processus de commande
            $step = isset($_GET['step']) ? $_GET['step'] : 'livraison';
            
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['id'])) {
                header('Location: index.php?page=connexion&redirect=panier&action=etape&step=' . $step);
                exit;
            }
            
            // Vérifier que le panier n'est pas vide
            $panier = $panierModel->getContenu();
            if (empty($panier['produits'])) {
                header('Location: index.php?page=panier');
                exit;
            }
            
            // Vérifier la disponibilité des produits
            $disponibilite = $panierModel->validerDisponibilite();
            if (!$disponibilite['success']) {
                header('Location: index.php?page=panier&erreur=disponibilite');
                exit;
            }
            
            // Initialiser le service de paiement Stripe pour l'étape paiement
            $stripeModel = new Stripe($db);
            
            // Récupérer les adresses enregistrées du client
            $adressesModel = new adresses($db);
            $adresses = $adressesModel->getByUtilisateur($_SESSION['id']);
            $adresse_principale = $adressesModel->getAdressePrincipale($_SESSION['id']);
            if ($adresse_principale === false || is_string($adresse_principale)) {
                $adresse_principale = null;
            }
            
            // Afficher le template avec l'étape correspondante
            echo $twig->render('checkout.twig', [
                'panier' => $panier,
                'utilisateur' => [
                    'nom' => $_SESSION['nom'] ?? '',
                    'prenom' => $_SESSION['prenom'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                    'adresse' => $_SESSION['adresse'] ?? '',
                    'code_postal' => $_SESSION['code_postal'] ?? '',
                    'ville' => $_SESSION['ville'] ?? '',
                    'telephone' => $_SESSION['telephone'] ?? ''
                ],
                'adresses' => $adresses,
                'adresse_principale' => $adresse_principale,
                'etape' => $step,
                'stripe_public_key' => $stripeModel->getPublicKey(),
                'mode_livraison' => $_SESSION['panier']['methode_livraison'] ?? 'domicile'
            ]);
            break;
            
        case 'vider':
            $resultat = $panierModel->vider();
            $message = $resultat['message'];
            $success = $resultat['success'];
            
            // Si c'est une requête AJAX, retourner JSON
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'panier' => $panierModel->getContenu()
                ]);
                exit;
            }
            
            // Redirection vers la page panier
            header('Location: index.php?page=panier');
            exit;
            break;
            
        case 'commander':
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['id'])) {
                // Rediriger vers la page de connexion
                header('Location: index.php?page=connexion&redirect=panier&action=commander');
                exit;
            }
            
            // Vérifier la disponibilité des produits
            $disponibilite = $panierModel->validerDisponibilite();
            
            if (!$disponibilite['success']) {
                $message = "Certains produits ne sont plus disponibles en quantité suffisante";
                $produits_indisponibles = $disponibilite['produits_indisponibles'];
                
                echo $twig->render('panier.twig', [
                    'panier' => $panierModel->getContenu(),
                    'erreur' => $message,
                    'produits_indisponibles' => $produits_indisponibles,
                    'etape' => 'panier'
                ]);
                break;
            }
            
            // Déterminer l'étape actuelle
            $etape = isset($_GET['etape']) ? $_GET['etape'] : 'livraison';
            
            // Initialiser les services de paiement
            $stripeModel = new Stripe($db);
            $paypalModel = new PayPal($db);
            
            // Récupérer les adresses enregistrées du client
            $adressesModel = new adresses($db);
            $adresses = $adressesModel->getByUtilisateur($_SESSION['id']);
            // S'assurer que adresse_principale est soit false, soit un tableau mais pas une chaîne
            $adresse_principale = $adressesModel->getAdressePrincipale($_SESSION['id']);
            // Vérifier si adresse_principale est une chaîne, un tableau ou false
            if ($adresse_principale === false) {
                $adresse_principale = null;
            } elseif (is_string($adresse_principale)) {
                // Si c'est une chaîne, convertir en null pour éviter l'erreur d'accès à un offset sur une chaîne
                $adresse_principale = null;
            }
            
            // Si tout est disponible, afficher le formulaire de commande avec l'étape appropriée
            echo $twig->render('checkout.twig', [
                'panier' => $panierModel->getContenu(),
                'utilisateur' => [
                    'nom' => $_SESSION['nom'] ?? '',
                    'prenom' => $_SESSION['prenom'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                    'adresse' => $_SESSION['adresse'] ?? '',
                    'code_postal' => $_SESSION['code_postal'] ?? '',
                    'ville' => $_SESSION['ville'] ?? '',
                    'telephone' => $_SESSION['telephone'] ?? ''
                ],
                'adresses' => $adresses,
                'adresse_principale' => $adresse_principale,
                'etape' => $etape,
                'stripe_public_key' => $stripeModel->getPublicKey(),
                'paypal_client_id' => $paypalModel->getClientId(),
                'mode_livraison' => $_SESSION['panier']['methode_livraison'] ?? 'domicile'
            ]);
            break;
            
        case 'valider-commande':
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['id'])) {
                header('Location: index.php?page=connexion&redirect=panier&action=commander');
                exit;
            }
            
            // Vérifier la disponibilité des produits
            $disponibilite = $panierModel->validerDisponibilite();
            
            if (!$disponibilite['success']) {
                $message = "Certains produits ne sont plus disponibles en quantité suffisante";
                
                echo $twig->render('panier.twig', [
                    'panier' => $panierModel->getContenu(),
                    'erreur' => $message,
                    'produits_indisponibles' => $disponibilite['produits_indisponibles'],
                    'etape' => 'panier'
                ]);
                break;
            }
            
            // Récupération des données de livraison et paiement
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Vérifier si nous sommes à l'étape de livraison
                if (isset($_POST['valider_livraison'])) {
                    // Validation des données de livraison
                    $erreurs = [];
                    
                    // Validation de l'adresse
                    if (isset($_POST['adresse_selection']) && $_POST['adresse_selection'] === 'nouvelle') {
                        if (empty($_POST['nom'])) $erreurs[] = "Le nom est obligatoire";
                        if (empty($_POST['prenom'])) $erreurs[] = "Le prénom est obligatoire";
                        if (empty($_POST['email'])) $erreurs[] = "L'email est obligatoire";
                        if (empty($_POST['adresse'])) $erreurs[] = "L'adresse est obligatoire";
                        if (empty($_POST['code_postal'])) $erreurs[] = "Le code postal est obligatoire";
                        if (empty($_POST['ville'])) $erreurs[] = "La ville est obligatoire";
                    }
                    
                    // Validation de la méthode de livraison
                    if (!isset($_POST['mode_livraison'])) {
                        $erreurs[] = "Veuillez choisir une méthode de livraison";
                    } else {
                        // Vérifications spécifiques selon le mode de livraison
                        switch ($_POST['mode_livraison']) {
                            case 'relay':
                                if (!isset($_POST['point_relais_id']) || empty($_POST['point_relais_id'])) {
                                    $erreurs[] = "Veuillez sélectionner un point relais Mondial Relay";
                                } else {
                                    // Vérifier si on a des détails supplémentaires sur le point relais
                                    $point_details = isset($_SESSION['point_relais_details'][$_POST['point_relais_id']]) 
                                        ? $_SESSION['point_relais_details'][$_POST['point_relais_id']] 
                                        : null;
                                    
                                    // Si on n'a pas les détails, on les cherche dans les champs cachés du formulaire
                                    if (!$point_details && isset($_POST['relay_name']) && isset($_POST['relay_address'])) {
                                        $_SESSION['point_relais_details'][$_POST['point_relais_id']] = [
                                            'name' => $_POST['relay_name'],
                                            'address' => $_POST['relay_address']
                                        ];
                                    }
                                }
                                break;
                            case 'poste':
                                if (!isset($_POST['bureau_poste_id']) || empty($_POST['bureau_poste_id'])) {
                                    $erreurs[] = "Veuillez sélectionner un bureau de poste";
                                }
                                break;
                            case 'pickup':
                                if (!isset($_POST['pickup_id']) || empty($_POST['pickup_id'])) {
                                    $erreurs[] = "Veuillez sélectionner un point de retrait";
                                }
                                break;
                        }
                    }
                    
                    // Si l'adresse de facturation est différente, valider les champs
                    if (!isset($_POST['adresse_facturation_identique']) && empty($_POST['adresse_facturation_identique'])) {
                        if (empty($_POST['adresse_facturation'])) $erreurs[] = "L'adresse de facturation est obligatoire";
                        if (empty($_POST['code_postal_facturation'])) $erreurs[] = "Le code postal de facturation est obligatoire";
                        if (empty($_POST['ville_facturation'])) $erreurs[] = "La ville de facturation est obligatoire";
                    }
                    
                    // S'il y a des erreurs, afficher à nouveau le formulaire de livraison avec les erreurs
                    if (!empty($erreurs)) {
                        // Récupérer les adresses du client
                        $adressesModel = new adresses($db);
                        $adresses = $adressesModel->getByUtilisateur($_SESSION['id']);
                        $adresse_principale = $adressesModel->getAdressePrincipale($_SESSION['id']);
                        
                        if ($adresse_principale === false || is_string($adresse_principale)) {
                            $adresse_principale = null;
                        }
                        
                        echo $twig->render('checkout.twig', [
                            'panier' => $panierModel->getContenu(),
                            'utilisateur' => [
                                'nom' => $_POST['nom'] ?? $_SESSION['nom'] ?? '',
                                'prenom' => $_POST['prenom'] ?? $_SESSION['prenom'] ?? '',
                                'email' => $_POST['email'] ?? $_SESSION['email'] ?? '',
                                'adresse' => $_POST['adresse'] ?? $_SESSION['adresse'] ?? '',
                                'code_postal' => $_POST['code_postal'] ?? $_SESSION['code_postal'] ?? '',
                                'ville' => $_POST['ville'] ?? $_SESSION['ville'] ?? '',
                                'telephone' => $_POST['telephone'] ?? $_SESSION['telephone'] ?? ''
                            ],
                            'adresses' => $adresses,
                            'adresse_principale' => $adresse_principale,
                            'erreur' => implode("<br>", $erreurs),
                            'etape' => 'livraison',
                            'mode_livraison' => $_POST['mode_livraison'] ?? 'domicile'
                        ]);
                        break;
                    }
                    
                    // Mettre à jour le mode de livraison dans le panier (on récupère l'option_id selon le mode)
                    $option_id = null;
                    switch ($_POST['mode_livraison']) {
                        case 'relay':
                            $option_id = $_POST['point_relais_id'] ?? null;
                            break;
                        case 'poste':
                            $option_id = $_POST['bureau_poste_id'] ?? null;
                            break;
                        case 'click-collect':
                            $option_id = $_POST['magasin_id'] ?? null;
                            break;
                    }
                    
                    $panierModel->setMethodeLivraison($_POST['mode_livraison'], $option_id);
                    
                    // Stocker les informations de livraison en session pour les utiliser plus tard
                    $_SESSION['livraison_info'] = [
                        'adresse_selection' => $_POST['adresse_selection'] ?? 'nouvelle',
                        'nom' => $_POST['nom'] ?? '',
                        'prenom' => $_POST['prenom'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'telephone' => $_POST['telephone'] ?? '',
                        'adresse' => $_POST['adresse'] ?? '',
                        'code_postal' => $_POST['code_postal'] ?? '',
                        'ville' => $_POST['ville'] ?? '',
                        'sauvegarder_adresse' => isset($_POST['sauvegarder_adresse']),
                        'adresse_facturation_identique' => isset($_POST['adresse_facturation_identique']),
                        'adresse_facturation' => $_POST['adresse_facturation'] ?? '',
                        'code_postal_facturation' => $_POST['code_postal_facturation'] ?? '',
                        'ville_facturation' => $_POST['ville_facturation'] ?? '',
                        'sauvegarder_adresse_facturation' => isset($_POST['sauvegarder_adresse_facturation']),
                        'mode_livraison' => $_POST['mode_livraison'],
                        'option_id' => $option_id,
                        'instructions_livraison' => $_POST['instructions_livraison'] ?? ''
                    ];
                    
                    // Rediriger vers l'étape de paiement
                    header('Location: index.php?page=panier&action=commander&etape=paiement');
                    exit;
                }
                
                // Vérification du consentement RGPD
                if (!isset($_POST['rgpd_consent'])) {
                    echo $twig->render('checkout.twig', [
                        'panier' => $panierModel->getContenu(),
                        'utilisateur' => [
                            'nom' => $_POST['nom'] ?? '',
                            'prenom' => $_POST['prenom'] ?? '',
                            'email' => $_POST['email'] ?? '',
                            'adresse' => $_POST['adresse'] ?? '',
                            'code_postal' => $_POST['code_postal'] ?? '',
                            'ville' => $_POST['ville'] ?? '',
                            'telephone' => $_POST['telephone'] ?? ''
                        ],
                        'erreur' => "Vous devez accepter la politique de confidentialité pour continuer.",
                        'etape' => 'livraison'
                    ]);
                    break;
                }
                
                // Initialiser le modèle d'adresses
                $adressesModel = new adresses($db);
                
                // Traitement de l'adresse de livraison
                if (isset($_POST['adresse_selection']) && $_POST['adresse_selection'] !== 'nouvelle') {
                    // Utiliser une adresse enregistrée
                    $id_adresse = (int) $_POST['adresse_selection'];
                    $adresse_data = $adressesModel->getById($id_adresse);
                    
                    if ($adresse_data && $adresse_data['id_utilisateur'] == $_SESSION['id']) {
                        // Utiliser cette adresse pour la livraison
                        $adresse_livraison = $adresse_data['rue'] . "\n" . $adresse_data['code_postal'] . " " . $adresse_data['ville'];
                    } else {
                        $adresse_livraison = $_POST['adresse'] . "\n" . $_POST['code_postal'] . " " . $_POST['ville'];
                    }
                } else {
                    // Nouvelle adresse
                    $adresse_livraison = $_POST['adresse'] . "\n" . $_POST['code_postal'] . " " . $_POST['ville'];
                    
                    // Sauvegarder l'adresse si demandé
                    if (isset($_POST['sauvegarder_adresse']) && $_POST['sauvegarder_adresse'] == '1') {
                        $nom_adresse = "Adresse de commande"; // Nom par défaut
                        $nom_complet = $_POST['nom'] . ' ' . $_POST['prenom'];
                        $is_default = $adressesModel->getByUtilisateur($_SESSION['id']) ? 0 : 1; // Première adresse = défaut
                        
                        $adressesModel->add(
                            $_SESSION['id'],
                            $nom_adresse,
                            'livraison',
                            $nom_complet,
                            $_POST['adresse'],
                            null, // Complément d'adresse
                            $_POST['code_postal'],
                            $_POST['ville'],
                            'France', // Pays par défaut
                            $_POST['telephone'],
                            $is_default
                        );
                    }
                }
                
                // Traitement de l'adresse de facturation
                if (isset($_POST['adresse_facturation_identique']) && $_POST['adresse_facturation_identique']) {
                    // Utiliser la même adresse pour la facturation
                    $adresse_facturation = $adresse_livraison;
                } elseif (isset($_POST['adresse_facturation_selection']) && $_POST['adresse_facturation_selection'] !== 'nouvelle') {
                    // Utiliser une adresse enregistrée pour la facturation
                    $id_adresse_facturation = (int) $_POST['adresse_facturation_selection'];
                    $adresse_fact_data = $adressesModel->getById($id_adresse_facturation);
                    
                    if ($adresse_fact_data && $adresse_fact_data['id_utilisateur'] == $_SESSION['id']) {
                        $adresse_facturation = $adresse_fact_data['rue'] . "\n" . $adresse_fact_data['code_postal'] . " " . $adresse_fact_data['ville'];
                    } else {
                        $adresse_facturation = $_POST['adresse_facturation'] . "\n" . $_POST['code_postal_facturation'] . " " . $_POST['ville_facturation'];
                    }
                } else {
                    // Nouvelle adresse de facturation
                    $adresse_facturation = $_POST['adresse_facturation'] . "\n" . $_POST['code_postal_facturation'] . " " . $_POST['ville_facturation'];
                    
                    // Sauvegarder l'adresse de facturation si demandé
                    if (isset($_POST['sauvegarder_adresse_facturation']) && $_POST['sauvegarder_adresse_facturation'] == '1') {
                        $nom_adresse = "Adresse de facturation";
                        $nom_complet = $_POST['nom'] . ' ' . $_POST['prenom'];
                        
                        $adressesModel->add(
                            $_SESSION['id'],
                            $nom_adresse,
                            'facturation',
                            $nom_complet,
                            $_POST['adresse_facturation'],
                            null, // Complément d'adresse
                            $_POST['code_postal_facturation'],
                            $_POST['ville_facturation'],
                            'France', // Pays par défaut
                            $_POST['telephone'],
                            0 // Pas par défaut
                        );
                    }
                }
                
                $methode_paiement = $_POST['methode_paiement'];
                
                // Récupérer les informations de livraison supplémentaires
                $mode_livraison = isset($_POST['mode_livraison']) ? $_POST['mode_livraison'] : 'domicile';
                $point_relais_id = isset($_POST['point_relais_id']) ? $_POST['point_relais_id'] : null;
                $bureau_poste_id = isset($_POST['bureau_poste_id']) ? $_POST['bureau_poste_id'] : null;
                $magasin_id = isset($_POST['magasin_id']) ? $_POST['magasin_id'] : null;
                $instructions_livraison = isset($_POST['instructions_livraison']) ? $_POST['instructions_livraison'] : null;
                
                // Mettre à jour la méthode de livraison dans le panier
                $panierModel->setMethodeLivraison($mode_livraison, 
                    $mode_livraison === 'relay' ? $point_relais_id : 
                    ($mode_livraison === 'poste' ? $bureau_poste_id : 
                    ($mode_livraison === 'click-collect' ? $magasin_id : null))
                );
                
                // Création de la commande
                $commandeModel = new Commande($db);
                $panier = $panierModel->getContenu();
                
                // Ajouter les instructions de livraison aux notes si disponibles
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                if (!empty($instructions_livraison)) {
                    $notes = "Instructions livraison: " . $instructions_livraison . "\n\n" . $notes;
                }
                
                $resultat = $commandeModel->creer(
                    $_SESSION['id'],
                    $panier,
                    $adresse_livraison,
                    $adresse_facturation,
                    $methode_paiement,
                    $notes
                );
                
                if ($resultat['success']) {
                    // Mise à jour des stocks
                    $panierModel->mettreAJourStock();
                    // Vider le panier
                    $panierModel->vider();
                    
                    // Redirection vers la page de confirmation
                    header('Location: index.php?page=confirmation-commande&reference=' . $resultat['reference']);
                    exit;
                } else {
                    $message = "Erreur lors de la création de la commande : " . $resultat['message'];
                    
                    echo $twig->render('checkout.twig', [
                        'panier' => $panierModel->getContenu(),
                        'utilisateur' => [
                            'nom' => $_POST['nom'],
                            'prenom' => $_POST['prenom'],
                            'email' => $_POST['email'],
                            'adresse' => $_POST['adresse'],
                            'code_postal' => $_POST['code_postal'],
                            'ville' => $_POST['ville'],
                            'telephone' => $_POST['telephone']
                        ],
                        'erreur' => $message,
                        'etape' => 'livraison'
                    ]);
                    break;
                }
            }
            break;
            
        default:
            // Affichage du panier
            break;
    }
    
    // Affichage du panier par défaut
    if (empty($action) || $action == 'ajouter') {
        echo $twig->render('panier.twig', [
            'panier' => $panierModel->getContenu(),
            'message' => $message,
            'success' => $success,
            'etape' => 'panier'
        ]);
    }
}
