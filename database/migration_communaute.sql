-- ====================================================================
-- MIGRATION MODULES COMMUNAUTAIRES, PASTORAUX & ENTRAIDE
-- ====================================================================

USE `eglise_app`;

-- 1. Table des messages et chat direct avec le pasteur
CREATE TABLE IF NOT EXISTS `messages_pasteur` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `nom_expediteur` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `sujet` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `canal_prefere` ENUM('app', 'whatsapp') DEFAULT 'whatsapp',
  `reponse_pasteur` TEXT DEFAULT NULL,
  `statut` ENUM('non_lu', 'en_cours', 'repondu', 'archive') DEFAULT 'non_lu',
  `date_reponse` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_past_statut` (`statut`),
  KEY `idx_msg_past_fidele` (`fidele_id`),
  CONSTRAINT `fk_msg_past_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des demandes de besoin et d'entraide (travail, finance, soutien émotionnel)
CREATE TABLE IF NOT EXISTS `besoins_entraide` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `nom_demandeur` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `type_besoin` ENUM('travail', 'finance', 'soutien_emotionnel', 'sante', 'autre') NOT NULL,
  `titre` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `anonyme` TINYINT(1) DEFAULT 1,
  `visible_communaute` TINYINT(1) DEFAULT 0,
  `statut` ENUM('en_attente', 'en_cours', 'pourvu', 'archive') DEFAULT 'en_attente',
  `traite_par_admin_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_besoins_type` (`type_besoin`),
  KEY `idx_besoins_statut` (`statut`),
  CONSTRAINT `fk_besoins_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_besoins_admin` FOREIGN KEY (`traite_par_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des publications communautaires ("Vivre Ensemble", Témoignages, Exhortations & Prestations Chorales)
CREATE TABLE IF NOT EXISTS `publications_communaute` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `auteur_nom` VARCHAR(100) NOT NULL,
  `type` ENUM('exhortation', 'temoignage', 'prestation_chorale', 'partage') NOT NULL DEFAULT 'partage',
  `titre` VARCHAR(200) NOT NULL,
  `contenu` TEXT NOT NULL,
  `verset_reference` VARCHAR(100) DEFAULT NULL,
  `verset_texte` TEXT DEFAULT NULL,
  `media_url` VARCHAR(500) DEFAULT NULL,
  `statut` ENUM('publie', 'en_attente_moderation', 'rejete') DEFAULT 'publie',
  `likes_count` INT UNSIGNED DEFAULT 0,
  `admin_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pub_type_statut` (`type`, `statut`),
  KEY `idx_pub_created` (`created_at`),
  CONSTRAINT `fk_pub_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des commentaires et réactions sur le mur communautaire
CREATE TABLE IF NOT EXISTS `commentaires_communaute` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `publication_id` INT NOT NULL,
  `fidele_id` INT DEFAULT NULL,
  `auteur_nom` VARCHAR(100) NOT NULL,
  `commentaire` TEXT NOT NULL,
  `statut` ENUM('publie', 'masque') DEFAULT 'publie',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comment_pub` (`publication_id`),
  CONSTRAINT `fk_comment_pub` FOREIGN KEY (`publication_id`) REFERENCES `publications_communaute` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales d'exemple pour la chorale et les partages
INSERT INTO `publications_communaute` (`id`, `auteur_nom`, `type`, `titre`, `contenu`, `verset_reference`, `verset_texte`, `media_url`, `statut`, `likes_count`) VALUES
(1, 'Chorale Principale', 'prestation_chorale', 'Prestation de Louange : « Que Ta Grâce Abonde »', 'Enregistrement de la prestation de louange de notre chorale lors du culte d\'actions de grâces. Soyez abondamment bénis par ces voix unies dans l\'adoration !', 'Psaume 150:6', 'Que tout ce qui respire loue l\'Éternel ! Louez l\'Éternel !', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'publie', 28),
(2, 'Département Média & Évangélisation', 'exhortation', 'Méditation : Dieu pourvoit au-delà de nos attentes', 'Bien-aimés, peu importe les incertitudes qui pèsent sur votre semaine, rappelez-vous que Dieu est notre Jéhovah Jiré. Confions-Lui nos fardeaux.', 'Philippiens 4:19', 'Et mon Dieu pourvoira à tous vos besoins selon sa richesse, avec gloire, en Jésus-Christ.', NULL, 'publie', 19),
(3, 'Sœur Marie-Claire', 'temoignage', 'Témoignage d\'Action de Grâce : Dieu a guéri mon fils', 'Je tiens à rendre grâce à Dieu et remercier toute l\'église pour les prières. Mon fils a reçu son congé d\'hôpital hier totalement rétabli ! Gloire à Jésus !', 'Psaume 103:2-3', 'Bénis l\'Éternel, mon âme, et n\'oublie aucun de ses bienfaits ! C\'est lui qui pardonne toutes tes iniquités, qui guérit toutes tes maladies.', NULL, 'publie', 34)
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

INSERT INTO `commentaires_communaute` (`id`, `publication_id`, `auteur_nom`, `commentaire`, `statut`) VALUES
(1, 1, 'Frère Jonathan', 'Magnifique louange ! Que Dieu bénisse nos chantres !', 'publie'),
(2, 3, 'David Koffi', 'Amen ! Que le Seigneur soit glorifié pour cette guérison merveilleuse !', 'publie')
ON DUPLICATE KEY UPDATE `commentaire` = VALUES(`commentaire`);
