<?php
// pasteur.php
// Échange direct et confidentiel avec le Pasteur — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Parler au Pasteur | Échange Confidentiel';
$isHeroPage = true;
$church = get_church_info();
$whatsappPasteur = !empty($church['whatsapp']) ? preg_replace('/[^0-9]/', '', $church['whatsapp']) : '33612345678';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou session expirée.";
    } else {
        $nom = trim($_POST['nom_expediteur'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $canal = $_POST['canal_prefere'] ?? 'whatsapp';

        if (empty($sujet)) {
            $errors[] = "Veuillez indiquer le motif ou sujet de votre message.";
        }
        if (empty($message)) {
            $errors[] = "Veuillez rédiger votre message à l'attention du Pasteur.";
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare("
                    INSERT INTO messages_pasteur 
                    (nom_expediteur, telephone, email, sujet, message, canal_prefere, statut, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'non_lu', NOW())
                ");
                $stmt->execute([
                    $nom ?: 'Fidèle Confidentiel',
                    $telephone ?: null,
                    $email ?: null,
                    $sujet,
                    $message,
                    $canal
                ]);

                if ($canal === 'whatsapp' && !empty($whatsappPasteur)) {
                    $waText = urlencode("Bonjour Pasteur,\n\nObjet : {$sujet}\n\n{$message}\n\n— " . ($nom ?: 'Un fidèle'));
                    header("Location: https://wa.me/{$whatsappPasteur}?text={$waText}");
                    exit;
                }

                $success = true;
                set_flash('success', "Votre message a été transmis directement au Pasteur dans sa boîte confidentielle.");
            } catch (Exception $e) {
                $errors[] = "Erreur lors de l'envoi : " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/templates/header.php';

echo render_church_hero(
    '💬 Accompagnement Pastoral',
    'Au Pasteur • Échange Confidentiel',
    'Besoin d’un conseil spirituel, d’une prière ou d’un temps d’écoute ? Écrivez au Pasteur en toute discrétion.',
    'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Écrire en ligne', 'href' => '#formulaire-pasteur', 'class' => 'btn-church-white'],
        ['label' => 'WhatsApp direct', 'href' => "https://wa.me/{$whatsappPasteur}", 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 900px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">

    <?php if ($success): ?>
        <div class="glass-card"
            style="padding: 2.5rem; border-radius: var(--radius-xs); border-left: 4px solid #10B981; margin-bottom: 2rem;">
            <h3 style="color: #10B981; margin-bottom: 0.5rem;">Message bien reçu</h3>
            <p style="color: var(--text-main);">
                Votre message a été transmis dans la boîte pastorale privée du Pasteur. Il vous répondra par le canal
                souhaité dans les plus brefs délais.
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 2rem;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div id="formulaire-pasteur" class="glass-card" style="padding: 3rem 2.5rem; border-radius: var(--radius-xs);">
        <span class="accent-dash"></span>
        <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Écrire un Message au Pasteur</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
            Seul le Pasteur a accès à cette boîte de réception. Vos coordonnées ne seront jamais partagées.
        </p>

        <form method="POST" action="<?= BASE_URL ?>/pasteur.php">
            <?= csrf_field() ?>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" style="color: var(--text-main);">Votre Nom / Prénom <small
                            style="color: var(--text-muted);">(Optionnel)</small></label>
                    <input type="text" name="nom_expediteur" class="form-control"
                        placeholder="Laissez vide pour l'anonymat"
                        style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                </div>

                <div class="form-group">
                    <label class="form-label" style="color: var(--text-main);">Téléphone / WhatsApp <small
                            style="color: var(--text-muted);">(Optionnel)</small></label>
                    <input type="tel" name="telephone" class="form-control" placeholder="Ex : 06 12 34 56 78"
                        style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="color: var(--text-main);">Adresse e-mail <small
                        style="color: var(--text-muted);">(Optionnel)</small></label>
                <input type="email" name="email" class="form-control"
                    placeholder="Pour recevoir une réponse par courriel"
                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="color: var(--text-main);">Objet / Motif de l'échange *</label>
                <input type="text" name="sujet" class="form-control" required
                    placeholder="Ex : Demande de conseil, entretien spirituel, situation familiale..."
                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main);">Votre Message au Pasteur *</label>
                <textarea name="message" class="form-control" rows="6" required
                    placeholder="Partagez librement ce que vous avez sur le cœur..."
                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label" style="color: var(--text-main);">Canal de communication souhaité</label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 6px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main);">
                        <input type="radio" name="canal_prefere" value="whatsapp" checked
                            style="accent-color: var(--accent-amber);">
                        <span>💬 Redirection directe sur WhatsApp</span>
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main);">
                        <input type="radio" name="canal_prefere" value="app" style="accent-color: var(--accent-amber);">
                        <span>📬 Boîte interne de l'application</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-church-white" style="width: 100%; padding: 14px; font-size: 1rem;">
                Envoyer le message confidentiel
            </button>
        </form>
    </div>

</div>

<?php include __DIR__ . '/templates/footer.php'; ?>