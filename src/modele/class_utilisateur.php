<?php

class Utilisateur {

    private $db;
    private $insert;
    private $connect;
    private $select;
    private $delete;
    private $update;
    private $duplicate;


    public function __construct($db) {
        $this->db = $db;
        $this->insert = $this->db->prepare("insert into utilisateur(email, mdp, nom, prenom, idRole) values (:email, :mdp, :nom, :prenom, :role)");
        $this->connect = $this->db->prepare("select email, idRole, mdp from utilisateur where email=:email");
        $this->select = $db->prepare("select u.id, email, idRole, nom, prenom, r.libelle as libellerole from utilisateur u, role r where u.idRole = r.id order by nom");
        $this->delete = $this->db->prepare("DELETE FROM utilisateur WHERE id = :id");
        $this->duplicate = $this->db->prepare("SELECT * FROM utilisateur WHERE id = :id");
        $this->update = $this->db->prepare("UPDATE utilisateur 
                                            SET email = :email, nom = :nom, prenom = :prenom, idRole = :role 
                                            WHERE id = :id");

    }

    public function select(){
        $this->select->execute();
        if ($this->select->errorCode()!=0){
        print_r($this->select->errorInfo());
        }
        return $this->select->fetchAll();
       }

    public function insert($email, $mdp, $role, $nom, $prenom) {
        $r = true;
        $hashedPassword = password_hash($mdp, PASSWORD_BCRYPT); 
        $this->insert->execute(array(':email'=>$email, ':mdp'=>$mdp, ':role'=>$role,':nom'=>$nom, ':prenom'=>$prenom));
        if ($this->insert->errorCode()!=0){print_r($this->insert->errorInfo());
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
            $unUtilisateur = $this->connect->execute(array(':email'=>$email));
            if ($this->connect->errorCode()!=0){
              print_r($this->connect->errorInfo());
            }
            return $this->connect->fetch();
           }

           public function update($id, $email, $nom, $prenom, $role) {
            $this->update->execute(array(
                ':id' => $id,
                ':email' => $email,
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
    }
?>
