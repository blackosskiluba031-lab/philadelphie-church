<?php
// admin/presences.php
// Pointage numérique des présences aux cultes et événements

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Pointage des Présences';
$pageTitle = 'Présences aux Cultes';

// Gestion de la requête AJAX de pointage immédiat
if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Jeton CSRF invalide.']);
        exit;
    }

    $eventId  = (int)($_POST['evenement_id'] ?? 0);
    $fideleId = (int)($_POST['fidele_id'] ?? 0);
    $statut   = $_POST['statut'] ?? 'present';
    $remarque = trim($_POST['remarque'] ?? '');
    $adminId  = current_user()['id'];

    if (!in_array($statut, ['present', 'absent', 'excuse'], true)) {
        echo json_encode(['success' => false, 'error' => 'Statut invalide.']);
        exit;
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO presences (evenement_id, fidele_id, statut, remarque, enregistre_par_admin_id, date_pointage)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE statut = VALUES(statut), remarque = VALUES(remarque), 
                                    enregistre_par_admin_id = VALUES(enregistre_par_admin_id), date_pointage = NOW()
        ");
        $stmt->execute([$eventId, $fideleId, $statut, $remarque ?: null, $adminId]);

        // Recalcul des totaux pour cet événement
        $stmtStats = db()->prepare("
            SELECT 
                SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) as presents,
                SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END) as absents,
                SUM(CASE WHEN statut = 'excuse' THEN 1 ELSE 0 END) as excuses,
                COUNT(*) as total_pointes
            FROM presences WHERE evenement_id = ?
        ");
        $stmtStats->execute([$eventId]);
        $stats = $stmtStats->fetch();

        echo json_encode([
            'success' => true,
            'statut'  => $statut,
            'stats'   => $stats
        ]);
        exit;
    } catch (Exception $e) {
        error_log('Erreur pointage ajax: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur BDD.']);
        exit;
    }
}

// 1. Sélection de l'événement à pointer
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Liste des événements récents ou à venir
$evenements = db()->query("
    SELECT id, titre, date_debut, lieu 
    FROM evenements 
    WHERE statut = 'publie' 
    ORDER BY date_debut DESC LIMIT 15
")->fetchAll();

if ($eventId === 0 && !empty($evenements)) {
    $eventId = (int)$evenements[0]['id'];
}

// Événement actif sélectionné
$stmtCurEvent = db()->prepare("SELECT * FROM evenements WHERE id = ?");
$stmtCurEvent->execute([$eventId]);
$currentEvent = $stmtCurEvent->fetch();

// 2. Récupération des fidèles avec leur état de présence pour cet événement
$filtreGroupe = $_GET['groupe'] ?? '';
$searchFidele = trim($_GET['q'] ?? '');

$whereF = ["f.statut = 'actif'"];
$paramsF = [$eventId];

if (!empty($filtreGroupe)) {
    $whereF[] = "f.groupe = ?";
    $paramsF[] = $filtreGroupe;
}
if ($searchFidele !== '') {
    $whereF[] = "(f.nom LIKE ? OR f.prenom LIKE ? OR f.telephone LIKE ?)";
    $paramsF[] = "%$searchFidele%";
    $paramsF[] = "%$searchFidele%";
    $paramsF[] = "%$searchFidele%";
}

$whereFSql = implode(' AND ', $whereF);

$stmtPointage = db()->prepare("
    SELECT f.id as fidele_id, f.nom, f.prenom, f.groupe, f.telephone, f.quartier,
           COALESCE(p.statut, 'non_pointe') as presence_statut,
           p.remarque, p.date_pointage
    FROM fideles f
    LEFT JOIN presences p ON f.id = p.fidele_id AND p.evenement_id = ?
    WHERE $whereFSql
    ORDER BY f.nom ASC, f.prenom ASC
");
$stmtPointage->execute($paramsF);
$pointageList = $stmtPointage->fetchAll();

// 3. Statistiques de pointage
$stmtStatsEvent = db()->prepare("
    SELECT 
        SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) as total_presents,
        SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END) as total_absents,
        SUM(CASE WHEN statut = 'excuse' THEN 1 ELSE 0 END) as total_excuses,
        COUNT(*) as total_pointes
    FROM presences WHERE evenement_id = ?
");
$stmtStatsEvent->execute([$eventId]);
$eventStats = $stmtStatsEvent->fetch();

$totalMembres = (int)db()->query("SELECT COUNT(*) FROM fideles WHERE statut = 'actif'")->fetchColumn();
$presentsCount = (int)($eventStats['total_presents'] ?? 0);
$tauxPresence = $totalMembres > 0 ? round(($presentsCount / $totalMembres) * 100) : 0;

include __DIR__ . '/../templates/admin_header.php';
?>

<!-- Sélecteur d'événement & Résumé métriques -->
<div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 1.25rem;">
        <div>
            <label for="event_select" class="form-label" style="font-size: 0.85rem;">Sélectionner le Culte ou l'Événement :</label>
            <select id="event_select" class="form-control" style="font-size: 1rem; font-weight: 600; min-width: 300px;" onchange="window.location.href='<?= ADMIN_URL ?>/presences.php?event_id=' + this.value">
                <?php foreach ($evenements as $evt): ?>
                    <option value="<?= $evt['id'] ?>" <?= $evt['id'] === $eventId ? 'selected' : '' ?>>
                        <?= e($evt['titre']) ?> — <?= format_date_fr($evt['date_debut'], true) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($currentEvent): ?>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge badge-gold" style="font-size: 0.9rem; padding: 8px 14px;">
                    📍 <?= e($currentEvent['lieu'] ?? 'Temple') ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Barre de statistiques en direct -->
    <div class="grid grid-cols-4" style="gap: 15px;">
        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Présents</span>
            <div id="stat-presents" style="font-size: 1.6rem; font-weight: 800; color: var(--color-success); font-family: var(--font-heading);">
                <?= $presentsCount ?>
            </div>
        </div>
        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Absents</span>
            <div id="stat-absents" style="font-size: 1.6rem; font-weight: 800; color: var(--color-danger); font-family: var(--font-heading);">
                <?= (int)($eventStats['total_absents'] ?? 0) ?>
            </div>
        </div>
        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Excusés</span>
            <div id="stat-excuses" style="font-size: 1.6rem; font-weight: 800; color: var(--color-warning); font-family: var(--font-heading);">
                <?= (int)($eventStats['total_excuses'] ?? 0) ?>
            </div>
        </div>
        <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Taux de Présence</span>
            <div id="stat-taux" style="font-size: 1.6rem; font-weight: 800; color: var(--gold-primary); font-family: var(--font-heading);">
                <?= $tauxPresence ?>%
            </div>
        </div>
    </div>
</div>

<!-- Filtres de recherche des fidèles pour pointage -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem 1.5rem;">
    <form method="GET" action="<?= ADMIN_URL ?>/presences.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">

        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="q" value="<?= e($searchFidele) ?>" placeholder="Rechercher un fidèle par nom ou prénom..." class="form-control">
        </div>

        <div style="min-width: 150px;">
            <select name="groupe" class="form-control" onchange="this.form.submit()">
                <option value="">Tous les groupes</option>
                <option value="jeunes" <?= $filtreGroupe === 'jeunes' ? 'selected' : '' ?>>Jeunes</option>
                <option value="hommes" <?= $filtreGroupe === 'hommes' ? 'selected' : '' ?>>Hommes</option>
                <option value="femmes" <?= $filtreGroupe === 'femmes' ? 'selected' : '' ?>>Femmes</option>
                <option value="enfants" <?= $filtreGroupe === 'enfants' ? 'selected' : '' ?>>Enfants</option>
                <option value="chorale" <?= $filtreGroupe === 'chorale' ? 'selected' : '' ?>>Chorale</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        <?php if ($searchFidele !== '' || !empty($filtreGroupe)): ?>
            <a href="<?= ADMIN_URL ?>/presences.php?event_id=<?= $eventId ?>" class="btn btn-outline btn-sm">✕</a>
        <?php endif; ?>
    </form>
</div>

<!-- Liste de Pointage Tactile / Rapide -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fidèle</th>
                    <th>Groupe</th>
                    <th>Téléphone</th>
                    <th style="text-align: center; width: 340px;">Pointage Numérique</th>
                    <th>Remarque</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointageList as $row): ?>
                    <tr id="row-fidele-<?= $row['fidele_id'] ?>">
                        <td>
                            <strong><?= e($row['nom']) ?></strong> <?= e($row['prenom'] ?? '') ?>
                            <?php if (!empty($row['quartier'])): ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">📍 <?= e($row['quartier']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-gold" style="font-size: 0.7rem;">
                                <?= ucfirst(e($row['groupe'])) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?= e($row['telephone'] ?: '-') ?>
                        </td>
                        <td style="text-align: center;">
                            <!-- Boutons de pointage interactifs AJAX -->
                            <div class="btn-group-presence" data-fidele="<?= $row['fidele_id'] ?>" style="display: inline-flex; gap: 6px;">
                                <button type="button" class="btn-pres btn btn-sm <?= $row['presence_statut'] === 'present' ? 'btn-primary' : 'btn-outline' ?>" 
                                        data-statut="present" title="Présent au culte">
                                    ✓ Présent
                                </button>
                                <button type="button" class="btn-pres btn btn-sm <?= $row['presence_statut'] === 'absent' ? 'btn-danger' : 'btn-outline' ?>" 
                                        data-statut="absent" title="Absent non excusé">
                                    ✗ Absent
                                </button>
                                <button type="button" class="btn-pres btn btn-sm <?= $row['presence_statut'] === 'excuse' ? 'badge-warning' : 'btn-outline' ?>" 
                                        data-statut="excuse" title="Absent avec motif">
                                    ⌛ Excusé
                                </button>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="form-control input-remarque" data-fidele="<?= $row['fidele_id'] ?>" 
                                   placeholder="Motif / note..." value="<?= e($row['remarque'] ?? '') ?>" 
                                   style="padding: 4px 8px; font-size: 0.85rem;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script de Pointage AJAX instantané -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const eventId = <?= $eventId ?>;
    const csrfToken = '<?= csrf_token() ?>';

    document.querySelectorAll('.btn-group-presence').forEach(group => {
        const fideleId = group.dataset.fidele;
        const buttons = group.querySelectorAll('.btn-pres');
        const row = document.getElementById(`row-fidele-${fideleId}`);
        const inputRemarque = row.querySelector('.input-remarque');

        buttons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const statut = btn.dataset.statut;
                const remarque = inputRemarque ? inputRemarque.value : '';

                // Effet visuel immédiat
                buttons.forEach(b => {
                    b.className = 'btn-pres btn btn-sm btn-outline';
                });
                if (statut === 'present') btn.className = 'btn-pres btn btn-sm btn-primary';
                else if (statut === 'absent') btn.className = 'btn-pres btn btn-sm btn-danger';
                else if (statut === 'excuse') btn.className = 'btn-pres btn btn-sm badge-warning';

                try {
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('evenement_id', eventId);
                    formData.append('fidele_id', fideleId);
                    formData.append('statut', statut);
                    formData.append('remarque', remarque);

                    const res = await fetch('<?= ADMIN_URL ?>/presences.php?ajax=1', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success && data.stats) {
                        const statP = document.getElementById('stat-presents');
                        const statA = document.getElementById('stat-absents');
                        const statE = document.getElementById('stat-excuses');
                        const statT = document.getElementById('stat-taux');

                        if (statP) statP.textContent = data.stats.presents || 0;
                        if (statA) statA.textContent = data.stats.absents || 0;
                        if (statE) statE.textContent = data.stats.excuses || 0;

                        const totalMembres = <?= $totalMembres ?>;
                        if (statT && totalMembres > 0) {
                            const pct = Math.round(((data.stats.presents || 0) / totalMembres) * 100);
                            statT.textContent = pct + '%';
                        }
                    }
                } catch (err) {
                    console.error('Erreur lors du pointage AJAX:', err);
                }
            });
        });

        // Enregistre aussi la remarque lors du changement
        if (inputRemarque) {
            inputRemarque.addEventListener('change', () => {
                const activeBtn = group.querySelector('.btn-primary, .btn-danger, .badge-warning');
                if (activeBtn) {
                    activeBtn.click();
                }
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
