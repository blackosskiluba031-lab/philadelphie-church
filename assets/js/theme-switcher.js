/**
 * assets/js/theme-switcher.js
 * Gestion de la bascule fluide Dark/Light Mode avec persistance (localStorage + Cookie)
 * et déclenchement d'événement pour l'adaptation automatique des graphiques Chart.js
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'eglise_app_theme';
  const COOKIE_KEY = 'app_theme';

  // Icônes SVG optimisées (Soleil / Lune)
  const ICON_SUN = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="5"></circle>
      <line x1="12" y1="1" x2="12" y2="3"></line>
      <line x1="12" y1="21" x2="12" y2="23"></line>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
      <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
      <line x1="1" y1="12" x2="3" y2="12"></line>
      <line x1="21" y1="12" x2="23" y2="12"></line>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
      <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
    </svg>`;

  const ICON_MOON = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>`;

  function getStoredTheme() {
    return localStorage.getItem(STORAGE_KEY) || getCookie(COOKIE_KEY) || 'dark';
  }

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  function setCookie(name, value, days = 365) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value};path=/;expires=${d.toUTCString()};SameSite=Lax`;
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
    setCookie(COOKIE_KEY, theme);

    // Mise à jour de tous les boutons de bascule présents sur la page
    const toggleButtons = document.querySelectorAll('.theme-toggle-btn');
    toggleButtons.forEach(btn => {
      btn.innerHTML = theme === 'dark' ? ICON_SUN : ICON_MOON;
      btn.setAttribute('aria-label', theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre');
      btn.setAttribute('title', theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre');
    });

    // Événement personnalisé pour Chart.js ou autres composants
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
  }

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  }

  // Initialisation immédiate (anti-flicker)
  const initialTheme = getStoredTheme();
  document.documentElement.setAttribute('data-theme', initialTheme);

  // Attachement des écouteurs dès que le DOM est prêt
  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getStoredTheme());

    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        toggleTheme();
      });
    });
  });

  // Expose l'utilitaire globalement
  window.AppTheme = {
    get: () => document.documentElement.getAttribute('data-theme') || 'dark',
    set: applyTheme,
    toggle: toggleTheme,
  };
})();
