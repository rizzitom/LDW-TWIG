<?php

// Import the Stripe SDK
require_once __DIR__ . '/../../lib/vendor/autoload.php';
require_once __DIR__ . '/../../lib/vendor/stripe/stripe-php/init.php';

/**
 * Classe pour l'intégration des paiements Stripe
 */
class Stripe {
    private $db;
    private $stripeSecretKey;
    private $stripePublicKey;
    private $currency;
    private $mode;

    public function __construct($db) {
        $this->db = $db;
        
        // Charger la configuration Stripe
        // Use require instead of require_once to ensure the config is loaded fresh each time
        $configPath = __DIR__ . '/../../config/stripe.php';
        $config = require $configPath;
        
        $this->stripeSecretKey = $config['secretKey'];
        $this->stripePublicKey = $config['publicKey'];
        $this->currency = $config['currency'];
        $this->mode = $config['mode'];
        
        // Log the secret key being used (without exposing the full key)
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/payment_errors.log';
        $keyPrefix = substr($this->stripeSecretKey, 0, 10);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using Stripe key prefix: {$keyPrefix}...\n", FILE_APPEND);
        
        // Initialiser l'API Stripe
        \Stripe\Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Récupère la clé publique Stripe pour le front-end
     */
    public function getPublicKey() {
        return $this->stripePublicKey;
    }

    /**
     * Récupère la devise configurée
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * Crée une intention de paiement Stripe
     * 
     * @param float $montant Montant à payer (en euros)
     * @param string $description Description du paiement
     * @param array $metadata Métadonnées supplémentaires
     * @param array $payment_methods Types de méthodes de paiement à activer
     * @return array Résultat de la création
     */
    public function creerIntentionPaiement($montant, $description, $metadata = [], $payment_methods = null) {
        // Configurer le journal pour le débogage
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/payment_errors.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Stripe::creerIntentionPaiement - Démarrage avec montant: $montant\n", FILE_APPEND);
        
        try {
            // Valider le montant
            if (!is_numeric($montant) || $montant <= 0) {
                file_put_contents($logFile, "Erreur: Montant invalide: $montant\n", FILE_APPEND);
                throw new \Exception("Montant invalide pour l'intention de paiement: $montant");
            }
            
            // Convertir en centimes pour Stripe
            $montantCentimes = (int)($montant * 100);
            file_put_contents($logFile, "Montant en centimes: $montantCentimes\n", FILE_APPEND);
            
            // Définir les méthodes de paiement par défaut si non spécifiées
            if ($payment_methods === null || !is_array($payment_methods) || empty($payment_methods)) {
                file_put_contents($logFile, "Définition des méthodes de paiement par défaut\n", FILE_APPEND);
                $payment_methods = [
                    'card',            // Cartes bancaires internationales
                    'link',            // Link
                    'revolut_pay',     // Revolut Pay
                ];
            }
            
            file_put_contents($logFile, "Méthodes de paiement reçues: " . print_r($payment_methods, true) . "\n", FILE_APPEND);
            
            // Filtrer les méthodes de paiement pour ne garder que celles valides
            $valid_methods = [
                'card',
                'link',
                'revolut_pay'
            ];
            
            $filtered_methods = array_values(array_filter($payment_methods, function($method) use ($valid_methods) {
                return in_array(trim($method), $valid_methods);
            }));
            
            // S'assurer qu'au moins card est disponible
            if (empty($filtered_methods)) {
                $filtered_methods = ['card'];
            }
            
            error_log("Méthodes de paiement utilisées: " . implode(', ', $filtered_methods));
            
            // Préparer les paramètres pour la création de l'intention de paiement
            $params = [
                'amount' => $montantCentimes,
                'currency' => $this->currency,
                'description' => $description,
                'metadata' => $metadata,
                'payment_method_types' => $filtered_methods
            ];
            
            // Ajouter les options pour PayPal seulement si PayPal est inclus
            if (in_array('paypal', $filtered_methods)) {
                $params['payment_method_options'] = [
                    'paypal' => [
                        'preferred_locale' => 'fr-FR',
                    ]
                ];
            }
            
            // Créer l'intention de paiement
            error_log("Création de l'intention de paiement avec Stripe");
            $paymentIntent = \Stripe\PaymentIntent::create($params);
            
            error_log("Intention de paiement créée avec succès. ID: " . $paymentIntent->id);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Erreur API Stripe: " . $e->getMessage());
            error_log("Fichier: " . $e->getFile() . " (Ligne: " . $e->getLine() . ")");
            
            return [
                'success' => false,
                'message' => "Erreur Stripe: " . $e->getMessage(),
                'type' => 'api_error'
            ];
        } catch (\Exception $e) {
            error_log("Exception générale: " . $e->getMessage());
            error_log("Fichier: " . $e->getFile() . " (Ligne: " . $e->getLine() . ")");
            
            return [
                'success' => false,
                'message' => "Erreur lors de la création de l'intention de paiement: " . $e->getMessage(),
                'type' => 'general_error'
            ];
        }
    }

    /**
     * Vérifie le statut d'une intention de paiement
     * 
     * @param string $paymentIntentId ID de l'intention de paiement
     * @return array Statut du paiement
     */
    public function verifierPaiement($paymentIntentId) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'payment_method' => $paymentIntent->payment_method,
                'amount' => $paymentIntent->amount / 100 // Convertir les centimes en euros
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Annule une intention de paiement
     * 
     * @param string $paymentIntentId ID de l'intention de paiement
     * @return bool Succès de l'annulation
     */
    public function annulerPaiement($paymentIntentId) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status !== 'succeeded') {
                $paymentIntent->cancel();
                return true;
            }
            
            return false;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return false;
        }
    }

    /**
     * Génère une trace de paiement dans la base de données
     * 
     * @param int $id_commande ID de la commande associée
     * @param string $payment_intent_id ID de l'intention de paiement Stripe
     * @param string $status Statut du paiement
     * @param float $amount Montant du paiement
     * @param string $details Détails supplémentaires (JSON)
     * @return bool Succès de l'enregistrement
     */
    public function enregistrerPaiement($id_commande, $payment_intent_id, $status, $amount, $details = null) {
        try {
            $query = $this->db->prepare("INSERT INTO paiements 
                                       (id_commande, payment_intent_id, statut, montant, details, date_paiement) 
                                       VALUES (:id_commande, :payment_intent_id, :statut, :montant, :details, NOW())");
            
            $query->execute([
                ':id_commande' => $id_commande,
                ':payment_intent_id' => $payment_intent_id,
                ':statut' => $status,
                ':montant' => $amount,
                ':details' => $details
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur d'enregistrement du paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les informations d'un paiement
     * 
     * @param int $id_commande ID de la commande
     * @return array|null Informations sur le paiement
     */
    public function getPaiementCommande($id_commande) {
        try {
            $query = $this->db->prepare("SELECT * FROM paiements WHERE id_commande = :id_commande ORDER BY date_paiement DESC LIMIT 1");
            $query->execute([':id_commande' => $id_commande]);
            
            return $query->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur de récupération du paiement: " . $e->getMessage());
            return null;
        }
    }
}
