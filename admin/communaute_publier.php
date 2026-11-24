<?php
// admin/communaute_publier.php
// Publications & Partages — réservé à l'Équipe Média, Pasteur et Développeur

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Réservé à l'Équipe Média, Responsable, Développeur
require_role([ROLE_MEDIA, ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Publications & Partages';
$pageTitle        = 'Publications & Partages';
$errors           = [];
$success          = false;

// S'assurer que les colonnes existent
try {
    $pdo = db();
    $cols = $pdo->query("SHOW COLUMNS FROM publications_communaute")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('fichier_local', $cols)) {
        $pdo->exec("ALTER TABLE publications_communaute ADD COLUMN fichier_local VARCHAR(255) DEFAULT NULL AFTER media_url");
    }
    if (!in_array('image_url', $cols)) {
        $pdo->exec("ALTER TABLE publications_communaute ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER fichier_local");
    }
} catch (Exception $e) {
    error_log('Erreur colonnes: ' . $e->getMessage());
}

// Répertoires uploads
$uploadDirMedia = ROOT_PATH . 'uploads/medias/';
$uploadDirImg   = ROOT_PATH . 'uploads/publications/';
if (!is_dir($uploadDirMedia)) mkdir($uploadDirMedia, 0775, true);
if (!is_dir($uploadDirImg))   mkdir($uploadDirImg, 0775, true);

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton expiré.";
    } else {
        $auteurNom    = trim($_POST['auteur_nom'] ?? (current_user()['nom'] ?? 'Équipe Média'));
        $type         = $_POST['type'] ?? 'exhortation';
        $titre        = trim($_POST['titre'] ?? '');
        $contenu      = trim($_POST['contenu'] ?? '');
        $versetRef    = trim($_POST['verset_reference'] ?? '');
        $versetTxt    = trim($_POST['verset_texte'] ?? '');
        $mediaUrl     = trim($_POST['media_url'] ?? '');
        $fichierLocal = null;
        $imageUrl     = null;

        $typesAutorises = ['exhortation', 'temoignage', 'partage', 'prestation_chorale'];
        if (!in_array($type, $typesAutorises, true)) {
            $type = 'exhortation';
        }

        if (empty($titre))   $errors[] = "Le titre est obligatoire.";
        if (empty($contenu)) $errors[] = "Le contenu / message est obligatoire.";

        // 1. Upload Photo / Image (verset illustré, citation, affiche, photo chorale)
        if (!empty($_FILES['fichier_image']['name']) && $_FILES['fichier_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['fichier_image'];
            $allowedImg = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxImgSize = 10 * 1024 * 1024; // 10 Mo max

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur lors de l'envoi de la photo.";
            } elseif ($file['size'] > $maxImgSize) {
                $errors[] = "La photo ne peut dépasser 10 Mo.";
            } elseif (!in_array($file['type'], $allowedImg, true)) {
                $errors[] = "Format d'image non valide (JPG, PNG, WebP uniquement).";
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDirImg . $filename)) {
                    $imageUrl = 'uploads/publications/' . $filename;
                } else {
                    $errors[] = "Échec de l'enregistrement de l'image.";
                }
            }
        }

        // 2. Upload Vidéo courte (moments forts 1-2 min)
        if (!empty($_FILES['fichier_video']['name']) && $_FILES['fichier_video']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['fichier_video'];
            $allowedMimes = ['video/mp4', 'video/webm', 'video/ogg'];
            $maxSize = 80 * 1024 * 1024; // 80 Mo max

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur lors de l'envoi de la vidéo.";
            } elseif ($file['size'] > $maxSize) {
                $errors[] = "La vidéo ne peut dépasser 80 Mo.";
            } elseif (!in_array($file['type'], $allowedMimes, true)) {
                $errors[] = "Format vidéo non accepté (MP4, WebM uniquement).";
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'prestation_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDirMedia . $filename)) {
                    $fichierLocal = 'uploads/medias/' . $filename;
                    $mediaUrl = ''; // Pas de lien YouTube si fichier local
                } else {
                    $errors[] = "Échec de l'enregistrement de la vidéo.";
                }
            }
        }

        if (empty($errors)) {
            $stmt = db()->prepare("
                INSERT INTO publications_communaute 
                (auteur_nom, type, titre, contenu, verset_reference, verset_texte, media_url, fichier_local, image_url, statut, likes_count, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'publie', 0, NOW())
            ");
            $stmt->execute([
                $auteurNom,
                $type,
                $titre,
                $contenu,
                $versetRef  ?: null,
                $versetTxt  ?: null,
                $mediaUrl   ?: null,
                $fichierLocal,
                $imageUrl
            ]);

            log_admin_action(current_user()['id'], 'Nouveau partage média', ['type' => $type, 'titre' => $titre]);
            set_flash('success', "✅ Publication partagée avec succès sur le Mur !");
            header('Location: ' . ADMIN_URL . '/communaute_publier.php');
            exit;
        }
    }
}

// 10 dernières publications
$recentes = db()->query("
    SELECT * FROM publications_communaute 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

$church = get_church_info();

// Inclusion unique de l'en-tête (qui ouvre admin-layout, admin_sidebar et admin-content)
include __DIR__ . '/../templates/admin_header.php';
?>

<!-- En-tête page -->
<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.6rem; margin-bottom: 4px;">📢 Publications & Partages</h2>
    <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
        Partagez des exhortations, versets illustrés, citations, photos ou vidéos de chorale avec les fidèles.
    </p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: start;">

    <!-- Formulaire de partage -->
    <div class="card" style="padding: 2rem; border-top: 4px solid var(--gold-primary);">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem;">Nouveau Partage</h3>

        <form method="POST" action="<?= ADMIN_URL ?>/communaute_publier.php" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Publié au nom de *</label>
                <input type="text" name="auteur_nom" class="form-control" required maxlength="100"
                       value="<?= e(current_user()['nom'] ?? 'Équipe Média') ?>"
                       placeholder="Ex : Équipe Média, Pasteur, Chorale Louange...">
            </div>

            <div class="form-group">
                <label class="form-label">Type de Publication *</label>
                <select name="type" id="type-select" class="form-control" onchange="toggleFields(this.value)">
                    <option value="exhortation">📖 Exhortation / Méditation & Verset</option>
                    <option value="prestation_chorale">🎤 Prestation Chorale / Louange</option>
                    <option value="partage">💬 Citation / Message Fraternel</option>
                    <option value="temoignage">✨ Témoignage d'Action de Grâce</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Titre *</label>
                <input type="text" name="titre" class="form-control" required maxlength="150"
                       placeholder="Ex : Verset du jour, Chant de louange du culte...">
            </div>

            <div class="form-group">
                <label class="form-label">Message / Description / Citation *</label>
                <textarea name="contenu" class="form-control" rows="5" required maxlength="3000"
                          placeholder="Écrivez le message, la méditation, les paroles ou la citation..."></textarea>
            </div>

            <!-- Upload Photo / Image (verset illustré, photo chorale, citation) -->
            <div class="form-group" style="background: rgba(217, 119, 6, 0.08); padding: 1.25rem; border-radius: var(--radius-md); border: 1px dashed rgba(217, 119, 6, 0.35);">
                <label class="form-label" style="color: var(--gold-primary); font-weight: 700;">
                    🖼️ Photo / Image (Verset illustré, Citation, Affiche)
                </label>
                <input type="file" name="fichier_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                    Format JPG, PNG ou WebP (10 Mo max). Idéal pour les versets illustrés et photos de culte.
                </small>
            </div>

            <!-- Versets bibliques -->
            <div id="fields-verset" class="grid grid-cols-2" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label">Référence Biblique <small>(Optionnel)</small></label>
                    <input type="text" name="verset_reference" class="form-control" maxlength="80"
                           placeholder="Ex : Jean 3:16, Psaume 23:1">
                </div>
                <div class="form-group">
                    <label class="form-label">Texte du Verset <small>(Optionnel)</small></label>
                    <input type="text" name="verset_texte" class="form-control" maxlength="400"
                           placeholder="Car Dieu a tant aimé le monde...">
                </div>
            </div>

            <!-- Upload vidéo locale (moments forts 1-2 min) -->
            <div id="fields-upload" class="form-group">
                <label class="form-label">
                    📹 Vidéo Courte (Moment Fort) 
                    <small style="color: var(--gold-primary);">1–2 min · MP4/WebM · 80 Mo max</small>
                </label>
                <input type="file" name="fichier_video" class="form-control" accept="video/mp4,video/webm">
                <small style="color: var(--text-secondary);">
                    Upload direct d'un extrait vidéo de louange ou de prédication.
                </small>
            </div>

            <!-- Lien YouTube optionnel -->
            <div id="fields-media" class="form-group">
                <label class="form-label">Lien Vidéo YouTube <small>(Optionnel si vous uploadez un fichier)</small></label>
                <input type="url" name="media_url" class="form-control"
                       placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                🌟 Partager Maintenant
            </button>
        </form>
    </div>

    <!-- Liste des partages récents -->
    <div>
        <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">📋 Partages Récents</h3>
        <?php if (empty($recentes)): ?>
            <div class="card" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                Aucune publication pour l'instant.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <?php
                $typeBadges = [
                    'prestation_chorale' => ['🎤', '#A78BFA', 'rgba(139,92,246,0.15)'],
                    'exhortation'        => ['📖', 'var(--gold-primary)', 'rgba(217,119,6,0.12)'],
                    'temoignage'         => ['✨', '#10B981', 'rgba(16,185,129,0.12)'],
                    'partage'            => ['💬', '#60A5FA', 'rgba(59,130,246,0.12)'],
                ];
                foreach ($recentes as $r):
                    $badge = $typeBadges[$r['type']] ?? ['📌', 'var(--gold-primary)', 'rgba(217,119,6,0.1)'];
                ?>
                <div class="card" style="padding: 1.25rem; border-left: 3px solid <?= $badge[1] ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 0.75rem; color: <?= $badge[1] ?>; background: <?= $badge[2] ?>; padding: 2px 8px; border-radius: 999px; font-weight: 700;">
                            <?= $badge[0] ?> <?= e(str_replace('_', ' ', $r['type'])) ?>
                        </span>
                        <span style="font-size: 0.72rem; color: var(--text-muted);"><?= format_date_fr($r['created_at'], true) ?></span>
                    </div>

                    <strong style="font-size: 0.95rem; color: var(--text-primary); display: block; margin-bottom: 4px;">
                        <?= e($r['titre']) ?>
                    </strong>

                    <?php if (!empty($r['image_url'])): ?>
                        <div style="margin: 8px 0;">
                            <img src="<?= e(BASE_URL . '/' . ltrim($r['image_url'], '/')) ?>" alt="<?= e($r['titre']) ?>" 
                                 style="max-width: 100%; max-height: 140px; border-radius: var(--radius-sm); object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <p style="font-size: 0.825rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">
                        <?= e(truncate_text($r['contenu'], 100)) ?>
                    </p>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-subtle); padding-top: 6px;">
                        <span>Par <?= e($r['auteur_nom']) ?></span>
                        <span>🙏 <?= (int)$r['likes_count'] ?> Amen</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= ADMIN_URL ?>/communaute_moderation.php" class="btn btn-outline btn-sm" style="margin-top: 1.25rem; width: 100%; text-align: center;">
            🛡️ Modération des Commentaires
        </a>
    </div>
</div>

<script>
function toggleFields(type) {
    const uploadDiv = document.getElementById('fields-upload');
    const mediaDiv  = document.getElementById('fields-media');
    const versetDiv = document.getElementById('fields-verset');
    
    uploadDiv.style.display = (type === 'prestation_chorale') ? 'block' : 'none';
    mediaDiv.style.display  = (type === 'prestation_chorale' || type === 'exhortation') ? 'block' : 'none';
    versetDiv.style.display = (type === 'exhortation' || type === 'temoignage') ? 'grid' : 'none';
}
toggleFields(document.getElementById('type-select').value);
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
