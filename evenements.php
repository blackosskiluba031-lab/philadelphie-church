<?php
// evenements.php
// Calendrier des cultes, veillées et événements paroissiaux — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Programme des Cultes & Événements';
$isHeroPage = true;

// Filtres
$filtreCategorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$periode = $_GET['periode'] ?? 'a_venir'; // 'a_venir' ou 'passes'

$where = ["e.statut = 'publie'"];
$params = [];

if ($periode === 'passes') {
    $where[] = "e.date_debut < NOW()";
    $orderBy = "e.date_debut DESC";
} else {
    $where[] = "e.date_debut >= NOW()";
    $orderBy = "e.date_debut ASC";
}

if ($filtreCategorie > 0) {
    $where[] = "e.categorie_id = ?";
    $params[] = $filtreCategorie;
}

$whereClause = implode(' AND ', $where);

$stmtEvents = db()->prepare("
    SELECT e.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
           s.titre as stream_titre, s.url_direct as stream_url, s.statut as stream_statut
    FROM evenements e
    LEFT JOIN categories c ON e.categorie_id = c.id
    LEFT JOIN streams s ON e.stream_id = s.id
    WHERE $whereClause
    ORDER BY $orderBy
");
$stmtEvents->execute($params);
$evenements = $stmtEvents->fetchAll();

// Catégories
$categories = db()->query("
    SELECT DISTINCT c.id, c.nom 
    FROM categories c 
    INNER JOIN evenements e ON e.categorie_id = c.id 
    WHERE e.statut = 'publie' 
    ORDER BY c.nom ASC
")->fetchAll();

include __DIR__ . '/templates/header.php';

// Hero cinématique Church X
echo render_church_hero(
    '📅 Agenda de l’Assemblée',
    'Programme des Cultes & Célébrations',
    'Rejoignez notre assemblée pour les cultes dominicaux, réunions de prière, veillées spirituelles et répétitions musicales.',
    'https://images.unsplash.com/photo-1510525009512-ad7fc13eefab?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Cultes à venir', 'href' => BASE_URL . '/evenements.php?periode=a_venir', 'class' => 'btn-church-white'],
        ['label' => 'Direct Streaming', 'href' => BASE_URL . '/videos.php', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">
    <!-- Barre de filtres Church X -->
    <div class="glass-card" style="margin-bottom: 2.5rem; padding: 1.25rem 1.75rem; border-radius: var(--radius-xs);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 10px;">
                <a href="<?= BASE_URL ?>/evenements.php?periode=a_venir<?= $filtreCategorie ? '&categorie=' . $filtreCategorie : '' ?>" 
                   class="<?= $periode === 'a_venir' ? 'btn-church-white' : 'btn-church-outline' ?>" style="padding: 8px 18px; font-size: 0.85rem;">
                    À Venir
                </a>
                <a href="<?= BASE_URL ?>/evenements.php?periode=passes<?= $filtreCategorie ? '&categorie=' . $filtreCategorie : '' ?>" 
                   class="<?= $periode === 'passes' ? 'btn-church-white' : 'btn-church-outline' ?>" style="padding: 8px 18px; font-size: 0.85rem;">
                    Archives Passées
                </a>
            </div>

            <!-- Filtre par catégorie -->
            <form method="GET" action="<?= BASE_URL ?>/evenements.php" style="display: flex; align-items: center; gap: 10px;">
                <input type="hidden" name="periode" value="<?= e($periode) ?>">
                <label for="categorie" style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">Catégorie :</label>
                <select name="categorie" id="categorie" class="form-control" style="width: auto; padding: 8px 14px; font-size: 0.875rem; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);" onchange="this.form.submit()">
                    <option value="0">Toutes les célébrations</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filtreCategorie === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Grille des événements Church X -->
    <?php if (empty($evenements)): ?>
        <div class="glass-card" style="text-align: center; padding: 4rem 1.5rem; border-radius: var(--radius-xs);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🗓️</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Aucun événement programmé</h3>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                Il n'y a pas d'événement répertorié pour ce filtre actuellement.
            </p>
            <a href="<?= BASE_URL ?>/evenements.php" class="btn-church-outline">
                Réinitialiser les filtres
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem;">
            <?php foreach ($evenements as $evt): ?>
                <article class="card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=700&q=75" 
                             alt="<?= e($evt['titre']) ?>" class="card-image blur-up-image">
                        <span class="card-category-badge"><?= e($evt['categorie_nom'] ?? 'Culte') ?></span>
                    </div>

                    <div class="card-content-body">
                        <span class="card-meta-date">
                            🗓️ <?= format_date_fr($evt['date_debut'], true) ?>
                            <?php if (!empty($evt['date_fin'])): ?>
                                • <?= date('H\hi', strtotime($evt['date_fin'])) ?>
                            <?php endif; ?>
                        </span>

                        <h3 class="card-title-church"><?= e($evt['titre']) ?></h3>

                        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                            📍 <?= e($evt['lieu'] ?? 'Temple Principal') ?>
                        </p>

                        <p class="card-snippet">
                            <?= nl2br(e(truncate_text($evt['description'] ?? '', 160))) ?>
                        </p>

                        <div style="border-top: 1px solid var(--glass-border); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <?php if (!empty($evt['stream_url'])): ?>
                                <a href="<?= e($evt['stream_url']) ?>" target="_blank" class="btn-church-white" style="padding: 6px 14px; font-size: 0.8rem;">
                                    🔴 Direct Vidéo
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-subtle);">En présentiel</span>
                            <?php endif; ?>

                            <?php
                                $startGcal = date('Ymd\THis', strtotime($evt['date_debut']));
                                $endGcal   = !empty($evt['date_fin']) ? date('Ymd\THis', strtotime($evt['date_fin'])) : date('Ymd\THis', strtotime($evt['date_debut']) + 7200);
                                $gcalUrl   = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=" . urlencode($evt['titre']) . "&dates=" . $startGcal . "/" . $endGcal . "&details=" . urlencode($evt['description'] ?? '') . "&location=" . urlencode($evt['lieu'] ?? '');
                            ?>
                            <a href="<?= $gcalUrl ?>" target="_blank" rel="noopener" class="card-link-underline" style="font-size: 0.8rem;">
                                + Google Agenda
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
