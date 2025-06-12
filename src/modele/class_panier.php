<?php



class Panier {

    private $db;

    private $selectProduit;

    private $updateStock;



    public function __construct($db) {

        $this->db = $db;

        $this->selectProduit = $this->db->prepare("SELECT id, nom, prix, prix_promo, stock, image_url FROM produits WHERE id = :id AND est_actif = 1");

        $this->updateStock = $this->db->prepare("UPDATE produits SET stock = stock - :quantite WHERE id = :id AND stock >= :quantite");

    }



    // Initialiser le panier dans la session si ce n'est pas déjà fait

    public function initialiser() {

        if (!isset($_SESSION['panier'])) {

            $_SESSION['panier'] = [

                'produits' => [],

                'total' => 0,

                'nombre_articles' => 0,

                'pays_livraison' => 'France', 

                'frais_livraison' => 0,

                'tva' => 0,

                'methode_livraison' => 'domicile', 

                'point_relais_id' => null,

                'point_relais_nom' => null,

                'point_relais_adresse' => null,

                'bureau_poste_id' => null,

                'magasin_id' => null,

                'instructions_livraison' => null,

                // Nouvelles informations pour les adresses
                'adresse_livraison' => null,
                'adresse_facturation' => null,
                'nom_client' => null,
                'prenom_client' => null,
                'email_client' => null,
                'telephone_client' => null

            ];

        }

    }

    

    // Mettre à jour le pays de livraison

    public function setPaysLivraison($pays) {

        $this->initialiser();

        $_SESSION['panier']['pays_livraison'] = $pays;

        $this->calculerFraisLivraison();

        $this->calculerTVA();

    }

    

    // Mettre à jour la méthode de livraison

    public function setMethodeLivraison($methode, $option_id = null) {

        $this->initialiser();

        

        // Valider la méthode

        $methodes_valides = ['domicile', 'relay', 'poste', 'click-collect'];

        if (!in_array($methode, $methodes_valides)) {

            $methode = 'domicile'; // Défaut en cas de méthode invalide

        }

        

        $_SESSION['panier']['methode_livraison'] = $methode;

        

        // Stocker l'ID de l'option sélectionnée selon la méthode

        switch ($methode) {

            case 'relay':

                $_SESSION['panier']['point_relais_id'] = $option_id;

                $_SESSION['panier']['bureau_poste_id'] = null;

                $_SESSION['panier']['magasin_id'] = null;

                break;

            case 'poste':

                $_SESSION['panier']['bureau_poste_id'] = $option_id;

                $_SESSION['panier']['point_relais_id'] = null;

                $_SESSION['panier']['magasin_id'] = null;

                break;

            case 'click-collect':

                $_SESSION['panier']['magasin_id'] = $option_id;

                $_SESSION['panier']['point_relais_id'] = null;

                $_SESSION['panier']['bureau_poste_id'] = null;

                break;

            default:

                $_SESSION['panier']['point_relais_id'] = null;

                $_SESSION['panier']['bureau_poste_id'] = null;

                $_SESSION['panier']['magasin_id'] = null;

                break;

        }

        

        $this->calculerFraisLivraison();

        $this->calculerTVA();

    }

    

    // Calculer les frais de livraison

    private function calculerFraisLivraison() {

        $this->initialiser();

        $total = $_SESSION['panier']['total'];

        $pays = $_SESSION['panier']['pays_livraison'];

        $methode = $_SESSION['panier']['methode_livraison'];

        

        // Livraison gratuite à partir de 50€ (sauf click & collect toujours gratuit)

        if ($total >= 50 || $methode === 'click-collect') {

            $_SESSION['panier']['frais_livraison'] = 0;

        } else {

            // Tarifs selon la méthode et le pays

            switch ($methode) {

                case 'relay':

                case 'poste':

                    // Point relais et bureau de poste: tarif légèrement réduit

                    $_SESSION['panier']['frais_livraison'] = ($pays == 'France') ? 3.5 : 8.5;

                    break;

                case 'click-collect':

                    // Retrait en magasin toujours gratuit

                    $_SESSION['panier']['frais_livraison'] = 0;

                    break;

                case 'domicile':

                default:

                    // Livraison à domicile: tarif standard

                    $_SESSION['panier']['frais_livraison'] = ($pays == 'France') ? 0 : 10;

                    break;

            }

        }

    }

    

    // Calculer la TVA (20%)

    private function calculerTVA() {

        $this->initialiser();

        $_SESSION['panier']['tva'] = $_SESSION['panier']['total'] * 0.20;

    }



    // Ajouter un produit au panier

    public function ajouter($id_produit, $quantite = 1) {

        $this->initialiser();

        

        // Vérifier si le produit existe et est disponible

        $this->selectProduit->execute([':id' => $id_produit]);

        $produit = $this->selectProduit->fetch(PDO::FETCH_ASSOC);

        

        if (!$produit) {

            return [

                'success' => false,

                'message' => 'Produit non trouvé ou indisponible'

            ];

        }

        

        // Vérifier le stock

        if ($produit['stock'] < $quantite) {

            return [

                'success' => false,

                'message' => 'Stock insuffisant. Stock disponible: ' . $produit['stock']

            ];

        }

        

        // Utiliser le prix promo s'il existe, sinon le prix normal

        $prix = !empty($produit['prix_promo']) ? $produit['prix_promo'] : $produit['prix'];

        

        // Vérifier si le produit est déjà dans le panier

        $produit_existe = false;

        

        foreach ($_SESSION['panier']['produits'] as $key => $item) {

            if ($item['id'] == $id_produit) {

                // Mettre à jour la quantité

                $nouvelle_quantite = $item['quantite'] + $quantite;

                

                // Vérifier si la nouvelle quantité est disponible en stock

                if ($produit['stock'] < $nouvelle_quantite) {

                    return [

                        'success' => false,

                        'message' => 'Stock insuffisant pour ajouter cette quantité supplémentaire. Stock disponible: ' . $produit['stock']

                    ];

                }

                

                $_SESSION['panier']['produits'][$key]['quantite'] = $nouvelle_quantite;

                $_SESSION['panier']['produits'][$key]['total_produit'] = $nouvelle_quantite * $prix;

                $produit_existe = true;

                break;

            }

        }

        

        // Si le produit n'existe pas, l'ajouter

        if (!$produit_existe) {

            $_SESSION['panier']['produits'][] = [

                'id' => $produit['id'],

                'nom' => $produit['nom'],

                'prix' => $prix,

                'quantite' => $quantite,

                'total_produit' => $quantite * $prix,

                'image_url' => $produit['image_url']

            ];

        }

        

        // Mettre à jour le total du panier

        $this->calculerTotal();

        

        return [

            'success' => true,

            'message' => 'Produit ajouté au panier',

            'panier' => $_SESSION['panier']

        ];

    }



    // Mettre à jour la quantité d'un produit dans le panier

    public function mettreAJour($id_produit, $quantite) {

        $this->initialiser();

        

        if ($quantite <= 0) {

            return $this->supprimer($id_produit);

        }

        

        // Vérifier si le produit existe et est disponible

        $this->selectProduit->execute([':id' => $id_produit]);

        $produit = $this->selectProduit->fetch(PDO::FETCH_ASSOC);

        

        if (!$produit) {

            return [

                'success' => false,

                'message' => 'Produit non trouvé ou indisponible'

            ];

        }

        

        // Vérifier le stock

        if ($produit['stock'] < $quantite) {

            return [

                'success' => false,

                'message' => 'Stock insuffisant. Stock disponible: ' . $produit['stock']

            ];

        }

        

        // Utiliser le prix promo s'il existe, sinon le prix normal

        $prix = !empty($produit['prix_promo']) ? $produit['prix_promo'] : $produit['prix'];

        

        foreach ($_SESSION['panier']['produits'] as $key => $item) {

            if ($item['id'] == $id_produit) {

                $_SESSION['panier']['produits'][$key]['quantite'] = $quantite;

                $_SESSION['panier']['produits'][$key]['total_produit'] = $quantite * $prix;

                break;

            }

        }

        

        // Mettre à jour le total du panier

        $this->calculerTotal();

        

        return [

            'success' => true,

            'message' => 'Panier mis à jour',

            'panier' => $_SESSION['panier']

        ];

    }



    // Supprimer un produit du panier

    public function supprimer($id_produit) {

        $this->initialiser();

        

        foreach ($_SESSION['panier']['produits'] as $key => $item) {

            if ($item['id'] == $id_produit) {

                unset($_SESSION['panier']['produits'][$key]);

                // Réindexer le tableau

                $_SESSION['panier']['produits'] = array_values($_SESSION['panier']['produits']);

                break;

            }

        }

        

        // Mettre à jour le total du panier

        $this->calculerTotal();

        

        return [

            'success' => true,

            'message' => 'Produit supprimé du panier',

            'panier' => $_SESSION['panier']

        ];

    }



    // Vider le panier

    public function vider() {

        $_SESSION['panier'] = [

            'produits' => [],

            'total' => 0,

            'nombre_articles' => 0,

            'pays_livraison' => 'France',

            'frais_livraison' => 0,

            'tva' => 0,

            'methode_livraison' => 'domicile',

            'point_relais_id' => null,

            'bureau_poste_id' => null,

            'magasin_id' => null,

            'adresse_livraison' => null,
            'adresse_facturation' => null,
            'nom_client' => null,
            'prenom_client' => null,
            'email_client' => null,
            'telephone_client' => null

        ];

        

        return [

            'success' => true,

            'message' => 'Panier vidé',

            'panier' => $_SESSION['panier']

        ];

    }



    // Récupérer le contenu du panier

    public function getContenu() {

        $this->initialiser();

        return $_SESSION['panier'];

    }



    // Calculer le total du panier

    private function calculerTotal() {

        $total = 0;

        $nombre_articles = 0;

        

        foreach ($_SESSION['panier']['produits'] as $item) {

            $total += $item['total_produit'];

            $nombre_articles += $item['quantite'];

        }

        

        $_SESSION['panier']['total'] = $total;

        $_SESSION['panier']['nombre_articles'] = $nombre_articles;

        

        // Recalculer les frais de livraison et la TVA

        $this->calculerFraisLivraison();

        $this->calculerTVA();

    }

    

    // Obtenir le total TTC (produits + frais de livraison + TVA)

    public function getTotalTTC() {

        $this->initialiser();

        return $_SESSION['panier']['total'] + $_SESSION['panier']['frais_livraison'] + $_SESSION['panier']['tva'];

    }



    // Valider la disponibilité des produits dans le panier

    public function validerDisponibilite() {

        $this->initialiser();

        $produits_indisponibles = [];

        

        foreach ($_SESSION['panier']['produits'] as $key => $item) {

            $this->selectProduit->execute([':id' => $item['id']]);

            $produit = $this->selectProduit->fetch(PDO::FETCH_ASSOC);

            

            if (!$produit || $produit['stock'] < $item['quantite']) {

                $produits_indisponibles[] = [

                    'id' => $item['id'],

                    'nom' => $item['nom'],

                    'quantite_demandee' => $item['quantite'],

                    'stock_disponible' => $produit ? $produit['stock'] : 0

                ];

            }

        }

        

        return [

            'success' => count($produits_indisponibles) === 0,

            'produits_indisponibles' => $produits_indisponibles

        ];

    }



    // Mettre à jour le stock après une commande

    public function mettreAJourStock() {

        $this->initialiser();

        $erreurs = [];

        

        $this->db->beginTransaction();

        

        try {

            foreach ($_SESSION['panier']['produits'] as $item) {

                $this->updateStock->execute([

                    ':id' => $item['id'],

                    ':quantite' => $item['quantite']

                ]);

                

                if ($this->updateStock->rowCount() === 0) {

                    throw new Exception("Impossible de mettre à jour le stock pour le produit ID: " . $item['id']);

                }

            }

            

            $this->db->commit();

            return ['success' => true];

            

        } catch (Exception $e) {

            $this->db->rollBack();

            return [

                'success' => false,

                'message' => $e->getMessage()

            ];

        }

    }

    // Définir les informations du client
    public function setClientInfo($nom, $prenom, $email, $telephone) {
        $this->initialiser();
        $_SESSION['panier']['nom_client'] = $nom;
        $_SESSION['panier']['prenom_client'] = $prenom;
        $_SESSION['panier']['email_client'] = $email;
        $_SESSION['panier']['telephone_client'] = $telephone;
        
        return [
            'success' => true,
            'message' => 'Informations client mises à jour'
        ];
    }
    
    // Définir l'adresse de livraison
    public function setAdresseLivraison($adresse) {
        $this->initialiser();
        $_SESSION['panier']['adresse_livraison'] = $adresse;
        
        return [
            'success' => true,
            'message' => 'Adresse de livraison mise à jour'
        ];
    }
    
    // Définir l'adresse de facturation
    public function setAdresseFacturation($adresse) {
        $this->initialiser();
        $_SESSION['panier']['adresse_facturation'] = $adresse;
        
        return [
            'success' => true,
            'message' => 'Adresse de facturation mise à jour'
        ];
    }
    
    // Définir les instructions de livraison
    public function setInstructionsLivraison($instructions) {
        $this->initialiser();
        $_SESSION['panier']['instructions_livraison'] = $instructions;
        
        return [
            'success' => true,
            'message' => 'Instructions de livraison mises à jour'
        ];
    }
    
    // Récupérer les informations client
    public function getClientInfo() {
        $this->initialiser();
        return [
            'nom' => $_SESSION['panier']['nom_client'],
            'prenom' => $_SESSION['panier']['prenom_client'],
            'email' => $_SESSION['panier']['email_client'],
            'telephone' => $_SESSION['panier']['telephone_client']
        ];
    }
    
    // Récupérer l'adresse de livraison
    public function getAdresseLivraison() {
        $this->initialiser();
        return $_SESSION['panier']['adresse_livraison'];
    }
    
    // Récupérer l'adresse de facturation
    public function getAdresseFacturation() {
        $this->initialiser();
        return $_SESSION['panier']['adresse_facturation'];
    }
}
