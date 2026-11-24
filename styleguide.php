<?php
// styleguide.php
// Page de démonstration du Design System Church X pour validation client

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$church = get_church_info();
$pageTitle = 'Styleguide Design System Church X';
$currentTheme = $_COOKIE['app_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Styleguide Church X • Église App</title>
    
    <!-- Design System Church X -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/church-x.css?v=<?= time() ?>">
    <!-- Chart.js pour la démo admin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     1. NAVBAR CHURCH X (BLUR 14px + LOGO + BOUTON NOUS REJOINDRE)
     ═══════════════════════════════════════════════════════ -->
<header class="church-header blur-nav">
    <div class="container church-navbar" style="padding: 0 1.5rem; max-width: 1280px; margin: 0 auto;">
        <!-- Logo : Croix/Livre + Nom de l'église -->
        <a href="<?= BASE_URL ?>/" class="church-brand">
            <div class="church-logo-icon">📖</div>
            <div class="church-brand-name">
                <?= e($church['nom'] ?? 'Church X • Église de la Grâce') ?>
            </div>
        </a>

        <!-- Menu Desktop -->
        <nav>
            <ul class="nav-desktop-menu">
                <li><a href="#hero" class="nav-item-link active">Accueil</a></li>
                <li><a href="#about" class="nav-item-link">À propos</a></li>
                <li><a href="#schedule" class="nav-item-link">Programme</a></li>
                <li><a href="#events" class="nav-item-link">Événements</a></li>
                <li><a href="#charts" class="nav-item-link">Graphiques Admin</a></li>
            </ul>
        </nav>

        <!-- Actions droite : Bascule Clair/Sombre/Auto + Bouton Blanc -->
        <div class="nav-actions-group">
            <!-- Sélecteur 3 modes : Clair / Sombre / Auto -->
            <div class="theme-mode-switch" title="Choisir le thème d'affichage">
                <button type="button" class="theme-mode-btn" data-mode="light" title="Mode Clair">☀️</button>
                <button type="button" class="theme-mode-btn active" data-mode="dark" title="Mode Sombre">🌙</button>
                <button type="button" class="theme-mode-btn" data-mode="auto" title="Automatique (Système)">⚙️</button>
            </div>

            <!-- Bouton principal blanc Church X -->
            <a href="#join" class="btn-church-white" style="display: none; @media(min-width:640px){display:inline-flex;}">
                Nous rejoindre
            </a>

            <!-- Hamburger mobile -->
            <button type="button" class="hamburger-btn" aria-label="Menu" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
</header>

<!-- Menu Plein Écran Mobile (Church X) -->
<div class="mobile-fullscreen-menu">
    <button type="button" class="mobile-menu-close" style="position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; color: var(--text-main); font-size: 2rem; cursor: pointer;">&times;</button>
    <ul class="mobile-nav-list">
        <li><a href="#hero" class="mobile-nav-link">Accueil</a></li>
        <li><a href="#about" class="mobile-nav-link">À propos</a></li>
        <li><a href="#schedule" class="mobile-nav-link">Programme & Culte</a></li>
        <li><a href="#events" class="mobile-nav-link">Événements & Annonces</a></li>
        <li><a href="<?= BASE_URL ?>/admin/index.php" class="mobile-nav-link">Console Admin</a></li>
    </ul>
    <div style="margin-top: 2rem;">
        <a href="#join" class="btn-church-white" style="width: 100%; text-align: center;">Nous rejoindre</a>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     2. HERO CINÉMATOGRAPHIQUE (GRANDE PHOTO + DÉGRADÉ SOMBRE + DEUX BOUTONS)
     ═══════════════════════════════════════════════════════ -->
<section id="hero" class="church-hero" style="background-image: url('https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80');">
    <div class="church-hero-overlay"></div>
    <div class="church-hero-radial"></div>

    <div class="container church-hero-content" style="max-width: 1280px; margin: 0 auto; width: 100%;">
        <span class="hero-tag">✞ Bienvenue dans notre communauté</span>
        <h1 class="hero-main-title">
            Vivez l'expérience d'une église vivante et unie
        </h1>
        <p class="hero-description">
            Un lieu d'amour, d'espérance et de partage spirituel où chacun trouve sa place pour louer et grandir dans la grâce de Dieu.
        </p>
        <div class="hero-buttons-row">
            <a href="#schedule" class="btn-church-white">
                Participer au culte
            </a>
            <a href="#about" class="btn-church-outline">
                Découvrir nos ministères
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     3. CARTE BLANCHE CHEVAUCHANTE (SIGNATURE DU MODÈLE CHURCH X)
     ═══════════════════════════════════════════════════════ -->
<section id="about" class="hero-overlap-section">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="overlap-white-card fade-in-on-scroll">
            <div>
                <span class="accent-dash"></span>
                <h2>Nous croyons en la force de l'amour de Dieu</h2>
            </div>
            <div>
                <p>
                    Dans notre communauté, nous unissons nos cœurs pour célébrer la vie, accompagner chaque famille dans les moments forts et soutenir ceux qui sont dans le besoin.
                </p>
                <div style="margin-top: 1.5rem;">
                    <a href="#events" class="card-link-underline">En savoir plus sur notre foi →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     4. SECTION SOMBRE : CARROUSEL HORIZONTAL TACTILE
     ═══════════════════════════════════════════════════════ -->
<section id="events" class="section-dark">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div class="section-heading-wrapper" style="margin-bottom: 0;">
                <span class="accent-dash"></span>
                <h2 class="section-title">Engagez-vous dans notre communauté</h2>
                <p class="section-subtitle">Découvrez nos groupes de partage, répétitions de chorale et activités pour la jeunesse.</p>
            </div>
            
            <!-- Flèches carrousel -->
            <div class="carousel-nav-controls">
                <button type="button" class="carousel-arrow-btn" id="carousel-prev-btn" aria-label="Précédent">←</button>
                <button type="button" class="carousel-arrow-btn" id="carousel-next-btn" aria-label="Suivant">→</button>
            </div>
        </div>

        <!-- Carrousel avec défilement tactile (touch swipe) -->
        <div class="carousel-church-container">
            <div class="carousel-church-track">
                <!-- Item 1 -->
                <article class="carousel-church-item card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=600&q=75" 
                             alt="Groupe des Jeunes" class="card-image blur-up-image">
                        <span class="card-category-badge">Jeunesse</span>
                    </div>
                    <div class="card-content-body">
                        <span class="card-meta-date">Tous les samedis • 16h00</span>
                        <h3 class="card-title-church">Rencontre des Jeunes & Ados</h3>
                        <p class="card-snippet">Échanges fraternels, partages bibliques interactifs et moments d'amitié entre jeunes chrétiens.</p>
                        <a href="#" class="card-link-underline">Plus d'informations</a>
                    </div>
                </article>

                <!-- Item 2 -->
                <article class="carousel-church-item card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1465847899084-d164df4dedc6?auto=format&fit=crop&w=600&q=75" 
                             alt="Répétition Chorale" class="card-image blur-up-image">
                        <span class="card-category-badge">Chorale & Louange</span>
                    </div>
                    <div class="card-content-body">
                        <span class="card-meta-date">Vendredis • 19h30</span>
                        <h3 class="card-title-church">Répétition Générale de Louange</h3>
                        <p class="card-snippet">Préparation des chants du culte dominical, apprentissage vocal et adoration fraternelle.</p>
                        <a href="#" class="card-link-underline">Plus d'informations</a>
                    </div>
                </article>

                <!-- Item 3 -->
                <article class="carousel-church-item card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=600&q=75" 
                             alt="Étude Biblique" class="card-image blur-up-image">
                        <span class="card-category-badge">Enseignement</span>
                    </div>
                    <div class="card-content-body">
                        <span class="card-meta-date">Mercredis • 19h00</span>
                        <h3 class="card-title-church">Étude des Écritures & Prière</h3>
                        <p class="card-snippet">Approfondissement verset par verset du livre des Actes des Apôtres avec session de questions-réponses.</p>
                        <a href="#" class="card-link-underline">Plus d'informations</a>
                    </div>
                </article>

                <!-- Item 4 -->
                <article class="carousel-church-item card-church">
                    <div class="card-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=600&q=75" 
                             alt="Entraide et Famille" class="card-image blur-up-image">
                        <span class="card-category-badge">Diaconat</span>
                    </div>
                    <div class="card-content-body">
                        <span class="card-meta-date">1er Dimanche du mois</span>
                        <h3 class="card-title-church">Distribution & Soutien Fraternel</h3>
                        <p class="card-snippet">Action solidaire envers les personnes isolées et les familles de notre communauté.</p>
                        <a href="#" class="card-link-underline">Plus d'informations</a>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     5. SECTION BLANCHE (ALTERNANCE DE STYLE CHURCH X)
     ═══════════════════════════════════════════════════════ -->
<section class="section-white">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="section-heading-wrapper text-center" style="text-align: center;">
            <span class="accent-dash"></span>
            <h2 class="section-title">Nous avons le ministère fait pour vous</h2>
            <p class="section-subtitle" style="margin-left: auto; margin-right: auto;">
                Chacun reçoit des dons uniques pour édifier le Corps du Christ.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 3rem;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; padding: 2rem; border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
                <div style="font-size: 2rem; color: var(--accent-amber); margin-bottom: 1rem;">👶</div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #111;">École du Dimanche</h3>
                <p style="color: #4B5563; font-size: 0.95rem; line-height: 1.6;">
                    Un accueil chaleureux pour les enfants de 3 à 12 ans avec contes bibliques et activités manuelles.
                </p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; padding: 2rem; border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
                <div style="font-size: 2rem; color: var(--accent-amber); margin-bottom: 1rem;">🎵</div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #111;">Chorale & Musique</h3>
                <p style="color: #4B5563; font-size: 0.95rem; line-height: 1.6;">
                    Rejoignez les musiciens et chantres pour guider l'assemblée dans une louange vibrante.
                </p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; padding: 2rem; border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
                <div style="font-size: 2rem; color: var(--accent-amber); margin-bottom: 1rem;">🤝</div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #111;">Écoute & Pastorale</h3>
                <p style="color: #4B5563; font-size: 0.95rem; line-height: 1.6;">
                    Un temps d'écoute confidentiel avec le Pasteur ou l'équipe pastorale en toute discrétion.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     6. SECTION PROGRAMME SOMBRE AVEC GRANDE IMAGE & CARTE BLANCHE
     ═══════════════════════════════════════════════════════ -->
<section id="schedule" class="schedule-church-section" style="background-image: url('https://images.unsplash.com/photo-1510525009512-ad7fc13eefab?auto=format&fit=crop&w=1920&q=80');">
    <div class="schedule-church-overlay"></div>
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="schedule-content-grid">
            <div>
                <span class="accent-dash"></span>
                <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-bottom: 1rem;">
                    Programme des Rendez-vous
                </h2>
                <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem; max-width: 480px; line-height: 1.6;">
                    Venez vivre la communion fraternelle lors de nos cultes hebdomadaires au temple ou en direct streaming.
                </p>
            </div>

            <!-- Carte blanche superposée pour l'événement mis en avant -->
            <div class="schedule-featured-white-card fade-in-on-scroll">
                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent-amber); display: block; margin-bottom: 0.5rem;">
                    Dimanche prochain • Grand Culte
                </span>
                <h3>Culte d'Action de Grâce & Sainte Cène</h3>
                <p>
                    Rejoignez-nous ce dimanche à 09h30 pour un temps fort de prédication, louange par la chorale et célébration fraternelle.
                </p>
                <div style="border-top: 1px solid #E5E7EB; padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #111; font-weight: 600; font-size: 0.9rem;">📍 Temple Principal & Direct YouTube</span>
                    <a href="#" class="btn-church-dark" style="padding: 8px 18px; font-size: 0.85rem; border-radius: var(--radius-xs); text-decoration: none;">
                        Détails
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     7. SECTION GRAPHIQUES ADMIN (THEME SOMBRE CHART.JS)
     ═══════════════════════════════════════════════════════ -->
<section id="charts" class="section-dark">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="section-heading-wrapper">
            <span class="accent-dash"></span>
            <h2 class="section-title">Indicateurs & Graphiques Admin (Chart.js)</h2>
            <p class="section-subtitle">Graphiques sur fond transparent adaptés au mode sombre avec courbes dégradées ambre.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <!-- Graphique 1 : Présences mensuelles -->
            <div class="glass-card" style="padding: 2rem; border-radius: var(--radius-xs);">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem;">📈 Présences aux Cultes (Derniers mois)</h3>
                <canvas id="chart-presences" style="max-height: 250px;"></canvas>
            </div>

            <!-- Graphique 2 : Répartition par Département -->
            <div class="glass-card" style="padding: 2rem; border-radius: var(--radius-xs);">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem;">👥 Répartition des Fidèles par Groupe</h3>
                <canvas id="chart-groupes" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     8. BLOC D'APPEL FINAL (PHOTO + TITRE + BOUTON BLANC)
     ═══════════════════════════════════════════════════════ -->
<section id="join" class="final-cta-section" style="background-image: url('https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80');">
    <div class="final-cta-overlay"></div>
    <div class="final-cta-content">
        <span class="accent-dash" style="margin-left: auto; margin-right: auto;"></span>
        <h2 class="final-cta-title">Faites partie de notre mouvement</h2>
        <p class="final-cta-text">
            Chaque vie compte. Venez découvrir la chaleur d'une famille spirituelle dévouée à l'enseignement du Christ.
        </p>
        <a href="<?= BASE_URL ?>/pasteur.php" class="btn-church-white" style="font-size: 1.05rem; padding: 14px 32px;">
            Contacter le Pasteur
        </a>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     9. MOBILE BOTTOM NAVIGATION (PWA)
     ═══════════════════════════════════════════════════════ -->
<nav class="mobile-bottom-nav">
    <a href="#hero" class="bottom-nav-item active">
        <span class="bottom-nav-icon">🏠</span>
        <span>Accueil</span>
    </a>
    <a href="#events" class="bottom-nav-item">
        <span class="bottom-nav-icon">📢</span>
        <span>Annonces</span>
    </a>
    <a href="<?= BASE_URL ?>/videos.php" class="bottom-nav-item">
        <span class="bottom-nav-icon">▶️</span>
        <span>Direct</span>
    </a>
    <a href="#schedule" class="bottom-nav-item">
        <span class="bottom-nav-icon">📅</span>
        <span>Programme</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/index.php" class="bottom-nav-item">
        <span class="bottom-nav-icon">⚙️</span>
        <span>Admin</span>
    </a>
</nav>

<!-- Scripts JS Church X -->
<script src="<?= ASSETS_URL ?>/js/church-x.js?v=<?= time() ?>"></script>

<!-- Script Graphiques Chart.js pour la démo -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Présences aux Cultes
    const ctxPres = document.getElementById('chart-presences');
    if (ctxPres) {
        new Chart(ctxPres, {
            type: 'line',
            data: {
                labels: ['Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov'],
                datasets: [{
                    label: 'Présences',
                    data: [180, 210, 195, 240, 265, 310],
                    borderColor: '#D97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.15)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#D97706',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.08)' }, ticks: { color: '#A3A3A3' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.08)' }, ticks: { color: '#A3A3A3' } }
                }
            }
        });
    }

    // 2. Fidèles par Groupe
    const ctxGroup = document.getElementById('chart-groupes');
    if (ctxGroup) {
        new Chart(ctxGroup, {
            type: 'doughnut',
            data: {
                labels: ['Jeunesse', 'Chorale', 'Diaconat', 'Enseignants', 'Accueil'],
                datasets: [{
                    data: [45, 30, 25, 20, 15],
                    backgroundColor: ['#D97706', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#A3A3A3', padding: 16 } }
                }
            }
        });
    }
});
</script>

</body>
</html>
