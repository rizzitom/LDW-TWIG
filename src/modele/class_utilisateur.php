<?php

class Utilisateur {
    private $db;
    private $insert;
    private $connect;
    private $select;

    public function __construct($db) {
        $this->db = $db;

        $this->insert = $this->db->prepare("INSERT INTO utilisateur(email, mdp, nom, prenom, idRole) VALUES (:email, :mdp, :nom, :prenom, :role)");

        $this->connect = $this->db->prepare("SELECT email, idRole, mdp FROM utilisateur WHERE email = :email");

        #$this->select = $this->db->prepare("SELECT u.id, email, u.idRole, nom, prenom, r.libelle AS libellerole 
                                             #FROM utilisateur u 
                                             #JOIN role r ON u.idRole = r.id 
                                             #ORDER BY nom");
    }

    public function insert($email, $mdp, $role, $nom, $prenom) {
        $r = true;

        $this->insert->execute(array(':email'=>$email, ':mdp'=>$mdp, ':role'=>$role,':nom'=>$nom, ':prenom'=>$prenom));
         if ($this->insert->errorCode()!=0){
         print_r($this->insert->errorInfo());
         $r=false;
         }
         return $r;
        }

    public function connect($email) {
        $this->connect->execute(array(':email' => $email));
        return $this->connect->fetch(PDO::FETCH_ASSOC);
    }

    public function select() {
        $this->select->execute();
        return $this->select->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
