<?php

/**
 * Classe pour la gestion des factures
 */
class Facture {
    private $db;
    private $insertFacture;
    private $selectFactureById;
    private $selectFactureByCommande;

    public function __construct($db) {
        $this->db = $db;
        
        // Requêtes préparées
        $this->insertFacture = $this->db->prepare("
            INSERT INTO factures (id_commande, numero_facture, date_emission, montant_ht, montant_tva, montant_ttc) 
            VALUES (:id_commande, :numero_facture, :date_emission, :montant_ht, :montant_tva, :montant_ttc)
        ");
        
        $this->selectFactureById = $this->db->prepare("
            SELECT * FROM factures WHERE id = :id
        ");
        
        $this->selectFactureByCommande = $this->db->prepare("
            SELECT * FROM factures WHERE id_commande = :id_commande
        ");
    }

    /**
     * Génère une facture pour une commande
     * 
     * @param int $id_commande ID de la commande
     * @return array Résultat de la génération
     */
    public function genererFacture($id_commande) {
        try {
            // Vérifier si la facture existe déjà
            $this->selectFactureByCommande->execute([':id_commande' => $id_commande]);
            $facture_existante = $this->selectFactureByCommande->fetch(PDO::FETCH_ASSOC);
            
            if ($facture_existante) {
                return [
                    'success' => true,
                    'message' => 'La facture existe déjà',
                    'id_facture' => $facture_existante['id']
                ];
            }
            
            // Récupérer les informations de la commande
            $commandeModel = new Commande($this->db);
            $commande = $commandeModel->getById($id_commande);
            
            if (!$commande) {
                return [
                    'success' => false,
                    'message' => 'Commande introuvable'
                ];
            }
            
            // Générer un numéro de facture
            $numero_facture = $this->genererNumeroFacture();
            
            // Calculer les montants
            $montant_ttc = $commande['montant_total'];
            $taux_tva = 20; // TVA à 20%
            $montant_ht = $montant_ttc / (1 + ($taux_tva / 100));
            $montant_tva = $montant_ttc - $montant_ht;
            
            // Insérer la facture dans la base de données
            $this->insertFacture->execute([
                ':id_commande' => $id_commande,
                ':numero_facture' => $numero_facture,
                ':date_emission' => date('Y-m-d H:i:s'),
                ':montant_ht' => $montant_ht,
                ':montant_tva' => $montant_tva,
                ':montant_ttc' => $montant_ttc
            ]);
            
            $id_facture = $this->db->lastInsertId();
            
            // Générer le PDF
            $this->genererPDF($id_facture);
            
            // Ajouter une entrée dans l'historique
            $commandeModel->addHistoriqueEntry(
                $id_commande,
                isset($_SESSION['id']) ? $_SESSION['id'] : 1,
                'facture_generee',
                "Facture n°$numero_facture générée"
            );
            
            return [
                'success' => true,
                'message' => 'Facture générée avec succès',
                'id_facture' => $id_facture,
                'numero_facture' => $numero_facture
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Génère un numéro de facture unique
     * 
     * @return string Numéro de facture
     */
    private function genererNumeroFacture() {
        // Format: FACT-YYYYMMDD-XXXX où XXXX est un nombre incrémental
        $date = date('Ymd');
        
        try {
            $query = $this->db->prepare("SELECT COUNT(*) FROM factures WHERE date_emission >= CURDATE()");
            $query->execute();
            $count = $query->fetchColumn();
            
            $count = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            return "FACT-{$date}-{$count}";
            
        } catch (Exception $e) {
            // En cas d'erreur, générer un numéro basé sur le timestamp
            return "FACT-" . date('Ymd') . "-" . substr(uniqid(), -4);
        }
    }

    /**
     * Génère le PDF de la facture
     * 
     * @param int $id_facture ID de la facture
     * @return bool Succès de la génération
     */
    public function genererPDF($id_facture) {
        try {
            // Récupérer les informations de la facture
            $this->selectFactureById->execute([':id' => $id_facture]);
            $facture = $this->selectFactureById->fetch(PDO::FETCH_ASSOC);
            
            if (!$facture) {
                return false;
            }
            
            // Récupérer les informations de la commande
            $commandeModel = new Commande($this->db);
            $commande = $commandeModel->getById($facture['id_commande']);
            
            if (!$commande) {
                return false;
            }
            
            // Dans un vrai système, on utiliserait une bibliothèque comme TCPDF, FPDF ou Dompdf
            // pour générer le PDF. Pour simplifier, nous allons juste créer un fichier HTML
            // qui pourra être converti en PDF plus tard.
            
            $output_dir = __DIR__ . '/../../public/factures/';
            if (!is_dir($output_dir)) {
                mkdir($output_dir, 0755, true);
            }
            
            $filename = 'facture_' . $facture['numero_facture'] . '.html';
            $filepath = $output_dir . $filename;
            
            // Générer le contenu HTML de la facture
            $html = $this->generateHtmlContent($facture, $commande);
            
            // Écrire le fichier
            file_put_contents($filepath, $html);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la génération du PDF : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère le contenu HTML de la facture - Version A4 Pro/Moderne/Innovante
     * @param array $facture Données de la facture
     * @param array $commande Données de la commande
     * @return string Contenu HTML
     */
    private function generateHtmlContent($facture, $commande) {
        // Informations de l'entreprise
        $entreprise = [
            'nom' => 'LE DESIGN DU WEB',
            'adresse' => '123 Rue Fictive',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'email' => 'contact@ldw.fr',
            'telephone' => '01 23 45 67 89',
            'siret' => '948 362 355 00026',
            'tva' => 'FR59948362355',
            'site_web' => 'www.ledesignweb.fr'
        ];
        
        // Logo de l'entreprise (noir ou blanc selon design)
        $logo_path = '../images/logov2/Logosfnoir.png';
        
        // Formatage des montants
        $fmt = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);
        $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        
        // Taux de TVA
        $taux_tva = 20; // 20% par défaut
        
        // Génération du HTML
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture ' . htmlspecialchars($facture['numero_facture']) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        body {
            font-family: "Inter", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #333333;
            font-size: 12px;
            line-height: 1.6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .invoice-container {
            width: 210mm;
            min-height: 296mm;
            margin: 0 auto;
            padding: 20mm;
            position: relative;
            background-color: #ffffff;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
        }
        @media print {
            .invoice-container {
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 20mm;
            }
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }
        .company-logo img {
            height: 60px;
            width: auto;
        }
        .document-title {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #0062cc;
            color: white;
            padding: 10px 20px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 8px 0 8px 0;
        }
        .info-box {
            padding: 20px;
            border-radius: 8px;
            position: relative;
            margin-bottom: 30px;
            background-color: #f8f9fa;
        }
        .company-info {
            border-left: 4px solid #0062cc;
        }
        .customer-info {
            border-left: 4px solid #3498db;
        }
        .info-heading {
            position: absolute;
            top: -12px;
            left: 15px;
            background-color: #ffffff;
            padding: 0 10px;
            font-size: 14px;
            font-weight: 600;
            color: #0062cc;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin: 30px 0;
            padding: 15px;
            border-radius: 8px;
            background: linear-gradient(to right, #0062cc 0%, #3498db 100%);
            color: white;
        }
        .meta-item {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 0 10px;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
        .meta-item:last-child {
            border-right: none;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 5px;
            opacity: 0.85;
        }
        .meta-value {
            font-size: 14px;
            font-weight: 700;
        }
        .invoice-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.05);
        }
        .invoice-table th {
            background-color: #0062cc;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .invoice-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .invoice-table tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        .invoice-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 350px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table th, .totals-table td {
            padding: 8px 15px;
            text-align: right;
            border-bottom: 1px solid #e0e0e0;
        }
        .totals-table tr:last-child {
            font-weight: 700;
            color: #0062cc;
            font-size: 16px;
            border-top: 2px solid #2ecc71;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            color: #777777;
            font-size: 11px;
        }
        .footer-columns {
            display: flex;
            justify-content: space-between;
        }
        .footer-column {
            flex: 1;
            padding: 0 15px;
        }
        .footer-column:first-child {
            padding-left: 0;
        }
        .footer-column:last-child {
            padding-right: 0;
            text-align: right;
        }
        .footer-title {
            font-weight: 600;
            color: #0062cc;
            margin-bottom: 5px;
        }
        .copyright {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- En-tête avec logo et titre -->
        <div class="invoice-header">
            <div class="company-logo">
                <img src="' . $logo_path . '" alt="Logo ' . htmlspecialchars($entreprise['nom']) . '">
            </div>
            <div class="document-title">FACTURE</div>
        </div>
        
        <!-- Informations entreprise et client -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-box company-info">
                    <div class="info-heading">ÉMETTEUR</div>
                    <h4>' . htmlspecialchars($entreprise['nom']) . '</h4>
                    <p>' . nl2br(htmlspecialchars($entreprise['adresse'] . "\n" . $entreprise['code_postal'] . ' ' . $entreprise['ville'])) . '</p>
                    <p>
                        <strong>Email:</strong> ' . htmlspecialchars($entreprise['email']) . '<br>
                        <strong>Tél:</strong> ' . htmlspecialchars($entreprise['telephone']) . '<br>
                        <strong>Web:</strong> ' . htmlspecialchars($entreprise['site_web']) . '
                    </p>
                    <p>
                        <strong>SIRET:</strong> ' . htmlspecialchars($entreprise['siret']) . '<br>
                        <strong>N° TVA:</strong> ' . htmlspecialchars($entreprise['tva']) . '
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box customer-info">
                    <div class="info-heading">DESTINATAIRE</div>
                    <h4>' . htmlspecialchars($commande['nom_client'] . ' ' . $commande['prenom_client']) . '</h4>
                    <p>' . nl2br(htmlspecialchars($commande['adresse_facturation'])) . '</p>
                    <p>
                        <strong>Email:</strong> ' . htmlspecialchars($commande['email']) . '
                        ' . (isset($commande['telephone_client']) && $commande['telephone_client'] ? '<br><strong>Tél:</strong> ' . htmlspecialchars($commande['telephone_client']) : '') . '
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Métadonnées de la facture -->
        <div class="invoice-meta">
            <div class="meta-item">
                <div class="meta-label">N° Facture</div>
                <div class="meta-value">' . htmlspecialchars($facture['numero_facture']) . '</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Date d\'émission</div>
                <div class="meta-value">' . date('d/m/Y', strtotime($facture['date_emission'])) . '</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Réf. Commande</div>
                <div class="meta-value">' . htmlspecialchars($commande['reference']) . '</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Méthode de paiement</div>
                <div class="meta-value">' . htmlspecialchars($commande['methode_paiement']) . '</div>
            </div>
        </div>
        
        <!-- Tableau des produits -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qté</th>
                    <th class="text-right">Prix Unit. HT</th>
                    <th class="text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>';
        
        // Ajouter les produits
        foreach ($commande['produits'] as $produit) {
            // Prix unitaire HT (si stocké en TTC, déduire la TVA)
            $prix_unitaire_ht = $produit['prix_unitaire']; // Supposons que c'est déjà HT
            $total_ht = $prix_unitaire_ht * $produit['quantite'];
            
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($produit['nom']) . '</td>
                    <td class="text-right">' . htmlspecialchars($produit['quantite']) . '</td>
                    <td class="text-right">' . $fmt->formatCurrency($prix_unitaire_ht, "EUR") . '</td>
                    <td class="text-right">' . $fmt->formatCurrency($total_ht, "EUR") . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>
        
        <!-- Totaux -->
        <table class="totals-table">
            <tr>
                <th>Total HT</th>
                <td>' . $fmt->formatCurrency($facture['montant_ht'], "EUR") . '</td>
            </tr>
            <tr>
                <th>TVA (' . $taux_tva . '%)</th>
                <td>' . $fmt->formatCurrency($facture['montant_tva'], "EUR") . '</td>
            </tr>
            <tr>
                <th>Total TTC</th>
                <td>' . $fmt->formatCurrency($facture['montant_ttc'], "EUR") . '</td>
            </tr>
        </table>
        
        <!-- Pied de page -->
        <div class="footer">
            <div class="footer-columns">
                <div class="footer-column">
                    <div class="footer-title">Conditions de paiement</div>
                    <p>Paiement à réception de la facture.<br>Aucun escompte pour paiement anticipé.</p>
                </div>
                <div class="footer-column">
                    <div class="footer-title">Contact</div>
                    <p>' . htmlspecialchars($entreprise['email']) . '<br>' . htmlspecialchars($entreprise['telephone']) . '</p>
                </div>
                <div class="footer-column">
                    <div class="footer-title">Facture générée le</div>
                    <p>' . date('d/m/Y à H:i') . '</p>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($entreprise['nom']) . ' - Tous droits réservés</p>
            </div>
        </div>
    </div>
</body>
</html>';
        
        // Nettoyer l'instance NumberFormatter
        unset($fmt);
        
        return $html;
    }

    /**
     * Récupère une facture par son ID
     * 
     * @param int $id ID de la facture
     * @return array|null Facture
     */
    public function getById($id) {
        try {
            $this->selectFactureById->execute([':id' => $id]);
            return $this->selectFactureById->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère une facture par l'ID de la commande
     * 
     * @param int $id_commande ID de la commande
     * @return array|null Facture
     */
    public function getByCommande($id_commande) {
        try {
            $this->selectFactureByCommande->execute([':id_commande' => $id_commande]);
            return $this->selectFactureByCommande->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
}
