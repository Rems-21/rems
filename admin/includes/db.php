<?php
declare(strict_types=1);

/**
 * GESTIONNAIRE DE BASE DE DONNÉES & INITIALISATION AUTOMATIQUE
 * Portfolio & Dashboard Dr Remus
 */

if (!function_exists('loadEnvConfig')) {
    function loadEnvConfig(): array {
        static $env = null;
        if ($env !== null) return $env;
        
        $env = [];
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $env[trim($parts[0])] = trim(trim($parts[1]), "\"'\t\n\r");
                }
            }
        }
        return $env;
    }
}

function getDatabaseConnection(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $env = loadEnvConfig();
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $dbName = $env['DB_NAME'] ?? 'portfolio_admin';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    try {
        // 1. Première connexion pour vérifier/créer la base de données
        $serverPdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3
            ]
        );
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // 2. Connexion à la base de données spécifique
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // 3. Initialiser les tables et données si nécessaire
        ensureSchemaAndSeedData($pdo, $env);

        return $pdo;
    } catch (Throwable $e) {
        // Journaliser l'erreur discrètement sans interrompre l'expérience utilisateur
        error_log('Erreur DB Portfolio : ' . $e->getMessage());
        return null;
    }
}

function ensureSchemaAndSeedData(PDO $pdo, array $env): void {
    // ── TABLE 1 : FORMATIONS ─────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `formations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(60) NOT NULL UNIQUE,
        `titre` VARCHAR(255) NOT NULL,
        `badge` VARCHAR(100) DEFAULT 'COHORTE',
        `description` TEXT NULL,
        `date_debut` VARCHAR(100) DEFAULT 'Date à venir',
        `duree` VARCHAR(50) DEFAULT '4 semaines',
        `places_total` INT DEFAULT 10,
        `places_disponibles` INT DEFAULT 7,
        `statut` ENUM('actif', 'ouvert', 'prochainement', 'complet', 'termine', 'archive') DEFAULT 'ouvert',
        `is_active_cohort` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ── TABLE 2 : RÉSERVATIONS / INSCRIPTIONS ────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reservations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `formation_id` INT NULL,
        `formation_titre` VARCHAR(255) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `nom` VARCHAR(100) NULL,
        `email` VARCHAR(191) NOT NULL,
        `whatsapp` VARCHAR(50) NULL,
        `message` TEXT NULL,
        `statut` ENUM('nouvelle', 'confirmee', 'terminee', 'annulee') DEFAULT 'nouvelle',
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_statut (`statut`),
        INDEX idx_created_at (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ── TABLE 3 : VISITES DU SITE (ANALYTICS) ────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `site_visits` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `page` VARCHAR(100) NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `device` VARCHAR(30) DEFAULT 'desktop',
        `user_agent` VARCHAR(255) NULL,
        `referrer` VARCHAR(255) NULL,
        `visited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_page (`page`),
        INDEX idx_visited_at (`visited_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ── TABLE 4 : UTILISATEURS ADMIN ─────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(60) NOT NULL UNIQUE,
        `email` VARCHAR(191) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ── INSERTION DE L'ADMIN PAR DÉFAUT SI VIDE ──────────────────────
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admin_users`");
    if ((int)$stmt->fetchColumn() === 0) {
        $adminUser = $env['ADMIN_USER'] ?? 'admin';
        $adminPass = $env['ADMIN_PASS'] ?? 'DrRemus2026!';
        $adminEmail = $env['ADMIN_EMAIL'] ?? 'dsonkouatremus@gmail.com';
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        
        $ins = $pdo->prepare("INSERT INTO `admin_users` (username, email, password_hash) VALUES (?, ?, ?)");
        $ins->execute([$adminUser, $adminEmail, $hash]);
    }

    // ── INSERTION DES FORMATIONS EXISTANTES SI VIDE ──────────────────
    $stmtForm = $pdo->query("SELECT COUNT(*) FROM `formations`");
    if ((int)$stmtForm->fetchColumn() === 0) {
        $formations = [
            [
                'code' => 'web-ia-octobre-2026',
                'titre' => "Créer un site web avec l'IA",
                'badge' => 'PROCHAINE COHORTE',
                'description' => "De l'idée à la mise en ligne, construis ton propre site web avec l'aide des outils d'intelligence artificielle.",
                'date_debut' => '15 octobre 2026',
                'duree' => '4 semaines',
                'places_total' => 10,
                'places_disponibles' => 7,
                'statut' => 'ouvert',
                'is_active_cohort' => 1
            ],
            [
                'code' => 'dev-ia',
                'titre' => "Développer avec l'IA",
                'badge' => 'DÉVELOPPEMENT',
                'description' => "Passer du site statique à une application web dynamique en utilisant l'IA comme copilote de code.",
                'date_debut' => 'Date à venir',
                'duree' => '5 semaines',
                'places_total' => 10,
                'places_disponibles' => 10,
                'statut' => 'prochainement',
                'is_active_cohort' => 0
            ],
            [
                'code' => 'premier-projet-web',
                'titre' => "Premier projet web",
                'badge' => 'INITIATION',
                'description' => "De zéro absolu à un premier projet web complet et hébergé. Aucun prérequis technique exigé.",
                'date_debut' => 'Date à venir',
                'duree' => '3 semaines',
                'places_total' => 12,
                'places_disponibles' => 12,
                'statut' => 'prochainement',
                'is_active_cohort' => 0
            ],
            [
                'code' => 'freelance-ia',
                'titre' => "Lancer son freelance",
                'badge' => 'BUSINESS',
                'description' => "Structurer son offre, trouver ses premiers clients et gérer son activité freelance avec l'appui de l'IA.",
                'date_debut' => 'Date à venir',
                'duree' => '4 semaines',
                'places_total' => 10,
                'places_disponibles' => 10,
                'statut' => 'prochainement',
                'is_active_cohort' => 0
            ],
            [
                'code' => 'archive-cohorte-03',
                'titre' => "Créer un site web avec l'IA (Cohorte #03)",
                'badge' => 'COHORTE #03',
                'description' => "10 participants ont conçu, intégré et mis en ligne leur plateforme web avec hébergement cloud.",
                'date_debut' => 'Juin 2026',
                'duree' => '4 semaines',
                'places_total' => 10,
                'places_disponibles' => 0,
                'statut' => 'termine',
                'is_active_cohort' => 0
            ],
            [
                'code' => 'archive-cohorte-02',
                'titre' => "Automatisation & Workflows IA (Cohorte #02)",
                'badge' => 'COHORTE #02',
                'description' => "8 participants ont automatisé des processus métiers complets : gestion de formulaires et intégration API.",
                'date_debut' => 'Février 2026',
                'duree' => '4 semaines',
                'places_total' => 8,
                'places_disponibles' => 0,
                'statut' => 'termine',
                'is_active_cohort' => 0
            ],
            [
                'code' => 'archive-cohorte-01',
                'titre' => "Fondations du Web Moderne & IA (Cohorte #01)",
                'badge' => 'COHORTE #01',
                'description' => "8 participants ont appris les fondamentaux du code propre assisté par l'IA.",
                'date_debut' => 'Novembre 2025',
                'duree' => '4 semaines',
                'places_total' => 8,
                'places_disponibles' => 0,
                'statut' => 'termine',
                'is_active_cohort' => 0
            ]
        ];

        $insForm = $pdo->prepare("INSERT INTO `formations` 
            (code, titre, badge, description, date_debut, duree, places_total, places_disponibles, statut, is_active_cohort)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($formations as $f) {
            $insForm->execute([
                $f['code'], $f['titre'], $f['badge'], $f['description'],
                $f['date_debut'], $f['duree'], $f['places_total'], $f['places_disponibles'],
                $f['statut'], $f['is_active_cohort']
            ]);
        }
    }

    // ── TABLE 5 : PROJETS DU PORTFOLIO ──────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `projects` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ordre` INT DEFAULT 1,
        `badge` VARCHAR(100) DEFAULT 'Projet Web',
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `demo_url` VARCHAR(255) DEFAULT '#',
        `github_url` VARCHAR(255) DEFAULT '#',
        `tech_stack` TEXT NOT NULL,
        `preview_svg` MEDIUMTEXT NULL,
        `preview_image` VARCHAR(255) NULL,
        `is_visible` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ordre (`ordre`),
        INDEX idx_is_visible (`is_visible`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ── INSERTION DES PROJETS EXISTANTS SI VIDE ──────────────────────
    $stmtProj = $pdo->query("SELECT COUNT(*) FROM `projects`");
    if ((int)$stmtProj->fetchColumn() === 0) {
        $seedFile = __DIR__ . '/projects_seed.json';
        if (file_exists($seedFile)) {
            $projectsData = json_decode(file_get_contents($seedFile), true);
            if (is_array($projectsData)) {
                $insProj = $pdo->prepare("INSERT INTO `projects` 
                    (ordre, badge, title, description, demo_url, github_url, tech_stack, preview_svg, is_visible)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                foreach ($projectsData as $p) {
                    $insProj->execute([
                        $p['ordre'] ?? 1,
                        $p['badge'] ?? 'Projet',
                        $p['title'] ?? '',
                        $p['description'] ?? '',
                        $p['demo_url'] ?? '#',
                        $p['github_url'] ?? '#',
                        is_string($p['tech_stack']) ? $p['tech_stack'] : json_encode($p['tech_stack'], JSON_UNESCAPED_UNICODE),
                        $p['preview_svg'] ?? ''
                    ]);
                }
            }
        }
    }

    // ── TABLE 6 : CERTIFICATS & ATTESTATIONS ─────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `certificats` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `cert_id` VARCHAR(64) NOT NULL UNIQUE,
        `nom_apprenant` VARCHAR(150) NOT NULL,
        `email_apprenant` VARCHAR(191) NULL,
        `formation_titre` VARCHAR(255) NOT NULL,
        `description_cert` TEXT NULL,
        `cohorte` VARCHAR(100) DEFAULT 'Octobre 2026',
        `mention` VARCHAR(100) DEFAULT 'Mention Très Bien',
        `duree` VARCHAR(50) DEFAULT '30 heures',
        `periode` VARCHAR(100) DEFAULT '15 Juin 2026 – 15 Juillet 2026',
        `niveau` VARCHAR(100) DEFAULT 'Débutant → Intermédiaire',
        `lieu` VARCHAR(100) DEFAULT 'À Douala, Cameroun',
        `formateur` VARCHAR(100) DEFAULT 'Dr Remus',
        `date_emission` DATE NOT NULL,
        `competences` TEXT NULL,
        `statut` ENUM('valide', 'revoque', 'archive') DEFAULT 'valide',
        `qr_code_hash` VARCHAR(128) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cert_id (`cert_id`),
        INDEX idx_statut (`statut`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insertion des certificats initiaux si vide
    $stmtCert = $pdo->query("SELECT COUNT(*) FROM `certificats`");
    if ((int)$stmtCert->fetchColumn() === 0) {
        $insCert = $pdo->prepare("INSERT INTO `certificats` 
            (cert_id, nom_apprenant, email_apprenant, formation_titre, description_cert, cohorte, mention, duree, periode, niveau, lieu, formateur, date_emission, competences, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide')");
        
        $insCert->execute([
            'REMUS-2026-001',
            'Alexandre Mbarga',
            'alexandre.mbarga@example.com',
            "Créer un site web moderne avec l'IA",
            "Cette formation a couvert les notions essentielles et les compétences pratiques pour concevoir, développer et déployer un site web professionnel à l'aide des outils d'IA.",
            'Cohorte Alpha · Octobre 2026',
            'Mention Très Bien (Félicitations du Jury)',
            '30 heures',
            '15 Juin 2026 – 15 Juillet 2026',
            'Débutant → Intermédiaire',
            'À Douala, Cameroun',
            'Dr Remus',
            '2026-10-15',
            "Ingénierie de Prompt & IA Générative\nDéveloppement Web Frontend (HTML5, CSS3, JavaScript moderne)\nArchitecture Backend & Base de données MySQL\nDéploiement en Production & Sécurisation SSL"
        ]);

        $insCert->execute([
            'CERT-2026-0158',
            'Jean Kévin Mbarga',
            'jean.kevin@example.com',
            "Créer un site web avec l’IA",
            "Cette formation a couvert les notions essentielles et les compétences pratiques pour concevoir, développer et déployer un site web professionnel à l'aide des outils d'IA.",
            'Cohorte Juin 2026',
            'Mention Très Bien',
            '30 heures',
            '15 Juin 2026 – 15 Juillet 2026',
            'Débutant → Intermédiaire',
            'À Douala, Cameroun',
            'Dr Remus',
            '2026-07-15',
            'Next.js 14, Tailwind CSS, Claude 3.5 Sonnet, Vercel CI/CD, API REST'
        ]);
    }
}

