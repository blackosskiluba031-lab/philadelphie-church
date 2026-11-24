<?php
// admin/dons.php
// Gestion de la trésorerie paroissiale, dîmes, offrandes et dons

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Réservé au Pasteur (Responsable) et au Développeur
require_role([ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Gestion des Dons & Dîmes';
$pageTitle = 'Dons & Finances';
$deviseDefaut = get_param('devise_defaut', 'EUR');
$errors = [];

// Traitement POST (Validation de don / Ajout manuel d'offrande)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide.";
    } else {
        $postAction = $_POST['post_action'] ?? '';
        $adminId = current_user()['id'];

        if ($postAction === 'valider') {
            $donId = (int)($_POST['don_id'] ?? 0);
            db()->prepare("UPDATE dons SET statut = 'valide', enregistre_par_admin_id = ? WHERE id = ?")->execute([$adminId, $donId]);
            log_admin_action($adminId, 'Validation don', ['id' => $donId]);
            set_flash('success', "Le don a été validé et intégré aux écritures comptables.");
            header('Location: ' . ADMIN_URL . '/dons.php');
            exit;
        }

        if ($postAction === 'annuler') {
            $donId = (int)($_POST['don_id'] ?? 0);
            db()->prepare("UPDATE dons SET statut = 'annule' WHERE id = ?")->execute([$donId]);
            log_admin_action($adminId, 'Annulation don', ['id' => $donId]);
            set_flash('info', "Le don a été marqué comme annulé.");
            header('Location: ' . ADMIN_URL . '/dons.php');
            exit;
        }

        if ($postAction === 'create_manuel') {
            $nom       = trim($_POST['nom_donateur'] ?? 'Donateur Anonyme');
            $typeDon   = $_POST['type_don'] ?? 'offrande';
            $montant   = floatval(str_replace(',', '.', $_POST['montant'] ?? '0'));
            $devise    = in_array($_POST['devise'] ?? '', ['FC', 'USD']) ? $_POST['devise'] : $deviseDefaut;
            $methode   = $_POST['methode'] ?? 'especes';
            $remarque  = trim($_POST['remarque'] ?? '');
            $dateDon   = !empty($_POST['date_don']) ? $_POST['date_don'] : date('Y-m-d H:i:s');

            if ($montant <= 0) {
                $errors[] = "Le montant doit être supérieur à zéro.";
            }

            if (empty($errors)) {
                $ref = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = db()->prepare("
                    INSERT INTO dons 
                    (nom_donateur, type_don, montant, devise, methode, reference_transaction, statut, remarque, date_don, enregistre_par_admin_id) 
                    VALUES (?, ?, ?, ?, ?, ?, 'valide', ?, ?, ?)
                ");
                $stmt->execute([$nom ?: 'Anonyme', $typeDon, $montant, $devise, $methode, $ref, $remarque ?: null, $dateDon, $adminId]);
                log_admin_action($adminId, 'Enregistrement don manuel', ['montant' => $montant, 'devise' => $devise, 'type' => $typeDon]);
                set_flash('success', "Don de " . format_currency($montant, $devise) . " enregistré avec succès.");
                header('Location: ' . ADMIN_URL . '/dons.php');
                exit;
            }
        }
    }
}

// Filtres
$filtreStatut = $_GET['statut'] ?? '';
$filtreType   = $_GET['type'] ?? '';

$where = ["1=1"];
$params = [];

if (!empty($filtreStatut)) {
    $where[] = "d.statut = ?";
    $params[] = $filtreStatut;
}
if (!empty($filtreType)) {
    $where[] = "d.type_don = ?";
    $params[] = $filtreType;
}

$whereClause = implode(' AND ', $where);

// Liste des dons
$stmtDons = db()->prepare("
    SELECT d.*, adm.nom as admin_nom, f.nom as fidele_nom, f.prenom as fidele_prenom 
    FROM dons d 
    LEFT JOIN admins adm ON d.enregistre_par_admin_id = adm.id 
    LEFT JOIN fideles f ON d.fidele_id = f.id 
    WHERE $whereClause 
    ORDER BY d.date_don DESC
");
$stmtDons->execute($params);
$donsList = $stmtDons->fetchAll();

// Métriques financières multidevises
$totauxValides = db()->query("
    SELECT devise, COALESCE(SUM(montant), 0) as total 
    FROM dons 
    WHERE statut = 'valide' 
    GROUP BY devise
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalFC = (float)($totauxValides['FC'] ?? ($totauxValides['CDF'] ?? 0));
$totalUSD = (float)($totauxValides['USD'] ?? 0);

$enAttenteCount = (int)db()->query("SELECT COUNT(*) FROM dons WHERE statut = 'en_attente'")->fetchColumn();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Finances & Trésorerie Paroissiale</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Suivi des dîmes, offrandes dominicales et contributions aux projets de l'église (RDC : FC & USD)
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-don-manuel').style.display='flex';">
        ➕ Enregistrer une Entrée de Culte
    </button>
</div>

<!-- Métriques financières -->
<div class="grid grid-cols-3" style="margin-bottom: 2rem;">
    <div class="metric-card">
        <div>
            <span class="metric-label">Recettes Validées (Franc Congolais)</span>
            <div class="metric-value" style="font-size: 1.6rem;"><?= number_format($totalFC, 0, ',', ' ') ?> FC</div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Trésorerie locale</span>
        </div>
        <div class="metric-icon">💰</div>
    </div>

    <div class="metric-card">
        <div>
            <span class="metric-label">Recettes Validées (Dollars USD)</span>
            <div class="metric-value" style="font-size: 1.6rem; color: var(--color-success);">$ <?= number_format($totalUSD, 2, '.', ' ') ?></div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Devises étrangères</span>
        </div>
        <div class="metric-icon">💵</div>
    </div>

    <div class="metric-card" style="<?= $enAttenteCount > 0 ? 'border-color: var(--color-warning);' : '' ?>">
        <div>
            <span class="metric-label">Dons en Attente de Validation</span>
            <div class="metric-value" style="<?= $enAttenteCount > 0 ? 'color: var(--color-warning);' : '' ?>">
                <?= $enAttenteCount ?>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                Promesses / déclarations reçues
            </span>
        </div>
        <div class="metric-icon">⏳</div>
    </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem 1.5rem;">
    <form method="GET" action="<?= ADMIN_URL ?>/dons.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <select name="type" class="form-control" style="width: auto; min-width: 180px;">
            <option value="">Tous les types</option>
            <option value="dime" <?= $filtreType === 'dime' ? 'selected' : '' ?>>Dîmes</option>
            <option value="offrande" <?= $filtreType === 'offrande' ? 'selected' : '' ?>>Offrandes dominicales</option>
            <option value="construction" <?= $filtreType === 'construction' ? 'selected' : '' ?>>Projets / Construction</option>
            <option value="action_sociale" <?= $filtreType === 'action_sociale' ? 'selected' : '' ?>>Action Sociale</option>
        </select>

        <select name="statut" class="form-control" style="width: auto; min-width: 160px;">
            <option value="">Tous les statuts</option>
            <option value="valide" <?= $filtreStatut === 'valide' ? 'selected' : '' ?>>Validés</option>
            <option value="en_attente" <?= $filtreStatut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
            <option value="annule" <?= $filtreStatut === 'annule' ? 'selected' : '' ?>>Annulés</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        <?php if (!empty($filtreType) || !empty($filtreStatut)): ?>
            <a href="<?= ADMIN_URL ?>/dons.php" class="btn btn-outline btn-sm">✕ Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table des dons -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Réf. Transaction</th>
                    <th>Date</th>
                    <th>Donateur</th>
                    <th>Affectation</th>
                    <th>Moyen</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($donsList)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Aucun don enregistré avec ces critères.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($donsList as $d): ?>
                        <tr>
                            <td>
                                <code><?= e($d['reference_transaction'] ?: 'DON-' . $d['id']) ?></code>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= date('d/m/Y', strtotime($d['date_don'])) ?>
                            </td>
                            <td>
                                <strong><?= e($d['nom_donateur'] ?? 'Anonyme') ?></strong>
                                <?php if (!empty($d['remarque'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                                        « <?= e($d['remarque']) ?> »
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-gold" style="font-size: 0.7rem;">
                                    <?= strtoupper(e($d['type_don'])) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= ucfirst(e($d['methode'])) ?>
                            </td>
                            <td style="font-weight: 700; color: var(--gold-primary); font-size: 1.05rem;">
                                <?= format_currency((float)$d['montant'], $d['devise']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $d['statut'] === 'valide' ? 'badge-success' : ($d['statut'] === 'en_attente' ? 'badge-warning' : 'badge-danger') ?>">
                                    <?= ucfirst(e($d['statut'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($d['statut'] === 'en_attente'): ?>
                                    <form method="POST" action="<?= ADMIN_URL ?>/dons.php" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="valider">
                                        <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" title="Valider l'encaissement">
                                            ✓ Valider
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= ADMIN_URL ?>/dons.php" style="display: inline;" onsubmit="return confirm('Annuler ce don ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="annuler">
                                        <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);" title="Annuler">
                                            ✗
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Enregistré</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Don Manuel / Quête du Culte -->
<div id="modal-don-manuel" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="width: 100%; max-width: 550px; background: var(--bg-card); padding: 2rem; border: 1px solid var(--border-accent);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.3rem;">➕ Enregistrer une Entrée de Culte</h3>
            <button type="button" onclick="document.getElementById('modal-don-manuel').style.display='none';" style="background: none; border: none; color: var(--text-primary); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="<?= ADMIN_URL ?>/dons.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="create_manuel">

            <div class="form-group">
                <label for="type_don" class="form-label">Type d'Offrande *</label>
                <select id="type_don" name="type_don" class="form-control" required>
                    <option value="offrande">Offrande dominicale (Tronc / Corbeille)</option>
                    <option value="dime">Dîme nominative</option>
                    <option value="construction">Projet Rénovation / Équipement</option>
                    <option value="action_sociale">Action Sociale / Solidarité</option>
                </select>
            </div>

            <div class="grid grid-cols-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label for="montant_manuel" class="form-label">Montant *</label>
                    <input type="number" id="montant_manuel" name="montant" step="any" min="1" class="form-control" required placeholder="Ex : 50000">
                </div>
                <div class="form-group">
                    <label for="devise_manuel" class="form-label">Devise *</label>
                    <select id="devise_manuel" name="devise" class="form-control" required>
                        <option value="FC" selected>FC (Franc Congolais)</option>
                        <option value="USD">USD ($)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="methode_manuel" class="form-label">Mode *</label>
                    <select id="methode_manuel" name="methode" class="form-control" required>
                        <option value="especes">Espèces (Culte)</option>
                        <option value="mobile_money">Mobile Money (M-Pesa / Airtel)</option>
                        <option value="virement">Virement bancaire</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="nom_manuel" class="form-label">Nom du donateur (Laisser vide si anonyme)</label>
                <input type="text" id="nom_manuel" name="nom_donateur" class="form-control" placeholder="Donateur anonyme ou Nom du fidèle">
            </div>

            <div class="form-group">
                <label for="date_manuel" class="form-label">Date du Culte</label>
                <input type="date" id="date_manuel" name="date_don" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="remarque_manuel" class="form-label">Observation ou Remarque</label>
                <input type="text" id="remarque_manuel" name="remarque" class="form-control" placeholder="Ex : Quête culte d'action de grâce">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">Valider l'entrée</button>
                <button type="button" class="btn btn-outline btn-lg" onclick="document.getElementById('modal-don-manuel').style.display='none';">Annuler</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
