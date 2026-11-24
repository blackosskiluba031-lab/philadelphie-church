<?php
// admin/videos.php
// Gestion des vidéos, prédications et flux directs

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Vidéos & prédications réservées au rôle media et supérieur
require_role([ROLE_MEDIA, ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Vidéos & Directs Streaming';
$pageTitle = 'Gestion des Vidéos';
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide.";
    } else {
        $postAction = $_POST['post_action'] ?? '';
        $adminId = current_user()['id'];

        if ($postAction === 'delete_video') {
            $vidId = (int)($_POST['video_id'] ?? 0);
            db()->prepare("DELETE FROM videos WHERE id = ?")->execute([$vidId]);
            log_admin_action($adminId, 'Suppression vidéo', ['id' => $vidId]);
            set_flash('success', "Vidéo supprimée.");
            header('Location: ' . ADMIN_URL . '/videos.php');
            exit;
        }

        if ($postAction === 'save_video') {
            $id          = (int)($_POST['id'] ?? 0);
            $titre       = trim($_POST['titre'] ?? '');
            $url         = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $predicateur = trim($_POST['predicateur'] ?? '');
            $duree       = trim($_POST['duree'] ?? '');
            $dateVideo   = !empty($_POST['date_video']) ? $_POST['date_video'] : date('Y-m-d');
            $statut      = $_POST['statut'] ?? 'publie';

            if (empty($titre) || empty($url)) {
                $errors[] = "Le titre et le lien de la vidéo sont obligatoires.";
            }

            if (empty($errors)) {
                // Auto-génération de miniature YouTube si applicable
                $miniature = null;
                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
                    $miniature = "https://img.youtube.com/vi/{$m[1]}/hqdefault.jpg";
                }

                if ($id > 0) {
                    $stmt = db()->prepare("
                        UPDATE videos 
                        SET titre = ?, url = ?, description = ?, predicateur = ?, duree = ?, date_video = ?, statut = ?, 
                            miniature = COALESCE(?, miniature), updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$titre, $url, $description ?: null, $predicateur ?: null, $duree ?: null, $dateVideo, $statut, $miniature, $id]);
                    log_admin_action($adminId, 'Modification vidéo', ['titre' => $titre]);
                    set_flash('success', "Vidéo mise à jour avec succès.");
                } else {
                    $stmt = db()->prepare("
                        INSERT INTO videos 
                        (titre, url, description, predicateur, duree, date_video, statut, miniature, plateforme, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'youtube', NOW())
                    ");
                    $stmt->execute([$titre, $url, $description ?: null, $predicateur ?: null, $duree ?: null, $dateVideo, $statut, $miniature]);
                    log_admin_action($adminId, 'Publication vidéo', ['titre' => $titre]);
                    set_flash('success', "Nouvelle vidéo publiée.");
                }
                header('Location: ' . ADMIN_URL . '/videos.php');
                exit;
            }
        }

        // Gestion du Direct / Stream
        if ($postAction === 'save_stream') {
            $streamTitre = trim($_POST['stream_titre'] ?? '');
            $streamUrl   = trim($_POST['stream_url'] ?? '');
            $streamDate  = !empty($_POST['stream_date']) ? str_replace('T', ' ', $_POST['stream_date']) : date('Y-m-d H:i:s');
            $streamStatut= $_POST['stream_statut'] ?? 'en_direct';

            if (!empty($streamTitre) && !empty($streamUrl)) {
                // Si statut est 'en_direct', passer les autres à 'termine'
                if ($streamStatut === 'en_direct') {
                    db()->query("UPDATE streams SET statut = 'termine' WHERE statut = 'en_direct'");
                }
                $stmtStr = db()->prepare("
                    INSERT INTO streams (titre, plateforme, url_direct, date_debut, statut, created_at) 
                    VALUES (?, 'youtube', ?, ?, ?, NOW())
                ");
                $stmtStr->execute([$streamTitre, $streamUrl, $streamDate, $streamStatut]);
                log_admin_action($adminId, 'Mise en place flux direct', ['titre' => $streamTitre, 'statut' => $streamStatut]);
                set_flash('success', "Le flux en direct a été activé !");
                header('Location: ' . ADMIN_URL . '/videos.php');
                exit;
            }
        }
    }
}

// Données d'édition
$videoData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$editId]);
    $videoData = $stmt->fetch();
}

$videosList = db()->query("SELECT * FROM videos ORDER BY date_video DESC, id DESC")->fetchAll();
$streamsList = db()->query("SELECT * FROM streams ORDER BY date_debut DESC LIMIT 5")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Gestion des Vidéos & Enseignements</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Publication des cultes enregistrés, séries de prédications et streaming en direct
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-outline-gold btn-sm" onclick="document.getElementById('modal-stream').style.display='flex';">
            🔴 Configurer un Direct
        </button>
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
            <a href="<?= ADMIN_URL ?>/videos.php?action=create" class="btn btn-primary btn-sm">
                ➕ Ajouter une Vidéo
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="card" style="max-width: 750px; margin-bottom: 2.5rem; padding: 2rem;">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem;">
            <?= $action === 'edit' ? '✏️ Modifier la Vidéo' : '➕ Publier une Nouvelle Vidéo' ?>
        </h3>

        <form method="POST" action="<?= ADMIN_URL ?>/videos.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save_video">
            <input type="hidden" name="id" value="<?= $videoData['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="titre" class="form-label">Titre de la Prédication ou du Chant *</label>
                <input type="text" id="titre" name="titre" class="form-control" required 
                       placeholder="Ex : La fidélité de Dieu dans nos épreuves..." 
                       value="<?= e($videoData['titre'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="url" class="form-label">Lien de la Vidéo (YouTube ou autre) *</label>
                <input type="url" id="url" name="url" class="form-control" required 
                       placeholder="https://www.youtube.com/watch?v=..." 
                       value="<?= e($videoData['url'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-3" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="predicateur" class="form-label">Orateur / Chantre</label>
                    <input type="text" id="predicateur" name="predicateur" class="form-control" 
                           placeholder="Pasteur Samuel, Chorale..." 
                           value="<?= e($videoData['predicateur'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="duree" class="form-label">Durée (ex: 45:30)</label>
                    <input type="text" id="duree" name="duree" class="form-control" 
                           placeholder="42:15" 
                           value="<?= e($videoData['duree'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="date_video" class="form-label">Date du Culte</label>
                    <input type="date" id="date_video" name="date_video" class="form-control" 
                           value="<?= e($videoData['date_video'] ?? date('Y-m-d')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description ou Résumé</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= e($videoData['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="statut" class="form-label">Statut</label>
                <select id="statut" name="statut" class="form-control">
                    <option value="publie" <?= ($videoData['statut'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié sur le site</option>
                    <option value="brouillon" <?= ($videoData['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="archive" <?= ($videoData['statut'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer la vidéo</button>
                <a href="<?= ADMIN_URL ?>/videos.php" class="btn btn-outline btn-lg">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Table des vidéos -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Titre & Prédicateur</th>
                    <th>Date</th>
                    <th>Durée</th>
                    <th>Vues</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($videosList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Aucune vidéo enregistrée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($videosList as $vid): ?>
                        <tr>
                            <td>
                                <strong><?= e($vid['titre']) ?></strong>
                                <?php if (!empty($vid['predicateur'])): ?>
                                    <div style="font-size: 0.8rem; color: var(--gold-primary);">🎙️ <?= e($vid['predicateur']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= format_date_fr($vid['date_video']) ?></td>
                            <td><?= e($vid['duree'] ?: '-') ?></td>
                            <td style="color: var(--gold-primary); font-weight: 600;">👁️ <?= (int)$vid['vues_count'] ?></td>
                            <td>
                                <span class="badge <?= $vid['statut'] === 'publie' ? 'badge-success' : 'badge-outline' ?>">
                                    <?= ucfirst(e($vid['statut'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= e($vid['url']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Voir la vidéo">▶</a>
                                    <a href="<?= ADMIN_URL ?>/videos.php?action=edit&id=<?= $vid['id'] ?>" class="btn btn-outline btn-sm" title="Modifier">✏️</a>
                                    <form method="POST" action="<?= ADMIN_URL ?>/videos.php" style="display: inline;" onsubmit="return confirm('Supprimer cette vidéo ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="delete_video">
                                        <input type="hidden" name="video_id" value="<?= $vid['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Direct Streaming -->
<div id="modal-stream" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 550px; background: var(--bg-card); padding: 2rem; border: 1px solid var(--border-accent);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.3rem;">🔴 Activer un Flux en Direct (Live)</h3>
            <button type="button" onclick="document.getElementById('modal-stream').style.display='none';" style="background: none; border: none; color: var(--text-primary); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= ADMIN_URL ?>/videos.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save_stream">

            <div class="form-group">
                <label for="stream_titre" class="form-label">Titre du Culte en Direct *</label>
                <input type="text" id="stream_titre" name="stream_titre" class="form-control" required placeholder="Ex : Grand Culte Dominical en Direct">
            </div>

            <div class="form-group">
                <label for="stream_url" class="form-label">Lien du Live (YouTube Live, Facebook Live, Zoom...) *</label>
                <input type="url" id="stream_url" name="stream_url" class="form-control" required placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div class="grid grid-cols-2">
                <div class="form-group">
                    <label for="stream_date" class="form-label">Date & Heure</label>
                    <input type="datetime-local" id="stream_date" name="stream_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="form-group">
                    <label for="stream_statut" class="form-label">État du Live</label>
                    <select id="stream_statut" name="stream_statut" class="form-control">
                        <option value="en_direct">🔴 En direct maintenant</option>
                        <option value="programme">⏳ Culte programmé</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">Activer la diffusion</button>
                <button type="button" class="btn btn-outline btn-lg" onclick="document.getElementById('modal-stream').style.display='none';">Annuler</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
