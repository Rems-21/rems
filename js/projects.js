/**
 * INJECTION DYNAMIQUE DES PROJETS DEPUIS LE BACKEND MYSQL
 * Portfolio Dr Remus — Production Ready (Zéro Mock)
 */

(function () {
  'use strict';

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getTechIcon(tag) {
    if (!tag) return '';
    const clean = tag.trim().toLowerCase();
    const map = {
      'react': 'react',
      'react 19': 'react',
      'react.js': 'react',
      'node.js': 'nodedotjs',
      'nodejs': 'nodedotjs',
      'node': 'nodedotjs',
      'mqtt': 'mqtt',
      'influxdb': 'influxdb',
      'docker': 'docker',
      'python': 'python',
      'fastapi': 'fastapi',
      'sqlite': 'sqlite',
      'raspberry pi': 'raspberrypi',
      'raspberrypi': 'raspberrypi',
      'c++': 'cplusplus',
      'c++ embarqué': 'cplusplus',
      'typescript': 'typescript',
      'javascript': 'javascript',
      'tailwind': 'tailwindcss',
      'tailwind css': 'tailwindcss',
      'tailwindcss': 'tailwindcss',
      'postgresql': 'postgresql',
      'postgres': 'postgresql',
      'mysql': 'mysql',
      'redis': 'redis',
      'linux': 'linux',
      'git': 'git',
      'github': 'github',
      'nginx': 'nginx',
      'grafana': 'grafana',
      'vue': 'vuedotjs',
      'vue.js': 'vuedotjs',
      'next.js': 'nextdotjs',
      'django': 'django',
      'bootstrap': 'bootstrap',
      'vercel': 'vercel'
    };

    if (map[clean]) {
      return `<img src="https://cdn.simpleicons.org/${map[clean]}/ffffff" alt="${escapeHtml(tag)}" onerror="this.remove()">`;
    }

    return `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>`;
  }

  function renderCard(p, index) {
    const cardNum = index + 1;
    const badge = escapeHtml(p.badge || 'PROJET');
    const title = escapeHtml(p.title || '');
    const desc = escapeHtml(p.description || '');

    // Stack tags
    let stackHtml = '';
    if (Array.isArray(p.tech_stack) && p.tech_stack.length > 0) {
      stackHtml = p.tech_stack.map(tag => {
        const iconHtml = getTechIcon(tag);
        return `<span class="cs-tag">${iconHtml} ${escapeHtml(tag)}</span>`;
      }).join('\n');
    }

    // Links
    let linksHtml = '';
    if (p.demo_url && p.demo_url !== '#') {
      linksHtml += `
        <a href="${escapeHtml(p.demo_url)}" class="cs-link cs-link--primary" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Voir la démo
        </a>`;
    } else {
      linksHtml += `
        <a href="#" class="cs-link cs-link--primary" onclick="alert('Démo bientôt accessible en ligne'); return false;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Voir la démo
        </a>`;
    }

    if (p.github_url && p.github_url !== '#') {
      linksHtml += `
        <a href="${escapeHtml(p.github_url)}" class="cs-link" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
          Code source
        </a>`;
    } else {
      linksHtml += `
        <a href="#" class="cs-link" onclick="alert('Dépôt privé ou code disponible sur demande.'); return false;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
          Code source
        </a>`;
    }

    // Preview
    let previewContent = '';
    if (p.preview_image) {
      previewContent = `<img src="${escapeHtml(p.preview_image)}" alt="${title}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" loading="lazy">`;
    } else if (p.preview_svg && p.preview_svg.trim() !== '') {
      previewContent = p.preview_svg;
    } else {
      // Fallback preview
      previewContent = `
        <svg viewBox="0 0 800 220" fill="none" xmlns="http://www.w3.org/2000/svg" class="cs-preview-svg">
          <rect x="20" y="16" width="760" height="32" rx="6" fill="rgba(255,255,255,0.08)"/>
          <circle cx="40" cy="32" r="7" fill="rgba(255,255,255,0.25)"/>
          <circle cx="62" cy="32" r="7" fill="rgba(255,255,255,0.15)"/>
          <circle cx="84" cy="32" r="7" fill="rgba(255,255,255,0.1)"/>
          <rect x="20" y="64" width="760" height="140" rx="8" fill="rgba(255,255,255,0.04)" stroke="rgba(255,255,255,0.12)" stroke-width="1"/>
          <text x="400" y="140" font-size="14" fill="rgba(255,255,255,0.7)" font-family="monospace" text-anchor="middle">${title}</text>
        </svg>`;
    }

    return `
    <div class="card-stack__item cs--${cardNum}" style="--stack-i: ${cardNum};">
      <div class="card-stack__item-inner">
        <div class="card-stack__item-top">
          <div class="cs-left">
            <span class="cs-badge">${badge}</span>
            <h3 class="cs-title">${title}</h3>
            <p class="cs-desc">${desc}</p>
            <div class="cs-links">
              ${linksHtml}
            </div>
          </div>
          <div class="cs-right">
            <div class="cs-stack-wrap">
              <p class="cs-stack-label">Stack technique</p>
              <div class="cs-stack">
                ${stackHtml}
              </div>
            </div>
          </div>
        </div>
        <div class="card-stack__item-bottom cs-preview">
          ${previewContent}
        </div>
      </div>
    </div>`;
  }

  async function loadProjectsFromBackend() {
    const container = document.getElementById('portfolioCardStack') || document.querySelector('.card-stack');
    if (!container) return;

    try {
      const response = await fetch('api/projects.php', { cache: 'no-store' });
      if (!response.ok) return;

      const data = await response.json();
      if (!data || !data.success || !Array.isArray(data.projects) || data.projects.length === 0) {
        return; // Conserver les cartes statiques par défaut
      }

      // Remplacer dynamiquement le contenu avec les projets MySQL
      const renderedCards = data.projects.map((proj, idx) => renderCard(proj, idx)).join('\n');
      container.innerHTML = renderedCards;

      // Recalculer les déclencheurs ScrollTrigger GSAP si disponibles
      if (typeof ScrollTrigger !== 'undefined' && ScrollTrigger.refresh) {
        ScrollTrigger.refresh();
      }
    } catch (err) {
      console.warn('Chargement des projets depuis la base : maintien du cache statique.', err);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadProjectsFromBackend);
  } else {
    loadProjectsFromBackend();
  }
})();
