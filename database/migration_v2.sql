-- ====================================================================
-- MIGRATION V2 - eglise_app
-- Approche non-destructive : Ajout des nouvelles tables, colonnes et index
-- Palette : Option A (Bleu Nuit Majestueux #0F172A & Or Sacre #D97706)
-- ====================================================================

USE `eglise_app`;

-- --------------------------------------------------------------------
-- 1. NOUVELLES TABLES
-- --------------------------------------------------------------------

-- Table pour les souscriptions Push PWA (W3C Web Push / VAPID)
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `endpoint` TEXT NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth_token` VARCHAR(255) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_push_fidele` (`fidele_id`),
  CONSTRAINT `fk_push_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de sécurisation contre les attaques par force brute
CREATE TABLE IF NOT EXISTS `tentatives_connexion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `statut` ENUM('succes','echec') NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `date_tentative` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tentatives_ip_date` (`ip`, `date_tentative`),
  KEY `idx_tentatives_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des demandes de prière et d'intercession
CREATE TABLE IF NOT EXISTS `demandes_priere` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `nom_demandeur` VARCHAR(100) DEFAULT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `sujet` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `anonyme` TINYINT(1) DEFAULT 0,
  `statut` ENUM('nouveau','en_priere','exauce','archive') DEFAULT 'nouveau',
  `traite_par_admin_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_priere_statut` (`statut`),
  KEY `idx_priere_fidele` (`fidele_id`),
  CONSTRAINT `fk_priere_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_priere_admin` FOREIGN KEY (`traite_par_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des départements et ministères de l'église
CREATE TABLE IF NOT EXISTS `departements` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icone` VARCHAR(50) DEFAULT 'users',
  `couleur` VARCHAR(7) DEFAULT '#0F172A',
  `responsable_nom` VARCHAR(100) DEFAULT NULL,
  `actif` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_departement_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison fidèles <-> départements
CREATE TABLE IF NOT EXISTS `fidele_departements` (
  `fidele_id` INT NOT NULL,
  `departement_id` INT NOT NULL,
  `role_departement` VARCHAR(80) DEFAULT 'Membre',
  `date_adhesion` DATE DEFAULT NULL,
  PRIMARY KEY (`fidele_id`, `departement_id`),
  KEY `idx_fd_departement` (`departement_id`),
  CONSTRAINT `fk_fd_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fd_departement` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paramètres globaux de configuration
CREATE TABLE IF NOT EXISTS `parametres` (
  `cle` VARCHAR(60) NOT NULL,
  `valeur` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des dons, dîmes et offrandes
CREATE TABLE IF NOT EXISTS `dons` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fidele_id` INT DEFAULT NULL,
  `nom_donateur` VARCHAR(120) DEFAULT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `type_don` ENUM('dime','offrande','construction','mission','action_sociale','autre') DEFAULT 'offrande',
  `montant` DECIMAL(12,2) NOT NULL,
  `devise` VARCHAR(5) DEFAULT 'EUR',
  `methode` ENUM('especes','virement','mobile_money','cheque','carte') DEFAULT 'especes',
  `reference_transaction` VARCHAR(100) DEFAULT NULL,
  `statut` ENUM('en_attente','valide','annule') DEFAULT 'valide',
  `date_don` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `enregistre_par_admin_id` INT DEFAULT NULL,
  `remarque` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dons_statut_date` (`statut`, `date_don`),
  KEY `idx_dons_type` (`type_don`),
  KEY `idx_dons_fidele` (`fidele_id`),
  CONSTRAINT `fk_dons_fidele` FOREIGN KEY (`fidele_id`) REFERENCES `fideles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dons_admin` FOREIGN KEY (`enregistre_par_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des médias téléchargeables (audios, bulletins paroissiaux, PDF)
CREATE TABLE IF NOT EXISTS `medias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `type` ENUM('audio','pdf','document','autre') NOT NULL,
  `fichier` VARCHAR(255) NOT NULL,
  `taille_octets` BIGINT DEFAULT NULL,
  `categorie_id` INT DEFAULT NULL,
  `statut` ENUM('publie','brouillon','archive') DEFAULT 'publie',
  `telechargements_count` INT UNSIGNED DEFAULT 0,
  `admin_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medias_statut` (`statut`),
  KEY `idx_medias_categorie` (`categorie_id`),
  CONSTRAINT `fk_medias_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_medias_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'analytique des vues (vidéos, annonces, streams)
CREATE TABLE IF NOT EXISTS `statistiques_vues` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type_contenu` ENUM('video','annonce','stream','media') NOT NULL,
  `contenu_id` INT NOT NULL,
  `ip_hash` VARCHAR(64) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `date_vue` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stats_contenu` (`type_contenu`, `contenu_id`),
  KEY `idx_stats_date` (`date_vue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
