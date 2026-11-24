<?php
// templates/footer.php
// Pied de page public avec verset biblique, Bottom Nav Mobile et scripts Church X

$versetAnnee = get_param('verset_annee', '« Car je connais les projets que j’ai formés sur vous, dit l’Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l’espérance. » — Jérémie 29:11');
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isPage = function ($pattern) use ($currentUri) {
    return str_contains($currentUri, $pattern) ? 'active' : '';
};
?>
</main><!-- /.site-main -->

<footer class="site-footer"
    style="background: var(--bg-surface); border-top: 1px solid var(--glass-border); padding: 4rem 0 2rem 0;">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 1.5rem;">
        <!-- Verset de l'année -->
        <?php if (!empty($versetAnnee)): ?>
            <div class="footer-verset"
                style="background: rgba(217, 119, 6, 0.08); border-left: 3px solid var(--accent-amber); padding: 1.25rem 1.75rem; border-radius: var(--radius-xs); margin-bottom: 3rem;">
                <p style="font-style: italic; color: var(--text-main); font-size: 1rem;"><?= e($versetAnnee) ?></p>
            </div>
        <?php endif; ?>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
            <!-- Coordonnées -->
            <div>
                <span class="accent-dash"></span>
                <h4 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--text-main);">Notre Église</h4>
                <p style="color: var(--text-muted); font-size: 0.925rem; margin-bottom: 0.5rem;">
                    📍
                    <?= e($church['adresse'] ?? '3ème avenue prolongée, Av : de la révolution N.160 (RÉF : école pintu)') ?>
                </p>
                <p style="color: var(--text-muted); font-size: 0.925rem; margin-bottom: 0.5rem;">
                    🏛️ <strong>CFCG GARENGANZE</strong>
                </p>
                <p style="color: var(--text-muted); font-size: 0.925rem;">
                    ✨ <em>« Soyez le bienvenu et que Dieu vous bénisse »</em>
                </p>
            </div>

            <!-- Horaires des cultes (Officiel Philadelphie Church) -->
            <div>
                <span class="accent-dash"></span>
                <h4 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--text-main);">Horaires Principaux</h4>
                <p style="color: var(--text-muted); font-size: 0.925rem; margin-bottom: 0.5rem;">
                    <strong style="color: var(--text-main);">Dimanche :</strong><br>
                    08h30 Sainte-Cène • 10h00 Bonne Nouvelle • 13h00 Jeunes
                </p>
                <p style="color: var(--text-muted); font-size: 0.925rem; margin-bottom: 0.5rem;">
                    <strong style="color: var(--text-main);">Mercredi & Vendredi :</strong><br>
                    16h30 - 18h00 (Enseignement & Culte de Prière)
                </p>
                <p style="color: var(--text-muted); font-size: 0.925rem;">
                    <strong style="color: var(--text-main);">Chorales :</strong> Mardi, Jeudi, Samedi
                </p>
            </div>

            <!-- Liens utiles & Soutien App -->
            <div>
                <span class="accent-dash"></span>
                <h4 style="font-size: 1.15rem; margin-bottom: 1rem; color: var(--text-main);">Accès Rapide</h4>
                <ul style="list-style: none; font-size: 0.925rem; display: flex; flex-direction: column; gap: 0.65rem;">
                    <li><a href="<?= BASE_URL ?>/evenements.php"
                            style="color: var(--text-muted); text-decoration: none;">Programme & Calendrier</a></li>
                    <li><a href="<?= BASE_URL ?>/priere.php"
                            style="color: var(--text-muted); text-decoration: none;">Déposer une demande de prière</a>
                    </li>
                    <li><a href="<?= BASE_URL ?>/don.php" style="color: var(--text-muted); text-decoration: none;">Dons
                            & Soutien de l'Église</a></li>
                    <li><a href="https://wa.me/243901247908?text=<?= urlencode("Bonjour, je souhaite soutenir l'application Philadelphie Church.") ?>"
                            target="_blank" style="color: #25D366; text-decoration: none; font-weight: 600;">
                            💬 Soutien App (WhatsApp : 0901247908)
                        </a>
                    </li>
                    <li><a href="<?= BASE_URL ?>/admin/login.php"
                            style="color: var(--accent-amber); text-decoration: none;">🔐 Espace Staff (Pasteur /
                            Média)</a></li>
                </ul>
            </div>
        </div>

        <div
            style="border-top: 1px solid var(--glass-border); padding-top: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-subtle);">
            <div>
                &copy; <?= date('Y') ?> <?= e($church['nom'] ?? 'Philadelphie Church') ?> • CFCG GARENGANZE.
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    Maintenance & hébergement application : <strong>0990945558</strong> • Infos :
                    <strong>0901247908</strong>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="https://wa.me/243901247908" target="_blank" class="btn-church-outline"
                    style="padding: 6px 12px; font-size: 0.75rem; border-color: #25D366; color: #25D366;">
                    WhatsApp Développeur
                </a>
                <button type="button" id="pwa-notif-btn" class="btn-church-outline"
                    style="padding: 6px 12px; font-size: 0.75rem;">
                    🔔 Notifications Push
                </button>
            </div>
        </div>
    </div>
</footer>

<!-- ═══════════════════════════════════════════════════════
     BARRE DE NAVIGATION FIXE MOBILE PWA (BOTTOM NAV)
     ═══════════════════════════════════════════════════════ -->
<nav class="mobile-bottom-nav">
    <a href="<?= BASE_URL ?>/"
        class="bottom-nav-item <?= ($currentUri === '/' || str_ends_with($currentUri, 'index.php')) ? 'active' : '' ?>">
        <span class="bottom-nav-icon">🏠</span>
        <span>Accueil</span>
    </a>
    <a href="<?= BASE_URL ?>/evenements.php" class="bottom-nav-item <?= $isPage('evenements') ?>">
        <span class="bottom-nav-icon">📅</span>
        <span>Programme</span>
    </a>
    <a href="<?= BASE_URL ?>/videos.php" class="bottom-nav-item <?= $isPage('videos') ?>">
        <span class="bottom-nav-icon">▶️</span>
        <span>Direct</span>
    </a>
    <a href="<?= BASE_URL ?>/communaute.php" class="bottom-nav-item <?= $isPage('communaute') ?>">
        <span class="bottom-nav-icon">💬</span>
        <span>Publications</span>
    </a>
    <a href="<?= ADMIN_URL ?>/login.php"
        class="bottom-nav-item <?= str_contains($currentUri, 'admin') ? 'active' : '' ?>"
        style="color: var(--accent-amber);">
        <span class="bottom-nav-icon">🔐</span>
        <span>Staff</span>
    </a>
</nav>

<!-- Scripts JavaScript Church X & PWA -->
<script src="<?= ASSETS_URL ?>/js/church-x.js?v=<?= APP_VERSION ?>"></script>
<script src="<?= ASSETS_URL ?>/js/app.js?v=<?= APP_VERSION ?>"></script>
<script src="<?= ASSETS_URL ?>/js/pwa-install.js?v=<?= APP_VERSION ?>"></script>

<!-- Enregistrement Service Worker PWA -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
                .then(reg => console.log('Service Worker PWA actif'))
                .catch(err => console.log('Erreur SW:', err));
        });
    }
</script>
</body>

</html>