<?php
include '../config/parametres.php';


function verifierMdp($mdp) {
    $nb1 = $nb2 = $nb3 = $nb4 = 0;

    for ($i = 0; $i < strlen($mdp); $i++) {
        $c = $mdp[$i];

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

    return strlen($mdp) >= 12 && $nb1 >= 1 && $nb2 >= 3 && $nb3 >= 4 && $nb4 >= 1;
}

function inscrireControleur($twig, $db) {
    $form = array();
    $validationMessages = [];
    $formValide = true;    

    if (isset($_POST['btInscrire'])) {
        
        $inputEmail = $_POST['inputEmail'];
        $inputPassword = trim($_POST['inputPassword'] ?? '');
        $inputPassword2 = trim($_POST['inputPassword2'] ?? '');
        $nom = $_POST['inputNom'];
        $prenom = $_POST['inputprenom'];
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
        } elseif (!verifierMdp($inputPassword)) {
            $validationMessages[] = "Le mot de passe doit contenir au moins 6 caractères.";
            $formValide = false;
        }

        if ($inputPassword !== $inputPassword2) {
            $validationMessages[] = "Les mots de passe ne correspondent pas.";
            $formValide = false;
        }

        if ($formValide) {
            try {
                $utilisateur = new Utilisateur($db);
                $utilisateur->insert($inputEmail, password_hash($inputPassword, PASSWORD_DEFAULT), $role, $nom, $prenom);
                $validationMessages[] = "Inscription réussie.";
            }
            catch(Eception $e){
                $form['valide'] = false;
                $form['message'] = 'Problème d\'insertion dans la table utilisateur ';
            }
        }

        $form['valide'] = $formValide;
        $form['email'] = $inputEmail;
        $form['role'] = $role;
        $form['nom'] = $nom;
        $form['prenom'] = $prenom;
        $form['messages'] = $validationMessages;
    }

        echo $twig->render('inscrire.html.twig', array('form' => $form));
    }
?>
