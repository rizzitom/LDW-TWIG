<?php

class Service {
    private $db;
    private $select;
    private $selectById;
    private $selectActifs;
    private $insert;
    private $update;
    private $delete;

    public function __construct($db) {
        $this->db = $db;
        
        // Requêtes de sélection
        $this->select = $db->prepare("SELECT * FROM services ORDER BY nom");
        
        $this->selectById = $db->prepare("SELECT * FROM services WHERE id = :id");
        
        $this->selectActifs = $db->prepare("SELECT * FROM services WHERE est_actif = 1 ORDER BY nom");
        
        // Requêtes de modification
        $this->insert = $db->prepare("INSERT INTO services (nom, description, prix_base, image_url, est_actif) 
                                     VALUES (:nom, :description, :prix_base, :image_url, :est_actif)");
        
        $this->update = $db->prepare("UPDATE services 
                                     SET nom = :nom, description = :description, prix_base = :prix_base, 
                                     image_url = :image_url, est_actif = :est_actif 
                                     WHERE id = :id");
        
        $this->delete = $db->prepare("UPDATE services SET est_actif = 0 WHERE id = :id");
    }

    // Récupérer tous les services
    public function selectAll() {
        try {
            $this->select->execute();
            if ($this->select->errorCode() != 0) {
                print_r($this->select->errorInfo());
                return [];
            }
            return $this->select->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Récupérer un service par son ID
    public function selectById($id) {
        try {
            $this->selectById->execute([':id' => $id]);
            if ($this->selectById->errorCode() != 0) {
                print_r($this->selectById->errorInfo());
                return null;
            }
            return $this->selectById->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return null;
        }
    }

    // Récupérer tous les services actifs
    public function selectActifs() {
        try {
            $this->selectActifs->execute();
            if ($this->selectActifs->errorCode() != 0) {
                print_r($this->selectActifs->errorInfo());
                return [];
            }
            return $this->selectActifs->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Créer un nouveau service
    public function insert($data) {
        try {
            $this->insert->execute([
                ':nom' => $data['nom'],
                ':description' => $data['description'] ?? null,
                ':prix_base' => $data['prix_base'] ?? null,
                ':image_url' => $data['image_url'] ?? null,
                ':est_actif' => $data['est_actif'] ?? true
            ]);
            
            if ($this->insert->errorCode() != 0) {
                print_r($this->insert->errorInfo());
                return false;
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Mettre à jour un service
    public function update($id, $data) {
        try {
            $this->update->execute([
                ':id' => $id,
                ':nom' => $data['nom'],
                ':description' => $data['description'] ?? null,
                ':prix_base' => $data['prix_base'] ?? null,
                ':image_url' => $data['image_url'] ?? null,
                ':est_actif' => $data['est_actif'] ?? true
            ]);
            
            if ($this->update->errorCode() != 0) {
                print_r($this->update->errorInfo());
                return false;
            }
            
            return $this->update->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Supprimer un service (désactivation)
    public function delete($id) {
        try {
            $this->delete->execute([':id' => $id]);
            if ($this->delete->errorCode() != 0) {
                print_r($this->delete->errorInfo());
                return false;
            }
            return $this->delete->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }
}

class Devis {
    private $db;
    private $insert;
    private $selectById;
    private $selectByUtilisateur;
    private $selectAll;
    private $updateStatut;
    private $updateMontant;
    private $updateNotes;

    public function __construct($db) {
        $this->db = $db;
        
        // Requêtes de création et sélection
        $this->insert = $db->prepare("INSERT INTO devis (id_utilisateur, id_service, description_demande, statut) 
                                     VALUES (:id_utilisateur, :id_service, :description_demande, :statut)");
        
        $this->selectById = $db->prepare("SELECT d.*, s.nom as service_nom, u.email, u.nom as nom_client, u.prenom as prenom_client 
                                         FROM devis d 
                                         JOIN services s ON d.id_service = s.id 
                                         JOIN utilisateurs u ON d.id_utilisateur = u.id 
                                         WHERE d.id = :id");
        
        $this->selectByUtilisateur = $db->prepare("SELECT d.*, s.nom as service_nom 
                                                 FROM devis d 
                                                 JOIN services s ON d.id_service = s.id 
                                                 WHERE d.id_utilisateur = :id_utilisateur 
                                                 ORDER BY d.date_demande DESC");
        
        $this->selectAll = $db->prepare("SELECT d.*, s.nom as service_nom, u.email, u.nom as nom_client, u.prenom as prenom_client 
                                       FROM devis d 
                                       JOIN services s ON d.id_service = s.id 
                                       JOIN utilisateurs u ON d.id_utilisateur = u.id 
                                       ORDER BY d.date_demande DESC");
        
        // Requêtes de mise à jour
        $this->updateStatut = $db->prepare("UPDATE devis 
                                           SET statut = :statut, date_reponse = CURRENT_TIMESTAMP 
                                           WHERE id = :id");
        
        $this->updateMontant = $db->prepare("UPDATE devis 
                                           SET montant_estime = :montant_estime 
                                           WHERE id = :id");
        
        $this->updateNotes = $db->prepare("UPDATE devis 
                                         SET notes_admin = :notes_admin 
                                         WHERE id = :id");
    }

    // Créer une nouvelle demande de devis
    public function creer($id_utilisateur, $id_service, $description_demande) {
        try {
            $this->insert->execute([
                ':id_utilisateur' => $id_utilisateur,
                ':id_service' => $id_service,
                ':description_demande' => $description_demande,
                ':statut' => 'en_attente'
            ]);
            
            if ($this->insert->errorCode() != 0) {
                print_r($this->insert->errorInfo());
                return false;
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Récupérer un devis par son ID
    public function getById($id) {
        try {
            $this->selectById->execute([':id' => $id]);
            if ($this->selectById->errorCode() != 0) {
                print_r($this->selectById->errorInfo());
                return null;
            }
            return $this->selectById->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return null;
        }
    }

    // Récupérer tous les devis d'un utilisateur
    public function getByUtilisateur($id_utilisateur) {
        try {
            $this->selectByUtilisateur->execute([':id_utilisateur' => $id_utilisateur]);
            if ($this->selectByUtilisateur->errorCode() != 0) {
                print_r($this->selectByUtilisateur->errorInfo());
                return [];
            }
            return $this->selectByUtilisateur->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Récupérer tous les devis (pour l'admin)
    public function getAll() {
        try {
            $this->selectAll->execute();
            if ($this->selectAll->errorCode() != 0) {
                print_r($this->selectAll->errorInfo());
                return [];
            }
            return $this->selectAll->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Mettre à jour le statut d'un devis
    public function updateStatut($id, $statut) {
        try {
            $this->updateStatut->execute([
                ':id' => $id,
                ':statut' => $statut
            ]);
            
            if ($this->updateStatut->errorCode() != 0) {
                print_r($this->updateStatut->errorInfo());
                return false;
            }
            
            return $this->updateStatut->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Mettre à jour le montant estimé d'un devis
    public function updateMontant($id, $montant_estime) {
        try {
            $this->updateMontant->execute([
                ':id' => $id,
                ':montant_estime' => $montant_estime
            ]);
            
            if ($this->updateMontant->errorCode() != 0) {
                print_r($this->updateMontant->errorInfo());
                return false;
            }
            
            return $this->updateMontant->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Mettre à jour les notes administrateur d'un devis
    public function updateNotes($id, $notes_admin) {
        try {
            $this->updateNotes->execute([
                ':id' => $id,
                ':notes_admin' => $notes_admin
            ]);
            
            if ($this->updateNotes->errorCode() != 0) {
                print_r($this->updateNotes->errorInfo());
                return false;
            }
            
            return $this->updateNotes->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }
}
