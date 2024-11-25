<?php
function connexionControleur($twig, $db) {
    $form = array();

    if (isset($_POST['btConnexion'])) {
        $inputEmail = $_POST['inputEmail'] ?? '';
        $inputPassword = $_POST['inputPassword'] ?? '';
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->connect($inputEmail);

        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $stmt->bindParam(':email', $inputEmail);
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($unUtilisateur != null) {
            if (!password_verify($inputPassword, $unUtilisateur['mot_de_passe'])) {
                $form['valide'] = false;
                $form['message'] = 'Login ou mot de passe incorrect';
            } else {

                $_SESSION['login'] = $inputEmail;
                $_SESSION['role'] = $unUtilisateur['idRole'];
                header("Location:index.php");
                exit; 
            }
        } else {
            $form['valide'] = false;
            $form['message'] = 'Login ou mot de passe incorrect';
        }
    }
    
    echo $twig->render('connexion.html.twig', array('form' => $form));
}

?>
