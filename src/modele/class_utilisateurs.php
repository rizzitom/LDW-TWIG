<?php

class utilisateurs {

    private $db;
    private $insert;
    private $connect;
    private $select;
    private $delete;
    private $update;
    private $duplicate;


    public function __construct($db) {
        $this->db = $db;
        $this->insert = $this->db->prepare("insert into utilisateurs(email, username, password, nom, prenom, idRole, adresse, code_postal, ville, telephone, profile_picture) values (:email, :username, :password, :nom, :prenom, :role, :adresse, :code_postal, :ville, :telephone, :profile_picture)");
        $this->connect = $this->db->prepare("select id, email, idRole, password, nom, prenom, username, profile_picture from utilisateurs where email=:email");
        $this->select = $db->prepare("select u.id, email, idRole, nom, prenom, r.libelle as libellerole from utilisateurs u, roles r where u.idRole = r.id order by nom");
        $this->delete = $this->db->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $this->duplicate = $this->db->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $this->update = $this->db->prepare("UPDATE utilisateurs 
                                            SET email = :email, username = :username, nom = :nom, prenom = :prenom, idRole = :role 
                                            WHERE id = :id");

    }

    public function select(){
        $this->select->execute();
        if ($this->select->errorCode()!=0){
        print_r($this->select->errorInfo());
        }
        return $this->select->fetchAll();
       }

    public function insert($email, $username, $password, $role, $nom, $prenom, $adresse = null, $code_postal = null, $ville = null, $telephone = null, $profile_picture = null) {
        $r = true;
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); 
        $this->insert->execute(array(
            ':email' => $email,
            ':username' => $username,
            ':password' => $hashedPassword,
            ':role' => $role,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':adresse' => $adresse,
            ':code_postal' => $code_postal,
            ':ville' => $ville,
            ':telephone' => $telephone,
            ':profile_picture' => $profile_picture
        ));
        if ($this->insert->errorCode()!=0){
            print_r($this->insert->errorInfo());
            $r=false;
        }
        return $r;
    }

        public function delete($id) {
            $this->delete->execute(array(':id' => $id));
            if ($this->delete->errorCode() != 0) {
                print_r($this->delete->errorInfo());
                return false;
            }
            return true;
        }

        public function connect($email){
            $unutilisateurs = $this->connect->execute(array(':email'=>$email));
            if ($this->connect->errorCode()!=0){
              print_r($this->connect->errorInfo());
            }
            return $this->connect->fetch();
           }

           public function update($id, $email, $username, $nom, $prenom, $role) {
            $this->update->execute(array(
                ':id' => $id,
                ':email' => $email,
                ':username' => $username,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':role' => $role
            ));
            if ($this->update->errorCode() != 0) {
                print_r($this->update->errorInfo());
                return false;
            }
            return true;
        }
        
        public function duplicate($id) {
            $this->duplicate->execute(array(':id' => $id));
            $userData = $this->duplicate->fetch();
    
            if ($userData) {

                $newEmail = $userData['email'] . "-copy";
                $newPassword = "defaultPassword"; 
                $this->insert($newEmail, $newPassword, $userData['idRole'], $userData['nom'], $userData['prenom']);
                return true;
            }
            return false; 
        }
        
        public function getById($id) {
            $query = $this->db->prepare("SELECT u.*, r.libelle as libellerole 
                                         FROM utilisateurs u 
                                         LEFT JOIN roles r ON u.idRole = r.id 
                                         WHERE u.id = :id");
            $query->execute(array(':id' => $id));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return $query->fetch();
        }
        
        public function updatePreferences($id, $newsletter, $promo_email, $order_updates, $sms_alerts) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         newsletter = :newsletter,
                                         promo_email = :promo_email,
                                         order_updates = :order_updates,
                                         sms_alerts = :sms_alerts
                                         WHERE id = :id");
            $query->execute(array(
                ':id' => $id,
                ':newsletter' => $newsletter,
                ':promo_email' => $promo_email,
                ':order_updates' => $order_updates,
                ':sms_alerts' => $sms_alerts
            ));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function updateInfos($id, $nom, $prenom, $email, $telephone) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         nom = :nom,
                                         prenom = :prenom,
                                         email = :email,
                                         telephone = :telephone
                                         WHERE id = :id");
            $query->execute(array(
                ':id' => $id,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => $telephone
            ));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function updatePassword($id, $password) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         password = :password
                                         WHERE id = :id");
            $query->execute(array(
                ':id' => $id,
                ':password' => $password
            ));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function updateSecurityOptions($id, $options) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         notif_new_connexion = :notif_new_connexion,
                                         notif_password_change = :notif_password_change,
                                         notif_failed_attempts = :notif_failed_attempts,
                                         remember_devices = :remember_devices,
                                         extended_session = :extended_session,
                                         confirm_order_by_email = :confirm_order_by_email
                                         WHERE id = :id");
            $query->execute(array(
                ':id' => $id,
                ':notif_new_connexion' => $options['notif_new_connexion'],
                ':notif_password_change' => $options['notif_password_change'],
                ':notif_failed_attempts' => $options['notif_failed_attempts'],
                ':remember_devices' => $options['remember_devices'],
                ':extended_session' => $options['extended_session'],
                ':confirm_order_by_email' => $options['confirm_order_by_email']
            ));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function enable2FA($id) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         two_factor_enabled = 1
                                         WHERE id = :id");
            $query->execute(array(':id' => $id));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function disable2FA($id) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         two_factor_enabled = 0,
                                         two_factor_secret = NULL
                                         WHERE id = :id");
            $query->execute(array(':id' => $id));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function updateSessionToken($id, $token) {
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         session_token = :token
                                         WHERE id = :id");
            $query->execute(array(
                ':id' => $id,
                ':token' => $token
            ));
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            return true;
        }
        
        public function deleteProfilePicture($id) {
            // First, get the current profile picture path
            $stmt = $this->db->prepare("SELECT profile_picture FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $oldPicture = $stmt->fetchColumn();
            
            // If there's a profile picture and it's stored locally, delete the file
            if ($oldPicture && strpos($oldPicture, '/public/images/uploads/profile_pictures/') !== false) {
                // Try different ways to get the full path
                $oldPicturePath = $_SERVER['DOCUMENT_ROOT'] . $oldPicture;
                if (file_exists($oldPicturePath)) {
                    @unlink($oldPicturePath);
                }
                
                // Try alternative path with dirname(__FILE__)
                $rootPath = dirname(dirname(dirname(__FILE__))); // Go up 3 levels from class_utilisateurs.php
                $altPath = $rootPath . $oldPicture;
                if (file_exists($altPath)) {
                    @unlink($altPath);
                }
                
                // Log deletion attempt for debugging
                error_log("Attempted to delete profile picture from utilisateurs class: $oldPicture");
            }
            
            // Then update the database to set profile_picture to NULL
            $query = $this->db->prepare("UPDATE utilisateurs SET 
                                         profile_picture = NULL
                                         WHERE id = :id");
            $query->execute(array(':id' => $id));
            
            if ($query->errorCode() != 0) {
                print_r($query->errorInfo());
                return false;
            }
            
            return true;
        }
    }
?>
