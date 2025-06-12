<?php

class messages {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getConversationsByUtilisateur($id_utilisateur) {
        $query = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id AND m.lu = 0 AND m.is_client = 0) as unread_count
            FROM conversations c
            WHERE c.id_utilisateur = :id_utilisateur
            ORDER BY c.date_derniere_activite DESC
        ");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return array();
        }
        return $query->fetchAll();
    }
    
    public function getConversationById($id_conversation) {
        // Récupérer les détails de la conversation
        $query = $this->db->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id AND m.lu = 0 AND m.is_client = 0) as unread_count
            FROM conversations c
            WHERE c.id = :id_conversation
        ");
        $query->execute(array(':id_conversation' => $id_conversation));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        $conversation = $query->fetch();
        
        if (!$conversation) {
            return false;
        }
        
        // Récupérer les messages de la conversation
        $query = $this->db->prepare("
            SELECT m.*, u.nom, u.prenom, u.email
            FROM messages m
            LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id
            WHERE m.id_conversation = :id_conversation
            ORDER BY m.date_creation ASC
        ");
        $query->execute(array(':id_conversation' => $id_conversation));
        $conversation['messages'] = $query->fetchAll();
        
        // Récupérer les pièces jointes pour chaque message
        foreach ($conversation['messages'] as $key => $message) {
            $query = $this->db->prepare("
                SELECT *
                FROM pieces_jointes
                WHERE id_message = :id_message
            ");
            $query->execute(array(':id_message' => $message['id']));
            $conversation['messages'][$key]['pieces_jointes'] = $query->fetchAll();
        }
        
        return $conversation;
    }
    
    public function markAsRead($id_conversation, $id_utilisateur) {
        $query = $this->db->prepare("
            UPDATE messages
            SET lu = 1
            WHERE id_conversation = :id_conversation
            AND is_client = 0
        ");
        $query->execute(array(':id_conversation' => $id_conversation));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    /**
     * Récupère une conversation associée à un devis
     * 
     * @param int $id_devis ID du devis
     * @return array|false Conversation ou false si non trouvée
     */
    public function getConversationByDevis($id_devis) {
        // Récupérer les détails de la conversation
        $query = $this->db->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id AND m.lu = 0 AND m.is_client = 0) as unread_count
            FROM conversations c
            WHERE c.type = 'devis' AND c.reference_devis = :id_devis
        ");
        $query->execute(array(':id_devis' => $id_devis));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        $conversation = $query->fetch();
        
        if (!$conversation) {
            return false;
        }
        
        // Récupérer les messages de la conversation
        $query = $this->db->prepare("
            SELECT m.*, u.nom, u.prenom, u.email
            FROM messages m
            LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id
            WHERE m.id_conversation = :id_conversation
            ORDER BY m.date_creation ASC
        ");
        $query->execute(array(':id_conversation' => $conversation['id']));
        $conversation['messages'] = $query->fetchAll();
        
        // Récupérer les pièces jointes pour chaque message
        foreach ($conversation['messages'] as $key => $message) {
            $query = $this->db->prepare("
                SELECT *
                FROM pieces_jointes
                WHERE id_message = :id_message
            ");
            $query->execute(array(':id_message' => $message['id']));
            $conversation['messages'][$key]['pieces_jointes'] = $query->fetchAll();
        }
        
        return $conversation;
    }

    public function createConversation($id_utilisateur, $sujet, $type, $reference = null) {
        $query = $this->db->prepare("
            INSERT INTO conversations (
                id_utilisateur, sujet, type, reference_commande, reference_devis, statut, date_creation, date_derniere_activite
            ) VALUES (
                :id_utilisateur, :sujet, :type, :reference_commande, :reference_devis, 'ouvert', NOW(), NOW()
            )
        ");
        
        $reference_commande = null;
        $reference_devis = null;
        
        if ($type == 'commande') {
            $reference_commande = $reference;
        } elseif ($type == 'devis') {
            $reference_devis = $reference;
        }
        
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':sujet' => $sujet,
            ':type' => $type,
            ':reference_commande' => $reference_commande,
            ':reference_devis' => $reference_devis
        ));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return $this->db->lastInsertId();
    }
    
    public function addMessage($id_conversation, $id_utilisateur, $message, $is_client = false) {
        // Ajouter le message
        $query = $this->db->prepare("
            INSERT INTO messages (
                id_conversation, id_utilisateur, contenu, is_client, lu, date_creation
            ) VALUES (
                :id_conversation, :id_utilisateur, :contenu, :is_client, 0, NOW()
            )
        ");
        $query->execute(array(
            ':id_conversation' => $id_conversation,
            ':id_utilisateur' => $id_utilisateur,
            ':contenu' => $message,
            ':is_client' => $is_client ? 1 : 0
        ));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        
        // Mettre à jour la date de dernière activité de la conversation
        $this->updateLastActivityDate($id_conversation);
        
        return $this->db->lastInsertId();
    }
    
    public function getLastMessageId($id_conversation) {
        $query = $this->db->prepare("
            SELECT id
            FROM messages
            WHERE id_conversation = :id_conversation
            ORDER BY date_creation DESC
            LIMIT 1
        ");
        $query->execute(array(':id_conversation' => $id_conversation));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        $result = $query->fetch();
        return $result ? $result['id'] : false;
    }
    
    public function addAttachment($id_conversation, $id_message, $filename, $filepath) {
        $query = $this->db->prepare("
            INSERT INTO pieces_jointes (
                id_message, id_conversation, nom_fichier, chemin_fichier, date_creation
            ) VALUES (
                :id_message, :id_conversation, :nom_fichier, :chemin_fichier, NOW()
            )
        ");
        $query->execute(array(
            ':id_message' => $id_message,
            ':id_conversation' => $id_conversation,
            ':nom_fichier' => $filename,
            ':chemin_fichier' => $filepath
        ));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return $this->db->lastInsertId();
    }
    
    public function updateConversationStatus($id_conversation, $statut) {
        $query = $this->db->prepare("
            UPDATE conversations
            SET statut = :statut,
                date_derniere_activite = NOW()
            WHERE id = :id_conversation
        ");
        $query->execute(array(
            ':id_conversation' => $id_conversation,
            ':statut' => $statut
        ));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    private function updateLastActivityDate($id_conversation) {
        $query = $this->db->prepare("
            UPDATE conversations
            SET date_derniere_activite = NOW()
            WHERE id = :id_conversation
        ");
        $query->execute(array(':id_conversation' => $id_conversation));
        return true;
    }
}
?>
