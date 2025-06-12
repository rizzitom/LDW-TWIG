<?php

class securite {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getLoginHistory($id_utilisateur = null) {
        $sql = "
            SELECT *
            FROM historique_connexions
            ";
        
        $params = array();
        
        if ($id_utilisateur !== null) {
            $sql .= "WHERE id_utilisateur = :id_utilisateur ";
            $params[':id_utilisateur'] = $id_utilisateur;
        }
        
        $sql .= "ORDER BY date_connexion DESC LIMIT 10";
        
        $query = $this->db->prepare($sql);
        $query->execute($params);
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return array();
        }
        return $query->fetchAll();
    }
    
    public function logLoginAttempt($id_utilisateur, $ip, $user_agent, $success) {
        $query = $this->db->prepare("
            INSERT INTO historique_connexions (
                id_utilisateur, ip, user_agent, succes, date_connexion
            ) VALUES (
                :id_utilisateur, :ip, :user_agent, :succes, NOW()
            )
        ");
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':ip' => $ip,
            ':user_agent' => $user_agent,
            ':succes' => $success ? 1 : 0
        ));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    public function logPasswordChange($id_utilisateur) {
        $query = $this->db->prepare("
            INSERT INTO historique_actions (
                id_utilisateur, type_action, details, date_action
            ) VALUES (
                :id_utilisateur, 'password_change', 'Modification du mot de passe', NOW()
            )
        ");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        return true;
    }
    
    public function generate2FASetupData($id_utilisateur) {
        // Générer un secret pour la 2FA
        $secret = $this->generateSecretKey();
        
        // Sauvegarder le secret en base de données
        $query = $this->db->prepare("
            UPDATE utilisateurs
            SET two_factor_secret = :secret
            WHERE id = :id_utilisateur
        ");
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':secret' => $secret
        ));
        
        // Récupérer les informations de l'utilisateur
        $query = $this->db->prepare("SELECT email FROM utilisateurs WHERE id = :id_utilisateur");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        $user = $query->fetch();
        
        // Générer l'URL pour le QR code
        $qr_code_url = $this->getQRCodeUrl($user['email'], $secret);
        
        // Générer un code de secours
        $backup_code = $this->generateBackupCode();
        
        // Sauvegarder le code de secours en base de données
        $query = $this->db->prepare("
            UPDATE utilisateurs
            SET backup_code = :backup_code
            WHERE id = :id_utilisateur
        ");
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':backup_code' => password_hash($backup_code, PASSWORD_BCRYPT)
        ));
        
        return array(
            'qr_code_url' => $qr_code_url,
            'secret' => $secret,
            'backup_code' => $backup_code
        );
    }
    
    public function validate2FACode($id_utilisateur, $code) {
        // Récupérer le secret de l'utilisateur
        $query = $this->db->prepare("SELECT two_factor_secret FROM utilisateurs WHERE id = :id_utilisateur");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        $user = $query->fetch();
        
        if (!$user || !$user['two_factor_secret']) {
            return false;
        }
        
        // Validation du code TOTP
        return $this->verifyCode($user['two_factor_secret'], $code);
    }
    
    public function logoutAllDevices($id_utilisateur) {
        // Supprimer tous les tokens de session sauf celui actuel
        $query = $this->db->prepare("
            DELETE FROM sessions
            WHERE id_utilisateur = :id_utilisateur
            AND session_token != :session_token
        ");
        $query->execute(array(
            ':id_utilisateur' => $id_utilisateur,
            ':session_token' => isset($_SESSION['session_token']) ? $_SESSION['session_token'] : ''
        ));
        
        if ($query->errorCode() != 0) {
            print_r($query->errorInfo());
            return false;
        }
        
        // Loguer l'action
        $query = $this->db->prepare("
            INSERT INTO historique_actions (
                id_utilisateur, type_action, details, date_action
            ) VALUES (
                :id_utilisateur, 'logout_all', 'Déconnexion de tous les appareils', NOW()
            )
        ");
        $query->execute(array(':id_utilisateur' => $id_utilisateur));
        
        return true;
    }
    
    // Méthodes utilitaires privées
    
    private function generateSecretKey($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    private function getQRCodeUrl($email, $secret) {
        $company = "NetTech";
        $qr_url = "otpauth://totp/" . urlencode($company) . ":" . urlencode($email) . "?secret=" . $secret . "&issuer=" . urlencode($company);
        return $qr_url;
    }
    
    private function generateBackupCode($length = 8) {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }
    
    private function verifyCode($secret, $code) {
        // Cette fonction simule la vérification d'un code TOTP
        // Dans une implémentation réelle, vous utiliseriez une bibliothèque comme OTPHP
        
        // Pour le moment, accepter tous les codes pour le test (ne pas faire en production!)
        return true;
    }
}
?>
