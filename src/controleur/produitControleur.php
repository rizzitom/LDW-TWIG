<?php
function produitControleur($twig, $db) {
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Récupérer les produits
    $produits = $db->query("SELECT * FROM produits ORDER BY nom")->fetchAll();

 // Ajouter un produit
 if (isset($_POST['btAjouterProduit'])) {
    $nom = $_POST['inputNom'];
    $description = $_POST['inputDescription'];
    $prix = $_POST['inputPrix'];
    $stock = $_POST['inputStock'];
    $categorie = $_POST['inputCategorie'];
    $marque = $_POST['inputMarque'];
    $modele = $_POST['inputModele'];
    $est_actif = isset($_POST['inputEstActif']) ? 1 : 0;

    // Gestion de l'upload d'image
    if (!empty($_FILES['inputImage']['name'])) {
        $uploadDir = '../public/images/uploads/';
        $fileName = time() . '_' . basename($_FILES['inputImage']['name']); 
        $uploadFile = $uploadDir . $fileName;
    
        if (move_uploaded_file($_FILES['inputImage']['tmp_name'], $uploadFile)) {
            $imageUrl = 'images/uploads/' . $fileName; 
        } 
        else {
            $imageUrl = null;
        }
    } else {
        $imageUrl = null;
    }

    $stmt = $db->prepare("INSERT INTO produits (nom, description, prix, stock, categorie, marque, modele, image_url, est_actif) 
                          VALUES (:nom, :description, :prix, :stock, :categorie, :marque, :modele, :image_url, :est_actif)");
    $stmt->execute([
        'nom' => $nom,
        'description' => $description,
        'prix' => $prix,
        'stock' => $stock,
        'categorie' => $categorie,
        'marque' => $marque,
        'modele' => $modele,
        'image_url' => $image_url,
        'est_actif' => $est_actif,
    ]);
        header("Location: index.php?page=produit");
        exit;
    }

    // Modifier un produit
    if (isset($_GET['action']) && $_GET['action'] === 'modifier' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("SELECT * FROM produits WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $produitAModifier = $stmt->fetch();
    
        if ($produitAModifier) {
            if (isset($_POST['btModifierProduit'])) {
                $nom = $_POST['inputNom'];
                $description = $_POST['inputDescription'];
                $prix = $_POST['inputPrix'];
                $stock = $_POST['inputStock'];
                $categorie = $_POST['inputCategorie'];
                $marque = $_POST['inputMarque'];
                $modele = $_POST['inputModele'];
                $est_actif = isset($_POST['inputEstActif']) ? 1 : 0;
    
                // Gestion de l'upload d'image
                if (!empty($_FILES['inputImage']['name'])) {
                    $uploadDir = 'public/images/uploads/';
                    $fileName = time() . '_' . basename($_FILES['inputImage']['name']);
                    $uploadFile = $uploadDir . $fileName;
        
                    if (move_uploaded_file($_FILES['inputImage']['tmp_name'], $uploadFile)) {
                        $imageUrl = 'images/uploads/' . $fileName;
                    } else {
                        $imageUrl = $produitAModifier['image_url']; 
                    }
                } else {
                    $imageUrl = $produitAModifier['image_url'];
                }
    
                $stmt = $db->prepare("UPDATE produits SET nom = :nom, description = :description, prix = :prix, stock = :stock, 
                                      categorie = :categorie, marque = :marque, modele = :modele, image_url = ? WHERE id = ?, est_actif = :est_actif 
                                      WHERE id = :id");
                $result = $stmt->execute([$image_url, $id]);

                $stmt->execute([
                    'nom' => $nom,
                    'description' => $description,
                    'prix' => $prix,
                    'stock' => $stock,
                    'categorie' => $categorie,
                    'marque' => $marque,
                    'modele' => $modele,
                    'image_url' => $imageUrl,
                    'est_actif' => $est_actif,
                    'id' => $id,
                ]);
                header("Location: index.php?page=produit");
                exit;
            }
    
            echo $twig->render('/admin/produit_modifier.twig', [
                'produit' => $produitAModifier
            ]);
        } else {
            header("Location: index.php?page=produit");
            exit;
        }
    }
    

    // Supprimer un produit
    if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM produits WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: index.php?page=produit");
        exit;
    }

    // Dupliquer un produit
    if (isset($_GET['action']) && $_GET['action'] === 'dupliquer' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("INSERT INTO produits (nom, description, prix, stock, categorie, marque, modele, image_url, est_actif) 
                              SELECT nom, description, prix, stock, categorie, marque, modele, image_url, est_actif FROM produits WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: index.php?page=produit");
        exit;
    }

    echo $twig->render('produit.twig', ['produits' => $produits]);
}
?>
