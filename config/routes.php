<?php

function getPage($db) {
    $lesPages['accueil'] = "accueilControleur";
    $lesPages['services'] = "servicesControleur";
    $lesPages['devis'] = "devisControleur";
    $lesPages['connexion'] = "connexionControleur";
    $lesPages['deconnexion'] = "deconnexionControleur";
    $lesPages['inscrire'] = "inscrireControleur";
    $lesPages['panier'] = "panierControleur";
    $lesPages['produit'] = "produitControleur";
    $lesPages['type'] = "typeControleur";
    $lesPages['utilisateurs'] = "utilisateursControleur";
    $lesPages['utilisateursmodif'] = "utilisateursModifControleur";
    $lesPages['boutique'] = "boutiqueControleur";
    $lesPages['maintenance'] = "maintenanceControleur";
    $lesPages['admin'] = "adminControleur";
    $lesPages['pc-sur-mesure'] = "PcSurMesureControleur"; 
    
    // Client routes
    $lesPages['mon_compte'] = "clientControleur";
    $lesPages['commandes'] = "clientControleur";
    $lesPages['commande'] = "clientControleur";
    $lesPages['adresses'] = "clientControleur";
    $lesPages['messages'] = "clientControleur";
    $lesPages['securite'] = "clientControleur";
    
    // Client devis routes
    $lesPages['mes-devis'] = "devisClientControleur";
    $lesPages['devis-detail'] = "devisDetailControleur";
    
    // Paiement route
    $lesPages['paiement'] = "paiementControleur";
    
    // Legal pages
    $lesPages['legal'] = "legalControleur";
    $lesPages['cgv'] = "legalControleur";
    $lesPages['politique-de-confidentialite'] = "legalControleur";
    $lesPages['mentions-legales'] = "legalControleur";
    $lesPages['cookies'] = "legalControleur";
    
    $lesPages['mondial-relay'] = "mondialRelayControleur";

    // if ($db!=null){
        if(isset($_GET['page'])){
            $page = $_GET['page'];
        } else{
            $page = 'accueil';
        }

        if (isset($lesPages[$page])){
            $contenu = $lesPages[$page];
        }
        else { 
            $contenu = $lesPages["accueil"];
        }
    // }

    //    else{
    //     $contenu = $lesPages['maintenance'];
    //    }
       
    return $contenu;
}

?>
