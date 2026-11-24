<?php
// templates/admin_header.php
// En-tête de l'espace administration

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

// Protection globale
require_login();

$currentUser = current_user();
$church = get_church_info();
$currentTheme = $_COOKIE['app_theme'] ?? 'dark';
$pageTitle = isset($pageTitle) ? $pageTitle . ' | Administration' : 'Administration | ' . e($church['nom'] ?? 'Église App');
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <!-- CSS Palette Option A -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/theme.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css?v=<?= APP_VERSION ?>">

    <!-- Script de prévention de clignotement de thème -->
    <script>
        (function() {
            var theme = localStorage.getItem('eglise_app_theme') || '<?= $currentTheme ?>';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button type="button" class="btn btn-outline btn-sm mobile-nav-toggle" id="sidebar-toggle" style="display: inline-flex;">
                    ☰
                </button>
                <h1 style="font-size: 1.25rem; margin: 0; font-family: var(--font-heading);">
                    <?= e($adminHeaderTitle ?? 'Tableau de bord') ?>
                </h1>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <!-- Lien retour site public -->
                <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-outline btn-sm">
                    🌐 Voir le site public
                </a>

                <!-- Bascule de thème -->
                <button type="button" class="theme-toggle-btn" aria-label="Changer de thème">
                    <!-- Rempli par theme-switcher.js -->
                </button>
            </div>
        </header>

        <main class="admin-content">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <span><?= e($flash['message']) ?></span>
                    <button type="button" class="alert-close" aria-label="Fermer">&times;</button>
                </div>
            <?php endif; ?>
