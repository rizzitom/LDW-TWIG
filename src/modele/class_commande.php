<?php

class Commande {
    private $db;
    private $insert;
    private $insertProduit;
    private $selectById;
    private $selectProduits;
    private $selectByUtilisateur;
    private $selectAll;
    private $updateStatut;
    private $genererReference;
    private $selectHistorique;

    public function __construct($db) {
        $this->db = $db;
        
        // Requêtes préparées
        $this->insert = $this->db->prepare("INSERT INTO commandes (id_utilisateur, montant_total, adresse_livraison, 
                                          adresse_facturation, methode_paiement, reference, notes) 
                                          VALUES (:id_utilisateur, :montant_total, :adresse_livraison, 
                                          :adresse_facturation, :methode_paiement, :reference, :notes)");
        
        $this->insertProduit = $this->db->prepare("INSERT INTO commande_produit (id_commande, id_produit, quantite, prix_unitaire) 
                                                 VALUES (:id_commande, :id_produit, :quantite, :prix_unitaire)");
        
        $this->selectById = $this->db->prepare("SELECT c.*, u.email, u.nom as nom_client, u.prenom as prenom_client 
                                              FROM commandes c 
                                              JOIN utilisateurs u ON c.id_utilisateur = u.id 
                                              WHERE c.id = :id");
        
        $this->selectProduits = $this->db->prepare("SELECT cp.*, p.nom, p.image_url 
                                                  FROM commande_produit cp 
                                                  JOIN produits p ON cp.id_produit = p.id 
                                                  WHERE cp.id_commande = :id_commande");
        
        $this->selectByUtilisateur = $this->db->prepare("SELECT * FROM commandes 
                                                       WHERE id_utilisateur = :id_utilisateur 
                                                       ORDER BY date_commande DESC");
        
        $this->selectAll = $this->db->prepare("SELECT c.*, u.email, u.nom as nom_client, u.prenom as prenom_client 
                                             FROM commandes c 
                                             JOIN utilisateurs u ON c.id_utilisateur = u.id 
                                             ORDER BY c.date_commande DESC");
        
        $this->updateStatut = $this->db->prepare("UPDATE commandes SET statut = :statut WHERE id = :id");
        
        $this->genererReference = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE DATE(date_commande) = CURDATE()");
        
        $this->selectHistorique = $this->db->prepare("SELECT h.*, u.nom, u.prenom 
                                                   FROM historique_commande h
                                                   LEFT JOIN utilisateurs u ON h.id_utilisateur = u.id
                                                   WHERE h.id_commande = :id_commande
                                                   ORDER BY h.date_action DESC");
    }

    // Créer une nouvelle commande
    public function creer($id_utilisateur, $panier, $adresse_livraison, $adresse_facturation, $methode_paiement, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Générer une référence unique pour la commande
            $reference = $this->genererReference();
            
            // Calculer le montant total TTC (produits + TVA + frais de livraison)
            $montant_total = $panier['total'] + $panier['tva'] + $panier['frais_livraison'];
            
            // Mettre à jour la requête préparée pour inclure les nouveaux champs
            $query = $this->db->prepare("INSERT INTO commandes (id_utilisateur, montant_total, adresse_livraison, 
                                      adresse_facturation, methode_paiement, reference, notes, 
                                      frais_livraison, tva, pays_livraison, mode_livraison, 
                                      point_relais_id, point_relais_nom, instructions_livraison) 
                                      VALUES (:id_utilisateur, :montant_total, :adresse_livraison, 
                                      :adresse_facturation, :methode_paiement, :reference, :notes,
                                      :frais_livraison, :tva, :pays_livraison, :mode_livraison,
                                      :point_relais_id, :point_relais_nom, :instructions_livraison)");
            
            // Préparation des données de livraison Mondial Relay si disponibles
            $mode_livraison = isset($panier['methode_livraison']) ? $panier['methode_livraison'] : 'domicile';
            $point_relais_id = null;
            $point_relais_nom = null;
            $instructions_livraison = isset($panier['instructions_livraison']) ? $panier['instructions_livraison'] : null;
            
            // Si c'est une livraison en point relais et que les données sont disponibles
            if ($mode_livraison === 'relay' && isset($panier['point_relais_id'])) {
                $point_relais_id = $panier['point_relais_id'];
                
                // Si le nom du point relais est fourni, on l'enregistre aussi
                if (isset($panier['point_relais_nom'])) {
                    $point_relais_nom = $panier['point_relais_nom'];
                }
            }
            
            // Utiliser les adresses du panier si elles sont définies
            if (empty($adresse_livraison) && !empty($panier['adresse_livraison'])) {
                $adresse_livraison = $panier['adresse_livraison'];
            }
            
            if (empty($adresse_facturation) && !empty($panier['adresse_facturation'])) {
                $adresse_facturation = $panier['adresse_facturation'];
            }
            
            // Insérer la commande avec les informations complètes
            $query->execute([
                ':id_utilisateur' => $id_utilisateur,
                ':montant_total' => $montant_total,
                ':adresse_livraison' => $adresse_livraison,
                ':adresse_facturation' => $adresse_facturation,
                ':methode_paiement' => $methode_paiement,
                ':reference' => $reference,
                ':notes' => $notes,
                ':frais_livraison' => $panier['frais_livraison'],
                ':tva' => $panier['tva'],
                ':pays_livraison' => $panier['pays_livraison'],
                ':mode_livraison' => $mode_livraison,
                ':point_relais_id' => $point_relais_id,
                ':point_relais_nom' => $point_relais_nom,
                ':instructions_livraison' => $instructions_livraison
            ]);
            
            $id_commande = $this->db->lastInsertId();
            
            // Insérer les produits de la commande
            foreach ($panier['produits'] as $produit) {
                $this->insertProduit->execute([
                    ':id_commande' => $id_commande,
                    ':id_produit' => $produit['id'],
                    ':quantite' => $produit['quantite'],
                    ':prix_unitaire' => $produit['prix']
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'id_commande' => $id_commande,
                'reference' => $reference
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Récupérer une commande par son ID avec ses produits
    public function getById($id) {
        try {
            $this->selectById->execute([':id' => $id]);
            $commande = $this->selectById->fetch(PDO::FETCH_ASSOC);
            
            if (!$commande) {
                return null;
            }
            
            // Récupérer les produits de la commande
            $this->selectProduits->execute([':id_commande' => $id]);
            $produits = $this->selectProduits->fetchAll(PDO::FETCH_ASSOC);
            
            $commande['produits'] = $produits;
            
            return $commande;
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return null;
        }
    }

    // Récupérer toutes les commandes d'un utilisateur
    public function getByUtilisateur($id_utilisateur) {
        try {
            $this->selectByUtilisateur->execute([':id_utilisateur' => $id_utilisateur]);
            return $this->selectByUtilisateur->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Récupérer toutes les commandes (pour l'admin)
    public function getAll() {
        try {
            $this->selectAll->execute();
            return $this->selectAll->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Mettre à jour le statut d'une commande
    public function updateStatut($id, $statut) {
        try {
            $this->updateStatut->execute([
                ':id' => $id,
                ':statut' => $statut
            ]);
            
            if ($this->updateStatut->rowCount() > 0) {
                // Ajouter une entrée dans l'historique
                $this->addHistoriqueEntry($id, isset($_SESSION['id']) ? $_SESSION['id'] : 1, 
                                        'modification_statut', 
                                        "Statut changé en: $statut");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Générer une référence unique pour la commande
    private function genererReference() {
        try {
            $this->genererReference->execute();
            $count = $this->genererReference->fetchColumn();
            
            // Format: CMD-YYYYMMDD-XXXX où XXXX est un nombre incrémental
            $date = date('Ymd');
            $count = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            return "CMD-{$date}-{$count}";
            
        } catch (Exception $e) {
            // En cas d'erreur, générer une référence basée sur le timestamp
            return "CMD-" . date('Ymd') . "-" . substr(uniqid(), -4);
        }
    }
    
    // Récupérer l'historique d'une commande
    public function getHistorique($id_commande) {
        try {
            $this->selectHistorique->execute([':id_commande' => $id_commande]);
            return $this->selectHistorique->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }
    
    // Ajouter une entrée dans l'historique d'une commande
    public function addHistoriqueEntry($id_commande, $id_utilisateur, $action, $details = null) {
        try {
            $query = $this->db->prepare("INSERT INTO historique_commande 
                                        (id_commande, id_utilisateur, action, details) 
                                        VALUES (:id_commande, :id_utilisateur, :action, :details)");
            
            $query->execute([
                ':id_commande' => $id_commande,
                ':id_utilisateur' => $id_utilisateur,
                ':action' => $action,
                ':details' => $details
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }
    
    // Ajouter un motif d'annulation à une commande
    public function addMotifAnnulation($id_commande, $motif, $commentaire = null) {
        try {
            // Enregistrer le motif dans la base de données
            $query = $this->db->prepare("UPDATE commandes SET 
                                      motif_annulation = :motif,
                                      commentaire_annulation = :commentaire 
                                      WHERE id = :id_commande");
            
            $query->execute([
                ':id_commande' => $id_commande,
                ':motif' => $motif,
                ':commentaire' => $commentaire
            ]);
            
            // Ajouter une entrée dans l'historique
            $details = "Motif: $motif";
            if (!empty($commentaire)) {
                $details .= ", Commentaire: $commentaire";
            }
            
            $this->addHistoriqueEntry($id_commande, isset($_SESSION['id']) ? $_SESSION['id'] : 1, 
                                    'annulation', $details);
            
            return true;
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }
    
    // Ajouter un avis sur une commande
    public function addAvis($id_commande, $note, $commentaire = null) {
        try {
            // Enregistrer l'avis dans la base de données
            $query = $this->db->prepare("UPDATE commandes SET 
                                      note_client = :note,
                                      commentaire_client = :commentaire 
                                      WHERE id = :id_commande");
            
            $query->execute([
                ':id_commande' => $id_commande,
                ':note' => $note,
                ':commentaire' => $commentaire
            ]);
            
            // Ajouter une entrée dans l'historique
            $details = "Note: $note/5";
            if (!empty($commentaire)) {
                $details .= ", Commentaire: $commentaire";
            }
            
            $this->addHistoriqueEntry($id_commande, isset($_SESSION['id']) ? $_SESSION['id'] : 1, 
                                    'avis_client', $details);
            
            return true;
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Obtenir les statistiques des commandes (pour l'admin)
    public function getStats() {
        try {
            // Total des ventes
            $totalVentes = $this->db->query("SELECT SUM(montant_total) AS total FROM commandes WHERE statut != 'annulee'")->fetchColumn();
            
            // Nombre de commandes
            $nombreCommandes = $this->db->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
            
            // Commandes par statut
            $commandesParStatut = $this->db->query("SELECT statut, COUNT(*) AS nombre FROM commandes GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC);
            
            // Ventes des 30 derniers jours
            $ventesRecentes = $this->db->query("SELECT DATE(date_commande) AS date, SUM(montant_total) AS total 
                                               FROM commandes 
                                               WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                                               AND statut != 'annulee' 
                                               GROUP BY DATE(date_commande)")->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total_ventes' => $totalVentes,
                'nombre_commandes' => $nombreCommandes,
                'commandes_par_statut' => $commandesParStatut,
                'ventes_recentes' => $ventesRecentes
            ];
            
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }
}
