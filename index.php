<?php
// index.php
// Page d'accueil publique — Design System Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$church = get_church_info();
$pageTitle = 'Accueil';
$isHeroPage = true; // Pas de container padding automatique en haut

// 1. Prochain événement
$stmtEvent = db()->prepare("
    SELECT e.*, c.nom as categorie_nom, c.couleur as categorie_couleur 
    FROM evenements e 
    LEFT JOIN categories c ON e.categorie_id = c.id 
    WHERE e.statut = 'publie' AND e.date_debut >= NOW() 
    ORDER BY e.date_debut ASC LIMIT 1
");
$stmtEvent->execute();
$nextEvent = $stmtEvent->fetch();

// 2. Dernières annonces
$stmtAnnonces = db()->prepare("
    SELECT a.*, c.nom as categorie_nom, c.couleur as categorie_couleur 
    FROM annonces a 
    LEFT JOIN categories c ON a.categorie_id = c.id 
    WHERE a.statut = 'publie' 
    ORDER BY a.epingle DESC, a.date_publication DESC LIMIT 4
");
$stmtAnnonces->execute();
$annonces = $stmtAnnonces->fetchAll();

// 3. Moment fort / Pensée du jour
$stmtMoment = db()->query("
    SELECT * FROM moments_forts 
    WHERE statut = 'publie' 
    ORDER BY date_moment DESC, id DESC LIMIT 1
");
$momentFort = $stmtMoment->fetch();

// 4. Dernière vidéo / prédication
$stmtVideo = db()->query("
    SELECT * FROM videos 
    WHERE statut = 'publie' 
    ORDER BY date_video DESC, id DESC LIMIT 1
");
$lastVideo = $stmtVideo->fetch();

// 5. Départements actifs
$departements = db()->query("SELECT * FROM departements WHERE actif = 1 ORDER BY id ASC LIMIT 4")->fetchAll();

// 6. Derniers partages du mur fraternel
$latestPubs = db()->query("
    SELECT * FROM publications_communaute 
    WHERE statut = 'publie' 
    ORDER BY created_at DESC LIMIT 3
")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<!-- ═══════════════════════════════════════════════════════
     1. HERO CINÉMATOGRAPHIQUE CHURCH X
     ═══════════════════════════════════════════════════════ -->
<?php $heroAccueilBg = function_exists('get_param') ? get_param('hero_bg_accueil', 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80') : 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80'; ?>
<section class="church-hero" style="background-image: url('<?= e($heroAccueilBg) ?>');">
    <div class="church-hero-overlay"></div>
    <div class="church-hero-radial"></div>

    <div class="container church-hero-content" style="max-width: 1280px; margin: 0 auto; width: 100%;">
        <span class="hero-tag">✞ <?= e($church['slogan'] ?? 'Bienvenue dans la maison du Seigneur') ?></span>
        <h1 class="hero-main-title">
            <?= e($church['nom'] ?? 'Église de la Grâce') ?>
        </h1>
        <p class="hero-description">
            <?= e($church['a_propos'] ?? 'Une communauté vivante pour louer, prier et grandir ensemble dans la foi chrétienne.') ?>
        </p>
        <div class="hero-buttons-row">
            <a href="<?= BASE_URL ?>/evenements.php" class="btn-church-white">
                Participer au culte
            </a>
            <a href="<?= BASE_URL ?>/communaute.php" class="btn-church-outline">
                Publications
            </a>
            <a href="<?= BASE_URL ?>/pasteur.php" class="btn-church-outline">
                Parler au Pasteur
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     2. CARTE BLANCHE CHEVAUCHANTE (SIGNATURE CHURCH X)
     ═══════════════════════════════════════════════════════ -->
<section class="hero-overlap-section">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="overlap-white-card fade-in-on-scroll">
            <div>
                <span class="accent-dash"></span>
                <h2>Nous croyons en la force de l'amour de Dieu</h2>
            </div>
            <div>
                <p>
                    Au cœur de notre assemblée, chaque fidèle et chaque visiteur est accueilli comme un membre de la
                    famille en Christ. Venez partager la joie de la louange et la bénédiction de la Parole.
                </p>
                <div style="margin-top: 1.5rem;">
                    <a href="<?= BASE_URL ?>/communaute.php" class="card-link-underline">Rejoindre les témoignages &
                        échanges →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     3. SECTION SOMBRE : CARROUSEL D'ANNONCES & ACTIVITÉS
     ═══════════════════════════════════════════════════════ -->
<section class="section-dark">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div
            style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div class="section-heading-wrapper" style="margin-bottom: 0;">
                <span class="accent-dash"></span>
                <h2 class="section-title">Vie Paroissiale & Annonces</h2>
                <p class="section-subtitle">Restez informés des cultes spéciaux, des activités des départements et des
                    moments forts.</p>
            </div>

            <div class="carousel-nav-controls">
                <button type="button" class="carousel-arrow-btn" id="carousel-prev-btn"
                    aria-label="Précédent">←</button>
                <button type="button" class="carousel-arrow-btn" id="carousel-next-btn" aria-label="Suivant">→</button>
            </div>
        </div>

        <div class="carousel-church-container">
            <div class="carousel-church-track">
                <?php if (!empty($annonces)): ?>
                    <?php foreach ($annonces as $a): ?>
                        <article class="carousel-church-item card-church">
                            <div class="card-image-wrapper">
                                <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=600&q=75"
                                    alt="<?= e($a['titre']) ?>" class="card-image blur-up-image">
                                <span class="card-category-badge"><?= e($a['categorie_nom'] ?? 'Annonce') ?></span>
                            </div>
                            <div class="card-content-body">
                                <span class="card-meta-date">🗓️ <?= format_date_fr($a['date_publication']) ?></span>
                                <h3 class="card-title-church"><?= e($a['titre']) ?></h3>
                                <p class="card-snippet"><?= e(truncate_text($a['contenu'], 120)) ?></p>
                                <a href="<?= BASE_URL ?>/annonces.php" class="card-link-underline">Plus d'informations</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: var(--text-muted); padding: 2rem;">Aucune annonce récente.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     4. SECTION BLANCHE (ALTERNANCE DE MINISTÈRES)
     ═══════════════════════════════════════════════════════ -->
<section class="section-white">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="section-heading-wrapper" style="text-align: center;">
            <span class="accent-dash"></span>
            <h2 class="section-title">Les Ministères de l'Église</h2>
            <p class="section-subtitle" style="margin-left: auto; margin-right: auto;">
                Chaque membre a un don précieux à mettre au service du Corps du Christ.
            </p>
        </div>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem; margin-top: 3rem;">
            <?php
            $icons = ['🎵', '🙏', '👶', '🤝', '📖', '🏥'];
            $i = 0;
            foreach ($departements as $dept):
                $ic = $icons[$i % count($icons)];
                $i++;
                ?>
                <div
                    style="background: #FFFFFF; border: 1px solid #E5E7EB; padding: 2.25rem 2rem; border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
                    <div style="font-size: 2.2rem; color: var(--accent-amber); margin-bottom: 1rem;"><?= $ic ?></div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #111;"><?= e($dept['nom']) ?></h3>
                    <p style="color: #4B5563; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                        <?= e(truncate_text($dept['description'] ?? 'Rejoignez notre équipe pour servir Dieu et notre communauté.', 95)) ?>
                    </p>
                    <span style="font-size: 0.825rem; font-weight: 700; color: var(--accent-amber);">
                        Resp. <?= e($dept['responsable_nom'] ?? 'Équipe pastorale') ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     5. SECTION NOS PROGRAMMES HEBDOMADAIRES (OFFICIEL PHILADELPHIE CHURCH)
     ═══════════════════════════════════════════════════════ -->
<section class="section-dark"
    style="background: linear-gradient(180deg, #070B14 0%, #0D1627 50%, #070B14 100%); padding: 5rem 0;">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">

        <!-- En-tête de l'affiche -->
        <div
            style="background: rgba(13, 22, 39, 0.85); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: var(--radius-sm); padding: 2.25rem 2rem; margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; backdrop-filter: blur(14px);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: #FFFFFF; color: #111; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
                    ⛪
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: #FFFFFF; margin: 0; line-height: 1.1;">
                        PHILADELPHIE Church</h3>
                    <span style="color: #EF4444; font-size: 1.1rem; font-weight: 900; letter-spacing: 0.05em;">CFCG
                        GARENGANZE</span>
                </div>
            </div>

            <div style="text-align: right; font-size: 0.95rem; color: #E2E8F0; line-height: 1.5;">
                <div style="display: flex; align-items: center; gap: 8px; justify-content: flex-end;">
                    <span style="font-size: 1.3rem;">📍</span>
                    <strong style="color: #FFFFFF; font-size: 1.05rem;">EGLISE PHILADELPHIE</strong>
                </div>
                <div>3ème avenue prolongée</div>
                <div style="color: var(--accent-amber); font-weight: 600;">RÉF : école pintu</div>
                <div style="font-size: 0.875rem; color: #94A3B8;">Av : de la révolution N.160</div>
            </div>
        </div>

        <!-- Titre calligraphique -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <span class="accent-dash" style="margin-left: auto; margin-right: auto;"></span>
            <h2
                style="font-family: var(--font-serif); font-size: clamp(2.5rem, 5vw, 3.8rem); font-style: italic; color: #FFFFFF; text-shadow: 0 4px 20px rgba(0,0,0,0.6);">
                Nos Programmes
            </h2>
            <p style="color: #94A3B8; font-size: 1.05rem; margin-top: 0.5rem;">
                Retrouvez l’ensemble des rassemblements et cultes de la semaine
            </p>
        </div>

        <!-- Grille des 7 jours (Inspirée de l'affiche officielle) -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">

            <!-- LUNDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #FFFFFF; color: #0B1323; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    LUNDI
                </div>
                <div
                    style="padding: 1.75rem 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 1.2rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">bloc</div>
                    <div style="color: var(--accent-amber); font-weight: 800; font-size: 1.05rem;">17h00 - 18h00</div>
                </div>
            </div>

            <!-- MARDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #FFFFFF; color: #0B1323; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    MARDI
                </div>
                <div
                    style="padding: 1.75rem 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 1.1rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">répétition des
                        chorales</div>
                    <div style="color: var(--accent-amber); font-weight: 800; font-size: 1.05rem;">16h30 - 18h00</div>
                </div>
            </div>

            <!-- MERCREDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #7F1D1D; color: #FFFFFF; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    MERCREDI
                </div>
                <div
                    style="padding: 1.75rem 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 1.2rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">Enseignement
                    </div>
                    <div style="color: var(--accent-amber); font-weight: 800; font-size: 1.05rem;">16h30 - 18h00</div>
                </div>
            </div>

            <!-- JEUDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #FFFFFF; color: #0B1323; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    JEUDI
                </div>
                <div
                    style="padding: 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                    <div>
                        <div style="font-size: 1rem; font-weight: 700; color: #FFFFFF;">culte des mamans</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.95rem;">15h30 - 16h30
                        </div>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px;">
                        <div style="font-size: 0.95rem; font-weight: 700; color: #E2E8F0;">répétition des chorales</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.95rem;">16h30 - 18h00
                        </div>
                    </div>
                </div>
            </div>

            <!-- VENDREDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #FFFFFF; color: #0B1323; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    VENDREDI
                </div>
                <div
                    style="padding: 1.75rem 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center;">
                    <div style="font-size: 1.15rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">culte de
                        prière</div>
                    <div style="color: var(--accent-amber); font-weight: 800; font-size: 1.05rem;">16h30 - 18h00</div>
                </div>
            </div>

            <!-- SAMEDI -->
            <div
                style="background: #0B1323; border: 1px solid rgba(255,255,255,0.15); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle);">
                <div
                    style="background: #FFFFFF; color: #0B1323; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    SAMEDI
                </div>
                <div
                    style="padding: 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                    <div>
                        <div style="font-size: 1rem; font-weight: 700; color: #FFFFFF;">collège des anciens</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.95rem;">13h00</div>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px;">
                        <div style="font-size: 0.95rem; font-weight: 700; color: #E2E8F0;">répétition des chorales</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.95rem;">15h30 - 18h00
                        </div>
                    </div>
                </div>
            </div>

            <!-- DIMANCHE -->
            <div
                style="background: #0B1323; border: 2px solid var(--accent-amber); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-elevated);">
                <div
                    style="background: var(--accent-amber); color: #070B14; text-align: center; font-weight: 900; font-size: 1.05rem; padding: 10px; letter-spacing: 0.08em;">
                    DIMANCHE
                </div>
                <div
                    style="padding: 1.25rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center; gap: 6px;">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #FFFFFF;">sainte-scène</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.9rem;">08h30 - 09h45
                        </div>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 4px;">
                        <div style="font-size: 0.95rem; font-weight: 800; color: #FFFFFF;">bonne nouvelle</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.9rem;">10h00 - 12h30
                        </div>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 4px;">
                        <div style="font-size: 0.95rem; font-weight: 700; color: #FFFFFF;">culte des jeunes</div>
                        <div style="color: var(--accent-amber); font-weight: 800; font-size: 0.9rem;">13h00 - 15h00
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Phrase d'accueil de l'affiche -->
        <div
            style="text-align: center; margin-top: 2.5rem; padding: 1.5rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: var(--radius-xs);">
            <p
                style="font-family: var(--font-serif); font-size: 1.35rem; color: #FFFFFF; font-style: italic; margin: 0;">
                « Soyez le bienvenu et que Dieu vous bénisse »
            </p>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     6. SECTION PRÉDICATION VIDÉO & DIRECT STREAMING
     ═══════════════════════════════════════════════════════ -->
<?php if ($lastVideo): ?>
    <section class="section-dark">
        <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
            <div class="glass-card" style="padding: 3rem 2.5rem; border-radius: var(--radius-xs);">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem; align-items: center;">
                    <div>
                        <span class="accent-dash"></span>
                        <span
                            style="font-size: 0.85rem; font-weight: 700; color: var(--accent-amber); text-transform: uppercase; display: block; margin-bottom: 0.5rem;">
                            🎙️ Enseignement Récent
                        </span>
                        <h2 style="font-size: clamp(1.8rem, 3.5vw, 2.5rem); margin-bottom: 1rem;">
                            <?= e($lastVideo['titre']) ?>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                            <?= e($lastVideo['description'] ?? 'Visionnez notre dernier message pour fortifier votre marche avec Dieu.') ?>
                        </p>
                        <div
                            style="display: flex; gap: 18px; color: var(--text-subtle); font-size: 0.9rem; margin-bottom: 2rem;">
                            <?php if (!empty($lastVideo['predicateur'])): ?>
                                <span>🎙️ <?= e($lastVideo['predicateur']) ?></span>
                            <?php endif; ?>
                            <span>👁️ <?= (int) $lastVideo['vues_count'] ?> vues</span>
                        </div>
                        <a href="<?= BASE_URL ?>/videos.php" class="btn-church-white">
                            ▶ Regarder les vidéos
                        </a>
                    </div>

                    <div>
                        <?php $embed = get_youtube_embed_url($lastVideo['url']);
                        if ($embed): ?>
                            <div
                                style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                                <iframe src="<?= e($embed) ?>" title="<?= e($lastVideo['titre']) ?>"
                                    style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;"
                                    allowfullscreen loading="lazy"></iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     SOUTIEN & MAINTENANCE DE L'APPLICATION (APPEL DE FONDS)
     ═══════════════════════════════════════════════════════ -->
<section class="section-dark" style="padding: 3.5rem 0 2rem;">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="glass-card"
            style="padding: 2.5rem; border-radius: var(--radius-xs); border: 2px solid var(--accent-amber); background: linear-gradient(135deg, rgba(217,119,6,0.12) 0%, rgba(18,18,18,0.92) 100%);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.75rem;">
                <span style="font-size: 2rem;">🚀</span>
                <div>
                    <span class="accent-dash" style="margin-bottom: 4px;"></span>
                    <h3 style="font-size: clamp(1.3rem, 3vw, 1.7rem); margin: 0; color: #FFF; font-weight: 700;">
                        Soutenez la Maintenance & le Déploiement de notre Application
                    </h3>
                </div>
            </div>

            <p
                style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; max-width: 900px; margin-bottom: 1.5rem;">
                Pour garantir la disponibilité continue de l'application <strong>Philadelphie Church (CFCG
                    GARENGANZE)</strong>, le renouvellement du nom de domaine officiel, l'hébergement serveur haute
                performance et l'ingénierie des développeurs, nous faisons appel à votre générosité chrétienne. Devis en
                Franc Congolais (FC) et Dollars ($).
            </p>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                <div
                    style="background: rgba(0,0,0,0.4); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid rgba(217,119,6,0.35);">
                    <div
                        style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-amber); font-weight: 700;">
                        💳 Envoi Mobile Money (RDC)
                    </div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #FFF; margin: 6px 0;">
                        0990945558
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        M-Pesa • Airtel Money • Orange Money (FC & USD)
                    </div>
                </div>

                <div
                    style="background: rgba(0,0,0,0.4); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                    <div
                        style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-amber); font-weight: 700;">
                        📞 Contact & Renseignements
                    </div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #FFF; margin: 6px 0;">
                        0901247908
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Ligne directe pour toute information technique
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <a href="https://wa.me/243901247908?text=<?= urlencode("Bonjour, je souhaite soutenir financièrement la maintenance et le déploiement de l'application Philadelphie Church.") ?>"
                    target="_blank" class="btn-church-white"
                    style="background: #25D366; color: #FFF; border-color: #25D366; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <span>🟢 Contacter sur WhatsApp (+243 901 247 908)</span>
                </a>
                <a href="<?= BASE_URL ?>/don.php" class="btn-church-outline">
                    💛 Déclarer un don en ligne
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     7. BLOC D'APPEL FINAL (CTA)
     ═══════════════════════════════════════════════════════ -->
<section class="final-cta-section"
    style="background-image: url('https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80');">
    <div class="final-cta-overlay"></div>
    <div class="final-cta-content">
        <span class="accent-dash" style="margin-left: auto; margin-right: auto;"></span>
        <h2 class="final-cta-title">Faites partie de notre famille</h2>
        <p class="final-cta-text">
            Chaque vie a une valeur infinie aux yeux du Créateur. Venez grandir et louer avec nous.
        </p>
        <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/pasteur.php" class="btn-church-white"
                style="font-size: 1.05rem; padding: 14px 30px;">
                Écrire au Pasteur
            </a>
            <a href="<?= BASE_URL ?>/don.php" class="btn-church-outline"
                style="font-size: 1.05rem; padding: 14px 30px;">
                Soutenir la mission
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/templates/footer.php'; ?>