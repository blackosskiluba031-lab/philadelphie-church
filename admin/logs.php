<?php
// admin/logs.php
// Journal d'audit et de traçabilité des opérations administratives

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_role(ROLE_DEVELOPPEUR);

$adminHeaderTitle = 'Journal d\'Audit & Logs';
$pageTitle = 'Journal d\'Audit';

// Purge éventuelle des anciens logs (> 60 jours)
if (isset($_POST['action']) && $_POST['action'] === 'purge') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        db()->query("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
        log_admin_action(current_user()['id'], 'Purge des anciens logs système (> 60 jours)');
        set_flash('success', "Les logs de plus de 60 jours ont été purgés.");
        header('Location: ' . ADMIN_URL . '/logs.php');
        exit;
    }
}

// Filtre par action
$actionFilter = trim($_GET['action_search'] ?? '');
$where = ["1=1"];
$params = [];

if (!empty($actionFilter)) {
    $where[] = "l.action LIKE ?";
    $params[] = "%$actionFilter%";
}

$whereSql = implode(' AND ', $where);

$stmtLogs = db()->prepare("
    SELECT l.*, a.nom as admin_nom, a.username 
    FROM admin_logs l 
    LEFT JOIN admins a ON l.admin_id = a.id 
    WHERE $whereSql 
    ORDER BY l.created_at DESC 
    LIMIT 100
");
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Journal de Traçabilité Système</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Enregistrement chronologique et immuable des opérations de gestion, connexions et modifications
        </p>
    </div>

    <form method="POST" action="<?= ADMIN_URL ?>/logs.php" onsubmit="return confirm('Purger les logs de plus de 60 jours ?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="purge">
        <button type="submit" class="btn btn-outline btn-sm">
            🧹 Nettoyer les logs anciens (> 60 j)
        </button>
    </form>
</div>

<!-- Filtre -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="<?= ADMIN_URL ?>/logs.php" style="display: flex; gap: 10px;">
        <input type="text" name="action_search" value="<?= e($actionFilter) ?>" placeholder="Filtrer par action (ex: Connexion, Modification, Pointage...)" class="form-control" style="flex: 1;">
        <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
        <?php if (!empty($actionFilter)): ?>
            <a href="<?= ADMIN_URL ?>/logs.php" class="btn btn-outline btn-sm">✕</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Administrateur</th>
                    <th>Action Réalisée</th>
                    <th>Adresse IP</th>
                    <th>Navigateur / Outil</th>
                    <th>Détails Métadonnées</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Aucune entrée d'audit enregistrée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <strong><?= e($log['admin_nom'] ?? 'Système Automatique') ?></strong>
                                <?php if (!empty($log['username'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">@<?= e($log['username']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-gold" style="font-size: 0.7rem;">
                                    <?= e($log['action']) ?>
                                </span>
                            </td>
                            <td><code><?= e($log['ip']) ?></code></td>
                            <td style="font-size: 0.75rem; color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= e($log['user_agent'] ?: '-') ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                <?= e($log['details'] ?: '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
