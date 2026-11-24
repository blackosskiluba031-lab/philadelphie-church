<?php
// entraide.php
// Plateforme d'entraide fraternelle, recherche d'emploi, soutien en anonymat — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Entraide & Besoins Fraternels';
$isHeroPage = true;
$errors = [];
$success = false;

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou jeton expiré.";
    } else {
        $typeBesoin  = $_POST['type_besoin'] ?? 'soutien_emotionnel';
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nom         = trim($_POST['nom_demandeur'] ?? '');
        $telephone   = trim($_POST['telephone'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $anonyme     = isset($_POST['anonyme']) ? 1 : 0;
        $visibleCom  = isset($_POST['visible_communaute']) ? 1 : 0;

        if (empty($titre)) {
            $errors[] = "Veuillez donner un titre à votre demande.";
        }
        if (empty($description)) {
            $errors[] = "Veuillez détailler votre situation ou votre besoin.";
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare("
                    INSERT INTO besoins_entraide 
                    (type_besoin, titre, description, nom_demandeur, telephone, email, anonyme, visible_communaute, statut, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', NOW())
                ");
                $stmt->execute([
                    $typeBesoin,
                    $titre,
                    $description,
                    $anonyme ? 'Anonyme' : ($nom ?: 'Membre'),
                    $anonyme ? null : ($telephone ?: null),
                    $anonyme ? null : ($email ?: null),
                    $anonyme,
                    $visibleCom
                ]);

                $success = true;
                set_flash('success', "Votre demande d'entraide a été enregistrée en toute confidentialité. L'équipe du diaconat prendra contact.");
            } catch (Exception $e) {
                $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            }
        }
    }
}

// Liste des besoins visibles par la communauté
$besoinsPublics = db()->query("
    SELECT * FROM besoins_entraide 
    WHERE visible_communaute = 1 AND statut IN ('en_attente', 'en_cours') 
    ORDER BY created_at DESC LIMIT 10
")->fetchAll();

include __DIR__ . '/templates/header.php';

echo render_church_hero(
    '🤝 Solidarité & Diaconat',
    'Entraide & Soutien Fraternel',
    'Emploi, soutien financier, secours moral : la communauté chrétienne est unie pour porter les fardeaux les uns des autres.',
    'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1920&q=80',
    [
        ['label' => 'Exprimer un besoin', 'href' => '#formulaire-besoin', 'class' => 'btn-church-white'],
        ['label' => 'Besoins de la paroisse', 'href' => '#besoins-communaute', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 960px; margin: 0 auto; padding: 3rem 1.5rem 5rem;">

    <?php if ($success): ?>
        <div class="glass-card" style="text-align: center; padding: 3rem 2rem; border-radius: var(--radius-xs); border-left: 4px solid #10B981; margin-bottom: 2.5rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🤝</div>
            <h2 style="color: #10B981; font-size: 1.8rem; margin-bottom: 0.5rem;">Demande bien transmise</h2>
            <p style="color: var(--text-muted); max-width: 580px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                Le département d'entraide et diaconat a été notifié. Votre situation sera examinée dans le respect absolu de votre vie privée.
            </p>
            <a href="<?= BASE_URL ?>/" class="btn-church-white">Retour à l'accueil</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 2rem;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Besoins partagés avec la communauté -->
    <?php if (!empty($besoinsPublics)): ?>
        <section id="besoins-communaute" style="margin-bottom: 4rem;">
            <div class="section-heading-wrapper">
                <span class="accent-dash"></span>
                <h2 class="section-title">Besoins Partagés avec l'Assemblée</h2>
                <p class="section-subtitle">Chaque opportunité de travail, don de matériel ou coup de main fait la différence.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($besoinsPublics as $b): ?>
                    <article class="glass-card" style="padding: 1.75rem; border-radius: var(--radius-xs); border-left: 3px solid var(--accent-amber);">
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--accent-amber); text-transform: uppercase;">
                            <?= strtoupper(e(str_replace('_', ' ', $b['type_besoin']))) ?>
                        </span>
                        <h3 style="font-size: 1.15rem; margin: 0.5rem 0; color: var(--text-main);"><?= e($b['titre']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.55; margin-bottom: 1rem;">
                            <?= e(truncate_text($b['description'], 130)) ?>
                        </p>
                        <div style="font-size: 0.8rem; color: var(--text-subtle); border-top: 1px solid var(--glass-border); padding-top: 0.75rem;">
                            Demandeur : <?= $b['anonyme'] ? 'Fidèle Anonyme' : e($b['nom_demandeur']) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Formulaire d'expression d'un besoin -->
    <div id="formulaire-besoin" class="glass-card" style="padding: 3rem 2.5rem; border-radius: var(--radius-xs);">
        <span class="accent-dash"></span>
        <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Exprimer un Besoin Fraternel</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
            Vous traversez une période difficile ? L'église est là pour vous soutenir moralement, matériellement et spirituellement.
        </p>

        <form method="POST" action="<?= BASE_URL ?>/entraide.php">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main);">Domaine du besoin *</label>
                <select name="type_besoin" class="form-control" style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                    <option value="emploi">💼 Emploi / Recherche de travail</option>
                    <option value="finance">💰 Soutien financier d'urgence</option>
                    <option value="soutien_emotionnel">🕊️ Soutien émotionnel & Écoute</option>
                    <option value="materiel">📦 Don ou prêt de matériel / Mobilier</option>
                    <option value="autre">✨ Autre besoin</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main);">Titre du besoin *</label>
                <input type="text" name="titre" class="form-control" required placeholder="Ex : Recherche un poste de comptable, aide déménagement..."
                       style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main);">Description détaillée *</label>
                <textarea name="description" class="form-control" rows="5" required placeholder="Expliquez votre situation, vos compétences ou vos difficultés..."
                          style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);"></textarea>
            </div>

            <div style="background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" name="anonyme" value="1" checked style="width: 18px; height: 18px; accent-color: var(--accent-amber);" onchange="document.getElementById('entraide-coords').style.display = this.checked ? 'none' : 'grid';">
                    <strong>Garder ma demande anonyme (recommandé)</strong>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-muted); font-size: 0.9rem;">
                    <input type="checkbox" name="visible_communaute" value="1" style="width: 18px; height: 18px; accent-color: var(--accent-amber);">
                    <span>Rendre le besoin visible sur le mur d'entraide communautaire (sans vos coordonnées personnelles)</span>
                </label>
            </div>

            <div id="entraide-coords" style="display: none; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="color: var(--text-main);">Votre Nom complet</label>
                    <input type="text" name="nom_demandeur" class="form-control" placeholder="Frère Marc..."
                           style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="color: var(--text-main);">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" placeholder="+33 6 12 34 56 78"
                           style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs);">
                </div>
            </div>

            <button type="submit" class="btn-church-white" style="width: 100%; padding: 14px; font-size: 1rem;">
                🤝 Soumettre ma demande d'entraide
            </button>
        </form>
    </div>

</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
