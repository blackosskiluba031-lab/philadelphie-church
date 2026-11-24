<?php
// admin/prieres.php
// Suivi pastoral et traitement des demandes de prière

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Réservé au Pasteur (Responsable) et au Développeur
require_role([ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Intercession & Sujets de Prière';
$pageTitle = 'Demandes de Prière';

// Traitement changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Requête invalide.');
    } else {
        $priereId = (int) ($_POST['priere_id'] ?? 0);
        $newStatut = $_POST['new_statut'] ?? '';
        $adminId = current_user()['id'];

        if ($priereId > 0 && in_array($newStatut, ['nouveau', 'en_priere', 'exauce', 'archive'], true)) {
            $stmt = db()->prepare("
                UPDATE demandes_priere 
                SET statut = ?, traite_par_admin_id = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$newStatut, $adminId, $priereId]);
            log_admin_action($adminId, 'Mise à jour statut prière', ['id' => $priereId, 'statut' => $newStatut]);
            set_flash('success', "Le statut du sujet de prière a été actualisé.");
        } elseif (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            db()->prepare("DELETE FROM demandes_priere WHERE id = ?")->execute([$delId]);
            log_admin_action($adminId, 'Suppression sujet de prière', ['id' => $delId]);
            set_flash('success', "Demande de prière supprimée.");
        }
    }
    header('Location: ' . ADMIN_URL . '/prieres.php' . (isset($_GET['statut']) ? '?statut=' . urlencode($_GET['statut']) : ''));
    exit;
}

// Filtre
$filtreStatut = $_GET['statut'] ?? '';
$where = ["1=1"];
$params = [];

if (!empty($filtreStatut)) {
    $where[] = "dp.statut = ?";
    $params[] = $filtreStatut;
}

$whereClause = implode(' AND ', $where);

$stmtPrieres = db()->prepare("
    SELECT dp.*, adm.nom as traite_par_nom 
    FROM demandes_priere dp
    LEFT JOIN admins adm ON dp.traite_par_admin_id = adm.id
    WHERE $whereClause
    ORDER BY FIELD(dp.statut, 'nouveau', 'en_priere', 'exauce', 'archive'), dp.created_at DESC
");
$stmtPrieres->execute($params);
$prieresList = $stmtPrieres->fetchAll();

// Compteurs par statut
$statsPrieres = db()->query("
    SELECT 
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as nb_nouveau,
        SUM(CASE WHEN statut = 'en_priere' THEN 1 ELSE 0 END) as nb_en_priere,
        SUM(CASE WHEN statut = 'exauce' THEN 1 ELSE 0 END) as nb_exauce,
        COUNT(*) as total
    FROM demandes_priere
")->fetch();

include __DIR__ . '/../templates/admin_header.php';
?>

<!-- En-tête et compteurs -->
<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Cellule d'Intercession & Pastorale</h2>
    <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
        Accompagnement spirituel, prières pour les fidèles et célébration des témoignages
    </p>
</div>

<!-- Filtres par statut -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem 1.5rem;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?= ADMIN_URL ?>/prieres.php"
            class="btn <?= empty($filtreStatut) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            Toutes (<?= (int) $statsPrieres['total'] ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/prieres.php?statut=nouveau"
            class="btn <?= $filtreStatut === 'nouveau' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🔔 Nouvelles requêtes (<?= (int) $statsPrieres['nb_nouveau'] ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/prieres.php?statut=en_priere"
            class="btn <?= $filtreStatut === 'en_priere' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🙏 En cours de prière (<?= (int) $statsPrieres['nb_en_priere'] ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/prieres.php?statut=exauce"
            class="btn <?= $filtreStatut === 'exauce' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ✨ Exaucées (<?= (int) $statsPrieres['nb_exauce'] ?>)
        </a>
    </div>
</div>

<!-- Liste des demandes de prière -->
<?php if (empty($prieresList)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🕊️</div>
        <h3>Aucun sujet de prière dans cette catégorie</h3>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">
            Toutes les demandes ont été traitées ou aucune n'a encore été déposée.
        </p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2">
        <?php foreach ($prieresList as $p): ?>
            <div class="card"
                style="display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid <?= $p['statut'] === 'nouveau' ? 'var(--color-warning)' : ($p['statut'] === 'exauce' ? 'var(--color-success)' : 'var(--gold-primary)') ?>;">
                <div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <div>
                            <strong style="font-size: 1.05rem; color: var(--text-primary);">
                                <?= $p['anonyme'] ? '🔒 Fidèle Anonyme' : e($p['nom_demandeur'] ?: 'Fidèle non nommé') ?>
                            </strong>
                            <?php if (!$p['anonyme'] && (!empty($p['telephone']) || !empty($p['email']))): ?>
                                <div style="font-size: 0.8rem; color: var(--gold-primary); margin-top: 2px;">
                                    <?= !empty($p['telephone']) ? '📞 ' . e($p['telephone']) : '' ?>
                                    <?= !empty($p['email']) ? ' ✉️ ' . e($p['email']) : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        $badgeClass = match ($p['statut']) {
                            'nouveau' => 'badge-warning',
                            'en_priere' => 'badge-gold',
                            'exauce' => 'badge-success',
                            default => 'badge-outline'
                        };
                        $badgeLabel = match ($p['statut']) {
                            'nouveau' => '🔔 Nouveau',
                            'en_priere' => '🙏 En Prière',
                            'exauce' => '✨ Exaucé',
                            default => ucfirst($p['statut'])
                        };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                    </div>

                    <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--text-primary);">
                        <?= e($p['sujet']) ?>
                    </h4>

                    <div
                        style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); font-size: 0.925rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 1.25rem; white-space: pre-line;">
                        <?= nl2br(e($p['message'])) ?>
                    </div>
                </div>

                <div
                    style="border-top: 1px solid var(--border-subtle); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        🗓️ <?= format_date_fr($p['created_at'], true) ?>
                    </span>

                    <!-- Formulaire de changement d'état -->
                    <form method="POST" action="<?= ADMIN_URL ?>/prieres.php" style="display: flex; gap: 6px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="priere_id" value="<?= $p['id'] ?>">

                        <?php if ($p['statut'] !== 'en_priere'): ?>
                            <button type="submit" name="new_statut" value="en_priere" class="btn btn-outline-gold btn-sm"
                                title="Prendre en charge dans la liste d'intercession">
                                🙏 Porter en Prière
                            </button>
                        <?php endif; ?>

                        <?php if ($p['statut'] !== 'exauce'): ?>
                            <button type="submit" name="new_statut" value="exauce" class="btn btn-outline btn-sm"
                                style="color: var(--color-success);" title="Témoignage reçu : Exaucé !">
                                ✨ Exaucé
                            </button>
                        <?php endif; ?>

                        <?php if ($p['statut'] !== 'archive'): ?>
                            <button type="submit" name="new_statut" value="archive" class="btn btn-outline btn-sm" title="Archiver">
                                📁
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>