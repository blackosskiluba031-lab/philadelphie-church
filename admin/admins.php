<?php
// admin/admins.php
// Gestion des comptes administrateurs et des rôles

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_role(ROLE_DEVELOPPEUR);

$adminHeaderTitle = 'Comptes Administrateurs';
$pageTitle = 'Gestion des Administrateurs';
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton expiré.";
    } else {
        $postAction = $_POST['post_action'] ?? 'save';

        if ($postAction === 'delete') {
            $delId = (int)($_POST['admin_id'] ?? 0);
            if ($delId === current_user()['id']) {
                $errors[] = "Vous ne pouvez pas supprimer votre propre compte administrateur.";
            } else {
                db()->prepare("DELETE FROM admins WHERE id = ?")->execute([$delId]);
                log_admin_action(current_user()['id'], 'Suppression administrateur', ['id' => $delId]);
                set_flash('success', "Le compte administrateur a été supprimé.");
                header('Location: ' . ADMIN_URL . '/admins.php');
                exit;
            }
        }

        if ($postAction === 'save') {
            $id       = (int)($_POST['id'] ?? 0);
            $nom      = trim($_POST['nom'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = $_POST['role'] ?? ROLE_SECRETAIRE;
            $actif    = isset($_POST['actif']) ? 1 : 0;
            $password = $_POST['password'] ?? '';

            if (empty($nom) || empty($username)) {
                $errors[] = "Le nom et le nom d'utilisateur sont obligatoires.";
            }

            // Vérification unicité username
            $stmtCheck = db()->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetch()) {
                $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
            }

            if ($id === 0 && empty($password)) {
                $errors[] = "Un mot de passe est obligatoire pour la création d'un compte.";
            }

            if (!empty($password) && strlen($password) < 8) {
                $errors[] = "Le mot de passe doit comporter au moins 8 caractères.";
            }

            if (empty($errors)) {
                try {
                    if ($id > 0) {
                        if (!empty($password)) {
                            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                            $stmt = db()->prepare("UPDATE admins SET nom = ?, username = ?, email = ?, role = ?, actif = ?, mot_de_passe_hash = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$nom, $username, $email ?: null, $role, $actif, $hash, $id]);
                        } else {
                            $stmt = db()->prepare("UPDATE admins SET nom = ?, username = ?, email = ?, role = ?, actif = ?, updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$nom, $username, $email ?: null, $role, $actif, $id]);
                        }
                        log_admin_action(current_user()['id'], 'Modification administrateur', ['username' => $username]);
                        set_flash('success', "Compte administrateur mis à jour.");
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $stmt = db()->prepare("INSERT INTO admins (nom, username, email, role, actif, mot_de_passe_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$nom, $username, $email ?: null, $role, $actif, $hash]);
                        log_admin_action(current_user()['id'], 'Création administrateur', ['username' => $username, 'role' => $role]);
                        set_flash('success', "Nouveau compte administrateur créé avec succès.");
                    }
                    header('Location: ' . ADMIN_URL . '/admins.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Erreur admin save: ' . $e->getMessage());
                    $errors[] = "Une erreur est survenue lors de l'enregistrement.";
                }
            }
        }
    }
}

// Données d'édition
$adminData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$editId]);
    $adminData = $stmt->fetch();
}

$adminsList = db()->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Comptes & Accès Administrateurs</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Gestion des autorisations selon les rôles : Développeur, Responsable / Pasteur, Secrétaire
        </p>
    </div>
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
        <a href="<?= ADMIN_URL ?>/admins.php?action=create" class="btn btn-primary">
            ➕ Nouvel Administrateur
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
        <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem;">
            <?= $action === 'edit' ? '✏️ Modifier l\'Administrateur' : '➕ Créer un Compte Administrateur' ?>
        </h3>

        <form method="POST" action="<?= ADMIN_URL ?>/admins.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= $adminData['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="nom" class="form-label">Nom complet *</label>
                <input type="text" id="nom" name="nom" class="form-control" required 
                       value="<?= e($adminData['nom'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="username" class="form-label">Identifiant de connexion *</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?= e($adminData['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Adresse E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($adminData['email'] ?? '') ?>">
                </div>
            </div>

            <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="role" class="form-label">Rôle & Droits d'accès</label>
                    <select id="role" name="role" class="form-control">
                        <option value="secretaire" <?= ($adminData['role'] ?? '') === 'secretaire' ? 'selected' : '' ?>>📋 Secrétaire — Fidèles, Présences, Départements</option>
                        <option value="media" <?= ($adminData['role'] ?? '') === 'media' ? 'selected' : '' ?>>🎬 Équipe Média — Annonces, Vidéos, Chorale, Mur</option>
                        <option value="responsable" <?= ($adminData['role'] ?? '') === 'responsable' ? 'selected' : '' ?>>✝️ Responsable / Pasteur — Prières, Dons, Messages privés</option>
                        <option value="developpeur" <?= ($adminData['role'] ?? '') === 'developpeur' ? 'selected' : '' ?>>⚙️ Développeur — Accès total système</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: center; padding-top: 25px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="actif" value="1" <?= (!isset($adminData) || $adminData['actif']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--gold-primary);">
                        <strong>Compte actif</strong>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <?= $action === 'edit' ? 'Modifier le Mot de passe (Laisser vide pour ne pas changer)' : 'Mot de passe sécurisé *' ?>
                </label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" <?= $action === 'create' ? 'required' : '' ?>>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer le compte</button>
                <a href="<?= ADMIN_URL ?>/admins.php" class="btn btn-outline btn-lg">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Table des administrateurs -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom Complet</th>
                    <th>Identifiant</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Dernière Connexion</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adminsList as $adm): ?>
                    <tr>
                        <td><strong><?= e($adm['nom']) ?></strong></td>
                        <td><code>@<?= e($adm['username']) ?></code></td>
                        <td><?= e($adm['email'] ?: '-') ?></td>
                        <td>
                            <?php
                                $roleClass = match($adm['role']) {
                                    'developpeur' => 'badge-gold',
                                    'responsable' => 'badge-success',
                                    'media'       => 'badge-outline',
                                    default       => 'badge-outline'
                                };
                                $roleLabel = match($adm['role']) {
                                    'developpeur' => '⚙️ Développeur',
                                    'responsable' => '✝️ Responsable',
                                    'media'       => '🎬 Équipe Média',
                                    default       => '📋 Secrétaire'
                                };
                            ?>
                            <span class="badge <?= $roleClass ?>">
                                <?= $roleLabel ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $adm['actif'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $adm['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>
                        <td style="font-size: 0.8rem; color: var(--text-muted);">
                            <?= $adm['derniere_connexion'] ? format_date_fr($adm['derniere_connexion'], true) : 'Jamais connecté' ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="<?= ADMIN_URL ?>/admins.php?action=edit&id=<?= $adm['id'] ?>" class="btn btn-outline btn-sm" title="Modifier">
                                    ✏️
                                </a>
                                <?php if ($adm['id'] !== current_user()['id']): ?>
                                    <form method="POST" action="<?= ADMIN_URL ?>/admins.php" style="display: inline;" onsubmit="return confirm('Supprimer ce compte administrateur ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger);">
                                            🗑️
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
