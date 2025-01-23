<?php
function utilisateurControleur($twig, $db) {
    $utilisateur = new Utilisateur($db);
    $liste = $utilisateur->select();
    $utilisateurData = null;

    if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
            $id = $_GET['id'];
            $utilisateur->delete($id);
            header("Location: index.php?page=utilisateur");
            exit;
        }
    
    if (isset($_POST['btModifierUtilisateur'])) {
            $id = $_POST['idUtilisateur'];
            $email = $_POST['inputEmail'];
            $nom = $_POST['inputNom'];
            $prenom = $_POST['inputPrenom'];
            $role = $_POST['inputRole'];
    
            $utilisateur->update($id, $email, $nom, $prenom, $role);
            header("Location: index.php?page=utilisateur");
            exit;
        }
    
        if (isset($_GET['action']) && $_GET['action'] == 'dupliquer' && isset($_GET['id'])) {
            $id = $_GET['id'];
            if ($utilisateur->duplicate($id)) {
                $_SESSION['message'] = "Utilisateur dupliqué avec succès !";
            } else {
                $_SESSION['message'] = "Erreur lors de la duplication de l'utilisateur.";
            }
            header("Location: index.php?page=utilisateur");
            exit;
        }    

    if (isset($_POST['btAjouterUtilisateur'])) {

        $email = $_POST['inputEmail'];
        $nom = $_POST['inputNom'];
        $prenom = $_POST['inputPrenom'];
        $role = $_POST['inputRole'];  
        $password = $_POST['inputPassword'];
        $passwordConfirm = $_POST['inputPasswordConfirm'];

        if ($password !== $passwordConfirm) {
            echo "<p>Les mots de passe ne correspondent pas.</p>";
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $emailExist = $stmt->fetchColumn();

            if ($emailExist) {
                echo "<p>L'email existe déjà dans la base de données.</p>";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $db->prepare("INSERT INTO utilisateur (email, mdp, nom, prenom, idRole) VALUES (:email, :mdp, :nom, :prenom, :idRole)");
                $stmt->execute([
                    'email' => $email,
                    'mdp' => $hashedPassword,  
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'idRole' => $role
                ]);

                header("Location: index.php?page=utilisateur");
                exit;
            }
        }
    }

    echo $twig->render('utilisateur.twig', array('form' => $_POST, 'liste' => $liste));
}
?>
