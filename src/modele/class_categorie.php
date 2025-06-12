<?php

class Categorie {
    private $db;
    private $select;
    private $selectById;
    private $selectBySlug;
    private $selectWithParent;
    private $selectChildren;
    private $selectMainCategories;
    private $insert;
    private $update;
    private $delete;

    public function __construct($db) {
        $this->db = $db;
        
        // Basic select queries
        $this->select = $db->prepare("SELECT * FROM categories WHERE est_actif = 1 ORDER BY nom");
        
        $this->selectById = $db->prepare("SELECT * FROM categories WHERE id = :id");
        
        $this->selectBySlug = $db->prepare("SELECT * FROM categories WHERE slug = :slug AND est_actif = 1");
        
        $this->selectWithParent = $db->prepare("SELECT c.*, p.nom as parent_nom 
                                              FROM categories c
                                              LEFT JOIN categories p ON c.parent_id = p.id
                                              WHERE c.est_actif = 1
                                              ORDER BY c.nom");
        
        $this->selectChildren = $db->prepare("SELECT * FROM categories 
                                            WHERE parent_id = :parent_id AND est_actif = 1
                                            ORDER BY nom");
        
        $this->selectMainCategories = $db->prepare("SELECT * FROM categories 
                                                  WHERE parent_id IS NULL AND est_actif = 1
                                                  ORDER BY nom");
        
        // CRUD operations
        $this->insert = $db->prepare("INSERT INTO categories(nom, description, parent_id, image_url, est_actif, slug) 
                                    VALUES (:nom, :description, :parent_id, :image_url, :est_actif, :slug)");
        
        $this->update = $db->prepare("UPDATE categories 
                                    SET nom = :nom, description = :description, parent_id = :parent_id, 
                                    image_url = :image_url, est_actif = :est_actif, slug = :slug 
                                    WHERE id = :id");
        
        $this->delete = $db->prepare("UPDATE categories SET est_actif = 0 WHERE id = :id");
    }

    // Get all active categories
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

    // Get a category by ID
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

    // Get a category by slug
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

    // Get all categories with parent information
    public function selectWithParent() {
        try {
            $this->selectWithParent->execute();
            if ($this->selectWithParent->errorCode() != 0) {
                print_r($this->selectWithParent->errorInfo());
                return [];
            }
            return $this->selectWithParent->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Get child categories of a parent
    public function selectChildren($parent_id) {
        try {
            $this->selectChildren->execute([':parent_id' => $parent_id]);
            if ($this->selectChildren->errorCode() != 0) {
                print_r($this->selectChildren->errorInfo());
                return [];
            }
            return $this->selectChildren->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Get main (top-level) categories
    public function selectMainCategories() {
        try {
            $this->selectMainCategories->execute();
            if ($this->selectMainCategories->errorCode() != 0) {
                print_r($this->selectMainCategories->errorInfo());
                return [];
            }
            return $this->selectMainCategories->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return [];
        }
    }

    // Create a new category
    public function insert($data) {
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['nom']);
            }
            
            $this->insert->execute([
                ':nom' => $data['nom'],
                ':description' => $data['description'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':image_url' => $data['image_url'] ?? null,
                ':est_actif' => $data['est_actif'] ?? true,
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

    // Update a category
    public function update($id, $data) {
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['nom']);
            }
            
            // Prevent a category from being its own parent
            if ($data['parent_id'] == $id) {
                $data['parent_id'] = null;
            }
            
            $this->update->execute([
                ':id' => $id,
                ':nom' => $data['nom'],
                ':description' => $data['description'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':image_url' => $data['image_url'] ?? null,
                ':est_actif' => $data['est_actif'] ?? true,
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

    // Soft delete a category (mark as inactive)
    public function delete($id) {
        try {
            $this->delete->execute([':id' => $id]);
            if ($this->delete->errorCode() != 0) {
                print_r($this->delete->errorInfo());
                return false;
            }
            return true;
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            return false;
        }
    }

    // Get hierarchical category tree
    public function getCategoryTree() {
        $mainCategories = $this->selectMainCategories();
        $tree = [];
        
        foreach ($mainCategories as $main) {
            $category = $main;
            $category['children'] = $this->selectChildren($main['id']);
            $tree[] = $category;
        }
        
        return $tree;
    }

    // Helper function to generate a slug from a category name
    private function generateSlug($nom) {
        $slug = strtolower($nom);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug already exists and add a number if needed
        $checkSlug = $this->db->prepare("SELECT COUNT(*) as count FROM categories WHERE slug = :slug");
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
