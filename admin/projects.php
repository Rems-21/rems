<?php
declare(strict_types=1);

$pageTitle = 'Gestion des Projets · Dr Remus Operations';
$pageTitleH1 = 'Gestion des Projets';
$pageSubtitle = 'Supervision, réordonnancement et visibilité des réalisations du portfolio';

require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();

$message = '';
$messageType = 'success';

// ── TRAITEMENT DE CRÉATION DIRECTE EN POST ─────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_direct' && $pdo) {
    $title = trim((string)($_POST['title'] ?? ''));
    $badge = trim((string)($_POST['badge'] ?? 'Projet Web'));
    $description = trim((string)($_POST['description'] ?? ''));
    $demoUrl = trim((string)($_POST['demo_url'] ?? '#'));
    $githubUrl = trim((string)($_POST['github_url'] ?? '#'));
    $ordre = max(1, (int)($_POST['ordre'] ?? 1));
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;
    
    // Stack formatée en JSON
    $rawStack = trim((string)($_POST['tech_stack'] ?? ''));
    $stackParts = array_filter(array_map('trim', explode(',', $rawStack)));
    $techStack = json_encode(array_values($stackParts), JSON_UNESCAPED_UNICODE);
    
    $previewSvg = trim((string)($_POST['preview_svg'] ?? ''));

    if ($title !== '') {
        $ins = $pdo->prepare("INSERT INTO `projects` 
            (ordre, badge, title, description, demo_url, github_url, tech_stack, preview_svg, is_visible)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $ordre, $badge, $title, $description,
            $demoUrl !== '' ? $demoUrl : '#',
            $githubUrl !== '' ? $githubUrl : '#',
            $techStack, $previewSvg, $isVisible
        ]);
        $message = 'Nouveau projet ajouté avec succès au portfolio !';
    } else {
        $message = 'Le titre du projet est obligatoire.';
        $messageType = 'error';
    }
}

// ── RÉCUPÉRATION DE TOUS LES PROJETS ───────────────────────────────
$projects = [];
$totalProjects = 0;
$visibleProjects = 0;
$hiddenProjects = 0;
$allTechs = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `projects` ORDER BY `ordre` ASC, `id` ASC");
    $projects = $stmt->fetchAll();
    $totalProjects = count($projects);

    foreach ($projects as $p) {
        if (!empty($p['is_visible'])) {
            $visibleProjects++;
        } else {
            $hiddenProjects++;
        }

        $stk = json_decode($p['tech_stack'] ?? '[]', true);
        if (is_array($stk)) {
            foreach ($stk as $t) {
                $allTechs[trim((string)$t)] = true;
            }
        }
    }
}

require_once __DIR__ . '/includes/layout_top.php';
?>

<!-- Message d'alerte s'il y a lieu -->
<?php if (!empty($message)): ?>
  <div style="background:<?= $messageType === 'success' ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)' ?>; border:1px solid <?= $messageType === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)' ?>; color:<?= $messageType === 'success' ? '#10b981' : '#ef4444' ?>; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span><?= htmlspecialchars($message) ?></span>
  </div>
<?php endif; ?>

<!-- ── 1. KPI BANNER TILES ────────────────────────────────────────── -->
<div class="adm-kpi-grid">
  <!-- Total Projets -->
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      </div>
      <span class="adm-kpi-label">Total Réalisations</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val"><?= $totalProjects ?></div>
        <div class="adm-trend-sub">Enregistrés en base MySQL</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 20 L18 14 L34 18 L50 8 L66 12" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <!-- Projets Actifs en Ligne -->
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-green);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span class="adm-kpi-label">Projets en Ligne</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-green);"><?= $visibleProjects ?></div>
        <div class="adm-trend-pill">
          <span class="adm-badge-dot"></span> Visibles sur le site
        </div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 18 L18 16 L34 10 L50 12 L66 4" stroke="#10b981" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <!-- Projets Masqués -->
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon" style="color:var(--adm-accent-orange);">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <span class="adm-kpi-label">Projets Masqués</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val" style="color:var(--adm-accent-orange);"><?= $hiddenProjects ?></div>
        <div class="adm-trend-sub">Brouillons / Archivés</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 8 L18 12 L34 16 L50 14 L66 22" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <!-- Technologies Référencées -->
  <div class="adm-kpi-card">
    <div class="adm-kpi-top">
      <div class="adm-kpi-icon">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      </div>
      <span class="adm-kpi-label">Stack Technique</span>
    </div>
    <div class="adm-kpi-main">
      <div>
        <div class="adm-kpi-val"><?= count($allTechs) ?></div>
        <div class="adm-trend-sub">Technologies uniques</div>
      </div>
      <div class="adm-kpi-sparkline">
        <svg width="68" height="26" viewBox="0 0 68 26" fill="none">
          <path d="M2 14 L18 6 L34 12 L50 4 L66 8" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>
</div>

<!-- ── 2. FORMULAIRE COLLAPSIBLE : AJOUTER UN PROJET ──────────────── -->
<div class="adm-card">
  <div class="adm-card-header" style="cursor:pointer;" onclick="toggleAddCollapse()">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Ajouter un nouveau projet au portfolio
    </span>
    <span class="adm-card-action-link" id="addCollapseToggleLabel">
      <span>Afficher le formulaire</span>
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
    </span>
  </div>
  <div class="adm-card-body" id="addProjectCollapse" style="display:none;">
    <form method="POST" action="projects.php" class="adm-grid-form">
      <input type="hidden" name="action" value="create_direct">

      <div>
        <label class="adm-label">Titre du projet *</label>
        <input type="text" name="title" class="adm-input" placeholder="Ex: Superviseur SCADA Cloud" required>
      </div>

      <div>
        <label class="adm-label">Badge / Catégorie</label>
        <input type="text" name="badge" class="adm-input" placeholder="Ex: SCADA & IoT, SaaS / Web, IA..." value="Projet Web">
      </div>

      <div>
        <label class="adm-label">Ordre d'affichage</label>
        <input type="number" name="ordre" class="adm-input" value="<?= $totalProjects + 1 ?>" min="1" required>
      </div>

      <div>
        <label class="adm-label">Visibilité immédiate</label>
        <select name="is_visible" class="adm-select">
          <option value="1">Visible en direct sur le site</option>
          <option value="0">Masqué (brouillon)</option>
        </select>
      </div>

      <div style="grid-column:1 / -1;">
        <label class="adm-label">Description détaillée *</label>
        <textarea name="description" class="adm-textarea" rows="3" placeholder="Exposez la problématique résolue, la valeur ajoutée et les résultats..." required></textarea>
      </div>

      <div>
        <label class="adm-label">Lien Démo / En Ligne (URL)</label>
        <input type="text" name="demo_url" class="adm-input" placeholder="https://monprojet.com ou #" value="#">
      </div>

      <div>
        <label class="adm-label">Lien Code Source (GitHub)</label>
        <input type="text" name="github_url" class="adm-input" placeholder="https://github.com/moncompte/repo ou #" value="#">
      </div>

      <div style="grid-column:1 / -1;">
        <label class="adm-label">Stack technique (séparée par des virgules)</label>
        <input type="text" name="tech_stack" class="adm-input" placeholder="React, Node.js, MQTT, Docker, InfluxDB">
        <span style="font-size:11px; color:var(--adm-text-muted); margin-top:4px; display:block;">Les icônes monochromes correspondantes seront automatiquement générées.</span>
      </div>

      <div style="grid-column:1 / -1;">
        <label class="adm-label">Aperçu Vectoriel SVG (optionnel ou schéma)</label>
        <textarea name="preview_svg" class="adm-textarea" rows="4" placeholder="<svg viewBox='0 0 800 220' ...>...</svg>" style="font-family:monospace; font-size:11.5px;"></textarea>
      </div>

      <div style="grid-column:1 / -1; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" class="adm-btn adm-btn-ghost" onclick="toggleAddCollapse()">Annuler</button>
        <button type="submit" class="adm-btn adm-btn-primary">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Créer &amp; Publier le projet
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── 3. LISTE DES PROJETS DU PORTFOLIO (TABLE UNIFIÉE) ─────────── -->
<div class="adm-card">
  <div class="adm-card-header">
    <span class="adm-card-title">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
      Réalisations Actuelles Injectées dans le Portfolio
    </span>
    <span style="font-size:12px; color:var(--adm-text-muted);"><?= $totalProjects ?> projet(s) en base</span>
  </div>

  <div class="adm-table-wrap">
    <table class="adm-table" id="projectsTable">
      <thead>
        <tr>
          <th style="width:64px; text-align:center;">Ordre</th>
          <th style="min-width:200px;">Projet &amp; Catégorie</th>
          <th style="min-width:240px;">Description</th>
          <th style="min-width:180px;">Technologies</th>
          <th style="min-width:120px;">Liens</th>
          <th style="width:110px; text-align:center;">Visibilité</th>
          <th style="width:130px; text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($projects as $p): ?>
          <?php 
            $stack = json_decode($p['tech_stack'] ?? '[]', true);
            if (!is_array($stack)) $stack = [];
            $isVisible = !empty($p['is_visible']);
          ?>
          <tr id="proj-row-<?= $p['id'] ?>">
            <td style="text-align:center;">
              <input type="number" class="adm-input js-input-proj-order" data-id="<?= $p['id'] ?>" value="<?= (int)$p['ordre'] ?>" min="1" style="width:54px; padding:4px 6px; text-align:center; font-weight:700;">
            </td>
            <td>
              <span class="g-brand-tag" style="margin-bottom:4px; display:inline-block;">
                <?= htmlspecialchars($p['badge'] ?? 'PROJET') ?>
              </span>
              <div style="font-size:13px; font-weight:700; color:#ffffff;" class="js-proj-title">
                <?= htmlspecialchars($p['title']) ?>
              </div>
            </td>
            <td style="font-size:12px; color:#c7c9cf; line-height:1.45;">
              <span class="js-proj-desc"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 110, '...')) ?></span>
            </td>
            <td>
              <div style="display:flex; flex-wrap:wrap; gap:4px;">
                <?php foreach ($stack as $tag): ?>
                  <span style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:5px; padding:2px 7px; font-size:11px; color:#e2e8f0; font-weight:600;">
                    <?= htmlspecialchars((string)$tag) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </td>
            <td style="font-size:11.5px;">
              <div style="display:flex; flex-direction:column; gap:4px;">
                <?php if (!empty($p['demo_url']) && $p['demo_url'] !== '#'): ?>
                  <a href="<?= htmlspecialchars($p['demo_url']) ?>" target="_blank" rel="noopener" style="color:var(--adm-accent-blue); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Démo live ↗
                  </a>
                <?php else: ?>
                  <span style="color:#6e7681;">Démo : '#'</span>
                <?php endif; ?>

                <?php if (!empty($p['github_url']) && $p['github_url'] !== '#'): ?>
                  <a href="<?= htmlspecialchars($p['github_url']) ?>" target="_blank" rel="noopener" style="color:var(--adm-text-muted); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                    GitHub ↗
                  </a>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:center;">
              <button type="button" class="adm-btn adm-btn-sm js-btn-toggle-proj-visibility <?= $isVisible ? 'adm-btn-success' : 'adm-btn-ghost' ?>" data-id="<?= $p['id'] ?>" data-visible="<?= $isVisible ? '1' : '0' ?>" style="min-width:76px; font-size:11px; padding:3px 8px;">
                <?= $isVisible ? '✓ Visible' : 'Masqué' ?>
              </button>
            </td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:6px;">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm js-btn-edit-proj" 
                  data-id="<?= $p['id'] ?>"
                  data-title="<?= htmlspecialchars($p['title']) ?>"
                  data-badge="<?= htmlspecialchars($p['badge'] ?? '') ?>"
                  data-ordre="<?= (int)$p['ordre'] ?>"
                  data-description="<?= htmlspecialchars($p['description'] ?? '') ?>"
                  data-demo="<?= htmlspecialchars($p['demo_url'] ?? '#') ?>"
                  data-github="<?= htmlspecialchars($p['github_url'] ?? '#') ?>"
                  data-stack="<?= htmlspecialchars(implode(', ', $stack)) ?>"
                  data-visible="<?= $isVisible ? '1' : '0' ?>"
                  title="Modifier le projet">
                  <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  Éditer
                </button>
                <button type="button" class="adm-btn adm-btn-danger adm-btn-sm js-btn-delete-proj" data-id="<?= $p['id'] ?>" title="Supprimer définitivement">
                  <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── MODAL D'ÉDITION D'UN PROJET ───────────────────────────────── -->
<div id="editProjModal" class="adm-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(6px); padding:16px;">
  <div class="adm-card" style="width:100%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,0.95); border:1px solid rgba(255,255,255,0.18);">
    <div class="adm-card-header">
      <span class="adm-card-title">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Modifier le projet : <span id="mEditTitleSpan" style="color:#ffffff;"></span>
      </span>
      <button type="button" onclick="closeEditModal()" style="background:transparent; border:none; color:var(--adm-text-muted); font-size:22px; cursor:pointer; line-height:1;">&times;</button>
    </div>
    <div class="adm-card-body">
      <form id="editProjForm" class="adm-grid-form" style="gap:14px;">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="mEditId">

        <div style="grid-column:1 / -1;">
          <label class="adm-label">Titre du projet *</label>
          <input type="text" name="title" id="mEditTitle" class="adm-input" required>
        </div>

        <div>
          <label class="adm-label">Badge / Thématique</label>
          <input type="text" name="badge" id="mEditBadge" class="adm-input" placeholder="Ex: SCADA & IoT">
        </div>

        <div>
          <label class="adm-label">Ordre d'affichage</label>
          <input type="number" name="ordre" id="mEditOrdre" class="adm-input" min="1" required>
        </div>

        <div style="grid-column:1 / -1;">
          <label class="adm-label">Description *</label>
          <textarea name="description" id="mEditDesc" class="adm-textarea" rows="3" required></textarea>
        </div>

        <div>
          <label class="adm-label">Lien Démo (URL)</label>
          <input type="text" name="demo_url" id="mEditDemo" class="adm-input">
        </div>

        <div>
          <label class="adm-label">Lien GitHub (URL)</label>
          <input type="text" name="github_url" id="mEditGithub" class="adm-input">
        </div>

        <div style="grid-column:1 / -1;">
          <label class="adm-label">Stack technique (séparée par des virgules)</label>
          <input type="text" name="tech_stack" id="mEditStack" class="adm-input">
        </div>

        <div style="grid-column:1 / -1;">
          <label class="adm-label">Aperçu Vectoriel SVG (optionnel)</label>
          <textarea name="preview_svg" id="mEditSvg" class="adm-textarea" rows="4" style="font-family:monospace; font-size:11px;"></textarea>
        </div>

        <div style="grid-column:1 / -1; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:6px;">
          <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#e2e8f0; cursor:pointer;">
            <input type="checkbox" name="is_visible" id="mEditVisible" value="1">
            Visible sur le portfolio
          </label>

          <div style="display:flex; gap:10px;">
            <button type="button" onclick="closeEditModal()" class="adm-btn adm-btn-ghost">Annuler</button>
            <button type="submit" class="adm-btn adm-btn-primary" id="mEditSaveBtn">Enregistrer les modifications</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleAddCollapse() {
  const c = document.getElementById('addProjectCollapse');
  const l = document.getElementById('addCollapseToggleLabel');
  if (!c) return;
  if (c.style.display === 'none') {
    c.style.display = 'block';
    if (l) l.innerHTML = '<span>Masquer le formulaire</span> <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>';
  } else {
    c.style.display = 'none';
    if (l) l.innerHTML = '<span>Afficher le formulaire</span> <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
  }
}

function closeEditModal() {
  const modal = document.getElementById('editProjModal');
  if (modal) modal.style.display = 'none';
}

// 1. Bouton Édition -> Remplissage du Modal
document.querySelectorAll('.js-btn-edit-proj').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    document.getElementById('mEditId').value = id;
    document.getElementById('mEditTitleSpan').textContent = this.getAttribute('data-title');
    document.getElementById('mEditTitle').value = this.getAttribute('data-title');
    document.getElementById('mEditBadge').value = this.getAttribute('data-badge');
    document.getElementById('mEditOrdre').value = this.getAttribute('data-ordre');
    document.getElementById('mEditDesc').value = this.getAttribute('data-description');
    document.getElementById('mEditDemo').value = this.getAttribute('data-demo');
    document.getElementById('mEditGithub').value = this.getAttribute('data-github');
    document.getElementById('mEditStack').value = this.getAttribute('data-stack');
    document.getElementById('mEditVisible').checked = this.getAttribute('data-visible') === '1';

    const modal = document.getElementById('editProjModal');
    modal.style.display = 'flex';
  });
});

// 2. Soumission AJAX du Modal d'édition
const editForm = document.getElementById('editProjForm');
if (editForm) {
  editForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const saveBtn = document.getElementById('mEditSaveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Enregistrement...';

    const formData = new FormData(this);
    if (!document.getElementById('mEditVisible').checked) {
      formData.set('is_visible', '0');
    }

    try {
      const res = await fetch('api/update-project.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data && data.success) {
        showToast('Projet mis à jour avec succès !');
        setTimeout(() => window.location.reload(), 600);
      } else {
        showToast(data.message || 'Erreur lors de la mise à jour.', false);
      }
    } catch (_) {
      showToast('Erreur serveur lors de la mise à jour.', false);
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Enregistrer les modifications';
    }
  });
}

// 3. Basculement rapide de Visibilité (Toggle)
document.querySelectorAll('.js-btn-toggle-proj-visibility').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.getAttribute('data-id');
    const curVal = this.getAttribute('data-visible') === '1';
    const newVal = curVal ? '0' : '1';

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('is_visible', newVal);

    this.disabled = true;
    this.textContent = '...';

    try {
      const res = await fetch('api/update-project.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data && data.success) {
        this.setAttribute('data-visible', newVal);
        if (newVal === '1') {
          this.className = 'adm-btn adm-btn-sm js-btn-toggle-proj-visibility adm-btn-success';
          this.textContent = '✓ Visible';
          showToast('Projet activé sur le portfolio !');
        } else {
          this.className = 'adm-btn adm-btn-sm js-btn-toggle-proj-visibility adm-btn-ghost';
          this.textContent = 'Masqué';
          showToast('Projet masqué du portfolio.');
        }
      } else {
        showToast(data.message || 'Erreur lors du changement.', false);
        this.textContent = curVal ? '✓ Visible' : 'Masqué';
      }
    } catch (_) {
      showToast('Erreur de communication serveur.', false);
      this.textContent = curVal ? '✓ Visible' : 'Masqué';
    } finally {
      this.disabled = false;
    }
  });
});

// 4. Changement direct de l'ordre d'un projet
document.querySelectorAll('.js-input-proj-order').forEach(input => {
  input.addEventListener('change', async function() {
    const id = this.getAttribute('data-id');
    const order = this.value;

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('ordre', order);

    try {
      const res = await fetch('api/update-project.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data && data.success) {
        showToast('Ordre d\'affichage mis à jour !');
      } else {
        showToast(data.message || 'Erreur ordre.', false);
      }
    } catch (_) {
      showToast('Erreur serveur.', false);
    }
  });
});

// 5. Suppression Définitive d'un Projet
document.querySelectorAll('.js-btn-delete-proj').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.getAttribute('data-id');
    if (!id) return;

    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement ce projet ?')) {
      return;
    }

    const row = document.getElementById('proj-row-' + id);
    this.disabled = true;

    try {
      const formData = new FormData();
      formData.append('id', id);

      const res = await fetch('api/delete-project.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data && data.success) {
        showToast('Projet supprimé définitivement.');
        if (row) {
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 350);
        }
      } else {
        showToast(data.message || 'Erreur de suppression.', false);
        this.disabled = false;
      }
    } catch (_) {
      showToast('Erreur serveur lors de la suppression.', false);
      this.disabled = false;
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
