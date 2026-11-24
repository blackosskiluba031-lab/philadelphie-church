<?php
// admin/index.php
// Tableau de bord principal de l'administration paroissiale

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Tableau de bord';
$pageTitle = 'Tableau de Bord';
$currentUser = current_user();

// 1. Métriques clés
// Nombre de fidèles
$totalFideles = (int)db()->query("SELECT COUNT(*) FROM fideles WHERE statut = 'actif'")->fetchColumn();

// Dernier événement avec pointage
$stmtLastPresence = db()->query("
    SELECT e.id, e.titre, e.date_debut,
           SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as presents_count,
           COUNT(p.id) as total_pointes
    FROM presences p
    INNER JOIN evenements e ON p.evenement_id = e.id
    GROUP BY e.id, e.titre, e.date_debut
    ORDER BY e.date_debut DESC LIMIT 1
");
$lastCulte = $stmtLastPresence->fetch();

// Total des dons du mois en cours
$stmtDonsMois = db()->query("
    SELECT COALESCE(SUM(montant), 0) as total_mois, COUNT(*) as nb_dons 
    FROM dons 
    WHERE statut = 'valide' 
    AND MONTH(date_don) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_don) = YEAR(CURRENT_DATE())
");
$donsStats = $stmtDonsMois->fetch();

// Demandes de prière en attente
$prieresEnAttente = (int)db()->query("SELECT COUNT(*) FROM demandes_priere WHERE statut = 'nouveau'")->fetchColumn();

// Messages non lus pour le Pasteur
$messagesPasteurNonLus = (int)db()->query("SELECT COUNT(*) FROM messages_pasteur WHERE statut = 'non_lu'")->fetchColumn();

// Abonnements Push PWA actifs
$pushAbonnes = (int)db()->query("SELECT COUNT(*) FROM push_subscriptions")->fetchColumn();

// 2. Activités récentes
// Dernières demandes de prière
$dernieresPrieres = db()->query("
    SELECT * FROM demandes_priere 
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Derniers dons
$derniersDons = db()->query("
    SELECT * FROM dons 
    ORDER BY date_don DESC LIMIT 5
")->fetchAll();

// Derniers logs (si rôle développeur)
$derniersLogs = [];
if (has_role(ROLE_DEVELOPPEUR)) {
    $derniersLogs = db()->query("
        SELECT l.*, a.nom as admin_nom, a.username 
        FROM admin_logs l 
        LEFT JOIN admins a ON l.admin_id = a.id 
        ORDER BY l.created_at DESC LIMIT 6
    ")->fetchAll();
}

include __DIR__ . '/../templates/admin_header.php';
?>

<!-- Message d'accueil personnalisé -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">
            Bonjour, <?= e($currentUser['nom']) ?> 👋
        </h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Aujourd'hui est le <?= format_date_fr(date('Y-m-d')) ?> • Rôle : <span class="badge badge-gold"><?= e(ucfirst($currentUser['role'])) ?></span>
        </p>
    </div>

    <!-- Actions rapides -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if ($messagesPasteurNonLus > 0 && has_role([ROLE_RESPONSABLE, ROLE_DEVELOPPEUR])): ?>
            <a href="<?= ADMIN_URL ?>/pasteur_messages.php" class="btn btn-primary btn-sm" style="background: var(--color-danger); border-color: var(--color-danger);">
                📬 <?= $messagesPasteurNonLus ?> Message(s) Pasteur
            </a>
        <?php endif; ?>
        <a href="<?= ADMIN_URL ?>/presences.php" class="btn btn-primary btn-sm">
            ✍️ Pointer un culte
        </a>
        <a href="<?= ADMIN_URL ?>/annonces.php?action=create" class="btn btn-outline btn-sm">
            📢 Publier une annonce
        </a>
        <a href="<?= ADMIN_URL ?>/fideles.php?action=create" class="btn btn-outline btn-sm">
            👥 Ajouter un fidèle
        </a>
    </div>
</div>

<!-- 4 Métriques principales -->
<div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
    <!-- Fidèles -->
    <div class="metric-card">
        <div>
            <span class="metric-label">Fidèles Inscrits</span>
            <div class="metric-value"><?= $totalFideles ?></div>
            <a href="<?= ADMIN_URL ?>/fideles.php" style="font-size: 0.8rem; color: var(--gold-primary);">
                Gérer le répertoire &rarr;
            </a>
        </div>
        <div class="metric-icon">👥</div>
    </div>

    <!-- Présences au dernier culte -->
    <div class="metric-card">
        <div>
            <span class="metric-label">Présences Dernier Culte</span>
            <div class="metric-value">
                <?= $lastCulte ? (int)$lastCulte['presents_count'] : '-' ?>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                <?= $lastCulte ? e(truncate_text($lastCulte['titre'], 25)) : 'Aucun pointage' ?>
            </span>
        </div>
        <div class="metric-icon">✍️</div>
    </div>

    <!-- Dons du mois -->
    <div class="metric-card">
        <div>
            <span class="metric-label">Dons ce Mois-ci</span>
            <div class="metric-value">
                <?= number_format($donsStats['total_mois'] ?? 0, 0, ',', ' ') ?> €
            </div>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                <?= (int)$donsStats['nb_dons'] ?> offrandes reçues
            </span>
        </div>
        <div class="metric-icon">💰</div>
    </div>

    <!-- Prières en attente -->
    <div class="metric-card" style="<?= $prieresEnAttente > 0 ? 'border-color: var(--color-warning);' : '' ?>">
        <div>
            <span class="metric-label">Sujets de Prière</span>
            <div class="metric-value" style="<?= $prieresEnAttente > 0 ? 'color: var(--color-warning);' : '' ?>">
                <?= $prieresEnAttente ?>
            </div>
            <a href="<?= ADMIN_URL ?>/prieres.php" style="font-size: 0.8rem; color: var(--gold-primary);">
                <?= $prieresEnAttente > 0 ? '⚠️ En attente de suivi' : 'Toutes traitées' ?> &rarr;
            </a>
        </div>
        <div class="metric-icon" style="<?= $prieresEnAttente > 0 ? 'background: rgba(245, 158, 11, 0.15); color: var(--color-warning);' : '' ?>">
            🙏
        </div>
    </div>
</div>

<!-- Deuxième section : Activités récentes -->
<div class="grid grid-cols-2" style="margin-bottom: 2.5rem;">
    <!-- Dernières demandes de prière -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🙏 Intentions de Prière Récentes</h3>
            <a href="<?= ADMIN_URL ?>/prieres.php" class="btn btn-outline btn-sm">Tout voir</a>
        </div>

        <?php if (empty($dernieresPrieres)): ?>
            <p style="color: var(--text-muted); padding: 1rem 0;">Aucune demande de prière enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Demandeur</th>
                            <th>Sujet</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresPrieres as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= $p['anonyme'] ? '🔒 Anonyme' : e($p['nom_demandeur'] ?? 'Fidèle') ?></strong>
                                </td>
                                <td><?= e(truncate_text($p['sujet'], 30)) ?></td>
                                <td>
                                    <?php
                                        $badgeClass = match($p['statut']) {
                                            'nouveau' => 'badge-warning',
                                            'en_priere' => 'badge-gold',
                                            'exauce' => 'badge-success',
                                            default => 'badge-outline'
                                        };
                                        $labelStatut = match($p['statut']) {
                                            'nouveau' => 'Nouveau',
                                            'en_priere' => 'En Prière',
                                            'exauce' => 'Exaucé',
                                            default => ucfirst($p['statut'])
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $labelStatut ?></span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Derniers dons enregistrés -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💰 Dernières Offrandes & Dîmes</h3>
            <a href="<?= ADMIN_URL ?>/dons.php" class="btn btn-outline btn-sm">Tout voir</a>
        </div>

        <?php if (empty($derniersDons)): ?>
            <p style="color: var(--text-muted); padding: 1rem 0;">Aucun don enregistré récemment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Donateur</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniersDons as $d): ?>
                            <tr>
                                <td><?= e($d['nom_donateur'] ?? 'Anonyme') ?></td>
                                <td>
                                    <span class="badge badge-gold" style="font-size: 0.65rem;">
                                        <?= strtoupper(e($d['type_don'])) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: var(--gold-primary);">
                                    <?= format_currency((float)$d['montant'], $d['devise']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $d['statut'] === 'valide' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst(e($d['statut'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section Journal d'Audit (Rôle Développeur uniquement) -->
<?php if (has_role(ROLE_DEVELOPPEUR)): ?>
<div class="card">
    <div class="card-header">
        <div>
            <span class="badge badge-gold" style="margin-bottom: 4px;">Sécurité & Traçabilité</span>
            <h3 class="card-title">📜 Journal d'Audit Système (Dernières Actions)</h3>
        </div>
        <span style="font-size: 0.85rem; color: var(--text-muted);">Réservé au rôle Développeur</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Action Réalisée</th>
                    <th>Adresse IP</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniersLogs as $log): ?>
                    <tr>
                        <td style="font-size: 0.8rem; color: var(--text-muted);">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td>
                            <strong><?= e($log['admin_nom'] ?? 'Système') ?></strong>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">(@<?= e($log['username'] ?? 'cli') ?>)</span>
                        </td>
                        <td><?= e($log['action']) ?></td>
                        <td><code><?= e($log['ip']) ?></code></td>
                        <td style="font-size: 0.8rem; color: var(--text-secondary);">
                            <?= e($log['details'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
