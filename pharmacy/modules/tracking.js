// C:\xampp\htdocs\HEALTHCARE\pharmacy\modules\tracking.js
// Leaflet 1.9.4 Interactive GPS Tracking, Vehicle Telemetry & Arrival Audio Chime

class TrackingModule {
  constructor() {
    this.map = null;
    this.ambulanceMarker = null;
    this.patientMarker = null;
    this.hospitalMarker = null;
    this.routePolyline = null;
    this.audioCtx = null;

    this.ambulanceCoords = [17.4120, 78.4280];
    this.patientCoords   = [17.4208, 78.4350];
    this.hospitalCoords  = [17.4320, 78.4110];

    this.routeStep = 0;
    this.routeWaypoints = [
      [17.4120, 78.4280],
      [17.4150, 78.4300],
      [17.4180, 78.4325],
      [17.4208, 78.4350], // Patient location
      [17.4240, 78.4290],
      [17.4280, 78.4210],
      [17.4320, 78.4110]  // Hospital
    ];

    this.simTimer = null;
    this.init();
  }

  init() {
    // Map will be instantiated once DOM is ready
    window.addEventListener('DOMContentLoaded', () => {
      this.initMap();
      this.startVehicleSimulation();
    });
  }

  initMap() {
    const mapEl = document.getElementById('trackingMap');
    if (!mapEl || typeof L === 'undefined') return;

    try {
      this.map = L.map('trackingMap', {
        zoomControl: true,
        scrollWheelZoom: false
      }).setView(this.patientCoords, 14);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | HealthResQ Telemetry'
      }).addTo(this.map);

      // Custom HTML Icons
      const patientIcon = L.divIcon({
        className: 'custom-leaflet-icon',
        html: '<div style="background:#2563eb;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 14px #2563eb;font-size:14px;border:2px solid white;"><i class="fa-solid fa-user"></i></div>',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
      });

      const ambulanceIcon = L.divIcon({
        className: 'custom-leaflet-icon',
        html: '<div style="background:#ef4444;color:white;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 20px #ef4444;font-size:16px;border:2px solid white;animation:pulseAmbulance 1.2s infinite;"><i class="fa-solid fa-truck-medical"></i></div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
      });

      const hospitalIcon = L.divIcon({
        className: 'custom-leaflet-icon',
        html: '<div style="background:#16a34a;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 14px #16a34a;font-size:15px;border:2px solid white;"><i class="fa-solid fa-hospital"></i></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
      });

      this.patientMarker = L.marker(this.patientCoords, { icon: patientIcon }).addTo(this.map)
        .bindPopup('<strong>Patient Location</strong><br>GPS Signal Active');

      this.ambulanceMarker = L.marker(this.ambulanceCoords, { icon: ambulanceIcon }).addTo(this.map)
        .bindPopup('<strong>108 Trauma Unit (TS-09-EM-1081)</strong><br>Speed: 48 km/h &bull; Driver: Rajesh Goud');

      this.hospitalMarker = L.marker(this.hospitalCoords, { icon: hospitalIcon }).addTo(this.map)
        .bindPopup('<strong>Apollo Hospitals</strong><br>Jubilee Hills Emergency Trauma Center');

      // Routing Polyline
      this.routePolyline = L.polyline(this.routeWaypoints, {
        color: '#ef4444',
        weight: 5,
        opacity: 0.85,
        dashArray: '8, 8'
      }).addTo(this.map);

    } catch (e) {
      console.warn('[TrackingModule] Map init error:', e);
    }
  }

  startVehicleSimulation() {
    this.simTimer = setInterval(() => {
      this.routeStep = (this.routeStep + 1) % this.routeWaypoints.length;
      const nextPos = this.routeWaypoints[this.routeStep];

      if (this.ambulanceMarker) {
        this.ambulanceMarker.setLatLng(nextPos);
      }

      // Dynamic ETA calculation
      const remainingSteps = this.routeWaypoints.length - this.routeStep;
      const etaMins = Math.max(1, Math.round(remainingSteps * 0.9));
      const pillText = document.getElementById('etaPillText');
      if (pillText) {
        pillText.textContent = `ETA: ~${etaMins} MINS`;
      }

      // If at patient pickup position (step 3), play chime
      if (this.routeStep === 3) {
        this.playArrivalChime();
        if (window.app) window.app.showToast('Ambulance Arrived', 'TS-09-EM-1081 is at your doorstep.', 'danger');
      }
    }, 3500);
  }

  updatePatientPosition(coords) {
    if (coords && coords.lat && coords.lng) {
      this.patientCoords = [coords.lat, coords.lng];
      if (this.patientMarker) {
        this.patientMarker.setLatLng(this.patientCoords);
      }
    }
  }

  updateHospitalTarget(hosp) {
    if (!hosp) return;
    const pillText = document.getElementById('etaPillText');
    if (pillText) {
      pillText.textContent = `ETA: ~${hosp.etaMins} MINS`;
    }
  }

  invalidateSize() {
    if (this.map) {
      this.map.invalidateSize();
      if (this.ambulanceMarker) {
        this.map.panTo(this.ambulanceMarker.getLatLng());
      }
    }
  }

  // Audio synthesizer for arrival chime
  playArrivalChime() {
    try {
      if (!this.audioCtx) {
        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (this.audioCtx.state === 'suspended') {
        this.audioCtx.resume();
      }

      const notes = [587.33, 880, 1174.66]; // D5, A5, D6
      notes.forEach((freq, idx) => {
        const osc = this.audioCtx.createOscillator();
        const gain = this.audioCtx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime + (idx * 0.18));
        gain.gain.setValueAtTime(0.2, this.audioCtx.currentTime + (idx * 0.18));
        gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + (idx * 0.18) + 0.35);
        osc.connect(gain);
        gain.connect(this.audioCtx.destination);
        osc.start(this.audioCtx.currentTime + (idx * 0.18));
        osc.stop(this.audioCtx.currentTime + (idx * 0.18) + 0.4);
      });
    } catch (e) {}
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.TrackingModule = new TrackingModule();
});
