<?php
include_once __DIR__ . '/../../config/parametres.php';


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

        // Nouveaux champs optionnels
        $adresse = $_POST['inputAdresse'] ?? null;
        $code_postal = $_POST['inputCodePostal'] ?? null;
        $ville = $_POST['inputVille'] ?? null;
        $telephone = $_POST['inputTelephone'] ?? null;

        // Gestion de l'upload de la photo de profil
        $profile_picture = null;
        if (isset($_FILES['inputProfilePicture']) && $_FILES['inputProfilePicture']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2 Mo
            $fileType = mime_content_type($_FILES['inputProfilePicture']['tmp_name']);
            $fileSize = $_FILES['inputProfilePicture']['size'];
            if (!in_array($fileType, $allowedTypes)) {
                $validationMessages[] = "Format de photo de profil non supporté (JPG, PNG, GIF uniquement).";
                $formValide = false;
            } elseif ($fileSize > $maxSize) {
                $validationMessages[] = "La photo de profil dépasse la taille maximale de 2 Mo.";
                $formValide = false;
            } else {
                $ext = pathinfo($_FILES['inputProfilePicture']['name'], PATHINFO_EXTENSION);
                $fileName = 'user_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../../public/images/uploads/profile_pictures/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $destPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['inputProfilePicture']['tmp_name'], $destPath)) {
                    $profile_picture = '/public/images/uploads/profile_pictures/' . $fileName;
                } else {
                    $validationMessages[] = "Erreur lors de l'upload de la photo de profil.";
                    $formValide = false;
                }
            }
        }

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
            $validationMessages[] = "Le mot de passe doit contenir au moins 12 caractères, 1 majuscule, 3 minuscules, 4 chiffres et 1 caractère spécial.";
            $formValide = false;
        }

        if ($inputPassword !== $inputPassword2) {
            $validationMessages[] = "Les mots de passe ne correspondent pas.";
            $formValide = false;
        }

        if ($formValide) {
            try {
                $utilisateurs = new utilisateurs($db);
                $utilisateurs->insert(
                    $inputEmail,
                    $inputUsername,
                    $inputPassword,
                    $role,
                    $nom,
                    $prenom,
                    $adresse,
                    $code_postal,
                    $ville,
                    $telephone,
                    $profile_picture
                );
                
                // Construction du message personnalisé pour la notification de succès
                $successMessage = "";
                if (!empty($prenom)) {
                    $successMessage = "Bienvenue " . htmlspecialchars($prenom) . " ! Votre compte a été créé avec succès.";
                } else {
                    $successMessage = "Votre compte a été créé avec succès.";
                }
                
                // Redirection vers la page de connexion avec notification de succès
                header("Location: index.php?page=connexion&register_success=" . urlencode($successMessage));
                exit;
            }
            catch(Exception $e){
                $form['valide'] = false;
                $form['message'] = 'Erreur lors de la création du compte. Veuillez réessayer.';
                $validationMessages[] = "Une erreur technique est survenue. Veuillez contacter l'administrateur si le problème persiste.";
                // Log de l'erreur pour les administrateurs
                error_log("Erreur d'inscription: " . $e->getMessage());
            }
        }

        $form['valide'] = $formValide;
        $form['email'] = $inputEmail;
        $form['username'] = $inputUsername;
        $form['role'] = $role;
        $form['nom'] = $nom;
        $form['prenom'] = $prenom;
        $form['adresse'] = $adresse;
        $form['code_postal'] = $code_postal;
        $form['ville'] = $ville;
        $form['telephone'] = $telephone;
        $form['profile_picture'] = $profile_picture;
        $form['messages'] = $validationMessages;
    }

        echo $twig->render('inscrire.twig', array('form' => $form));
    }
?>
