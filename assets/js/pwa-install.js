/**
 * assets/js/pwa-install.js
 * Gestion de l'installation de l'application PWA et de l'activation des notifications Web Push
 */

(function () {
  'use strict';

  let deferredPrompt = null;
  const topBanner = document.getElementById('pwa-top-banner');
  const topInstallBtn = document.getElementById('pwa-top-install-btn');
  const topDismissBtn = document.getElementById('pwa-top-dismiss-btn');
  const iosInstructions = document.getElementById('pwa-ios-instructions');
  const notifBtn = document.getElementById('pwa-notif-btn');

  // Détection si déjà installée en mode application autonome (standalone)
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

  if (isStandalone && topBanner) {
    topBanner.style.display = 'none';
  } else if (sessionStorage.getItem('pwa_top_dismissed') === '1' && topBanner) {
    topBanner.style.display = 'none';
  }

  // Détection Safari iOS
  const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

  // 1. Événement d'invitation à l'installation PWA (Android, Chrome, Edge)
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    if (topBanner && !isStandalone && sessionStorage.getItem('pwa_top_dismissed') !== '1') {
      topBanner.style.display = 'block';
    }
  });

  // Action sur le bouton d'installation supérieur
  if (topInstallBtn) {
    topInstallBtn.addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`Résultat de l'installation PWA : ${outcome}`);
        deferredPrompt = null;

        if (outcome === 'accepted' && topBanner) {
          topBanner.style.display = 'none';
        }
      } else if (isIos) {
        if (iosInstructions) {
          iosInstructions.style.display = 'block';
        }
      } else {
        alert('Pour installer Philadelphie Church sur votre ordinateur ou téléphone : cliquez sur l\'icône "Installer l\'application" située dans la barre d\'adresse de votre navigateur ou dans le menu (⋮).');
      }
    });
  }

  // Fermeture du bandeau supérieur
  if (topDismissBtn) {
    topDismissBtn.addEventListener('click', () => {
      if (topBanner) {
        topBanner.style.display = 'none';
      }
      sessionStorage.setItem('pwa_top_dismissed', '1');
    });
  }

  window.addEventListener('appinstalled', () => {
    console.log('Application PWA installée avec succès !');
    if (topBanner) topBanner.style.display = 'none';
  });

  // 2. Gestion des notifications Web Push
  if (notifBtn && 'Notification' in window && 'serviceWorker' in navigator) {
    if (Notification.permission === 'granted') {
      notifBtn.innerHTML = '🔔 Notifications actives';
      notifBtn.classList.add('btn-outline-gold');
      notifBtn.disabled = true;
    } else {
      notifBtn.addEventListener('click', async () => {
        try {
          const permission = await Notification.requestPermission();
          if (permission === 'granted') {
            notifBtn.innerHTML = '🔔 Notifications activées';
            notifBtn.disabled = true;

            const registration = await navigator.serviceWorker.ready;
            // Pour le test ou production, conversion et enregistrement
            const subscription = await registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: urlB64ToUint8Array('BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZ_WJJNxqhSnKEBsGLN4HN25EvwbGS5gnhKZhTM') // Clé publique VAPID de démonstration
            }).catch(async () => {
              // Si déjà souscrit, on récupère la souscription
              return await registration.pushManager.getSubscription();
            });

            if (subscription) {
              const subJson = subscription.toJSON();
              await fetch('./api/push_subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  endpoint: subscription.endpoint,
                  p256dh: subJson.keys ? subJson.keys.p256dh : '',
                  auth: subJson.keys ? subJson.keys.auth : ''
                })
              });
            }
          }
        } catch (err) {
          console.warn('Erreur activation push:', err);
        }
      });
    }
  }

  function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }
})();
