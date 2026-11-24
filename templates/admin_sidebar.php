<?php
// templates/admin_sidebar.php
// Barre latérale d'administration avec contrôle des accès selon le rôle

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function(string $page) use ($currentUri) {
    return str_contains($currentUri, $page) ? 'active' : '';
};
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <a href="<?= ADMIN_URL ?>/index.php" class="sidebar-brand">
        <div class="brand-icon" style="width: 32px; height: 32px; font-size: 1rem;">✞</div>
        <div class="brand-text">
            <span class="brand-title" style="font-size: 1rem;"><?= e($church['nom'] ?? 'Église App') ?></span>
            <span class="brand-subtitle">Console Admin</span>
        </div>
    </a>

    <ul class="sidebar-menu">
        <li class="menu-label">Principal</li>
        <li class="menu-item <?= $currentUri === '/admin/' || str_ends_with($currentUri, 'admin/index.php') ? 'active' : '' ?>">
            <a href="<?= ADMIN_URL ?>/index.php">📊 Tableau de bord</a>
        </li>
        <li class="menu-item <?= $isActive('statistiques') ?>">
            <a href="<?= ADMIN_URL ?>/statistiques.php">📈 Statistiques & Graphiques</a>
        </li>

        <li class="menu-label">Gestion Paroissiale</li>
        <li class="menu-item <?= $isActive('fideles') ?>">
            <a href="<?= ADMIN_URL ?>/fideles.php">👥 Fidèles & Membres</a>
        </li>
        <li class="menu-item <?= $isActive('presences') ?>">
            <a href="<?= ADMIN_URL ?>/presences.php">✍️ Pointage Présences</a>
        </li>
        <li class="menu-item <?= $isActive('departements') ?>">
            <a href="<?= ADMIN_URL ?>/departements.php">🏛️ Départements</a>
        </li>

        <li class="menu-label">Communication & Médias</li>
        <?php if (has_role(ROLE_MEDIA)): ?>
        <li class="menu-item <?= $isActive('communaute_publier') ?>">
            <a href="<?= ADMIN_URL ?>/communaute_publier.php">📢 Publications & Partages</a>
        </li>
        <li class="menu-item <?= $isActive('annonces') ?>">
            <a href="<?= ADMIN_URL ?>/annonces.php">📢 Annonces</a>
        </li>
        <li class="menu-item <?= $isActive('videos') ?>">
            <a href="<?= ADMIN_URL ?>/videos.php">🎥 Vidéos & Direct</a>
        </li>
        <li class="menu-item <?= $isActive('notifications') ?>">
            <a href="<?= ADMIN_URL ?>/notifications.php">🔔 Notifications Push</a>
        </li>
        <li class="menu-item <?= $isActive('images_ui') ?>">
            <a href="<?= ADMIN_URL ?>/images_ui.php">🖼️ Images d'arrière-plan</a>
        </li>
        <li class="menu-item <?= $isActive('communaute_moderation') ?>">
            <a href="<?= ADMIN_URL ?>/communaute_moderation.php">🛡️ Modération des Échanges</a>
        </li>
        <?php endif; ?>
        <li class="menu-item <?= $isActive('evenements') ?>">
            <a href="<?= ADMIN_URL ?>/evenements.php">📅 Événements</a>
        </li>

        <?php if (has_role(ROLE_RESPONSABLE)): ?>
        <li class="menu-label">Pastorale & Finances (Pasteur)</li>
        <li class="menu-item <?= $isActive('pasteur_messages') ?>">
            <a href="<?= ADMIN_URL ?>/pasteur_messages.php">📬 Boîte du Pasteur</a>
        </li>
        <li class="menu-item <?= $isActive('priere') ?>">
            <a href="<?= ADMIN_URL ?>/prieres.php">🙏 Demandes de Prière</a>
        </li>
        <li class="menu-item <?= $isActive('dons') ?>">
            <a href="<?= ADMIN_URL ?>/dons.php">💰 Dons & Finances (RDC)</a>
        </li>
        <?php endif; ?>

        <?php if (has_role(ROLE_DEVELOPPEUR)): ?>
            <li class="menu-label">Système & Sécurité</li>
            <li class="menu-item <?= $isActive('parametres') ?>">
                <a href="<?= ADMIN_URL ?>/parametres.php">⚙️ Paramètres Généraux</a>
            </li>
            <li class="menu-item <?= $isActive('admins') ?>">
                <a href="<?= ADMIN_URL ?>/admins.php">🛡️ Comptes Administrateurs</a>
            </li>
            <li class="menu-item <?= $isActive('logs') ?>">
                <a href="<?= ADMIN_URL ?>/logs.php">📜 Journal d'Audit (Logs)</a>
            </li>
        <?php endif; ?>
        <li class="menu-item <?= $isActive('aide') ?>">
            <a href="<?= ADMIN_URL ?>/aide.php">❓ Guide Rôles & Accès</a>
        </li>
    </ul>

    <!-- Profil connecté & Déconnexion -->
    <div class="sidebar-user">
        <div style="display: flex; flex-direction: column;">
            <span style="font-weight: 600; font-size: 0.875rem; color: var(--text-primary);"><?= e($currentUser['nom']) ?></span>
            <span class="badge badge-gold" style="font-size: 0.65rem; margin-top: 4px;"><?= e($currentUser['role']) ?></span>
        </div>
        <a href="<?= ADMIN_URL ?>/logout.php" title="Se déconnecter" style="color: var(--color-danger); font-size: 1.25rem;">
            🚪
        </a>
    </div>
</aside>
