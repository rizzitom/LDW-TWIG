<?php
function panierControleur($twig) {
    $panier = [
        ['nom' => 'Produit 1', 'prix' => 29.99, 'quantite' => 1],
        ['nom' => 'Produit 2', 'prix' => 49.99, 'quantite' => 2],
    ];
    
    echo $twig->render('panier.html.twig', ['panier' => $panier]);
}
?>
