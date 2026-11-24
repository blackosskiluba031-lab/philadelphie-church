<?php
// admin/pasteur_messages.php
// Boîte de réception confidentielle du Pasteur

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Réservé au Pasteur (Responsable) et au Développeur
require_role([ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Boîte Pastorale Confidentielle';
$pageTitle = 'Messages au Pasteur';

// Traitement POST : Réponse ou changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Requête invalide.');
    } else {
        $msgId = (int)($_POST['msg_id'] ?? 0);
        $action = $_POST['post_action'] ?? '';

        if ($action === 'repondre' && $msgId > 0) {
            $reponse = trim($_POST['reponse_pasteur'] ?? '');
            $stmt = db()->prepare("
                UPDATE messages_pasteur 
                SET reponse_pasteur = ?, statut = 'repondu', date_reponse = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$reponse, $msgId]);
            log_admin_action(current_user()['id'], 'Réponse message pastorale', ['id' => $msgId]);
            set_flash('success', "Votre réponse a été enregistrée.");
        } elseif ($action === 'delete' && $msgId > 0) {
            db()->prepare("DELETE FROM messages_pasteur WHERE id = ?")->execute([$msgId]);
            log_admin_action(current_user()['id'], 'Suppression message pastorale', ['id' => $msgId]);
            set_flash('success', "Message supprimé.");
        }
    }
    header('Location: ' . ADMIN_URL . '/pasteur_messages.php');
    exit;
}

// Liste des messages
$messages = db()->query("
    SELECT * FROM messages_pasteur 
    ORDER BY FIELD(statut, 'non_lu', 'en_cours', 'repondu', 'archive'), created_at DESC
")->fetchAll();

$nbNonLus = (int)db()->query("SELECT COUNT(*) FROM messages_pasteur WHERE statut = 'non_lu'")->fetchColumn();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Boîte de Réception Pastorale</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Courriers et sollicitations adressés directement et confidentiellement au Pasteur
        </p>
    </div>
    <?php if ($nbNonLus > 0): ?>
        <span class="badge badge-warning" style="font-size: 0.85rem; padding: 6px 14px;">
            🔔 <?= $nbNonLus ?> nouveau(x) message(s) non lu(s)
        </span>
    <?php endif; ?>
</div>

<?php if (empty($messages)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📬</div>
        <h3>Aucun message pour le Pasteur</h3>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">
            La boîte pastorale est actuellement vide.
        </p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php foreach ($messages as $m): ?>
            <div class="card" style="padding: 1.75rem; border-left: 4px solid <?= $m['statut'] === 'non_lu' ? 'var(--color-danger)' : ($m['statut'] === 'repondu' ? 'var(--color-success)' : 'var(--gold-primary)') ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="font-size: 1.15rem; color: var(--text-primary);">
                            <?= e($m['sujet']) ?>
                        </strong>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 2px;">
                            De : <strong><?= e($m['nom_expediteur'] ?: 'Fidèle Confidentiel') ?></strong> • 
                            <?= !empty($m['telephone']) ? '📞 ' . e($m['telephone']) : '' ?>
                            <?= !empty($m['email']) ? ' • ✉️ ' . e($m['email']) : '' ?>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge <?= $m['statut'] === 'non_lu' ? 'badge-danger' : ($m['statut'] === 'repondu' ? 'badge-success' : 'badge-gold') ?>">
                            <?= $m['statut'] === 'non_lu' ? 'Nouveau' : ($m['statut'] === 'repondu' ? 'Répondu' : 'En cours') ?>
                        </span>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            <?= format_date_fr($m['created_at'], true) ?>
                        </span>
                    </div>
                </div>

                <!-- Contenu du message -->
                <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: var(--radius-md); font-size: 0.95rem; line-height: 1.6; color: var(--text-primary); margin-bottom: 1.25rem; white-space: pre-line;">
                    <?= nl2br(e($m['message'])) ?>
                </div>

                <!-- Réponse du pasteur si déjà renseignée -->
                <?php if (!empty($m['reponse_pasteur'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.08); border-left: 3px solid var(--color-success); padding: 1rem 1.25rem; border-radius: 0 var(--radius-md) var(--radius-md) 0; margin-bottom: 1.25rem;">
                        <strong style="color: var(--color-success); font-size: 0.85rem;">Votre réponse pastorale :</strong>
                        <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: var(--text-secondary);">
                            <?= nl2br(e($m['reponse_pasteur'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Actions de réponse : WhatsApp direct ou formulaire interne -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                    <div style="display: flex; gap: 10px;">
                        <?php if (!empty($m['telephone'])): ?>
                            <?php 
                                $telClean = preg_replace('/[^0-9]/', '', $m['telephone']);
                                $waText = urlencode("Bonjour {$m['nom_expediteur']}, c'est le Pasteur à la suite de votre message sur l'application concernant '{$m['sujet']}'.");
                            ?>
                            <a href="https://wa.me/<?= $telClean ?>?text=<?= $waText ?>" target="_blank" class="btn btn-primary btn-sm" style="background: #25D366; border-color: #25D366;">
                                💬 Répondre sur WhatsApp direct
                            </a>
                        <?php endif; ?>

                        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('form-rep-<?= $m['id'] ?>').style.display='block';">
                            ✏️ Rédiger une réponse interne
                        </button>
                    </div>

                    <form method="POST" action="<?= ADMIN_URL ?>/pasteur_messages.php" onsubmit="return confirm('Supprimer ce message ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="post_action" value="delete">
                        <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);">🗑️</button>
                    </form>
                </div>

                <!-- Formulaire masqué de réponse -->
                <div id="form-rep-<?= $m['id'] ?>" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-subtle);">
                    <form method="POST" action="<?= ADMIN_URL ?>/pasteur_messages.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="post_action" value="repondre">
                        <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">

                        <div class="form-group">
                            <label class="form-label">Réponse pastorale pour <?= e($m['nom_expediteur']) ?></label>
                            <textarea name="reponse_pasteur" class="form-control" rows="3" required placeholder="Votre message d'encouragement ou conseil..."><?= e($m['reponse_pasteur'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Valider la réponse</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
