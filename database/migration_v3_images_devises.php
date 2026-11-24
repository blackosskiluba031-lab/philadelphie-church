<?php
// database/migration_v3_images_devises.php
// Migration pour images de publications et paramètres UI

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== Migration v3 : Images & Devises RDC ===\n";

try {
    $pdo = db();

    // 1. Colonne image_url dans publications_communaute
    $colCheck = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME   = 'publications_communaute' 
          AND COLUMN_NAME  = 'image_url'
    ");
    $colCheck->execute();
    if ((int)$colCheck->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE publications_communaute ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER fichier_local");
        echo "✅ Colonne 'image_url' ajoutée à 'publications_communaute'.\n";
    } else {
        echo "ℹ️ Colonne 'image_url' déjà existante.\n";
    }

    // 2. Dossiers uploads pour images UI et publications
    $dirs = [
        ROOT_PATH . 'uploads/ui',
        ROOT_PATH . 'uploads/publications',
        ROOT_PATH . 'uploads/medias'
    ];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            mkdir($d, 0775, true);
            echo "✅ Dossier créé : " . basename($d) . "\n";
        }
    }

    // 3. Paramètres initiaux pour les devises RDC et images de fond UI
    $initialParams = [
        'devise_defaut' => 'FC',
        'contact_support_app' => '0901247908',
        'paiement_support_app' => '0990945558',
        'hero_bg_accueil' => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_programme' => 'https://images.unsplash.com/photo-1510525009512-ad7fc13eefab?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_videos' => 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_communaute' => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_pasteur' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_dons' => 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_priere' => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80',
        'hero_bg_entraide' => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1920&q=80'
    ];

    $stmtParam = $pdo->prepare("
        INSERT INTO parametres (cle, valeur, description) 
        VALUES (?, ?, 'Paramètre système') 
        ON DUPLICATE KEY UPDATE cle = cle
    ");

    foreach ($initialParams as $cle => $val) {
        $stmtParam->execute([$cle, $val]);
    }
    echo "✅ Paramètres RDC et images UI initialisés.\n";

    echo "=== Migration terminée avec succès ===\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
