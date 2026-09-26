<?php
declare(strict_types=1);

$pageTitle = 'Statistiques & Trafic · Dr Remus Operations';
$pageTitleH1 = 'Statistiques & Trafic';
$pageSubtitle = "Analyse d'audience en temps réel, terminaux utilisés et pages les plus consultées";

require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();

$totalVisits = 0;
$uniqueVisitors = 0;
$visitsByPage = [];
$visitsByDevice = [];
$recentVisits = [];

if ($pdo) {
    // 1. Total & Uniques
    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT ip_address) as uniques FROM `site_visits`");
    if ($row = $stmt->fetch()) {
        $totalVisits = (int)$row['total'];
        $uniqueVisitors = (int)$row['uniques'];
    }

    // 2. Visites par page
    $stmt = $pdo->query("SELECT page, COUNT(*) as count FROM `site_visits` GROUP BY page ORDER BY count DESC LIMIT 10");
    $visitsByPage = $stmt->fetchAll();

    // 3. Visites par appareil
    $stmt = $pdo->query("SELECT device, COUNT(*) as count FROM `site_visits` GROUP BY device ORDER BY count DESC");
    $visitsByDevice = $stmt->fetchAll();

    // 4. Dernières visites
    $stmt = $pdo->query("SELECT * FROM `site_visits` ORDER BY visited_at DESC LIMIT 30");
    $recentVisits = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/layout_top.php';
?>

<!-- ── 1. KPI BANNER ANALYTICS ───────────────────────────────────── -->
<div class="adm-kpi-grid">
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-blue);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </div>
      <span class="adm-kpi-label">Pages Vues Totales</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val"><?= number_format($totalVisits, 0, ',', ' ') ?></div>
        <div class="adm-trend-sub">Trafic global cumulé</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 18 L18 10 L34 14 L50 6 L66 10" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-green);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span class="adm-kpi-label">Visiteurs Uniques (IP)</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-green);"><?= number_format($uniqueVisitors, 0, ',', ' ') ?></div>
        <div class="adm-trend-pill">
          <span class="adm-badge-dot"></span> IP Dédupliquées
        </div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 20 L18 14 L34 10 L50 12 L66 4" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-orange);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <span class="adm-kpi-label">Page la Plus Consultée</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="font-size:18px; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-bottom:4px;">
          <?= htmlspecialchars($visitsByPage[0]['page'] ?? 'index.html') ?>
        </div>
        <div class="adm-trend-sub">
          <?= $visitsByPage[0]['count'] ?? 0 ?> consultations
        </div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 14 L18 18 L34 8 L50 12 L66 6" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      </div>
      <span class="adm-kpi-label">Terminal Principal</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="text-transform:capitalize;">
          <?= htmlspecialchars($visitsByDevice[0]['device'] ?? 'Desktop') ?>
        </div>
        <div class="adm-trend-sub">
          <?= $visitsByDevice[0]['count'] ?? 0 ?> sessions
        </div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 10 L18 8 L34 16 L50 10 L66 4" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<!-- ── 2. RÉPARTITIONS PAGES & APPAREILS (GRILLE 2 COLONNES) ───────── -->
<div class="adm-grid-1-1">
  <!-- Pages populaires -->
  <div class="adm-card">
    <div class="adm-card-header">
      <span class="adm-card-title">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Consultations par Page
      </span>
    </div>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr>
            <th>Page</th>
            <th style="width:90px; text-align:right;">Vues</th>
            <th style="width:40%;">Part du trafic</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($visitsByPage)): ?>
            <?php foreach ($visitsByPage as $p): ?>
              <?php $pct = $totalVisits > 0 ? round(($p['count'] / $totalVisits) * 100) : 0; ?>
              <tr>
                <td><strong style="color:#ffffff; font-family:monospace;"><?= htmlspecialchars($p['page']) ?></strong></td>
                <td style="text-align:right; font-weight:700; color:#ffffff;"><?= $p['count'] ?></td>
                <td>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <div style="flex:1; height:6px; background:#1c202a; border-radius:999px; overflow:hidden;">
                      <div style="width:<?= $pct ?>%; height:100%; background:var(--adm-accent-blue); border-radius:999px;"></div>
                    </div>
                    <span style="font-size:11px; color:var(--adm-text-muted); min-width:30px; text-align:right;"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--adm-text-muted);">Aucune visite enregistrée</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Appareils -->
  <div class="adm-card">
    <div class="adm-card-header">
      <span class="adm-card-title">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        Répartition par Appareil
      </span>
    </div>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead>
          <tr>
            <th>Terminal</th>
            <th style="width:90px; text-align:right;">Sessions</th>
            <th style="width:40%;">Proportion</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($visitsByDevice)): ?>
            <?php foreach ($visitsByDevice as $dev): ?>
              <?php $pct = $totalVisits > 0 ? round(($dev['count'] / $totalVisits) * 100) : 0; ?>
              <tr>
                <td style="text-transform:capitalize;"><strong style="color:#ffffff;"><?= htmlspecialchars($dev['device']) ?></strong></td>
                <td style="text-align:right; font-weight:700; color:#ffffff;"><?= $dev['count'] ?></td>
                <td>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <div style="flex:1; height:6px; background:#1c202a; border-radius:999px; overflow:hidden;">
                      <div style="width:<?= $pct ?>%; height:100%; background:var(--adm-accent-green); border-radius:999px;"></div>
                    </div>
                    <span style="font-size:11px; color:var(--adm-text-muted); min-width:30px; text-align:right;"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--adm-text-muted);">Aucune donnée d'appareil</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── 3. HISTORIQUE RÉCENT DES CONNEXIONS ─────────────────────────── -->
<div class="adm-card">
  <div class="adm-card-header">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Flux Récent des Requêtes &amp; Connexions (Temps Réel)
    </span>
    <span style="font-size:12px; color:var(--adm-text-muted);"><?= count($recentVisits) ?> dernières requêtes</span>
  </div>

  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead>
        <tr>
          <th>Horodatage</th>
          <th>Page Consultée</th>
          <th>Adresse IP</th>
          <th>Appareil</th>
          <th>Source / Référent</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentVisits)): ?>
          <?php foreach ($recentVisits as $v): ?>
            <tr>
              <td style="color:var(--adm-text-muted); font-size:11.5px; white-space:nowrap;">
                <?= date('d/m/Y H:i:s', strtotime($v['visited_at'])) ?>
              </td>
              <td>
                <span style="font-family:monospace; color:#ffffff; font-weight:600;"><?= htmlspecialchars($v['page']) ?></span>
              </td>
              <td style="font-family:monospace; color:#a1a1aa; font-size:11.5px;">
                <?= htmlspecialchars((string)($v['ip_address'] ?? '127.0.0.1')) ?>
              </td>
              <td style="text-transform:capitalize;">
                <span class="adm-badge adm-badge--confirmee"><?= htmlspecialchars((string)($v['device'] ?? 'Desktop')) ?></span>
              </td>
              <td style="color:var(--adm-text-muted); font-size:11.5px;">
                <?= htmlspecialchars(mb_strimwidth((string)($v['referrer'] ?? 'Accès Direct'), 0, 45, '...')) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center; padding:36px; color:var(--adm-text-muted);">Aucune visite dans le journal.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
