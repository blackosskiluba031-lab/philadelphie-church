<?php
// templates/header.php
// En-tête HTML5 universel pour l'espace public — Design System Church X

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

$church = get_church_info();
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . e($church['nom'] ?? 'Église App') : e($church['nom'] ?? 'Église App');
$currentTheme = $_COOKIE['app_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $pageTitle ?></title>

    <!-- Meta SEO & PWA -->
    <meta name="description" content="<?= e(truncate_text($church['slogan'] ?? 'Bienvenue sur notre application paroissiale.', 160)) ?>">
    <meta name="theme-color" content="#0E0E0E">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">

    <!-- CSS : Thème de base + Design System Church X -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/theme.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/church-x.css?v=<?= APP_VERSION ?>">

    <!-- Script de prévention du clignotement de thème -->
    <script>
        (function() {
            var mode = localStorage.getItem('eglise_app_theme_mode') || 'dark';
            if (mode === 'auto') {
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
            } else {
                document.documentElement.setAttribute('data-theme', mode);
            }
        })();
    </script>
</head>
<body>
<?php
$numPaiementTop = function_exists('get_param') ? get_param('paiement_support_app', '0990945558') : '0990945558';
$numContactTop  = function_exists('get_param') ? get_param('contact_support_app', '0901247908') : '0901247908';
?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="site-main" style="padding-top: var(--navbar-height);">

    <!-- ═══════════════════════════════════════════════════════
         BANNIÈRE PWA EN HAUT (MISE EN ÉVIDENCE INSTALLATION)
         ═══════════════════════════════════════════════════════ -->
    <div id="pwa-top-banner" class="top-pwa-install-banner">
        <div class="container top-pwa-content" style="max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 1.8rem; background: rgba(217,119,6,0.18); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--accent-amber); flex-shrink: 0;">
                    📲
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 0.95rem; color: #FFF;">
                        Installer l'Application Philadelphie Church
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.3;">
                        Accès direct sur votre téléphone, cultes, versets et alertes même sans connexion.
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                <button type="button" id="pwa-top-install-btn" class="btn-church-white" style="padding: 8px 18px; font-size: 0.85rem; font-weight: 700; cursor: pointer;">
                    📲 Installer l'application
                </button>
                <button type="button" id="pwa-top-dismiss-btn" title="Masquer" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; padding: 4px 8px; line-height: 1;">
                    &times;
                </button>
            </div>
        </div>
        <!-- Instructions spécifiques iPhone Safari -->
        <div id="pwa-ios-instructions" style="display: none; width: 100%; max-width: 1280px; margin: 8px auto 0; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.825rem; color: #FCD34D; text-align: center;">
            ℹ️ Sur iPhone / iPad : appuyez sur le bouton Partager <span style="font-size: 1.15rem; font-weight: bold;">⎋</span> en bas de Safari, puis sélectionnez <strong>« Sur l'écran d'accueil » ➕</strong>.
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         BANDE D'AIDE FINANCIÈRE & MAINTENANCE (JUSTE APRÈS BARRE DES TÂCHES)
         ═══════════════════════════════════════════════════════ -->
    <div class="top-support-strip" id="top-support-strip">
        <div class="container" style="max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 280px;">
                <span style="font-size: 1.4rem; flex-shrink: 0;">🚀</span>
                <div style="font-size: 0.88rem; line-height: 1.4; color: #FFF;">
                    <strong style="color: var(--accent-amber);">Aide & Maintenance de l'App :</strong>
                    <span style="color: var(--text-muted);">Soutenez l'hébergement, le nom de domaine et l'équipe technique.</span>
                    <span class="support-strip-badges" style="display: inline-flex; gap: 6px; margin-left: 6px; vertical-align: middle;">
                        <span style="background: rgba(217,119,6,0.22); border: 1px solid var(--accent-amber); color: #FCD34D; font-size: 0.75rem; padding: 2px 7px; border-radius: 4px; font-weight: 700;">FC & USD</span>
                        <span style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #FFF; font-size: 0.75rem; padding: 2px 7px; border-radius: 4px; font-weight: 700;">Mobile Money : <?= e($numPaiementTop) ?></span>
                    </span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn-strip-copy" onclick="navigator.clipboard.writeText('<?= e($numPaiementTop) ?>'); alert('Numéro <?= e($numPaiementTop) ?> copié ! (M-Pesa / Airtel / Orange)');" title="Copier le numéro de transfert" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #FFF; padding: 6px 12px; font-size: 0.78rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    📋 Copier <?= e($numPaiementTop) ?>
                </button>
                <a href="https://wa.me/243901247908?text=<?= urlencode("Bonjour, je souhaite soutenir financièrement la maintenance de l'application Philadelphie Church.") ?>" target="_blank" class="btn-strip-wa" style="background: #25D366; color: #FFF; border: none; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                    <span>💬 WhatsApp (<?= e($numContactTop) ?>)</span>
                </a>
                <a href="<?= BASE_URL ?>/don.php#soutien-app" class="btn-strip-don" style="background: var(--accent-amber); color: #000; font-weight: 700; padding: 6px 14px; font-size: 0.78rem; border-radius: 4px; text-decoration: none;">
                    💛 Faire un Don
                </a>
            </div>
        </div>
    </div>
    <?php if (empty($isHeroPage)): ?>
    <div class="container" style="padding-top: 1.5rem;">
        <?php $flash = get_flash(); if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="alert-close" aria-label="Fermer">&times;</button>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
