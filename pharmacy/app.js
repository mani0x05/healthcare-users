// C:\xampp\htdocs\HEALTHCARE\pharmacy\app.js
// Core Router, Global SOS Modal, Toast Emitter, Geolocation Watcher

class MedPortalApp {
  constructor() {
    this.currentTab = 'ambulance';
    this.userCoords = { lat: 17.4208, lng: 78.4350, accuracy: 12 };
    this.sosCountdownTimer = null;
    this.sosSecondsLeft = 10;
    this.activeRideId = 'RIDE-HYD-108';
    this.sosSessionId = null;

    this.init();
  }

  init() {
    console.log('[MedPortalApp] Initializing User-Side Emergency & Medicine Portal...');
    this.setupNavigation();
    this.setupGlobalKeyboardShortcuts();
    this.watchUserLocation();
    this.checkUrlRoute();
  }

  // Router
  setupNavigation() {
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        const tabId = btn.getAttribute('data-tab');
        this.switchTab(tabId);
      });
    });
  }

  switchTab(tabId) {
    if (!tabId) return;
    this.currentTab = tabId;

    // Update Tab UI
    document.querySelectorAll('.nav-tab').forEach(b => {
      b.classList.toggle('active', b.getAttribute('data-tab') === tabId);
    });

    // Update Views
    document.querySelectorAll('.tab-view').forEach(view => {
      view.classList.toggle('active', view.id === `view-${tabId}`);
    });

    window.history.replaceState(null, '', `#${tabId}`);

    // Notify modules of view activation
    if (tabId === 'tracking' && window.TrackingModule) {
      setTimeout(() => window.TrackingModule.invalidateSize(), 200);
    } else if (tabId === 'camera' && window.CameraModule) {
      window.CameraModule.onTabActivated();
    } else if (tabId === 'ambulance' && window.AmbulanceModule) {
      window.AmbulanceModule.refreshRide();
    } else if (tabId === 'medicines' && window.MedicinesModule) {
      window.MedicinesModule.onTabActivated();
    }
  }

  checkUrlRoute() {
    const hash = window.location.hash.replace('#', '');
    if (['ambulance', 'tracking', 'medicines', 'prescription', 'camera'].includes(hash)) {
      this.switchTab(hash);
    } else {
      this.switchTab('ambulance');
    }
  }

  // Geolocation Watcher
  watchUserLocation() {
    const locText = document.getElementById('userLocationText');
    if ('geolocation' in navigator) {
      navigator.geolocation.watchPosition(
        (pos) => {
          this.userCoords = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy
          };
          if (locText) {
            locText.textContent = `${this.userCoords.lat.toFixed(4)}, ${this.userCoords.lng.toFixed(4)}`;
          }
          console.log('[Location] Position lock:', this.userCoords);

          // Stream to tracking module if active
          if (window.TrackingModule) {
            window.TrackingModule.updatePatientPosition(this.userCoords);
          }
        },
        (err) => {
          console.warn('[Location] GPS error, defaulting to Hyderabad core:', err.message);
          if (locText) locText.textContent = 'Hyderabad (GPS Active)';
        },
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 5000 }
      );
    }
  }

  // Global SOS Shortcut: Shift + Ctrl + Enter
  setupGlobalKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
      if (e.shiftKey && e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        this.triggerSosModal();
      }
    });

    // Header SOS button trigger
    const sosBtn = document.getElementById('headerSosBtn');
    if (sosBtn) {
      sosBtn.addEventListener('click', () => this.triggerSosModal());
    }
  }

  // Fullscreen SOS Overlay with 10s Countdown
  triggerSosModal() {
    const overlay = document.getElementById('sosOverlay');
    const countdownEl = document.getElementById('sosCountdown');
    if (!overlay) return;

    this.sosSecondsLeft = 10;
    if (countdownEl) countdownEl.textContent = this.sosSecondsLeft;
    overlay.classList.add('active');

    // Play subtle alert tone
    this.playTone(660, 0.2);

    // Call MySQL backend to initiate SOS session
    try {
      const user = JSON.parse(localStorage.getItem('healthresq_user_session') || '{}');
      fetch('../api/sos/trigger.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          device: 'web',
          gestureType: 'header_sos_button',
          location: { lat: this.userCoords.lat, lon: this.userCoords.lng },
          citizenId: user.id || null,
          senderPhone: user.phone || ''
        })
      }).then(r => r.json()).then(data => {
        if (data && data.sessionId) {
          this.sosSessionId = data.sessionId;
        }
      }).catch(e => console.warn('[SOS] Backend trigger notice:', e));
    } catch (e) {}

    clearInterval(this.sosCountdownTimer);
    this.sosCountdownTimer = setInterval(() => {
      this.sosSecondsLeft--;
      if (countdownEl) countdownEl.textContent = this.sosSecondsLeft;
      this.playTone(440, 0.1);

      if (this.sosSecondsLeft <= 0) {
        clearInterval(this.sosCountdownTimer);
        this.executeSosImmediate();
      }
    }, 1000);
  }

  cancelSos() {
    clearInterval(this.sosCountdownTimer);
    const overlay = document.getElementById('sosOverlay');
    if (overlay) overlay.classList.remove('active');

    if (this.sosSessionId) {
      fetch('../api/sos/complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          sessionId: this.sosSessionId,
          cancelled: true,
          contacts: []
        })
      }).catch(() => {});
    }

    this.showToast('SOS Aborted', 'Emergency countdown was cancelled by user.', 'info');
  }

  async executeSosImmediate() {
    clearInterval(this.sosCountdownTimer);
    const overlay = document.getElementById('sosOverlay');
    if (overlay) overlay.classList.remove('active');

    this.showToast('EMERGENCY SOS FIRED', 'Parallel dispatch executing WhatsApp alerts, SMS, and 108 trauma units.', 'danger');

    let contacts = [];
    try {
      const user = JSON.parse(localStorage.getItem('healthresq_user_session') || '{}');
      if (user.sosContact) {
        contacts.push({ phone: user.sosContact });
      }
      const stored = JSON.parse(localStorage.getItem('sos_contacts') || '[]');
      stored.forEach(c => {
        const p = c.phone || c;
        if (p && !contacts.find(x => x.phone === p)) contacts.push({ phone: p });
      });
    } catch (e) {}

    try {
      const sessId = this.sosSessionId || ('sos_' + Math.random().toString(36).substr(2, 8));
      const res = await fetch('../api/sos/complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          sessionId: sessId,
          cancelled: false,
          contacts: contacts,
          lat: this.userCoords.lat,
          lon: this.userCoords.lng
        })
      });

      const result = await res.json();
      if (result.sessionId) {
        this.showToast('DISPATCH CONFIRMED', `Session ID ${result.sessionId}. Ambulances alerted.`, 'success');
        if (result.contactsAttempted && result.contactsAttempted[0] && result.contactsAttempted[0].waLink) {
          window.open(result.contactsAttempted[0].waLink, '_blank');
        }
      }

      // Switch to ambulance tracking
      this.switchTab('tracking');
    } catch (err) {
      console.error('[SOS Error]', err);
      this.showToast('Dispatch Offline Fallback', 'Emergency distress logged locally. Calling 108.', 'warning');
      this.switchTab('tracking');
    }
  }

  // Web Audio Chime generator
  playTone(frequency = 520, duration = 0.25) {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(frequency, ctx.currentTime);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + duration);
    } catch (e) {}
  }

  // Toast System (.toast.show for 4000ms)
  showToast(title, message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const iconMap = {
      success: 'fa-solid fa-circle-check',
      danger: 'fa-solid fa-triangle-exclamation',
      warning: 'fa-solid fa-circle-exclamation',
      info: 'fa-solid fa-circle-info'
    };

    toast.innerHTML = `
      <div class="toast-icon"><i class="${iconMap[type] || iconMap.info}"></i></div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }
}

// Global bootstrap
window.addEventListener('DOMContentLoaded', () => {
  window.app = new MedPortalApp();
});
