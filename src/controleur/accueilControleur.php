<?php
function accueilControleur($twig, $db) {
    // Récupération des produits en vedette pour la page d'accueil
    $produitModel = new produit($db);
    $categorieModel = new categorie($db);
    
    // Récupérer les produits en vedette (les 4 plus récents par défaut)
    $produits_vedette = $produitModel->getProduitsFeatured(4);
    
    // Récupérer les catégories principales
    $categories = $categorieModel->selectAll();
    
    // Nombre total de produits par catégorie
    $nb_produits_par_categorie = [];
    foreach ($categories as $cat) {
        $nb_produits_par_categorie[$cat['id']] = $produitModel->countByCategorie($cat['id']);
    }
    
    echo $twig->render('index.twig', [
        'name' => 'Le Design du web',
        'produits_vedette' => $produits_vedette,
        'categories' => $categories,
        'nb_produits_par_categorie' => $nb_produits_par_categorie
    ]);
}

function servicesControleur($twig) {
    echo $twig->render('services.twig', ['title' => 'Nos Services']);
}

function contactControleur($twig) {
    echo $twig->render('contact.twig', ['title' => 'Contactez-nous']);
}

function maintenanceControleur($twig) {
    echo $twig->render('maintenance.twig', ['title' => 'Site en maintenance']);
}
