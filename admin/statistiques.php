<?php
// admin/statistiques.php
// Graphiques analytiques Chart.js adaptés au Mode Sombre & Mode Clair

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Statistiques & Graphiques';
$pageTitle = 'Statistiques Chart.js';

// 1. Données Graphique 1 : Présences aux 8 derniers cultes
$stmtPresences = db()->query("
    SELECT e.id, e.titre, DATE_FORMAT(e.date_debut, '%d/%m/%Y') as date_label,
           COALESCE(SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END), 0) as presents,
           COALESCE(SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END), 0) as absents,
           COALESCE(SUM(CASE WHEN p.statut = 'excuse' THEN 1 ELSE 0 END), 0) as excuses
    FROM evenements e
    LEFT JOIN presences p ON e.id = p.evenement_id
    GROUP BY e.id, e.titre, e.date_debut
    ORDER BY e.date_debut ASC
    LIMIT 8
");
$presenceEvents = $stmtPresences->fetchAll();

$labelsPresence = [];
$dataPresents   = [];
$dataAbsents    = [];
$dataExcuses    = [];

foreach ($presenceEvents as $pe) {
    $labelsPresence[] = $pe['date_label'] . ' - ' . truncate_text($pe['titre'], 16);
    $dataPresents[]   = (int)$pe['presents'];
    $dataAbsents[]    = (int)$pe['absents'];
    $dataExcuses[]    = (int)$pe['excuses'];
}

// 2. Données Graphique 2 : Répartition des fidèles par groupe
$stmtGroupes = db()->query("
    SELECT groupe, COUNT(*) as total 
    FROM fideles 
    WHERE statut = 'actif' 
    GROUP BY groupe 
    ORDER BY total DESC
");
$groupesData = $stmtGroupes->fetchAll();

$labelsGroupes = [];
$dataGroupes   = [];
$groupColors   = [
    'jeunes'  => '#D97706', // Or
    'hommes'  => '#3B82F6', // Bleu
    'femmes'  => '#EC4899', // Rose
    'chorale' => '#8B5CF6', // Violet
    'enfants' => '#10B981', // Vert
    'autre'   => '#64748B'  // Gris
];
$bgGroupes = [];

foreach ($groupesData as $g) {
    $labelsGroupes[] = ucfirst($g['groupe']);
    $dataGroupes[]   = (int)$g['total'];
    $bgGroupes[]     = $groupColors[$g['groupe']] ?? '#D97706';
}

// 3. Données Graphique 3 : Vues des vidéos et annonces
$videosTop = db()->query("
    SELECT titre, vues_count FROM videos WHERE statut = 'publie' ORDER BY vues_count DESC LIMIT 4
")->fetchAll();

$annoncesTop = db()->query("
    SELECT titre, vues_count FROM annonces WHERE statut = 'publie' ORDER BY vues_count DESC LIMIT 4
")->fetchAll();

$labelsMedia = [];
$dataMediaVues = [];

foreach ($videosTop as $v) {
    $labelsMedia[] = '🎥 ' . truncate_text($v['titre'], 20);
    $dataMediaVues[] = (int)$v['vues_count'];
}
foreach ($annoncesTop as $a) {
    $labelsMedia[] = '📢 ' . truncate_text($a['titre'], 20);
    $dataMediaVues[] = (int)$a['vues_count'];
}

// 4. Données Graphique 4 : Recettes mensuelles des dons
$stmtDonsMois = db()->query("
    SELECT DATE_FORMAT(date_don, '%m/%Y') as mois_label,
           DATE_FORMAT(date_don, '%Y-%m') as mois_tri,
           COALESCE(SUM(montant), 0) as total_montant
    FROM dons 
    WHERE statut = 'valide'
    GROUP BY mois_tri, mois_label
    ORDER BY mois_tri ASC
    LIMIT 6
");
$donsMois = $stmtDonsMois->fetchAll();

$labelsDons = [];
$dataDons   = [];

foreach ($donsMois as $dm) {
    $labelsDons[] = $dm['mois_label'];
    $dataDons[]   = (float)$dm['total_montant'];
}

include __DIR__ . '/../templates/admin_header.php';
?>

<!-- En-tête -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.6rem; margin-bottom: 4px;">Indicateurs & Graphiques de l'Église</h2>
        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
            Analyses visuelles interactives générées avec Chart.js (optimisées pour les modes sombre et clair)
        </p>
    </div>
    <span class="badge badge-gold" style="font-size: 0.85rem; padding: 6px 14px;">
        ✨ Thème Dynamique Actif
    </span>
</div>

<!-- Grille 2x2 des Graphiques Chart.js -->
<div class="grid grid-cols-2" style="gap: 2rem; margin-bottom: 2rem;">

    <!-- Graphique 1 : Présences aux Cultes -->
    <div class="card" style="padding: 1.5rem;">
        <div class="card-header">
            <div>
                <span class="badge badge-gold" style="margin-bottom: 4px;">Assiduité Paroissiale</span>
                <h3 class="card-title">Évolution des Présences aux Cultes</h3>
            </div>
            <span style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 600;">Courbe Lissante</span>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chartPresences"></canvas>
        </div>
    </div>

    <!-- Graphique 2 : Répartition des Fidèles -->
    <div class="card" style="padding: 1.5rem;">
        <div class="card-header">
            <div>
                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #60A5FA; margin-bottom: 4px;">Démographie</span>
                <h3 class="card-title">Répartition des Fidèles par Groupe</h3>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Membres Actifs</span>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chartGroupes"></canvas>
        </div>
    </div>

    <!-- Graphique 3 : Audiences Vidéos & Annonces -->
    <div class="card" style="padding: 1.5rem;">
        <div class="card-header">
            <div>
                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; margin-bottom: 4px;">Impact & Diffusion</span>
                <h3 class="card-title">Consultations des Vidéos & Annonces</h3>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Vues Enregistrées</span>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chartMedia"></canvas>
        </div>
    </div>

    <!-- Graphique 4 : Évolution des Dons & Dîmes -->
    <div class="card" style="padding: 1.5rem;">
        <div class="card-header">
            <div>
                <span class="badge badge-gold" style="margin-bottom: 4px;">Trésorerie</span>
                <h3 class="card-title">Collectes Mensuelles des Dons & Dîmes</h3>
            </div>
            <span style="font-size: 0.8rem; color: var(--color-success); font-weight: 600;">Montants en €</span>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chartDons"></canvas>
        </div>
    </div>

</div>

<!-- CDN Chart.js v4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Vérification présence Chart.js
    if (typeof Chart === 'undefined') {
        console.warn("Chart.js n'a pas pu être chargé depuis le CDN.");
        return;
    }

    // Récupération du mode actuel
    function isDarkTheme() {
        return document.documentElement.getAttribute('data-theme') !== 'light';
    }

    function getChartThemeColors() {
        const dark = isDarkTheme();
        return {
            textColor: dark ? '#94A3B8' : '#475569',
            gridColor: dark ? 'rgba(255, 255, 255, 0.07)' : 'rgba(0, 0, 0, 0.06)',
            tooltipBg: dark ? '#0F172A' : '#FFFFFF',
            tooltipText: dark ? '#F8FAFC' : '#0F172A',
            tooltipBorder: '#D97706',
            goldPrimary: '#D97706',
            goldLight: '#F59E0B'
        };
    }

    let colors = getChartThemeColors();

    // 1. Initialisation Graphique Présences
    const ctxPresences = document.getElementById('chartPresences').getContext('2d');
    
    // Gradient or pour la courbe
    const gradientGold = ctxPresences.createLinearGradient(0, 0, 0, 300);
    gradientGold.addColorStop(0, 'rgba(217, 119, 6, 0.35)');
    gradientGold.addColorStop(1, 'rgba(217, 119, 6, 0.01)');

    const chartPresences = new Chart(ctxPresences, {
        type: 'line',
        data: {
            labels: <?= json_encode($labelsPresence, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Présents au culte',
                data: <?= json_encode($dataPresents) ?>,
                borderColor: '#D97706',
                backgroundColor: gradientGold,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#F59E0B',
                pointBorderColor: '#0F172A',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: colors.textColor, font: { family: "'Plus Jakarta Sans', sans-serif" } } },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.goldPrimary,
                    bodyColor: colors.tooltipText,
                    borderColor: colors.tooltipBorder,
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    ticks: { color: colors.textColor, font: { size: 11 } },
                    grid: { color: colors.gridColor }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: colors.textColor, precision: 0 },
                    grid: { color: colors.gridColor }
                }
            }
        }
    });

    // 2. Initialisation Graphique Groupes (Doughnut)
    const ctxGroupes = document.getElementById('chartGroupes').getContext('2d');
    const chartGroupes = new Chart(ctxGroupes, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labelsGroupes, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($dataGroupes) ?>,
                backgroundColor: <?= json_encode($bgGroupes) ?>,
                borderColor: isDarkTheme() ? '#151F32' : '#FFFFFF',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: colors.textColor, font: { family: "'Plus Jakarta Sans', sans-serif" }, boxWidth: 14 }
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.goldPrimary,
                    bodyColor: colors.tooltipText,
                    borderColor: colors.tooltipBorder,
                    borderWidth: 1
                }
            },
            cutout: '65%'
        }
    });

    // 3. Initialisation Graphique Vues Médias (Barres horizontales)
    const ctxMedia = document.getElementById('chartMedia').getContext('2d');
    const chartMedia = new Chart(ctxMedia, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsMedia, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Nombre de consultations / vues',
                data: <?= json_encode($dataMediaVues) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: '#3B82F6',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.goldPrimary,
                    bodyColor: colors.tooltipText,
                    borderColor: colors.tooltipBorder,
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { color: colors.textColor, precision: 0 },
                    grid: { color: colors.gridColor }
                },
                y: {
                    ticks: { color: colors.textColor, font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });

    // 4. Initialisation Graphique Dons
    const ctxDons = document.getElementById('chartDons').getContext('2d');
    const chartDons = new Chart(ctxDons, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsDons, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Total collecté (€)',
                data: <?= json_encode($dataDons) ?>,
                backgroundColor: 'rgba(217, 119, 6, 0.75)',
                borderColor: '#D97706',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: colors.textColor } },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: colors.goldPrimary,
                    bodyColor: colors.tooltipText,
                    borderColor: colors.tooltipBorder,
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y.toLocaleString('fr-FR') + ' €';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: colors.textColor },
                    grid: { color: colors.gridColor }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) { return value + ' €'; }
                    },
                    grid: { color: colors.gridColor }
                }
            }
        }
    });

    // ÉCOUTEUR D'ÉVÉNEMENT : Adaptation instantanée des graphiques lors du clic sur le commutateur de thème
    window.addEventListener('themeChanged', (e) => {
        const newColors = getChartThemeColors();
        const charts = [chartPresences, chartGroupes, chartMedia, chartDons];

        charts.forEach(c => {
            if (!c) return;

            // Mise à jour légendes & infobulles
            if (c.options.plugins.legend) {
                c.options.plugins.legend.labels.color = newColors.textColor;
            }
            if (c.options.plugins.tooltip) {
                c.options.plugins.tooltip.backgroundColor = newColors.tooltipBg;
                c.options.plugins.tooltip.bodyColor = newColors.tooltipText;
            }

            // Mise à jour des échelles X & Y
            if (c.options.scales) {
                if (c.options.scales.x && c.options.scales.x.ticks) {
                    c.options.scales.x.ticks.color = newColors.textColor;
                    if (c.options.scales.x.grid) c.options.scales.x.grid.color = newColors.gridColor;
                }
                if (c.options.scales.y && c.options.scales.y.ticks) {
                    c.options.scales.y.ticks.color = newColors.textColor;
                    if (c.options.scales.y.grid) c.options.scales.y.grid.color = newColors.gridColor;
                }
            }

            c.update();
        });
    });
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
