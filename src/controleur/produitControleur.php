<?php
function produitControleur($twig, $db) {

    $types = $db->query("SELECT * FROM type ORDER BY nom")->fetchAll();

    $produits = $db->query("SELECT p.*, t.nom AS type_nom FROM produit p 
    JOIN type t ON p.idType = t.id ORDER BY p.designation")->fetchAll();


    if (isset($_POST['btAjouterProduit'])) {
        $designation = $_POST['inputDesignation'];
        $description = $_POST['inputDescription'];
        $prix = $_POST['inputPrix'];
        $idType = $_POST['selectType'];

        $stmt = $db->prepare("INSERT INTO produit (designation, description, prix, idType) VALUES (:designation, :description, :prix, :idType)");
        $stmt->execute([
            'designation' => $designation,
            'description' => $description,
            'prix' => $prix,
            'idType' => $idType,
        ]);
        header("Location: index.php?page=produit");
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'modifier' && isset($_GET['id'])) {
        $id = $_GET['id'];
    
        $stmt = $db->prepare("SELECT * FROM produit WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $produitAModifier = $stmt->fetch();
    
        if ($produitAModifier) {
            if (isset($_POST['btModifierProduit'])) {
                $designation = $_POST['inputDesignation'];
                $description = $_POST['inputDescription'];
                $prix = $_POST['inputPrix'];
                $idType = $_POST['selectType'];
    
                $stmt = $db->prepare("UPDATE produit SET designation = :designation, description = :description, prix = :prix, idType = :idType WHERE id = :id");
                $stmt->execute([
                    'designation' => $designation,
                    'description' => $description,
                    'prix' => $prix,
                    'idType' => $idType,
                    'id' => $id,
                ]);
    
                header("Location: index.php?page=produit");
                exit;
            }
    
            echo $twig->render('produit.html.twig', [
                'types' => $types,
                'produits' => $produits,
                'produitAModifier' => $produitAModifier
            ]);
        } else {
            header("Location: index.php?page=produit");
            exit;
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM produit WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: index.php?page=produit");
        exit;
    }

    // Dupliquer un produit
    if (isset($_GET['action']) && $_GET['action'] === 'dupliquer' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("INSERT INTO produit (designation, description, prix, idType) 
                              SELECT designation, description, prix, idType FROM produit WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: index.php?page=produit");
        exit;
    }

    echo $twig->render('produit.html.twig', array('types' => $types, 'produits' => $produits));
}
?>
