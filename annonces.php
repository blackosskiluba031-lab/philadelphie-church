<?php
// annonces.php
// Liste des annonces paroissiales — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Annonces & Nouvelles';
$isHeroPage = true;

// Recherche & Filtres
$search = trim($_GET['q'] ?? '');
$filtreCategorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

$where = ["a.statut = 'publie'"];
$params = [];

if ($search !== '') {
    $where[] = "(a.titre LIKE ? OR a.contenu LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($filtreCategorie > 0) {
    $where[] = "a.categorie_id = ?";
    $params[] = $filtreCategorie;
}

$whereClause = implode(' AND ', $where);

$stmtAnnonces = db()->prepare("
    SELECT a.*, c.nom as categorie_nom, c.couleur as categorie_couleur
    FROM annonces a
    LEFT JOIN categories c ON a.categorie_id = c.id
    WHERE $whereClause
    ORDER BY a.epingle DESC, a.date_publication DESC
");
$stmtAnnonces->execute($params);
$annonces = $stmtAnnonces->fetchAll();

// Catégories
$categories = db()->query("
    SELECT DISTINCT c.id, c.nom 
    FROM categories c 
    INNER JOIN annonces a ON a.categorie_id = c.id 
    WHERE a.statut = 'publie' 
    ORDER BY c.nom ASC
")->fetchAll();

include __DIR__ . '/templates/header.php';

// Hero cinématique Church X
echo render_church_hero(
    '📢 Communications Officielles',
    'Annonces & Vie de l’Église',
    'Restez informés des décisions pastorales, des projets de l’assemblée et des événements à ne pas manquer.',
    'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Toutes les annonces', 'href' => BASE_URL . '/annonces.php', 'class' => 'btn-church-white'],
        ['label' => 'Au Pasteur', 'href' => BASE_URL . '/pasteur.php', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">
    <!-- Barre de recherche et filtres -->
    <div class="glass-card" style="margin-bottom: 2.5rem; padding: 1.25rem 1.75rem; border-radius: var(--radius-xs);">
        <form method="GET" action="<?= BASE_URL ?>/annonces.php" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="flex: 1; min-width: 260px;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher une annonce, un mot-clé..." 
                       class="form-control" style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <select name="categorie" class="form-control" style="width: auto; padding: 8px 14px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);" onchange="this.form.submit()">
                    <option value="0">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filtreCategorie === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-church-white" style="padding: 8px 18px; font-size: 0.85rem;">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Grille des annonces -->
    <?php if (empty($annonces)): ?>
        <div class="glass-card" style="text-align: center; padding: 4rem 1.5rem; border-radius: var(--radius-xs);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📢</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Aucune annonce trouvée</h3>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                Modifiez vos critères de recherche ou réinitialisez le filtre.
            </p>
            <a href="<?= BASE_URL ?>/annonces.php" class="btn-church-outline">Voir toutes les annonces</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem;">
            <?php foreach ($annonces as $a): ?>
                <article class="card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1465847899084-d164df4dedc6?auto=format&fit=crop&w=700&q=75" 
                             alt="<?= e($a['titre']) ?>" class="card-image blur-up-image">
                        <span class="card-category-badge"><?= e($a['categorie_nom'] ?? 'Annonce') ?></span>
                    </div>

                    <div class="card-content-body">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span class="card-meta-date">🗓️ <?= format_date_fr($a['date_publication']) ?></span>
                            <?php if ($a['epingle']): ?>
                                <span style="font-size: 0.75rem; font-weight: 700; color: var(--accent-amber); background: rgba(217,119,6,0.15); padding: 2px 8px; border-radius: var(--radius-xs);">
                                    📌 Prioritaire
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="card-title-church"><?= e($a['titre']) ?></h3>

                        <p class="card-snippet">
                            <?= nl2br(e(truncate_text($a['contenu'], 160))) ?>
                        </p>

                        <div style="border-top: 1px solid var(--glass-border); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.825rem; color: var(--text-subtle);">
                            <span>👁️ <?= (int)$a['vues_count'] ?> lectures</span>
                            <span style="color: var(--text-main); font-weight: 600;">Par la paroisse</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
