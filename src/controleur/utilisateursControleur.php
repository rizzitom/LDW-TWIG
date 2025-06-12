<?php
function utilisateursControleur($twig, $db) {
    // Vérifier si l'utilisateur est administrateur
    require_once __DIR__ . '/../middleware/security.php';
    
    // Vérifie les permissions d'administrateur
    if (!verifyAdminAccess()) {
        // Si l'accès est refusé, la fonction verifyAdminAccess redirige déjà l'utilisateur
        exit;
    }
    
    $utilisateurs = new utilisateurs($db);
    $liste = $utilisateurs->select();
    $utilisateursData = null;

    if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
            $id = $_GET['id'];
            $utilisateurs->delete($id);
            header("Location: index.php?page=utilisateurs");
            exit;
        }
    
    if (isset($_POST['btModifierutilisateurs'])) {
            $id = $_POST['idutilisateurs'];
            $email = $_POST['inputEmail'];
            $username = $_POST['inputUsername'];
            $nom = $_POST['inputNom'];
            $prenom = $_POST['inputPrenom'];
            $role = $_POST['inputRole'];
    
            $utilisateurs->update($id, $email, $username, $nom, $prenom, $role);
            header("Location: index.php?page=utilisateurs");
            exit;
        }
    
        if (isset($_GET['action']) && $_GET['action'] == 'dupliquer' && isset($_GET['id'])) {
            $id = $_GET['id'];
            if ($utilisateurs->duplicate($id)) {
                $_SESSION['message'] = "utilisateurs dupliqué avec succès !";
            } else {
                $_SESSION['message'] = "Erreur lors de la duplication de l'utilisateurs.";
            }
            header("Location: index.php?page=utilisateurs");
            exit;
        }    

    if (isset($_POST['btAjouterutilisateurs'])) {

        $email = $_POST['inputEmail'];
        $username = $_POST['inputUsername'];
        $nom = $_POST['inputNom'];
        $prenom = $_POST['inputPrenom'];
        $role = $_POST['inputRole'];  
        $password = $_POST['inputPassword'];
        $passwordConfirm = $_POST['inputPasswordConfirm'];

        if ($password !== $passwordConfirm) {
            echo "<p>Les mots de passe ne correspondent pas.</p>";
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $emailExist = $stmt->fetchColumn();

            if ($emailExist) {
                echo "<p>L'email existe déjà dans la base de données.</p>";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $db->prepare("INSERT INTO utilisateurs (email, username, password, nom, prenom, idRole) VALUES (:email, :username, :password, :nom, :prenom, :idRole)");
                $stmt->execute([
                    'email' => $email,
                    'username' => $username,
                    'password' => $hashedPassword,  
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'idRole' => $role
                ]);

                header("Location: index.php?page=utilisateurs");
                exit;
            }
        }
    }

    echo $twig->render('utilisateurs.twig', array('form' => $_POST, 'liste' => $liste));
}
?>
