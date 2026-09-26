/**
 * JAVASCRIPT ADMIN TEMPS RÉEL (100% PRODUCTION BACKEND) — DR REMUS
 * Connexion directe à l'API MySQL `api/get-stats.php` pour la courbe de trafic,
 * le donut des sources, la gestion des places de cohortes et les actions AJAX.
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── 1. MENU MOBILE TOGGLE AVEC BACKDROP ET CLOSE BTN ────────────────
  const mobileToggle = document.getElementById('admMobileToggle');
  const sidebar = document.getElementById('admSidebar');
  const backdrop = document.getElementById('admSidebarBackdrop');
  const closeBtn = document.getElementById('admSidebarClose');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('is-open');
    if (backdrop) backdrop.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('is-open');
    if (backdrop) backdrop.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (sidebar && sidebar.classList.contains('is-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
      closeSidebar();
    }
  });

  // ── 2. TOAST NOTIFICATIONS ─────────────────────────────────────────
  const toastEl = document.getElementById('gToast');
  const toastMsg = document.getElementById('gToastMsg');
  let toastTimer = null;

  window.showToast = function(msg, isSuccess = true) {
    if (!toastEl || !toastMsg) return;
    toastMsg.textContent = msg;
    toastEl.style.borderLeftColor = isSuccess ? '#10b981' : '#ef4444';
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastEl.classList.remove('show');
    }, 3200);
  };

  // ── 3. MARQUER UNE RÉSERVATION COMME TERMINÉE ───────────────────────
  document.querySelectorAll('.js-btn-complete-resa').forEach(btn => {
    btn.addEventListener('click', async function() {
      const resaId = this.getAttribute('data-id');
      if (!resaId) return;

      const originalText = this.innerHTML;
      this.disabled = true;
      this.innerHTML = 'Enregistrement...';

      try {
        const formData = new FormData();
        formData.append('id', resaId);
        formData.append('statut', 'terminee');

        const res = await fetch('api/update-reservation.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data && data.success) {
          showToast('Réservation marquée comme terminée !', true);

          const row = document.getElementById('resa-row-' + resaId) || this.closest('tr');
          if (row) {
            const badgeCell = row.querySelector('.js-status-badge');
            if (badgeCell) {
              badgeCell.className = 'g-badge g-badge--terminee js-status-badge';
              badgeCell.innerHTML = '<span class="g-badge-dot"></span> Terminée';
            }
          }
          this.parentElement.innerHTML = '<span style="color:#10b981; font-size:11px; font-weight:600;">✓ Terminée</span>';
        } else {
          showToast(data.message || 'Erreur lors de la mise à jour.', false);
          this.disabled = false;
          this.innerHTML = originalText;
        }
      } catch (err) {
        showToast('Erreur de connexion avec le serveur.', false);
        this.disabled = false;
        this.innerHTML = originalText;
      }
    });
  });

  // ── 4. CHANGER LE STATUT D'UNE RÉSERVATION (SELECT) ────────────────
  document.querySelectorAll('.js-select-resa-status').forEach(select => {
    select.addEventListener('change', async function() {
      const resaId = this.getAttribute('data-id');
      const newStatus = this.value;
      if (!resaId) return;

      try {
        const formData = new FormData();
        formData.append('id', resaId);
        formData.append('statut', newStatus);

        const res = await fetch('api/update-reservation.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data && data.success) {
          showToast('Statut mis à jour : ' + newStatus, true);
          const row = this.closest('tr');
          if (row) {
            const badge = row.querySelector('.js-status-badge');
            if (badge) {
              badge.className = `g-badge g-badge--${newStatus} js-status-badge`;
              badge.innerHTML = `<span class="g-badge-dot"></span> ${newStatus.toUpperCase()}`;
            }
          }
        } else {
          showToast(data.message || 'Erreur de mise à jour.', false);
        }
      } catch (_) {
        showToast('Erreur serveur.', false);
      }
    });
  });

  // ── 5. COMPTEUR RAPIDE DE PLACES (− / +) ───────────────────────────
  document.querySelectorAll('.js-stepper-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
      const formationId = this.getAttribute('data-id');
      const delta = parseInt(this.getAttribute('data-delta'), 10);
      const valEl = document.getElementById('step-val-' + formationId);
      if (!formationId || !valEl || isNaN(delta)) return;

      let currentVal = parseInt(valEl.textContent.trim(), 10);
      if (isNaN(currentVal)) currentVal = 0;
      const newVal = Math.max(0, currentVal + delta);

      valEl.textContent = newVal;

      try {
        const formData = new FormData();
        formData.append('id', formationId);
        formData.append('places_disponibles', newVal);

        const res = await fetch('api/update-formation.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data && data.success) {
          showToast(`Places disponibles mises à jour : ${newVal}`, true);
        } else {
          valEl.textContent = currentVal;
          showToast(data.message || 'Erreur lors de la modification des places.', false);
        }
      } catch (_) {
        valEl.textContent = currentVal;
        showToast('Erreur réseau.', false);
      }
    });
  });

  // ── 6. GRAPHIQUES 100% TEMPS RÉEL (CHART.JS CONNECTÉ À L'API) ──────
  let trafficChartInstance = null;
  let sourcesChartInstance = null;

  async function loadRealDashboardCharts(range = '30d') {
    const trafficCanvas = document.getElementById('chartSiteTraffic');
    const donutCanvas = document.getElementById('chartSourcesDonut');

    if (!trafficCanvas && !donutCanvas) return;

    try {
      const res = await fetch(`api/get-stats.php?range=${encodeURIComponent(range)}`);
      if (!res.ok) return;
      const data = await res.json();
      if (!data || !data.success) return;

      // ── 6A. Courbe de trafic réelle
      if (trafficCanvas && data.timeline) {
        const ctx = trafficCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.28)');
        gradient.addColorStop(0.6, 'rgba(255, 255, 255, 0.08)');
        gradient.addColorStop(1, 'rgba(255, 255, 255, 0.0)');

        const maxVal = Math.max(...data.timeline.visits, 5);
        const yMax = Math.ceil(maxVal * 1.25);

        if (trafficChartInstance) trafficChartInstance.destroy();

        trafficChartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: data.timeline.labels,
            datasets: [
              {
                label: 'Visites totales',
                data: data.timeline.visits,
                borderColor: '#ffffff',
                borderWidth: 2.2,
                backgroundColor: gradient,
                fill: true,
                tension: 0.38,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#13151b',
                pointBorderWidth: 2,
                pointRadius: (ctx) => (data.timeline.visits[ctx.dataIndex] > 0 ? 3 : 0),
                pointHoverRadius: 6
              },
              {
                label: 'Visiteurs uniques',
                data: data.timeline.uniques || [],
                borderColor: '#a1a1aa',
                borderWidth: 1.5,
                borderDash: [3, 3],
                backgroundColor: 'transparent',
                tension: 0.38,
                pointRadius: 0
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                  color: '#8e8e93',
                  boxWidth: 10,
                  font: { size: 10.5 }
                }
              },
              tooltip: {
                backgroundColor: '#1b1d24',
                titleColor: '#8e8e93',
                titleFont: { size: 11 },
                bodyColor: '#ffffff',
                bodyFont: { size: 12.5, weight: 'bold' },
                borderColor: 'rgba(255, 255, 255, 0.15)',
                borderWidth: 1,
                padding: 10
              }
            },
            scales: {
              x: {
                grid: {
                  color: 'rgba(255, 255, 255, 0.035)',
                  drawBorder: false
                },
                ticks: {
                  color: '#71717a',
                  font: { size: 10.5 },
                  maxRotation: 0,
                  autoSkip: true,
                  maxTicksLimit: 7
                }
              },
              y: {
                beginAtZero: true,
                suggestedMax: yMax,
                grid: {
                  color: 'rgba(255, 255, 255, 0.035)',
                  drawBorder: false
                },
                ticks: {
                  color: '#71717a',
                  font: { size: 10.5 },
                  precision: 0
                }
              }
            }
          }
        });
      }

      // ── 6B. Donut des Sources réelles
      if (donutCanvas && data.sources) {
        const ctx2 = donutCanvas.getContext('2d');
        if (sourcesChartInstance) sourcesChartInstance.destroy();

        const percents = data.sources.percents || [0, 0, 100, 0, 0];
        const totalCount = data.sources.total || 0;

        // Mise à jour de l'étiquette centrale du donut
        const totalValEl = document.getElementById('donutTotalVal');
        if (totalValEl) totalValEl.textContent = new Intl.NumberFormat('fr-FR').format(totalCount);

        // Mise à jour des pourcentages de la légende
        const idMap = ['pct-google', 'pct-social', 'pct-direct', 'pct-referral', 'pct-other'];
        idMap.forEach((id, idx) => {
          const el = document.getElementById(id);
          if (el) el.textContent = (percents[idx] !== undefined ? percents[idx] : 0) + '%';
        });

        // Données d'affichage du donut
        const donutData = totalCount > 0 ? percents : [1, 1, 1, 1, 1];

        sourcesChartInstance = new Chart(ctx2, {
          type: 'doughnut',
          data: {
            labels: data.sources.labels,
            datasets: [{
              data: donutData,
              backgroundColor: [
                '#ffffff',
                '#a1a1aa',
                '#71717a',
                '#52525b',
                '#3f3f46'
              ],
              borderColor: '#13151b',
              borderWidth: 2.5,
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#1b1d24',
                titleColor: '#ffffff',
                bodyColor: '#d4d4d8',
                borderColor: 'rgba(255, 255, 255, 0.15)',
                borderWidth: 1,
                padding: 9,
                callbacks: {
                  label: function(item) {
                    if (totalCount === 0) return ' En attente de trafic';
                    return ' ' + item.label + ' : ' + item.formattedValue + '%';
                  }
                }
              }
            }
          }
        });
      }

    } catch (err) {
      console.error('Erreur chargement statistiques réelles:', err);
    }
  }

  // Chargement initial des graphiques réels
  loadRealDashboardCharts('30d');

  // Sélecteur de période de trafic
  const trafficRangeSelect = document.getElementById('trafficRangeSelect');
  if (trafficRangeSelect) {
    trafficRangeSelect.addEventListener('change', () => {
      const selectedRange = trafficRangeSelect.value;
      loadRealDashboardCharts(selectedRange);
      showToast('Période actualisée : ' + trafficRangeSelect.options[trafficRangeSelect.selectedIndex].text);
    });
  }

});
