<?php

function connexionControleur($twig, $db){
    $form = array();

    if (isset($_POST['btConnecter'])){
      $form['valide'] = true;
      $inputEmail = filter_var($_POST['inputEmail'], FILTER_SANITIZE_EMAIL);
      $inputPassword = trim($_POST['inputPassword']);
      $utilisateurs = new utilisateurs($db);
      $unutilisateurs = $utilisateurs->connect($inputEmail);
      if ($unutilisateurs!=null){
        if(!password_verify($inputPassword,$unutilisateurs['password'])){
            $form['valide'] = false;
            $form['message'] = 'Login ou mot de passe incorrect';
          }
          else{
            session_start();
            $_SESSION['login'] = $inputEmail; 
            $_SESSION['role'] = $unutilisateurs['idRole'];
            header("Location:index.php");
          }
        }
        else{
        $form['valide'] = false;
        $form['message'] = 'Login ou mot de passe incorrect';
        }
    }
    echo $twig->render('connexion.twig', array('form'=>$form));
}

?>
