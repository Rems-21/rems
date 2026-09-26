/**
 * JAVASCRIPT DÉDIÉ — PAGE FORMATIONS & ACCOMPAGNEMENT (DR REMUS)
 * Gère le compte à rebours dynamique, la configuration de cohorte facilement modifiable
 * pour l'espace d'administration futur, et les animations.
 */
document.addEventListener('DOMContentLoaded', () => {

  // ── 1. CONFIGURATION DE LA COHORTE (MODIFIABLE FACILEMENT) ─────────
  // Ces données pourront être injectées depuis votre futur espace d'administration / API
  window.COHORT_CONFIG = {
    title: "Créer un site web avec l'IA",
    startDate: "2026-10-15T09:00:00", // Format ISO
    totalSeats: 10,
    availableSeats: 7,
    status: "Inscriptions ouvertes"
  };

  // Mise à jour automatique de la jauge et du statut selon la config
  function applyCohortConfig() {
    const statusText   = document.getElementById('fStatusText');
    const seatsText    = document.getElementById('fSeatsText');
    const progressFill = document.getElementById('fProgressFill');

    const { totalSeats, availableSeats, status } = window.COHORT_CONFIG;

    if (statusText) statusText.textContent = status;
    if (seatsText) {
      seatsText.innerHTML = `<strong>${availableSeats}</strong> / ${totalSeats} places disponibles`;
    }
    if (progressFill && totalSeats > 0) {
      const percentage = Math.round((availableSeats / totalSeats) * 100);
      progressFill.style.width = `${percentage}%`;
    }
  }

  applyCohortConfig();

  // ── 2. COMPTE À REBOURS DYNAMIQUE JUSQU'AU 15 OCTOBRE 2026 ────────
  function initCountdown() {
    const wrap = document.getElementById('fCountdownWrap');
    if (!wrap) return;

    const daysEl  = document.getElementById('cdDays');
    const hoursEl = document.getElementById('cdHours');
    const minsEl  = document.getElementById('cdMinutes');
    const secsEl  = document.getElementById('cdSeconds');

    const targetTime = new Date(window.COHORT_CONFIG.startDate).getTime();

    // Si la date est invalide ou non définie, masquer discrètement le compte à rebours
    if (isNaN(targetTime)) {
      wrap.style.display = 'none';
      return;
    }

    function updateTimer() {
      const now = new Date().getTime();
      const distance = targetTime - now;

      if (distance < 0) {
        if (daysEl)  daysEl.textContent  = '00';
        if (hoursEl) hoursEl.textContent = '00';
        if (minsEl)  minsEl.textContent  = '00';
        if (secsEl)  secsEl.textContent  = '00';
        return;
      }

      const days    = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      if (daysEl)  daysEl.textContent  = String(days).padStart(2, '0');
      if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
      if (minsEl)  minsEl.textContent  = String(minutes).padStart(2, '0');
      if (secsEl)  secsEl.textContent  = String(seconds).padStart(2, '0');
    }

    updateTimer();
    setInterval(updateTimer, 1000);
  }

  initCountdown();

  // ── 3. MENU MOBILE BURGER ──────────────────────────────────────────
  const fBurger = document.getElementById('fBurger');
  const fNav    = document.getElementById('fNavLinks');
  if (fBurger && fNav) {
    fBurger.addEventListener('click', () => {
      const open = fNav.classList.toggle('open');
      fBurger.setAttribute('aria-expanded', open);
    });
    fNav.querySelectorAll('.nav-link').forEach(l => {
      l.addEventListener('click', () => fNav.classList.remove('open'));
    });
  }

  // ── 4. DÉFILEMENT FLUIDE (SMOOTH SCROLL) ───────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        const top = target.getBoundingClientRect().top + window.pageYOffset - 80;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  // ── 5. ANIMATIONS GSAP AU DÉFILEMENT (SUBTILES ET FLUIDES) ─────────
  if (typeof gsap !== 'undefined') {
    // Apparition du Hero
    gsap.from('.f-label-pill',   { y: 15, opacity: 0, duration: 0.6, ease: 'power3.out' });
    gsap.from('.f-hero-title',   { y: 25, opacity: 0, duration: 0.75, ease: 'power4.out', delay: 0.1 });
    gsap.from('.f-hero-sub',     { y: 18, opacity: 0, duration: 0.6, ease: 'power3.out', delay: 0.2 });
    gsap.from('.f-hero-note',    { y: 15, opacity: 0, duration: 0.6, ease: 'power3.out', delay: 0.3 });
    gsap.from('.f-hero-ctas',    { y: 15, opacity: 0, duration: 0.6, ease: 'power3.out', delay: 0.35 });

    // Apparition de la carte Cohorte
    const cohortCard = document.querySelector('.f-cohort-card');
    if (cohortCard) {
      gsap.from(cohortCard, {
        y: 40,
        opacity: 0,
        scale: 0.98,
        duration: 0.85,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: cohortCard,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    }

    // Apparition des étapes du parcours
    gsap.utils.toArray('.f-journey-step').forEach((step, i) => {
      gsap.from(step, {
        y: 25,
        opacity: 0,
        duration: 0.65,
        ease: 'power3.out',
        delay: i * 0.12,
        scrollTrigger: {
          trigger: step,
          start: 'top 88%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Apparition des cartes formules
    gsap.utils.toArray('.f-plan-card').forEach((card, i) => {
      gsap.from(card, {
        y: 30,
        opacity: 0,
        duration: 0.7,
        ease: 'power3.out',
        delay: i * 0.12,
        scrollTrigger: {
          trigger: card,
          start: 'top 88%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Apparition des cartes de prochaines formations
    gsap.utils.toArray('.f-upcoming-card').forEach((card, i) => {
      gsap.from(card, {
        y: 25,
        opacity: 0,
        duration: 0.65,
        ease: 'power3.out',
        delay: i * 0.1,
        scrollTrigger: {
          trigger: card,
          start: 'top 90%',
          toggleActions: 'play none none none'
        }
      });
    });
  }

  // ── 6. GESTION DU MODAL POPUP DE RÉSERVATION & DU SUCCÈS ─────────
  const resaModal       = document.getElementById('resaModal');
  const resaModalClose  = document.getElementById('resaModalClose');
  const resaForm        = document.getElementById('resaForm');
  const resaSubmitBtn   = document.getElementById('resaSubmitBtn');
  const resaBtnText     = document.getElementById('resaBtnText');
  const resaSuccessPopup= document.getElementById('resaSuccessPopup');
  const resaPopupMsg    = document.getElementById('resaPopupMsg');
  const resaPopupClose  = document.getElementById('resaPopupClose');
  const resaPlacesEl    = document.getElementById('resaPlacesLeft');

  // Ouvrir le modal popup de réservation
  window.openResaModal = function(formationName) {
    const modal = document.getElementById('resaModal');
    if (!modal) return;
    if (formationName) {
      const inputFormation = document.getElementById('modalFormationInput');
      if (inputFormation) inputFormation.value = formationName;
    }
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-visible');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
      const firstInput = modal.querySelector('input[type="text"]');
      if (firstInput) firstInput.focus();
    }, 120);
  };

  // Fermer le modal popup de réservation
  window.closeResaModal = function() {
    const modal = document.getElementById('resaModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    const successPopup = document.getElementById('resaSuccessPopup');
    if (!successPopup || successPopup.style.display === 'none' || !successPopup.classList.contains('is-visible')) {
      document.body.style.overflow = '';
    }
  };

  // Ouvrir le popup de confirmation succès ou erreur
  window.showSuccessPopup = function(message, isSuccess = true) {
    window.closeResaModal();
    const successPopup = document.getElementById('resaSuccessPopup');
    const msgEl = document.getElementById('resaPopupMsg');
    const titleEl = document.getElementById('resaPopupTitle');
    if (!successPopup) return;
    if (msgEl) msgEl.textContent = message;
    if (titleEl) {
      titleEl.textContent = isSuccess ? 'Demande transmise.' : 'Une erreur est survenue.';
    }
    successPopup.style.display = 'flex';
    successPopup.setAttribute('aria-hidden', 'false');
    successPopup.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  };

  // Fermer le popup de succès
  window.hideSuccessPopup = function() {
    const successPopup = document.getElementById('resaSuccessPopup');
    if (!successPopup) return;
    successPopup.style.display = 'none';
    successPopup.classList.remove('is-visible');
    successPopup.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  // Événements déclencheurs pour ouvrir le modal
  document.querySelectorAll('.js-trigger-resa-modal, #btnOpenResaModal, #btnNavResaModal').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.openResaModal();
    });
  });

  // Liens nav vers #reservation
  document.querySelectorAll('a[href="#reservation"]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      openResaModal();
    });
  });

  // Fermeture du modal de réservation
  if (resaModalClose) resaModalClose.addEventListener('click', closeResaModal);
  if (resaModal) {
    resaModal.addEventListener('click', (e) => {
      if (e.target === resaModal) closeResaModal();
    });
  }

  // Fermeture du popup de succès
  if (resaPopupClose) resaPopupClose.addEventListener('click', hideSuccessPopup);
  if (resaSuccessPopup) {
    resaSuccessPopup.addEventListener('click', (e) => {
      if (e.target === resaSuccessPopup) hideSuccessPopup();
    });
  }

  // Fermeture générale par touche Échap
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (resaModal && resaModal.classList.contains('is-visible')) closeResaModal();
      if (resaSuccessPopup && resaSuccessPopup.classList.contains('is-visible')) hideSuccessPopup();
    }
  });

  // Ouverture automatique si l'URL contient le hash #reservation
  if (window.location.hash === '#reservation') {
    setTimeout(openResaModal, 400);
  }

  // ── 7. SYNCHRONISATION EN DIRECT DES PLACES DEPUIS L'ADMIN ───────
  async function fetchLiveCohortSeats() {
    try {
      const res = await fetch('api/cohort-status.php');
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.success && data.formation) {
        const { places_total, places_disponibles, statut } = data.formation;
        if (typeof places_disponibles !== 'undefined') {
          if (resaPlacesEl) {
            resaPlacesEl.textContent = `${places_disponibles} / ${places_total}`;
          }
          const seatsText = document.getElementById('fSeatsText');
          if (seatsText) {
            seatsText.innerHTML = `<strong>${places_disponibles}</strong> / ${places_total} places disponibles`;
          }
          const progressFill = document.getElementById('fProgressFill');
          if (progressFill && places_total > 0) {
            const pct = Math.round(((places_total - places_disponibles) / places_total) * 100);
            progressFill.style.width = pct + '%';
          }
        }
      }
    } catch (_) {
      // Fallback gracieux sur COHORT_CONFIG
    }
  }
  // ── 7b. INDICATIF PAYS DYNAMIQUE POUR WHATSAPP ─────────────────────
  const resaIndicatif = document.getElementById('resaIndicatif');
  const resaWhatsapp  = document.getElementById('resaWhatsapp');
  if (resaIndicatif && resaWhatsapp) {
    const placeholders = {
      '+237': 'Ex: 6 93 29 01 35',
      '+33':  'Ex: 6 12 34 56 78',
      '+225': 'Ex: 07 12 34 56 78',
      '+221': 'Ex: 77 123 45 67',
      '+1':   'Ex: 514 123 4567',
      '+32':  'Ex: 470 12 34 56',
      '+41':  'Ex: 79 123 45 67',
      '+241': 'Ex: 66 12 34 56',
      '+242': 'Ex: 06 123 45 67',
      '+243': 'Ex: 81 234 56 78',
      '+212': 'Ex: 6 12 34 56 78',
      '+216': 'Ex: 20 123 456',
      '+213': 'Ex: 5 12 34 56 78',
      '+':    'Ex: +... Numéro avec indicatif'
    };
    resaIndicatif.addEventListener('change', () => {
      const code = resaIndicatif.value;
      resaWhatsapp.placeholder = placeholders[code] || 'Ex: Numéro de téléphone';
    });
  }

  // ── 8. SOUMISSION DU FORMULAIRE DE RÉSERVATION (AJAX) ─────────────
  if (resaForm) {
    resaForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const prenom = resaForm.querySelector('[name="prenom"]');
      const email  = resaForm.querySelector('[name="email"]');
      if (!prenom || prenom.value.trim().length < 2) {
        prenom && prenom.focus();
        return;
      }
      const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email || !emailRe.test(email.value.trim())) {
        email && email.focus();
        return;
      }

      if (resaSubmitBtn) resaSubmitBtn.disabled = true;
      if (resaBtnText) resaBtnText.innerHTML = '<span class="f-resa-spinner"></span> Envoi en cours...';

      try {
        const formData = new FormData(resaForm);
        const response = await fetch('send-reservation.php', {
          method: 'POST',
          body: formData
        });

        let data = {};
        try { data = await response.json(); } catch (_) {}

        if (data.success) {
          resaForm.reset();
          showSuccessPopup(data.message || 'Votre réservation a bien été enregistrée. Un email de confirmation vous a été envoyé.', true);
          // Rafraîchir les places
          fetchLiveCohortSeats();
        } else {
          showSuccessPopup(data.message || 'Une erreur est survenue. Veuillez réessayer ou me contacter sur WhatsApp.', false);
        }
      } catch (err) {
        showSuccessPopup('Impossible de joindre le serveur. Contactez-moi directement sur WhatsApp au +237 693 29 01 35.', false);
      } finally {
        if (resaSubmitBtn) resaSubmitBtn.disabled = false;
        if (resaBtnText) resaBtnText.textContent = 'Confirmer ma réservation';
      }
    });
  }

});
