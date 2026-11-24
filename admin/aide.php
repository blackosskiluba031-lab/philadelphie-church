<?php
// admin/aide.php
// Guide complet d'accès et des rôles pour le Pasteur et l'équipe

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

require_login();

$adminHeaderTitle = 'Guide & Rôles';
$pageTitle        = 'Guide des Accès';
$church = get_church_info();

include __DIR__ . '/../templates/admin_header.php';
include __DIR__ . '/../templates/admin_sidebar.php';
?>
<main class="admin-main">
    <div class="admin-content" style="max-width: 860px;">

        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">❓ Guide des Rôles & Accès</h1>
            <p style="color: var(--text-secondary);">Comment le Pasteur, l'Équipe Média et les autres membres accèdent à l'administration.</p>
        </div>

        <!-- Comment se connecter -->
        <div class="card" style="padding: 2rem; margin-bottom: 1.5rem; border-left: 4px solid var(--gold-primary);">
            <h2 style="font-size: 1.3rem; margin-bottom: 1rem; color: var(--gold-primary);">🔑 Comment accéder à l'espace administration ?</h2>
            <ol style="color: var(--text-secondary); line-height: 1.9; padding-left: 1.5rem;">
                <li>Ouvrez un navigateur et allez sur : <strong style="color: var(--text-primary);"><?= e(ADMIN_URL) ?>/login.php</strong></li>
                <li>Entrez votre <strong>nom d'utilisateur</strong> (ou votre adresse e-mail) et votre <strong>mot de passe</strong>.</li>
                <li>Cliquez sur <em>« Se connecter »</em>.</li>
                <li>Vous arrivez automatiquement sur votre tableau de bord selon votre rôle.</li>
            </ol>
            <div style="background: rgba(217,119,6,0.09); border-radius: var(--radius-md); padding: 1rem; margin-top: 1rem;">
                <strong style="color: var(--gold-primary);">💡 Conseil :</strong> Enregistrez l'URL 
                <code style="background: var(--bg-surface); padding: 2px 6px; border-radius: 4px;"><?= e(ADMIN_URL) ?>/login.php</code> 
                dans vos favoris pour y accéder rapidement depuis votre téléphone ou ordinateur.
            </div>
        </div>

        <!-- Tableau des rôles -->
        <div class="card" style="padding: 2rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 1.5rem;">👑 Les 4 Rôles du Système</h2>

            <div style="display: flex; flex-direction: column; gap: 1rem;">

                <!-- Responsable = Pasteur -->
                <div style="border-left: 4px solid #D97706; padding: 1.25rem; background: rgba(217,119,6,0.07); border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">✝️</span>
                        <div>
                            <strong style="color: var(--gold-primary); font-size: 1.05rem;">RESPONSABLE</strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">— Rôle du Pasteur</span>
                        </div>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                        Le Pasteur a accès à <strong>tout ce que fait l'Équipe Média</strong>, plus :
                    </p>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>📬 <strong>Boîte du Pasteur</strong> — Lire les messages privés envoyés directement par les fidèles</li>
                        <li>🙏 Demandes de prière (consultation et réponse)</li>
                        <li>💰 Suivi des dons et dîmes</li>
                        <li>📢 Annonces, Vidéos, Notifications Push</li>
                        <li>🎤 Publier sur le Mur Fraternel (exhortations, prestations, versets)</li>
                        <li>📅 Gestion des événements et des présences</li>
                        <li>👥 Consultation des fidèles et départements</li>
                    </ul>
                </div>

                <!-- Équipe Média -->
                <div style="border-left: 4px solid #A78BFA; padding: 1.25rem; background: rgba(139,92,246,0.07); border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">🎬</span>
                        <div>
                            <strong style="color: #A78BFA; font-size: 1.05rem;">MÉDIA</strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">— Équipe Média & Communication</span>
                        </div>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                        <strong>Monopole exclusif</strong> sur la publication de contenus médias :
                    </p>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>🎤 Publier sur le Mur : exhortations, prestations chorales, moments forts (vidéos 1-2 min)</li>
                        <li>📢 Rédiger et publier les <strong>Annonces paroissiales</strong></li>
                        <li>🎥 Mettre en ligne les <strong>vidéos et prédications</strong> (YouTube ou upload)</li>
                        <li>🔔 Envoyer les <strong>Notifications Push</strong> aux fidèles abonnés</li>
                        <li>📅 Gestion des événements</li>
                    </ul>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.5rem; font-style: italic;">
                        ⚠️ Le public (fidèles non-admins) ne peut PAS publier de liens, vidéos ni de prestations.
                    </p>
                </div>

                <!-- Secrétaire -->
                <div style="border-left: 4px solid #60A5FA; padding: 1.25rem; background: rgba(59,130,246,0.07); border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">📋</span>
                        <div>
                            <strong style="color: #60A5FA; font-size: 1.05rem;">SECRÉTAIRE</strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">— Gestion paroissiale</span>
                        </div>
                    </div>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>👥 Gestion des fidèles et membres</li>
                        <li>✍️ Pointage des présences aux cultes</li>
                        <li>🏛️ Gestion des départements</li>
                        <li>📅 Gestion des événements</li>
                        <li>🛡️ Modération du Mur Fraternel</li>
                    </ul>
                </div>

                <!-- Développeur -->
                <div style="border-left: 4px solid #10B981; padding: 1.25rem; background: rgba(16,185,129,0.07); border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">⚙️</span>
                        <div>
                            <strong style="color: #10B981; font-size: 1.05rem;">DÉVELOPPEUR</strong>
                            <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">— Accès total système</span>
                        </div>
                    </div>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>Accès à tout ce qui précède +</li>
                        <li>⚙️ Paramètres généraux de l'application</li>
                        <li>🛡️ Gestion de tous les comptes administrateurs</li>
                        <li>📜 Journal d'audit complet</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Ce que le public peut faire -->
        <div class="card" style="padding: 2rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 1rem;">🌍 Ce que les fidèles (public) peuvent faire</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <h4 style="color: #10B981; margin-bottom: 0.5rem;">✅ Autorisé</h4>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>Publier des témoignages (texte)</li>
                        <li>Publier des partages fraternels (texte)</li>
                        <li>Ajouter une référence biblique</li>
                        <li>Commenter et réagir (Amen)</li>
                        <li>Écrire au Pasteur en privé</li>
                        <li>Soumettre une demande de prière</li>
                        <li>Déclarer un besoin d'entraide (anonyme)</li>
                        <li>Faire un don en ligne</li>
                    </ul>
                </div>
                <div>
                    <h4 style="color: #EF4444; margin-bottom: 0.5rem;">🚫 Réservé à l'Équipe Média</h4>
                    <ul style="color: var(--text-secondary); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                        <li>Publier des liens YouTube ou URLs</li>
                        <li>Publier des vidéos de prestation chorale</li>
                        <li>Publier des prédications / enseignements</li>
                        <li>Créer des annonces officielles</li>
                        <li>Envoyer des notifications push</li>
                        <li>Publier des exhortations officielles</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- FAQ rapide -->
        <div class="card" style="padding: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 1rem;">❓ Questions Fréquentes</h2>
            <details style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
                <summary style="cursor: pointer; font-weight: 600; color: var(--gold-primary);">Le Pasteur a-t-il un espace personnel séparé ?</summary>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem; padding-left: 1rem;">
                    Oui ! La <strong>Boîte du Pasteur</strong> (menu « 📬 Boîte du Pasteur ») est l'espace privé où arrivent les messages envoyés via la page « Au Pasteur » du site. 
                    Seuls les rôles <code>responsable</code> et <code>developpeur</code> peuvent y accéder.
                </p>
            </details>
            <details style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
                <summary style="cursor: pointer; font-weight: 600; color: var(--gold-primary);">Comment créer un compte pour l'Équipe Média ?</summary>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem; padding-left: 1rem;">
                    Allez dans <strong>Comptes Administrateurs</strong> (accessible au développeur uniquement), cliquez « Nouveau compte », 
                    renseignez les informations et choisissez le rôle <strong>Équipe Média</strong>. 
                    La personne pourra alors se connecter et accéder à l'espace publication.
                </p>
            </details>
            <details style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
                <summary style="cursor: pointer; font-weight: 600; color: var(--gold-primary);">Les prestations chorales nécessitent-elles toujours un lien YouTube ?</summary>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem; padding-left: 1rem;">
                    Non ! Dans l'espace « 🎤 Publier sur le Mur », l'Équipe Média peut uploader directement une <strong>vidéo courte (moment fort)</strong> 
                    de 1 à 2 minutes (MP4, WebM, 80 Mo max) sans avoir besoin d'un lien YouTube. 
                    La vidéo sera lue directement dans le navigateur des fidèles.
                </p>
            </details>
            <details>
                <summary style="cursor: pointer; font-weight: 600; color: var(--gold-primary);">J'ai oublié mon mot de passe, que faire ?</summary>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem; padding-left: 1rem;">
                    Contactez votre <strong>développeur ou administrateur système</strong> pour réinitialiser votre mot de passe via le panneau 
                    « Comptes Administrateurs » ou via le script <code>scripts/creer_developpeur.php</code>.
                </p>
            </details>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
