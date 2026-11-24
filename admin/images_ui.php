<?php
// admin/images_ui.php
// Personnalisation des images d'arrière-plan de l'interface (Hero images)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_role([ROLE_MEDIA, ROLE_RESPONSABLE, ROLE_DEVELOPPEUR]);

$adminHeaderTitle = 'Images d’Arrière-plan du Site';
$pageTitle = 'Personnalisation des Images UI';
$errors = [];
$success = false;

$uploadDirUI = ROOT_PATH . 'uploads/ui/';
if (!is_dir($uploadDirUI)) {
    mkdir($uploadDirUI, 0775, true);
}

// Clés d'images supportées
$sections = [
    'hero_bg_accueil' => [
        'titre' => 'Page d’Accueil (Hero Principal)',
        'description' => 'Image de fond du grand écran d’accueil (ex : mains levées dans la louange, foule réunie en prière).',
        'default' => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80'
    ],
    'hero_bg_programme' => [
        'titre' => 'Page Programme & Cultes',
        'description' => 'Image d’en-tête de la page des événements et du calendrier hebdomadaire.',
        'default' => 'https://images.unsplash.com/photo-1510525009512-ad7fc13eefab?auto=format&fit=crop&w=1920&q=80'
    ],
    'hero_bg_videos' => [
        'titre' => 'Page Médias & Vidéos',
        'description' => 'Image d’en-tête pour les prédications et les retransmissions en direct.',
        'default' => 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80'
    ],
    'hero_bg_communaute' => [
        'titre' => 'Page Publications & Partages',
        'description' => 'Image d’en-tête pour les publications, témoignages et partages.',
        'default' => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1920&q=80'
    ],
    'hero_bg_pasteur' => [
        'titre' => 'Page Au Pasteur (Confidentiel)',
        'description' => 'Image sobre pour la section d’écoute et d’échange pastoral.',
        'default' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1920&q=80'
    ],
    'hero_bg_dons' => [
        'titre' => 'Page Dons & Soutien',
        'description' => 'Image d’arrière-plan pour la page de générosité, dîmes et offrandes.',
        'default' => 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80'
    ]
];

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou session expirée.";
    } else {
        $key = $_POST['section_key'] ?? '';
        if (isset($sections[$key])) {
            $urlCustom = trim($_POST['custom_url'] ?? '');

            // 1. Upload fichier
            if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image_file'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $maxSize = 10 * 1024 * 1024; // 10 Mo

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Erreur lors de l'envoi du fichier.";
                } elseif ($file['size'] > $maxSize) {
                    $errors[] = "L'image ne peut dépasser 10 Mo.";
                } elseif (!in_array($file['type'], $allowed, true)) {
                    $errors[] = "Format d'image non valide (JPG, PNG ou WebP).";
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = $key . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDirUI . $filename)) {
                        $newUrl = BASE_URL . '/uploads/ui/' . $filename;
                        set_param($key, $newUrl);
                        log_admin_action(current_user()['id'], "Changement image UI : $key");
                        set_flash('success', "L'image pour {$sections[$key]['titre']} a été mise à jour !");
                        header('Location: ' . ADMIN_URL . '/images_ui.php');
                        exit;
                    } else {
                        $errors[] = "Échec de l'enregistrement de l'image.";
                    }
                }
            } elseif (!empty($urlCustom)) {
                // 2. URL externe personnalisée
                set_param($key, $urlCustom);
                log_admin_action(current_user()['id'], "Changement URL image UI : $key");
                set_flash('success', "L'image a été mise à jour !");
                header('Location: ' . ADMIN_URL . '/images_ui.php');
                exit;
            } elseif (isset($_POST['reset_default'])) {
                // 3. Réinitialiser
                set_param($key, $sections[$key]['default']);
                set_flash('info', "Image réinitialisée avec succès.");
                header('Location: ' . ADMIN_URL . '/images_ui.php');
                exit;
            }
        }
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.6rem; margin-bottom: 4px;">🖼️ Images d’Arrière-plan de l’Interface</h2>
    <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
        Personnalisez les photos en arrière-plan des différentes pages publiques (mains levées, chorale, temple).
    </p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem;">
    <?php foreach ($sections as $k => $sec): ?>
        <?php $currentImg = get_param($k, $sec['default']); ?>
        <div class="card"
            style="padding: 1.75rem; border-top: 3px solid var(--gold-primary); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem; color: var(--text-primary);"><?= e($sec['titre']) ?>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1rem; min-height: 40px;">
                    <?= e($sec['description']) ?>
                </p>

                <!-- Prévisualisation miniature -->
                <div
                    style="position: relative; padding-bottom: 45%; height: 0; overflow: hidden; border-radius: var(--radius-sm); margin-bottom: 1.25rem; background: #000; border: 1px solid var(--border-color);">
                    <img src="<?= e($currentImg) ?>" alt="<?= e($sec['titre']) ?>"
                        style="position: absolute; top:0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>

            <!-- Formulaire de modification -->
            <form method="POST" action="<?= ADMIN_URL ?>/images_ui.php" enctype="multipart/form-data"
                style="border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="section_key" value="<?= e($k) ?>">

                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <label class="form-label" style="font-size: 0.825rem;">Uploader une photo depuis
                        l'ordinateur/téléphone</label>
                    <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp"
                        style="font-size: 0.85rem; padding: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="font-size: 0.825rem;">Ou coller une URL d'image web</label>
                    <input type="url" name="custom_url" class="form-control" placeholder="https://..." value=""
                        style="font-size: 0.85rem; padding: 6px 10px;">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">
                        💾 Enregistrer l'image
                    </button>
                    <button type="submit" name="reset_default" value="1" class="btn btn-outline btn-sm"
                        title="Remettre la photo d'origine"
                        onclick="return confirm('Réinitialiser cette photo par défaut ?');">
                        ↺ Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>