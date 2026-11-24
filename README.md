# ✞ ÉGLISE APP (v2.0) - Application Paroissiale PWA & Gestion Complète

Application paroissiale moderne, responsive et complète, conçue selon la **Palette Option A (Bleu Nuit Majestueux `#0F172A` & Or Sacré `#D97706`)** avec bascule instantanée Mode Sombre / Mode Clair.

---

## 🚀 Fonctionnalités Clés

### 🌐 Espace Public & Expérience Fidèle (PWA)
1. **Accueil Solennel (`index.php`)** :
   - Présentation de la communauté, slogan et verset biblique de l'année.
   - Prochain culte ou rendez-vous en vedette.
   - Méditation / exhortation du jour.
   - Dernières annonces épinglées et dernières prédications vidéo.
2. **Le Mur Fraternel « Vivre Ensemble » (`communaute.php`)** :
   - Partage de témoignages d'actions de grâce et d'édification.
   - Prestations vidéo et musicales de la chorale paroissiale.
   - Méditations et versets bibliques publiés par les fidèles ou le département Média.
   - Système de commentaires en direct et réactions *« Amen / Gloire à Dieu »*.
3. **Échanger avec le Pasteur (`pasteur.php`)** :
   - Boîte pastorale confidentielle interne ou continuation directe sur le **WhatsApp officiel du Pasteur** en un clic.
4. **Plateforme d'Entraide Fraternelle (`entraide.php`)** :
   - Dépôt de besoins : Travail / Emploi, Aide financière d'urgence, Soutien émotionnel / Écoute, Visites aux malades.
   - Option d'anonymat complet garantie.
   - Tableau d'affichage des besoins partagés avec la communauté.
5. **Calendrier des Cultes & Événements (`evenements.php`)** :
   - Liste des cultes, veillées et rassemblements avec dates précises, lieux et récurrences.
   - Bouton d'ajout direct au calendrier Google Calendar en 1 clic.
6. **Annonces & Nouvelles (`annonces.php`)** :
   - Moteur de recherche instantané et filtres thématiques.
7. **Médias & Prédications (`videos.php`)** :
   - Lecteur vidéo YouTube intégré sans cookies traceurs.
   - Bandeau d'alerte Live Stream si un culte est en direct.
   - Bulletins paroissiaux et documents téléchargeables (PDF / Audios).
8. **Intercession & Prière (`priere.php`)** :
   - Dépôt d'intentions de prière confidentielles ou anonymes avec protection anti-spam CSRF et honeypot.
9. **Soutien Financier & Dons (`don.php`)** :
   - Déclaration d'offrandes, dîmes ou dons aux projets paroissiaux.
   - Affichage des coordonnées bancaires officielles (avec bouton *« Copier l'IBAN »*) et numéros Mobile Money.

---

### 🛡️ Espace Administration Sécurisé (`/admin/`)
* **Sécurité Anti Brute-Force** : surveillance automatique des adresses IP dans `tentatives_connexion` et blocage de 15 minutes dès 5 échecs consécutifs.
* **Tableau de Bord (`admin/index.php`)** : métriques instantanées (effectif fidèles, présences du culte, dons du mois, alertes de prière, messages pasteur non lus).
* **Graphiques Chart.js v4 (`admin/statistiques.php`)** :
  - Assiduité aux cultes (courbe lissée en dégradé d'or).
  - Démographie par groupe (anneau *Doughnut*).
  - Consultations des vidéos et annonces (barres horizontales).
  - Recettes mensuelles des offrandes et dîmes.
  - **Adaptation instantanée au mode sombre/clair** sans rechargement de page.
* **Pointage Numérique des Présences (`admin/presences.php`)** :
  - Interface tactile ultra-rapide asynchrone (AJAX) pour pointer les fidèles à l'entrée (*Présent*, *Absent*, *Excusé*) en moins de 2 minutes pour 50 personnes.
* **Répertoire des Fidèles (`admin/fideles.php`)** : fiche complète, rattachement multi-départements, export et filtres.
* **Départements & Ministères (`admin/departements.php`)** : effectifs de chaque équipe de service en direct.
* **Centre de Notifications Web Push PWA (`admin/notifications.php`)** : envoi de notifications push sur les téléphones et ordinateurs des fidèles (norme W3C / Service Worker), ciblage par groupe ministériel et suivi des taux de lecture.
* **Boîte Pastorale Confidentielle (`admin/pasteur_messages.php`)** : consultation des messages adressés au pasteur, réponses directes dans l'app ou relance via WhatsApp d'un clic.
* **Modération du Mur Fraternel (`admin/communaute_moderation.php`)** : modération bienveillante des messages et commentaires.
* **Finances & Trésorerie (`admin/dons.php`)** : validation des dons et enregistrement des quêtes en espèces/chèques.
* **Journal d'Audit Système (`admin/logs.php`)** : historique des opérations administratives.

---

## 🔑 Compte Administrateur Initial

* **URL d'accès** : `http://localhost/eglise-app/admin/login.php`
* **Identifiant (Username)** : `admin_dev`
* **Email** : `admin@eglisedelagrace.fr`
* **Rôle** : `developpeur` (accès total)
* **Mot de passe généré** : `18eb5034cf8a@241!`
* *Note* : Vous pouvez créer d'autres comptes ou modifier votre mot de passe à tout moment via le script sécurisé en CLI :
  ```bash
  php scripts/creer_developpeur.php
  ```
