// C:\xampp\htdocs\HEALTHCARE\pharmacy\modules\ambulance.js
// In-Ride Telemetry, Vitals Stream, Periodic Check, and Hospital Rerouter

class AmbulanceModule {
  constructor() {
    this.currentRideCode = 'RIDE-HYD-108';
    this.feedbackSeconds = 60;
    this.feedbackTimer = null;
    this.vitalsInterval = null;

    this.hospitals = [
      { id: 'hosp-1', name: 'Apollo Hospitals - Jubilee Hills', distKm: 3.2, etaMins: 5, traumaLevel: 'Level 1 Trauma', active: true },
      { id: 'hosp-2', name: 'Care Hospitals - Banjara Hills', distKm: 5.8, etaMins: 9, traumaLevel: 'Level 1 Trauma', active: false },
      { id: 'hosp-3', name: 'Yashoda Hospitals - Somajiguda', distKm: 7.4, etaMins: 12, traumaLevel: 'Emergency Critical Care', active: false },
      { id: 'hosp-4', name: 'KIMS Hospitals - Secunderabad', distKm: 11.2, etaMins: 18, traumaLevel: 'Multi-Specialty Trauma', active: false }
    ];

    this.init();
  }

  init() {
    this.bindEvents();
    this.startVitalsSimulation();
    this.startFeedbackTimer();
    this.renderHospitals();
  }

  bindEvents() {
    const btnJoin = document.getElementById('btnJoinRide');
    const inputRide = document.getElementById('inputRideCode');
    if (btnJoin && inputRide) {
      btnJoin.addEventListener('click', () => {
        const code = inputRide.value.trim().toUpperCase();
        if (code) {
          this.currentRideCode = code;
          window.app.showToast('Ride Linked', `Connected to emergency telemetry for ${code}`, 'success');
        }
      });
    }

    const painSlider = document.getElementById('inputPainScale');
    const painBadge = document.getElementById('painValueBadge');
    if (painSlider && painBadge) {
      painSlider.addEventListener('input', () => {
        painBadge.textContent = painSlider.value;
      });
    }

    const formFeedback = document.getElementById('formInRideFeedback');
    if (formFeedback) {
      formFeedback.addEventListener('submit', (e) => {
        e.preventDefault();
        const pain = document.getElementById('inputPainScale')?.value || '4';
        const cons = document.getElementById('selectConsciousness')?.value || 'alert';
        const breath = document.getElementById('selectBreathing')?.value || 'normal';
        const notes = document.getElementById('inputFeedbackNotes')?.value || 'Stable';

        window.app.showToast('Vitals Submitted', `Transmitted to trauma center: Pain ${pain}/10, Resp: ${breath}`, 'success');
        this.feedbackSeconds = 60;
        if (document.getElementById('inputFeedbackNotes')) {
          document.getElementById('inputFeedbackNotes').value = '';
        }
      });
    }
  }

  startVitalsSimulation() {
    this.vitalsInterval = setInterval(() => {
      const hrEl = document.getElementById('vitalHeartRate');
      const bpEl = document.getElementById('vitalBp');
      const o2El = document.getElementById('vitalSpo2');

      if (hrEl) hrEl.textContent = `${84 + Math.floor(Math.random() * 8)} BPM`;
      if (bpEl) bpEl.textContent = `${122 + Math.floor(Math.random() * 6)}/${80 + Math.floor(Math.random() * 4)}`;
      if (o2El) o2El.textContent = `${97 + Math.floor(Math.random() * 3)}%`;
    }, 4000);
  }

  startFeedbackTimer() {
    this.feedbackTimer = setInterval(() => {
      this.feedbackSeconds--;
      const badge = document.getElementById('feedbackTimerBadge');
      if (badge) badge.textContent = `${this.feedbackSeconds}s`;

      if (this.feedbackSeconds <= 0) {
        this.feedbackSeconds = 60;
        window.app.showToast('Vitals Check Due', 'Please update patient consciousness and pain level.', 'warning');
      }
    }, 1000);
  }

  renderHospitals() {
    const container = document.getElementById('nearbyHospitalsList');
    if (!container) return;

    container.innerHTML = '';
    this.hospitals.forEach(h => {
      const div = document.createElement('div');
      div.className = `hospital-reroute-item ${h.active ? 'active' : ''}`;
      div.innerHTML = `
        <div>
          <div style="font-weight:800;font-size:0.95rem;color:var(--text-main);">${h.name}</div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
            ${h.traumaLevel} &bull; ${h.distKm} km away &bull; <strong style="color:var(--primary);">${h.etaMins} mins ETA</strong>
          </div>
        </div>
        <div>
          ${h.active
            ? '<span class="badge-tag badge-danger" style="font-size:0.75rem;">CURRENT DESTINATION</span>'
            : `<button class="btn-outline" style="padding:6px 12px;font-size:0.8rem;" onclick="window.AmbulanceModule.rerouteTo('${h.id}')">
                 <i class="fa-solid fa-arrows-split-up-and-left"></i> Reroute
               </button>`
          }
        </div>
      `;
      container.appendChild(div);
    });
  }

  rerouteTo(hospId) {
    this.hospitals.forEach(h => h.active = (h.id === hospId));
    const target = this.hospitals.find(h => h.id === hospId);

    if (target) {
      const nameEl = document.getElementById('destHospitalName');
      const etaEl = document.getElementById('destHospitalEta');
      if (nameEl) nameEl.textContent = target.name;
      if (etaEl) etaEl.textContent = `ETA: ~${target.etaMins} min`;

      window.app.showToast('Destination Updated', `Rerouted ambulance trajectory to ${target.name} (ETA ${target.etaMins}m)`, 'info');
      this.renderHospitals();

      if (window.TrackingModule) {
        window.TrackingModule.updateHospitalTarget(target);
      }
    }
  }

  refreshRide() {
    console.log('[AmbulanceModule] Ride refreshed');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.AmbulanceModule = new AmbulanceModule();
});
