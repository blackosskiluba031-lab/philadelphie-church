<?php
// database/migration_role_media.php
// Ajoute le rôle 'media' à l'ENUM de la table admins

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== Migration : Rôle Équipe Média ===\n\n";

try {
    $pdo = db();

    // Vérifier la définition actuelle de la colonne role
    $stmt = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME   = 'admins' 
          AND COLUMN_NAME  = 'role'
    ");
    $stmt->execute();
    $col = $stmt->fetchColumn();
    echo "Définition actuelle de la colonne 'role' : $col\n";

    if (strpos($col, "'media'") !== false) {
        echo "✅ Le rôle 'media' est déjà présent dans l'ENUM.\n";
    } else {
        $pdo->exec("
            ALTER TABLE admins 
            MODIFY COLUMN role ENUM('developpeur','responsable','media','secretaire') 
            NOT NULL DEFAULT 'secretaire'
        ");
        echo "✅ Rôle 'media' ajouté avec succès à la table admins.\n";

        // Vérification post-migration
        $stmt->execute();
        $newCol = $stmt->fetchColumn();
        echo "Nouvelle définition : $newCol\n";
    }

    // Créer le répertoire d'uploads pour les vidéos courtes si absent
    $uploadsDir = __DIR__ . '/../uploads/medias';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0775, true);
        echo "✅ Répertoire uploads/medias/ créé.\n";
    } else {
        echo "✅ Répertoire uploads/medias/ déjà existant.\n";
    }

    echo "\n=== Migration terminée avec succès ===\n";

} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
