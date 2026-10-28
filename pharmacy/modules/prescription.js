// C:\xampp\htdocs\HEALTHCARE\pharmacy\modules\prescription.js
// Prescription OCR Extractor, Drag & Drop Scanner, and Itemized Order Cart

class PrescriptionModule {
  constructor() {
    this.cart = [
      { name: 'Dolo 650', dosage: 'Paracetamol 650mg (10 Tabs)', price: 32, qty: 1 },
      { name: 'Pan 40', dosage: 'Pantoprazole 40mg (15 Tabs)', price: 145, qty: 1 }
    ];

    this.isScanning = false;
    this.init();
  }

  init() {
    this.bindDropzone();
    this.bindCheckout();
    this.renderCart();
  }

  bindDropzone() {
    const dropzone = document.getElementById('prescriptionDropzone');
    const fileInput = document.getElementById('prescriptionFileInput');

    if (dropzone && fileInput) {
      dropzone.addEventListener('click', () => fileInput.click());

      dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
      });

      dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
      });

      dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
          this.processPrescriptionFile(e.dataTransfer.files[0]);
        }
      });

      fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
          this.processPrescriptionFile(e.target.files[0]);
        }
      });
    }
  }

  processPrescriptionFile(file) {
    if (!file) return;
    this.simulatePrescriptionOcr(file.name);
  }

  simulatePrescriptionOcr(fileName = 'sample') {
    if (this.isScanning) return;
    this.isScanning = true;

    const wrap = document.getElementById('ocrStatusWrap');
    const bar = document.getElementById('ocrProgressBar');
    const text = document.getElementById('ocrStatusText');

    if (wrap) wrap.style.display = 'block';
    if (bar) bar.style.width = '10%';
    if (text) text.textContent = 'Preprocessing prescription image resolution...';

    setTimeout(() => {
      if (bar) bar.style.width = '45%';
      if (text) text.textContent = 'Extracting handwritten entities & Rx headers...';
    }, 800);

    setTimeout(() => {
      if (bar) bar.style.width = '80%';
      if (text) text.textContent = 'Matching with Indian National Formulary & FDA index...';
    }, 1700);

    setTimeout(() => {
      if (bar) bar.style.width = '100%';
      if (text) text.textContent = '✅ Extraction complete! Items populated to cart.';

      // Add detected prescription drugs
      this.addToCart({ name: 'Azithral 500', dosage: 'Azithromycin 500mg (5 Tabs)', price: 120, qty: 1 });
      this.addToCart({ name: 'Augmentin 625', dosage: 'Amoxicillin + Clavulanate (10 Tabs)', price: 185, qty: 1 });

      window.app.showToast('Prescription Extracted', 'Added 2 verified Rx medicines to your cart.', 'success');
      this.isScanning = false;

      setTimeout(() => {
        if (wrap) wrap.style.display = 'none';
      }, 3500);
    }, 2600);
  }

  addToCart(item) {
    const existing = this.cart.find(c => c.name.toLowerCase() === item.name.toLowerCase());
    if (existing) {
      existing.qty += (item.qty || 1);
    } else {
      this.cart.push({
        name: item.name,
        dosage: item.dosage || 'Standard Unit',
        price: item.price || 50,
        qty: item.qty || 1
      });
    }
    this.renderCart();
  }

  removeFromCart(index) {
    this.cart.splice(index, 1);
    this.renderCart();
  }

  updateQty(index, delta) {
    if (this.cart[index]) {
      this.cart[index].qty = Math.max(1, this.cart[index].qty + delta);
      this.renderCart();
    }
  }

  renderCart() {
    const container = document.getElementById('prescriptionCartItems');
    const badge = document.getElementById('cartCountBadge');
    const subtotalEl = document.getElementById('cartSubtotal');
    const grandTotalEl = document.getElementById('cartGrandTotal');

    if (!container) return;

    if (this.cart.length === 0) {
      container.innerHTML = `
        <div style="text-align:center;padding:40px 10px;color:var(--text-muted);">
          <i class="fa-solid fa-cart-arrow-down" style="font-size:36px;color:#cbd5e1;margin-bottom:10px;display:block;"></i>
          <strong>Your prescription cart is empty</strong>
          <p style="font-size:0.8rem;margin-top:4px;">Upload a prescription slip or select drugs from the catalog.</p>
        </div>
      `;
      if (badge) badge.textContent = '0 items';
      if (subtotalEl) subtotalEl.textContent = '₹0.00';
      if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
      return;
    }

    let subtotal = 0;
    container.innerHTML = '';

    this.cart.forEach((item, idx) => {
      const lineTotal = item.price * item.qty;
      subtotal += lineTotal;

      const div = document.createElement('div');
      div.className = 'cart-item';
      div.innerHTML = `
        <div style="flex:1;">
          <div style="font-weight:800;color:var(--text-main);">${item.name}</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">${item.dosage}</div>
          <div style="font-weight:700;color:var(--primary);font-size:0.85rem;margin-top:2px;">₹${item.price} each</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <button class="cart-qty-btn" onclick="window.PrescriptionModule.updateQty(${idx}, -1)">-</button>
          <span style="font-weight:800;font-size:0.9rem;min-width:18px;text-align:center;">${item.qty}</span>
          <button class="cart-qty-btn" onclick="window.PrescriptionModule.updateQty(${idx}, 1)">+</button>
          <button style="background:none;border:none;color:#ef4444;cursor:pointer;margin-left:8px;font-size:0.9rem;" 
                  onclick="window.PrescriptionModule.removeFromCart(${idx})" title="Remove">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </div>
      `;
      container.appendChild(div);
    });

    const deliveryFee = 40;
    const gst = Math.round(subtotal * 0.05);
    const grandTotal = subtotal + deliveryFee + gst;

    if (badge) badge.textContent = `${this.cart.length} item${this.cart.length > 1 ? 's' : ''}`;
    if (subtotalEl) subtotalEl.textContent = `₹${subtotal.toFixed(2)}`;
    if (grandTotalEl) grandTotalEl.textContent = `₹${grandTotal.toFixed(2)}`;
  }

  bindCheckout() {
    const btn = document.getElementById('btnPrescriptionCheckout');
    if (btn) {
      btn.addEventListener('click', () => {
        if (this.cart.length === 0) {
          window.app.showToast('Cart is Empty', 'Please scan or add medicines before placing order.', 'warning');
          return;
        }

        const grandTotal = document.getElementById('cartGrandTotal')?.textContent || '₹0.00';
        window.app.showToast('Order Placed Successfully', `Prescription dispatched to Apollo 24x7! Total: ${grandTotal}. ETA: ~25 mins.`, 'success');

        // Clear cart after checkout
        this.cart = [];
        this.renderCart();
      });
    }
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.PrescriptionModule = new PrescriptionModule();
});
