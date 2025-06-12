<?php

function produitControleur($twig, $db) {
    $produitModel = new Produit($db);
    $categorieModel = new Categorie($db);
    
    $admin = isset($_SESSION['role']) && $_SESSION['role'] == 1;
    $action_param = isset($_GET['action']) ? $_GET['action'] : null;
    $slug_param = isset($_GET['slug']) ? $_GET['slug'] : null;
    $id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $is_view_request = false;
    if ($slug_param) {
        $is_view_request = true;
    } elseif ($id_param && !in_array($action_param, ['modifier', 'supprimer'])) {
        $is_view_request = true;
    }

    if ($is_view_request) {
        $produit_to_view = null;
        if ($slug_param) {
            $produit_to_view = $produitModel->selectBySlug($slug_param);
        } elseif ($id_param) { 
            $produit_to_view = $produitModel->selectById($id_param);
        }

        if ($produit_to_view) {
            if (!empty($produit_to_view['caracteristiques'])) {
                $produit_to_view['caracteristiques'] = json_decode($produit_to_view['caracteristiques'], true) ?: [];
            } else {
                $produit_to_view['caracteristiques'] = [];
            }
            if (!empty($produit_to_view['images_supplementaires'])) {
                $produit_to_view['images_supplementaires'] = json_decode($produit_to_view['images_supplementaires'], true) ?: [];
            } else {
                $produit_to_view['images_supplementaires'] = [];
            }
            
            $produits_associes = $produitModel->selectByCategory($produit_to_view['id_categorie']);
            $produits_associes = array_filter($produits_associes, function($p) use ($produit_to_view) {
                return $p['id'] != $produit_to_view['id'];
            });
            $produits_associes = array_slice($produits_associes, 0, 4);
            
            echo $twig->render('produit-detail.twig', [
                'produit' => $produit_to_view,
                'produits_associes' => $produits_associes,
                'ajout_success' => isset($_GET['ajout']) && $_GET['ajout'] === 'success', 
                'ajout_error' => isset($_GET['ajout']) && $_GET['ajout'] === 'error'     
            ]);
            return; 
        } else {
            header('Location: index.php?page=boutique');
            exit;
        }
    } elseif ($admin) {
        $effective_action = $action_param ?: 'liste'; 

        switch ($effective_action) {
            case 'ajouter':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = [
                        'nom' => $_POST['nom'],
                        'description' => $_POST['description'],
                        'description_courte' => $_POST['description_courte'],
                        'prix' => $_POST['prix'],
                        'prix_promo' => !empty($_POST['prix_promo']) ? $_POST['prix_promo'] : null,
                        'stock' => $_POST['stock'],
                        'id_categorie' => $_POST['id_categorie'],
                        'est_actif' => isset($_POST['est_actif']) ? 1 : 0,
                        'est_featured' => isset($_POST['est_featured']) ? 1 : 0
                    ];
                    if (isset($_POST['carac_nom']) && isset($_POST['carac_valeur'])) {
                        $caracteristiques = [];
                        foreach ($_POST['carac_nom'] as $key => $nom) {
                            if (!empty($nom) && isset($_POST['carac_valeur'][$key])) {
                                $caracteristiques[$nom] = $_POST['carac_valeur'][$key];
                            }
                        }
                        if (!empty($caracteristiques)) {
                            $data['caracteristiques'] = $caracteristiques; 
                        }
                    }
                    $id_produit_inserted = $produitModel->insert($data);
                    if ($id_produit_inserted) {
                        $update_data = [];
                        $upload = new Upload($db);
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $upload_result = $upload->uploadProductImage($_FILES['image'], $id_produit_inserted);
                            if ($upload_result && $upload_result['success']) {
                                $update_data['image_url'] = $upload_result['url'];
                            }
                        }
                        if (isset($_FILES['images_supplementaires'])) {
                            $images_supplementaires_urls = [];
                            foreach ($_FILES['images_supplementaires']['name'] as $key => $name) {
                                if ($_FILES['images_supplementaires']['error'][$key] === UPLOAD_ERR_OK) {
                                    $file_item = [
                                        'name' => $_FILES['images_supplementaires']['name'][$key],
                                        'type' => $_FILES['images_supplementaires']['type'][$key],
                                        'tmp_name' => $_FILES['images_supplementaires']['tmp_name'][$key],
                                        'error' => $_FILES['images_supplementaires']['error'][$key],
                                        'size' => $_FILES['images_supplementaires']['size'][$key]
                                    ];
                                    $upload_result = $upload->uploadProductImage($file_item, $id_produit_inserted);
                                    if ($upload_result && $upload_result['success']) {
                                        $images_supplementaires_urls[] = $upload_result['url'];
                                    }
                                }
                            }
                            if (!empty($images_supplementaires_urls)) {
                                $update_data['images_supplementaires'] = $images_supplementaires_urls; 
                            }
                        }
                        if (!empty($update_data)) {
                            $produitModel->update($id_produit_inserted, $update_data);
                        }
                        header('Location: index.php?page=produit&ajout=success');
                        exit;
                    } else {
                        $erreur = "Erreur lors de l'ajout du produit";
                    }
                }
                $categories_for_form = $categorieModel->selectAll();
                echo $twig->render('admin/produit-ajout.twig', [
                    'categories' => $categories_for_form,
                    'erreur' => isset($erreur) ? $erreur : null
                ]);
                break;
                
            case 'modifier':
                if (!$id_param) {
                    header('Location: index.php?page=produit&action=liste'); 
                    exit;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = [
                        'nom' => $_POST['nom'],
                        'description' => $_POST['description'],
                        'description_courte' => $_POST['description_courte'],
                        'prix' => $_POST['prix'],
                        'prix_promo' => !empty($_POST['prix_promo']) ? $_POST['prix_promo'] : null,
                        'stock' => $_POST['stock'],
                        'id_categorie' => $_POST['id_categorie'],
                        'est_actif' => isset($_POST['est_actif']) ? 1 : 0,
                        'est_featured' => isset($_POST['est_featured']) ? 1 : 0,
                        'slug' => $_POST['slug'] 
                    ];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload = new Upload($db);
                        $upload_result = $upload->uploadProductImage($_FILES['image'], $id_param);
                        if ($upload_result && $upload_result['success']) {
                            $data['image_url'] = $upload_result['url'];
                        }
                    }
                    $produit_actuel = $produitModel->selectById($id_param);
                    $images_actuelles = [];
                    if (!empty($produit_actuel['images_supplementaires'])) {
                        $images_actuelles = json_decode($produit_actuel['images_supplementaires'], true) ?: [];
                    }
                    $images_a_conserver = isset($_POST['images_existantes']) ? $_POST['images_existantes'] : [];
                    $nouvelles_images = []; 
                    foreach ($images_a_conserver as $index) {
                        if (isset($images_actuelles[$index])) {
                            $nouvelles_images[] = $images_actuelles[$index];
                        }
                    }
                    if (isset($_FILES['nouvelles_images'])) {
                        $upload = new Upload($db);
                        foreach ($_FILES['nouvelles_images']['name'] as $key => $name) {
                            if ($_FILES['nouvelles_images']['error'][$key] === UPLOAD_ERR_OK) {
                                $file_item = [
                                    'name' => $_FILES['nouvelles_images']['name'][$key],
                                    'type' => $_FILES['nouvelles_images']['type'][$key],
                                    'tmp_name' => $_FILES['nouvelles_images']['tmp_name'][$key],
                                    'error' => $_FILES['nouvelles_images']['error'][$key],
                                    'size' => $_FILES['nouvelles_images']['size'][$key]
                                ];
                                $upload_result = $upload->uploadProductImage($file_item, $id_param);
                                if ($upload_result && $upload_result['success']) {
                                    $nouvelles_images[] = $upload_result['url'];
                                }
                            }
                        }
                    }
                    $data['images_supplementaires'] = $nouvelles_images;
                    if (isset($_POST['carac_nom']) && isset($_POST['carac_valeur'])) {
                        $caracteristiques = [];
                        foreach ($_POST['carac_nom'] as $key => $nom) {
                            if (!empty($nom) && isset($_POST['carac_valeur'][$key])) {
                                $caracteristiques[$nom] = $_POST['carac_valeur'][$key];
                            }
                        }
                        $data['caracteristiques'] = $caracteristiques; 
                    }
                    $resultat = $produitModel->update($id_param, $data);
                    if ($resultat) {
                        header('Location: index.php?page=produit&action=liste&modif=success');
                        exit;
                    } else {
                        $erreur = "Erreur lors de la modification du produit";
                    }
                }
                $produit_to_edit = $produitModel->selectById($id_param);
                if (!$produit_to_edit) {
                    header('Location: index.php?page=produit&action=liste');
                    exit;
                }
                if (!empty($produit_to_edit['caracteristiques'])) {
                    $produit_to_edit['caracteristiques'] = json_decode($produit_to_edit['caracteristiques'], true) ?: [];
                } else {
                    $produit_to_edit['caracteristiques'] = [];
                }
                if (!empty($produit_to_edit['images_supplementaires'])) {
                    $produit_to_edit['images_supplementaires'] = json_decode($produit_to_edit['images_supplementaires'], true) ?: [];
                } else {
                    $produit_to_edit['images_supplementaires'] = [];
                }
                $categories_for_form = $categorieModel->selectAll();
                echo $twig->render('admin/produit-modification.twig', [
                    'produit' => $produit_to_edit,
                    'categories' => $categories_for_form,
                    'erreur' => isset($erreur) ? $erreur : null
                ]);
                break;
                
            case 'supprimer':
                if (!$id_param) { 
                    header('Location: index.php?page=produit&action=liste');
                    exit;
                }
                $resultat = $produitModel->delete($id_param);
                if ($resultat) {
                    header('Location: index.php?page=produit&action=liste&suppression=success');
                } else {
                    header('Location: index.php?page=produit&action=liste&suppression=error');
                }
                exit;
                break;
                
            default: // 'liste'
                $page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
                $limite = 20;
              
                $stmt = $db->prepare("SELECT p.*, c.nom as categorie_nom FROM produits p JOIN categories c ON p.id_categorie = c.id ORDER BY p.created_at DESC");
                $stmt->execute();
                $tous_les_produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $nombre_total = count($tous_les_produits);
                $nombre_pages = ceil($nombre_total / $limite);
                if ($nombre_pages > 0) {
                    $page_num = max(1, min($page_num, $nombre_pages));
                } else {
                    $page_num = 1; 
                }
                $debut = ($page_num - 1) * $limite;
                $produits_page = array_slice($tous_les_produits, $debut, $limite);
                echo $twig->render('admin/produit-liste.twig', [
                    'produits' => $produits_page,
                    'pagination' => [
                        'page_courante' => $page_num,
                        'nombre_pages' => $nombre_pages,
                        'nombre_total' => $nombre_total
                    ],
                    'success' => [
                        'ajout' => isset($_GET['ajout']) && $_GET['ajout'] === 'success',
                        'modif' => isset($_GET['modif']) && $_GET['modif'] === 'success',
                        'suppression' => isset($_GET['suppression']) && $_GET['suppression'] === 'success'
                    ],
                    'erreur' => [
                        'suppression' => isset($_GET['suppression']) && $_GET['suppression'] === 'error'
                    ]
                ]);
                break;
        }
    } else {
        header('Location: index.php?page=boutique'); 
        exit;
    }
}
?>
