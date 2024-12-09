<?php
require_once '../src/modele/class_utilisateur.php';

function connexionControleur($twig, $db){
    $form = array();

    if (isset($_POST['btConnecter'])){
      $form['valide'] = true;
      $inputEmail = filter_var($_POST['inputEmail'], FILTER_SANITIZE_EMAIL);
      $inputPassword = trim($_POST['inputPassword']);
      $utilisateur = new Utilisateur($db);
      $unUtilisateur = $utilisateur->connect($inputEmail);
      if ($unUtilisateur!=null){
        if(!password_verify($inputPassword,$unUtilisateur['mdp'])){
            $form['valide'] = false;
            $form['message'] = 'Login ou mot de passe incorrect';
          }
          else{
            session_start();
            $_SESSION['email'] = $unUtilisateur['email']; 
            $_SESSION['role'] = $unUtilisateur['role'];
            header("Location:index.php");
            exit;
          }
        }
        else{
        $form['valide'] = false;
        $form['message'] = 'Login ou mot de passe incorrect';
        }
    }
    echo $twig->render('connexion.html.twig', array('form'=>$form));
}

?>
