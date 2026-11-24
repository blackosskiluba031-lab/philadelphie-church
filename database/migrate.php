<?php
// database/migrate.php
// Script de migration sécurisé et idempotent pour eglise_app

$host = '127.0.0.1';
$db   = 'eglise_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "=== Début de la migration eglise_app ===\n";

    // 1. Exécution du script de création des nouvelles tables
    $sqlFile = __DIR__ . '/migration_v2.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "[OK] Tables créées ou déjà existantes via migration_v2.sql\n";
    }

    // Fonctions d'assistance idempotentes
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'eglise_app' AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    }

    function indexExists($pdo, $table, $index) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'eglise_app' AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $stmt->execute([$table, $index]);
        return $stmt->fetchColumn() > 0;
    }

    function ensureColumn($pdo, $table, $column, $definition) {
        if (!columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "[AJOUT COLONNE] $table.$column ajouté avec succès.\n";
        } else {
            echo "[OK] Colonne $table.$column existe déjà.\n";
        }
    }

    function ensureIndex($pdo, $table, $indexName, $indexDefinition) {
        if (!indexExists($pdo, $table, $indexName)) {
            $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` $indexDefinition");
            echo "[AJOUT INDEX] Index $indexName sur $table ajouté.\n";
        } else {
            echo "[OK] Index $indexName sur $table existe déjà.\n";
        }
    }

    // 2. Évolution non destructive des tables existantes

    // Table admins
    ensureColumn($pdo, 'admins', 'email', "VARCHAR(120) NULL UNIQUE AFTER `username`");
    ensureColumn($pdo, 'admins', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

    // Table admin_logs
    ensureColumn($pdo, 'admin_logs', 'user_agent', "VARCHAR(255) NULL AFTER `ip`");
    ensureColumn($pdo, 'admin_logs', 'details', "JSON NULL AFTER `user_agent`");

    // Table fideles
    ensureColumn($pdo, 'fideles', 'prenom', "VARCHAR(100) NULL AFTER `nom`");
    ensureColumn($pdo, 'fideles', 'sexe', "ENUM('M','F') NULL AFTER `prenom`");
    ensureColumn($pdo, 'fideles', 'date_naissance', "DATE NULL AFTER `sexe`");
    ensureColumn($pdo, 'fideles', 'statut', "ENUM('actif','inactif','visiteur') DEFAULT 'actif' AFTER `recoit_notifications`");
    ensureColumn($pdo, 'fideles', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
    ensureIndex($pdo, 'fideles', 'idx_fideles_groupe', "(`groupe`)");
    ensureIndex($pdo, 'fideles', 'idx_fideles_recoit_notifs', "(`recoit_notifications`)");

    // Table evenements
    ensureColumn($pdo, 'evenements', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `statut`");
    ensureColumn($pdo, 'evenements', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
    ensureIndex($pdo, 'evenements', 'idx_events_statut_date', "(`statut`, `date_debut`)");

    // Table presences
    ensureColumn($pdo, 'presences', 'enregistre_par_admin_id', "INT NULL AFTER `date_pointage`");
    ensureColumn($pdo, 'presences', 'remarque', "VARCHAR(255) NULL AFTER `enregistre_par_admin_id`");

    // Table videos
    ensureColumn($pdo, 'videos', 'categorie_id', "INT NULL AFTER `statut`");
    ensureColumn($pdo, 'videos', 'duree', "VARCHAR(20) NULL AFTER `categorie_id`");
    ensureColumn($pdo, 'videos', 'vues_count', "INT UNSIGNED DEFAULT 0 AFTER `duree`");
    ensureColumn($pdo, 'videos', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
    ensureIndex($pdo, 'videos', 'idx_videos_statut', "(`statut`, `date_video`)");

    // Table annonces
    ensureColumn($pdo, 'annonces', 'vues_count', "INT UNSIGNED DEFAULT 0 AFTER `statut`");
    ensureColumn($pdo, 'annonces', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

    // Table albums
    ensureColumn($pdo, 'albums', 'statut', "ENUM('publie','brouillon','archive') DEFAULT 'publie' AFTER `date_album`");
    ensureColumn($pdo, 'albums', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

    // Table images
    ensureColumn($pdo, 'images', 'ordre', "INT DEFAULT 0 AFTER `statut`");

    // Table categories
    ensureIndex($pdo, 'categories', 'idx_categories_type', "(`type`)");

    // 3. Initialisation des paramètres par défaut
    $paramsInit = [
        ['app_nom', 'Église de la Grâce', 'Nom officiel affiché dans l\'application'],
        ['theme_primaire', '#0F172A', 'Couleur primaire (Bleu nuit profond)'],
        ['theme_accent', '#D97706', 'Couleur d\'accentuation (Or ambré)'],
        ['theme_mode_defaut', 'dark', 'Mode d\'affichage par défaut (dark / light)'],
        ['devise_defaut', 'EUR', 'Devise monétaire principale pour les dons et offrandes'],
        ['pwa_push_active', '1', 'Activation du service Web Push PWA'],
        ['verset_annee', '« Car je connais les projets que j’ai formés sur vous, dit l’Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l’espérance. » — Jérémie 29:11', 'Verset biblique de l\'année'],
    ];

    $stmtParam = $pdo->prepare("INSERT INTO `parametres` (`cle`, `valeur`, `description`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `description` = VALUES(`description`)");
    foreach ($paramsInit as $p) {
        $stmtParam->execute($p);
    }
    echo "[OK] Paramètres d'initialisation configurés (Palette Option A intégrée).\n";

    echo "=== Migration terminée avec succès ! ===\n";

} catch (Exception $e) {
    echo "Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
