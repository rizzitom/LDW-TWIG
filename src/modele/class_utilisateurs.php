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
        $this->insert = $this->db->prepare("insert into utilisateurs(email, username, password, nom, prenom, idRole) values (:email, :username, :password, :nom, :prenom, :role)");
        $this->connect = $this->db->prepare("select email, idRole, password from utilisateurs where email=:email");
        $this->select = $db->prepare("select u.id, email, idRole, nom, prenom, r.libelle as libellerole from utilisateurs u, role r where u.idRole = r.id order by nom");
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

    public function insert($email, $username, $password, $role, $nom, $prenom) {
        $r = true;
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); 
        $this->insert->execute(array(':email'=>$email, ':username'=>$username, ':password'=>$password, ':role'=>$role,':nom'=>$nom, ':prenom'=>$prenom));
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
    }
?>
