<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$rawUser = $_SESSION['admin_user'] ?? 'Dr Remus';
$adminDisplayName = ($rawUser === 'admin' || $rawUser === 'Raphaël') ? 'Dr Remus' : $rawUser;
$userEmail = $_SESSION['admin_email'] ?? 'dsonkouatremus@gmail.com';

// Compter les nouvelles réservations pour le badge
$pendingCount = 0;
$pdo = getDatabaseConnection();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `reservations` WHERE statut = 'nouvelle'");
        $pendingCount = (int)$stmt->fetchColumn();
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Tableau de bord · Dr Remus Operations') ?></title>
  <link rel="icon" href="../logo-wolf.jpg" type="image/jpeg">
  <link rel="stylesheet" href="css/admin-grafana.css?v=3.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="adm-body-root">

  <div class="adm-app">

    <!-- Voile d'ombrage mobile (Backdrop) -->
    <div class="adm-sidebar-backdrop" id="admSidebarBackdrop" aria-hidden="true"></div>

    <!-- ═══ 1. SIDEBAR NAVIGATION GAUCHE ═════════════════════════════ -->
    <aside class="adm-sidebar" id="admSidebar">
      <!-- Logo Wolf en haut -->
      <div class="adm-sidebar-brand">
        <a href="dashboard.php" class="adm-brand-link" title="Dr Remus Dashboard">
          <img src="../logo-wolf.jpg" alt="Dr Remus Logo" class="adm-brand-wolf">
        </a>
        <button type="button" class="adm-sidebar-close" id="admSidebarClose" aria-label="Fermer le menu">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Navigation links -->
      <nav class="adm-nav">
        <!-- Tableau de bord -->
        <a href="dashboard.php" class="adm-nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Tableau de bord</span>
        </a>

        <!-- Site web -->
        <a href="../index.html" target="_blank" class="adm-nav-item">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span>Site web</span>
        </a>

        <!-- Projets -->
        <a href="projects.php" class="adm-nav-item <?= $currentPage === 'projects.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          <span>Projets</span>
        </a>

        <!-- Formations -->
        <a href="formations.php" class="adm-nav-item <?= $currentPage === 'formations.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
          <span>Formations</span>
        </a>

        <!-- Certificats & Diplômes -->
        <a href="certificats.php" class="adm-nav-item <?= $currentPage === 'certificats.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
          <span>Certificats</span>
        </a>

        <!-- Messages / Inscriptions avec Badge -->
        <a href="reservations.php" class="adm-nav-item <?= $currentPage === 'reservations.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <span>Messages</span>
          <?php if ($pendingCount > 0): ?>
            <span class="adm-nav-badge"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>

        <!-- Analytics -->
        <a href="analytics.php" class="adm-nav-item <?= $currentPage === 'analytics.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <span>Analytics</span>
        </a>

        <!-- Grafana External link -->
        <a href="dashboard.php#server-perf" class="adm-nav-item">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span>Monitoring</span>
        </a>

        <!-- Paramètres -->
        <a href="formations.php" class="adm-nav-item">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Paramètres</span>
        </a>
      </nav>

      <!-- Lien Bas : Voir le site -->
      <div class="adm-sidebar-footer">
        <a href="../index.html" target="_blank" class="adm-btn-site-link">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          <span>Voir le site</span>
        </a>
      </div>
    </aside>

    <!-- ═══ 2. ZONE PRINCIPALE CONTENU ═══════════════════════════════ -->
    <div class="adm-main-wrap">
      
      <!-- Barre d'en-tête supérieure -->
      <header class="adm-topbar">
        <div class="adm-topbar-left">
          <button type="button" class="adm-mobile-burger" id="admMobileToggle" aria-label="Menu latéral">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>
          <div class="adm-title-block">
            <h1 class="adm-page-title"><?= htmlspecialchars($pageTitleH1 ?? 'Tableau de bord') ?></h1>
            <p class="adm-page-subtitle"><?= htmlspecialchars($pageSubtitle ?? "Vue d'ensemble de votre site et de vos services") ?></p>
          </div>
        </div>

        <div class="adm-topbar-right">
          <!-- Filtre de Date Dropdown Pill -->
          <div class="adm-date-pill">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span class="adm-date-text">1 Sept. 2026 - 30 Sept. 2026</span>
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </div>

          <!-- Bouton Thème Jour/Nuit (Dark Titanium de référence) -->
          <button type="button" class="adm-icon-btn" title="Thème Graphite Titanium">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          </button>

          <!-- Bouton Notifications avec point d'alerte -->
          <button type="button" class="adm-icon-btn adm-icon-btn--bell" title="Notifications">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="adm-bell-dot"></span>
          </button>

          <!-- Profil Utilisateur Dr Remus -->
          <div class="adm-profile-pill">
            <img src="../remus-cta.jpg" alt="Dr Remus" class="adm-profile-avatar" onerror="this.src='../profil-pro.jpg'">
            <div class="adm-profile-info">
              <span class="adm-profile-name"><?= htmlspecialchars($adminDisplayName) ?></span>
              <span class="adm-profile-role">Administrateur</span>
            </div>
            <a href="logout.php" class="adm-logout-link" title="Se déconnecter">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
          </div>
        </div>
      </header>

      <!-- Corps principal de la page -->
      <main class="adm-body">
