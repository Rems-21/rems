<?php
declare(strict_types=1);

$pageTitle = 'Messages & Inscriptions · Dr Remus Operations';
$pageTitleH1 = 'Messages & Inscriptions';
$pageSubtitle = 'Suivi des candidatures, réservations de sessions et messages reçus via le portfolio';

require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();

// Export CSV si demandé
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $pdo) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="messages_dr_remus_' . date('Y-m-d') . '.csv"');
    
    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date', 'Formation / Type', 'Prénom', 'Nom', 'Email', 'WhatsApp', 'Statut', 'Message', 'IP'], ';');

    $stmt = $pdo->query("SELECT * FROM `reservations` ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['formation_titre'],
            $row['prenom'],
            $row['nom'],
            $row['email'],
            $row['whatsapp'],
            $row['statut'],
            $row['message'],
            $row['ip_address']
        ], ';');
    }
    fclose($output);
    exit;
}

// Filtre de statut et recherche
$filterStatus = trim((string)($_GET['status'] ?? 'all'));
$search = trim((string)($_GET['q'] ?? ''));

$reservations = [];
$countTotal = 0;
$countNouvelles = 0;
$countConfirmees = 0;
$countTerminees = 0;

if ($pdo) {
    // Calcul des KPI globaux
    try {
        $stmtStats = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'nouvelle' THEN 1 ELSE 0 END) as nouvelles,
            SUM(CASE WHEN statut = 'confirmee' THEN 1 ELSE 0 END) as confirmees,
            SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) as terminees
            FROM `reservations`");
        if ($statsRow = $stmtStats->fetch()) {
            $countTotal = (int)$statsRow['total'];
            $countNouvelles = (int)$statsRow['nouvelles'];
            $countConfirmees = (int)$statsRow['confirmees'];
            $countTerminees = (int)$statsRow['terminees'];
        }
    } catch(Exception $e) {}

    // Requête filtrée
    $where = [];
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['nouvelle', 'confirmee', 'terminee', 'annulee'], true)) {
        $where[] = "statut = ?";
        $params[] = $filterStatus;
    }

    if ($search !== '') {
        $where[] = "(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR whatsapp LIKE ? OR formation_titre LIKE ? OR message LIKE ?)";
        $term = "%{$search}%";
        $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
    }

    $sql = "SELECT * FROM `reservations`";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/layout_top.php';
?>

<!-- ── 1. KPI BANNER MESSAGES ────────────────────────────────────── -->
<div class="adm-kpi-grid">
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      </div>
      <span class="adm-kpi-label">Total Messages &amp; Inscriptions</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val"><?= $countTotal ?></div>
        <div class="adm-trend-sub">Reçus en base de données</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 18 L18 10 L34 14 L50 6 L66 12" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-orange);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <span class="adm-kpi-label">Nouvelles Demandes</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-orange);"><?= $countNouvelles ?></div>
        <div class="adm-trend-pill">
          <span class="adm-badge-dot"></span> En attente de traitement
        </div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 14 L18 18 L34 8 L50 16 L66 6" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-blue);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span class="adm-kpi-label">Confirmées</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-blue);"><?= $countConfirmees ?></div>
        <div class="adm-trend-sub">Dossiers validés</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 16 L18 12 L34 10 L50 8 L66 4" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-green);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <span class="adm-kpi-label">Terminées</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-green);"><?= $countTerminees ?></div>
        <div class="adm-trend-sub">Réponses envoyées</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 20 L18 16 L34 12 L50 8 L66 4" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<!-- ── 2. BARRE D'ACTIONS ET DE FILTRES UNIFIÉE ───────────────────── -->
<div class="adm-card">
  <div class="adm-card-body" style="padding:14px 18px;">
    <div class="adm-filter-bar">
      <!-- Filtres rapides par statut (Pills) -->
      <div class="adm-filter-group">
        <a href="reservations.php?status=all" class="adm-btn <?= $filterStatus === 'all' ? 'adm-btn-primary' : 'adm-btn-ghost' ?> adm-btn-sm">
          Toutes (<?= $countTotal ?>)
        </a>
        <a href="reservations.php?status=nouvelle" class="adm-btn <?= $filterStatus === 'nouvelle' ? 'adm-btn-primary' : 'adm-btn-ghost' ?> adm-btn-sm" style="<?= $filterStatus !== 'nouvelle' ? 'color:var(--adm-accent-orange);' : '' ?>">
          ● Nouvelles (<?= $countNouvelles ?>)
        </a>
        <a href="reservations.php?status=confirmee" class="adm-btn <?= $filterStatus === 'confirmee' ? 'adm-btn-primary' : 'adm-btn-ghost' ?> adm-btn-sm" style="<?= $filterStatus !== 'confirmee' ? 'color:var(--adm-accent-blue);' : '' ?>">
          ● Confirmées (<?= $countConfirmees ?>)
        </a>
        <a href="reservations.php?status=terminee" class="adm-btn <?= $filterStatus === 'terminee' ? 'adm-btn-primary' : 'adm-btn-ghost' ?> adm-btn-sm" style="<?= $filterStatus !== 'terminee' ? 'color:var(--adm-accent-green);' : '' ?>">
          ✓ Terminées (<?= $countTerminees ?>)
        </a>
        <a href="reservations.php?status=annulee" class="adm-btn <?= $filterStatus === 'annulee' ? 'adm-btn-primary' : 'adm-btn-ghost' ?> adm-btn-sm">
          ✕ Annulées
        </a>
      </div>

      <!-- Recherche et Export CSV -->
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <form method="GET" action="reservations.php" class="adm-search-form">
          <?php if ($filterStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
          <?php endif; ?>
          <input type="text" name="q" class="adm-input" placeholder="Rechercher nom, email..." value="<?= htmlspecialchars($search) ?>" style="width:200px; padding:6px 12px; font-size:12px;">
          <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Filtrer</button>
        </form>

        <a href="reservations.php?export=csv" class="adm-btn adm-btn-ghost adm-btn-sm" title="Télécharger le fichier CSV">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Exporter CSV
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ── 3. TABLEAU DES MESSAGES & CANDIDATURES ─────────────────────── -->
<div class="adm-card">
  <div class="adm-card-header">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Liste des Inscriptions &amp; Demandes de Contact
    </span>
    <span style="font-size:12px; color:var(--adm-text-muted);"><?= count($reservations) ?> résultat(s)</span>
  </div>

  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead>
        <tr>
          <th style="width:50px;">ID</th>
          <th style="min-width:110px;">Date</th>
          <th style="min-width:200px;">Candidat / Expéditeur</th>
          <th style="min-width:180px;">Formation / Objet</th>
          <th style="min-width:260px;">Message &amp; Attentes</th>
          <th style="min-width:140px;">Statut</th>
          <th style="width:120px; text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($reservations)): ?>
          <?php foreach ($reservations as $r): ?>
            <?php
              $cleanPhone = preg_replace('/[^0-9]/', '', (string)$r['whatsapp']);
              $waUrl = !empty($cleanPhone) && strlen($cleanPhone) >= 8 ? "https://wa.me/{$cleanPhone}" : "";
              $statusClass = 'adm-badge--' . htmlspecialchars($r['statut']);
              $initials = strtoupper(mb_substr((string)$r['prenom'], 0, 1) . mb_substr((string)($r['nom'] ?? 'X'), 0, 1));
            ?>
            <tr id="resa-row-<?= $r['id'] ?>">
              <td style="color:#71717a; font-family:monospace; font-size:11px;">#<?= $r['id'] ?></td>
              <td style="color:var(--adm-text-muted); font-size:11.5px; white-space:nowrap;">
                <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:9px;">
                  <div style="width:28px; height:28px; border-radius:50%; background:#1c202a; border:1px solid rgba(255,255,255,0.1); font-size:10px; font-weight:700; color:#ffffff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <?= $initials ?>
                  </div>
                  <div>
                    <strong style="font-size:13px; color:#ffffff; display:block;"><?= htmlspecialchars($r['prenom'] . ' ' . ($r['nom'] ?? '')) ?></strong>
                    <a href="mailto:<?= htmlspecialchars($r['email']) ?>" style="color:var(--adm-accent-blue); font-size:11px; text-decoration:none;">
                      <?= htmlspecialchars($r['email']) ?>
                    </a>
                    <?php if ($waUrl): ?>
                      <div style="margin-top:2px;">
                        <a href="<?= $waUrl ?>" target="_blank" rel="noopener" style="color:var(--adm-accent-green); font-size:11px; display:inline-flex; align-items:center; gap:3px;">
                          <span>💬 WhatsApp : <?= htmlspecialchars((string)$r['whatsapp']) ?></span>
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-weight:600; color:#ffffff; font-size:12.5px;">
                  <?= htmlspecialchars($r['formation_titre']) ?>
                </div>
              </td>
              <td style="font-size:12px; color:#c7c9cf; line-height:1.45;">
                <?php if (!empty($r['message'])): ?>
                  <?= nl2br(htmlspecialchars($r['message'])) ?>
                <?php else: ?>
                  <span style="color:#52525b; font-style:italic;">Aucun message textuel joint</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; flex-direction:column; gap:5px;">
                  <span class="adm-badge <?= $statusClass ?> js-status-badge">
                    <span class="adm-badge-dot"></span>
                    <?= ucfirst($r['statut']) ?>
                  </span>

                  <!-- Sélecteur rapide de statut -->
                  <select class="adm-select js-select-resa-status" data-id="<?= $r['id'] ?>" style="padding:3px 6px; font-size:11px;">
                    <option value="nouvelle" <?= $r['statut'] === 'nouvelle' ? 'selected' : '' ?>>Nouvelle</option>
                    <option value="confirmee" <?= $r['statut'] === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                    <option value="terminee" <?= $r['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                    <option value="annulee" <?= $r['statut'] === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                  </select>
                </div>
              </td>
              <td style="text-align:right;">
                <?php if ($r['statut'] !== 'terminee'): ?>
                  <button type="button" class="adm-btn adm-btn-success adm-btn-sm js-btn-complete-resa" data-id="<?= $r['id'] ?>">
                    ✓ Terminer
                  </button>
                <?php else: ?>
                  <span style="color:var(--adm-accent-green); font-size:11.5px; font-weight:700;">Traité</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:36px; color:var(--adm-text-muted);">
              Aucune inscription ni message ne correspond aux critères sélectionnés.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
