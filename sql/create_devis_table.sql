-- Table pour stocker les demandes de devis
CREATE TABLE IF NOT EXISTS `devis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `statut` enum('en_attente','en_cours','termine','annule') NOT NULL DEFAULT 'en_attente',
  `date_creation` datetime NOT NULL,
  `date_modification` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_service` (`service_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter les contraintes de clés étrangères si les tables existent
-- ALTER TABLE `devis` ADD CONSTRAINT `fk_devis_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `devis` ADD CONSTRAINT `fk_devis_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
