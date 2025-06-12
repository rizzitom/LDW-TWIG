<?php

function commandesControleur($twig, $db) {
    // Rediriger vers la fonction commandes() dans clientControleur
    return commandes($twig, $db);
}

function commandeControleur($twig, $db) {
    // Rediriger vers la fonction commande() dans clientControleur
    return commande($twig, $db);
}

?>
