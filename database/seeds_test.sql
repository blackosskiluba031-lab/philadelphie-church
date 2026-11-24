-- ====================================================================
-- SEEDS DE TEST - eglise_app
-- Données réalistes pour tester l'ensemble des modules
-- ====================================================================

USE `eglise_app`;

-- 1. Configuration de l'église (Table eglise)
INSERT INTO `eglise` (`id`, `nom`, `slogan`, `adresse`, `telephone`, `email`, `logo`, `a_propos`, `facebook_url`, `youtube_url`, `tiktok_url`, `whatsapp`)
VALUES (
  1,
  'Église de la Grâce et de la Vérité',
  'Une communauté vivante, ancrée dans la Parole et unie par l\'Amour',
  '12 Boulevard de l\'Alliance, 75011 Paris',
  '+33 1 42 00 12 34',
  'contact@eglisedelagrace.fr',
  'uploads/eglise/logo.png',
  'Bienvenue à l\'Église de la Grâce. Notre mission est d\'annoncer la Bonne Nouvelle de Jésus-Christ, d\'édifier les croyants et d\'impacter positivement notre société par des actions d\'amour et de compassion.',
  'https://facebook.com/eglisedelagrace',
  'https://youtube.com/@eglisedelagrace',
  'https://tiktok.com/@eglisedelagrace',
  '+33612345678'
) ON DUPLICATE KEY UPDATE `nom` = VALUES(`nom`);

-- 2. Départements
INSERT INTO `departements` (`id`, `nom`, `description`, `icone`, `couleur`, `responsable_nom`, `actif`) VALUES
(1, 'Louange & Chorale', 'Ministère de louange, chantres et musiciens pour l\'édification spirituelle.', 'music', '#D97706', 'David Koffi', 1),
(2, 'Accueil & Protocole', 'Accueil des fidèles et des nouveaux arrivants, gestion du culte et logistique.', 'users', '#2563EB', 'Sarah Martin', 1),
(3, 'Intercession & Prière', 'Sentinelles de prière, accompagnement spirituel et veillées d\'intercession.', 'heart-handshake', '#7C3AED', 'Pasteur Samuel', 1),
(4, 'Média & Communication', 'Diffusion en direct, sonorisation, gestion des réseaux sociaux et de la PWA.', 'video', '#059669', 'Alexandre Dubois', 1),
(5, 'Jeunesse Dynamique', 'Encadrement spirituel, partage et activités fraternelles pour les 15-30 ans.', 'sparkles', '#DC2626', 'Grace Mendy', 1),
(6, 'École du Dimanche (Écodim)', 'Enseignement biblique adapté et éveil spirituel pour les enfants.', 'smile', '#EA580C', 'Esther Nzuzi', 1)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- 3. Fidèles
INSERT INTO `fideles` (`id`, `nom`, `prenom`, `telephone`, `email`, `quartier`, `groupe`, `sexe`, `date_naissance`, `recoit_notifications`, `statut`) VALUES
(1, 'Martin', 'Jean-Pierre', '+33601020304', 'jp.martin@email.fr', 'Centre-Ville', 'hommes', 'M', '1982-04-12', 1, 'actif'),
(2, 'Kouassi', 'Marie-Claire', '+33605060708', 'mc.kouassi@email.fr', 'Belleville', 'femmes', 'F', '1988-09-23', 1, 'actif'),
(3, 'Mendy', 'Jonathan', '+33609101112', 'jonathan.mendy@email.fr', 'République', 'jeunes', 'M', '2001-11-05', 1, 'actif'),
(4, 'Dubois', 'Sophie', '+33613141516', 'sophie.dubois@email.fr', 'Nation', 'chorale', 'F', '1995-03-18', 1, 'actif'),
(5, 'Nzuzi', 'Pauline', '+33617181920', 'pauline.nzuzi@email.fr', 'Bastille', 'femmes', 'F', '1975-07-30', 1, 'actif'),
(6, 'Traore', 'Ibrahim', '+33621222324', 'ibrahim.traore@email.fr', 'Vincennes', 'hommes', 'M', '1985-12-14', 1, 'actif'),
(7, 'Leroy', 'Camille', '+33625262728', 'camille.leroy@email.fr', 'Montreuil', 'jeunes', 'F', '2004-02-09', 1, 'actif'),
(8, 'Moreau', 'Christian', '+33629303132', 'christian.moreau@email.fr', 'Saint-Denis', 'autre', 'M', '1968-06-19', 1, 'actif')
ON DUPLICATE KEY UPDATE `nom` = VALUES(`nom`);

-- 4. Fidèle <-> Départements
INSERT IGNORE INTO `fidele_departements` (`fidele_id`, `departement_id`, `role_departement`, `date_adhesion`) VALUES
(1, 2, 'Responsable Accueil', '2023-01-15'),
(2, 3, 'Intercesseur', '2023-03-01'),
(3, 5, 'Leader Adjoint Jeunesse', '2022-09-10'),
(4, 1, 'Chantre Soprano', '2021-10-05'),
(5, 6, 'Monitrice Enfants', '2022-02-20'),
(6, 2, 'Équipe Logistique', '2023-05-12');

-- 5. Événements
INSERT INTO `evenements` (`id`, `categorie_id`, `titre`, `description`, `lieu`, `date_debut`, `date_fin`, `recurrent`, `affiche`, `statut`) VALUES
(1, 1, 'Grand Culte d\'Adoration & de Louange', 'Culte dominical avec Sainte-Cène, prédication puissante et louange prophétique.', 'Temple Principal, 12 Bd de l\'Alliance', '2026-11-29 09:30:00', '2026-11-29 12:00:00', 'hebdomadaire', 'uploads/events/culte_dimanche.jpg', 'publie'),
(2, 5, 'Veillée de Prière & d\'Impact Spirituel', 'Nuit d\'intercession intense pour les familles, les malades et le réveil des cœurs.', 'Temple Principal', '2026-12-04 21:00:00', '2026-12-05 04:00:00', 'mensuel', 'uploads/events/veillee.jpg', 'publie'),
(3, 2, 'Concert Gospel : Nuit de Grâce 2026', 'Concert caritatif avec la chorale principale et des chantres invités.', 'Salle des Fêtes Paroissiale', '2026-12-19 18:00:00', '2026-12-19 21:30:00', 'aucun', 'uploads/events/concert_gospel.jpg', 'publie'),
(4, 1, 'Étude Biblique & Enseignement Pratique', 'Approfondissement des Épîtres de Paul et partage en petits groupes.', 'Salle Annexe & En direct Zoom', '2026-12-02 19:00:00', '2026-12-02 20:30:00', 'hebdomadaire', NULL, 'publie')
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

-- 6. Annonces
INSERT INTO `annonces` (`id`, `categorie_id`, `titre`, `contenu`, `image`, `epingle`, `statut`, `date_publication`, `vues_count`) VALUES
(1, 1, 'Reprise des inscriptions pour les baptêmes d\'eau', 'Les cours de préparation aux baptêmes d\'eau débutent le premier samedi du mois prochain. Rapprochez-vous du secrétariat ou inscrivez-vous via l\'application.', 'uploads/annonces/baptemes.jpg', 1, 'publie', '2026-11-20 10:00:00', 142),
(2, 7, 'Action de Grâce : Célébration des nouveaux-nés', 'Rejoignez-nous ce dimanche pour présenter et bénir les nouveau-nés de notre communauté nés au cours des trois derniers mois.', NULL, 0, 'publie', '2026-11-22 14:30:00', 89),
(3, 4, 'Soutien fraternel et prière pour la famille Kouassi', 'Nous portons dans nos prières notre sœur Marie-Claire suite au deuil qui touche sa famille en Côte d\'Ivoire.', NULL, 0, 'publie', '2026-11-23 09:15:00', 115)
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

-- 7. Moments Forts (Pensée du Jour / Verset)
INSERT INTO `moments_forts` (`id`, `titre`, `verset_reference`, `verset_texte`, `message`, `predicateur`, `date_moment`, `statut`) VALUES
(1, 'Demeurer ferme dans l\'épreuve', 'Jacques 1:12', 'Heureux l\'homme qui supporte patiemment la tentation; car, après avoir été éprouvé, il recevra la couronne de vie...', 'Quelle que soit la tempête que vous traversez aujourd\'hui, sachez que la fidélité de Dieu ne faiblit jamais. Gardez les yeux fixés sur Christ.', 'Pasteur Samuel', '2026-11-23', 'publie'),
(2, 'La puissance de la louange sincère', 'Psaume 100:4', 'Entrez dans ses portes avec des louanges, dans ses parvis avec des cantiques ! Célébrez-le, bénissez son nom !', 'La louange brise les chaînes et ouvre les portes fermées. Louons Dieu même avant de voir la délivrance.', 'Pasteur Samuel', '2026-11-21', 'publie')
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

-- 8. Vidéos & Enseignements
INSERT INTO `videos` (`id`, `titre`, `description`, `plateforme`, `url`, `miniature`, `predicateur`, `date_video`, `statut`, `duree`, `vues_count`) VALUES
(1, 'La foi qui déplace les montagnes', 'Enseignement puissant sur le rôle actif de la foi dans notre quotidien et face à l\'adversité.', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', 'Pasteur Samuel', '2026-11-15', 'publie', '45:20', 320),
(2, 'Vivre une vie guidée par l\'Esprit', 'Deuxième partie de la série consacrée aux fruits du Saint-Esprit et au discernement divin.', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', 'David Koffi', '2026-11-22', 'publie', '38:15', 185)
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);

-- 9. Présences de test au culte
INSERT IGNORE INTO `presences` (`evenement_id`, `fidele_id`, `statut`, `date_pointage`, `remarque`) VALUES
(1, 1, 'present', '2026-11-22 09:25:00', 'À l\'heure'),
(1, 2, 'present', '2026-11-22 09:30:00', 'À l\'heure'),
(1, 3, 'present', '2026-11-22 09:40:00', 'En service sono'),
(1, 4, 'present', '2026-11-22 09:15:00', 'Répétition chorale'),
(1, 5, 'excuse', '2026-11-22 08:50:00', 'En voyage'),
(1, 6, 'present', '2026-11-22 09:28:00', 'À l\'heure'),
(1, 7, 'absent', '2026-11-22 12:00:00', 'Sans motif');

-- 10. Demandes de prière
INSERT INTO `demandes_priere` (`id`, `fidele_id`, `nom_demandeur`, `telephone`, `sujet`, `message`, `anonyme`, `statut`) VALUES
(1, 2, 'Marie-Claire Kouassi', '+33605060708', 'Consolation et paix familiale', 'Prière pour le repos de l\'âme de mon oncle et le réconfort de mes cousins en cette période de deuil.', 0, 'en_priere'),
(2, NULL, 'Visiteur Anonyme', NULL, 'Guérison pour ma mère hospitalisée', 'Ma maman subit une opération chirurgicale délicate ce vendredi matin. Merci de prier pour le succès des médecins.', 1, 'nouveau'),
(3, 3, 'Jonathan Mendy', '+33609101112', 'Recherche d\'emploi / Stage ingénieur', 'Remerciement : Dieu a ouvert une porte d\'entretien décisif mardi prochain !', 0, 'exauce')
ON DUPLICATE KEY UPDATE `sujet` = VALUES(`sujet`);

-- 11. Dons et offrandes
INSERT INTO `dons` (`id`, `fidele_id`, `nom_donateur`, `type_don`, `montant`, `devise`, `methode`, `statut`, `date_don`, `remarque`) VALUES
(1, 1, 'Jean-Pierre Martin', 'dime', 250.00, 'EUR', 'virement', 'valide', '2026-11-10 14:00:00', 'Dîme du mois de novembre'),
(2, NULL, 'Donateur anonyme', 'offrande', 50.00, 'EUR', 'especes', 'valide', '2026-11-22 11:30:00', 'Tronc offrandes dominicales'),
(3, 4, 'Sophie Dubois', 'construction', 100.00, 'EUR', 'carte', 'valide', '2026-11-18 16:45:00', 'Projet rénovation sono temple'),
(4, 6, 'Ibrahim Traore', 'action_sociale', 75.00, 'EUR', 'mobile_money', 'valide', '2026-11-21 18:20:00', 'Paniers solidaires de Noël')
ON DUPLICATE KEY UPDATE `montant` = VALUES(`montant`);

-- 12. Statistiques de vues (pour les graphiques Chart.js)
INSERT INTO `statistiques_vues` (`type_contenu`, `contenu_id`, `ip_hash`, `user_agent`, `date_vue`) VALUES
('video', 1, SHA2('192.168.1.10', 256), 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-11-20 14:10:00'),
('video', 1, SHA2('192.168.1.11', 256), 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', '2026-11-20 18:25:00'),
('video', 1, SHA2('192.168.1.12', 256), 'Mozilla/5.0 (Linux; Android 14)', '2026-11-21 10:05:00'),
('video', 2, SHA2('192.168.1.13', 256), 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-11-22 15:30:00'),
('annonce', 1, SHA2('192.168.1.14', 256), 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', '2026-11-21 09:40:00'),
('annonce', 1, SHA2('192.168.1.15', 256), 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-11-22 11:15:00'),
('annonce', 3, SHA2('192.168.1.16', 256), 'Mozilla/5.0 (Android 14)', '2026-11-23 10:00:00');
