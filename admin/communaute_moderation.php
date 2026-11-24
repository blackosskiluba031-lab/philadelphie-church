<?php
// admin/communaute_moderation.php
// Modération des publications et des commentaires du Mur Fraternel

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Modération des Publications';
$pageTitle = 'Modération Publications';

// Traitement POST : Suppression ou masquage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Requête invalide.');
    } else {
        $action = $_POST['post_action'] ?? '';
        $adminId = current_user()['id'];

        if ($action === 'delete_pub') {
            $pubId = (int)($_POST['pub_id'] ?? 0);
            db()->prepare("DELETE FROM publications_communaute WHERE id = ?")->execute([$pubId]);
            log_admin_action($adminId, 'Modération suppression publication', ['id' => $pubId]);
            set_flash('success', "Publication supprimée du mur.");
        } elseif ($action === 'delete_comment') {
            $comId = (int)($_POST['comment_id'] ?? 0);
            db()->prepare("DELETE FROM commentaires_communaute WHERE id = ?")->execute([$comId]);
            log_admin_action($adminId, 'Modération suppression commentaire', ['id' => $comId]);
            set_flash('success', "Commentaire supprimé.");
        }
    }
    header('Location: ' . ADMIN_URL . '/communaute_moderation.php');
    exit;
}

// Publications
$pubs = db()->query("
    SELECT p.*, COUNT(c.id) as nb_commentaires 
    FROM publications_communaute p 
    LEFT JOIN commentaires_communaute c ON p.id = c.publication_id 
    GROUP BY p.id 
    ORDER BY p.created_at DESC
")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Modération du Mur « Vivre Ensemble »</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Surveillance bienveillante des partages, chorales et commentaires des membres
        </p>
    </div>
    <a href="<?= BASE_URL ?>/communaute.php" target="_blank" class="btn btn-outline-gold btn-sm">
        🌐 Voir le mur public
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Auteur</th>
                    <th>Type</th>
                    <th>Titre & Extrait</th>
                    <th>Interactions</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pubs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Aucune publication à modérer.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pubs as $p): ?>
                        <tr>
                            <td><strong><?= e($p['auteur_nom']) ?></strong></td>
                            <td>
                                <span class="badge badge-gold" style="font-size: 0.65rem;">
                                    <?= strtoupper(e(str_replace('_', ' ', $p['type']))) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= e($p['titre']) ?></strong>
                                <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--text-secondary);">
                                    <?= e(truncate_text($p['contenu'], 65)) ?>
                                </p>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                ❤️ <?= (int)$p['likes_count'] ?> • 💬 <?= (int)$p['nb_commentaires'] ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="<?= ADMIN_URL ?>/communaute_moderation.php" onsubmit="return confirm('Supprimer définitivement ce message et ses commentaires ?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_action" value="delete_pub">
                                    <input type="hidden" name="pub_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
