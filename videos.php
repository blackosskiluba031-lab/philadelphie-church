<?php
// videos.php
// Prédications vidéos, enregistrements de cultes, bulletins paroissiaux et médias — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Médias & Prédications Vidéos';
$isHeroPage = true;

// Direct en cours ou programmé
$stmtStream = db()->query("
    SELECT * FROM streams 
    WHERE statut IN ('en_direct', 'programme') 
    ORDER BY FIELD(statut, 'en_direct', 'programme'), date_debut ASC LIMIT 1
");
$streamActif = $stmtStream->fetch();

// Vidéos publiées
$search = trim($_GET['q'] ?? '');
$where = ["v.statut = 'publie'"];
$params = [];

if ($search !== '') {
    $where[] = "(v.titre LIKE ? OR v.description LIKE ? OR v.predicateur LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereClause = implode(' AND ', $where);
$stmtVideos = db()->prepare("
    SELECT v.*, c.nom as categorie_nom, c.couleur as categorie_couleur
    FROM videos v
    LEFT JOIN categories c ON v.categorie_id = c.id
    WHERE $whereClause
    ORDER BY v.date_video DESC, v.id DESC
");
$stmtVideos->execute($params);
$videos = $stmtVideos->fetchAll();

// Documents & Médias
$medias = db()->query("
    SELECT m.*, c.nom as categorie_nom 
    FROM medias m 
    LEFT JOIN categories c ON m.categorie_id = c.id 
    WHERE m.statut = 'publie' 
    ORDER BY m.created_at DESC LIMIT 6
")->fetchAll();

include __DIR__ . '/templates/header.php';

// Hero cinématique Church X
echo render_church_hero(
    '🎥 Médias & Direct',
    'Prédications & Enseignements en Vidéo',
    'Retrouvez les enseignements de nos cultes, les temps de louange de la chorale et suivez nos retransmissions en direct.',
    'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Direct Streaming', 'href' => '#direct-stream', 'class' => 'btn-church-white'],
        ['label' => 'Publications & Chorale', 'href' => BASE_URL . '/communaute.php?type=prestation_chorale', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">

    <!-- Direct Streaming si actif -->
    <?php if ($streamActif): ?>
        <section id="direct-stream" style="margin-bottom: 4rem;">
            <div class="glass-card" style="padding: 2.5rem; border-radius: var(--radius-xs); border-left: 4px solid #EF4444;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <span style="background: rgba(239, 68, 68, 0.2); color: #EF4444; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: var(--radius-xs); text-transform: uppercase; letter-spacing: 0.08em;">
                            🔴 <?= $streamActif['statut'] === 'en_direct' ? 'En Direct Maintenant' : 'Prochain Direct' ?>
                        </span>
                        <h2 style="font-size: 1.6rem; margin-top: 0.5rem;"><?= e($streamActif['titre']) ?></h2>
                    </div>
                </div>

                <?php $embedStream = get_youtube_embed_url($streamActif['url_direct']); if ($embedStream): ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-xs); background: #000;">
                        <iframe src="<?= e($embedStream) ?>?autoplay=1" title="<?= e($streamActif['titre']) ?>" 
                                style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem 1rem;">
                        <p style="color: var(--text-muted); font-size: 1.05rem;">Le direct commencera à l'heure programmée.</p>
                        <a href="<?= e($streamActif['url_direct']) ?>" target="_blank" class="btn-church-white" style="margin-top: 1rem;">Ouvrir le flux sur YouTube</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Recherche -->
    <div class="glass-card" style="margin-bottom: 2.5rem; padding: 1.25rem 1.75rem; border-radius: var(--radius-xs);">
        <form method="GET" action="<?= BASE_URL ?>/videos.php" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher une prédication, un thème, un pasteur..." 
                   class="form-control" style="flex: 1; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
            <button type="submit" class="btn-church-white" style="padding: 9px 20px; font-size: 0.85rem;">Rechercher</button>
        </form>
    </div>

    <!-- Grille des Vidéos Church X -->
    <div class="section-heading-wrapper">
        <span class="accent-dash"></span>
        <h2 class="section-title">Enseignements & Cultes Enregistrés</h2>
    </div>

    <?php if (empty($videos)): ?>
        <div class="glass-card" style="text-align: center; padding: 4rem 1.5rem; border-radius: var(--radius-xs);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📹</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Aucune vidéo trouvée</h3>
            <p style="color: var(--text-muted);">Essayez d'autres mots-clés ou réinitialisez la recherche.</p>
            <a href="<?= BASE_URL ?>/videos.php" class="btn-church-outline" style="margin-top: 1.25rem;">Voir toutes les vidéos</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
            <?php foreach ($videos as $v): ?>
                <article class="card-church">
                    <div class="card-image-wrapper">
                        <?php if (!empty($v['miniature'])): ?>
                            <img src="<?= e($v['miniature']) ?>" alt="<?= e($v['titre']) ?>" class="card-image blur-up-image">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1510525009512-ad7fc13eefab?auto=format&fit=crop&w=700&q=75" 
                                 alt="<?= e($v['titre']) ?>" class="card-image blur-up-image">
                        <?php endif; ?>
                        <span class="card-category-badge"><?= e($v['categorie_nom'] ?? 'Prédication') ?></span>
                    </div>

                    <div class="card-content-body">
                        <span class="card-meta-date">
                            🗓️ <?= format_date_fr($v['date_video']) ?>
                            <?php if (!empty($v['duree'])): ?> • ⏱️ <?= e($v['duree']) ?><?php endif; ?>
                        </span>

                        <h3 class="card-title-church"><?= e($v['titre']) ?></h3>

                        <?php if (!empty($v['predicateur'])): ?>
                            <p style="font-size: 0.85rem; color: var(--accent-amber); font-weight: 600; margin-bottom: 0.5rem;">
                                🎙️ Prédicateur : <?= e($v['predicateur']) ?>
                            </p>
                        <?php endif; ?>

                        <p class="card-snippet">
                            <?= nl2br(e(truncate_text($v['description'] ?? '', 140))) ?>
                        </p>

                        <div style="border-top: 1px solid var(--glass-border); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.8rem; color: var(--text-subtle);">👁️ <?= (int)$v['vues_count'] ?> vues</span>
                            <a href="<?= e($v['url']) ?>" target="_blank" rel="noopener" class="card-link-underline">
                                Visionner la vidéo →
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Documents & Bulletins -->
    <?php if (!empty($medias)): ?>
        <div class="section-heading-wrapper" style="margin-top: 2rem;">
            <span class="accent-dash"></span>
            <h2 class="section-title">Bulletins & Documents Paroissiaux</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <?php foreach ($medias as $m): ?>
                <div class="glass-card" style="padding: 1.5rem; border-radius: var(--radius-xs); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span style="font-size: 0.75rem; color: var(--accent-amber); font-weight: 700; text-transform: uppercase;">
                            <?= e($m['type']) ?>
                        </span>
                        <h4 style="font-size: 1.05rem; margin-top: 2px; color: var(--text-main);"><?= e($m['titre']) ?></h4>
                    </div>
                    <a href="<?= e($m['fichier_url']) ?>" target="_blank" class="btn-church-white" style="padding: 6px 14px; font-size: 0.8rem;">
                        Télécharger
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
