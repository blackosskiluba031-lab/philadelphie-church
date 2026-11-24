<?php
// scripts/setup_philadelphie.php
// Configuration officielle de Philadelphie Church / CFCG Garenganze et de ses programmes

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== Configuration Philadelphie Church ===\n";

try {
    $pdo = db();

    // 1. Mettre à jour les informations de l'église
    $checkEglise = $pdo->query("SELECT id FROM eglise LIMIT 1")->fetch();
    if ($checkEglise) {
        $stmt = $pdo->prepare("
            UPDATE eglise SET 
                nom = 'EGLISE PHILADELPHIE - CFCG GARENGANZE',
                slogan = 'Soyez le bienvenu et que Dieu vous bénisse',
                adresse = '3ème avenue prolongée, Av : de la révolution N.160 (RÉF : école pintu)',
                a_propos = 'Bienvenue à Philadelphie Church - Communauté Fraternelle Chrétienne de Garenganze (CFCG). Une église unie dans la Parole, la prière et la louange.'
            WHERE id = ?
        ");
        $stmt->execute([$checkEglise['id']]);
        echo "✅ Table 'eglise' mise à jour avec succès.\n";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO eglise (nom, slogan, adresse, a_propos) 
            VALUES (
                'EGLISE PHILADELPHIE - CFCG GARENGANZE',
                'Soyez le bienvenu et que Dieu vous bénisse',
                '3ème avenue prolongée, Av : de la révolution N.160 (RÉF : école pintu)',
                'Bienvenue à Philadelphie Church - CFCG Garenganze.'
            )
        ");
        $stmt->execute();
        echo "✅ Ligne créée dans la table 'eglise'.\n";
    }

    // 2. Récupérer ou insérer les catégories
    $catNames = [
        'Culte'        => '#D97706',
        'Chorale'      => '#8B5CF6',
        'Jeunesse'     => '#3B82F6',
        'Mamans'       => '#EC4899',
        'Prière'       => '#10B981',
        'Enseignement' => '#F59E0B',
        'Anciens'      => '#6B7280',
        'Bloc'         => '#6366F1',
    ];

    $catIds = [];
    foreach ($catNames as $name => $color) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $stmtIns = $pdo->prepare("INSERT INTO categories (nom, couleur) VALUES (?, ?)");
            $stmtIns->execute([$name, $color]);
            $id = $pdo->lastInsertId();
        }
        $catIds[$name] = (int)$id;
    }
    echo "✅ Catégories vérifiées / créées.\n";

    // 3. Programmes hebdomadaires selon l'image
    // Lundi : bloc (17h00 - 18h00)
    // Mardi : répétition des chorales (16h30 - 18h00)
    // Mercredi : Enseignement (16h30 - 18h00)
    // Jeudi : culte des mamans (15h30 - 16h30) & répétition des chorales (16h30 - 18h00)
    // Vendredi : culte de prière (16h30 - 18h00)
    // Samedi : collège des anciens (13h00) & répétition des chorales (15h30 - 18h00)
    // Dimanche : sainte-scène (08h30 - 09h45), bonne nouvelle (10h00 - 12h30), culte des jeunes (13h00 - 15h00)

    $programmes = [
        [
            'titre' => 'Bloc',
            'jour'  => 'next monday',
            'heure_debut' => '17:00:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Bloc',
            'desc'  => 'Moment de prière et rassemblement par bloc.'
        ],
        [
            'titre' => 'Répétition des Chorales',
            'jour'  => 'next tuesday',
            'heure_debut' => '16:30:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Chorale',
            'desc'  => 'Répétition générale de louange et préparation des cantiques.'
        ],
        [
            'titre' => 'Enseignement Biblique',
            'jour'  => 'next wednesday',
            'heure_debut' => '16:30:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Enseignement',
            'desc'  => 'Approfondissement de la Parole de Dieu et études doctrinales.'
        ],
        [
            'titre' => 'Culte des Mamans',
            'jour'  => 'next thursday',
            'heure_debut' => '15:30:00',
            'heure_fin'   => '16:30:00',
            'cat'   => 'Mamans',
            'desc'  => 'Rassemblement et intercession du ministère des femmes et mamans.'
        ],
        [
            'titre' => 'Répétition des Chorales',
            'jour'  => 'next thursday',
            'heure_debut' => '16:30:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Chorale',
            'desc'  => 'Seconde séance de répétition chorale de la semaine.'
        ],
        [
            'titre' => 'Culte de Prière',
            'jour'  => 'next friday',
            'heure_debut' => '16:30:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Prière',
            'desc'  => 'Temps fort d’intercession, supplications et combat spirituel.'
        ],
        [
            'titre' => 'Collège des Anciens',
            'jour'  => 'next saturday',
            'heure_debut' => '13:00:00',
            'heure_fin'   => '15:00:00',
            'cat'   => 'Anciens',
            'desc'  => 'Réunion pastorale et de direction du collège des anciens.'
        ],
        [
            'titre' => 'Répétition des Chorales',
            'jour'  => 'next saturday',
            'heure_debut' => '15:30:00',
            'heure_fin'   => '18:00:00',
            'cat'   => 'Chorale',
            'desc'  => 'Dernière mise au point des chœurs avant les cultes du dimanche.'
        ],
        [
            'titre' => 'Culte de Sainte-Cène',
            'jour'  => 'next sunday',
            'heure_debut' => '08:30:00',
            'heure_fin'   => '09:45:00',
            'cat'   => 'Culte',
            'desc'  => 'Communion fraternelle et partage de la table du Seigneur.'
        ],
        [
            'titre' => 'Culte de la Bonne Nouvelle',
            'jour'  => 'next sunday',
            'heure_debut' => '10:00:00',
            'heure_fin'   => '12:30:00',
            'cat'   => 'Culte',
            'desc'  => 'Grand culte d’évangélisation, louange solennelle et prédication.'
        ],
        [
            'titre' => 'Culte des Jeunes',
            'jour'  => 'next sunday',
            'heure_debut' => '13:00:00',
            'heure_fin'   => '15:00:00',
            'cat'   => 'Jeunesse',
            'desc'  => 'Rassemblement dynamique de la jeunesse, prédication adaptée et louange.'
        ],
    ];

    // Nettoyer les anciens événements de test et insérer les vrais
    $pdo->exec("DELETE FROM evenements");

    $stmtInsEvt = $pdo->prepare("
        INSERT INTO evenements (categorie_id, titre, description, date_debut, date_fin, lieu, recurrent, statut, created_at)
        VALUES (?, ?, ?, ?, ?, 'Temple Philadelphie (CFCG Garenganze)', 'hebdomadaire', 'publie', NOW())
    ");

    foreach ($programmes as $p) {
        $baseDate = date('Y-m-d', strtotime($p['jour']));
        $start = $baseDate . ' ' . $p['heure_debut'];
        $end   = $baseDate . ' ' . $p['heure_fin'];
        $catId = $catIds[$p['cat']] ?? null;

        $stmtInsEvt->execute([
            $catId,
            $p['titre'],
            $p['desc'],
            $start,
            $end
        ]);
    }

    echo "✅ 11 Programmes hebdomadaires insérés dans la table 'evenements'.\n";
    echo "=== Configuration terminée avec succès ===\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
