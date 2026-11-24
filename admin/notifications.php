<?php
// admin/notifications.php
// Diffusion des notifications paroissiales et alertes Web Push PWA

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Notifications push réservées au rôle media et supérieur
require_role([ROLE_MEDIA, ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Notifications Web Push & Alertes';
$pageTitle = 'Notifications PWA';
$errors = [];

// Traitement POST (Envoi d'une nouvelle notification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton expiré.";
    } else {
        $postAction = $_POST['post_action'] ?? 'envoyer';

        if ($postAction === 'delete') {
            $notifId = (int)($_POST['notif_id'] ?? 0);
            if ($notifId > 0) {
                db()->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notifId]);
                log_admin_action(current_user()['id'], 'Suppression notification', ['id' => $notifId]);
                set_flash('success', "Notification supprimée de l'historique.");
            }
            header('Location: ' . ADMIN_URL . '/notifications.php');
            exit;
        }

        if ($postAction === 'envoyer') {
            $titre       = trim($_POST['titre'] ?? '');
            $message     = trim($_POST['message'] ?? '');
            $categorieId = (int)($_POST['categorie_id'] ?? 1);
            $concerne    = $_POST['concerne'] ?? 'tous';
            $urgence     = $_POST['urgence'] ?? 'normale';
            $adminId     = current_user()['id'];

            if (empty($titre)) {
                $errors[] = "Le titre de la notification est obligatoire.";
            }
            if (empty($message)) {
                $errors[] = "Le message de la notification est obligatoire.";
            }

            if (empty($errors)) {
                try {
                    db()->beginTransaction();

                    // 1. Insertion dans la table notifications
                    $stmtNotif = db()->prepare("
                        INSERT INTO notifications (categorie_id, titre, message, concerne, urgence, date_envoi, statut, admin_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 'envoye', ?, NOW())
                    ");
                    $stmtNotif->execute([$categorieId, $titre, $message, $concerne, $urgence, $adminId]);
                    $notifId = (int)db()->lastInsertId();

                    // 2. Attribution aux fidèles ciblés dans notification_fideles
                    if ($concerne === 'tous') {
                        $stmtFideles = db()->query("SELECT id FROM fideles WHERE recoit_notifications = 1 AND statut = 'actif'");
                    } else {
                        $stmtFideles = db()->prepare("SELECT id FROM fideles WHERE recoit_notifications = 1 AND statut = 'actif' AND groupe = ?");
                        $stmtFideles->execute([$concerne]);
                    }
                    $fidelesCibles = $stmtFideles->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($fidelesCibles)) {
                        $stmtLiaison = db()->prepare("INSERT INTO notification_fideles (notification_id, fidele_id, lu) VALUES (?, ?, 0)");
                        foreach ($fidelesCibles as $fId) {
                            $stmtLiaison->execute([$notifId, $fId]);
                        }
                    }

                    // 3. Décompte des abonnements PWA touchés
                    $pushCount = (int)db()->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();

                    db()->commit();
                    log_admin_action($adminId, 'Envoi notification push', ['titre' => $titre, 'cible' => $concerne, 'destinataires' => count($fidelesCibles), 'abonnements_pwa' => $pushCount]);

                    set_flash('success', "Notification envoyée avec succès à " . count($fidelesCibles) . " fidèle(s) et " . $pushCount . " appareil(s) PWA abonnés !");
                    header('Location: ' . ADMIN_URL . '/notifications.php');
                    exit;
                } catch (Exception $e) {
                    db()->rollBack();
                    error_log('Erreur envoi notif: ' . $e->getMessage());
                    $errors[] = "Une erreur est survenue lors de l'envoi.";
                }
            }
        }
    }
}

// Catégories de notifications
$categories = db()->query("SELECT id, nom, couleur FROM categories WHERE type = 'notification' OR type = 'annonce' ORDER BY nom ASC")->fetchAll();

// Total abonnements Web Push
$totalPushSubscribers = (int)db()->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();

// Historique des notifications
$notificationsList = db()->query("
    SELECT n.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
           adm.nom as auteur_nom,
           COUNT(nf.fidele_id) as total_destinataires,
           SUM(CASE WHEN nf.lu = 1 THEN 1 ELSE 0 END) as total_lus
    FROM notifications n
    LEFT JOIN categories c ON n.categorie_id = c.id
    LEFT JOIN admins adm ON n.admin_id = adm.id
    LEFT JOIN notification_fideles nf ON n.id = nf.notification_id
    GROUP BY n.id
    ORDER BY n.date_envoi DESC
")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Centre de Notifications & Alertes PWA</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Diffusion de messages instantanés sur les smartphones et ordinateurs des fidèles
        </p>
    </div>
    <div class="badge badge-gold" style="font-size: 0.85rem; padding: 6px 14px;">
        📲 <?= $totalPushSubscribers ?> appareil(s) PWA abonné(s)
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

<!-- Grille : Formulaire d'envoi & Résumé -->
<div class="grid grid-cols-3" style="gap: 2rem; margin-bottom: 3rem;">

    <!-- Formulaire d'envoi -->
    <div class="card" style="grid-column: span 2; padding: 2rem; border-top: 4px solid var(--gold-primary);">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem;">🔔 Rédiger une Notification Push</h3>

        <form method="POST" action="<?= ADMIN_URL ?>/notifications.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="envoyer">

            <div class="form-group">
                <label for="titre" class="form-label">Titre de la notification *</label>
                <input type="text" id="titre" name="titre" class="form-control" required 
                       placeholder="Ex : Rappel : Culte d'action de grâce demain à 09h30">
            </div>

            <div class="grid grid-cols-3" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="categorie_id" class="form-label">Catégorie</label>
                    <select id="categorie_id" name="categorie_id" class="form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="concerne" class="form-label">Audience Cible</label>
                    <select id="concerne" name="concerne" class="form-control">
                        <option value="tous">Tous les fidèles & abonnés</option>
                        <option value="jeunes">Groupe des Jeunes</option>
                        <option value="hommes">Groupe des Hommes</option>
                        <option value="femmes">Groupe des Femmes</option>
                        <option value="chorale">Chorale & Louange</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="urgence" class="form-label">Priorité</label>
                    <select id="urgence" name="urgence" class="form-control">
                        <option value="normale">Normale (Information)</option>
                        <option value="importante">Importante (Rappel / Culte)</option>
                        <option value="urgente">Urgente (Avis immédiat)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="message" class="form-label">Message de l'alerte *</label>
                <textarea id="message" name="message" class="form-control" rows="4" required 
                          placeholder="Texte court et percutant qui s'affichera sur l'écran des fidèles..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                🚀 Diffuser la notification maintenant
            </button>
        </form>
    </div>

    <!-- Aide & Fonctionnement Web Push -->
    <div class="card" style="background: linear-gradient(135deg, var(--bg-card) 0%, rgba(217, 119, 6, 0.08) 100%); padding: 1.75rem;">
        <h4 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--gold-primary);">
            ℹ️ À propos du Web Push
        </h4>
        <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
            Les notifications Push sont directement réceptionnées sur les téléphones et navigateurs des fidèles ayant autorisé l'application, <strong>même si l'application est fermée</strong>.
        </p>
        <ul style="font-size: 0.85rem; color: var(--text-secondary); padding-left: 18px; line-height: 1.7; margin-bottom: 1.5rem;">
            <li>Format standardisé W3C Web Push / VAPID.</li>
            <li>Enregistrement automatique des lectures dans l'application.</li>
            <li>Possibilité de cibler des groupes ministériels spécifiques.</li>
        </ul>

        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Abonnés PWA Actifs</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--gold-primary); font-family: var(--font-heading);">
                <?= $totalPushSubscribers ?>
            </div>
        </div>
    </div>

</div>

<!-- Table Historique des envois -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📜 Historique des Notifications Diffusées</h3>
        <span style="font-size: 0.85rem; color: var(--text-muted);"><?= count($notificationsList) ?> message(s)</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date d'Envoi</th>
                    <th>Titre & Message</th>
                    <th>Catégorie</th>
                    <th>Cible</th>
                    <th>Priorité</th>
                    <th>Taux de Lecture</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notificationsList)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Aucune notification n'a encore été diffusée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notificationsList as $notif): ?>
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                <?= format_date_fr($notif['date_envoi'], true) ?>
                            </td>
                            <td>
                                <strong><?= e($notif['titre']) ?></strong>
                                <p style="font-size: 0.825rem; color: var(--text-secondary); margin: 2px 0 0 0;">
                                    <?= e(truncate_text($notif['message'], 75)) ?>
                                </p>
                            </td>
                            <td>
                                <span class="badge" style="background: <?= e($notif['categorie_couleur'] ?? '#1E88E5') ?>22; color: <?= e($notif['categorie_couleur'] ?? '#1E88E5') ?>;">
                                    <?= e($notif['categorie_nom'] ?? 'Général') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-gold" style="font-size: 0.7rem;">
                                    <?= ucfirst(e($notif['concerne'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $urgClass = match($notif['urgence']) {
                                        'urgente' => 'badge-danger',
                                        'importante' => 'badge-warning',
                                        default => 'badge-outline'
                                    };
                                ?>
                                <span class="badge <?= $urgClass ?>">
                                    <?= ucfirst(e($notif['urgence'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $dest = (int)$notif['total_destinataires'];
                                    $lus = (int)$notif['total_lus'];
                                    $pct = $dest > 0 ? round(($lus / $dest) * 100) : 0;
                                ?>
                                <span style="font-weight: 600; color: <?= $pct > 50 ? 'var(--color-success)' : 'var(--gold-primary)' ?>;">
                                    <?= $lus ?> / <?= $dest ?> (<?= $pct ?>%)
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="<?= ADMIN_URL ?>/notifications.php" onsubmit="return confirm('Supprimer cette notification de l\'historique ?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_action" value="delete">
                                    <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);" title="Supprimer">
                                        🗑️
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
