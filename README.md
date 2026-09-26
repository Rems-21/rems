# ⚡ Dr Remus — Portfolio & Plateforme de Formation & Dashboard

Plateforme web complète développée pour **Dr Remus** — Technicien en Automatisme Industriel & Développeur Full-Stack.

Ce projet réunit :
1. **Un Portfolio d'ingénieur haute fidélité** : Présentation des compétences, simulateurs visuels interactifs SCADA/IoT/MES en SVG pur, étude de cas et formulaires de contact.
2. **Une Plateforme de Formations Pratiques** : Catalogue de formations par cohortes (Création Web, IA Générative, Automatisation, Freelancing) avec système de réservation en ligne.
3. **Un Vérificateur Public de Certificats & Diplômes (`/verify.php`)** : Registre officiel permettant de vérifier l'authenticité d'un diplôme via QR code ou ID unique, avec export PDF haute fidélité au format A4 paysage.
4. **Un Tableau de Bord d'Administration Sécurisé (`/admin`)** : Gestion des cohortes, inscriptions, statistiques de fréquentation en temps réel et émission de nouveaux certificats.

---

## 🛠️ Stack Technique

- **Frontend** : HTML5 sémantique, CSS3 moderne (design Dark Titane, micro-animations, glassmorphism, responsive mobile), JavaScript ES6+ vanilla (sans dépendance lourde).
- **Backend** : PHP 8.x (Architecture modulaire, PDO, requêtes préparées, sécurité CSRF/XSS, sessions sécurisées).
- **Base de données** : MySQL / MariaDB (Charset `utf8mb4_unicode_ci`).
- **Emails transactionnels** : Intégration API Brevo (v3) pour notifications instantanées des prises de contact et réservations.

---

## 🚀 Installation & Déploiement

### 1. Cloner le dépôt
```bash
git clone https://github.com/Rems-21/rems.git
cd rems
```

### 2. Configuration des variables d'environnement
Copiez le fichier `.env.example` en `.env` :
```bash
cp .env.example .env
```
Renseignez vos identifiants MySQL et votre clé API Brevo dans `.env`.

### 3. Initialisation de la Base de Données
Vous avez deux options :
- **Option 1 (Automatique)** : Ouvrez simplement l'application dans votre navigateur. Le script `admin/includes/db.php` créera automatiquement la base `portfolio_admin`, les 6 tables et les données d'initialisation.
- **Option 2 (Script SQL)** : Importez le fichier `database.sql` dans votre serveur MySQL ou via phpMyAdmin :
```bash
mysql -u root -p < database.sql
```

### 4. Accès par défaut
- **Site public** : `http://localhost/portfolio/index.html`
- **Formations** : `http://localhost/portfolio/formation.html`
- **Vérificateur officiel** : `http://localhost/portfolio/verify.php?cert=CERT-2026-0158`
- **Administration** : `http://localhost/portfolio/admin/login.php`
  - *Identifiant* : `admin`
  - *Mot de passe* : `DrRemus2026!`

---

## 📂 Structure du Projet

```
portfolio/
├── admin/                     # Dashboard & Back-office d'administration
│   ├── api/                   # Points d'entrée AJAX (réservations, certificats, etc.)
│   ├── css/                   # Styles du tableau de bord
│   ├── includes/              # DB, Authentification, Layout
│   ├── certificats.php        # Gestion & émission de certificats
│   ├── formations.php         # Gestion des cohortes
│   ├── reservations.php       # Suivi des inscrits
│   └── index.php              # Tableau de bord principal & KPIs
├── css/                       # Feuilles de style principales
├── js/                        # Scripts JS (animations, navigation, modals)
├── database.sql               # Script SQL de création et d'initialisation complet
├── formation.html             # Page publique des formations
├── index.html                 # Page d'accueil du portfolio
├── send-email.php             # Envoi d'email de contact (Brevo API)
├── send-reservation.php       # Traitement des réservations de formation
├── track.php                  # Télémétrie & comptage des visites
├── verify.php                 # Vérification publique et impression A4 des certificats
├── .env.example               # Modèle de variables d'environnement
└── .gitignore                 # Exclusion des fichiers sensibles
```

---

## 🔒 Sécurité & Bonnes Pratiques
- Mots de passe hashés avec `bcrypt`.
- Requêtes préparées PDO contre les injections SQL.
- Échappement systématique des sorties HTML (`htmlspecialchars`).
- Protection contre l'accès direct aux fichiers de configuration.
