<?php
declare(strict_types=1);

$pageTitle = 'Formations & Places · Dr Remus Operations';
$pageTitleH1 = 'Formations & Cohortes';
$pageSubtitle = 'Configuration des cohortes, suivi des sessions et ajustement des places disponibles';

require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();

$message = '';
$messageType = 'success';

// ── TRAITEMENT DE CRÉATION D'UNE FORMATION ─────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $pdo) {
    $titre = trim((string)($_POST['titre'] ?? ''));
    $badge = trim((string)($_POST['badge'] ?? 'COHORTE'));
    $description = trim((string)($_POST['description'] ?? ''));
    $dateDebut = trim((string)($_POST['date_debut'] ?? 'Date à venir'));
    $duree = trim((string)($_POST['duree'] ?? '4 semaines'));
    $placesTotal = max(1, (int)($_POST['places_total'] ?? 10));
    $placesDispo = max(0, (int)($_POST['places_disponibles'] ?? $placesTotal));
    $statut = trim((string)($_POST['statut'] ?? 'ouvert'));
    $code = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $titre)) . '-' . time();

    if ($titre !== '') {
        $ins = $pdo->prepare("INSERT INTO `formations` 
            (code, titre, badge, description, date_debut, duree, places_total, places_disponibles, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$code, $titre, $badge, $description, $dateDebut, $duree, $placesTotal, $placesDispo, $statut]);
        $message = 'Formation ajoutée avec succès.';
    } else {
        $message = 'Le titre de la formation est requis.';
        $messageType = 'error';
    }
}

// ── RÉCUPÉRATION DE TOUTES LES FORMATIONS & CALCUL KPI ─────────────
$formations = [];
$totalFormations = 0;
$totalSeats = 0;
$availableSeats = 0;
$activeCohortTitle = 'Aucune';

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `formations` ORDER BY is_active_cohort DESC, id ASC");
    $formations = $stmt->fetchAll();
    $totalFormations = count($formations);

    foreach ($formations as $f) {
        $totalSeats += (int)$f['places_total'];
        $availableSeats += (int)$f['places_disponibles'];
        if (!empty($f['is_active_cohort'])) {
            $activeCohortTitle = $f['titre'];
        }
    }
}

$bookedSeats = max(0, $totalSeats - $availableSeats);
$fillRate = $totalSeats > 0 ? (int)round(($bookedSeats / $totalSeats) * 100) : 0;

require_once __DIR__ . '/includes/layout_top.php';
?>

<!-- Message d'alerte s'il y a lieu -->
<?php if (!empty($message)): ?>
  <div style="background:<?= $messageType === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)' ?>; border:1px solid <?= $messageType === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)' ?>; color:<?= $messageType === 'success' ? '#10b981' : '#ef4444' ?>; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span><?= htmlspecialchars($message) ?></span>
  </div>
<?php endif; ?>

<!-- ── 1. KPI BANNER FORMATIONS ──────────────────────────────────── -->
<div class="adm-kpi-grid">
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
      <span class="adm-kpi-label">Formations Référencées</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val"><?= $totalFormations ?></div>
        <div class="adm-trend-sub">Programmes au catalogue</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 18 L18 12 L34 14 L50 8 L66 10" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-green);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span class="adm-kpi-label">Places Restantes</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-green);"><?= $availableSeats ?></div>
        <div class="adm-trend-pill">
          Sur <?= $totalSeats ?> places totales
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
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <span class="adm-kpi-label">Taux de Remplissage</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-orange);"><?= $fillRate ?>%</div>
        <div class="adm-trend-sub"><?= $bookedSeats ?> participants inscrits</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 12 L18 16 L34 8 L50 14 L66 6" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
      <span class="adm-kpi-label">Cohorte Active</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="font-size:17px; line-height:1.25; margin-bottom:4px; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
          <?= htmlspecialchars($activeCohortTitle) ?>
        </div>
        <div class="adm-trend-pill">
          <span class="adm-badge-dot"></span> Session Principale
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── 2. FORMULAIRE COLLAPSIBLE D'AJOUT D'UNE FORMATION ─────────── -->
<div class="adm-card">
  <div class="adm-card-header" style="cursor:pointer;" onclick="toggleAddFormation()">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Ajouter une nouvelle formation au catalogue
    </span>
    <span class="adm-card-action-link" id="addFormationToggleLabel">
      <span>Afficher le formulaire</span>
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
    </span>
  </div>
  <div class="adm-card-body" id="addFormationCollapse" style="display:none;">
    <form method="POST" action="formations.php" class="adm-grid-form">
      <input type="hidden" name="action" value="create">

      <div>
        <label class="adm-label">Titre de la formation *</label>
        <input type="text" name="titre" class="adm-input" placeholder="Ex: Développer avec l'IA" required>
      </div>

      <div>
        <label class="adm-label">Badge thématique</label>
        <input type="text" name="badge" class="adm-input" placeholder="Ex: DÉVELOPPEMENT, BUSINESS">
      </div>

      <div>
        <label class="adm-label">Date de début</label>
        <input type="text" name="date_debut" class="adm-input" placeholder="Ex: 15 octobre 2026">
      </div>

      <div>
        <label class="adm-label">Durée estimée</label>
        <input type="text" name="duree" class="adm-input" placeholder="Ex: 4 semaines">
      </div>

      <div>
        <label class="adm-label">Places totales</label>
        <input type="number" name="places_total" class="adm-input" value="10" min="1" required>
      </div>

      <div>
        <label class="adm-label">Places disponibles</label>
        <input type="number" name="places_disponibles" class="adm-input" value="10" min="0" required>
      </div>

      <div>
        <label class="adm-label">Statut initial</label>
        <select name="statut" class="adm-select">
          <option value="ouvert">Ouvert</option>
          <option value="prochainement">Prochainement</option>
          <option value="complet">Complet</option>
          <option value="termine">Terminé / Archive</option>
        </select>
      </div>

      <div style="grid-column:1 / -1;">
        <label class="adm-label">Description du programme</label>
        <textarea name="description" class="adm-textarea" rows="2" placeholder="Objectifs pédagogiques et profil apprenant..."></textarea>
      </div>

      <div style="grid-column:1 / -1; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" class="adm-btn adm-btn-ghost" onclick="toggleAddFormation()">Annuler</button>
        <button type="submit" class="adm-btn adm-btn-primary">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Enregistrer la formation
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── 3. TABLEAU DES FORMATIONS ET GESTION DES PLACES ───────────── -->
<div class="adm-card">
  <div class="adm-card-header">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Gestion des Formations &amp; Places Disponibles
    </span>
    <span style="font-size:12px; color:var(--adm-text-muted);"><?= count($formations) ?> session(s)</span>
  </div>

  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead>
        <tr>
          <th style="min-width:110px;">Statut / Badge</th>
          <th style="min-width:240px;">Formation</th>
          <th style="min-width:120px;">Date &amp; Durée</th>
          <th style="width:90px; text-align:center;">Places Tot.</th>
          <th style="width:130px; text-align:center;">Places Restantes</th>
          <th style="min-width:140px;">Statut Session</th>
          <th style="width:110px; text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($formations as $f): ?>
          <tr id="form-row-<?= $f['id'] ?>">
            <td>
              <span class="g-brand-tag" style="margin-bottom:3px; display:inline-block;">
                <?= htmlspecialchars($f['badge'] ?? 'COHORTE') ?>
              </span>
              <?php if (!empty($f['is_active_cohort'])): ?>
                <div style="font-size:10px; font-weight:800; color:var(--adm-accent-green); display:flex; align-items:center; gap:3px;">
                  <span class="adm-badge-dot"></span> ACTIVE
                </div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size:13.5px; font-weight:700; color:#ffffff;">
                <?= htmlspecialchars($f['titre']) ?>
              </div>
              <?php if (!empty($f['description'])): ?>
                <div style="font-size:11.5px; color:var(--adm-text-muted); max-width:320px; margin-top:2px;">
                  <?= htmlspecialchars(mb_strimwidth($f['description'], 0, 80, '...')) ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-size:12px; color:#c7c9cf;">
              <div><?= htmlspecialchars($f['date_debut']) ?></div>
              <div style="font-size:11px; color:var(--adm-text-muted);"><?= htmlspecialchars($f['duree']) ?></div>
            </td>
            <td style="text-align:center;">
              <input type="number" class="adm-input js-input-total-seats" data-id="<?= $f['id'] ?>" value="<?= $f['places_total'] ?>" min="1" style="width:64px; padding:5px 6px; text-align:center; font-weight:700;">
            </td>
            <td style="text-align:center;">
              <!-- Stepper direct - / + pour modifier instantanément les places disponibles -->
              <div class="adm-seats-stepper">
                <button type="button" class="adm-step-btn js-stepper-btn" data-id="<?= $f['id'] ?>" data-delta="-1" title="Diminuer de 1 place">−</button>
                <span class="adm-step-val" id="step-val-<?= $f['id'] ?>"><?= $f['places_disponibles'] ?></span>
                <button type="button" class="adm-step-btn js-stepper-btn" data-id="<?= $f['id'] ?>" data-delta="1" title="Augmenter de 1 place">+</button>
              </div>
            </td>
            <td>
              <select class="adm-select js-select-formation-status" data-id="<?= $f['id'] ?>" style="padding:5px 8px; font-size:12px;">
                <option value="ouvert" <?= $f['statut'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                <option value="prochainement" <?= $f['statut'] === 'prochainement' ? 'selected' : '' ?>>Prochainement</option>
                <option value="complet" <?= $f['statut'] === 'complet' ? 'selected' : '' ?>>Complet</option>
                <option value="termine" <?= $f['statut'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                <option value="archive" <?= $f['statut'] === 'archive' ? 'selected' : '' ?>>Archivé</option>
              </select>
            </td>
            <td style="text-align:right;">
              <button type="button" class="adm-btn adm-btn-primary adm-btn-sm js-btn-save-formation" data-id="<?= $f['id'] ?>">
                Enregistrer
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleAddFormation() {
  const c = document.getElementById('addFormationCollapse');
  const l = document.getElementById('addFormationToggleLabel');
  if (!c) return;
  if (c.style.display === 'none') {
    c.style.display = 'block';
    if (l) l.innerHTML = '<span>Masquer le formulaire</span> <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>';
  } else {
    c.style.display = 'none';
    if (l) l.innerHTML = '<span>Afficher le formulaire</span> <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
  }
}

// Stepper interactif - / +
document.querySelectorAll('.js-stepper-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const delta = parseInt(this.getAttribute('data-delta'), 10);
    const valEl = document.getElementById('step-val-' + id);
    if (!valEl) return;
    let cur = parseInt(valEl.textContent.trim(), 10) || 0;
    cur = Math.max(0, cur + delta);
    valEl.textContent = cur;
  });
});

// Sauvegarde complète d'une ligne formation
document.querySelectorAll('.js-btn-save-formation').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.getAttribute('data-id');
    const row = document.getElementById('form-row-' + id);
    if (!id || !row) return;

    const totalInput = row.querySelector('.js-input-total-seats');
    const statusSelect = row.querySelector('.js-select-formation-status');
    const stepVal = document.getElementById('step-val-' + id);

    const formData = new FormData();
    formData.append('id', id);
    if (totalInput) formData.append('places_total', totalInput.value);
    if (statusSelect) formData.append('statut', statusSelect.value);
    if (stepVal) formData.append('places_disponibles', stepVal.textContent.trim());

    this.disabled = true;
    this.textContent = '...';

    try {
      const res = await fetch('api/update-formation.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data && data.success) {
        showToast('Formation et places mises à jour avec succès !');
      } else {
        showToast(data.message || 'Erreur lors de la sauvegarde.', false);
      }
    } catch (_) {
      showToast('Erreur serveur.', false);
    } finally {
      this.disabled = false;
      this.textContent = 'Enregistrer';
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
