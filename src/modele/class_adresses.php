<?php

class adresses {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getByUtilisateur($id_utilisateur) {
        $query = $this->db->prepare("SELECT * FROM adresses WHERE id_utilisateur = :id_utilisateur ORDER BY is_default DESC");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return array();
        }
        return $query->fetchAll();
    }
    
    public function getAdressePrincipale($id_utilisateur = null) {
        $query = $this->db->prepare("SELECT * FROM adresses WHERE id_utilisateur = :id_utilisateur AND is_default = 1 LIMIT 1");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return $query->fetch();
    }
    
    public function getById($id) {
        $query = $this->db->prepare("SELECT * FROM adresses WHERE id = :id");
        $query->execute(array(':id' => $id));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return $query->fetch();
    }
    
    public function add($id_utilisateur, $nom_adresse, $type_adresse, $nom_complet, $rue, $complement, $code_postal, $ville, $pays, $telephone, $is_default) {
        // Si l'adresse est définie par défaut, on désactive les autres adresses par défaut
        if ($is_default) {
            $this->resetDefault($id_utilisateur);
        }
        
        $query = $this->db->prepare("INSERT INTO adresses (
                                        id_utilisateur, nom_adresse, type_adresse, nom_complet, rue, complement, 
                                        code_postal, ville, pays, telephone, is_default, date_creation
                                    ) VALUES (
                                        :id_utilisateur, :nom_adresse, :type_adresse, :nom_complet, :rue, :complement, 
                                        :code_postal, :ville, :pays, :telephone, :is_default, NOW()
                                    )");
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':nom_adresse' => $nom_adresse,
            ':type_adresse' => $type_adresse,
            ':nom_complet' => $nom_complet,
            ':rue' => $rue,
            ':complement' => $complement,
            ':code_postal' => $code_postal,
            ':ville' => $ville,
            ':pays' => $pays,
            ':telephone' => $telephone,
            ':is_default' => $is_default
        ));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return $this->db->lastInsertId();
    }
    
    public function update($id, $nom_adresse, $type_adresse, $nom_complet, $rue, $complement, $code_postal, $ville, $pays, $telephone, $is_default) {
        // Récupérer l'id_utilisateur pour réinitialiser les adresses par défaut
        $adresse = $this->getById($id);
        
        // Si l'adresse est définie par défaut, on désactive les autres adresses par défaut
        if ($is_default && $adresse) {
            $this->resetDefault($adresse['id_utilisateur']);
        }
        
        $query = $this->db->prepare("UPDATE adresses SET 
                                        nom_adresse = :nom_adresse,
                                        type_adresse = :type_adresse,
                                        nom_complet = :nom_complet,
                                        rue = :rue,
                                        complement = :complement,
                                        code_postal = :code_postal,
                                        ville = :ville,
                                        pays = :pays,
                                        telephone = :telephone,
                                        is_default = :is_default,
                                        date_modification = NOW()
                                     WHERE id = :id");
        $query->execute(array(
            ':id' => $id,
            ':nom_adresse' => $nom_adresse,
            ':type_adresse' => $type_adresse,
            ':nom_complet' => $nom_complet,
            ':rue' => $rue,
            ':complement' => $complement,
            ':code_postal' => $code_postal,
            ':ville' => $ville,
            ':pays' => $pays,
            ':telephone' => $telephone,
            ':is_default' => $is_default
        ));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    public function delete($id) {
        // Vérifier si c'est une adresse par défaut
        $adresse = $this->getById($id);
        
        $query = $this->db->prepare("DELETE FROM adresses WHERE id = :id");
        $query->execute(array(':id' => $id));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        
        // Si c'était l'adresse par défaut, définir une autre adresse comme défaut s'il en reste
        if ($adresse && $adresse['is_default']) {
            $this->setNewDefault($adresse['id_utilisateur']);
        }
        
        return true;
    }
    
    public function setDefault($id, $id_utilisateur) {
        // Désactiver toutes les adresses par défaut pour cet utilisateur
        $this->resetDefault($id_utilisateur);
        
        // Définir cette adresse comme défaut
        $query = $this->db->prepare("UPDATE adresses SET is_default = 1 WHERE id = :id");
        $query->execute(array(':id' => $id));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    private function resetDefault($id_utilisateur) {
        $query = $this->db->prepare("UPDATE adresses SET is_default = 0 WHERE id_utilisateur = :id_utilisateur");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    private function setNewDefault($id_utilisateur) {
        // Sélectionner une autre adresse pour cet utilisateur
        $query = $this->db->prepare("SELECT id FROM adresses WHERE id_utilisateur = :id_utilisateur LIMIT 1");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        $adresse = $query->fetch();
        
        // S'il reste une adresse, la définir comme défaut
        if ($adresse) {
            return $this->setDefault($adresse['id'], $id_utilisateur);
        }
        
        return true;
    }
}
?>
