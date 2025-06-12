<?php

// système à revoir car ne marche pas bien surement mieux faire les webhook sur stripe ...

function paiementControleur($twig, $db) {
    $logFile = 'logs/payment_errors.log';
    
    $original_error_reporting = error_reporting();
    $original_display_errors = ini_get('display_errors');
    
    // Récupérer l'action
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Pour les actions qui renvoient du JSON, désactiver l'affichage des erreurs
    if (in_array($action, ['creer-intention', 'confirmer-paiement', 'annuler-paiement', 'config-paypal'])) {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
    
    // Vérifier si l'utilisateur est connecté pour toutes les actions
    if (!isset($_SESSION['id'])) {
        header('Location: index.php?page=connexion&redirect=panier&action=commander');
        exit;
    }
    
    // Initialiser les modèles nécessaires
    $panierModel = new Panier($db);
    $commandeModel = new Commande($db);
    $stripeModel = new Stripe($db);
    $paypalModel = new PayPal($db);
    $factureModel = new Facture($db);
    
    // Initialiser le panier si nécessaire
    $panierModel->initialiser();
    
    // Si on est sur l'étape de paiement, préparer les configurations pour les templates
    if (isset($_GET['etape']) && $_GET['etape'] == 'paiement') {
        // Ajouter les clés publiques pour les templates
        $GLOBALS['stripePubKey'] = $stripeModel->getPublicKey();
        $GLOBALS['paypalClientId'] = $paypalModel->getClientId();
    }
    
    switch ($action) {
        case 'creer-intention':
            // Définir l'en-tête Content-Type pour la réponse JSON
            header('Content-Type: application/json');
            
            try {
                // Log request data for debugging
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Payment intent creation attempt\n", FILE_APPEND);
                file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
                
                // Capture toute la sortie pour éviter les problèmes de buffer
                ob_start();
                
                // Vérifier la disponibilité des produits avant de créer l'intention de paiement
                $disponibilite = $panierModel->validerDisponibilite();
                
                if (!$disponibilite['success']) {
                    file_put_contents($logFile, "Product availability check failed\n", FILE_APPEND);
                    echo json_encode([
                        'success' => false,
                        'message' => "Certains produits ne sont plus disponibles en quantité suffisante"
                    ]);
                    exit;
                }
                
                // Récupérer le contenu du panier
                $panier = $panierModel->getContenu();
                
                // Log panier details
                file_put_contents($logFile, "Panier: " . print_r($panier, true) . "\n", FILE_APPEND);
                
                // Vérifier si le panier a un total valide
                if (!isset($panier['total']) || $panier['total'] <= 0) {
                    file_put_contents($logFile, "Invalid cart total: " . ($panier['total'] ?? 'undefined') . "\n", FILE_APPEND);
                    echo json_encode([
                        'success' => false,
                        'message' => "Le montant du panier est invalide"
                    ]);
                    exit;
                }
                
                // Créer une description pour le paiement
                $produits = [];
                foreach ($panier['produits'] as $produit) {
                    $produits[] = $produit['nom'] . ' x' . $produit['quantite'];
                }
                $description = 'Commande - ' . implode(', ', $produits);
                
                // Calculer le total TTC (incluant TVA et frais de livraison)
                $total_ttc = $panier['total'] + $panier['tva'] + $panier['frais_livraison'];
                
                // Créer les métadonnées pour Stripe avec les informations d'adresse
                $metadata = [
                    'id_utilisateur' => $_SESSION['id'],
                    'email' => $_SESSION['email'],
                    'nombre_produits' => count($panier['produits']),
                    'pays_livraison' => $panier['pays_livraison'],
                    'frais_livraison' => $panier['frais_livraison'],
                    'tva' => $panier['tva']
                ];
                
                // Ajouter les informations d'adresse si disponibles
                if (!empty($panier['adresse_livraison'])) {
                    $metadata['adresse_livraison'] = substr($panier['adresse_livraison'], 0, 500); // Limiter la taille pour Stripe
                }
                
                if (!empty($panier['adresse_facturation'])) {
                    $metadata['adresse_facturation'] = substr($panier['adresse_facturation'], 0, 500);
                }
                
                if (!empty($panier['nom_client'])) {
                    $metadata['nom_client'] = $panier['nom_client'];
                }
                
                if (!empty($panier['prenom_client'])) {
                    $metadata['prenom_client'] = $panier['prenom_client'];
                }
                
                // Récupérer les méthodes de paiement demandées
                $payment_methods = [];
                
                if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
                    $payment_methods = $_POST['payment_methods'];
                } elseif (isset($_POST['payment_methods'])) {

                    if (strpos($_POST['payment_methods'], ',') !== false) {
                        $payment_methods = explode(',', $_POST['payment_methods']);
                    } else {
                        $payment_methods = [$_POST['payment_methods']];
                    }
                }
                
                // Nettoyer et valider les méthodes de paiement
                $valid_methods = [
                    'card',            // Cartes bancaires internationales
                    'link',            // Link
                    'revolut_pay',     // Revolut Pay
                    'paypal'           // PayPal pour compatibilité
                ];
                
                $payment_methods = array_filter($payment_methods, function($method) use ($valid_methods) {
                    return in_array(trim($method), $valid_methods);
                });
                
                // S'assurer qu'au moins une méthode valide existe
                if (empty($payment_methods)) {
                    $payment_methods = ['card']; // Méthode par défaut
                }
                
                // Log payment method information
                file_put_contents($logFile, "Payment methods: " . print_r($payment_methods, true) . "\n", FILE_APPEND);
                
                // Créer l'intention de paiement avec le total TTC
                $resultat = $stripeModel->creerIntentionPaiement($total_ttc, $description, $metadata, $payment_methods);
                
                // Log the result
                file_put_contents($logFile, "Payment intent result: " . print_r($resultat, true) . "\n", FILE_APPEND);
                
                // Nettoyer tout buffer de sortie potentiel
                ob_end_clean();
                
                // Retourner le résultat en JSON
                echo json_encode($resultat);
            } catch (Exception $e) {
                // Nettoyer tout buffer de sortie potentiel
                ob_end_clean();
                
                // Journaliser l'erreur en détail pour le débogage
                $errorMsg = "Erreur Stripe: " . $e->getMessage() . "\n";
                $errorMsg .= "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
                $errorMsg .= "Trace: " . $e->getTraceAsString() . "\n";
                file_put_contents($logFile, $errorMsg, FILE_APPEND);
                error_log($errorMsg);
                
                // Capturer toute erreur et retourner un JSON d'erreur
                echo json_encode([
                    'success' => false,
                    'message' => "Erreur lors de la création de l'intention de paiement: " . $e->getMessage()
                ]);
            }
            exit;
            break;
            
        case 'confirmer-paiement':
            // Définir l'en-tête Content-Type en début de fonction
            header('Content-Type: application/json');
            
            // Configurer un journal d'erreurs spécifique
            $paymentLogFile = 'logs/payment_confirmations.log';
            file_put_contents($paymentLogFile, date('Y-m-d H:i:s') . " - Tentative de confirmation de paiement\n", FILE_APPEND);
            file_put_contents($paymentLogFile, "Données POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
            
            // Récupérer les données POST
            $paymentIntentId = isset($_POST['payment_intent_id']) ? $_POST['payment_intent_id'] : '';
            $id_commande = isset($_POST['id_commande']) ? (int)$_POST['id_commande'] : 0;
            $methode = isset($_POST['methode_paiement']) ? $_POST['methode_paiement'] : 'carte';
            $reference = isset($_POST['reference']) ? $_POST['reference'] : '';
            
            if ($id_commande <= 0) {
                file_put_contents($paymentLogFile, "Erreur: ID de commande invalide ($id_commande)\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => "Commande invalide"
                ]);
                exit;
            }
            
            // Récupérer les informations de la commande pour vérification
            $commande = $commandeModel->getById($id_commande);
            if (!$commande) {
                file_put_contents($paymentLogFile, "Erreur: Commande #$id_commande introuvable\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => "Commande introuvable"
                ]);
                exit;
            }
            
            // Vérifier que la commande appartient bien à l'utilisateur connecté
            if ($commande['id_utilisateur'] != $_SESSION['id']) {
                file_put_contents($paymentLogFile, "Erreur: Tentative d'accès à une commande d'un autre utilisateur\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => "Vous n'êtes pas autorisé à accéder à cette commande"
                ]);
                exit;
            }
            
            try {
                if ($methode === 'carte') {
                    // Paiement par carte (Stripe)
                    if (empty($paymentIntentId)) {
                        file_put_contents($paymentLogFile, "Erreur: Payment Intent ID manquant\n", FILE_APPEND);
                        echo json_encode([
                            'success' => false,
                            'message' => "Données de paiement Stripe manquantes"
                        ]);
                        exit;
                    }
                    
                    file_put_contents($paymentLogFile, "Vérification du paiement pour PI: $paymentIntentId\n", FILE_APPEND);
                    
                    // Vérifier le statut du paiement
                    $resultat = $stripeModel->verifierPaiement($paymentIntentId);
                    file_put_contents($paymentLogFile, "Résultat vérification: " . print_r($resultat, true) . "\n", FILE_APPEND);
                    
                    if (!$resultat['success']) {
                        echo json_encode($resultat);
                        exit;
                    }
                    
                    // Si le paiement est réussi ou en attente de capture
                    if (in_array($resultat['status'], ['succeeded', 'requires_capture', 'processing'])) {
                        $db->beginTransaction();
                        try {
                            // Vérifier si le paiement n'est pas déjà enregistré
                            $paiementExistant = $stripeModel->getPaiementCommande($id_commande);
                            
                            if (!$paiementExistant) {
                                // Enregistrer le paiement
                                $stripeModel->enregistrerPaiement(
                                    $id_commande,
                                    $paymentIntentId,
                                    $resultat['status'],
                                    $resultat['amount'],
                                    json_encode(['payment_method' => $resultat['payment_method']])
                                );
                                file_put_contents($paymentLogFile, "Paiement enregistré pour commande #$id_commande\n", FILE_APPEND);
                            } else {
                                file_put_contents($paymentLogFile, "Paiement déjà enregistré pour commande #$id_commande\n", FILE_APPEND);
                            }
                            
                            // Mettre à jour le statut de la commande seulement si pas déjà payée
                            if ($commande['statut'] != 'payée') {
                                $commandeModel->updateStatut($id_commande, 'payée');
                                file_put_contents($paymentLogFile, "Statut de commande #$id_commande mis à jour: payée\n", FILE_APPEND);
                            }
                            
                            // Ajouter une entrée dans l'historique
                            $commandeModel->addHistoriqueEntry(
                                $id_commande,
                                $_SESSION['id'],
                                'paiement',
                                "Paiement Stripe confirmé (méthode: carte, status: {$resultat['status']})"
                            );
                            
                            // Générer la facture automatiquement si elle n'existe pas
                            $factureExists = $db->prepare("SELECT id FROM factures WHERE id_commande = :id_commande LIMIT 1");
                            $factureExists->execute([':id_commande' => $id_commande]);
                            
                            if (!$factureExists->fetch()) {
                                $resultFacture = $factureModel->genererFacture($id_commande);
                                if ($resultFacture['success']) {
                                    file_put_contents($paymentLogFile, "Facture générée pour commande #$id_commande\n", FILE_APPEND);
                                } else {
                                    file_put_contents($paymentLogFile, "Erreur lors de la génération de la facture: " . $resultFacture['message'] . "\n", FILE_APPEND);
                                }
                            } else {
                                file_put_contents($paymentLogFile, "Facture existante pour commande #$id_commande\n", FILE_APPEND);
                            }
                            
                            $db->commit();
                            file_put_contents($paymentLogFile, "Transaction complétée avec succès\n", FILE_APPEND);
                        } catch (Exception $e) {
                            $db->rollBack();
                            file_put_contents($paymentLogFile, "Erreur lors du traitement: " . $e->getMessage() . "\n", FILE_APPEND);
                            throw $e;
                        }
                    } else {
                        file_put_contents($paymentLogFile, "Statut de paiement non géré: " . $resultat['status'] . "\n", FILE_APPEND);
                    }
                    
                    // Retourner le résultat en JSON
                    $response = [
                        'success' => true,
                        'status' => $resultat['status'],
                        'redirect' => 'index.php?page=confirmation-commande&reference=' . $reference
                    ];
                    
                    file_put_contents($paymentLogFile, "Réponse envoyée: " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } 
                else if ($methode === 'paypal') {
                    // Paiement par PayPal
                    $paypalOrderId = isset($_POST['paypal_order_id']) ? $_POST['paypal_order_id'] : '';
                    
                    if (empty($paypalOrderId)) {
                        echo json_encode([
                            'success' => false,
                            'message' => "Données de paiement PayPal manquantes"
                        ]);
                        exit;
                    }
                    
                    // Récupérer les infos de la commande pour le montant
                    $commande = $commandeModel->getById($id_commande);
                    
                    // Vérifier le statut du paiement PayPal
                    $resultat = $paypalModel->verifierPaiement($paypalOrderId);
                    
                    if (!$resultat['success']) {
                        echo json_encode($resultat);
                        exit;
                    }
                    
                    // Si le paiement est réussi
                    if ($resultat['status'] === 'COMPLETED') {
                        $db->beginTransaction();
                        try {
                            // Mettre à jour le montant
                            $resultat['amount'] = $commande['montant_total'];
                            
                            // Enregistrer le paiement
                            $paypalModel->enregistrerPaiement(
                                $id_commande,
                                $paypalOrderId,
                                'payée', // statut de la commande
                                $resultat['amount'],
                                json_encode(['payment_method' => 'paypal'])
                            );
                            
                            // Mettre à jour le statut de la commande
                            $commandeModel->updateStatut($id_commande, 'payée');
                            
                            // Ajouter une entrée dans l'historique
                            $commandeModel->addHistoriqueEntry(
                                $id_commande,
                                $_SESSION['id'],
                                'paiement',
                                "Paiement PayPal confirmé"
                            );
                            
                            // Générer la facture automatiquement si elle n'existe pas
                            $factureExists = $db->prepare("SELECT id FROM factures WHERE id_commande = :id_commande LIMIT 1");
                            $factureExists->execute([':id_commande' => $id_commande]);
                            
                            if (!$factureExists->fetch()) {
                                $resultFacture = $factureModel->genererFacture($id_commande);
                                if ($resultFacture['success']) {
                                    file_put_contents($paymentLogFile, "Facture générée pour commande #$id_commande\n", FILE_APPEND);
                                } else {
                                    file_put_contents($paymentLogFile, "Erreur lors de la génération de la facture: " . $resultFacture['message'] . "\n", FILE_APPEND);
                                }
                            } else {
                                file_put_contents($paymentLogFile, "Facture existante pour commande #$id_commande\n", FILE_APPEND);
                            }
                            
                            $db->commit();
                        } catch (Exception $e) {
                            $db->rollBack();
                            file_put_contents($paymentLogFile, "Erreur lors du traitement PayPal: " . $e->getMessage() . "\n", FILE_APPEND);
                            throw $e;
                        }
                    }
                    
                    // Retourner le résultat en JSON
                    echo json_encode([
                        'success' => true,
                        'status' => $resultat['status'],
                        'redirect' => 'index.php?page=confirmation-commande&reference=' . $_POST['reference']
                    ]);
                } 
                else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Méthode de paiement non prise en charge"
                    ]);
                }
            } catch (Exception $e) {
                file_put_contents($paymentLogFile, "Exception lors du traitement du paiement: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => "Erreur lors du traitement du paiement: " . $e->getMessage()
                ]);
            }
            exit;
            break;
            
        case 'annuler-paiement':
            // Définir l'en-tête Content-Type en début de fonction
            header('Content-Type: application/json');
            
            // Capture toute la sortie pour éviter les problèmes de buffer
            ob_start();
            
            // Récupérer les données du paiement
            $methode = isset($_POST['methode_paiement']) ? $_POST['methode_paiement'] : '';
            
            if ($methode === 'carte') {
                // Annulation d'un paiement Stripe
                $paymentIntentId = isset($_POST['payment_intent_id']) ? $_POST['payment_intent_id'] : '';
                
                if (empty($paymentIntentId)) {
                    echo json_encode([
                        'success' => false,
                        'message' => "ID de paiement Stripe manquant"
                    ]);
                    exit;
                }
                
                // Annuler le paiement
                $success = $stripeModel->annulerPaiement($paymentIntentId);
                
                // Nettoyer tout buffer de sortie potentiel
                ob_end_clean();
                
                // Retourner le résultat en JSON
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? "Paiement annulé avec succès" : "Impossible d'annuler le paiement"
                ]);
            } else {
                // Nettoyer tout buffer de sortie potentiel
                ob_end_clean();
                
                // Pour PayPal ou autres méthodes
                echo json_encode([
                    'success' => true,
                    'message' => "Paiement annulé"
                ]);
            }
            exit;
            break;
            
        case 'config-paypal':
            // Définir l'en-tête Content-Type en début de fonction
            header('Content-Type: application/json');
            
            // Capture toute la sortie pour éviter les problèmes de buffer
            ob_start();
            
            // Retourner la configuration PayPal pour le front-end
            echo json_encode([
                'clientId' => $paypalModel->getClientId(),
                'currency' => $paypalModel->getCurrency()
            ]);
            
            // Nettoyer tout buffer de sortie potentiel
            ob_end_clean();
            exit;
            break;
            
        default:
            // Afficher la page de paiement par défaut (redirection vers le panier)
            header('Location: index.php?page=panier');
            exit;
    }
    
    // Restaurer les paramètres d'erreur originaux
    if (in_array($action, ['creer-intention', 'confirmer-paiement', 'annuler-paiement', 'config-paypal'])) {
        error_reporting($original_error_reporting);
        ini_set('display_errors', $original_display_errors);
    }
}
