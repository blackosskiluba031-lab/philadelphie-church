<?php
// admin/evenements.php
// Gestion du calendrier des cultes et des événements

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Gestion des Événements & Cultes';
$pageTitle = 'Événements';
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
            $eventId = (int)($_POST['event_id'] ?? 0);
            if ($eventId > 0) {
                db()->prepare("DELETE FROM evenements WHERE id = ?")->execute([$eventId]);
                log_admin_action(current_user()['id'], 'Suppression événement', ['id' => $eventId]);
                set_flash('success', "L'événement a été supprimé.");
            }
            header('Location: ' . ADMIN_URL . '/evenements.php');
            exit;
        }

        if ($postAction === 'save') {
            $id          = (int)($_POST['id'] ?? 0);
            $titre       = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categorieId = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
            $lieu        = trim($_POST['lieu'] ?? 'Temple Principal');
            $dateDebut   = !empty($_POST['date_debut']) ? str_replace('T', ' ', $_POST['date_debut']) : '';
            $dateFin     = !empty($_POST['date_fin']) ? str_replace('T', ' ', $_POST['date_fin']) : null;
            $recurrent   = $_POST['recurrent'] ?? 'aucun';
            $statut      = $_POST['statut'] ?? 'publie';

            if (empty($titre)) {
                $errors[] = "Le titre de l'événement est obligatoire.";
            }
            if (empty($dateDebut)) {
                $errors[] = "La date et l'heure de début sont obligatoires.";
            }

            if (empty($errors)) {
                try {
                    if ($id > 0) {
                        $stmt = db()->prepare("
                            UPDATE evenements 
                            SET titre = ?, description = ?, categorie_id = ?, lieu = ?, 
                                date_debut = ?, date_fin = ?, recurrent = ?, statut = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$titre, $description ?: null, $categorieId, $lieu ?: null, $dateDebut, $dateFin, $recurrent, $statut, $id]);
                        log_admin_action(current_user()['id'], 'Modification événement', ['titre' => $titre]);
                        set_flash('success', "Événement mis à jour avec succès.");
                    } else {
                        $stmt = db()->prepare("
                            INSERT INTO evenements (titre, description, categorie_id, lieu, date_debut, date_fin, recurrent, statut, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$titre, $description ?: null, $categorieId, $lieu ?: null, $dateDebut, $dateFin, $recurrent, $statut]);
                        log_admin_action(current_user()['id'], 'Création événement', ['titre' => $titre]);
                        set_flash('success', "Nouvel événement programmé avec succès.");
                    }
                    header('Location: ' . ADMIN_URL . '/evenements.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Erreur événement: ' . $e->getMessage());
                    $errors[] = "Une erreur est survenue lors de l'enregistrement.";
                }
            }
        }
    }
}

// Données d'édition
$eventData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = db()->prepare("SELECT * FROM evenements WHERE id = ?");
    $stmt->execute([$editId]);
    $eventData = $stmt->fetch();
}

// Catégories
$categories = db()->query("SELECT id, nom FROM categories WHERE type = 'evenement' OR type = 'notification' ORDER BY nom ASC")->fetchAll();

// Liste des événements
$eventsList = db()->query("
    SELECT e.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
           COUNT(p.id) as total_pointages
    FROM evenements e 
    LEFT JOIN categories c ON e.categorie_id = c.id 
    LEFT JOIN presences p ON e.id = p.evenement_id
    GROUP BY e.id
    ORDER BY e.date_debut DESC
")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Calendrier des Cultes & Événements</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            <?= count($eventsList) ?> événement(s) au calendrier
        </p>
    </div>
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
        <a href="<?= ADMIN_URL ?>/evenements.php?action=create" class="btn btn-primary">
            ➕ Programmer un Événement
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
    <div class="card" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.5rem;">
            <?= $action === 'edit' ? '✏️ Modifier l\'Événement' : '➕ Programmer un Nouvel Événement' ?>
        </h3>

        <form method="POST" action="<?= ADMIN_URL ?>/evenements.php">
            <?= csrf_field() ?>
            <input type="hidden" name="post_action" value="save">
            <input type="hidden" name="id" value="<?= $eventData['id'] ?? 0 ?>">

            <div class="form-group">
                <label for="titre" class="form-label">Titre de l'événement / Nom du culte *</label>
                <input type="text" id="titre" name="titre" class="form-control" required 
                       placeholder="Ex : Culte d'Adoration & Action de Grâce..." 
                       value="<?= e($eventData['titre'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="date_debut" class="form-label">Date et Heure de Début *</label>
                    <input type="datetime-local" id="date_debut" name="date_debut" class="form-control" required 
                           value="<?= !empty($eventData['date_debut']) ? date('Y-m-d\TH:i', strtotime($eventData['date_debut'])) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="date_fin" class="form-label">Date et Heure de Fin</label>
                    <input type="datetime-local" id="date_fin" name="date_fin" class="form-control" 
                           value="<?= !empty($eventData['date_fin']) ? date('Y-m-d\TH:i', strtotime($eventData['date_fin'])) : '' ?>">
                </div>
            </div>

            <div class="grid grid-cols-3" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="categorie_id" class="form-label">Catégorie</label>
                    <select id="categorie_id" name="categorie_id" class="form-control">
                        <option value="">Sélectionner</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($eventData['categorie_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="recurrent" class="form-label">Récurrence</label>
                    <select id="recurrent" name="recurrent" class="form-control">
                        <option value="aucun" <?= ($eventData['recurrent'] ?? '') === 'aucun' ? 'selected' : '' ?>>Aucune (Événement ponctuel)</option>
                        <option value="hebdomadaire" <?= ($eventData['recurrent'] ?? '') === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire (Chaque semaine)</option>
                        <option value="mensuel" <?= ($eventData['recurrent'] ?? '') === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                        <option value="annuel" <?= ($eventData['recurrent'] ?? '') === 'annuel' ? 'selected' : '' ?>>Annuel</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statut" class="form-label">Statut</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="publie" <?= ($eventData['statut'] ?? '') === 'publie' ? 'selected' : '' ?>>Publié</option>
                        <option value="brouillon" <?= ($eventData['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                        <option value="annule" <?= ($eventData['statut'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="lieu" class="form-label">Lieu du rassemblement</label>
                <input type="text" id="lieu" name="lieu" class="form-control" 
                       placeholder="Temple Principal, 12 Bd de l'Alliance..." 
                       value="<?= e($eventData['lieu'] ?? 'Temple Principal') ?>">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description & Programme détaillé</label>
                <textarea id="description" name="description" class="form-control" rows="4" 
                          placeholder="Orateurs invités, programme, déroulement..."><?= e($eventData['description'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer l'événement</button>
                <a href="<?= ADMIN_URL ?>/evenements.php" class="btn btn-outline btn-lg">Annuler</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Table des événements -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre du Culte / Événement</th>
                        <th>Date & Heure</th>
                        <th>Lieu</th>
                        <th>Récurrence</th>
                        <th>Pointage</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventsList)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                Aucun événement programmé.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventsList as $evt): ?>
                            <tr>
                                <td>
                                    <strong><?= e($evt['titre']) ?></strong>
                                    <?php if (!empty($evt['categorie_nom'])): ?>
                                        <span class="badge" style="font-size: 0.65rem; margin-left: 6px; background: <?= e($evt['categorie_couleur'] ?? '#D97706') ?>22; color: <?= e($evt['categorie_couleur'] ?? '#D97706') ?>;">
                                            <?= e($evt['categorie_nom']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--gold-primary); font-weight: 600;">
                                    <?= format_date_fr($evt['date_debut'], true) ?>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-secondary);"><?= e($evt['lieu'] ?: 'Temple') ?></td>
                                <td>
                                    <?php if ($evt['recurrent'] !== 'aucun'): ?>
                                        <span class="badge badge-gold" style="font-size: 0.7rem;">
                                            🔄 <?= ucfirst(e($evt['recurrent'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">Ponctuel</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= ADMIN_URL ?>/presences.php?event_id=<?= $evt['id'] ?>" class="btn btn-outline-gold btn-sm" style="font-size: 0.75rem;">
                                        ✍️ <?= (int)$evt['total_pointages'] ?> pointé(s)
                                    </a>
                                </td>
                                <td>
                                    <span class="badge <?= $evt['statut'] === 'publie' ? 'badge-success' : ($evt['statut'] === 'brouillon' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= ucfirst(e($evt['statut'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="<?= ADMIN_URL ?>/evenements.php?action=edit&id=<?= $evt['id'] ?>" class="btn btn-outline btn-sm" title="Modifier">
                                            ✏️
                                        </a>
                                        <form method="POST" action="<?= ADMIN_URL ?>/evenements.php" style="display: inline;" onsubmit="return confirm('Supprimer cet événement ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
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
