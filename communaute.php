<?php
// communaute.php
// Le Mur Fraternel « Vivre Ensemble » : Témoignages, Méditations, Prestations Chorale & Commentaires
// Le Mur Fraternel « Vivre Ensemble » — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Publications | Vivre Ensemble & Partages';
$isHeroPage = true;
$errors = [];

// Traitement POST : Nouveau message OU Nouveau commentaire OU Like/Amen
// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expirée ou requête invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Dépôt d'un commentaire
        // 1. Commentaire
        if ($action === 'comment') {
            $pubId = (int) ($_POST['publication_id'] ?? 0);
            $auteurNom = trim($_POST['auteur_nom'] ?? 'Un fidèle');
            $commentaire = trim($_POST['commentaire'] ?? '');

            if ($pubId > 0 && !empty($commentaire)) {
                $stmt = db()->prepare("
                    INSERT INTO commentaires_communaute (publication_id, auteur_nom, commentaire, statut, created_at) 
                    VALUES (?, ?, ?, 'publie', NOW())
                ");
                $stmt->execute([$pubId, $auteurNom, $commentaire]);
                set_flash('success', "Votre commentaire a été publié dans les publications !");
                set_flash('success', "Votre encouragement a été publié sur le mur !");
                header('Location: ' . BASE_URL . '/communaute.php#pub-' . $pubId);
                exit;
            }
        }

        // 2. Réaction Like / Amen
        // 2. Like / Amen
        if ($action === 'amen') {
            $pubId = (int) ($_POST['publication_id'] ?? 0);
            if ($pubId > 0) {
                db()->prepare("UPDATE publications_communaute SET likes_count = likes_count + 1 WHERE id = ?")->execute([$pubId]);
                header('Location: ' . BASE_URL . '/communaute.php#pub-' . $pubId);
                exit;
            }
        }

        // 3. Nouvelle publication sur le mur (public — TEXTE UNIQUEMENT)
        // 3. Nouvelle publication (Public = texte seul)
        if ($action === 'publish') {
            $nom = trim($_POST['auteur_nom'] ?? 'Membre');
            $titre = trim($_POST['titre'] ?? '');
            $contenu = trim($_POST['contenu'] ?? '');
            $versetRef = trim($_POST['verset_reference'] ?? '');
            $versetTxt = trim($_POST['verset_texte'] ?? '');

            // ⚠️ Restriction serveur : seuls partage et temoignage sont autorisés pour le public
            $typeRaw = $_POST['type'] ?? 'partage';
            $type = in_array($typeRaw, ['partage', 'temoignage'], true) ? $typeRaw : 'partage';

            if (empty($titre)) {
                $errors[] = "Veuillez renseigner un titre pour votre partage.";
                $errors[] = "Veuillez renseigner un titre pour votre message.";
            }
            if (empty($contenu)) {
                $errors[] = "Veuillez renseigner le contenu de votre message.";
            }
            if (mb_strlen($contenu) > 2000) {
                $errors[] = "Votre message ne peut dépasser 2 000 caractères.";
            }

            if (empty($errors)) {
                // media_url toujours NULL pour le public
                $stmt = db()->prepare("
                    INSERT INTO publications_communaute 
                    (auteur_nom, type, titre, contenu, verset_reference, verset_texte, media_url, statut, likes_count, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NULL, 'publie', 0, NOW())
                ");
                $stmt->execute([
                    $nom,
                    $type,
                    $titre,
                    $contenu,
                    $versetRef ?: null,
                    $versetTxt ?: null,
                ]);

                set_flash('success', "🙌 Votre témoignage a été partagé dans les publications ! Gloire à Dieu !");
                header('Location: ' . BASE_URL . '/communaute.php');
                exit;
            }
        }
    }
}

// Filtre par type
// Filtre
$filtreType = $_GET['type'] ?? '';
$where = ["p.statut = 'publie'"];
$params = [];

if (!empty($filtreType)) {
    $where[] = "p.type = ?";
    $params[] = $filtreType;
}

$whereSql = implode(' AND ', $where);

// Publications avec commentaires
$stmtPubs = db()->prepare("
    SELECT p.*, COUNT(c.id) as nb_commentaires 
    FROM publications_communaute p 
    LEFT JOIN commentaires_communaute c ON p.id = c.publication_id AND c.statut = 'publie' 
    WHERE $whereSql 
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");
$stmtPubs->execute($params);
$publications = $stmtPubs->fetchAll();

include __DIR__ . '/templates/header.php';

// Hero cinématique Church X
echo render_church_hero(
    '🌟 Vivre Ensemble & Foi Partagée',
    'Publications de l’Assemblée',
    'Partagez vos actions de grâce, témoignages et découvrez les prestations de la chorale pour vous édifier mutuellement.',
    'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => '✍️ Partager un témoignage', 'href' => '#form-publier', 'class' => 'btn-church-white'],
        ['label' => 'Prestations chorale', 'href' => BASE_URL . '/communaute.php?type=prestation_chorale', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container container-narrow" style="padding-bottom: 4rem;">
    <!-- En-tête -->
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="badge badge-gold" style="margin-bottom: 0.5rem;">Vivre Ensemble • Édification Mutuelle</span>
        <h1 style="font-size: 2.4rem; margin-bottom: 0.75rem;">Publications de l'Église</h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem;">
            Partagez vos témoignages, méditations bibliques et prestations musicales de la chorale. Échangez et
            encouragez-vous les uns les autres !
        </p>
        <button type="button" class="btn btn-primary btn-lg" style="margin-top: 1rem;"
            onclick="document.getElementById('form-publier').scrollIntoView({behavior: 'smooth'});">
            ✍️ Partager un Témoignage ou un Message
        </button>
    </div>
    <div class="container" style="max-width: 900px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">

        <!-- Barre d'onglets de filtres -->
        <div class="card" style="margin-bottom: 2rem; padding: 0.75rem 1.25rem;">
            <!-- Filtres en boutons Church X -->
            <div class="glass-card"
                style="margin-bottom: 2.5rem; padding: 1rem 1.5rem; border-radius: var(--radius-xs);">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    <a href="<?= BASE_URL ?>/communaute.php"
                        class="btn <?= empty($filtreType) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                        <a href="<?= BASE_URL ?>/communaute.php"
                            class="<?= empty($filtreType) ? 'btn-church-white' : 'btn-church-outline' ?>"
                            style="padding: 6px 14px; font-size: 0.8rem;">
                            🌟 Tout voir
                        </a>
                        <a href="<?= BASE_URL ?>/communaute.php?type=prestation_chorale"
                            class="btn <?= $filtreType === 'prestation_chorale' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                            🎤 Prestations Chorale & Louange
                            <a href="<?= BASE_URL ?>/communaute.php?type=prestation_chorale"
                                class="<?= $filtreType === 'prestation_chorale' ? 'btn-church-white' : 'btn-church-outline' ?>"
                                style="padding: 6px 14px; font-size: 0.8rem;">
                                🎤 Prestations Chorale
                            </a>
                            <a href="<?= BASE_URL ?>/communaute.php?type=exhortation"
                                class="btn <?= $filtreType === 'exhortation' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                                <a href="<?= BASE_URL ?>/communaute.php?type=exhortation"
                                    class="<?= $filtreType === 'exhortation' ? 'btn-church-white' : 'btn-church-outline' ?>"
                                    style="padding: 6px 14px; font-size: 0.8rem;">
                                    📖 Méditations & Versets
                                </a>
                                <a href="<?= BASE_URL ?>/communaute.php?type=temoignage"
                                    class="btn <?= $filtreType === 'temoignage' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                                    <a href="<?= BASE_URL ?>/communaute.php?type=temoignage"
                                        class="<?= $filtreType === 'temoignage' ? 'btn-church-white' : 'btn-church-outline' ?>"
                                        style="padding: 6px 14px; font-size: 0.8rem;">
                                        ✨ Témoignages
                                    </a>
                                    <a href="<?= BASE_URL ?>/communaute.php?type=partage"
                                        class="btn <?= $filtreType === 'partage' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                                        <a href="<?= BASE_URL ?>/communaute.php?type=partage"
                                            class="<?= $filtreType === 'partage' ? 'btn-church-white' : 'btn-church-outline' ?>"
                                            style="padding: 6px 14px; font-size: 0.8rem;">
                                            💬 Partages fraternels
                                        </a>
                </div>
            </div>

            <!-- Fil des Publications -->
            <!-- Fil des publications -->
            <?php if (empty($publications)): ?>
                <div class="card" style="text-align: center; padding: 3rem; margin-bottom: 3rem;">
                    <div class="glass-card"
                        style="text-align: center; padding: 4rem 1.5rem; border-radius: var(--radius-xs); margin-bottom: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🕊️</div>
                        <h3>Aucune publication pour le moment</h3>
                        <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                            Soyez le premier à partager un témoignage ou une parole d'encouragement !
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Aucune publication pour le moment</h3>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                            Soyez le premier à partager un témoignage d'action de grâce ou un mot d'encouragement !
                        </p>
                        <a href="#form-publier" class="btn-church-white">Écrire le premier message</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 2rem; margin-bottom: 3.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 2rem; margin-bottom: 4rem;">
                            <?php foreach ($publications as $pub): ?>
                                <?php
                                // Chargement des commentaires de cette publication
                                $stmtComm = db()->prepare("SELECT * FROM commentaires_communaute WHERE publication_id = ? AND statut = 'publie' ORDER BY created_at ASC");
                                $stmtComm->execute([$pub['id']]);
                                $commentaires = $stmtComm->fetchAll();
                                ?>
                                <article class="card" id="pub-<?= $pub['id'] ?>"
                                    style="padding: 2rem; border-top: 3px solid var(--gold-primary);">
                                    <!-- En-tête de la publication -->
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <article class="glass-card" id="pub-<?= $pub['id'] ?>"
                                            style="padding: 2.25rem 2rem; border-radius: var(--radius-xs); border-left: 3px solid var(--accent-amber);">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div
                                                        style="width: 44px; height: 44px; border-radius: var(--radius-full); background: var(--gold-subtle); color: var(--gold-primary); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem;">
                                                        <div
                                                            style="width: 44px; height: 44px; border-radius: var(--radius-xs); background: #FFFFFF; color: #111; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem;">
                                                            <?= mb_substr($pub['auteur_nom'], 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <strong
                                                                style="font-size: 1.05rem; color: var(--text-primary); display: block;">
                                                                <strong
                                                                    style="font-size: 1.05rem; color: var(--text-main); display: block;">
                                                                    <?= e($pub['auteur_nom']) ?>
                                                                </strong>
                                                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                                                    <span style="font-size: 0.8rem; color: var(--text-subtle);">
                                                                        <?= format_date_fr($pub['created_at'], true) ?>
                                                                    </span>
                                                        </div>
                                                    </div>

                                                    <?php
                                                    $typeLabels = [
                                                        'prestation_chorale' => ['🎤 Prestation Chorale', 'background: rgba(139, 92, 246, 0.15); color: #A78BFA;'],
                                                        'exhortation' => ['📖 Méditation & Verset', 'background: rgba(217, 119, 6, 0.15); color: var(--gold-primary);'],
                                                        'temoignage' => ['✨ Témoignage', 'background: rgba(16, 185, 129, 0.15); color: #10B981;'],
                                                        'partage' => ['💬 Partage Fraternel', 'background: rgba(59, 130, 246, 0.15); color: #60A5FA;'],
                                                        'prestation_chorale' => ['🎤 Prestation Chorale', 'background: rgba(139, 92, 246, 0.2); color: #A78BFA;'],
                                                        'exhortation' => ['📖 Méditation', 'background: rgba(217, 119, 6, 0.2); color: var(--accent-amber);'],
                                                        'temoignage' => ['✨ Témoignage', 'background: rgba(16, 185, 129, 0.2); color: #10B981;'],
                                                        'partage' => ['💬 Partage', 'background: rgba(59, 130, 246, 0.2); color: #60A5FA;']
                                                    ];
                                                    $typeBadge = $typeLabels[$pub['type']] ?? ['Partage', ''];
                                                    ?>
                                                    <span class="badge" style="<?= $typeBadge[1] ?>">
                                                        <span
                                                            style="font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-xs); <?= $typeBadge[1] ?>">
                                                            <?= $typeBadge[0] ?>
                                                        </span>
                                                </div>

                                                <!-- Titre & Contenu -->
                                                <h3
                                                    style="font-size: 1.35rem; margin-bottom: 0.75rem; color: var(--text-primary);">
                                                    <h3
                                                        style="font-size: 1.35rem; margin-bottom: 0.85rem; color: var(--text-main);">
                                                        <?= e($pub['titre']) ?>
                                                    </h3>

                                                    <?php if (!empty($pub['verset_reference'])): ?>
                                                        <blockquote
                                                            style="font-style: italic; color: var(--gold-primary); margin-bottom: 1rem; border-left: 3px solid var(--gold-primary); padding: 8px 14px; background: var(--bg-surface); border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
                                                            <blockquote class="verset-block" style="margin-bottom: 1.25rem;">
                                                                « <?= e($pub['verset_texte']) ?> »
                                                                <footer
                                                                    style="font-size: 0.85rem; font-weight: 600; margin-top: 4px;">—
                                                                    <?= e($pub['verset_reference']) ?>
                                                                </footer>
                                                                <footer>— <?= e($pub['verset_reference']) ?></footer>
                                                            </blockquote>
                                                        <?php endif; ?>

                                                        <!-- Image / Photo attachée (Verset illustré, Citation, Photo chorale) -->
                                                        <?php if (!empty($pub['image_url'])): ?>
                                                            <div
                                                                style="border-radius: var(--radius-xs); overflow: hidden; margin-bottom: 1.5rem; border: 1px solid var(--glass-border); background: #000; text-align: center;">
                                                                <img src="<?= e(BASE_URL . '/' . ltrim($pub['image_url'], '/')) ?>"
                                                                    alt="<?= e($pub['titre']) ?>"
                                                                    style="max-width: 100%; max-height: 480px; width: auto; height: auto; display: inline-block; object-fit: contain;"
                                                                    class="blur-up-image">
                                                            </div>
                                                        <?php endif; ?>

                                                        <p
                                                            style="color: var(--text-secondary); font-size: 1rem; line-height: 1.6; margin-bottom: 1.25rem; white-space: pre-line;">
                                                        <p
                                                            style="color: var(--text-muted); font-size: 1rem; line-height: 1.65; margin-bottom: 1.5rem; white-space: pre-line;">
                                                            <?= nl2br(e($pub['contenu'])) ?>
                                                        </p>

                                                        <!-- Vidéo locale (moments forts uploadés) ou YouTube (Équipe Média) -->
                                                        <!-- Vidéo locale ou YouTube -->
                                                        <?php
                                                        // Priorité : fichier local > lien YouTube
                                                        $hasFichierLocal = !empty($pub['fichier_local'] ?? null);
                                                        $hasMediaUrl = !empty($pub['media_url']);
                                                        ?>
                                                        <?php if ($hasFichierLocal): ?>
                                                            <div
                                                                style="border-radius: var(--radius-md); overflow: hidden; margin-bottom: 1.5rem; background: #000;">
                                                                <video controls
                                                                    style="width: 100%; display: block; border-radius: var(--radius-md); max-height: 320px;"
                                                                    preload="metadata">
                                                                    <div
                                                                        style="border-radius: var(--radius-xs); overflow: hidden; margin-bottom: 1.5rem; background: #000; border: 1px solid var(--glass-border);">
                                                                        <video controls
                                                                            style="width: 100%; display: block; max-height: 380px;"
                                                                            preload="metadata">
                                                                            <source
                                                                                src="<?= e(BASE_URL . '/' . ltrim($pub['fichier_local'], '/')) ?>">
                                                                            Votre navigateur ne supporte pas la lecture vidéo HTML5.
                                                                            Votre navigateur ne prend pas en charge la lecture
                                                                            vidéo.
                                                                        </video>
                                                                    </div>
                                                                <?php elseif ($hasMediaUrl): ?>
                                                                    <?php $embedUrl = get_youtube_embed_url($pub['media_url']); ?>
                                                                    <?php if ($embedUrl): ?>
                                                                        <div
                                                                            style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-md); margin-bottom: 1.5rem; background: #000;">
                                                                            <div
                                                                                style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-xs); margin-bottom: 1.5rem; background: #000; border: 1px solid var(--glass-border);">
                                                                                <iframe src="<?= e($embedUrl) ?>"
                                                                                    title="<?= e($pub['titre']) ?>"
                                                                                    style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;"
                                                                                    allowfullscreen loading="lazy"></iframe>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>

                                                                    <!-- Barre de Réactions & Commentaires -->
                                                                    <div
                                                                        style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle); padding: 10px 0; margin-bottom: 1.25rem;">
                                                                        <!-- Barre de réactions -->
                                                                        <div
                                                                            style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); padding: 12px 0; margin-bottom: 1.25rem;">
                                                                            <form method="POST"
                                                                                action="<?= BASE_URL ?>/communaute.php">
                                                                                <?= csrf_field() ?>
                                                                                <input type="hidden" name="action" value="amen">
                                                                                <input type="hidden" name="publication_id"
                                                                                    value="<?= $pub['id'] ?>">
                                                                                <button type="submit"
                                                                                    class="btn btn-outline-gold btn-sm">
                                                                                    <button type="submit"
                                                                                        class="btn-church-outline"
                                                                                        style="padding: 6px 14px; font-size: 0.85rem;">
                                                                                        🙏 Amen / Gloire à Dieu
                                                                                        (<?= (int) $pub['likes_count'] ?>)
                                                                                    </button>
                                                                            </form>
                                                                            <span
                                                                                style="font-size: 0.875rem; color: var(--text-muted);">
                                                                                <span
                                                                                    style="font-size: 0.85rem; color: var(--text-subtle);">
                                                                                    💬 <?= count($commentaires) ?>
                                                                                    commentaire(s)
                                                                                </span>
                                                                        </div>

                                                                        <!-- Liste des commentaires existants -->
                                                                        <!-- Liste des commentaires -->
                                                                        <?php if (!empty($commentaires)): ?>
                                                                            <div
                                                                                style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 1.25rem; background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md);">
                                                                                <div
                                                                                    style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 1.25rem; background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: var(--radius-xs);">
                                                                                    <?php foreach ($commentaires as $com): ?>
                                                                                        <div
                                                                                            style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 8px;">
                                                                                            <div
                                                                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                                                                                <strong
                                                                                                    style="font-size: 0.85rem; color: var(--gold-primary);">
                                                                                                    <div
                                                                                                        style="border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                                                                                                        <div
                                                                                                            style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                                                                                            <strong
                                                                                                                style="font-size: 0.85rem; color: var(--accent-amber);">
                                                                                                                <?= e($com['auteur_nom']) ?>
                                                                                                            </strong>
                                                                                                            <span
                                                                                                                style="font-size: 0.75rem; color: var(--text-muted);">
                                                                                                                <span
                                                                                                                    style="font-size: 0.75rem; color: var(--text-subtle);">
                                                                                                                    <?= format_date_fr($com['created_at'], true) ?>
                                                                                                                </span>
                                                                                                        </div>
                                                                                                        <p
                                                                                                            style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                                                                                                        <p
                                                                                                            style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                                                                                                            <?= e($com['commentaire']) ?>
                                                                                                        </p>
                                                                                                    </div>
                                                                                                <?php endforeach; ?>
                                                                                        </div>
                                                                                    <?php endif; ?>

                                                                                    <!-- Formulaire d'ajout d'un commentaire -->
                                                                                    <!-- Formulaire commentaire -->
                                                                                    <form method="POST"
                                                                                        action="<?= BASE_URL ?>/communaute.php"
                                                                                        style="display: flex; gap: 8px;">
                                                                                        <?= csrf_field() ?>
                                                                                        <input type="hidden" name="action"
                                                                                            value="comment">
                                                                                        <input type="hidden"
                                                                                            name="publication_id"
                                                                                            value="<?= $pub['id'] ?>">

                                                                                        <input type="text" name="auteur_nom"
                                                                                            placeholder="Votre nom..." required
                                                                                            style="width: 140px; padding: 6px 10px; font-size: 0.85rem;"
                                                                                            class="form-control">
                                                                                        style="width: 140px; padding: 8px 12px;
                                                                                        font-size: 0.85rem; background:
                                                                                        var(--bg-surface); border: 1px solid
                                                                                        var(--glass-border); border-radius:
                                                                                        var(--radius-xs); color:
                                                                                        var(--text-main);" class="form-control">

                                                                                        <input type="text" name="commentaire"
                                                                                            placeholder="Écrire un encouragement, Amen..."
                                                                                            required
                                                                                            style="flex: 1; padding: 6px 12px; font-size: 0.85rem;"
                                                                                            class="form-control">
                                                                                        style="flex: 1; padding: 8px 14px;
                                                                                        font-size: 0.85rem; background:
                                                                                        var(--bg-surface); border: 1px solid
                                                                                        var(--glass-border); border-radius:
                                                                                        var(--radius-xs); color:
                                                                                        var(--text-main);" class="form-control">

                                                                                        <button type="submit"
                                                                                            class="btn btn-primary btn-sm">Envoyer</button>
                                                                                        <button type="submit"
                                                                                            class="btn-church-white"
                                                                                            style="padding: 8px 16px; font-size: 0.85rem;">Envoyer</button>
                                                                                    </form>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Formulaire pour poster une nouvelle publication sur le mur -->
                            <div id="form-publier" class="card"
                                style="padding: 2rem; border-top: 4px solid var(--gold-primary); margin-top: 1rem;">
                                <h3 style="font-size: 1.4rem; margin-bottom: 0.75rem;">
                                    ✍️ Partager un Témoignage ou un Message Fraternel
                                </h3>
                                <!-- Formulaire public (texte uniquement) -->
                                <div id="form-publier" class="glass-card"
                                    style="padding: 3rem 2.5rem; border-radius: var(--radius-xs);">
                                    <span class="accent-dash"></span>
                                    <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">
                                        ✍️ Partager un Témoignage ou un Message
                                    </h2>

                                    <!-- Bandeau des règles de publication -->
                                    <div
                                        style="background: rgba(217,119,6,0.09); border: 1px solid rgba(217,119,6,0.3); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                                        <strong style="color: var(--gold-primary);">📜 Règles de publication
                                            communautaire</strong><br>
                                        Sur ce mur, chacun peut partager librement ses <strong>témoignages</strong> et
                                        <strong>partages fraternels en texte</strong>.<br>
                                        Les <em>prestations chorales, prédications, versets officiels et annonces</em>
                                        sont publiés par <strong>l'Équipe Média</strong>.<br>
                                        <small style="opacity: 0.7;">💡 Pas de liens, pas de vidéos dans le formulaire
                                            public — seul le texte est accepté.</small>
                                        <div
                                            style="background: rgba(217,119,6,0.08); border-left: 3px solid var(--accent-amber); padding: 1rem 1.25rem; border-radius: var(--radius-xs); margin-bottom: 2rem; font-size: 0.9rem; color: var(--text-muted);">
                                            <strong style="color: var(--accent-amber);">📜 Règles de publication
                                                :</strong><br>
                                            Sur ce mur, chacun peut partager des <strong>témoignages</strong> et des
                                            <strong>messages fraternels en texte</strong>.<br>
                                            Les <em>prestations chorales, prédications vidéos et annonces</em> sont
                                            réservées à <strong>l'Équipe Média</strong>.<br>
                                            <small style="opacity:0.75;">💡 Pas de liens ni d'URLs dans le formulaire
                                                public.</small>
                                        </div>

                                        <?php if (!empty($errors)): ?>
                                            <div class="alert alert-danger">
                                                <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                                                    <ul style="margin: 0; padding-left: 20px;">
                                                        <?php foreach ($errors as $err): ?>
                                                            <li><?= e($err) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <form method="POST" action="<?= BASE_URL ?>/communaute.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="publish">

                                                <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                                                    <div
                                                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                                                        <div class="form-group">
                                                            <label for="auteur_nom" class="form-label">Votre Nom ou
                                                                Ministère *</label>
                                                            <input type="text" id="auteur_nom" name="auteur_nom"
                                                                class="form-control" required maxlength="100"
                                                                placeholder="Ex : Frère David, Sœur Esther...">
                                                            <label class="form-label"
                                                                style="color: var(--text-main);">Votre Nom / Ministère
                                                                *</label>
                                                            <input type="text" name="auteur_nom" class="form-control"
                                                                required maxlength="100"
                                                                placeholder="Ex : Frère David, Sœur Esther..."
                                                                style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="type" class="form-label">Type de Partage
                                                                *</label>
                                                            <select id="type" name="type" class="form-control">
                                                                <label class="form-label"
                                                                    style="color: var(--text-main);">Type de Partage
                                                                    *</label>
                                                                <select name="type" class="form-control"
                                                                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                                                                    <option value="partage">💬 Partage Fraternel
                                                                    </option>
                                                                    <option value="temoignage">✨ Témoignage d'Action de
                                                                        Grâce</option>
                                                                </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="titre" class="form-label">Titre du Partage *</label>
                                                        <input type="text" id="titre" name="titre" class="form-control"
                                                            required maxlength="150"
                                                            placeholder="Ex : Dieu a accompli un miracle dans ma famille...">
                                                        <div class="form-group" style="margin-bottom: 1.25rem;">
                                                            <label class="form-label"
                                                                style="color: var(--text-main);">Titre du Partage
                                                                *</label>
                                                            <input type="text" name="titre" class="form-control"
                                                                required maxlength="150"
                                                                placeholder="Ex : Dieu a restauré la santé de mon proche..."
                                                                style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="contenu" class="form-label">Votre Message
                                                                *</label>
                                                            <textarea id="contenu" name="contenu" class="form-control"
                                                                rows="5" required maxlength="2000"
                                                                placeholder="Écrivez ici pour fortifier vos frères et sœurs en Christ... (2 000 caractères max)"></textarea>
                                                            <small
                                                                style="color: var(--text-muted); display: block; margin-top: 4px;">Texte
                                                                uniquement · Pas de liens ni de vidéos</small>
                                                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                                                <label class="form-label"
                                                                    style="color: var(--text-main);">Votre Message
                                                                    *</label>
                                                                <textarea name="contenu" class="form-control" rows="5"
                                                                    required maxlength="2000"
                                                                    placeholder="Écrivez pour fortifier vos frères et sœurs en Christ..."
                                                                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);"></textarea>
                                                                <small
                                                                    style="color: var(--text-subtle); display: block; margin-top: 4px;">Texte
                                                                    uniquement (2000 caractères max)</small>
                                                            </div>

                                                            <!-- Versets bibliques optionnels — ouverts au public pour les témoignages -->
                                                            <div class="grid grid-cols-2"
                                                                style="margin-bottom: 1.25rem;">
                                                                <div
                                                                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                                                                    <div class="form-group">
                                                                        <label for="verset_reference"
                                                                            class="form-label">Référence Biblique
                                                                            <small>(Optionnel)</small></label>
                                                                        <input type="text" id="verset_reference"
                                                                            name="verset_reference" class="form-control"
                                                                            maxlength="80" placeholder="Ex : Jean 3:16">
                                                                        <label class="form-label"
                                                                            style="color: var(--text-main);">Référence
                                                                            Biblique <small>(Optionnel)</small></label>
                                                                        <input type="text" name="verset_reference"
                                                                            class="form-control" maxlength="80"
                                                                            placeholder="Ex : Psaume 23:1"
                                                                            style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="verset_texte"
                                                                            class="form-label">Texte du Verset
                                                                            <small>(Optionnel)</small></label>
                                                                        <input type="text" id="verset_texte"
                                                                            name="verset_texte" class="form-control"
                                                                            maxlength="400"
                                                                            placeholder="Car Dieu a tant aimé le monde...">
                                                                        <label class="form-label"
                                                                            style="color: var(--text-main);">Texte du
                                                                            Verset <small>(Optionnel)</small></label>
                                                                        <input type="text" name="verset_texte"
                                                                            class="form-control" maxlength="400"
                                                                            placeholder="Le Seigneur est mon berger..."
                                                                            style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                                                                    </div>
                                                                </div>

                                                                <button type="submit" class="btn btn-primary btn-lg"
                                                                    style="width: 100%;">
                                                                    <button type="submit" class="btn-church-white"
                                                                        style="width: 100%; padding: 14px; font-size: 1rem;">
                                                                        🌟 Publier mon message
                                                                    </button>
                                            </form>

                                            <p
                                                style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: var(--text-muted);">
                                                Vous êtes de l'Équipe Média ?
                                                <a href="<?= ADMIN_URL ?>/communaute_publier.php"
                                                    style="color: var(--gold-primary);">Accédez à l'espace publication
                                                    médias →</a>
                                            <p
                                                style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--text-subtle);">
                                                Vous êtes de l'Équipe Média ?
                                                <a href="<?= ADMIN_URL ?>/communaute_publier.php"
                                                    style="color: var(--accent-amber); font-weight: 600;">Espace de
                                                    publication médias →</a>
                                            </p>
                                        </div>

                                    </div>

                                    <?php include __DIR__ . '/templates/footer.php'; ?>