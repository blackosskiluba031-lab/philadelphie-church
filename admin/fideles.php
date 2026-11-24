<?php
// admin/fideles.php
// Gestion du répertoire des fidèles et membres de l'église

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Gestion des Fidèles';
$pageTitle = 'Fidèles & Membres';
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Traitement POST (Ajout / Modification / Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton de sécurité expiré.";
    } else {
        $postAction = $_POST['post_action'] ?? 'save';

        if ($postAction === 'delete') {
            $fideleId = (int)($_POST['fidele_id'] ?? 0);
            if ($fideleId > 0) {
                $stmtNom = db()->prepare("SELECT nom, prenom FROM fideles WHERE id = ?");
                $stmtNom->execute([$fideleId]);
                $fidele = $stmtNom->fetch();

                if ($fidele) {
                    db()->prepare("DELETE FROM fideles WHERE id = ?")->execute([$fideleId]);
                    log_admin_action(current_user()['id'], 'Suppression fidèle', ['id' => $fideleId, 'nom' => $fidele['nom'] . ' ' . $fidele['prenom']]);
                    set_flash('success', "Le fidèle " . e($fidele['prenom'] . ' ' . $fidele['nom']) . " a été supprimé.");
                }
            }
            header('Location: ' . ADMIN_URL . '/fideles.php');
            exit;
        }

        if ($postAction === 'save') {
            $id            = (int)($_POST['id'] ?? 0);
            $nom           = trim($_POST['nom'] ?? '');
            $prenom        = trim($_POST['prenom'] ?? '');
            $sexe          = $_POST['sexe'] ?? 'M';
            $telephone     = trim($_POST['telephone'] ?? '');
            $email         = trim($_POST['email'] ?? '');
            $quartier      = trim($_POST['quartier'] ?? '');
            $groupe        = $_POST['groupe'] ?? 'autre';
            $statut        = $_POST['statut'] ?? 'actif';
            $dateNaissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
            $deptIds       = isset($_POST['departements']) && is_array($_POST['departements']) ? array_map('intval', $_POST['departements']) : [];

            if (empty($nom)) {
                $errors[] = "Le nom de famille est obligatoire.";
            }

            // Vérification unicité téléphone
            if (!empty($telephone)) {
                $stmtCheckTel = db()->prepare("SELECT id FROM fideles WHERE telephone = ? AND id != ?");
                $stmtCheckTel->execute([$telephone, $id]);
                if ($stmtCheckTel->fetch()) {
                    $errors[] = "Ce numéro de téléphone est déjà attribué à un autre fidèle.";
                }
            }

            if (empty($errors)) {
                try {
                    db()->beginTransaction();

                    if ($id > 0) {
                        // Mise à jour
                        $stmt = db()->prepare("
                            UPDATE fideles 
                            SET nom = ?, prenom = ?, sexe = ?, telephone = ?, email = ?, 
                                quartier = ?, groupe = ?, statut = ?, date_naissance = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$nom, $prenom, $sexe, $telephone ?: null, $email ?: null, $quartier ?: null, $groupe, $statut, $dateNaissance, $id]);
                        log_admin_action(current_user()['id'], 'Modification fidèle', ['id' => $id, 'nom' => "$nom $prenom"]);
                        $fideleId = $id;
                        $msg = "Fiche du fidèle mise à jour avec succès.";
                    } else {
                        // Création
                        $stmt = db()->prepare("
                            INSERT INTO fideles 
                            (nom, prenom, sexe, telephone, email, quartier, groupe, statut, date_naissance, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$nom, $prenom, $sexe, $telephone ?: null, $email ?: null, $quartier ?: null, $groupe, $statut, $dateNaissance]);
                        $fideleId = (int)db()->lastInsertId();
                        log_admin_action(current_user()['id'], 'Ajout fidèle', ['id' => $fideleId, 'nom' => "$nom $prenom"]);
                        $msg = "Nouveau fidèle enregistré avec succès.";
                    }

                    // Synchronisation des départements
                    db()->prepare("DELETE FROM fidele_departements WHERE fidele_id = ?")->execute([$fideleId]);
                    if (!empty($deptIds)) {
                        $stmtDept = db()->prepare("INSERT INTO fidele_departements (fidele_id, departement_id, date_adhesion) VALUES (?, ?, CURDATE())");
                        foreach ($deptIds as $deptId) {
                            $stmtDept->execute([$fideleId, $deptId]);
                        }
                    }

                    db()->commit();
                    set_flash('success', $msg);
                    header('Location: ' . ADMIN_URL . '/fideles.php');
                    exit;
                } catch (Exception $e) {
                    db()->rollBack();
                    error_log('Erreur sauvegarde fidele: ' . $e->getMessage());
                    $errors[] = "Une erreur est survenue lors de l'enregistrement.";
                }
            }
        }
    }
}

// Données pour le formulaire d'édition
$fideleData = null;
$fideleDepts = [];
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM fideles WHERE id = ?");
    $stmt->execute([$editId]);
    $fideleData = $stmt->fetch();
    if (!$fideleData) {
        set_flash('danger', "Fidèle introuvable.");
        header('Location: ' . ADMIN_URL . '/fideles.php');
        exit;
    }
    // Départements du fidèle
    $stmtD = db()->prepare("SELECT departement_id FROM fidele_departements WHERE fidele_id = ?");
    $stmtD->execute([$editId]);
    $fideleDepts = $stmtD->fetchAll(PDO::FETCH_COLUMN);
}

// Liste des fidèles avec filtres
$search = trim($_GET['q'] ?? '');
$filtreGroupe = $_GET['groupe'] ?? '';
$filtreStatut = $_GET['statut'] ?? '';

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(f.nom LIKE ? OR f.prenom LIKE ? OR f.telephone LIKE ? OR f.quartier LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($filtreGroupe)) {
    $where[] = "f.groupe = ?";
    $params[] = $filtreGroupe;
}
if (!empty($filtreStatut)) {
    $where[] = "f.statut = ?";
    $params[] = $filtreStatut;
}

$whereSql = implode(' AND ', $where);

$stmtFideles = db()->prepare("
    SELECT f.*, 
           GROUP_CONCAT(d.nom SEPARATOR ', ') as departements_noms
    FROM fideles f
    LEFT JOIN fidele_departements fd ON f.id = fd.fidele_id
    LEFT JOIN departements d ON fd.departement_id = d.id
    WHERE $whereSql
    GROUP BY f.id
    ORDER BY f.nom ASC, f.prenom ASC
");
$stmtFideles->execute($params);
$fidelesList = $stmtFideles->fetchAll();

// Liste des départements pour sélection
$allDepartements = db()->query("SELECT id, nom FROM departements WHERE actif = 1 ORDER BY nom ASC")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Formulaire d'Ajout / Édition -->
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.5rem; margin: 0;">
                <?= $action === 'edit' ? '✏️ Modifier la Fiche du Fidèle' : '➕ Nouveau Fidèle' ?>
            </h2>
            <a href="<?= ADMIN_URL ?>/fideles.php" class="btn btn-outline btn-sm">&larr; Retour à la liste</a>
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

        <div class="card" style="padding: 2rem;">
            <form method="POST" action="<?= ADMIN_URL ?>/fideles.php">
                <?= csrf_field() ?>
                <input type="hidden" name="post_action" value="save">
                <input type="hidden" name="id" value="<?= $fideleData['id'] ?? 0 ?>">

                <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom de Famille *</label>
                        <input type="text" id="nom" name="nom" class="form-control" required 
                               value="<?= e($fideleData['nom'] ?? ($_POST['nom'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="prenom" class="form-label">Prénom(s)</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?= e($fideleData['prenom'] ?? ($_POST['prenom'] ?? '')) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-3" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label for="sexe" class="form-label">Genre</label>
                        <select id="sexe" name="sexe" class="form-control">
                            <option value="M" <?= ($fideleData['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Homme (M)</option>
                            <option value="F" <?= ($fideleData['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Femme (F)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_naissance" class="form-label">Date de Naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control" 
                               value="<?= e($fideleData['date_naissance'] ?? ($_POST['date_naissance'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="groupe" class="form-label">Groupe d'Appartenance</label>
                        <select id="groupe" name="groupe" class="form-control">
                            <?php 
                                $groupes = ['jeunes' => 'Jeunes', 'hommes' => 'Hommes', 'femmes' => 'Femmes', 'enfants' => 'Enfants', 'chorale' => 'Chorale', 'autre' => 'Autre'];
                                foreach ($groupes as $gKey => $gLabel):
                            ?>
                                <option value="<?= $gKey ?>" <?= ($fideleData['groupe'] ?? '') === $gKey ? 'selected' : '' ?>><?= $gLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label for="telephone" class="form-label">Numéro de Téléphone (WhatsApp)</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="+33 6 12 34 56 78" 
                               value="<?= e($fideleData['telephone'] ?? ($_POST['telephone'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="fidele@email.fr" 
                               value="<?= e($fideleData['email'] ?? ($_POST['email'] ?? '')) ?>">
                    </div>
                </div>

                <div class="grid grid-cols-2" style="margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label for="quartier" class="form-label">Quartier / Ville de Résidence</label>
                        <input type="text" id="quartier" name="quartier" class="form-control" placeholder="Ex : Belleville, Montreuil..." 
                               value="<?= e($fideleData['quartier'] ?? ($_POST['quartier'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="statut" class="form-label">Statut Membre</label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="actif" <?= ($fideleData['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Membre Actif</option>
                            <option value="visiteur" <?= ($fideleData['statut'] ?? '') === 'visiteur' ? 'selected' : '' ?>>Nouveau Visiteur</option>
                            <option value="inactif" <?= ($fideleData['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif / Transféré</option>
                        </select>
                    </div>
                </div>

                <!-- Rattachement aux Départements -->
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label">Départements / Ministères d'engagement</label>
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php foreach ($allDepartements as $d): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                                <input type="checkbox" name="departements[]" value="<?= $d['id'] ?>" 
                                       <?= in_array($d['id'], $fideleDepts, true) ? 'checked' : '' ?> 
                                       style="accent-color: var(--gold-primary); width: 16px; height: 16px;">
                                <?= e($d['nom']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        💾 Enregistrer la fiche
                    </button>
                    <a href="<?= ADMIN_URL ?>/fideles.php" class="btn btn-outline btn-lg">Annuler</a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- Liste des Fidèles -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Répertoire des Fidèles</h2>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
                <?= count($fidelesList) ?> fidèle(s) répertorié(s)
            </p>
        </div>
        <a href="<?= ADMIN_URL ?>/fideles.php?action=create" class="btn btn-primary">
            ➕ Nouveau Fidèle
        </a>
    </div>

    <!-- Filtres & Recherche -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem 1.5rem;">
        <form method="GET" action="<?= ADMIN_URL ?>/fideles.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, prénom, téléphone, quartier..." class="form-control">
            </div>

            <div style="min-width: 150px;">
                <select name="groupe" class="form-control">
                    <option value="">Tous les groupes</option>
                    <option value="jeunes" <?= $filtreGroupe === 'jeunes' ? 'selected' : '' ?>>Jeunes</option>
                    <option value="hommes" <?= $filtreGroupe === 'hommes' ? 'selected' : '' ?>>Hommes</option>
                    <option value="femmes" <?= $filtreGroupe === 'femmes' ? 'selected' : '' ?>>Femmes</option>
                    <option value="enfants" <?= $filtreGroupe === 'enfants' ? 'selected' : '' ?>>Enfants</option>
                    <option value="chorale" <?= $filtreGroupe === 'chorale' ? 'selected' : '' ?>>Chorale</option>
                </select>
            </div>

            <div style="min-width: 140px;">
                <select name="statut" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?= $filtreStatut === 'actif' ? 'selected' : '' ?>>Actifs</option>
                    <option value="visiteur" <?= $filtreStatut === 'visiteur' ? 'selected' : '' ?>>Visiteurs</option>
                    <option value="inactif" <?= $filtreStatut === 'inactif' ? 'selected' : '' ?>>Inactifs</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <?php if ($search !== '' || !empty($filtreGroupe) || !empty($filtreStatut)): ?>
                <a href="<?= ADMIN_URL ?>/fideles.php" class="btn btn-outline btn-sm">✕ Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tableau des données -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom & Prénom</th>
                        <th>Genre</th>
                        <th>Téléphone</th>
                        <th>Groupe</th>
                        <th>Ministère(s)</th>
                        <th>Quartier</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fidelesList)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                Aucun fidèle ne correspond à vos filtres.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fidelesList as $f): ?>
                            <tr>
                                <td>
                                    <strong><?= e($f['nom']) ?></strong> <?= e($f['prenom'] ?? '') ?>
                                    <?php if (!empty($f['email'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($f['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: <?= ($f['sexe'] ?? 'M') === 'F' ? 'rgba(236, 72, 153, 0.15)' : 'rgba(59, 130, 246, 0.15)' ?>; color: <?= ($f['sexe'] ?? 'M') === 'F' ? '#F472B6' : '#60A5FA' ?>;">
                                        <?= $f['sexe'] ?? 'M' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($f['telephone'])): ?>
                                        <a href="tel:<?= e($f['telephone']) ?>" style="color: var(--gold-primary);">
                                            <?= e($f['telephone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-gold">
                                        <?= ucfirst(e($f['groupe'])) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                    <?= e($f['departements_noms'] ?: '-') ?>
                                </td>
                                <td><?= e($f['quartier'] ?: '-') ?></td>
                                <td>
                                    <span class="badge <?= $f['statut'] === 'actif' ? 'badge-success' : ($f['statut'] === 'visiteur' ? 'badge-warning' : 'badge-outline') ?>">
                                        <?= ucfirst(e($f['statut'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="<?= ADMIN_URL ?>/fideles.php?action=edit&id=<?= $f['id'] ?>" class="btn btn-outline btn-sm" title="Modifier">
                                            ✏️
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/fideles.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce fidèle ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="fidele_id" value="<?= $f['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);" title="Supprimer">
                                                🗑️
                                            </button>
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
<?php endif; ?>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
