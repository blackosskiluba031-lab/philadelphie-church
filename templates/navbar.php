<?php
// templates/navbar.php
// Barre de navigation publique Church X (Blur 14px, logo, sélecteur 3 thèmes, menu plein écran mobile)

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isPage = function ($pattern) use ($currentUri) {
    return str_contains($currentUri, $pattern) ? 'active' : '';
};
?>
<header class="church-header blur-nav">
    <div class="container church-navbar" style="padding: 0 1.5rem; max-width: 1280px; margin: 0 auto;">
        <!-- Logo & Titre de l'Église -->
        <a href="<?= BASE_URL ?>/" class="church-brand">
            <div class="church-logo-icon"
                style="background:#0E0E0E; color:#FFFFFF; border:1px solid rgba(255,255,255,0.2);">
                ⛪
            </div>
            <div style="display: flex; flex-direction: column;">
                <span class="church-brand-name" style="line-height: 1.1;">Philadelphie Church</span>
                <span
                    style="font-size: 0.68rem; font-weight: 700; color: #EF4444; letter-spacing: 0.08em; text-transform: uppercase;">CFCG
                    GARENGANZE</span>
            </div>
        </a>

        <!-- Menu Desktop -->
        <nav>
            <ul class="nav-desktop-menu">
                <li><a href="<?= BASE_URL ?>/"
                        class="nav-item-link <?= ($currentUri === '/' || str_ends_with($currentUri, 'index.php')) ? 'active' : '' ?>">Accueil</a>
                </li>
                <li><a href="<?= BASE_URL ?>/communaute.php"
                        class="nav-item-link <?= $isPage('communaute') ?>">Publications</a></li>
                <li><a href="<?= BASE_URL ?>/evenements.php"
                        class="nav-item-link <?= $isPage('evenements') ?>">Programme</a></li>
                <li><a href="<?= BASE_URL ?>/annonces.php" class="nav-item-link <?= $isPage('annonces') ?>">Annonces</a>
                </li>
                <li><a href="<?= BASE_URL ?>/videos.php" class="nav-item-link <?= $isPage('videos') ?>">Médias &
                        Direct</a></li>
                <li><a href="<?= BASE_URL ?>/entraide.php" class="nav-item-link <?= $isPage('entraide') ?>">Entraide</a>
                </li>
                <li><a href="<?= BASE_URL ?>/pasteur.php" class="nav-item-link <?= $isPage('pasteur') ?>">Au Pasteur</a>
                </li>
                <li><a href="<?= BASE_URL ?>/don.php" class="nav-item-link <?= $isPage('don') ?>">Dons</a></li>
            </ul>
        </nav>

        <!-- Actions droite : Sélecteur thème 3 modes + Bouton blanc + Hamburger -->
        <!-- Actions droite : Sélecteur thème 3 modes + Espace Staff + Bouton blanc + Hamburger -->
        <div class="nav-actions-group">
            <!-- Bascule 3 modes (Clair / Sombre / Auto) -->
            <div class="theme-mode-switch" title="Choisir le thème (Clair / Sombre / Auto)">
                <button type="button" class="theme-mode-btn" data-mode="light" title="Mode Clair">☀️</button>
                <button type="button" class="theme-mode-btn" data-mode="dark" title="Mode Sombre">🌙</button>
                <button type="button" class="theme-mode-btn" data-mode="auto" title="Automatique (Système)">⚙️</button>
            </div>

            <!-- Bouton Espace Staff / Pasteur / Déconnexion -->
            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <a href="<?= ADMIN_URL ?>/index.php" class="btn-staff-pill" title="Tableau de bord Staff connecté">
                        👑 <?= e(current_user()['username'] ?? 'Staff') ?>
                    </a>
                    <a href="<?= ADMIN_URL ?>/logout.php" class="btn-logout-pill" title="Se déconnecter de la session">
                        🚪 Déconnexion
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= ADMIN_URL ?>/login.php" class="btn-staff-pill" title="Connexion Pasteur & Équipe Média">
                    🔐 Staff
                </a>
            <?php endif; ?>

            <!-- Bouton Blanc Nous Rejoindre / Direct -->
            <a href="<?= BASE_URL ?>/evenements.php" class="btn-church-white"
                style="display: none; @media(min-width:768px){display:inline-flex;}">
                Nous rejoindre
            </a>

            <!-- Hamburger mobile -->
            <button type="button" class="hamburger-btn" aria-label="Menu de navigation" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
</header>

<!-- Menu Plein Écran Mobile -->
<div class="mobile-fullscreen-menu">
    <button type="button" class="mobile-menu-close"
        style="position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; color: var(--text-main); font-size: 2.2rem; cursor: pointer;">&times;</button>
    <ul class="mobile-nav-list">
        <li><a href="<?= BASE_URL ?>/" class="mobile-nav-link">Accueil</a></li>
        <li><a href="<?= BASE_URL ?>/communaute.php" class="mobile-nav-link">Publications</a></li>
        <li><a href="<?= BASE_URL ?>/evenements.php" class="mobile-nav-link">Programme des Cultes</a></li>
        <li><a href="<?= BASE_URL ?>/annonces.php" class="mobile-nav-link">Annonces</a></li>
        <li><a href="<?= BASE_URL ?>/videos.php" class="mobile-nav-link">Médias & Direct</a></li>
        <li><a href="<?= BASE_URL ?>/entraide.php" class="mobile-nav-link">Entraide</a></li>
        <li><a href="<?= BASE_URL ?>/pasteur.php" class="mobile-nav-link">Parler au Pasteur</a></li>
        <li><a href="<?= BASE_URL ?>/don.php" class="mobile-nav-link">Faire un Don</a></li>
        <li><a href="<?= BASE_URL ?>/admin/index.php" class="mobile-nav-link"
                style="color: var(--accent-amber);">Console Admin</a></li>
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
            <li
                style="border-top: 1px solid var(--glass-border); padding-top: 1rem; display: flex; flex-direction: column; gap: 8px;">
                <a href="<?= ADMIN_URL ?>/index.php" class="mobile-nav-link" style="color: var(--accent-amber);">
                    👑 Tableau de bord Staff (<?= e(current_user()['username'] ?? 'Connecté') ?>)
                </a>
                <a href="<?= ADMIN_URL ?>/logout.php" class="mobile-nav-link" style="color: #EF4444; font-size: 1.25rem;">
                    🚪 Se déconnecter
                </a>
            </li>
        <?php else: ?>
            <li style="border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                <a href="<?= ADMIN_URL ?>/login.php" class="mobile-nav-link"
                    style="color: var(--accent-amber); display: flex; align-items: center; gap: 8px;">
                    🔐 Espace Staff & Pasteur (Connexion)
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div style="margin-top: 2rem;">
        <a href="<?= BASE_URL ?>/evenements.php" class="btn-church-white" style="width: 100%; text-align: center;">Nous
            rejoindre au culte</a>
    </div>
</div>