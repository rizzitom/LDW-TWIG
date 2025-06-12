<?php

function adminControleur($twig, $db) {
    // On utilise le middleware de sécurité pour vérifier l'accès admin
    require_once __DIR__ . '/../middleware/security.php';
    
    // Vérifie les permissions d'administrateur avec toutes les validations de sécurité
    if (!verifyAdminAccess()) {
        // Si l'accès est refusé, la fonction verifyAdminAccess redirige déjà l'utilisateur
        exit;
    }
    
    // Si l'IP n'est pas autorisée, bloquer l'accès
    if (!isIpAllowedForAdmin()) {
        logFailedAdminAttempt('IP non autorisée');
        destroySession();
        header('Location: index.php?page=connexion&error=ip_not_allowed');
        exit;
    }
    
    $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
    
    switch ($section) {
        case 'dashboard':
            // Statistiques pour le tableau de bord
            $stats = getAdminStats($db);
            
            // Récentes commandes
            $stmt = $db->prepare("SELECT c.*, u.nom, u.prenom, u.email 
                                FROM commandes c 
                                JOIN utilisateurs u ON c.id_utilisateur = u.id 
                                ORDER BY c.date_commande DESC LIMIT 5");
            $stmt->execute();
            $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Produits en stock faible
            $stmt = $db->prepare("SELECT id, nom, stock, prix FROM produits WHERE stock < 5 AND est_actif = 1 ORDER BY stock ASC LIMIT 5");
            $stmt->execute();
            $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Demandes de devis récentes
            $stmt = $db->prepare("SELECT d.*, u.nom, u.prenom, u.email, s.nom as service_nom 
                                FROM devis d 
                                JOIN utilisateurs u ON d.id_utilisateur = u.id 
                                JOIN services s ON d.id_service = s.id
                                ORDER BY d.date_demande DESC LIMIT 5");
            $stmt->execute();
            $recent_quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Données pour les graphiques
            $sales_data = getSalesData($db);
            
            echo $twig->render('admin/dashboard.twig', [
                'admin_page' => 'dashboard',
                'stats' => $stats,
                'recent_orders' => $recent_orders,
                'low_stock' => $low_stock,
                'recent_quotes' => $recent_quotes,
                'sales_data' => $sales_data
            ]);
            break;
            
        case 'commandes':
            // Gestion de la mise à jour de statut depuis la liste
            if (isset($_GET['update_status']) && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = (int)$_GET['id'];
                $nouveau_statut = $_POST['nouveau_statut'];
                
                if ($id > 0 && !empty($nouveau_statut)) {
                    $stmt = $db->prepare("UPDATE commandes SET statut = :statut WHERE id = :id");
                    $stmt->bindValue(':statut', $nouveau_statut);
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        // Journaliser l'action
                        logAdminActivity("Modification du statut de la commande #$id: $nouveau_statut");
                        
                        // Redirection pour éviter double soumission
                        header('Location: index.php?page=admin&section=commandes&success=status_updated');
                        exit;
                    }
                }
                
                // En cas d'erreur
                header('Location: index.php?page=admin&section=commandes&error=update_failed');
                exit;
            }
            
            // Pagination
            $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
            $limite = 20;
            
            // Filtres
            $whereClause = "";
            $params = [];
            
            if (isset($_GET['statut']) && !empty($_GET['statut'])) {
                $whereClause .= " AND c.statut = :statut";
                $params[':statut'] = $_GET['statut'];
            }
            
            if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
                $whereClause .= " AND c.date_commande >= :date_debut";
                $params[':date_debut'] = $_GET['date_debut'] . " 00:00:00";
            }
            
            if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
                $whereClause .= " AND c.date_commande <= :date_fin";
                $params[':date_fin'] = $_GET['date_fin'] . " 23:59:59";
            }
            
            if (isset($_GET['recherche']) && !empty($_GET['recherche'])) {
                $whereClause .= " AND (c.reference LIKE :recherche OR u.nom LIKE :recherche OR u.email LIKE :recherche)";
                $params[':recherche'] = "%" . $_GET['recherche'] . "%";
            }
            
            // Comptage total
            $query = "SELECT COUNT(*) as total FROM commandes c JOIN utilisateurs u ON c.id_utilisateur = u.id WHERE 1=1" . $whereClause;
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_total = $count['total'];
            
            // Récup des commandes
            $query = "SELECT c.*, u.nom, u.prenom, u.email 
                      FROM commandes c 
                      JOIN utilisateurs u ON c.id_utilisateur = u.id 
                      WHERE 1=1" . $whereClause . " 
                      ORDER BY c.date_commande DESC 
                      LIMIT :limite OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $offset = ($page - 1) * $limite;
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pagination
            $nombre_pages = ceil($nombre_total / $limite);
            
            echo $twig->render('admin/commandes.twig', [
                'admin_page' => 'orders',
                'commandes' => $commandes,
                'pagination' => [
                    'page_courante' => $page,
                    'nombre_pages' => $nombre_pages,
                    'nombre_total' => $nombre_total
                ],
                'filtres' => [
                    'statut' => isset($_GET['statut']) ? $_GET['statut'] : '',
                    'date_debut' => isset($_GET['date_debut']) ? $_GET['date_debut'] : '',
                    'date_fin' => isset($_GET['date_fin']) ? $_GET['date_fin'] : '',
                    'recherche' => isset($_GET['recherche']) ? $_GET['recherche'] : ''
                ],
                'success' => isset($_GET['success']) ? $_GET['success'] : null,
                'error' => isset($_GET['error']) ? $_GET['error'] : null
            ]);
            break;
            
        case 'commande-detail':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if (!$id) {
                header('Location: index.php?page=admin&section=commandes');
                exit;
            }
            
            // Récupération des informations de la commande
            $stmt = $db->prepare("SELECT c.*, u.nom, u.prenom, u.email, u.telephone 
                                  FROM commandes c 
                                  JOIN utilisateurs u ON c.id_utilisateur = u.id 
                                  WHERE c.id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $commande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$commande) {
                header('Location: index.php?page=admin&section=commandes');
                exit;
            }
            
            // Récupération des produits de la commande
            $stmt = $db->prepare("SELECT cp.*, p.nom, p.image_url 
                                  FROM commande_produit cp 
                                  JOIN produits p ON cp.id_produit = p.id 
                                  WHERE cp.id_commande = :id_commande");
            $stmt->bindValue(':id_commande', $id, PDO::PARAM_INT);
            $stmt->execute();
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Générer un token CSRF pour les formulaires
            $csrf_token = generateCsrfToken();
            
            // Modification du statut si demandé
            if (isset($_POST['nouveau_statut']) && isset($_POST['csrf_token'])) {
                // Vérifier le token CSRF
                if (validateCsrfToken($_POST['csrf_token'])) {
                    $nouveau_statut = $_POST['nouveau_statut'];
                    $stmt = $db->prepare("UPDATE commandes SET statut = :statut WHERE id = :id");
                    $stmt->bindValue(':statut', $nouveau_statut);
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Journaliser l'action
                    logAdminActivity("Modification du statut de la commande #$id: $nouveau_statut");
                    
                    // Redirection pour éviter double soumission
                    header('Location: index.php?page=admin&section=commande-detail&id=' . $id . '&statut_updated=1');
                    exit;
                } else {
                    // Token CSRF invalide
                    logSecurityEvent("Tentative de modification de commande avec token CSRF invalide");
                    header('Location: index.php?page=admin&section=commande-detail&id=' . $id . '&error=csrf');
                    exit;
                }
            }
            
            echo $twig->render('admin/commande-detail.twig', [
                'admin_page' => 'orders',
                'commande' => $commande,
                'produits' => $produits,
                'statut_updated' => isset($_GET['statut_updated']),
                'csrf_token' => $csrf_token,
                'error' => isset($_GET['error']) ? $_GET['error'] : null
            ]);
            break;
            
        case 'devis':
            // Pagination
            $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
            $limite = 20;
            
            // Filtres éventuels
            $whereClause = "";
            $params = [];
            
            if (isset($_GET['statut']) && !empty($_GET['statut'])) {
                $whereClause .= " AND d.statut = :statut";
                $params[':statut'] = $_GET['statut'];
            }
            
            if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
                $whereClause .= " AND d.date_demande >= :date_debut";
                $params[':date_debut'] = $_GET['date_debut'] . " 00:00:00";
            }
            
            if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
                $whereClause .= " AND d.date_demande <= :date_fin";
                $params[':date_fin'] = $_GET['date_fin'] . " 23:59:59";
            }
            
            if (isset($_GET['service']) && !empty($_GET['service'])) {
                $whereClause .= " AND d.id_service = :service";
                $params[':service'] = (int)$_GET['service'];
            }
            
            // Comptage total
            $query = "SELECT COUNT(*) as total FROM devis d WHERE 1=1" . $whereClause;
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_total = $count['total'];
            
            // Récupération des devis
            $query = "SELECT d.*, u.nom, u.prenom, u.email, s.nom as service_nom 
                      FROM devis d 
                      JOIN utilisateurs u ON d.id_utilisateur = u.id 
                      JOIN services s ON d.id_service = s.id
                      WHERE 1=1" . $whereClause . " 
                      ORDER BY d.date_demande DESC 
                      LIMIT :limite OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $offset = ($page - 1) * $limite;
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Liste des services pour le filtre
            $stmt = $db->prepare("SELECT id, nom FROM services ORDER BY nom");
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pagination
            $nombre_pages = ceil($nombre_total / $limite);
            
            echo $twig->render('admin/devis-liste.twig', [
                'admin_page' => 'quotes',
                'devis' => $devis,
                'services' => $services,
                'pagination' => [
                    'page_courante' => $page,
                    'nombre_pages' => $nombre_pages,
                    'nombre_total' => $nombre_total
                ],
                'filtres' => [
                    'statut' => isset($_GET['statut']) ? $_GET['statut'] : '',
                    'date_debut' => isset($_GET['date_debut']) ? $_GET['date_debut'] : '',
                    'date_fin' => isset($_GET['date_fin']) ? $_GET['date_fin'] : '',
                    'service' => isset($_GET['service']) ? $_GET['service'] : ''
                ]
            ]);
            break;
            
        case 'devis-detail':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if (!$id) {
                header('Location: index.php?page=admin&section=devis');
                exit;
            }
            
            // Récupération des informations du devis
            $stmt = $db->prepare("SELECT d.*, u.nom, u.prenom, u.email, u.telephone, s.nom as service_nom, s.prix_base
                                  FROM devis d 
                                  JOIN utilisateurs u ON d.id_utilisateur = u.id 
                                  JOIN services s ON d.id_service = s.id
                                  WHERE d.id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $devis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$devis) {
                header('Location: index.php?page=admin&section=devis');
                exit;
            }
            
            // Traitement du formulaire de réponse
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nouveau_statut = $_POST['statut'];
                $montant_estime = $_POST['montant_estime'];
                $notes_admin = $_POST['notes_admin'];
                
                $stmt = $db->prepare("UPDATE devis SET 
                                     statut = :statut, 
                                     montant_estime = :montant_estime, 
                                     notes_admin = :notes_admin,
                                     date_reponse = NOW() 
                                     WHERE id = :id");
                $stmt->bindValue(':statut', $nouveau_statut);
                $stmt->bindValue(':montant_estime', $montant_estime);
                $stmt->bindValue(':notes_admin', $notes_admin);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Redirection pour éviter double soumission
                header('Location: index.php?page=admin&section=devis-detail&id=' . $id . '&updated=1');
                exit;
            }
            
            echo $twig->render('admin/devis-detail.twig', [
                'admin_page' => 'quotes',
                'devis' => $devis,
                'updated' => isset($_GET['updated'])
            ]);
            break;
            
        case 'messages':
            // Cette section pourrait gérer les messages de contact
            // Pour l'instant, nous utiliserons un template de base
            echo $twig->render('admin/messages.twig', [
                'admin_page' => 'messages'
            ]);
            break;
            
        case 'statistiques':
            // Récupération des données pour les statistiques avancées
            $stats = getAdvancedStats($db);
            
            echo $twig->render('admin/statistiques.twig', [
                'admin_page' => 'statistics',
                'stats' => $stats
            ]);
            break;
            
        case 'parametres':
            // Gestion des paramètres du site
            echo $twig->render('admin/parametres.twig', [
                'admin_page' => 'settings'
            ]);
            break;
            
        default:
            // Redirection vers le tableau de bord par défaut
            header('Location: index.php?page=admin');
            exit;
    }
}

/**
 * Récupère les statistiques de base pour le tableau de bord
 */
function getAdminStats($db) {
    $stats = [];
    
    // Nombre total de commandes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM commandes");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_commandes'] = $result['total'];
    
    // Nombre de commandes en cours
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM commandes WHERE statut NOT IN ('livree', 'annulee')");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['commandes_en_cours'] = $result['total'];
    
    // Chiffre d'affaires total
    $stmt = $db->prepare("SELECT SUM(montant_total) as ca_total FROM commandes WHERE statut != 'annulee'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['ca_total'] = $result['ca_total'] ?: 0;
    
    // Chiffre d'affaires du mois courant
    $debut_mois = date('Y-m-01');
    $fin_mois = date('Y-m-t');
    $stmt = $db->prepare("SELECT SUM(montant_total) as ca_mois FROM commandes 
                          WHERE statut != 'annulee' 
                          AND date_commande BETWEEN :debut AND :fin");
    $stmt->bindValue(':debut', $debut_mois . ' 00:00:00');
    $stmt->bindValue(':fin', $fin_mois . ' 23:59:59');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['ca_mois'] = $result['ca_mois'] ?: 0;
    
    // Nombre total d'utilisateurs
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM utilisateurs WHERE idRole = 2");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_clients'] = $result['total'];
    
    // Nouveaux clients du mois
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM utilisateurs 
                          WHERE idRole = 2 
                          AND date_inscription BETWEEN :debut AND :fin");
    $stmt->bindValue(':debut', $debut_mois . ' 00:00:00');
    $stmt->bindValue(':fin', $fin_mois . ' 23:59:59');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['nouveaux_clients'] = $result['total'];
    
    // Nombre de produits en stock
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produits WHERE est_actif = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['produits_actifs'] = $result['total'];
    
    // Nombre de produits en rupture
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produits WHERE stock <= 0 AND est_actif = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['produits_rupture'] = $result['total'];
    
    // Nombre de devis en attente
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM devis WHERE statut = 'en_attente'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['devis_attente'] = $result['total'];
    
    return $stats;
}

/**
 * Récupère les données de ventes par période pour les graphiques
 */
function getSalesData($db) {
    $data = [];
    
    // Ventes des 12 derniers mois
    $months = [];
    $sales = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime($month));
        
        $start = $month . '-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($month));
        
        $stmt = $db->prepare("SELECT SUM(montant_total) as total 
                             FROM commandes 
                             WHERE statut != 'annulee' 
                             AND date_commande BETWEEN :start AND :end");
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sales[] = $result['total'] ? round($result['total'], 2) : 0;
    }
    
    $data['monthly'] = [
        'labels' => $months,
        'values' => $sales
    ];
    
    // Répartition des ventes par catégorie (top 5)
    $stmt = $db->prepare("SELECT c.nom, SUM(cp.prix_unitaire * cp.quantite) as total
                         FROM commande_produit cp
                         JOIN produits p ON cp.id_produit = p.id
                         JOIN categories c ON p.id_categorie = c.id
                         JOIN commandes cmd ON cp.id_commande = cmd.id
                         WHERE cmd.statut != 'annulee'
                         GROUP BY c.id
                         ORDER BY total DESC
                         LIMIT 5");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $category_names = [];
    $category_values = [];
    
    foreach ($categories as $cat) {
        $category_names[] = $cat['nom'];
        $category_values[] = round($cat['total'], 2);
    }
    
    $data['categories'] = [
        'labels' => $category_names,
        'values' => $category_values
    ];
    
    return $data;
}

/**
 * Récupère les statistiques avancées pour l'analyse
 */
function getAdvancedStats($db) {
    $stats = [];
    
    // Statistiques de base
    $stats['basic'] = getAdminStats($db);
    
    // Ventes par jour de la semaine
    $stmt = $db->prepare("
        SELECT 
            DAYOFWEEK(date_commande) as jour, 
            COUNT(*) as nombre_commandes, 
            SUM(montant_total) as total_ventes
        FROM commandes
        WHERE statut != 'annulee'
        GROUP BY jour
        ORDER BY jour
    ");
    $stmt->execute();
    $ventes_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Conversion en tableau avec les noms des jours
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $stats['ventes_jour'] = [];
    
    foreach ($ventes_par_jour as $vente) {
        $jour_index = $vente['jour'] - 1; // DAYOFWEEK commence à 1 (dimanche)
        $stats['ventes_jour'][] = [
            'jour' => $jours[$jour_index],
            'nombre_commandes' => $vente['nombre_commandes'],
            'total_ventes' => $vente['total_ventes']
        ];
    }
    
    // Panier moyen
    $stmt = $db->prepare("
        SELECT AVG(montant_total) as panier_moyen
        FROM commandes
        WHERE statut != 'annulee'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['panier_moyen'] = $result['panier_moyen'] ?: 0;
    
    // Produits les plus vendus
    $stmt = $db->prepare("
        SELECT 
            p.id, p.nom, p.image_url, 
            SUM(cp.quantite) as total_vendu, 
            SUM(cp.prix_unitaire * cp.quantite) as total_ca
        FROM commande_produit cp
        JOIN produits p ON cp.id_produit = p.id
        JOIN commandes c ON cp.id_commande = c.id
        WHERE c.statut != 'annulee'
        GROUP BY p.id
        ORDER BY total_vendu DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['produits_populaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Clients fidèles (avec le plus de commandes)
    $stmt = $db->prepare("
        SELECT 
            u.id, u.nom, u.prenom, u.email,
            COUNT(c.id) as nombre_commandes,
            SUM(c.montant_total) as total_depense
        FROM utilisateurs u
        JOIN commandes c ON u.id = c.id_utilisateur
        WHERE c.statut != 'annulee'
        GROUP BY u.id
        ORDER BY nombre_commandes DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['clients_fideles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Évolution mensuelle du CA sur les 12 derniers mois (pour graphique)
    $stats['evolution_ca'] = getSalesData($db)['monthly'];
    
    return $stats;
}
