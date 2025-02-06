<?php
include '../config/parametres.php';


function verifierpassword($password) {
    $nb1 = $nb2 = $nb3 = $nb4 = 0;

    for ($i = 0; $i < strlen($password); $i++) {
        $c = $password[$i];

        if (ctype_upper($c)) {
            $nb1++;
        } elseif (ctype_lower($c)) {
            $nb2++;
        } elseif (ctype_digit($c)) {
            $nb3++;
        } elseif ((ord($c) >= 33 && ord($c) <= 46) || ord($c) == 64) {
            $nb4++;
        }
    }

    return strlen($password) >= 12 && $nb1 >= 1 && $nb2 >= 3 && $nb3 >= 4 && $nb4 >= 1;
}

function inscrireControleur($twig, $db) {
    $form = array();
    $validationMessages = [];
    $formValide = true;    

    if (isset($_POST['btInscrire'])) {
        
        $inputEmail = $_POST['inputEmail'];
        $inputUsername = $_POST['inputUsername'];
        $inputPassword = trim($_POST['inputPassword'] ?? '');
        $inputPassword2 = trim($_POST['inputPassword2'] ?? '');
        $nom = $_POST['inputNom'];
        $prenom = $_POST['inputPrenom'];
        $role = ($_POST['role']); 
        

        if (empty($inputEmail)) {
            $validationMessages[] = "L'email est requis.";
            $formValide = false;
        } elseif (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
            $validationMessages[] = "L'email n'est pas valide.";
            $formValide = false;
        }

        if (empty($inputPassword)) {
            $validationMessages[] = "Le mot de passe est requis.";
            $formValide = false;
        } elseif (!verifierpassword($inputPassword)) {
            $validationMessages[] = "Le mot de passe doit contenir au moins 6 caractères.";
            $formValide = false;
        }

        if ($inputPassword !== $inputPassword2) {
            $validationMessages[] = "Les mots de passe ne correspondent pas.";
            $formValide = false;
        }

        if ($formValide) {
            try {
                $utilisateurs = new utilisateurs($db);
                $utilisateurs->insert($inputEmail, $inputUsername, password_hash($inputPassword, PASSWORD_DEFAULT), $role, $nom, $prenom);
                $validationMessages[] = "Inscription réussie.";
            }
            catch(Eception $e){
                $form['valide'] = false;
                $form['message'] = 'Problème d\'insertion dans la table utilisateurs ';
            }
        }

        $form['valide'] = $formValide;
        $form['email'] = $inputEmail;
        $form['username'] = $inputUsername;
        $form['role'] = $role;
        $form['nom'] = $nom;
        $form['prenom'] = $prenom;
        $form['messages'] = $validationMessages;
    }

        echo $twig->render('inscrire.twig', array('form' => $form));
    }
?>
