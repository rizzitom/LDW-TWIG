<?php

function utilisateursModifControleur($twig, $db) {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    $id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
    $message = "";
    $erreur = "";
    
    // Récupérer les informations de l'utilisateur
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$utilisateur) {
        header('Location: index.php?page=connexion');
        exit;
    }
    
    // Traitement du formulaire de modification
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
        $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
        $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
        $code_postal = isset($_POST['code_postal']) ? trim($_POST['code_postal']) : '';
        $ville = isset($_POST['ville']) ? trim($_POST['ville']) : '';
        $nouveau_mot_de_passe = isset($_POST['nouveau_mot_de_passe']) ? trim($_POST['nouveau_mot_de_passe']) : '';
        $confirmer_mot_de_passe = isset($_POST['confirmer_mot_de_passe']) ? trim($_POST['confirmer_mot_de_passe']) : '';
        $ancien_mot_de_passe = isset($_POST['ancien_mot_de_passe']) ? trim($_POST['ancien_mot_de_passe']) : '';
        
        // Validation des champs
        $erreurs = [];
        
        if (empty($nom)) {
            $erreurs[] = "Le nom est obligatoire.";
        }
        
        if (empty($prenom)) {
            $erreurs[] = "Le prénom est obligatoire.";
        }
        
        if (empty($email)) {
            $erreurs[] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "Le format de l'email est invalide.";
        } elseif ($email !== $utilisateur['email']) {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id != :id");
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $erreurs[] = "Cet email est déjà utilisé par un autre compte.";
            }
        }
        
        // Validation du mot de passe si l'utilisateur souhaite le changer
        if (!empty($nouveau_mot_de_passe)) {
            if (empty($ancien_mot_de_passe)) {
                $erreurs[] = "Veuillez saisir votre ancien mot de passe pour confirmer le changement.";
            } elseif (!password_verify($ancien_mot_de_passe, $utilisateur['mot_de_passe'])) {
                $erreurs[] = "L'ancien mot de passe est incorrect.";
            }
            
            if (strlen($nouveau_mot_de_passe) < 8) {
                $erreurs[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            }
            
            if ($nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
                $erreurs[] = "Les nouveaux mots de passe ne correspondent pas.";
            }
        }
        
        // Si aucune erreur, mettre à jour les informations
        if (empty($erreurs)) {
            try {
                // Préparer la requête SQL en fonction des champs à mettre à jour
                $sql = "UPDATE utilisateurs SET 
                        nom = :nom, 
                        prenom = :prenom, 
                        email = :email, 
                        telephone = :telephone, 
                        adresse = :adresse, 
                        code_postal = :code_postal, 
                        ville = :ville";
                
                // Ajouter la mise à jour du mot de passe si nécessaire
                if (!empty($nouveau_mot_de_passe)) {
                    $sql .= ", mot_de_passe = :mot_de_passe";
                }
                
                $sql .= " WHERE id = :id";
                
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':nom', $nom);
                $stmt->bindValue(':prenom', $prenom);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':telephone', $telephone);
                $stmt->bindValue(':adresse', $adresse);
                $stmt->bindValue(':code_postal', $code_postal);
                $stmt->bindValue(':ville', $ville);
                
                if (!empty($nouveau_mot_de_passe)) {
                    $hashed_password = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                    $stmt->bindValue(':mot_de_passe', $hashed_password);
                }
                
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Mettre à jour les informations de session
                $_SESSION['user']['nom'] = $nom;
                $_SESSION['user']['prenom'] = $prenom;
                $_SESSION['user']['email'] = $email;
                
                $message = "Vos informations ont été mises à jour avec succès.";
                
                // Récupérer les informations mises à jour
                $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = :id");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $erreur = "Une erreur est survenue lors de la mise à jour de vos informations.";
            }
        } else {
            $erreur = implode("<br>", $erreurs);
        }
    }
    
    echo $twig->render('utilisateurs.twig', [
        'utilisateur' => $utilisateur,
        'message' => $message,
        'erreur' => $erreur
    ]);
}
