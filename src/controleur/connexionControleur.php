<?php

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
            $_SESSION['login'] = $inputEmail; 
            $_SESSION['role'] = $unUtilisateur['idRole'];
            header("Location:index.php");
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
