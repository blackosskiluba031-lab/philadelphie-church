<?php
// admin/annonces.php
// Gestion des annonces paroissiales

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Annonces réservées au rôle media et supérieur (Équipe Média, Responsable, Développeur)
require_role([ROLE_MEDIA, ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Gestion des Annonces';
$pageTitle = 'Annonces';
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
            $annonceId = (int)($_POST['annonce_id'] ?? 0);
            if ($annonceId > 0) {
                db()->prepare("DELETE FROM annonces WHERE id = ?")->execute([$annonceId]);
                log_admin_action(current_user()['id'], 'Suppression annonce', ['id' => $annonceId]);
                set_flash('success', "L'annonce a été supprimée.");
            }
            header('Location: ' . ADMIN_URL . '/annonces.php');
            exit;
        }

        if ($postAction === 'save') {
            $id          = (int)($_POST['id'] ?? 0);
            $titre       = trim($_POST['titre'] ?? '');
            $contenu     = trim($_POST['contenu'] ?? '');
            $categorieId = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
            $epingle     = isset($_POST['epingle']) ? 1 : 0;
            $statut      = $_POST['statut'] ?? 'publie';
            $datePub     = !empty($_POST['date_publication']) ? $_POST['date_publication'] : date('Y-m-d H:i:s');
            $adminId     = current_user()['id'];

            if (empty($titre)) {
                $errors[] = "Le titre de l'annonce est obligatoire.";
            }
            if (empty($contenu)) {
                $errors[] = "Le contenu de l'annonce est obligatoire.";
            }

            if (empty($errors)) {
                try {
                    if ($id > 0) {
                        $stmt = db()->prepare("
                            UPDATE annonces 
                            SET titre = ?, contenu = ?, categorie_id = ?, epingle = ?, statut = ?, date_publication = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$titre, $contenu, $categorieId, $epingle, $statut, $datePub, $id]);
                        log_admin_action($adminId, 'Modification annonce', ['titre' => $titre]);
                        set_flash('success', "Annonce mise à jour avec succès.");
                    } else {
                        $stmt = db()->prepare("
                            INSERT INTO annonces (titre, contenu, categorie_id, epingle, statut, date_publication, admin_id, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$titre, $contenu, $categorieId, $epingle, $statut, $datePub, $adminId]);
                        log_admin_action($adminId, 'Publication annonce', ['titre' => $titre]);
                        set_flash('success', "Nouvelle annonce publiée avec succès.");
                    }
                    header('Location: ' . ADMIN_URL . '/annonces.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Erreur sauvegarde annonce: ' . $e->getMessage());
                    $errors[] = "Une erreur est survenue lors de l'enregistrement.";
                }
            }
        }
    }
}

// Données d'édition
$annonceData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM annonces WHERE id = ?");
    $stmt->execute([$editId]);
    $annonceData = $stmt->fetch();
}

// Catégories
$categories = db()->query("SELECT id, nom FROM categories WHERE type = 'annonce' OR type = 'notification' ORDER BY nom ASC")->fetchAll();

// Liste des annonces
$annoncesList = db()->query("
    SELECT a.*, c.nom as categorie_nom, c.couleur as categorie_couleur, adm.nom as auteur_nom 
    FROM annonces a 
    LEFT JOIN categories c ON a.categorie_id = c.id 
    LEFT JOIN admins adm ON a.admin_id = adm.id 
    ORDER BY a.epingle DESC, a.date_publication DESC
")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Annonces & Nouvelles</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            <?= count($annoncesList) ?> annonce(s) répertoriée(s)
        </p>
    </div>
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
        <a href="<?= ADMIN_URL ?>/annonces.php?action=create" class="btn btn-primary">
            ➕ Rédiger une Annonce
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
    <!-- Formulaire -->
    <div class="card" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem;">
            <?= $action === 'edit' ? '✏️ Modifier l\'Annonce' : '➕ Rédiger une Annonce' ?>
        </h3>

        <form method="POST" action="<?= ADMIN_URL ?>/annonces.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= $annonceData['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="titre" class="form-label">Titre de l'annonce *</label>
                <input type="text" id="titre" name="titre" class="form-control" required 
                       placeholder="Ex : Réunion de prière extraordinaire..." 
                       value="<?= e($annonceData['titre'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="categorie_id" class="form-label">Catégorie</label>
                    <select id="categorie_id" name="categorie_id" class="form-control">
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($annonceData['categorie_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statut" class="form-label">Statut de Publication</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="publie" <?= ($annonceData['statut'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié immédiatement</option>
                        <option value="brouillon" <?= ($annonceData['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon (Non visible)</option>
                        <option value="archive" <?= ($annonceData['statut'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="contenu" class="form-label">Texte du Communiqué *</label>
                <textarea id="contenu" name="contenu" class="form-control" rows="6" required 
                          placeholder="Rédigez ici le détail de l'annonce paroissiale..."><?= e($annonceData['contenu'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 2rem;">
                <input type="checkbox" id="epingle" name="epingle" value="1" 
                       <?= (!empty($annonceData['epingle'])) ? 'checked' : '' ?> 
                       style="width: 18px; height: 18px; accent-color: var(--gold-primary);">
                <label for="epingle" style="cursor: pointer; font-size: 0.95rem;">
                    📌 <strong>Épingler cette annonce</strong> en haut de page sur le site public
                </label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer l'annonce</button>
                <a href="<?= ADMIN_URL ?>/annonces.php" class="btn btn-outline btn-lg">Annuler</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Table des annonces -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Date de publication</th>
                        <th>Vues</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($annoncesList)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                Aucune annonce enregistrée.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($annoncesList as $a): ?>
                            <tr>
                                <td>
                                    <strong><?= e($a['titre']) ?></strong>
                                    <?php if ($a['epingle']): ?>
                                        <span class="badge badge-gold" style="font-size: 0.65rem; margin-left: 6px;">📌 Épinglé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: <?= e($a['categorie_couleur'] ?? '#1E88E5') ?>22; color: <?= e($a['categorie_couleur'] ?? '#1E88E5') ?>;">
                                        <?= e($a['categorie_nom'] ?? 'Général') ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                    <?= format_date_fr($a['date_publication']) ?>
                                </td>
                                <td style="font-weight: 600; color: var(--gold-primary);">
                                    👁️ <?= (int)$a['vues_count'] ?>
                                </td>
                                <td>
                                    <span class="badge <?= $a['statut'] === 'publie' ? 'badge-success' : ($a['statut'] === 'brouillon' ? 'badge-warning' : 'badge-outline') ?>">
                                        <?= ucfirst(e($a['statut'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="<?= ADMIN_URL ?>/annonces.php?action=edit&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm" title="Modifier">
                                            ✏️
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/annonces.php" style="display: inline;" onsubmit="return confirm('Supprimer définitivement cette annonce ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="annonce_id" value="<?= $a['id'] ?>">
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
