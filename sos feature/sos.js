/**
 * SosController — Emergency SOS Logic for HealthResQ Healthcare Portal (WhatsApp edition).
 * Handles countdown overlay, audio alert, GPS breadcrumb streaming,
 * MySQL backend sessions, and WhatsApp dispatch.
 */
export class SosController {
  constructor() {
    this.countdownSecs = parseInt(localStorage.getItem('sos_countdown') || '10', 10);
    this.apiBase       = this._resolveApiBase();
    this.idToken       = localStorage.getItem('sos_id_token') || '';
    this.senderPhone   = localStorage.getItem('sos_sender_phone') || '';
    this.mode          = localStorage.getItem('sos_mode') || 'testing';
    this.contacts      = this._loadContacts();

    this._timer          = null;
    this._remaining      = 0;
    this._sessionId      = null;
    this._locationWatcher= null;
    this._lastLocation   = null;
    this._triggered      = false;
    this._locationUpdates= 0;
    this._audioCtx       = null;
  }

  _resolveApiBase() {
    const saved = localStorage.getItem('sos_api_url');
    if (saved && saved.trim() !== '') return saved.trim();

    // Auto-detect XAMPP /HEALTHCARE path
    const origin = window.location.origin;
    if (window.location.pathname.toLowerCase().includes('/healthcare')) {
      return origin + '/HEALTHCARE';
    }
    return '';
  }

  _loadContacts() {
    try {
      const saved = localStorage.getItem('sos_contacts');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return parsed;
      }

      // Fallback: check logged-in HealthResQ citizen session
      const userSessionStr = localStorage.getItem('healthresq_user_session');
      if (userSessionStr) {
        const user = JSON.parse(userSessionStr);
        if (user && user.sosContact) {
          // Extract phone number from e.g. "Priya Sharma (+91 98765 43211)"
          const digits = user.sosContact.replace(/[^0-9+]/g, '');
          if (digits) {
            const initialList = [{ phone: digits.startsWith('+') ? digits : ('+91' + digits) }];
            localStorage.setItem('sos_contacts', JSON.stringify(initialList));
            return initialList;
          }
        }
      }
    } catch (e) {
      console.warn('[SOS] Error loading contacts:', e);
    }
    return [];
  }

  init() {
    this._bindConfigUI();
    this._bindSosButton();
    this._bindCancelButton();
    if (this.mode === 'testing') this._registerKeyboardShortcut();
    this._bindNativeBridge();
    this._renderContactList();
    this._prefillCitizenInfo();
    console.log('[SOS] Controller initialized. API Base:', this.apiBase, 'Mode:', this.mode);
  }

  _prefillCitizenInfo() {
    try {
      const userSessionStr = localStorage.getItem('healthresq_user_session');
      if (userSessionStr) {
        const user = JSON.parse(userSessionStr);
        const citizenNotice = document.getElementById('citizen-notice');
        if (citizenNotice && user) {
          citizenNotice.innerHTML = `Connected as <strong>${user.name || 'Citizen'}</strong> (${user.bloodGroup || 'Blood Group N/A'} | ID: ${user.id || 'CIT'})`;
          citizenNotice.style.display = 'block';
        }
        if (!this.senderPhone && user.phone) {
          this.senderPhone = user.phone.replace(/[^0-9+]/g, '');
          const senderEl = document.getElementById('sender-phone');
          if (senderEl) senderEl.value = this.senderPhone;
          localStorage.setItem('sos_sender_phone', this.senderPhone);
        }
      }
    } catch (e) {
      console.warn('[SOS] Error reading citizen info:', e);
    }
  }

  // ─── Trigger ─────────────────────────────────────────────────────────────

  async trigger(gestureType = 'keyboard_shortcut') {
    if (this._triggered) return;
    this._triggered = true;
    console.log('[SOS] Triggered:', gestureType);
    this._playBeep(880, 0.25);
    this._showOverlay();
    this._startLocationWatch();
    await this._postTrigger(gestureType);
    this._startCountdown();
  }

  // ─── Sound FX ────────────────────────────────────────────────────────────

  _playBeep(freq = 880, duration = 0.2) {
    try {
      if (!this._audioCtx) {
        this._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (this._audioCtx.state === 'suspended') {
        this._audioCtx.resume();
      }
      const osc = this._audioCtx.createOscillator();
      const gain = this._audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(freq, this._audioCtx.currentTime);
      gain.gain.setValueAtTime(0.3, this._audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, this._audioCtx.currentTime + duration);
      osc.connect(gain);
      gain.connect(this._audioCtx.destination);
      osc.start();
      osc.stop(this._audioCtx.currentTime + duration);
    } catch (e) {
      // AudioContext may be restricted before user interaction
    }
  }

  // ─── Keyboard shortcut (Testing mode) ────────────────────────────────────

  _registerKeyboardShortcut() {
    window.addEventListener('keydown', (e) => {
      if (e.shiftKey && (e.metaKey || e.key === 'Meta' || e.ctrlKey) && e.key === 'Enter') {
        e.preventDefault();
        this.trigger('keyboard_shortcut');
      }
    });
    console.log('[SOS] Keyboard shortcut active: Shift + Win/Ctrl + Enter');
  }

  // ─── Native WebView bridge ────────────────────────────────────────────────

  _bindNativeBridge() {
    window.addEventListener('message', (e) => {
      if (e.data?.type === 'SOS_TRIGGERED') {
        this.trigger(e.data.gestureType || 'power_volume_up');
      }
    });
  }

  // ─── Overlay ──────────────────────────────────────────────────────────────

  _showOverlay() {
    document.getElementById('overlay').classList.add('active');
    document.getElementById('countdown').textContent = this.countdownSecs;
    document.getElementById('status-panel').style.display = 'none';
    if (navigator.vibrate) navigator.vibrate([400, 100, 400, 100, 400]);
  }

  _hideOverlay() {
    document.getElementById('overlay').classList.remove('active');
  }

  // ─── Countdown ────────────────────────────────────────────────────────────

  _startCountdown() {
    this._remaining = this.countdownSecs;
    this._timer = setInterval(async () => {
      this._remaining--;
      document.getElementById('countdown').textContent = this._remaining;
      this._playBeep(this._remaining <= 3 ? 950 : 800, 0.15);

      if (this._remaining <= 0) {
        clearInterval(this._timer);
        this._hideOverlay();
        await this._complete(false);
      }
    }, 1000);
  }

  _cancelCountdown() {
    if (this._timer) clearInterval(this._timer);
    this._timer = null;
  }

  // ─── GPS Location ─────────────────────────────────────────────────────────

  _startLocationWatch() {
    if (!navigator.geolocation) {
      console.warn('[SOS] Geolocation not supported by browser.');
      return;
    }
    this._locationWatcher = navigator.geolocation.watchPosition(
      (pos) => {
        const newLoc = { lat: pos.coords.latitude, lon: pos.coords.longitude };
        this._lastLocation = newLoc;
        console.log('[SOS] GPS updated:', newLoc);
        if (this._sessionId) this._postLocationUpdate();
      },
      (err) => console.warn('[SOS] GPS watch error:', err.message),
      { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
    );
  }

  _stopLocationWatch() {
    if (this._locationWatcher !== null) {
      navigator.geolocation.clearWatch(this._locationWatcher);
      this._locationWatcher = null;
    }
  }

  async _postLocationUpdate() {
    if (!this._sessionId || !this._lastLocation) return;
    this._locationUpdates++;
    try {
      await this._fetch(`/api/sos/location/${this._sessionId}`, 'POST', {
        sessionId: this._sessionId,
        ...this._lastLocation,
        updateNumber: this._locationUpdates,
      }, `/api/sos/location.php?sessionId=${encodeURIComponent(this._sessionId)}`);
    } catch (e) {
      console.warn('[SOS] Location update post failed:', e.message);
    }
  }

  // ─── API calls ────────────────────────────────────────────────────────────

  async _postTrigger(gestureType) {
    try {
      let citizenId = null;
      try {
        const user = JSON.parse(localStorage.getItem('healthresq_user_session') || '{}');
        citizenId = user.id || null;
      } catch (e) {}

      const body = {
        device:      this._detectDevice(),
        gestureType,
        timestamp:   Math.floor(Date.now() / 1000),
        location:    this._lastLocation,
        senderPhone: this.senderPhone,
        citizenId:   citizenId
      };
      const res = await this._fetch('/api/sos/trigger', 'POST', body, '/api/sos/trigger.php');
      if (res && res.sessionId) {
        this._sessionId    = res.sessionId;
        this.countdownSecs = res.countdownSeconds ?? this.countdownSecs;
        document.getElementById('countdown').textContent = this.countdownSecs;
      }
    } catch (err) {
      console.error('[SOS] Trigger API failed:', err);
    }
  }

  async _complete(cancelled) {
    this._stopLocationWatch();
    try {
      const body = {
        cancelled,
        contacts: this.contacts,
        lat: this._lastLocation ? this._lastLocation.lat : null,
        lon: this._lastLocation ? this._lastLocation.lon : null
      };

      const path = this._sessionId
        ? `/api/sos/complete/${this._sessionId}`
        : '/api/sos/complete/unknown';
      const fallbackPath = this._sessionId
        ? `/api/sos/complete.php?sessionId=${encodeURIComponent(this._sessionId)}`
        : '/api/sos/complete.php';

      const res = await this._fetch(path, 'POST', body, fallbackPath);
      this._showFeedback(res, cancelled);
    } catch (err) {
      this._showFeedback({ error: err.message }, cancelled);
    } finally {
      this._triggered       = false;
      this._sessionId       = null;
      this._locationUpdates = 0;
    }
  }

  async _fetch(path, method, body, fallbackDirectPath = null) {
    const url = (this.apiBase || '') + path;
    const headers = { 'Content-Type': 'application/json' };
    if (this.idToken) headers['Authorization'] = `Bearer ${this.idToken}`;

    try {
      const res = await fetch(url, { method, headers, body: JSON.stringify(body) });
      if (!res.ok && res.status === 404 && fallbackDirectPath) {
        // Fallback to direct PHP endpoint if URL rewriting is not configured
        const fallbackUrl = (this.apiBase || '') + fallbackDirectPath;
        const fallbackRes = await fetch(fallbackUrl, { method, headers, body: JSON.stringify(body) });
        return fallbackRes.json();
      }
      return res.json();
    } catch (e) {
      if (fallbackDirectPath) {
        const fallbackUrl = (this.apiBase || '') + fallbackDirectPath;
        const fallbackRes = await fetch(fallbackUrl, { method, headers, body: JSON.stringify(body) });
        return fallbackRes.json();
      }
      throw e;
    }
  }

  // ─── Feedback UI ──────────────────────────────────────────────────────────

  _showFeedback(data, cancelled) {
    const panel = document.getElementById('status-panel');
    const list  = document.getElementById('status-list');
    list.innerHTML = '';

    const add = (content, cssClass = '', isHtml = false) => {
      const li = document.createElement('li');
      if (cssClass) li.classList.add(cssClass);
      if (isHtml) li.innerHTML = content;
      else li.textContent = content;
      list.appendChild(li);
    };

    if (data.error) {
      add(`❌ Error: ${data.error}`, 'error');
    } else if (cancelled || data.status === 'cancelled') {
      add('✅ SOS cancelled — no emergency dispatch triggered.', 'ok');
    } else {
      add(`📋 Emergency Session: ${data.sessionId || 'Recorded'}`, 'ok');
      const contacts = data.contactsAttempted || [];
      if (contacts.length === 0) {
        add('⚠️ No trusted contacts configured. Please add contacts below.', 'error');
      } else {
        contacts.forEach(c => {
          const locIcon = c.locationResult ? '📍' : '🔘';
          const waAction = c.waLink
            ? ` <a href="${c.waLink}" target="_blank" class="wa-action-btn" title="Send WhatsApp alert now">Open WhatsApp 📲</a>`
            : '';
          add(
            `${locIcon} <strong>${c.phone}</strong> — WhatsApp Alert Ready! ${waAction}`,
            'ok',
            true
          );
        });
      }

      if (data.location && data.location.mapsUrl && data.location.lat) {
        add(
          `📍 <a href="${data.location.mapsUrl}" target="_blank" style="color: #60a5fa; text-decoration: underline;">View Live GPS Beacon on Google Maps (${data.location.lat.toFixed(4)}, ${data.location.lon.toFixed(4)})</a>`,
          '',
          true
        );
      }
      add(`🕒 Timestamp: ${new Date().toLocaleTimeString()} (${new Date().toLocaleDateString()})`);
    }

    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth' });
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────

  _detectDevice() {
    if (/android/i.test(navigator.userAgent)) return 'android';
    if (/ipad|iphone|ipod/i.test(navigator.userAgent)) return 'ios';
    return 'web';
  }

  // ─── UI bindings ──────────────────────────────────────────────────────────

  _bindSosButton() {
    const btn = document.getElementById('sos-btn');
    if (btn) btn.addEventListener('click', () => this.trigger('manual_button'));
  }

  _bindCancelButton() {
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', async () => {
        this._cancelCountdown();
        this._hideOverlay();
        await this._complete(true);
      });
    }
  }

  _bindConfigUI() {
    const cfgCountdown = document.getElementById('countdown-cfg');
    const cfgApi = document.getElementById('api-url');
    const cfgToken = document.getElementById('id-token');
    const cfgSender = document.getElementById('sender-phone');
    const cfgMode = document.getElementById('mode');

    if (cfgCountdown) cfgCountdown.value  = this.countdownSecs;
    if (cfgApi)       cfgApi.value        = this.apiBase;
    if (cfgToken)     cfgToken.value      = this.idToken;
    if (cfgSender)    cfgSender.value     = this.senderPhone;
    if (cfgMode)      cfgMode.value       = this.mode;

    const addBtn = document.getElementById('add-contact');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        const input = document.getElementById('new-phone');
        let phone = input.value.trim();
        if (!phone) return;
        if (!phone.startsWith('+') && phone.length === 10) phone = '+91' + phone;

        if (!this.contacts.find(c => (c.phone || c) === phone)) {
          this.contacts.push({ phone });
          this._saveContacts();
          this._renderContactList();
        }
        input.value = '';
      });
    }

    const saveBtn = document.getElementById('save-config');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => {
        this.countdownSecs = parseInt(cfgCountdown.value, 10) || 10;
        this.apiBase       = cfgApi.value.trim();
        this.idToken       = cfgToken.value.trim();
        this.senderPhone   = cfgSender.value.trim();
        this.mode          = cfgMode.value;

        localStorage.setItem('sos_countdown',    this.countdownSecs);
        localStorage.setItem('sos_api_url',      this.apiBase);
        localStorage.setItem('sos_id_token',     this.idToken);
        localStorage.setItem('sos_sender_phone', this.senderPhone);
        localStorage.setItem('sos_mode',         this.mode);

        alert('Settings saved successfully!');
      });
    }
  }

  _renderContactList() {
    const ul = document.getElementById('contact-list');
    if (!ul) return;
    ul.innerHTML = '';
    this.contacts.forEach((c, i) => {
      const li  = document.createElement('li');
      li.textContent = c.phone || c;
      const btn = document.createElement('button');
      btn.textContent = 'Remove';
      btn.addEventListener('click', () => {
        this.contacts.splice(i, 1);
        this._saveContacts();
        this._renderContactList();
      });
      li.appendChild(btn);
      ul.appendChild(li);
    });
  }

  _saveContacts() {
    localStorage.setItem('sos_contacts', JSON.stringify(this.contacts));
  }
}

// Native WebView bridge
window.SosBridge = {
  trigger: (gestureType) => window.__sosCtrl?.trigger(gestureType ?? 'power_volume_up'),
};
