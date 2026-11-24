/**
 * assets/js/church-x.js
 * Logique d'interaction Church X :
 * - Navbar opacité au scroll
 * - Bascule thème 3 modes (Dark / Light / Auto)
 * - Carrousel horizontal tactile + flèches
 * - Menu hamburger mobile plein écran
 * - IntersectionObserver pour fade-in au scroll
 * - Parallaxe doux sur le hero
 * - Blur-up images progressif
 */

(function () {
  'use strict';

  // 1. GESTION DU THÈME (Clair / Sombre / Auto-Système)
  const THEME_STORAGE_KEY = 'eglise_app_theme_mode';
  const htmlEl = document.documentElement;

  function applyThemeMode(mode) {
    if (mode === 'auto') {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      htmlEl.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    } else {
      htmlEl.setAttribute('data-theme', mode);
    }
    localStorage.setItem(THEME_STORAGE_KEY, mode);
    document.cookie = `app_theme=${htmlEl.getAttribute('data-theme')};path=/;max-age=31536000;SameSite=Lax`;

    // Met à jour les boutons UI
    document.querySelectorAll('.theme-mode-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-mode') === mode);
    });

    // Déclenche un événement global pour Chart.js ou autre écouteur
    window.dispatchEvent(new CustomEvent('churchThemeChanged', { detail: { mode, resolved: htmlEl.getAttribute('data-theme') } }));
  }

  // Écouteur système si en mode auto
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (localStorage.getItem(THEME_STORAGE_KEY) === 'auto') {
      applyThemeMode('auto');
    }
  });

  // Initialisation des boutons de thème
  document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem(THEME_STORAGE_KEY) || 'dark';
    applyThemeMode(savedMode);

    document.querySelectorAll('.theme-mode-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        applyThemeMode(btn.getAttribute('data-mode'));
      });
    });
  });

  // 2. NAVBAR BLUR & SCROLL OPACITY
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.church-header');
    if (!navbar) return;
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }

    // Parallaxe doux sur le fond du hero (sauf si prefers-reduced-motion)
    const heroBg = document.querySelector('.church-hero');
    if (heroBg && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      const offset = window.scrollY * 0.35;
      heroBg.style.backgroundPositionY = `calc(50% + ${offset}px)`;
    }
  }, { passive: true });

  // 3. MENU HAMBURGER PLEIN ÉCRAN MOBILE
  document.addEventListener('DOMContentLoaded', () => {
    const burgerBtn = document.querySelector('.hamburger-btn');
    const mobileMenu = document.querySelector('.mobile-fullscreen-menu');
    const closeBtn = document.querySelector('.mobile-menu-close');

    if (burgerBtn && mobileMenu) {
      burgerBtn.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.toggle('open');
        burgerBtn.setAttribute('aria-expanded', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });

      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          mobileMenu.classList.remove('open');
          burgerBtn.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        });
      }
    }
  });

  // 4. CARROUSEL HORIZONTAL TACTILE & CONTRÔLES FLÈCHES
  window.initChurchCarousel = function (trackSelector, prevBtnSelector, nextBtnSelector) {
    const track = document.querySelector(trackSelector);
    const prevBtn = document.querySelector(prevBtnSelector);
    const nextBtn = document.querySelector(nextBtnSelector);

    if (!track) return;

    const scrollAmount = 380;

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
      });
    }
  };

  // 5. APPARITION PROGRESSIVE AU SCROLL (IntersectionObserver)
  document.addEventListener('DOMContentLoaded', () => {
    const fadeElements = document.querySelectorAll('.fade-in-on-scroll');
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.15,
        rootMargin: '0px 0px -40px 0px'
      });

      fadeElements.forEach(el => observer.observe(el));
    } else {
      fadeElements.forEach(el => el.classList.add('is-visible'));
    }

    // 6. BLUR-UP IMAGES
    const blurImages = document.querySelectorAll('.blur-up-image');
    blurImages.forEach(img => {
      if (img.complete) {
        img.classList.add('loaded');
      } else {
        img.addEventListener('load', () => img.classList.add('loaded'));
      }
    });

    // Initialiser le carrousel s'il est présent
    initChurchCarousel('.carousel-church-track', '#carousel-prev-btn', '#carousel-next-btn');
  });

})();
