<?php
// admin/departements.php
// Gestion des départements et ministères de l'église

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Départements & Ministères';
$pageTitle = 'Départements';
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton de sécurité expiré.";
    } else {
        $postAction = $_POST['post_action'] ?? 'save';

        if ($postAction === 'delete') {
            $deptId = (int)($_POST['dept_id'] ?? 0);
            if ($deptId > 0) {
                db()->prepare("DELETE FROM departements WHERE id = ?")->execute([$deptId]);
                log_admin_action(current_user()['id'], 'Suppression département', ['id' => $deptId]);
                set_flash('success', "Le département a été supprimé.");
            }
            header('Location: ' . ADMIN_URL . '/departements.php');
            exit;
        }

        if ($postAction === 'save') {
            $id          = (int)($_POST['id'] ?? 0);
            $nom         = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $couleur     = trim($_POST['couleur'] ?? '#0F172A');
            $responsable = trim($_POST['responsable_nom'] ?? '');
            $actif       = isset($_POST['actif']) ? 1 : 0;

            if (empty($nom)) {
                $errors[] = "Le nom du département est obligatoire.";
            }

            if (empty($errors)) {
                try {
                    if ($id > 0) {
                        $stmt = db()->prepare("
                            UPDATE departements 
                            SET nom = ?, description = ?, couleur = ?, responsable_nom = ?, actif = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$nom, $description ?: null, $couleur, $responsable ?: null, $actif, $id]);
                        log_admin_action(current_user()['id'], 'Modification département', ['nom' => $nom]);
                        set_flash('success', "Département mis à jour avec succès.");
                    } else {
                        $stmt = db()->prepare("
                            INSERT INTO departements (nom, description, couleur, responsable_nom, actif, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$nom, $description ?: null, $couleur, $responsable ?: null, $actif]);
                        log_admin_action(current_user()['id'], 'Création département', ['nom' => $nom]);
                        set_flash('success', "Nouveau département créé avec succès.");
                    }
                    header('Location: ' . ADMIN_URL . '/departements.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Erreur departement: ' . $e->getMessage());
                    $errors[] = "Ce nom de département existe déjà ou est invalide.";
                }
            }
        }
    }
}

// Données d'édition
$deptData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM departements WHERE id = ?");
    $stmt->execute([$editId]);
    $deptData = $stmt->fetch();
}

// Liste avec effectifs
$departements = db()->query("
    SELECT d.*, COUNT(fd.fidele_id) as total_membres
    FROM departements d
    LEFT JOIN fidele_departements fd ON d.id = fd.departement_id
    GROUP BY d.id
    ORDER BY d.nom ASC
")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Ministères & Départements</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Organisation des équipes de service et répartition des membres
        </p>
    </div>
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
        <a href="<?= ADMIN_URL ?>/departements.php?action=create" class="btn btn-primary">
            ➕ Nouveau Département
        </a>
    <?php endif; ?>
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
    <div class="card" style="max-width: 650px; margin-bottom: 2.5rem; padding: 2rem;">
        <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;">
            <?= $action === 'edit' ? '✏️ Modifier le Département' : '➕ Nouveau Département' ?>
        </h3>

        <form method="POST" action="<?= ADMIN_URL ?>/departements.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= $deptData['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="nom" class="form-label">Nom du Département / Ministère *</label>
                <input type="text" id="nom" name="nom" class="form-control" required 
                       placeholder="Ex : Chorale & Louange, Accueil..." 
                       value="<?= e($deptData['nom'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="responsable_nom" class="form-label">Nom du Responsable</label>
                <input type="text" id="responsable_nom" name="responsable_nom" class="form-control" 
                       placeholder="Ex : Frère David Koffi" 
                       value="<?= e($deptData['responsable_nom'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description & Rôle du Ministère</label>
                <textarea id="description" name="description" class="form-control" rows="3" 
                          placeholder="Missions, objectifs et composition de l'équipe..."><?= e($deptData['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="couleur" class="form-label">Couleur d'identification</label>
                    <input type="color" id="couleur" name="couleur" class="form-control" style="height: 44px; padding: 4px;" 
                           value="<?= e($deptData['couleur'] ?? '#D97706') ?>">
                </div>
                <div class="form-group" style="display: flex; align-items: center; padding-top: 25px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="actif" value="1" <?= (!isset($deptData) || $deptData['actif']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--gold-primary);">
                        <strong>Département actif</strong>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="<?= ADMIN_URL ?>/departements.php" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Grille des départements -->
<div class="grid grid-cols-3">
    <?php foreach ($departements as $d): ?>
        <div class="card" style="border-top: 4px solid <?= e($d['couleur'] ?? '#D97706') ?>; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <h3 style="font-size: 1.25rem; margin: 0;"><?= e($d['nom']) ?></h3>
                    <span class="badge badge-gold">
                        <?= (int)$d['total_membres'] ?> membre(s)
                    </span>
                </div>

                <p style="color: var(--gold-primary); font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">
                    👤 Resp. : <?= e($d['responsable_nom'] ?: 'Non désigné') ?>
                </p>

                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    <?= nl2br(e($d['description'] ?? 'Aucune description.')) ?>
                </p>
            </div>

            <div style="border-top: 1px solid var(--border-subtle); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <span class="badge <?= $d['actif'] ? 'badge-success' : 'badge-outline' ?>">
                    <?= $d['actif'] ? 'Actif' : 'Inactif' ?>
                </span>
                <div style="display: flex; gap: 6px;">
                    <a href="<?= ADMIN_URL ?>/departements.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">
                        ✏️ Modifier
                    </a>
                    <form method="POST" action="<?= ADMIN_URL ?>/departements.php" style="display: inline;" onsubmit="return confirm('Supprimer ce département ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="post_action" value="delete">
                        <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
