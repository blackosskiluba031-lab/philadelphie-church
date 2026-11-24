<?php
// admin/parametres.php
// Configuration générale de l'église et de l'application

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_role(ROLE_DEVELOPPEUR);

$adminHeaderTitle = 'Paramètres Généraux';
$pageTitle = 'Paramètres de l\'Application';
$church = get_church_info();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton expiré.";
    } else {
        $nom        = trim($_POST['nom'] ?? '');
        $slogan     = trim($_POST['slogan'] ?? '');
        $adresse    = trim($_POST['adresse'] ?? '');
        $telephone  = trim($_POST['telephone'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $whatsapp   = trim($_POST['whatsapp'] ?? '');
        $aPropos    = trim($_POST['a_propos'] ?? '');
        $versetYear = trim($_POST['verset_annee'] ?? '');
        $devise     = trim($_POST['devise_defaut'] ?? 'EUR');

        if (empty($nom)) {
            $errors[] = "Le nom de l'église est obligatoire.";
        }

        if (empty($errors)) {
            try {
                // Mise à jour de la table eglise
                $stmtChurch = db()->prepare("
                    UPDATE eglise 
                    SET nom = ?, slogan = ?, adresse = ?, telephone = ?, email = ?, whatsapp = ?, a_propos = ? 
                    WHERE id = 1
                ");
                $stmtChurch->execute([$nom, $slogan, $adresse, $telephone, $email, $whatsapp, $aPropos]);

                // Mise à jour des paramètres système
                set_param('app_nom', $nom);
                set_param('verset_annee', $versetYear);
                set_param('devise_defaut', $devise);

                log_admin_action(current_user()['id'], 'Mise à jour des paramètres généraux');
                set_flash('success', "Les paramètres de l'église ont été enregistrés avec succès.");
                header('Location: ' . ADMIN_URL . '/parametres.php');
                exit;
            } catch (Exception $e) {
                error_log('Erreur update paramètres: ' . $e->getMessage());
                $errors[] = "Une erreur est survenue lors de l'enregistrement des paramètres.";
            }
        }
    }
}

$versetActuel = get_param('verset_annee', '');
$deviseActuelle = get_param('devise_defaut', 'EUR');

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Configuration & Identité Paroissiale</h2>
    <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
        Personnalisation du nom officiel, des coordonnées de contact et du verset biblique de l'année
    </p>
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

<div class="card" style="max-width: 800px; padding: 2rem;">
    <form method="POST" action="<?= ADMIN_URL ?>/parametres.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="nom" class="form-label">Nom officiel de l'Église *</label>
            <input type="text" id="nom" name="nom" class="form-control" required 
                   value="<?= e($church['nom'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="slogan" class="form-label">Slogan spirituel / Devise</label>
            <input type="text" id="slogan" name="slogan" class="form-control" 
                   value="<?= e($church['slogan'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="verset_annee" class="form-label">Verset Biblique de l'Année (affiché dans le pied de page et sur l'accueil)</label>
            <textarea id="verset_annee" name="verset_annee" class="form-control" rows="3"><?= e($versetActuel) ?></textarea>
        </div>

        <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
            <div class="form-group">
                <label for="telephone" class="form-label">Téléphone du Secrétariat</label>
                <input type="text" id="telephone" name="telephone" class="form-control" 
                       value="<?= e($church['telephone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="whatsapp" class="form-label">Numéro WhatsApp Officiel (pour échanges pastoraux)</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-control" 
                       placeholder="+33612345678" value="<?= e($church['whatsapp'] ?? '') ?>">
            </div>
        </div>

        <div class="grid grid-cols-2" style="margin-bottom: 1rem;">
            <div class="form-group">
                <label for="email" class="form-label">Adresse E-mail officielle</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= e($church['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="devise_defaut" class="form-label">Devise monétaire principale pour les dons</label>
                <select id="devise_defaut" name="devise_defaut" class="form-control">
                    <option value="EUR" <?= $deviseActuelle === 'EUR' ? 'selected' : '' ?>>Euro (€)</option>
                    <option value="USD" <?= $deviseActuelle === 'USD' ? 'selected' : '' ?>>Dollar US ($)</option>
                    <option value="XOF" <?= $deviseActuelle === 'XOF' ? 'selected' : '' ?>>Franc CFA Ouest (XOF)</option>
                    <option value="XAF" <?= $deviseActuelle === 'XAF' ? 'selected' : '' ?>>Franc CFA Centre (XAF)</option>
                    <option value="CDF" <?= $deviseActuelle === 'CDF' ? 'selected' : '' ?>>Franc Congolais (CDF)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="adresse" class="form-label">Adresse physique du Temple / Église</label>
            <input type="text" id="adresse" name="adresse" class="form-control" 
                   value="<?= e($church['adresse'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="a_propos" class="form-label">Présentation / À Propos de la communauté</label>
            <textarea id="a_propos" name="a_propos" class="form-control" rows="4"><?= e($church['a_propos'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
            💾 Sauvegarder les modifications
        </button>
    </form>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
