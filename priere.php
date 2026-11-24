<?php
// priere.php
// Dépôt d'une demande de prière et d'intercession spirituelle — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Demande de Prière & Intercession';
$isHeroPage = true;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website_hp'])) {
        exit;
    }

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expirée ou requête invalide. Veuillez réessayer.";
    } else {
        $sujet     = trim($_POST['sujet'] ?? '');
        $message   = trim($_POST['message'] ?? '');
        $nom       = trim($_POST['nom_demandeur'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $anonyme   = isset($_POST['anonyme']) ? 1 : 0;

        if (mb_strlen($sujet) < 3) {
            $errors[] = "Veuillez préciser le sujet de votre prière (minimum 3 caractères).";
        }
        if (mb_strlen($message) < 10) {
            $errors[] = "Veuillez détailler votre sujet de prière (minimum 10 caractères).";
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse e-mail saisie n'est pas valide.";
        }

        if (empty($errors)) {
            try {
                if ($anonyme) {
                    $nom = 'Anonyme';
                    $telephone = null;
                    $email = null;
                }

                $stmt = db()->prepare("
                    INSERT INTO demandes_priere (nom_demandeur, telephone, email, sujet, message, anonyme, statut, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'en_attente', NOW())
                ");
                $stmt->execute([
                    $nom ?: 'Anonyme',
                    $telephone ?: null,
                    $email ?: null,
                    $sujet,
                    $message,
                    $anonyme
                ]);

                $success = true;
                set_flash('success', "Votre sujet de prière a été reçu avec recueillement. L'équipe pastorale prie pour vous.");
            } catch (Exception $e) {
                error_log('Erreur enregistrement prière: ' . $e->getMessage());
                $errors[] = "Une erreur est survenue lors de l'enregistrement de votre demande.";
            }
        }
    }
}

include __DIR__ . '/templates/header.php';

echo render_church_hero(
    '🙏 Intercession & Prière',
    'Déposer une Demande de Prière',
    '« Ne vous inquiétez de rien; mais en toute chose faites connaître vos besoins à Dieu par des prières et des supplications. » — Philippiens 4:6',
    'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Rédiger une prière', 'href' => '#formulaire-priere', 'class' => 'btn-church-white'],
        ['label' => 'Parler au Pasteur', 'href' => BASE_URL . '/pasteur.php', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 860px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">

    <?php if ($success): ?>
        <div class="glass-card" style="text-align: center; padding: 3.5rem 2rem; border-radius: var(--radius-xs); border-left: 4px solid #10B981; margin-bottom: 2.5rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🙏</div>
            <h2 style="color: #10B981; font-size: 1.8rem; margin-bottom: 0.5rem;">Votre prière est portée avec foi</h2>
            <p style="color: var(--text-muted); max-width: 540px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                L'équipe pastorale et les sentinelles de prière intercèdent pour vous. Que la paix de Christ vous accompagne.
            </p>
            <a href="<?= BASE_URL ?>/" class="btn-church-white">Retour à l'accueil</a>
        </div>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom: 2rem;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div id="formulaire-priere" class="glass-card" style="padding: 3rem 2.5rem; border-radius: var(--radius-xs);">
            <span class="accent-dash"></span>
            <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Votre Requête de Prière</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
                Vous pouvez formuler votre prière de manière nominative ou totalement anonyme.
            </p>

            <form method="POST" action="<?= BASE_URL ?>/priere.php">
                <?= csrf_field() ?>
                <!-- Honeypot anti-robot -->
                <div style="display:none !important;" aria-hidden="true">
                    <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="sujet" class="form-label" style="color: var(--text-main);">Sujet de prière *</label>
                    <input type="text" id="sujet" name="sujet" class="form-control" placeholder="Ex : Guérison pour un proche, délivrance, paix dans le foyer..." required
                           style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="message" class="form-label" style="color: var(--text-main);">Détails de votre prière *</label>
                    <textarea id="message" name="message" class="form-control" rows="6" placeholder="Confiez librement votre intention de prière..." required
                              style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; background: rgba(0,0,0,0.25); padding: 1rem 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" id="anonyme" name="anonyme" value="1" style="width: 18px; height: 18px; accent-color: var(--accent-amber);" onchange="document.getElementById('coordonnees-bloc').style.display = this.checked ? 'none' : 'grid';">
                        <span>Garder cette demande anonyme</span>
                    </label>
                </div>

                <div id="coordonnees-bloc" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="nom_demandeur" class="form-label" style="color: var(--text-main);">Votre Prénom ou Nom</label>
                        <input type="text" id="nom_demandeur" name="nom_demandeur" class="form-control" placeholder="Frère Jean, Sœur Marie..."
                               style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="telephone" class="form-label" style="color: var(--text-main);">Téléphone (Optionnel)</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="+33 6 12 34 56 78"
                               style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                    </div>
                </div>

                <button type="submit" class="btn-church-white" style="width: 100%; padding: 14px; font-size: 1rem;">
                    🙏 Confier ma prière
                </button>
            </form>
        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
