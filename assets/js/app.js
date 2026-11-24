/**
 * assets/js/app.js
 * Interactions globales de l'application (Menu mobile, alertes, etc.)
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // 1. Fermeture interactive des alertes flash
  document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', () => {
      const alert = btn.closest('.alert');
      if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        alert.style.transition = 'all 0.25s ease';
        setTimeout(() => alert.remove(), 250);
      }
    });
  });

  // 2. Bascule du menu de navigation mobile
  const mobileToggle = document.querySelector('.mobile-nav-toggle');
  const navLinks = document.querySelector('.nav-links');

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      const isOpen = navLinks.classList.contains('open');
      mobileToggle.setAttribute('aria-expanded', isOpen);
    });

    // Fermeture du menu si on clique en dehors
    document.addEventListener('click', (e) => {
      if (!navLinks.contains(e.target) && !mobileToggle.contains(e.target)) {
        navLinks.classList.remove('open');
      }
    });
  }
});
