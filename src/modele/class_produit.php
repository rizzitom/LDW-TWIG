<?php

require_once __DIR__ . '/../config/parametres.php';

class Produit
{
    public static function getProduitsActifs()
    {
        global $pdo;

        $sql = "SELECT id, nom, description, prix, stock, image_url FROM produits WHERE est_actif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
