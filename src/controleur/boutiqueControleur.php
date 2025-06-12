<?php

function boutiqueControleur($twig, $db) {
    // Initialisation des variables
    $categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : null;
    $recherche = isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : null;
    $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
    $limite = 12; // Nombre de produits par page
    
    // Instanciation des classes
    $categorieModel = new Categorie($db);
    $produitModel = new Produit($db);
    
    // Récupération des catégories
    $categories = $categorieModel->getCategoryTree();
    
    // Récupération des produits
    if ($categorie_id) {
        // Produits d'une catégorie spécifique
        $produits = $produitModel->selectByCategory($categorie_id);
        $categorie_active = $categorieModel->selectById($categorie_id);
    } elseif ($recherche) {
        // Recherche de produits
        $stmt = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                            FROM produits p 
                            JOIN categories c ON p.id_categorie = c.id 
                            WHERE p.est_actif = 1 
                            AND (p.nom LIKE :recherche OR p.description LIKE :recherche OR p.description_courte LIKE :recherche) 
                            ORDER BY p.created_at DESC");
        $stmt->execute([':recherche' => "%$recherche%"]);
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categorie_active = null;
    } else {
        // Tous les produits
        $produits = $produitModel->selectAll();
        $categorie_active = null;
    }
    
    // Pagination
    $nombre_total = count($produits);
    $nombre_pages = ceil($nombre_total / $limite);
    $page = max(1, min($page, $nombre_pages));
    $debut = ($page - 1) * $limite;
    $produits_page = array_slice($produits, $debut, $limite);
    
    // Récupération des produits en vedette pour le carrousel
    $produits_vedette = $produitModel->selectFeatured(6);
    
    // Rendu de la vue
    echo $twig->render('boutique.twig', [
        'categories' => $categories,
        'produits' => $produits_page,
        'produits_vedette' => $produits_vedette,
        'categorie_active' => $categorie_active,
        'recherche' => $recherche,
        'pagination' => [
            'page_courante' => $page,
            'nombre_pages' => $nombre_pages,
            'nombre_total' => $nombre_total
        ]
    ]);
}
