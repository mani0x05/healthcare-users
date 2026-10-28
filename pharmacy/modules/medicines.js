// C:\xampp\htdocs\HEALTHCARE\pharmacy\modules\medicines.js
// Medicine Catalog Search, Live Inventory Matching, and Proximity Sorter

class MedicinesModule {
  constructor() {
    this.catalog = [
      { id: 'm1', name: 'Dolo 650', generic: 'Paracetamol 650mg', category: 'Analgesic / Antipyretic', price: 32, mfr: 'Micro Labs', uses: 'Fever, Body Ache, Headache' },
      { id: 'm2', name: 'Pan 40', generic: 'Pantoprazole Gastro-Resistant 40mg', category: 'Proton Pump Inhibitor (PPI)', price: 145, mfr: 'Alkem Laboratories', uses: 'Acidity, Heartburn, Peptic Ulcers' },
      { id: 'm3', name: 'Azithral 500', generic: 'Azithromycin 500mg', category: 'Macrolide Antibiotic', price: 120, mfr: 'Alembic Pharma', uses: 'Throat, Chest & Sinus Infections' },
      { id: 'm4', name: 'Augmentin 625 Duo', generic: 'Amoxicillin + Potassium Clavulanate', category: 'Broad Spectrum Antibiotic', price: 185, mfr: 'GSK India', uses: 'Severe Bacterial Infections' },
      { id: 'm5', name: 'Okacet / Cetirizine 10mg', generic: 'Cetirizine Hydrochloride 10mg', category: 'Antihistamine / Anti-Allergy', price: 25, mfr: 'Cipla', uses: 'Runny Nose, Sneezing, Hives' },
      { id: 'm6', name: 'Glycomet 500', generic: 'Metformin Hydrochloride 500mg', category: 'Antidiabetic Biguanide', price: 42, mfr: 'USV Limited', uses: 'Blood Sugar Control in Type 2 Diabetes' },
      { id: 'm7', name: 'Telma 40', generic: 'Telmisartan 40mg', category: 'Antihypertensive ARB', price: 92, mfr: 'Glenmark Pharma', uses: 'High Blood Pressure & Cardiac Risk' },
      { id: 'm8', name: 'Atorva 10', generic: 'Atorvastatin Calcium 10mg', category: 'Statin / Lipid Lowering', price: 85, mfr: 'Zydus Cadila', uses: 'High Cholesterol & Arterial Plaque' }
    ];

    this.pharmacies = [
      { id: 'apollo', name: 'Apollo Pharmacy 24x7', address: 'Banjara Hills Rd No. 12, Hyderabad', lat: 17.4150, lng: 78.4380, phone: '+919876543201', rating: 4.8, open247: true },
      { id: 'medplus', name: 'MedPlus Pharmacy', address: 'Jubilee Hills Check Post, Hyderabad', lat: 17.4290, lng: 78.4110, phone: '+919876543202', rating: 4.6, open247: true },
      { id: 'sri-sai', name: 'Sri Sai Medicals', address: 'SR Nagar Main Road, Hyderabad', lat: 17.4420, lng: 78.4480, phone: '+919876543203', rating: 4.4, open247: false },
      { id: 'care-pharm', name: 'Care Pharmacy & Surgical', address: 'Somajiguda Circle, Hyderabad', lat: 17.4240, lng: 78.4590, phone: '+919876543204', rating: 4.7, open247: true }
    ];

    this.selectedDrug = this.catalog[0];
    this.debounceTimer = null;

    this.init();
  }

  init() {
    this.bindSearch();
    this.renderCatalog(this.catalog);
    this.renderPharmacies();
  }

  bindSearch() {
    const input = document.getElementById('searchMedicineInput');
    if (input) {
      input.addEventListener('input', () => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.handleSearch(input.value.trim().toLowerCase());
        }, 300);
      });
    }

    const sortSelect = document.getElementById('sortPharmaciesSelect');
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        this.renderPharmacies();
      });
    }
  }

  handleSearch(term) {
    if (!term) {
      this.renderCatalog(this.catalog);
      return;
    }

    // Match catalog + localStorage dashboard inventory
    let matches = this.catalog.filter(d =>
      d.name.toLowerCase().includes(term) ||
      d.generic.toLowerCase().includes(term) ||
      d.uses.toLowerCase().includes(term)
    );

    // Merge dashboard inventories if present
    try {
      const savedInventories = JSON.parse(localStorage.getItem('pharmacy_inventories') || '{}');
      Object.keys(savedInventories).forEach(pharmId => {
        const inv = savedInventories[pharmId];
        Object.keys(inv).forEach(medKey => {
          const item = inv[medKey];
          if (item && item.displayName && item.displayName.toLowerCase().includes(term)) {
            if (!matches.find(m => m.name.toLowerCase() === item.displayName.toLowerCase())) {
              matches.push({
                id: 'custom_' + medKey,
                name: item.displayName,
                generic: 'Custom Pharmacist Inventory Item',
                category: 'Local Pharmacy Stock',
                price: parseFloat(item.price) || 50,
                mfr: 'Local Verified Stock',
                uses: 'Added via Pharmacy Dashboard'
              });
            }
          }
        });
      });
    } catch (e) {}

    this.renderCatalog(matches);
  }

  renderCatalog(items) {
    const container = document.getElementById('medicineSearchResults');
    if (!container) return;

    if (items.length === 0) {
      container.innerHTML = `
        <div style="text-align:center;padding:30px 10px;color:var(--text-muted);">
          <i class="fa-solid fa-triangle-exclamation" style="font-size:32px;color:var(--warning);margin-bottom:10px;display:block;"></i>
          <strong>No matching drugs found</strong>
          <p style="font-size:0.8rem;margin-top:4px;">Try searching for "Dolo", "Pan", or "Cetirizine"</p>
        </div>
      `;
      return;
    }

    container.innerHTML = '';
    items.forEach(d => {
      const isSel = this.selectedDrug && this.selectedDrug.id === d.id;
      const card = document.createElement('div');
      card.className = `drug-card ${isSel ? 'selected' : ''}`;
      card.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <div style="font-weight:800;font-size:1.05rem;color:var(--text-main);">${d.name}</div>
            <div style="font-size:0.8rem;color:var(--secondary);font-weight:600;margin-top:2px;">${d.generic}</div>
          </div>
          <span style="font-size:1.1rem;font-weight:800;color:var(--primary);">₹${d.price}</span>
        </div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px;">
          <strong>Uses:</strong> ${d.uses} &bull; <strong>Mfg:</strong> ${d.mfr}
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
          <button class="btn-primary" style="padding:6px 12px;font-size:0.75rem;" onclick="event.stopPropagation(); window.MedicinesModule.selectDrug('${d.id}')">
            <i class="fa-solid fa-check"></i> Select
          </button>
          <button class="btn-outline" style="padding:6px 12px;font-size:0.75rem;" onclick="event.stopPropagation(); window.MedicinesModule.addToPrescriptionCart('${d.id}')">
            <i class="fa-solid fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      `;
      card.addEventListener('click', () => this.selectDrug(d.id));
      container.appendChild(card);
    });
  }

  selectDrug(drugId) {
    this.selectedDrug = this.catalog.find(d => d.id === drugId) || this.selectedDrug;
    this.renderCatalog(this.catalog);
    this.renderPharmacies();
    window.app.showToast('Drug Selected', `Showing stock availability for ${this.selectedDrug.name}`, 'info');
  }

  addToPrescriptionCart(drugId) {
    const item = this.catalog.find(d => d.id === drugId) || this.selectedDrug;
    if (window.PrescriptionModule && item) {
      window.PrescriptionModule.addToCart({
        name: item.name,
        dosage: item.generic,
        price: item.price,
        qty: 1
      });
      window.app.showToast('Item Added', `${item.name} added to your prescription order cart.`, 'success');
    }
  }

  // Calculate Haversine distance in KM
  calcDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return parseFloat((R * c).toFixed(1));
  }

  renderPharmacies() {
    const container = document.getElementById('pharmacyAvailabilityList');
    if (!container) return;

    const uCoords = window.app ? window.app.userCoords : { lat: 17.4208, lng: 78.4350 };
    const sortMode = document.getElementById('sortPharmaciesSelect')?.value || 'distance';

    let list = this.pharmacies.map(p => {
      const dist = this.calcDistance(uCoords.lat, uCoords.lng, p.lat, p.lng);
      // Check if stock exists in localStorage
      let inStock = true;
      let stockQty = 25 + Math.floor(Math.random() * 50);

      try {
        const savedInventories = JSON.parse(localStorage.getItem('pharmacy_inventories') || '{}');
        if (savedInventories[p.id] && this.selectedDrug) {
          const key = this.selectedDrug.name.toLowerCase().trim();
          if (savedInventories[p.id][key]) {
            stockQty = savedInventories[p.id][key].quantity;
            inStock = stockQty > 0;
          }
        }
      } catch (e) {}

      return {
        ...p,
        distanceKm: dist,
        inStock: inStock,
        stockQty: stockQty,
        itemPrice: this.selectedDrug ? this.selectedDrug.price : 40
      };
    });

    if (sortMode === 'distance') {
      list.sort((a, b) => a.distanceKm - b.distanceKm);
    } else if (sortMode === 'price') {
      list.sort((a, b) => a.itemPrice - b.itemPrice);
    } else if (sortMode === 'rating') {
      list.sort((a, b) => b.rating - a.rating);
    }

    container.innerHTML = `
      <div style="background:#f8fafc;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:16px;font-size:0.85rem;display:flex;justify-content:space-between;align-items:center;">
        <span>Availability for: <strong style="color:var(--primary);">${this.selectedDrug ? this.selectedDrug.name : 'All Medicines'}</strong></span>
        <a href="pharmacy_dashboard.html" style="color:var(--secondary);font-weight:700;text-decoration:none;">
          <i class="fa-solid fa-gears"></i> Open Pharmacy Owner Dashboard
        </a>
      </div>
    `;

    list.forEach(p => {
      const card = document.createElement('div');
      card.className = 'pharmacy-stock-card';
      const mapsLink = `https://www.google.com/maps/dir/?api=1&destination=${p.lat},${p.lng}`;

      card.innerHTML = `
        <div style="flex:1;min-width:240px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <h4 style="font-size:1.05rem;font-weight:800;color:var(--text-main);">${p.name}</h4>
            ${p.open247 ? '<span class="badge-tag badge-success">24x7 OPEN</span>' : '<span class="badge-tag badge-warning">Closes 10 PM</span>'}
          </div>
          <div style="font-size:0.85rem;color:var(--text-muted);margin-top:4px;">
            <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> ${p.address}
          </div>
          <div style="font-size:0.8rem;margin-top:6px;display:flex;gap:16px;color:var(--text-main);">
            <span><i class="fa-solid fa-route" style="color:var(--secondary);"></i> <strong>${p.distanceKm} km</strong> away</span>
            <span><i class="fa-solid fa-star" style="color:#eab308;"></i> <strong>${p.rating} / 5.0</strong></span>
            <span style="color:${p.inStock ? 'var(--success)' : 'var(--danger)'};font-weight:700;">
              ${p.inStock ? `&bull; ${p.stockQty} in stock` : '&bull; Out of stock'}
            </span>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
          <div style="font-size:1.15rem;font-weight:800;color:var(--text-main);">₹${p.itemPrice}</div>
          <div style="display:flex;gap:8px;">
            <a href="${mapsLink}" target="_blank" class="btn-outline" style="padding:6px 12px;font-size:0.8rem;text-decoration:none;">
              <i class="fa-solid fa-diamond-turn-right"></i> Navigate
            </a>
            <a href="tel:${p.phone}" class="btn-primary" style="padding:6px 12px;font-size:0.8rem;text-decoration:none;">
              <i class="fa-solid fa-phone"></i> Call
            </a>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }

  onTabActivated() {
    this.renderPharmacies();
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.MedicinesModule = new MedicinesModule();
});
