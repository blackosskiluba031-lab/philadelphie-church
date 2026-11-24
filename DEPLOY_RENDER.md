# 🚀 Guide de Déploiement : Philadelphie Church sur Render Free

Ce guide détaille pas-à-pas la mise en ligne gratuite de l'application **Philadelphie Church (CFCG GARENGANZE)** sur **Render Free** avec base de données MySQL dans le Cloud.

---

## 📋 Prérequis & Fichiers Inclus

Le projet est déjà configuré avec :
- `Dockerfile` : image PHP 8.3 Apache prête pour Render avec extensions (`pdo_mysql`, `gd`, `intl`, `opcache`).
- `render.yaml` : blueprint de déploiement automatique.
- `database/schema_render.sql` : schéma SQL complet avec toutes les tables, comptes initiaux (Pasteur, Média, Admin Dev), paramètres RDC, devises (FC & USD), programmes officiels et structure Church X.
- `.gitignore` & `.dockerignore` : exclusion des fichiers temporaires et des uploads locaux.

---

## Étape 1 : Créer une Base de Données MySQL Gratuite dans le Cloud

Render proposant nativement PostgreSQL en offre gratuite, pour MySQL nous recommandons l'un de ces services gratuits sans carte bancaire :

### Option A (Recommandée) : Aiven Free MySQL
1. Rendez-vous sur [https://aiven.io](https://aiven.io) et créez un compte gratuit.
2. Créez un nouveau service **MySQL** (plan gratuit).
3. Notez les informations de connexion fournies :
   - **Host** : `mysql-xxxx-xxxx.aivencloud.com`
   - **Port** : `xxxx` (souvent 10000-30000)
   - **User** : `avnadmin`
   - **Password** : votre mot de passe généré
   - **Database Name** : `defaultdb` ou `eglise_app`

### Option B : TiDB Serverless (MySQL Compatible)
1. Rendez-vous sur [https://tidbcloud.com](https://tidbcloud.com).
2. Créez un cluster Serverless gratuit (5 Go gratuits à vie).

---

## Étape 2 : Importer les Tables et Données (`schema_render.sql`)

Depuis votre ordinateur (Invite de commande ou PowerShell dans Laragon) :

```powershell
# Syntaxe standard d'importation :
mysql -h VOTRE_HOST_CLOUD -P VOTRE_PORT -u VOTRE_USER -p VOTRE_DB_NAME < database/schema_render.sql
```

*(Ou importez directement le fichier `database/schema_render.sql` via **phpMyAdmin**, **DBeaver**, ou l'interface web de votre hébergeur de base de données).*

---

## Étape 3 : Initialiser le Dépôt Git et Publier sur GitHub

Ouvrez un terminal PowerShell dans le dossier de l'application (`c:\laragon\www\eglise-app`) :

```powershell
cd c:\laragon\www\eglise-app

# 1. Initialiser le dépôt Git
git init

# 2. Configurer la branche principale
git branch -M main

# 3. Ajouter tous les fichiers
git add .

# 4. Premier commit
git commit -m "Initial commit - Philadelphie Church PWA v2.0"

# 5. Lier à votre dépôt GitHub (remplacez par votre URL de dépôt GitHub)
git remote add origin https://github.com/VOTRE_PSEUDO/philadelphie-church.git

# 6. Pousser vers GitHub
git push -u origin main
```

---

## Étape 4 : Déployer sur Render Free

1. Connectez-vous sur [https://render.com](https://render.com).
2. Cliquez sur **New +** > **Web Service**.
3. Choisissez **Build and deploy from a Git repository** et sélectionnez votre dépôt `philadelphie-church`.
4. Configurez les paramètres :
   - **Name** : `philadelphie-church`
   - **Region** : `Frankfurt (EU Central)` ou la région la plus proche.
   - **Runtime** : **Docker** (Render détectera automatiquement le `Dockerfile`).
   - **Instance Type** : **Free**.
5. Dans la section **Environment Variables**, ajoutez :

| Variable | Valeur |
|---|---|
| `DB_HOST` | *(Votre hôte MySQL Cloud, ex: `mysql-xxxx.aivencloud.com`)* |
| `DB_PORT` | *(Votre port MySQL Cloud, ex: `25060`)* |
| `DB_NAME` | *(Nom de la base, ex: `eglise_app` ou `defaultdb`)* |
| `DB_USER` | *(Votre nom d'utilisateur MySQL Cloud)* |
| `DB_PASSWORD` | *(Votre mot de passe MySQL Cloud)* |
| `DEVISE_DEFAUT` | `FC` |
| `APP_ENV` | `production` |

*(Alternative : Si votre fournisseur fournit une URL complète, définissez simplement `DATABASE_URL=mysql://user:pass@host:port/dbname`).*

6. Cliquez sur **Create Web Service**. Render va construire l'image Docker et démarrer votre application !
7. Votre application sera accessible via une URL HTTPS gratuite du type :  
   `https://philadelphie-church.onrender.com`

---

## 🔐 Identifiants d'Accès Staff & Pastorale

Une fois déployée, accédez à la console d'administration via :  
`https://votre-app.onrender.com/admin/login.php`

| Espace | Rôle | Nom d'utilisateur | Mot de passe |
|---|---|---|---|
| **Espace Développeur** | Administrateur Système | `admin_dev` | `Philadelphie2026!` |
| **Espace Pastoral** | Pasteur (Responsable) | `pasteur` | `Pasteur2026!` |
| **Espace Média** | Responsable Média | `media` | `Media2026!` |

---

## 📱 Numéros & Lignes Officielles (RDC)

- **Transfert / Soutien à la maintenance & au domaine** : `0990945558` (M-Pesa / Airtel Money / Orange Money)
- **Contact & Renseignements techniques** : `0901247908`
- **Lien WhatsApp direct** : [https://wa.me/243901247908](https://wa.me/243901247908)
- **Devises** : Franc Congolais (`FC`) et Dollars Américains (`$ USD`).
