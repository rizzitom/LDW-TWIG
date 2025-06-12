<?php

class Produit {
    private $db;
    private $select;
    private $selectById;
    private $selectBySlug;
    private $selectFeatured;
    private $selectByCategory;
    private $insert;
    private $update;
    private $delete;
    private $updateStock;

    public function __construct($db) {
        $this->db = $db;
        
        $this->select = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                                     FROM produits p 
                                     JOIN categories c ON p.id_categorie = c.id 
                                     WHERE p.est_actif = 1 
                                     ORDER BY p.created_at DESC");
        
        $this->selectById = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                                         FROM produits p 
                                         JOIN categories c ON p.id_categorie = c.id 
                                         WHERE p.id = :id");
        
        $this->selectBySlug = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                                           FROM produits p 
                                           JOIN categories c ON p.id_categorie = c.id 
                                           WHERE p.slug = :slug AND p.est_actif = 1");
        
        $this->selectFeatured = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                                            FROM produits p 
                                            JOIN categories c ON p.id_categorie = c.id 
                                            WHERE p.est_featured = 1 AND p.est_actif = 1 
                                            ORDER BY p.created_at DESC 
                                            LIMIT :limit");
        
        $this->selectByCategory = $db->prepare("SELECT p.*, c.nom as categorie_nom 
                                              FROM produits p 
                                              JOIN categories c ON p.id_categorie = c.id 
                                              WHERE p.id_categorie = :id_categorie AND p.est_actif = 1 
                                              ORDER BY p.created_at DESC");
        
        $this->insert = $db->prepare("INSERT INTO produits(nom, type, description, description_courte, composants, prix, prix_revient, prix_promo, 
                                    stock, id_categorie, image_url, images_supplementaires, caracteristiques, infos_performance,
                                    est_actif, est_featured, statut, slug) 
                                    VALUES (:nom, :type, :description, :description_courte, :composants, :prix, :prix_revient, :prix_promo, 
                                    :stock, :id_categorie, :image_url, :images_supplementaires, :caracteristiques, :infos_performance,
                                    :est_actif, :est_featured, :statut, :slug)");
        
        $this->update = $db->prepare("UPDATE produits 
                                    SET nom = :nom, type = :type, description = :description, description_courte = :description_courte, composants = :composants,
                                    prix = :prix, prix_revient = :prix_revient, prix_promo = :prix_promo, stock = :stock, id_categorie = :id_categorie, 
                                    image_url = :image_url, images_supplementaires = :images_supplementaires, 
                                    caracteristiques = :caracteristiques, infos_performance = :infos_performance, est_actif = :est_actif, 
                                    est_featured = :est_featured, statut = :statut, slug = :slug 
                                    WHERE id = :id");
        
        $this->delete = $db->prepare("DELETE FROM produits WHERE id = :id");
        
        $this->updateStock = $db->prepare("UPDATE produits SET stock = stock - :quantite WHERE id = :id AND stock >= :quantite");
    }

    public function selectAll() {
        try {
            $this->select->execute();
            if ($this->select->errorCode() != 0) {
                print_r($this->select->errorInfo());
                return [];
            }
            return $this->select->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    public function selectById($id) {
        try {
            $this->selectById->execute([':id' => $id]);
            if ($this->selectById->errorCode() != 0) {
                print_r($this->selectById->errorInfo());
                return null;
            }
            return $this->selectById->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return null;
        }
    }

    public function selectBySlug($slug) {
        try {
            $this->selectBySlug->execute([':slug' => $slug]);
            if ($this->selectBySlug->errorCode() != 0) {
                print_r($this->selectBySlug->errorInfo());
                return null;
            }
            return $this->selectBySlug->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return null;
        }
    }

    public function selectFeatured($limit = 4) {
        try {
            $this->selectFeatured->bindParam(':limit', $limit, PDO::PARAM_INT);
            $this->selectFeatured->execute();
            if ($this->selectFeatured->errorCode() != 0) {
                print_r($this->selectFeatured->errorInfo());
                return [];
            }
            return $this->selectFeatured->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }
    
    public function getProduitsFeatured($limit = 4) {
        return $this->selectFeatured($limit);
    }
    
    public function countByCategorie($id_categorie) {
        try {
            $query = $this->db->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie = :id_categorie AND est_actif = 1");
            $query->execute([':id_categorie' => $id_categorie]);
            return $query->fetchColumn();
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return 0;
        }
    }

    public function selectByCategory($id_categorie) {
        try {
            $this->selectByCategory->execute([':id_categorie' => $id_categorie]);
            if ($this->selectByCategory->errorCode() != 0) {
                print_r($this->selectByCategory->errorInfo());
                return [];
            }
            return $this->selectByCategory->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    public function insert($data) {
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['nom']);
            }
            
            // Prepare JSON fields
            if (isset($data['images_supplementaires']) && is_array($data['images_supplementaires'])) {
                $data['images_supplementaires'] = json_encode($data['images_supplementaires']);
            } else {
                $data['images_supplementaires'] = null;
            }
            
            if (isset($data['caracteristiques']) && is_array($data['caracteristiques'])) {
                $data['caracteristiques'] = json_encode($data['caracteristiques']);
            } else {
                $data['caracteristiques'] = null;
            }

            if (isset($data['composants']) && is_array($data['composants'])) {
                $data['composants'] = json_encode($data['composants']);
            } elseif (isset($data['composants']) && is_string($data['composants'])) {

            } else {
                $data['composants'] = null;
            }

            if (isset($data['infos_performance']) && is_array($data['infos_performance'])) {
                $data['infos_performance'] = json_encode($data['infos_performance']);
            } elseif (isset($data['infos_performance']) && is_string($data['infos_performance'])) {
               
            } else {
                $data['infos_performance'] = null;
            }
            
            $this->insert->execute([
                ':nom' => $data['nom'],
                ':type' => $data['type'] ?? null,
                ':description' => $data['description'] ?? null,
                ':description_courte' => $data['description_courte'] ?? null,
                ':composants' => $data['composants'],
                ':prix' => $data['prix'],
                ':prix_revient' => $data['prix_revient'] ?? null,
                ':prix_promo' => $data['prix_promo'] ?? null,
                ':stock' => $data['stock'] ?? 0,
                ':id_categorie' => $data['id_categorie'],
                ':image_url' => $data['image_url'] ?? null,
                ':images_supplementaires' => $data['images_supplementaires'],
                ':caracteristiques' => $data['caracteristiques'],
                ':infos_performance' => $data['infos_performance'],
                ':est_actif' => $data['est_actif'] ?? true,
                ':est_featured' => $data['est_featured'] ?? false,
                ':statut' => $data['statut'] ?? 'en_stock',
                ':slug' => $data['slug']
            ]);
            
            if ($this->insert->errorCode() != 0) {
                print_r($this->insert->errorInfo());
                return false;
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Update a product
    public function update($id, $data) {
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['nom']);
            }
            
            // Prepare JSON fields
            if (isset($data['images_supplementaires']) && is_array($data['images_supplementaires'])) {
                $data['images_supplementaires'] = json_encode($data['images_supplementaires']);
            } else {
                $data['images_supplementaires'] = null;
            }
            
            if (isset($data['caracteristiques']) && is_array($data['caracteristiques'])) {
                $data['caracteristiques'] = json_encode($data['caracteristiques']);
            } else {
                $data['caracteristiques'] = null;
            }

            if (isset($data['composants']) && is_array($data['composants'])) {
                $data['composants'] = json_encode($data['composants']);
            } elseif (isset($data['composants']) && is_string($data['composants'])) {

            } else {
                $data['composants'] = null;
            }

            if (isset($data['infos_performance']) && is_array($data['infos_performance'])) {
                $data['infos_performance'] = json_encode($data['infos_performance']);
            } elseif (isset($data['infos_performance']) && is_string($data['infos_performance'])) {
                // Keep as string if already JSON string
            } else {
                $data['infos_performance'] = null;
            }
            
            $this->update->execute([
                ':id' => $id,
                ':nom' => $data['nom'],
                ':type' => $data['type'] ?? null,
                ':description' => $data['description'] ?? null,
                ':description_courte' => $data['description_courte'] ?? null,
                ':composants' => $data['composants'],
                ':prix' => $data['prix'],
                ':prix_revient' => $data['prix_revient'] ?? null,
                ':prix_promo' => $data['prix_promo'] ?? null,
                ':stock' => $data['stock'] ?? 0,
                ':id_categorie' => $data['id_categorie'],
                ':image_url' => $data['image_url'] ?? null,
                ':images_supplementaires' => $data['images_supplementaires'],
                ':caracteristiques' => $data['caracteristiques'],
                ':infos_performance' => $data['infos_performance'],
                ':est_actif' => $data['est_actif'] ?? true,
                ':est_featured' => $data['est_featured'] ?? false,
                ':statut' => $data['statut'] ?? 'en_stock',
                ':slug' => $data['slug']
            ]);
            
            if ($this->update->errorCode() != 0) {
                print_r($this->update->errorInfo());
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Delete a product permanently from database
    public function delete($id) {
        try {
            // Vérifier s'il y a des références dans les commandes
            $checkCommandes = $this->db->prepare("SELECT COUNT(*) as count FROM commande_produit WHERE id_produit = :id");
            $checkCommandes->execute([':id' => $id]);
            $commandeCount = $checkCommandes->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($commandeCount > 0) {
                // Si le produit est dans des commandes, faire un soft delete seulement
                $softDelete = $this->db->prepare("UPDATE produits SET est_actif = 0 WHERE id = :id");
                $softDelete->execute([':id' => $id]);
                if ($softDelete->errorCode() != 0) {
                    print_r($softDelete->errorInfo());
                    return false;
                }
                return true;
            } else {
                // Sinon, supprimer d'abord les références dans les paniers
                $deletePanier = $this->db->prepare("DELETE FROM panier WHERE id_produit = :id");
                $deletePanier->execute([':id' => $id]);
                
                $deletePanierTemp = $this->db->prepare("DELETE FROM panier_temp WHERE id_produit = :id");
                $deletePanierTemp->execute([':id' => $id]);
                
                // Puis supprimer le produit
                $this->delete->execute([':id' => $id]);
                if ($this->delete->errorCode() != 0) {
                    print_r($this->delete->errorInfo());
                    return false;
                }
                return true;
            }
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Update product stock
    public function updateStock($id, $quantite) {
        try {
            $this->updateStock->execute([
                ':id' => $id,
                ':quantite' => $quantite
            ]);
            
            if ($this->updateStock->errorCode() != 0) {
                print_r($this->updateStock->errorInfo());
                return false;
            }
            
            return $this->updateStock->rowCount() > 0;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Helper function to generate a slug from a product name
    private function generateSlug($nom) {
        $slug = strtolower($nom);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug already exists and add a number if needed
        $checkSlug = $this->db->prepare("SELECT COUNT(*) as count FROM produits WHERE slug = :slug");
        $checkSlug->execute([':slug' => $slug]);
        $result = $checkSlug->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $i = 1;
            $base_slug = $slug;
            do {
                $slug = $base_slug . '-' . $i++;
                $checkSlug->execute([':slug' => $slug]);
                $result = $checkSlug->fetch(PDO::FETCH_ASSOC);
            } while ($result['count'] > 0);
        }
        
        return $slug;
    }
}
