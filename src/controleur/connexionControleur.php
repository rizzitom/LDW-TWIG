<?php
function connexionControleur($twig, $db){
    $form = array();
    if (isset($_POST['btConnecter'])){
        $form['valide'] = true;
        $inputEmail = $_POST['inputEmail'];
        $inputPassword = $_POST['inputPassword'];
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->connect($inputEmail);
        if ($unUtilisateur!=null){
            if(!password_verify($inputPassword,$unUtilisateur['mdp'])){
                $form['valide'] = false;
                $form['message'] = 'Login ou mot de passe incorrect';
            }
            
            else{
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
