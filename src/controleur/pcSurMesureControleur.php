<?php

namespace App\Controleur;

class PcSurMesureControleur extends AbstractControleur
{
    public function index()
    {        
        $produitModele = new \Produit($this->db);
        $categorieModele = new \Categorie($this->db);

        $categoriePcSurMesure = $categorieModele->selectBySlug('pc-sur-mesure');
        $categorieId = null;
        if ($categoriePcSurMesure) {
            $categorieId = $categoriePcSurMesure['id'];
        }

        $produits = [];
        if ($categorieId) {

            $produits = $produitModele->selectByCategory($categorieId);
        } else {
            // Si pas de catégorie PC sur mesure, afficher tous les produits pour le moment
            // En production, il faudrait créer la catégorie dans la base de données
            $produits = $produitModele->selectAll();
        }
        
        $data = [
            'titre' => 'PC sur mesure',
            'page' => 'pc-sur-mesure',
            'produits' => $produits,
            'categorie_id' => $categorieId,
            'user' => isset($_SESSION['utilisateur']) ? $_SESSION['utilisateur'] : null
        ];

        $this->afficherVue('pc-sur-mesure', $data);
    }

    public function filter()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $produitModele = new \Produit($this->db);
            
            $type = $_POST['type'] ?? '';
            $budget = $_POST['budget'] ?? '';
            $stock = $_POST['stock'] ?? '';
            
            $produits = $produitModele->selectAll();
            
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'produits' => $produits,
                'count' => count($produits)
            ]);
            exit;
        }
    }
}
