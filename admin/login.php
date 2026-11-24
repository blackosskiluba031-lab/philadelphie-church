<?php
// admin/login.php
// Écran d'authentification des administrateurs avec protection anti brute-force

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Si déjà connecté, rediriger vers le tableau de bord
if (is_logged_in()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$church = get_church_info();
$ip = get_client_ip();
$errors = [];
$rateLimited = is_ip_rate_limited($ip, 5, 15);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($rateLimited) {
        $errors[] = "Trop de tentatives infructueuses depuis cette adresse IP. Par mesure de sécurité, l'accès est bloqué pendant 15 minutes.";
    } elseif (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expirée ou requête invalide. Veuillez recharger la page.";
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $errors[] = "Veuillez renseigner votre identifiant et votre mot de passe.";
        } else {
            // Recherche par username OU par email
            $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['mot_de_passe_hash'])) {
                if (!$admin['actif']) {
                    record_login_attempt($identifier, false);
                    $errors[] = "Ce compte administrateur a été désactivé. Veuillez contacter le développeur.";
                } else {
                    // Connexion réussie
                    record_login_attempt($identifier, true);
                    login_user($admin);
                    log_admin_action($admin['id'], 'Connexion réussie', ['username' => $admin['username']]);
                    set_flash('success', "Bienvenue, " . $admin['nom'] . " !");
                    header('Location: ' . ADMIN_URL . '/index.php');
                    exit;
                }
            } else {
                // Échec de connexion (identifiant ou mot de passe faux)
                record_login_attempt($identifier, false);
                $errors[] = "Identifiant ou mot de passe incorrect.";

                // Vérifie si l'IP devient bloquée dès maintenant
                if (is_ip_rate_limited($ip, 5, 15)) {
                    $errors[] = "Attention : Suite à 5 échecs consécutifs, votre adresse IP est désormais bloquée pour 15 minutes.";
                    $rateLimited = true;
                }
            }
        }
    }
}

$currentTheme = $_COOKIE['app_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration | <?= e($church['nom'] ?? 'Église App') ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/theme.css?v=<?= APP_VERSION ?>">
    <script>
        (function() {
            var theme = localStorage.getItem('eglise_app_theme') || '<?= $currentTheme ?>';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(30, 41, 59, 0.5) 0%, var(--bg-body) 100%);
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand-icon {
            width: 50px;
            height: 50px;
            background: var(--gold-gradient);
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 1.6rem;
            margin-bottom: 1rem;
            box-shadow: var(--gold-glow);
        }
    </style>
</head>
<body>

<div style="position: absolute; top: 20px; right: 20px;">
    <button type="button" class="theme-toggle-btn" aria-label="Changer de thème">
        <!-- Rempli par theme-switcher.js -->
    </button>
</div>

<div class="login-card">
    <div class="login-header">
        <div class="login-brand-icon">✞</div>
        <h1 style="font-size: 1.45rem; margin-bottom: 4px;"><?= e($church['nom'] ?? 'Église App') ?></h1>
        <p style="color: var(--text-secondary); font-size: 0.875rem;">Espace d'Administration Sécurisé</p>
    </div>

    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="alert-close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul style="margin: 0; padding-left: 20px; font-size: 0.875rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($rateLimited): ?>
        <div class="alert alert-warning" style="text-align: center;">
            <p style="margin: 0;">🔒 <strong>Accès temporairement suspendu</strong> pour votre sécurité en raison de trop nombreuses tentatives infructueuses.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= ADMIN_URL ?>/login.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="identifier" class="form-label">Identifiant ou Adresse E-mail</label>
                <input type="text" id="identifier" name="identifier" class="form-control" 
                       placeholder="admin_dev ou email@exemple.fr" required autofocus 
                       value="<?= e($_POST['identifier'] ?? '') ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Mot de passe</label>
                    <button type="button" id="toggle-pwd-btn" style="background: none; border: none; color: var(--gold-primary); font-size: 0.8rem; cursor: pointer;">
                        Afficher
                    </button>
                </div>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
                Se connecter à l'administration
            </button>
        </form>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 2rem; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
        <a href="<?= BASE_URL ?>/" style="color: var(--text-muted); font-size: 0.85rem;">
            &larr; Retourner sur le site public
        </a>
    </div>
</div>

<script src="<?= ASSETS_URL ?>/js/theme-switcher.js?v=<?= APP_VERSION ?>"></script>
<script src="<?= ASSETS_URL ?>/js/app.js?v=<?= APP_VERSION ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-pwd-btn');
    const pwdInput = document.getElementById('password');
    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', () => {
            const isText = pwdInput.type === 'text';
            pwdInput.type = isText ? 'password' : 'text';
            toggleBtn.textContent = isText ? 'Afficher' : 'Masquer';
        });
    }
});
</script>
</body>
</html>
