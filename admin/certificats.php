<?php
declare(strict_types=1);

$pageTitle = 'Certifications & Diplômes · Dr Remus Operations';
$pageTitleH1 = 'Certifications & Diplômes';
$pageSubtitle = 'Registre public officiel des attestations et diplômes délivrés aux apprenants';

require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getDatabaseConnection();

$filterStatus = trim((string)($_GET['status'] ?? 'all'));
$search = trim((string)($_GET['q'] ?? ''));

$certificatsList = [];
$countTotal = 0;
$countValides = 0;
$countMentions = 0;
$countRevoques = 0;

// Récupérer la liste des formations disponibles pour le sélecteur
$formationsList = [];
if ($pdo) {
    try {
        $stmtForm = $pdo->query("SELECT titre FROM formations ORDER BY id ASC");
        $formationsList = $stmtForm->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Exception $e) {}

    try {
        $stmtStats = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'valide' THEN 1 ELSE 0 END) as valides,
            SUM(CASE WHEN mention LIKE '%Très Bien%' OR mention LIKE '%Félicitations%' THEN 1 ELSE 0 END) as mentions,
            SUM(CASE WHEN statut = 'revoque' THEN 1 ELSE 0 END) as revoques
            FROM `certificats`");
        if ($statsRow = $stmtStats->fetch()) {
            $countTotal = (int)$statsRow['total'];
            $countValides = (int)$statsRow['valides'];
            $countMentions = (int)$statsRow['mentions'];
            $countRevoques = (int)$statsRow['revoques'];
        }
    } catch(Exception $e) {}

    $where = [];
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['valide', 'revoque', 'archive'], true)) {
        $where[] = "statut = ?";
        $params[] = $filterStatus;
    }

    if ($search !== '') {
        $where[] = "(cert_id LIKE ? OR nom_apprenant LIKE ? OR email_apprenant LIKE ? OR formation_titre LIKE ? OR competences LIKE ?)";
        $term = "%{$search}%";
        $params = array_merge($params, [$term, $term, $term, $term, $term]);
    }

    $sql = "SELECT * FROM `certificats`";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY date_emission DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $certificatsList = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/layout_top.php';
?>

<!-- ── 1. KPI BANNER CERTIFICATS ─────────────────────────────────── -->
<div class="adm-kpi-grid">
  <div class="adm-kpi-card">
    <div class="adm-kpi-head">
      <span class="adm-kpi-label">TOTAL CERTIFICATS</span>
      <span class="adm-kpi-icon" style="color:var(--adm-accent-yellow);">🎓</span>
    </div>
    <div class="adm-kpi-val"><?= $countTotal ?></div>
    <div class="adm-kpi-sub">Titres émis au registre officiel</div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-head">
      <span class="adm-kpi-label">CERTIFICATS VALIDES</span>
      <span class="adm-kpi-icon" style="color:var(--adm-accent-green);">✓</span>
    </div>
    <div class="adm-kpi-val" style="color:var(--adm-accent-green);"><?= $countValides ?></div>
    <div class="adm-kpi-sub">En règle &amp; consultables publiquement</div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-head">
      <span class="adm-kpi-label">MENTIONS D'EXCELLENCE</span>
      <span class="adm-kpi-icon" style="color:var(--adm-accent-blue);">★</span>
    </div>
    <div class="adm-kpi-val"><?= $countMentions ?></div>
    <div class="adm-kpi-sub">Très Bien / Félicitations du Jury</div>
  </div>

  <div class="adm-kpi-card">
    <div class="adm-kpi-head">
      <span class="adm-kpi-label">RÉVOQUÉS / INVALIDÉS</span>
      <span class="adm-kpi-icon" style="color:var(--adm-accent-red);">⚠</span>
    </div>
    <div class="adm-kpi-val" style="<?= $countRevoques > 0 ? 'color:var(--adm-accent-red);' : '' ?>"><?= $countRevoques ?></div>
    <div class="adm-kpi-sub">Annulés pour non-conformité</div>
  </div>
</div>

<!-- ── 2. BARRE D'ACTIONS ET OUTILS ──────────────────────────────── -->
<div class="adm-card" style="margin-bottom: 20px;">
  <div class="adm-card-head" style="flex-wrap:wrap; gap:14px;">
    <div>
      <h2 class="adm-card-title">Registre des Titres et Diplômes</h2>
      <p class="adm-card-desc">Générez un certificat officiel avec empreinte cryptographique vérifiable en direct sur /verify.php.</p>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <a href="../verify.php" target="_blank" class="adm-btn adm-btn-secondary">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Page de Vérification Publique
      </a>
      <button type="button" class="adm-btn adm-btn-primary" id="btnOpenNewCertModal">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Délivrer un Certificat
      </button>
    </div>
  </div>

  <!-- Filtres et Recherche -->
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:14px; padding-top:14px; border-top:1px solid var(--adm-border-color);">
    <div class="adm-filter-pills">
      <a href="certificats.php?status=all<?= $search !== '' ? '&q='.urlencode($search) : '' ?>" class="adm-pill-btn <?= $filterStatus === 'all' ? 'active' : '' ?>">Tous (<?= $countTotal ?>)</a>
      <a href="certificats.php?status=valide<?= $search !== '' ? '&q='.urlencode($search) : '' ?>" class="adm-pill-btn <?= $filterStatus === 'valide' ? 'active' : '' ?>">Valides (<?= $countValides ?>)</a>
      <a href="certificats.php?status=revoque<?= $search !== '' ? '&q='.urlencode($search) : '' ?>" class="adm-pill-btn <?= $filterStatus === 'revoque' ? 'active' : '' ?>">Révoqués (<?= $countRevoques ?>)</a>
    </div>

    <form method="GET" action="certificats.php" style="display:flex; gap:8px;">
      <?php if ($filterStatus !== 'all'): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher apprenant, ID, formation..." class="adm-input" style="width:250px; font-size:12px; padding:6px 12px;">
      <button type="submit" class="adm-btn adm-btn-secondary" style="padding:6px 12px;">Filtrer</button>
      <?php if ($search !== '' || $filterStatus !== 'all'): ?>
        <a href="certificats.php" class="adm-btn adm-btn-sm" style="align-self:center;">Effacer</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ── 3. TABLEAU DES CERTIFICATS ────────────────────────────────── -->
<div class="adm-card">
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead>
        <tr>
          <th>RÉFÉRENCE &amp; DATE</th>
          <th>APPRENANT</th>
          <th>FORMATION &amp; COHORTE</th>
          <th>MENTION &amp; COMPÉTENCES</th>
          <th>STATUT</th>
          <th style="text-align:right;">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($certificatsList)): ?>
          <?php foreach ($certificatsList as $c): ?>
            <?php
              $statusBadgeClass = match($c['statut']) {
                  'valide'   => 'adm-badge-success',
                  'revoque'  => 'adm-badge-danger',
                  'archive'  => 'adm-badge-secondary',
                  default    => 'adm-badge-info'
              };
            ?>
            <tr id="cert-row-<?= $c['id'] ?>">
              <td>
                <div style="font-weight:800; color:var(--adm-accent-yellow); font-family:'JetBrains Mono',monospace; font-size:13px;">
                  <a href="../verify.php?cert=<?= urlencode($c['cert_id']) ?>" target="_blank" style="color:inherit; text-decoration:none;" title="Voir le certificat officiel">
                    <?= htmlspecialchars($c['cert_id']) ?> ↗
                  </a>
                </div>
                <div style="font-size:11px; color:#71717a; margin-top:2px;">
                  Émis le <?= date('d/m/Y', strtotime($c['date_emission'])) ?>
                </div>
              </td>
              <td>
                <div style="font-weight:700; color:#ffffff; font-size:13px;">
                  <?= htmlspecialchars($c['nom_apprenant']) ?>
                </div>
                <?php if (!empty($c['email_apprenant'])): ?>
                  <div style="font-size:11.5px; color:#a1a1aa; margin-top:2px;">
                    <a href="mailto:<?= htmlspecialchars($c['email_apprenant']) ?>" style="color:#a1a1aa; text-decoration:none;">
                      ✉ <?= htmlspecialchars($c['email_apprenant']) ?>
                    </a>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600; color:#ffffff; font-size:12.5px; max-width:240px;">
                  <?= htmlspecialchars($c['formation_titre']) ?>
                </div>
                <div style="font-size:11px; color:#a1a1aa; margin-top:2px;">
                  Cohorte : <?= htmlspecialchars($c['cohorte'] ?: '2026') ?>
                </div>
              </td>
              <td>
                <span class="adm-badge adm-badge-warning" style="font-size:11px; padding:3px 7px;">
                  ★ <?= htmlspecialchars($c['mention'] ?: 'Validé') ?>
                </span>
                <?php if (!empty($c['competences'])): ?>
                  <div style="font-size:11px; color:#a1a1aa; margin-top:4px; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($c['competences']) ?>">
                    <?= htmlspecialchars($c['competences']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; flex-direction:column; gap:5px;">
                  <span class="adm-badge <?= $statusBadgeClass ?>" id="badge-cert-<?= $c['id'] ?>">
                    <span class="adm-badge-dot"></span>
                    <?= ucfirst($c['statut']) ?>
                  </span>

                  <!-- Sélecteur rapide de statut -->
                  <select class="adm-select js-select-cert-status" data-id="<?= $c['id'] ?>" style="padding:3px 6px; font-size:11px;">
                    <option value="valide" <?= $c['statut'] === 'valide' ? 'selected' : '' ?>>Valide</option>
                    <option value="revoque" <?= $c['statut'] === 'revoque' ? 'selected' : '' ?>>Révoqué</option>
                    <option value="archive" <?= $c['statut'] === 'archive' ? 'selected' : '' ?>>Archivé</option>
                  </select>
                </div>
              </td>
              <td style="text-align:right;">
                <div style="display:flex; justify-content:flex-end; gap:6px;">
                  <a href="../verify.php?cert=<?= urlencode($c['cert_id']) ?>" target="_blank" class="adm-btn adm-btn-secondary adm-btn-sm" title="Consulter la page publique">
                    Voir ↗
                  </a>
                  <button type="button" class="adm-btn adm-btn-danger adm-btn-sm js-btn-delete-cert" data-id="<?= $c['id'] ?>" title="Supprimer ce certificat">
                    ✕
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; padding:36px; color:var(--adm-text-muted);">
              Aucun certificat ne correspond aux critères sélectionnés.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── MODAL CRÉATION / ÉMISSION DE CERTIFICAT ────────────────────── -->
<div id="modalNewCert" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:20px;">
  <div style="background:#14151b; border:1px solid rgba(255,255,255,0.15); border-radius:18px; width:100%; max-width:620px; max-height:92vh; overflow-y:auto; padding:26px; box-shadow:0 25px 60px rgba(0,0,0,0.9); position:relative;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px;">
      <div>
        <h3 style="font-size:1.15rem; font-weight:800; color:#ffffff; margin:0;">Délivrer un Certificat Officiel</h3>
        <p style="font-size:0.78rem; color:#a1a1aa; margin:3px 0 0 0;">Le titre sera instantanément vérifiable en ligne via son ID ou QR code</p>
      </div>
      <button type="button" id="btnCloseNewCertModal" style="background:transparent; border:none; color:#a1a1aa; font-size:1.4rem; cursor:pointer; padding:4px 8px;">✕</button>
    </div>

    <form id="formNewCert">
      <input type="hidden" name="action" value="create">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Nom complet de l'apprenant *</label>
          <input type="text" name="nom_apprenant" required placeholder="Ex : Sarah Kamga" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Email de l'apprenant</label>
          <input type="email" name="email_apprenant" placeholder="sarah.kamga@gmail.com" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Intitulé du cursus / formation *</label>
        <input type="text" name="formation_titre" id="inpFormationTitre" required list="listFormations" placeholder="Ex : Créer un site web moderne avec l'IA" class="adm-input" style="width:100%; box-sizing:border-box;">
        <datalist id="listFormations">
          <?php foreach ($formationsList as $fTitre): ?>
            <option value="<?= htmlspecialchars($fTitre) ?>">
          <?php endforeach; ?>
          <option value="Créer un site web moderne avec l'IA">
          <option value="IA Générative & Automatisation pour Développeurs">
          <option value="Supervision Industrielle SCADA & IoT Web">
        </datalist>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Période de formation</label>
          <input type="text" name="periode" value="15 Juin 2026 – 15 Juillet 2026" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Durée</label>
          <input type="text" name="duree" value="30 heures" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Niveau</label>
          <input type="text" name="niveau" value="Débutant → Intermédiaire" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Mention / Distinction honorifique *</label>
          <input type="text" name="mention" list="listMentions" value="Mention Très Bien" class="adm-input" style="width:100%; box-sizing:border-box;">
          <datalist id="listMentions">
            <option value="Mention Très Bien avec Félicitations du Jury">
            <option value="Mention Très Bien">
            <option value="Mention Bien">
            <option value="Mention Assez Bien">
            <option value="Mention Honorable">
          </datalist>
        </div>
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Cohorte</label>
          <input type="text" name="cohorte" value="Cohorte Juin 2026" placeholder="Cohorte Juin 2026" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Lieu de délivrance</label>
          <input type="text" name="lieu" value="À Douala, Cameroun" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
        <div>
          <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Date d'émission</label>
          <input type="date" name="date_emission" value="<?= date('Y-m-d') ?>" class="adm-input" style="width:100%; box-sizing:border-box;">
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Description des compétences acquises</label>
        <textarea name="description_cert" rows="2" class="adm-input" style="width:100%; box-sizing:border-box; font-size:12px; resize:vertical;">Cette formation a couvert les notions essentielles et les compétences pratiques pour concevoir, développer et déployer un site web professionnel à l'aide des outils d'IA.</textarea>
      </div>

      <div style="margin-bottom:14px;">
        <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">Compétences &amp; Outils validés (séparés par des virgules)</label>
        <input type="text" name="competences" placeholder="Ex: React.js, Tailwind CSS, Claude 3.5 Sonnet, CI/CD, Git" class="adm-input" style="width:100%; box-sizing:border-box;">
      </div>

      <div style="margin-bottom:20px;">
        <label class="adm-label" style="display:block; font-size:11.5px; color:#a1a1aa; margin-bottom:5px; text-transform:uppercase; font-weight:700;">N° de Certificat Personnalisé (optionnel)</label>
        <input type="text" name="cert_id" placeholder="Ex: CERT-2026-0158 (laisser vide pour auto)" class="adm-input" style="width:100%; box-sizing:border-box; font-family:'JetBrains Mono',monospace;">
        <div style="font-size:11px; color:#71717a; margin-top:3px;">Si laissé vide, un identifiant officiel au format CERT-2026-XXXX sera généré automatiquement.</div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" id="btnCancelNewCert" class="adm-btn adm-btn-secondary">Annuler</button>
        <button type="submit" id="btnSubmitNewCert" class="adm-btn adm-btn-primary">
          Émettre le certificat 🎓
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  function showToast(msg, isSuccess) {
    var toast = document.getElementById('gToast');
    var txt = document.getElementById('gToastMsg');
    if (toast && txt) {
      txt.textContent = msg;
      toast.style.borderColor = isSuccess ? '#10b981' : '#ef4444';
      toast.classList.add('show');
      setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }
  }

  // Modal Émission de certificat
  var modal = document.getElementById('modalNewCert');
  var openBtn = document.getElementById('btnOpenNewCertModal');
  var closeBtn = document.getElementById('btnCloseNewCertModal');
  var cancelBtn = document.getElementById('btnCancelNewCert');
  var form = document.getElementById('formNewCert');

  function openModal() {
    if (modal) modal.style.display = 'flex';
  }
  function closeModal() {
    if (modal) modal.style.display = 'none';
  }

  if (openBtn) openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

  // Soumission du formulaire
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var btnSubmit = document.getElementById('btnSubmitNewCert');
      btnSubmit.disabled = true;
      btnSubmit.textContent = 'Génération en cours...';

      var fd = new FormData(form);

      fetch('api/update-certificate.php', { method: 'POST', body: fd })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Émettre le certificat 🎓';
        if (data.success) {
          showToast(data.message, true);
          closeModal();
          location.reload();
        } else {
          showToast(data.message || "Erreur de création", false);
        }
      })
      .catch(function() {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Émettre le certificat 🎓';
        showToast("Erreur de connexion au serveur", false);
      });
    });
  }

  // Changement de statut via dropdown
  document.querySelectorAll('.js-select-cert-status').forEach(function(sel) {
    sel.addEventListener('change', function() {
      var id = this.dataset.id;
      var newStatut = this.value;

      var fd = new FormData();
      fd.append('id', id);
      fd.append('statut', newStatut);
      fd.append('action', 'update_status');

      fetch('api/update-certificate.php', { method: 'POST', body: fd })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          showToast("Statut mis à jour : " + newStatut, true);
          var badge = document.getElementById('badge-cert-' + id);
          if (badge) {
            badge.className = 'adm-badge';
            if (newStatut === 'valide') badge.classList.add('adm-badge-success');
            else if (newStatut === 'revoque') badge.classList.add('adm-badge-danger');
            else badge.classList.add('adm-badge-secondary');
            badge.innerHTML = '<span class="adm-badge-dot"></span> ' + newStatut.charAt(0).toUpperCase() + newStatut.slice(1);
          }
        } else {
          showToast(data.message, false);
        }
      });
    });
  });

  // Suppression
  document.querySelectorAll('.js-btn-delete-cert').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm("Voulez-vous vraiment supprimer ce certificat du registre ?")) return;
      var id = this.dataset.id;

      var fd = new FormData();
      fd.append('id', id);
      fd.append('action', 'delete');

      fetch('api/update-certificate.php', { method: 'POST', body: fd })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          showToast("Certificat supprimé", true);
          var row = document.getElementById('cert-row-' + id);
          if (row) row.remove();
        } else {
          showToast(data.message, false);
        }
      });
    });
  });
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
