/**
 * Portfolio Dr Remus
 * Informatique Industrielle & Web Fullstack
 * Animations GSAP + Navigation Fluide
 */

window.addEventListener('load', () => {

  gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

  // ── 1. Barre de navigation & Défilement ──────────────────────────────
  const navInner = document.querySelector('.nav-inner');
  const btt      = document.getElementById('btt');

  ScrollTrigger.create({
    start: 40,
    onEnter:     () => navInner?.classList.add('scrolled'),
    onLeaveBack: () => navInner?.classList.remove('scrolled'),
  });

  if (btt) {
    ScrollTrigger.create({
      start: 500,
      onEnter:     () => btt.classList.add('show'),
      onLeaveBack: () => btt.classList.remove('show'),
    });

    btt.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ── 2. Lien de navigation actif au défilement ────────────────────────
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  sections.forEach(sec => {
    ScrollTrigger.create({
      trigger: sec,
      start: 'top center',
      end:   'bottom center',
      onEnter:     () => setActive(sec.id),
      onEnterBack: () => setActive(sec.id),
    });
  });

  function setActive(id) {
    navLinks.forEach(l =>
      l.classList.toggle('active', l.getAttribute('href') === `#${id}`)
    );
  }

  // ── 3. Menu Mobile (Burger) ──────────────────────────────────────────
  const burger  = document.getElementById('burger');
  const navMenu = document.getElementById('navLinks');
  if (burger && navMenu) {
    burger.addEventListener('click', () => {
      const open = navMenu.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
    });
    navMenu.querySelectorAll('.nav-link').forEach(l =>
      l.addEventListener('click', () => navMenu.classList.remove('open'))
    );
  }

  // ── 4. Défilement fluide sur tous les liens internes ────────────────
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const targetId = a.getAttribute('href');
      if (!targetId || targetId === '#') return;
      const target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();
      const offsetTop = target.offsetTop - 75;
      window.scrollTo({ top: offsetTop, behavior: 'smooth' });

      if (history.pushState) {
        history.pushState(null, null, targetId);
      }
    });
  });

  // ── 4b. Effet Télé Cathodique (CRT TV Defocus & Défloutage Vidéo) ──
  let isCrtTuning = false;

  function triggerCrtPowerOn() {
    const photo = document.querySelector('.laptop-photo-img');
    const beam = document.querySelector('.laptop-crt-flash');
    const status = document.querySelector('.laptop-status');
    if (!photo) return;

    if (isCrtTuning) return;
    isCrtTuning = true;

    const crtTl = gsap.timeline({
      onComplete: () => {
        isCrtTuning = false;
      }
    });

    if (status) {
      status.innerHTML = '<span class="pulse-dot" style="background:#fff;box-shadow:0 0 8px #fff"></span> SIGNAL TUNING...';
    }

    // Préparation immédiate de l'état CRT déflouté
    gsap.set(photo, {
      opacity: 0,
      scale: 1.05,
      filter: 'grayscale(100%) blur(26px) brightness(2.5) contrast(1.5)'
    });

    if (beam) {
      gsap.set(beam, { opacity: 0, scaleY: 0.02, scaleX: 1 });
      crtTl
        .to(beam, { opacity: 1, scaleY: 0.04, duration: 0.12, ease: 'power2.out' })
        .to(beam, { scaleY: 1.15, duration: 0.18, ease: 'power3.out' })
        .to(beam, { opacity: 0, duration: 0.3, ease: 'power2.in' }, '-=0.08');
    }

    // Apparition floutée de l'image (téléviseur cathodique qui allume le tube et stabilise le signal)
    crtTl
      .to(photo, { opacity: 1, duration: 0.15, ease: 'none' }, beam ? '-=0.25' : 0)
      // Étape 1 : Gros flou analogique (effet télé qui s'allume)
      .to(photo, {
        filter: 'grayscale(100%) blur(20px) brightness(2.1) contrast(1.35)',
        duration: 0.35,
        ease: 'power1.out'
      })
      // Micro-scintillement CRT (balayage d'image)
      .to(photo, { opacity: 0.75, duration: 0.05 })
      .to(photo, { opacity: 1, duration: 0.06 })
      // Étape 2 : Défloutage intermédiaire (focalisation du faisceau)
      .to(photo, {
        filter: 'grayscale(100%) blur(11px) brightness(1.6) contrast(1.25)',
        scale: 1.03,
        duration: 0.4,
        ease: 'power2.out'
      })
      // Micro-sursaut de trame
      .to(photo, { opacity: 0.88, duration: 0.04 })
      .to(photo, { opacity: 1, duration: 0.05 })
      // Étape 3 : Défloutage fin
      .to(photo, {
        filter: 'grayscale(100%) blur(4px) brightness(1.2) contrast(1.12)',
        scale: 1.01,
        duration: 0.45,
        ease: 'power2.out'
      })
      // Étape 4 : Netteté parfaite & stabilisation
      .to(photo, {
        filter: 'grayscale(100%) blur(0px) brightness(1.02) contrast(1.05)',
        scale: 1,
        duration: 0.5,
        ease: 'power3.out'
      })
      .add(() => {
        if (status) {
          status.innerHTML = '<span class="pulse-dot"></span> SYSTEM ONLINE';
        }
      }, '-=0.3');

    return crtTl;
  }

  // Initialisation photo floue au chargement
  const initPhoto = document.querySelector('.laptop-photo-img');
  if (initPhoto) {
    gsap.set(initPhoto, {
      opacity: 0,
      scale: 1.05,
      filter: 'grayscale(100%) blur(26px) brightness(2.5) contrast(1.5)'
    });
  }

  // Clic sur l'ordinateur pour relancer l'effet téléviseur à tout moment
  const laptopElem = document.querySelector('.laptop-mockup');
  if (laptopElem) {
    laptopElem.style.cursor = 'pointer';
    laptopElem.setAttribute('title', 'Cliquer pour relancer la syntonie vidéo CRT');
    laptopElem.addEventListener('click', () => {
      triggerCrtPowerOn();
    });
  }

  // ── 5. Animations Hero & Câblage HUD ────────────────────────────────
  const heroTl = gsap.timeline({ paused: true });
  heroTl
    .from('.hero-available',   { y: 20, opacity: 0, duration: 0.6, ease: 'power3.out' })
    .from('.hero-title',       { y: 30, opacity: 0, duration: 0.75, ease: 'power4.out' }, '-=.35')
    .from('.hero-lead',        { y: 20, opacity: 0, duration: 0.6, ease: 'power3.out' }, '-=.4')
    .from('.hud-center-laptop',{ scale: 0.92, y: 25, opacity: 0, duration: 0.85, ease: 'power3.out' }, '-=.45')
    .add(() => {
      triggerCrtPowerOn();
    }, '-=.4')
    .from('.hud-col--left .hud-card',  { x: -25, opacity: 0, duration: 0.65, ease: 'power3.out', stagger: 0.12 }, '-=.5')
    .from('.hud-col--right .hud-card', { x: 25, opacity: 0, duration: 0.65, ease: 'power3.out', stagger: 0.12 }, '-=.6')
    .from('#hudSvgOverlay',    { opacity: 0, duration: 0.8, ease: 'power2.out', onUpdate: updateHudWires }, '-=.4')
    .from('.stat-card',        { y: 24, opacity: 0, scale: 0.96, duration: 0.6, ease: 'power3.out', stagger: 0.1 }, '-=.25');

  // ── 6. Révélations de Titres de Sections ────────────────────────────
  gsap.utils.toArray('.sec-header').forEach(el => {
    const pill  = el.querySelector('.sec-pill');
    const title = el.querySelector('.sec-title');
    const desc  = el.querySelector('.sec-desc');
    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: el,
        start: 'top 85%',
        toggleActions: 'play none none none'
      }
    });
    if (pill)  tl.from(pill,  { y: 16, opacity: 0, duration: 0.5,  ease: 'power3.out' });
    if (title) tl.from(title, { y: 24, opacity: 0, duration: 0.65, ease: 'power4.out' }, '-=.3');
    if (desc)  tl.from(desc,  { y: 16, opacity: 0, duration: 0.5,  ease: 'power2.out' }, '-=.35');
  });

  // ── 7. Révélation Éléments Génériques (data-reveal & data-stagger) ──
  gsap.utils.toArray('[data-reveal]').forEach(el => {
    const dir   = el.dataset.revealDir || 'up';
    const delay = parseFloat(el.dataset.revealDelay || '0');
    const dist  = 30;
    const from  = { opacity: 0, duration: 0.8, ease: 'power3.out', delay };
    if (dir === 'up')    from.y =  dist;
    if (dir === 'down')  from.y = -dist;
    if (dir === 'left')  from.x =  dist;
    if (dir === 'right') from.x = -dist;
    gsap.from(el, {
      ...from,
      scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'play none none none' }
    });
  });

  gsap.utils.toArray('[data-stagger]').forEach(parent => {
    const children = parent.children;
    const st  = parseFloat(parent.dataset.stagger || '.1');
    const dst = parseFloat(parent.dataset.staggerDist || '24');
    gsap.from(children, {
      y: dst,
      opacity: 0,
      duration: 0.7,
      ease: 'power3.out',
      stagger: st,
      scrollTrigger: { trigger: parent, start: 'top 86%', toggleActions: 'play none none none' }
    });
  });

  // ── 8. Parallax subtil sur la carte À Propos ────────────────────────
  const aboutCard = document.querySelector('.about-card');
  if (aboutCard) {
    gsap.to(aboutCard, {
      y: -16,
      ease: 'none',
      scrollTrigger: { trigger: '#a-propos', start: 'top bottom', end: 'bottom top', scrub: 1.4 }
    });
  }

  // ── 9. Compétences (Colonnes fluides et rangées toujours visibles) ───
  gsap.utils.toArray('.skill-col').forEach((col, i) => {
    gsap.from(col, {
      y: 35,
      opacity: 0,
      duration: 0.75,
      ease: 'power3.out',
      delay: i * 0.1,
      scrollTrigger: {
        trigger: col,
        start: 'top 88%',
        toggleActions: 'play none none none'
      }
    });
  });

  // ── 10. Révélation Section Contact ──────────────────────────────────
  const cInfo = document.querySelector('.contact-info');
  const cForm = document.querySelector('.contact-form-wrap');
  if (cInfo) {
    gsap.from(cInfo, {
      x: -40,
      opacity: 0,
      duration: 0.85,
      ease: 'power3.out',
      scrollTrigger: { trigger: '#contact', start: 'top 80%', toggleActions: 'play none none none' }
    });
  }
  if (cForm) {
    gsap.from(cForm, {
      x: 40,
      opacity: 0,
      duration: 0.85,
      ease: 'power3.out',
      scrollTrigger: { trigger: '#contact', start: 'top 80%', toggleActions: 'play none none none' }
    });
  }

  // ── 11. Footer ───────────────────────────────────────────────────────
  gsap.from('.site-footer', {
    y: 18,
    opacity: 0,
    duration: 0.65,
    ease: 'power3.out',
    scrollTrigger: { trigger: '.site-footer', start: 'top 96%', toggleActions: 'play none none none' }
  });

  // ── 12. Formulaire de Contact & Modal Popup de Succès ───────────────
  const contactForm  = document.getElementById('contactForm');
  const formNote     = document.getElementById('formNote');
  const subBtn       = contactForm?.querySelector('button[type="submit"]');
  const successModal = document.getElementById('contactSuccessModal');
  const modalName    = document.getElementById('cModalName');
  const modalClose   = document.getElementById('cModalClose');
  const modalBtn     = document.getElementById('cModalBtn');
  const modalOverlay = document.getElementById('cModalOverlay');

  function openSuccessModal(userName) {
    if (!successModal) return;
    if (modalName) {
      modalName.textContent = userName ? ` ${userName}` : '';
    }
    successModal.classList.add('active');
    successModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeSuccessModal() {
    if (!successModal) return;
    successModal.classList.remove('active');
    successModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  if (modalClose)   modalClose.addEventListener('click', closeSuccessModal);
  if (modalBtn)     modalBtn.addEventListener('click', closeSuccessModal);
  if (modalOverlay) modalOverlay.addEventListener('click', closeSuccessModal);

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && successModal?.classList.contains('active')) {
      closeSuccessModal();
    }
  });

  if (contactForm) {
    contactForm.addEventListener('submit', async e => {
      e.preventDefault();
      const name = document.getElementById('cName')?.value.trim();
      const mail = document.getElementById('cEmail')?.value.trim();
      const msg  = document.getElementById('cMsg')?.value.trim();
      if (!name || !mail || !msg) {
        if (formNote) {
          formNote.className = 'form-note err';
          formNote.innerHTML = '<strong>Attention :</strong> Veuillez remplir tous les champs obligatoires (*).';
          gsap.from(formNote, { y: 10, opacity: 0, duration: 0.4, ease: 'power3.out' });
        }
        return;
      }

      const orig = subBtn ? subBtn.innerHTML : '';
      if (subBtn) {
        subBtn.disabled = true;
        subBtn.innerHTML = '<span>Transmission en cours…</span>';
        gsap.to(subBtn, { scale: 0.97, duration: 0.15 });
      }

      try {
        const formData = new FormData(contactForm);
        const res = await fetch('send-email.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json().catch(() => null);

        if (subBtn) {
          subBtn.disabled = false;
          subBtn.innerHTML = orig;
          gsap.to(subBtn, { scale: 1, duration: 0.2, ease: 'back.out(2)' });
        }

        if (res.ok && data?.success) {
          // Affichage de la popup modale grand format à haute visibilité
          openSuccessModal(name);
          contactForm.reset();

          if (formNote) {
            formNote.className = 'form-note ok';
            formNote.innerHTML = `<strong>Message transmis avec succès !</strong> Un accusé de réception a été envoyé à votre adresse email.`;
            setTimeout(() => { formNote.className = 'form-note'; }, 9000);
          }
        } else {
          if (formNote) {
            formNote.className = 'form-note err';
            formNote.innerHTML = `<strong>Information :</strong> ${data?.message || 'Une erreur est survenue lors de l\'envoi. Vous pouvez me contacter directement via WhatsApp.'}`;
            gsap.from(formNote, { y: 10, opacity: 0, duration: 0.45, ease: 'power3.out' });
          }
        }
      } catch (err) {
        if (subBtn) {
          subBtn.disabled = false;
          subBtn.innerHTML = orig;
          gsap.to(subBtn, { scale: 1, duration: 0.2, ease: 'back.out(2)' });
        }
        if (formNote) {
          formNote.className = 'form-note err';
          formNote.innerHTML = '<strong>Erreur réseau :</strong> Impossible de joindre le serveur. Vous pouvez me joindre directement via WhatsApp.';
          gsap.from(formNote, { y: 10, opacity: 0, duration: 0.45, ease: 'power3.out' });
        }
      }
    });
  }

  // ── 13. Calcul et Tracé Dynamique des Câbles HUD (Cartes -> Écran PC) ──
  function updateHudWires() {
    const stage = document.querySelector('.hero-hud-stage');
    const overlay = document.getElementById('hudSvgOverlay');
    if (!stage || !overlay) return;

    if (window.innerWidth <= 960) {
      overlay.style.display = 'none';
      return;
    }
    overlay.style.display = 'block';

    const stageRect = stage.getBoundingClientRect();
    const width = Math.round(stageRect.width);
    const height = Math.round(stageRect.height);
    if (width <= 0 || height <= 0) return;

    overlay.setAttribute('viewBox', `0 0 ${width} ${height}`);
    overlay.setAttribute('width', width);
    overlay.setAttribute('height', height);

    const cardL1 = document.querySelector('.hud-card--l1');
    const cardL2 = document.querySelector('.hud-card--l2');
    const cardR1 = document.querySelector('.hud-card--r1');
    const cardR2 = document.querySelector('.hud-card--r2');
    const laptopScreen = document.querySelector('.laptop-screen');

    if (!cardL1 || !cardL2 || !cardR1 || !cardR2 || !laptopScreen) return;

    const rL1 = cardL1.getBoundingClientRect();
    const rL2 = cardL2.getBoundingClientRect();
    const rR1 = cardR1.getBoundingClientRect();
    const rR2 = cardR2.getBoundingClientRect();
    const rScreen = laptopScreen.getBoundingClientRect();

    // Coordonnées de jonction sub-pixel relatives au conteneur stage
    // Cartes gauches -> bord gauche de l'écran du PC
    const xL1_start = rL1.right - stageRect.left;
    const yL1_start = rL1.top + rL1.height * 0.5 - stageRect.top;
    const xL1_end   = rScreen.left - stageRect.left + 2;
    const yL1_end   = rScreen.top + rScreen.height * 0.32 - stageRect.top;

    const xL2_start = rL2.right - stageRect.left;
    const yL2_start = rL2.top + rL2.height * 0.5 - stageRect.top;
    const xL2_end   = rScreen.left - stageRect.left + 2;
    const yL2_end   = rScreen.top + rScreen.height * 0.72 - stageRect.top;

    // Cartes droites -> bord droit de l'écran du PC
    const xR1_start = rR1.left - stageRect.left;
    const yR1_start = rR1.top + rR1.height * 0.5 - stageRect.top;
    const xR1_end   = rScreen.right - stageRect.left - 2;
    const yR1_end   = rScreen.top + rScreen.height * 0.32 - stageRect.top;

    const xR2_start = rR2.left - stageRect.left;
    const yR2_start = rR2.top + rR2.height * 0.5 - stageRect.top;
    const xR2_end   = rScreen.right - stageRect.left - 2;
    const yR2_end   = rScreen.top + rScreen.height * 0.72 - stageRect.top;

    // Courbe cyber S-curve fluide et tendue
    function buildCircuitCurve(x1, y1, x2, y2) {
      const dx = x2 - x1;
      const cp1x = x1 + dx * 0.55;
      const cp1y = y1;
      const cp2x = x1 + dx * 0.45;
      const cp2y = y2;
      return `M ${x1.toFixed(1)} ${y1.toFixed(1)} C ${cp1x.toFixed(1)} ${cp1y.toFixed(1)}, ${cp2x.toFixed(1)} ${cp2y.toFixed(1)}, ${x2.toFixed(1)} ${y2.toFixed(1)}`;
    }

    function setPathAndDots(id, d, x1, y1, x2, y2) {
      const guide = document.getElementById(`hudGuide${id}`);
      const wire  = document.getElementById(`hudWire${id}`);
      const dotC  = document.getElementById(`hudDotCard${id}`);
      const dotL  = document.getElementById(`hudDotLap${id}`);

      if (guide) guide.setAttribute('d', d);
      if (wire)  wire.setAttribute('d', d);
      if (dotC) {
        dotC.setAttribute('cx', x1.toFixed(1));
        dotC.setAttribute('cy', y1.toFixed(1));
      }
      if (dotL) {
        dotL.setAttribute('cx', x2.toFixed(1));
        dotL.setAttribute('cy', y2.toFixed(1));
      }
    }

    setPathAndDots('L1', buildCircuitCurve(xL1_start, yL1_start, xL1_end, yL1_end), xL1_start, yL1_start, xL1_end, yL1_end);
    setPathAndDots('L2', buildCircuitCurve(xL2_start, yL2_start, xL2_end, yL2_end), xL2_start, yL2_start, xL2_end, yL2_end);
    setPathAndDots('R1', buildCircuitCurve(xR1_start, yR1_start, xR1_end, yR1_end), xR1_start, yR1_start, xR1_end, yR1_end);
    setPathAndDots('R2', buildCircuitCurve(xR2_start, yR2_start, xR2_end, yR2_end), xR2_start, yR2_start, xR2_end, yR2_end);
  }

  // Recalcul sur redimensionnement fluide
  let resizeTimer = null;
  window.addEventListener('resize', () => {
    cancelAnimationFrame(resizeTimer);
    resizeTimer = requestAnimationFrame(updateHudWires);
  });
  window.addEventListener('orientationchange', () => setTimeout(updateHudWires, 150));

  // ── 14. Séquence de Démarrage 2s (01 CODE -> 02 CONNECT -> 03 VISUALIZE -> 04 DR REMUS) ──
  function initCyberLoader(onComplete) {
    const loader  = document.getElementById('cyberLoader');
    const skipBtn = document.getElementById('cyberSkipBtn');
    const flash   = document.getElementById('cyberFlash');

    if (!loader) {
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    let isCompleted = false;

    function finishLoader() {
      if (isCompleted) return;
      isCompleted = true;

      // Éclair blanc laser cinématographique
      if (flash) {
        gsap.to(flash, {
          opacity: 0.92,
          duration: 0.16,
          ease: 'power2.in',
          onComplete: () => {
            gsap.to(flash, { opacity: 0, duration: 0.45, ease: 'power3.out' });
          }
        });
      }

      // Fondu de sortie et suppression fluide
      gsap.to(loader, {
        opacity: 0,
        scale: 1.05,
        filter: 'blur(10px)',
        duration: 0.55,
        ease: 'power2.inOut',
        onComplete: () => {
          loader.style.display = 'none';
          loader.remove();
          if (typeof onComplete === 'function') {
            onComplete();
          }
        }
      });
    }

    // Écouteurs pour passer immédiatement (Clic ou Échap)
    if (skipBtn) skipBtn.addEventListener('click', finishLoader);
    const escHandler = (e) => {
      if (e.key === 'Escape' || e.code === 'Space') {
        finishLoader();
        window.removeEventListener('keydown', escHandler);
      }
    };
    window.addEventListener('keydown', escHandler);

    const nameEl = document.getElementById('remusEpicName');
    const subEl  = document.getElementById('remusEpicSub');
    const wave1  = document.getElementById('remusShockwave');
    const wave2  = document.getElementById('remusShockwave2');
    const flare  = document.getElementById('remusFlare');
    const rings  = document.querySelectorAll('.remus-halo-ring');

    const bootTl = gsap.timeline({
      onComplete: () => finishLoader()
    });

    // ── TEMPS 1 (0.0s – 1.6s) : Émergence Mystique & Présence Sombre ──
    bootTl
      .fromTo(rings, { opacity: 0, scale: 0.7 }, { opacity: 0.8, scale: 1, duration: 1.5, ease: 'power2.out' }, 0.2)
      .fromTo(nameEl,
        { scale: 0.88, opacity: 0, letterSpacing: '0.22em', filter: 'blur(8px)' },
        { scale: 1, opacity: 1, letterSpacing: '0.05em', filter: 'blur(0px)', duration: 1.6, ease: 'power3.out' },
        0.3
      );

    // ── TEMPS 2 (1.6s – 2.9s) : Montée en Puissance & Sous-Titre Rayonnant ──
    bootTl
      .fromTo(subEl,
        { opacity: 0, y: 16, letterSpacing: '0.3em' },
        { opacity: 1, y: 0, letterSpacing: '0.22em', duration: 1.1, ease: 'power3.out' },
        1.6
      )
      .to(nameEl, {
        textShadow: '0 0 50px rgba(255,255,255,1), 0 0 100px rgba(255,255,255,0.7), 0 0 160px rgba(255,255,255,0.4)',
        duration: 1.3,
        ease: 'power2.inOut'
      }, 1.6);

    // ── TEMPS 3 (2.9s – 4.8s) : L'EXPLOSION LUMINEUSE SUBTILE & MAJESTUEUSE ──
    bootTl
      // Première onde de choc circulaire (expansion sphérique)
      .fromTo(wave1,
        { scale: 0.15, opacity: 0 },
        { scale: 3.6, opacity: 1, duration: 1.15, ease: 'power2.out' },
        2.9
      )
      .to(wave1, { opacity: 0, duration: 0.5, ease: 'power2.in' }, 3.65)
      // Deuxième onde de choc décalée
      .fromTo(wave2,
        { scale: 0.1, opacity: 0 },
        { scale: 2.8, opacity: 0.85, duration: 1.0, ease: 'power2.out' },
        3.15
      )
      .to(wave2, { opacity: 0, duration: 0.45, ease: 'power2.in' }, 3.75)
      // Halo radial de lumière pure (Radial Bloom Flare)
      .fromTo(flare,
        { scale: 0.1, opacity: 0 },
        { scale: 2.8, opacity: 0.95, duration: 0.95, ease: 'power3.out' },
        2.95
      )
      .to(flare, { opacity: 0, scale: 3.2, duration: 0.7, ease: 'power2.in' }, 3.85)
      // Rebond d'énergie subtil sur le nom
      .to(nameEl, { scale: 1.04, duration: 0.45, ease: 'power2.out' }, 2.95)
      .to(nameEl, { scale: 1, duration: 0.65, ease: 'power2.inOut' }, 3.4);

    // ── TEMPS 4 (4.8s – 6.0s) : Résonance & Transition Finale vers le Héro ──
    bootTl
      .to(rings, { opacity: 0, duration: 0.8, ease: 'power2.in' }, 5.0)
      .to(subEl, { opacity: 0.7, duration: 0.6 }, 5.1);
  }

  // Démarrage : synchronisation loader -> activation Hero
  initCyberLoader(() => {
    updateHudWires();
    heroTl.play();
  });

  // Premier calcul direct des câbles HUD
  updateHudWires();

});
