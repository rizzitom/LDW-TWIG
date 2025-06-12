-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 12 juin 2025 à 12:16
-- Version du serveur : 8.2.0
-- Version de PHP : 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ldw`
--

-- --------------------------------------------------------

--
-- Structure de la table `adresses`
--

DROP TABLE IF EXISTS `adresses`;
CREATE TABLE IF NOT EXISTS `adresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `nom_adresse` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type_adresse` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nom_complet` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `rue` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `complement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code_postal` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pays` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `adresses`
--

INSERT INTO `adresses` (`id`, `id_utilisateur`, `nom_adresse`, `type_adresse`, `nom_complet`, `rue`, `complement`, `code_postal`, `ville`, `pays`, `telephone`, `is_default`, `date_creation`, `date_modification`) VALUES
(1, 2, '4 Lotissement Les Bastides Du Colombier, Orgon', 'livraison', 'Tom Rizzi', '4 lots les bastides du colombier', '', '13660', 'Orgon', 'France', '0622516629', 1, '2025-04-14 14:53:55', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `slug` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `parent_id`, `image_url`, `est_actif`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Informatique', 'Tous les produits informatiques', NULL, '/public/images/pc.PNG', 1, 'informatique', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(2, 'Périphériques', 'Accessoires et périphériques pour ordinateurs', 1, '/public/images/périphérique.PNG', 1, 'peripheriques', '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(3, 'Ordinateurs', 'Ordinateurs portables et PC de bureau', 1, '/public/images/pcportables.PNG', 1, 'ordinateurs', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(4, 'Composants', 'Composants informatiques pour PC', 1, '/public/images/composants.PNG', 1, 'composants', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(5, 'Image & Son', 'Équipements audio et vidéo', NULL, '/public/images/son.PNG', 1, 'image-et-son', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(6, 'TV & Écrans', 'Télévisions et moniteurs', 5, '/public/images/tv.PNG', 1, 'televiseurs', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(7, 'Audio', 'Casques, enceintes et équipement audio', 5, '/public/images/son.PNG', 1, 'audio', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(8, 'Accessoires', 'Accessoires divers', NULL, '/public/images/smartphones.PNG', 1, 'telephonie', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(9, 'Smartphones', 'Téléphones mobiles et smartphones', 8, '/public/images/smartphones.PNG', 1, 'smartphones', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(10, 'Reconditionné', 'Produits reconditionnés', NULL, NULL, 1, 'seconde-vie', '2025-04-14 10:52:34', '2025-04-14 11:05:48'),
(11, 'PC sur-mesure', 'PC composés et assemblés sur mesure par nos soins', 3, NULL, 1, 'pc-sur-mesure', '2025-06-11 12:19:11', '2025-06-11 12:19:11');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `date_commande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','validee','en_preparation','expediee','livree','annulee') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `montant_total` decimal(10,2) NOT NULL,
  `adresse_livraison` text COLLATE utf8mb4_general_ci NOT NULL,
  `adresse_facturation` text COLLATE utf8mb4_general_ci NOT NULL,
  `methode_paiement` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `motif_annulation` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `commentaire_annulation` text COLLATE utf8mb4_general_ci,
  `note_client` int DEFAULT NULL,
  `commentaire_client` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `id_utilisateur`, `date_commande`, `statut`, `montant_total`, `adresse_livraison`, `adresse_facturation`, `methode_paiement`, `reference`, `notes`, `motif_annulation`, `commentaire_annulation`, `note_client`, `commentaire_client`) VALUES
(1, 2, '2025-04-14 14:43:37', 'expediee', 2598.00, '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', 'carte', 'CMD-20250414-0001', 'test', NULL, NULL, NULL, NULL),
(2, 2, '2025-04-14 14:54:42', 'en_attente', 849.00, '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', 'carte', 'CMD-20250414-0002', '', NULL, NULL, NULL, NULL),
(3, 2, '2025-04-14 16:25:08', 'en_attente', 1.00, '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', 'carte', 'CMD-20250414-0003', '', NULL, NULL, NULL, NULL),
(4, 2, '2025-04-14 16:55:21', 'en_attente', 1.00, '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', '4 Lotissement Les Bastides Du Colombier, Orgon\n13660 Orgon', 'paypal', 'CMD-20250414-0004', '', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande_produit`
--

DROP TABLE IF EXISTS `commande_produit`;
CREATE TABLE IF NOT EXISTS `commande_produit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_commande` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_commande` (`id_commande`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande_produit`
--

INSERT INTO `commande_produit` (`id`, `id_commande`, `id_produit`, `quantite`, `prix_unitaire`) VALUES
(1, 1, 21, 2, 1299.00),
(2, 2, 22, 1, 849.00),
(3, 3, 27, 1, 1.00),
(4, 4, 27, 1, 1.00);

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('general','commande','devis','support') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `reference_commande` int DEFAULT NULL,
  `reference_devis` int DEFAULT NULL,
  `statut` enum('ouvert','en_attente','resolu','ferme') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ouvert',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_activite` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `reference_commande` (`reference_commande`),
  KEY `reference_devis` (`reference_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

DROP TABLE IF EXISTS `devis`;
CREATE TABLE IF NOT EXISTS `devis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_service` int NOT NULL,
  `description_demande` text COLLATE utf8mb4_general_ci NOT NULL,
  `statut` enum('en_attente','en_cours','termine','rejete') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `montant_estime` decimal(10,2) DEFAULT NULL,
  `notes_admin` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_service` (`id_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

DROP TABLE IF EXISTS `factures`;
CREATE TABLE IF NOT EXISTS `factures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_commande` int NOT NULL,
  `numero_facture` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_emission` datetime NOT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `montant_tva` decimal(10,2) NOT NULL,
  `montant_ttc` decimal(10,2) NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_factures_commande` (`id_commande`),
  KEY `idx_factures_numero` (`numero_facture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_actions`
--

DROP TABLE IF EXISTS `historique_actions`;
CREATE TABLE IF NOT EXISTS `historique_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `type_action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_commande`
--

DROP TABLE IF EXISTS `historique_commande`;
CREATE TABLE IF NOT EXISTS `historique_commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_commande` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_commande` (`id_commande`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_commande`
--

INSERT INTO `historique_commande` (`id`, `id_commande`, `id_utilisateur`, `action`, `details`, `date_action`) VALUES
(1, 1, 2, 'creation', 'Commande créée avec un montant total de 2598.00€', '2025-04-14 14:47:13'),
(2, 1, 1, 'test_script', 'Test de fonctionnalité via script de test', '2025-04-14 14:47:54'),
(3, 1, 2, 'facture_generee', 'Facture n°FACT-20250414-0001 générée', '2025-04-14 16:56:43'),
(4, 2, 2, 'facture_generee', 'Facture n°FACT-20250414-0002 générée', '2025-04-14 16:57:01'),
(5, 4, 2, 'facture_generee', 'Facture n°FACT-20250415-0001 générée', '2025-04-15 08:33:46');

-- --------------------------------------------------------

--
-- Structure de la table `historique_connexions`
--

DROP TABLE IF EXISTS `historique_connexions`;
CREATE TABLE IF NOT EXISTS `historique_connexions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `succes` tinyint(1) DEFAULT '1',
  `date_connexion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_conversation` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `contenu` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_client` tinyint(1) NOT NULL DEFAULT '1',
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_conversation` (`id_conversation`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_commande` int NOT NULL,
  `payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `date_paiement` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_paiements_commande` (`id_commande`),
  KEY `idx_paiements_payment_intent` (`payment_intent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id`, `id_commande`, `payment_intent_id`, `statut`, `montant`, `details`, `date_paiement`) VALUES
(1, 1, 'pi_3RFKMEG7RjirpzdI0Tbm5Gad', 'test', 1.00, '{\"test\":true,\"method\":\"card\"}', '2025-04-18 21:13:51');

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

DROP TABLE IF EXISTS `panier`;
CREATE TABLE IF NOT EXISTS `panier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `date_ajout` datetime NOT NULL,
  `date_modification` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateur_produit` (`id_utilisateur`,`id_produit`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panier_temp`
--

DROP TABLE IF EXISTS `panier_temp`;
CREATE TABLE IF NOT EXISTS `panier_temp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `date_ajout` datetime NOT NULL,
  `date_modification` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_produit` (`session_id`,`id_produit`),
  KEY `id_produit` (`id_produit`),
  KEY `idx_panier_temp_date` (`date_modification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

DROP TABLE IF EXISTS `pieces_jointes`;
CREATE TABLE IF NOT EXISTS `pieces_jointes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_message` int NOT NULL,
  `id_conversation` int NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `chemin_fichier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_message` (`id_message`),
  KEY `id_conversation` (`id_conversation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Type de PC (Gaming/Bureautique)',
  `description` text COLLATE utf8mb4_general_ci,
  `description_courte` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `composants` text COLLATE utf8mb4_general_ci COMMENT 'Liste des composants (JSON)',
  `prix` decimal(10,2) NOT NULL,
  `prix_revient` decimal(10,2) DEFAULT NULL COMMENT 'Prix de revient du produit',
  `prix_promo` decimal(10,2) DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `id_categorie` int NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `images_supplementaires` text COLLATE utf8mb4_general_ci,
  `caracteristiques` text COLLATE utf8mb4_general_ci,
  `infos_performance` text COLLATE utf8mb4_general_ci COMMENT 'Informations de performance (JSON, pour Gaming)',
  `est_actif` tinyint(1) DEFAULT '1',
  `est_featured` tinyint(1) DEFAULT '0',
  `statut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'en_stock' COMMENT 'Statut du produit (en_stock, epuise)',
  `slug` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `id_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `type`, `description`, `description_courte`, `composants`, `prix`, `prix_revient`, `prix_promo`, `stock`, `id_categorie`, `image_url`, `images_supplementaires`, `caracteristiques`, `infos_performance`, `est_actif`, `est_featured`, `statut`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Ordinateur Portable Pro', NULL, 'Cet ordinateur portable professionnel est équipé d\'un processeur dernière génération, 16 Go de RAM et un SSD de 512 Go pour des performances exceptionnelles.', 'Puissant ordinateur portable pour professionnels', NULL, 999.99, NULL, NULL, 15, 3, '/public/images/pcportables.PNG', NULL, NULL, NULL, 1, 1, 'en_stock', 'ordinateur-portable-pro', '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(2, 'Casque Audio Premium', NULL, 'Profitez d\'une qualité audio exceptionnelle avec ce casque sans fil doté de la technologie de réduction de bruit active.', 'Casque sans fil avec réduction de bruit active', NULL, 249.99, NULL, NULL, 30, 7, '/public/images/casque.png', NULL, NULL, NULL, 1, 1, 'en_stock', 'casque-audio-premium', '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(3, 'Smartphone Latest Gen', NULL, 'Le smartphone de dernière génération avec un appareil photo exceptionnel, un écran AMOLED et une batterie longue durée.', 'Le dernier smartphone haute performance', NULL, 849.99, NULL, NULL, 20, 9, '/public/images/smartphones.PNG', NULL, NULL, NULL, 1, 1, 'en_stock', 'smartphone-latest-gen', '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(4, 'Écran 27\" Ultra HD', NULL, 'Cet écran 4K de 27 pouces offre des couleurs vibrantes et une résolution exceptionnelle pour le travail et les loisirs.', 'Moniteur 4K pour une expérience visuelle immersive', NULL, 349.99, NULL, NULL, 12, 2, '/public/images/tv.PNG', NULL, NULL, NULL, 1, 0, 'en_stock', 'ecran-27-ultra-hd', '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(21, 'Laptop Gaming Pro', NULL, 'Le Laptop Gaming Pro est équipé d\'un écran 15.6\" Full HD 144Hz, d\'un processeur Intel Core i7-12700H, de 16GB RAM DDR4, d\'un SSD NVMe 512GB et d\'une carte graphique NVIDIA RTX 3070 8GB. Idéal pour les joueurs exigeants.', 'Ordinateur portable gaming avec écran 15\", processeur i7, 16GB RAM, SSD 512GB', NULL, 1299.00, NULL, NULL, 13, 3, '/public/images/pc.PNG', NULL, '{\"Processeur\":\"Intel Core i7-12700H\",\"RAM\":\"16GB DDR4\",\"Stockage\":\"SSD NVMe 512GB\",\"Écran\":\"15.6\" Full HD 144Hz\",\"Carte graphique\":\"NVIDIA RTX 3070 8GB\",\"Système d\'exploitation\":\"Windows 11 Pro\"}', NULL, 1, 1, 'en_stock', 'laptop-gaming-pro', '2025-04-14 11:06:14', '2025-04-14 14:43:37'),
(22, 'Smartphone Premium X12', NULL, 'Le Smartphone Premium X12 offre un écran AMOLED 6.5\" 120Hz, un processeur octa-core performant, 12GB RAM, 256GB de stockage, un appareil photo principal 108MP et une batterie 5000mAh avec charge rapide 65W.', 'Smartphone haut de gamme avec écran 6.5\", appareil photo 108MP, batterie 5000mAh', NULL, 999.00, NULL, 849.00, 19, 9, '/public/images/smartphones.PNG', NULL, '{\"Processeur\":\"Octa-core 3.0GHz\",\"RAM\":\"12GB\",\"Stockage\":\"256GB\",\"Écran\":\"6.5\" AMOLED 120Hz\",\"Appareil photo\":\"108MP + 12MP + 8MP\",\"Batterie\":\"5000mAh\"}', NULL, 1, 1, 'en_stock', 'smartphone-premium-x12', '2025-04-14 11:06:14', '2025-04-14 14:54:42'),
(23, 'Télévision 4K Smart 55\"', NULL, 'Cette télévision 4K offre une qualité d\'image exceptionnelle grâce à sa technologie HDR10+ et son écran QLED. Elle dispose de fonctionnalités smart TV complètes, d\'un système audio premium avec Dolby Atmos et d\'une connectivité étendue.', 'Télévision 4K UHD avec connectivité smart, HDR et son premium intégré', NULL, 799.00, NULL, NULL, 12, 6, '/public/images/tv.PNG', NULL, '{\"Taille\":\"55 pouces\",\"Résolution\":\"4K UHD (3840x2160)\",\"Technologie\":\"QLED\",\"HDR\":\"HDR10+\",\"Smart TV\":\"Oui\",\"Audio\":\"Dolby Atmos\"}', NULL, 1, 1, 'en_stock', 'television-4k-smart-55', '2025-04-14 11:06:14', '2025-04-14 11:06:14'),
(24, 'Écouteurs Sans Fil Bluetooth', NULL, 'Ces écouteurs sans fil offrent une qualité sonore exceptionnelle, une réduction de bruit active, une autonomie de 8h par charge (24h avec le boîtier), la résistance à l\'eau et à la transpiration (IPX4) et une connexion Bluetooth 5.2 stable.', 'Écouteurs intra-auriculaires avec réduction de bruit et résistance à l\'eau', NULL, 149.00, NULL, 129.00, 25, 7, '/public/images/casque.png', NULL, '{\"Type\":\"Intra-auriculaires\",\"Réduction de bruit\":\"Active\",\"Autonomie\":\"8h (24h avec boîtier)\",\"Connectivité\":\"Bluetooth 5.2\",\"Résistance\":\"IPX4\",\"Commandes\":\"Tactiles\"}', NULL, 1, 0, 'en_stock', 'ecouteurs-sans-fil-bluetooth', '2025-04-14 11:06:14', '2025-04-14 11:06:14'),
(25, 'Console de Jeu NextGen', NULL, 'La console de jeu NextGen offre des graphismes en 4K à 60fps, un SSD ultra-rapide de 1TB, une rétrocompatibilité avec les jeux précédents, un contrôleur à retour haptique et une expérience de jeu immersive.', 'Console de jeu dernière génération avec graphismes 4K et SSD ultra-rapide', NULL, 499.00, NULL, NULL, 10, 3, '/public/images/playstation5.jpg', NULL, '{\"Résolution\":\"4K\",\"Fréquence d\'images\":\"60fps\",\"Stockage\":\"SSD 1TB\",\"Audio\":\"3D Audio\",\"Contrôleur\":\"Haptique\",\"Connectivité\":\"Wi-Fi 6, HDMI 2.1\"}', NULL, 1, 0, 'en_stock', 'console-jeu-nextgen', '2025-04-14 11:06:14', '2025-04-14 11:06:14'),
(26, 'Tablette Premium Pro', NULL, 'Cette tablette haut de gamme est équipée d\'un écran 11\" 120Hz, d\'un processeur puissant, de 8GB RAM, 128GB de stockage, d\'un appareil photo 12MP, d\'une batterie longue durée et du support pour stylet.', 'Tablette 11\" avec écran 120Hz et processeur haute performance', NULL, 599.00, NULL, 549.00, 18, 9, '/public/images/apple.PNG', NULL, '{\"Processeur\":\"Octa-core 2.5GHz\",\"RAM\":\"8GB\",\"Stockage\":\"128GB\",\"Écran\":\"11\" 120Hz\",\"Appareil photo\":\"12MP\",\"Batterie\":\"8000mAh\"}', NULL, 1, 0, 'en_stock', 'tablette-premium-pro', '2025-04-14 11:06:14', '2025-04-14 11:06:14'),
(27, 'Montre Connectée Sport', NULL, 'Cette montre connectée offre un suivi de santé complet (fréquence cardiaque, SpO2, sommeil), plus de 100 modes sportifs, un GPS intégré, une autonomie de 14 jours, un écran AMOLED et une résistance à l\'eau jusqu\'à 50m.', 'Montre intelligente avec suivi de santé, GPS et autonomie de 14 jours', NULL, 1.00, NULL, 1.00, 28, 8, '/public/images/composants.PNG', NULL, '{\"Écran\":\"AMOLED 1.43\"\",\"Autonomie\":\"14 jours\",\"GPS\":\"Intégré\",\"Étanchéité\":\"5 ATM\",\"Capteurs\":\"Cardiaque, SpO2, Accéléromètre\",\"Connectivité\":\"Bluetooth 5.0\"}', NULL, 1, 0, 'en_stock', 'montre-connectee-sport', '2025-04-14 11:06:14', '2025-04-14 16:55:22'),
(28, 'PC Gaming RTX 5060', NULL, 'PC Gaming série limitée, composants haut de gamme, stock limité.', 'PC Gaming série limitée, composants haut de gamme, stock limité.', NULL, 1197.44, NULL, NULL, 10, 11, NULL, '[]', '[]', NULL, 1, 0, 'en_stock', 'pc-gaming-rtx-5060', '2025-06-11 12:19:12', '2025-06-12 11:34:45'),
(29, 'PC Bureautique RTX 3060', 'bureautique', 'PC Bureautique premium, série limitée, stock limité.', 'PC Bureautique premium, série limitée, stock limité.', '{\"Boitier\":\"ATX AeroCool Rift\",\"Carte mère\":\"MSI B550\",\"CPU\":\"Ryzen 7 5700X (4.2GHz)\",\"GPU\":\"RTX 3060 8GB\",\"RAM\":\"16GB DDR5 6000MHz\",\"Alim\":\"AeroCool Lux 750W\",\"SSD\":\"NVMe 1To\",\"HDD\":\"aucun\",\"Ventirad\":\"Alpine 23\",\"Ventilo\":\"boitier\"}', 781.94, 679.95, NULL, 2, 11, '/images/produits/bureautique-rtx3060.jpg', NULL, NULL, NULL, 1, 0, 'en_stock', 'pc-bureautique-rtx-3060', '2025-06-11 12:19:12', '2025-06-11 12:19:12'),
(30, 'RIZZI', NULL, 'aa', 'PC Gaming série limitée, composants haut de gamme, stock limité.', NULL, 60.00, NULL, 60.00, 0, 5, NULL, NULL, NULL, NULL, 1, 1, 'en_stock', 'rizzi', '2025-06-12 11:37:39', '2025-06-12 11:37:39');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `libelle`, `created_at`) VALUES
(1, 'Administrateur', '2025-04-14 10:52:34'),
(2, 'Client', '2025-04-14 10:52:34');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `prix_base` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `nom`, `description`, `prix_base`, `image_url`, `est_actif`, `created_at`, `updated_at`) VALUES
(1, 'Développement Web', 'Création de sites web et applications sur mesure selon vos besoins.', 500.00, '/public/images/services/developpement.png', 1, '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(2, 'CopyWriting', 'Des textes persuasifs qui convertissent. Nous créons des contenus engageants pour votre site web, blog, réseaux sociaux et supports marketing.', 22.00, '/public/images/services/Copywriting.png', 1, '2025-04-14 10:52:34', '2025-05-08 11:53:00'),
(3, 'Montage PC sur mesure', 'Configuration et assemblage d\'ordinateurs personnalisés.', 150.00, '/public/images/services/Montage.png', 1, '2025-04-14 10:52:34', '2025-04-14 10:52:34'),
(4, 'SEO/SEA', 'Boostez votre visibilité en ligne. Notre expertise en référencement naturel (SEO) et référencement payant (SEA) vous permet d\'attirer un trafic ciblé et qualifié.', 25.00, '/public/images/services/SEO.png', 1, '2025-05-08 11:53:00', '2025-05-08 11:53:00');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `adresse` text COLLATE utf8mb4_general_ci,
  `code_postal` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ville` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idRole` int NOT NULL,
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `two_factor_secret` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `backup_code` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notif_new_connexion` tinyint(1) DEFAULT '1',
  `notif_password_change` tinyint(1) DEFAULT '1',
  `notif_failed_attempts` tinyint(1) DEFAULT '1',
  `remember_devices` tinyint(1) DEFAULT '0',
  `extended_session` tinyint(1) DEFAULT '0',
  `confirm_order_by_email` tinyint(1) DEFAULT '1',
  `points_fidelite` int DEFAULT '0',
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idRole` (`idRole`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `email`, `username`, `password`, `nom`, `prenom`, `adresse`, `code_postal`, `ville`, `telephone`, `idRole`, `date_inscription`, `derniere_connexion`, `two_factor_secret`, `two_factor_enabled`, `backup_code`, `notif_new_connexion`, `notif_password_change`, `notif_failed_attempts`, `remember_devices`, `extended_session`, `confirm_order_by_email`, `points_fidelite`, `profile_picture`) VALUES
(1, 'admin@example.com', 'admin', '$2y$10$9lBoPJgw7CIyF89Z5Y5zXe3QJvFvT5M2OYj9mIKhNRIJiyVmYT8o6', 'Admin', 'System', NULL, NULL, NULL, NULL, 1, '2025-04-14 10:52:34', NULL, NULL, 0, NULL, 1, 1, 1, 0, 0, 1, 0, NULL),
(2, 'rizzitom306@gmail.com', 'tomrizzi', '$2y$12$rpydianYkWSMUElFw6B4..rVChqClkzrKAg7eHJW9vbFhxGaGPijO', 'Rizzi', 'Tom', NULL, NULL, NULL, NULL, 1, '2025-04-14 10:53:40', NULL, '36T656EOOZOZKQ77', 0, '$2y$12$RAPzQtdeMbI9Y2Yf9IQee.FNFOuyM5fCts/9Dh.SIKZgfyYJSo4d2', 1, 1, 1, 0, 0, 1, 0, '/public/images/uploads/profile_pictures/user_2_1744705020.jpg'),
(3, 'admin@ledesignduweb.fr', 'admin', '$2y$12$C6Xhi/yNtUbbjvjzqlEDWePKNLrc/k/8iV3Tzx9FWwyNzzriKiX8y', 'Admin', 'Admin', NULL, NULL, NULL, NULL, 1, '2025-04-14 14:47:52', NULL, NULL, 0, NULL, 1, 1, 1, 0, 0, 1, 0, NULL),
(4, 'lucie.rizzi@gmail.com', 'lucie.rizzi', '21edeea9b08a13118fa1e7514a5b8ba29931fe5eb2db9a378cc17feac896ef19', 'Rizzi', 'Lucie', NULL, NULL, NULL, NULL, 2, '2025-05-10 16:30:23', NULL, NULL, 0, NULL, 1, 1, 1, 0, 0, 1, 0, NULL),
(5, 'lucierizzi@gmail.com', 'lucie.rizzi', 'Ldwpass1*', 'Rizzi', 'Lucie', NULL, NULL, NULL, NULL, 2, '2025-05-10 16:33:18', NULL, NULL, 0, NULL, 1, 1, 1, 0, 0, 1, 0, NULL),
(6, 'admin@ledesignduweb.com', 'admin', '$2y$12$M54xL/rMpCutG8Ee7VK8z.FsWIBlrEkt5jp2trGxBtW.QXE7a4DbW', 'admin', 'admin', 'Théorodre Aubanel', '84000', 'Avignon', '', 1, '2025-06-12 11:23:22', NULL, '2DKRMKBAHNWTR2IM', 0, '$2y$12$JhopmgzNHjb1cVHi3fEAGeKM5lC6VgGWof72yD1dhl8iHnbX5AgPC', 1, 1, 1, 0, 0, 1, 0, NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `adresses`
--
ALTER TABLE `adresses`
  ADD CONSTRAINT `adresses_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD CONSTRAINT `commande_produit_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `commande_produit_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`reference_commande`) REFERENCES `commandes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`reference_devis`) REFERENCES `devis` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `devis_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `services` (`id`);

--
-- Contraintes pour la table `historique_actions`
--
ALTER TABLE `historique_actions`
  ADD CONSTRAINT `historique_actions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_commande`
--
ALTER TABLE `historique_commande`
  ADD CONSTRAINT `historique_commande_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `historique_commande_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `historique_connexions`
--
ALTER TABLE `historique_connexions`
  ADD CONSTRAINT `historique_connexions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_conversation`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `panier_temp`
--
ALTER TABLE `panier_temp`
  ADD CONSTRAINT `panier_temp_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `pieces_jointes_ibfk_1` FOREIGN KEY (`id_message`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pieces_jointes_ibfk_2` FOREIGN KEY (`id_conversation`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`idRole`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
