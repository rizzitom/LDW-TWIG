<?php

/**
 * Contrôleur pour la page de confirmation de commande
 * Affiche les détails de la commande après un paiement réussi
 */
function confirmationCommandeControleur($twig, $db) {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['id'])) {
        header('Location: index.php?page=connexion&redirect=panier');
        exit;
    }
    
    // Récupérer la référence de la commande depuis l'URL
    $reference = isset($_GET['reference']) ? $_GET['reference'] : null;
    
    if (empty($reference)) {
        // Rediriger vers la page d'accueil si aucune référence n'est fournie
        header('Location: index.php');
        exit;
    }
    
    // Initialiser les modèles nécessaires
    $commandeModel = new Commande($db);
    $panierModel = new Panier($db);
    
    // Récupérer la commande par sa référence
    $query = $db->prepare("SELECT id FROM commandes WHERE reference = :reference AND id_utilisateur = :id_utilisateur LIMIT 1");
    $query->execute([
        ':reference' => $reference,
        ':id_utilisateur' => $_SESSION['id']
    ]);
    
    $result = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Commande non trouvée ou n'appartenant pas à l'utilisateur
        header('Location: index.php?page=mon-compte&erreur=commande_introuvable');
        exit;
    }
    
    $id_commande = $result['id'];
    
    // Récupérer les détails complets de la commande
    $commande = $commandeModel->getById($id_commande);
    
    if (!$commande) {
        // Erreur lors de la récupération des détails
        header('Location: index.php?page=mon-compte&erreur=details_commande_introuvables');
        exit;
    }
    
    // Récupérer les informations de paiement
    $paiement = null;
    if ($commande['methode_paiement'] === 'carte') {
        // Récupérer les informations du paiement Stripe
        $stripeModel = new Stripe($db);
        $paiement = $stripeModel->getPaiementCommande($id_commande);
    } else if ($commande['methode_paiement'] === 'paypal') {
        // Récupérer les informations du paiement PayPal
        $paypalModel = new PayPal($db);
        $paiement = $paypalModel->getPaiementCommande($id_commande);
    }
    
    // Vider le panier après confirmation de la commande
    $panierModel->vider();
    
    // Préparer les données pour le template
    $data = [
        'commande' => $commande,
        'paiement' => $paiement,
        'titre' => 'Confirmation de commande'
    ];
    
    // Afficher le template de confirmation
    echo $twig->render('confirmation-commande.twig', $data);
}
