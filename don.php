<?php
// don.php
// Soutien financier, dîmes, offrandes et dons pour les œuvres de l'église & soutien de l'application — Style Church X

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_helpers.php';

$pageTitle = 'Soutenir l’Œuvre & l’Application | Dons & Dîmes';
$isHeroPage = true;
$church = get_church_info();
$deviseDefaut = get_param('devise_defaut', 'FC');
$numeroPaiementSupport = get_param('paiement_support_app', '0990945558');
$numeroContactSupport = get_param('contact_support_app', '0901247908');
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Requête invalide ou session expirée.";
    } else {
        $montant = floatval(str_replace(',', '.', $_POST['montant'] ?? '0'));
        $devise = in_array($_POST['devise'] ?? '', ['FC', 'USD']) ? $_POST['devise'] : $deviseDefaut;
        $typeDon = $_POST['type_don'] ?? 'offrande';
        $methode = $_POST['methode'] ?? 'mobile_money';
        $nom = trim($_POST['nom_donateur'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $remarque = trim($_POST['remarque'] ?? '');

        if ($montant <= 0) {
            $errors[] = "Veuillez indiquer un montant supérieur à 0.";
        }

        if (empty($errors)) {
            try {
                $ref = 'DON-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = db()->prepare("
                    INSERT INTO dons 
                    (nom_donateur, telephone, type_don, montant, devise, methode, reference_transaction, statut, remarque, date_don) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, NOW())
                ");
                $stmt->execute([
                    $nom ?: 'Donateur Anonyme',
                    $telephone ?: null,
                    $typeDon,
                    $montant,
                    $devise,
                    $methode,
                    $ref,
                    $remarque ?: null
                ]);

                set_flash('success', "Votre intention de don de " . format_currency($montant, $devise) . " a été enregistrée avec la référence $ref. Que Dieu vous bénisse !");
                header('Location: ' . BASE_URL . '/don.php?merci=1&ref=' . urlencode($ref) . '&devise=' . urlencode($devise) . '&montant=' . urlencode((string) $montant));
                exit;
            } catch (Exception $e) {
                error_log('Erreur enregistrement don: ' . $e->getMessage());
                $errors[] = "Une erreur est survenue lors de l'enregistrement de votre don.";
            }
        }
    }
}

include __DIR__ . '/templates/header.php';

$heroDonBg = get_param('hero_bg_don', 'https://images.unsplash.com/photo-1519491050282-cf00c82424b4?auto=format&fit=crop&w=1920&q=80');

echo render_church_hero(
    '💛 Générosité & Bénédiction',
    'Soutenir l’Œuvre de Dieu',
    '« Que chacun donne comme il l’a résolu en son cœur, sans tristesse ni contrainte; car Dieu aime celui qui donne avec joie. » — 2 Corinthiens 9:7',
    $heroDonBg,
    [
        ['label' => 'Déclarer une offrande', 'href' => '#formulaire-don', 'class' => 'btn-church-white'],
        ['label' => 'Soutien Application & Média', 'href' => '#soutien-app', 'class' => 'btn-church-outline']
    ]
);
?>

<div class="container" style="max-width: 960px; margin: 0 auto; padding: 2.5rem 1.25rem 5rem;">

    <?php if (isset($_GET['merci'])): ?>
        <div class="glass-card"
            style="text-align: center; padding: 3rem 1.5rem; border-radius: var(--radius-xs); border-left: 4px solid #10B981; margin-bottom: 2.5rem;">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">💛</div>
            <h2 style="color: #10B981; font-size: 1.8rem; margin-bottom: 0.5rem;">Merci pour votre offrande !</h2>
            <p style="color: var(--text-muted); max-width: 580px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                Votre contribution participe activement au rayonnement de l'Évangile, aux charges du temple et aux actions
                de solidarité.
                <?php if (!empty($_GET['ref'])): ?>
                    <br><strong
                        style="color: var(--text-main); font-size: 1.1rem; display: inline-block; margin-top: 0.5rem; padding: 4px 12px; background: rgba(16,185,129,0.12); border-radius: 4px;">Référence
                        : <?= e($_GET['ref']) ?></strong>
                <?php endif; ?>
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/" class="btn-church-white">Retour à l'accueil</a>
                <a href="https://wa.me/243901247908?text=<?= urlencode("Bonjour, j'ai enregistré mon offrande sous la référence " . ($_GET['ref'] ?? '')) ?>"
                    target="_blank" class="btn-church-outline" style="border-color: #25D366; color: #25D366;">
                    💬 Confirmer sur WhatsApp
                </a>
            </div>
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

    <!-- ═══════════════════════════════════════════════════════════════
         BLOC MAJEUR : APPEL DE FONDS POUR LA MAINTENANCE DE L'APPLICATION
         ═══════════════════════════════════════════════════════════════ -->
    <div id="soutien-app" class="glass-card"
        style="margin-bottom: 2.5rem; padding: 2rem; border-radius: var(--radius-xs); border: 2px solid var(--accent-amber); background: linear-gradient(135deg, rgba(217,119,6,0.12) 0%, rgba(14,14,14,0.85) 100%);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem;">
            <span style="font-size: 1.75rem;">🚀</span>
            <div>
                <span class="accent-dash" style="margin-bottom: 4px;"></span>
                <h3 style="font-size: 1.35rem; margin: 0; color: #FFF; font-weight: 700;">
                    Fonds de Soutien & Maintenance de l’Application
                </h3>
            </div>
        </div>

        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.65; margin-bottom: 1.25rem;">
            Chers frères, sœurs et amis de <strong>Philadelphie Church (CFCG GARENGANZE)</strong>, le fonctionnement
            permanent de notre plateforme chrétienne (hébergement haute disponibilité, nom de domaine officiel,
            diffusion multimédia des cultes et travail d'ingénierie des développeurs) requiert des coûts de maintenance
            réguliers.
        </p>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
            <!-- Box Paiement Mobile Money -->
            <div
                style="background: rgba(0,0,0,0.4); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid rgba(217,119,6,0.35);">
                <div
                    style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-amber); font-weight: 700; margin-bottom: 4px;">
                    💳 Envoi de votre soutien (Mobile Money RDC)
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: #FFF; letter-spacing: 0.03em; margin: 6px 0;">
                    <?= e($numeroPaiementSupport) ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                    Accepté : <strong>M-Pesa (Vodacom)</strong>, <strong>Airtel Money</strong>, <strong>Orange
                        Money</strong>.<br>
                    <span style="color: #FCD34D;">Devises acceptées : FC (Franc Congolais) & USD ($)</span>
                </div>
                <div style="margin-top: 10px;">
                    <button type="button" class="btn-church-outline" style="padding: 5px 12px; font-size: 0.78rem;"
                        onclick="navigator.clipboard.writeText('<?= e($numeroPaiementSupport) ?>'); alert('Numéro <?= e($numeroPaiementSupport) ?> copié !');">
                        📋 Copier le numéro
                    </button>
                </div>
            </div>

            <!-- Box Contact & Infos Développeur -->
            <div
                style="background: rgba(0,0,0,0.4); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                <div
                    style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-amber); font-weight: 700; margin-bottom: 4px;">
                    📞 Renseignements & Ligne Développeur
                </div>
                <div style="font-size: 1.35rem; font-weight: 800; color: #FFF; letter-spacing: 0.03em; margin: 6px 0;">
                    <?= e($numeroContactSupport) ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                    Pour toute information complémentaire, proposition de partenariat ou suivi des versements.
                </div>
                <div style="margin-top: 10px;">
                    <a href="tel:<?= e($numeroContactSupport) ?>" class="btn-church-outline"
                        style="padding: 5px 12px; font-size: 0.78rem; text-decoration: none; display: inline-block;">
                        📞 Appeler
                    </a>
                </div>
            </div>
        </div>

        <!-- Bouton WhatsApp direct -->
        <div
            style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.08);">
            <div style="font-size: 0.875rem; color: var(--text-muted);">
                💬 Vous préférez échanger directement sur WhatsApp avec le responsable technique ?
            </div>
            <a href="https://wa.me/243901247908?text=<?= urlencode("Bonjour, je souhaite soutenir financièrement la maintenance et le déploiement de l'application Philadelphie Church.") ?>"
                target="_blank" class="btn-church-white"
                style="background: #25D366; color: #FFF; border-color: #25D366; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                <span>🟢 Écrire sur WhatsApp</span>
                <span style="font-size: 0.85rem; opacity: 0.9;">(+243 901 247 908)</span>
            </a>
        </div>
    </div>

    <!-- Coordonnées bancaires & de culte officielles -->
    <div id="coordonnees-bancaires" class="glass-card"
        style="margin-bottom: 2.5rem; padding: 2rem; border-radius: var(--radius-xs); border-left: 3px solid var(--accent-amber);">
        <span class="accent-dash"></span>
        <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--text-main);">
            Canaux de Collecte pour les Offrandes & Dîmes
        </h3>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
            <?= e($church['nom'] ?? 'Philadelphie Church') ?> • CFCG GARENGANZE
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
            <div
                style="background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-amber);">📱 Mobile Money (Dons
                    du Culte)</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 0.75rem;">
                    <strong>Numéro Paroissial :</strong> <?= e($church['telephone'] ?? '0990945558') ?><br>
                    <strong>Devises acceptées :</strong> FC et USD ($)<br>
                    <strong>Motif conseillé :</strong> [Votre Nom] - Dîme / Offrande
                </p>
                <button type="button" class="btn-church-outline" style="padding: 4px 10px; font-size: 0.78rem;"
                    onclick="navigator.clipboard.writeText('<?= e($church['telephone'] ?? '0990945558') ?>'); alert('Numéro copié !');">
                    📋 Copier le numéro
                </button>
            </div>

            <div
                style="background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: var(--radius-xs); border: 1px solid var(--glass-border);">
                <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-amber);">🏛️ En Présentiel
                    lors des Cultes</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                    <strong>Adresse :</strong>
                    <?= e($church['adresse'] ?? '3ème avenue prolongée, Av : de la révolution N.160 (RÉF : école pintu)') ?><br>
                    <strong>Enveloppes :</strong> Disponibles à l'accueil pour les dîmes et offrandes spéciales.
                </p>
            </div>
        </div>
    </div>

    <!-- Formulaire de don -->
    <div id="formulaire-don" class="glass-card" style="padding: 2.5rem 2rem; border-radius: var(--radius-xs);">
        <span class="accent-dash"></span>
        <h2 style="font-size: 1.6rem; margin-bottom: 0.5rem;">Déclarer ou Préparer une Offrande</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.75rem;">
            Enregistrez votre engagement financier pour que le secrétariat et le pasteur puissent en assurer le suivi.
        </p>

        <form method="POST" action="<?= BASE_URL ?>/don.php">
            <?= csrf_field() ?>

            <!-- Choix de la Devise -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main); font-weight: 600;">Devise de contribution
                    *</label>
                <div style="display: flex; gap: 12px; margin-top: 6px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-weight: 600;">
                        <input type="radio" name="devise" value="FC" checked onchange="updatePresets('FC')"
                            style="accent-color: var(--accent-amber);">
                        Franc Congolais (FC)
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-weight: 600;">
                        <input type="radio" name="devise" value="USD" onchange="updatePresets('USD')"
                            style="accent-color: var(--accent-amber);">
                        Dollar Américain ($ USD)
                    </label>
                </div>
            </div>

            <!-- Type d'Offrande -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="color: var(--text-main); font-weight: 600;">Nature de la contribution
                    *</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                    <label
                        style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-size: 0.9rem;">
                        <input type="radio" name="type_don" value="dime" checked
                            style="accent-color: var(--accent-amber);"> Dîme
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-size: 0.9rem;">
                        <input type="radio" name="type_don" value="offrande" style="accent-color: var(--accent-amber);">
                        Offrande Dominicale
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-size: 0.9rem;">
                        <input type="radio" name="type_don" value="construction"
                            style="accent-color: var(--accent-amber);"> Travaux & Temple
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); cursor: pointer; color: var(--text-main); font-size: 0.9rem;">
                        <input type="radio" name="type_don" value="action_sociale"
                            style="accent-color: var(--accent-amber);"> Action Sociale / Entraide
                    </label>
                    <label
                        style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: rgba(217,119,6,0.15); border: 1px solid var(--accent-amber); border-radius: var(--radius-xs); cursor: pointer; color: #FCD34D; font-size: 0.9rem;">
                        <input type="radio" name="type_don" value="maintenance_app"
                            style="accent-color: var(--accent-amber);"> 🚀 Soutien Application / Média
                    </label>
                </div>
            </div>

            <!-- Montant et Boutons rapides -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="montant" class="form-label" style="color: var(--text-main); font-weight: 600;">Montant
                    *</label>

                <!-- Boutons présélection FC -->
                <div id="presets-fc" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(5000)">5 000 FC</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(10000)">10 000 FC</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(25000)">25 000 FC</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(50000)">50 000 FC</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(100000)">100 000 FC</button>
                </div>

                <!-- Boutons présélection USD (cachés par défaut) -->
                <div id="presets-usd" style="display: none; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(5)">5 $</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(10)">10 $</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(20)">20 $</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(50)">50 $</button>
                    <button type="button" class="btn-church-outline" style="padding: 6px 12px; font-size: 0.825rem;"
                        onclick="setMontant(100)">100 $</button>
                </div>

                <div style="position: relative;">
                    <input type="number" id="montant" name="montant" step="any" min="1" class="form-control"
                        placeholder="Entrez le montant..." required value="<?= e($_POST['montant'] ?? '') ?>"
                        style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); padding: 12px 14px; font-size: 1.1rem; color: var(--text-main); font-weight: 600;">
                </div>
            </div>

            <!-- Moyen de Paiement -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="methode" class="form-label" style="color: var(--text-main); font-weight: 600;">Moyen de
                    Règlement *</label>
                <select id="methode" name="methode" class="form-control" required
                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); padding: 10px 14px; color: var(--text-main);">
                    <option value="mobile_money">📱 Mobile Money (M-Pesa / Airtel / Orange)</option>
                    <option value="especes">💵 Espèces au culte (Enveloppe d'offrande)</option>
                    <option value="virement">🏦 Virement bancaire (Rawbank / EquityBCDC)</option>
                    <option value="autre">🤝 Autre modalité</option>
                </select>
            </div>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="nom_donateur" class="form-label" style="color: var(--text-main);">Votre Nom complet
                        <small style="color: var(--text-muted);">(Optionnel)</small></label>
                    <input type="text" id="nom_donateur" name="nom_donateur" class="form-control"
                        placeholder="Ex: Frère / Sœur..." value="<?= e($_POST['nom_donateur'] ?? '') ?>"
                        style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); padding: 10px 14px; color: var(--text-main);">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="telephone" class="form-label" style="color: var(--text-main);">Votre Téléphone <small
                            style="color: var(--text-muted);">(Optionnel)</small></label>
                    <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="099..."
                        value="<?= e($_POST['telephone'] ?? '') ?>"
                        style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); padding: 10px 14px; color: var(--text-main);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="remarque" class="form-label" style="color: var(--text-main);">Remarque ou intention
                    particulière <small style="color: var(--text-muted);">(Optionnel)</small></label>
                <input type="text" id="remarque" name="remarque" class="form-control"
                    placeholder="Ex : Action de grâce, dîme de reconnaissance..."
                    value="<?= e($_POST['remarque'] ?? '') ?>"
                    style="background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xs); padding: 10px 14px; color: var(--text-main);">
            </div>

            <button type="submit" class="btn-church-white"
                style="width: 100%; padding: 14px; font-size: 1.05rem; font-weight: 700; cursor: pointer;">
                💛 Confirmer mon offrande
            </button>
        </form>
    </div>

</div>

<script>
    function setMontant(val) {
        document.getElementById('montant').value = val;
    }

    function updatePresets(devise) {
        var pFc = document.getElementById('presets-fc');
        var pUsd = document.getElementById('presets-usd');
        if (devise === 'USD') {
            pFc.style.display = 'none';
            pUsd.style.display = 'flex';
        } else {
            pFc.style.display = 'flex';
            pUsd.style.display = 'none';
        }
    }
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>