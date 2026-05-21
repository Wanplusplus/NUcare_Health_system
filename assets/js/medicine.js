document.addEventListener('DOMContentLoaded', () => {
  const yearLabel = document.getElementById('yearLabel');
  const btnYearPrev = document.getElementById('btnYearPrev');
  const btnYearNext = document.getElementById('btnYearNext');
  const monthTabs = document.querySelectorAll('.month-tab');
  const summaryPeriodLabel = document.getElementById('summaryPeriodLabel');
  const medTableBody = document.getElementById('medTableBody');
  const medEmpty = document.getElementById('medEmpty');
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const addBtn = document.getElementById('btnAddMedicine');
  const exportBtn = document.getElementById('btnExport');
  const modal = document.getElementById('addMedicineModal');
  const form = document.getElementById('addMedicineForm');
  const saveBtn = document.getElementById('saveMedicineBtn');
  const toastWrap = document.getElementById('toastWrap');

<<<<<<< HEAD
  const modalTabs  = document.querySelectorAll('.modal-tab');
  const tabPanels  = document.querySelectorAll('.tab-panel');
  const btnTabNext = document.getElementById('btnTabNext');
  const btnTabPrev = document.getElementById('btnTabPrev');

  const qty          = document.getElementById('quantity');
  const unitCost     = document.getElementById('unit_cost');
  const purchaseQty  = document.getElementById('purchase_quantity');
  const totalCost    = document.getElementById('total_cost');
  const expDate      = document.getElementById('expiration_date');
  const endingBal    = document.getElementById('ending_balance');
  const statusPreview= document.getElementById('statusPreview');
=======
  const modalTabs = document.querySelectorAll('.modal-tab');
  const tabPanels = document.querySelectorAll('.tab-panel');
  const btnTabNext = document.getElementById('btnTabNext');
  const btnTabPrev = document.getElementById('btnTabPrev');

  const medicineIdInput = document.getElementById('medicine_id');
  const inventoryIdInput = document.getElementById('inventory_id');
  const formActionInput = document.getElementById('form_action');

  const qty = document.getElementById('quantity');
  const unitCost = document.getElementById('unit_cost');
  const purchaseQty = document.getElementById('purchase_quantity');
  const totalCost = document.getElementById('total_cost');
  const expDate = document.getElementById('expiration_date');
  const endingBal = document.getElementById('ending_balance');
  const statusPreview = document.getElementById('statusPreview');
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76

  const errs = {
    medicine_name: document.getElementById('err_medicine_name'),
    category: document.getElementById('err_category'),
    unit: document.getElementById('err_unit'),
    quantity: document.getElementById('err_quantity'),
    expiration_date: document.getElementById('err_expiration_date'),
  };

<<<<<<< HEAD
  /* ══════════════════════════════════════════════
     IMPORTANT: update this path to match where
     you placed medicine_ajax.php on your server.
     From assets/js/medicine.js → ajax/medicine/medicine_ajax.php
     ══════════════════════════════════════════════ */
  const AJAX_URL = '../../ajax/medicine_ajax.php';

  let currentYear  = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;
  let activeTab    = 'master';
  let medicines    = [];

  const monthNames = ['January','February','March','April','May','June',
    'July','August','September','October','November','December'];

  /* ── Helpers ── */
  const toast = (msg, type = 'success') => {
=======
  const apiUrl = '../../ajax/medicine_ajax.php';
  const currentUrl = new URL(window.location.href);

  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;
  let activeTab = 'master';
  let medicines = [];
  let filteredMedicines = [];
  let isLoading = false;

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];

  const toast = (msg, type = 'success') => {
    if (!toastWrap) return;

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    toastWrap.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  };

  let deleteConfirmResolve = null;

<<<<<<< HEAD
  const statusFrom = (q, exp) => {
    if (!exp) return 'Available';
    const days = Math.ceil((new Date(exp) - new Date()) / 86400000);
    if (q <= 0)    return 'Out Of Stock';
    if (days < 0)  return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (q <= 10)   return 'Low Stock';
=======
  const ensureDeleteConfirmModal = () => {
    let overlay = document.getElementById('deleteConfirmModal');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.className = 'delete-confirm-overlay';
    overlay.id = 'deleteConfirmModal';
    overlay.innerHTML = `
      <div class="delete-confirm-box" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
        <div class="delete-confirm-head">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <h3 id="deleteConfirmTitle">Delete Medicine</h3>
        </div>
        <p class="delete-confirm-message">Are you sure do you want to delete this medicine?</p>
        <div class="delete-confirm-actions">
          <button type="button" class="btn btn-outline" data-confirm-no>No</button>
          <button type="button" class="btn btn-danger" data-confirm-yes>Yes</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    const close = (result) => {
      overlay.classList.remove('show');
      if (deleteConfirmResolve) {
        const resolve = deleteConfirmResolve;
        deleteConfirmResolve = null;
        resolve(result);
      }
    };

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) close(false);
    });

    overlay.querySelector('[data-confirm-no]')?.addEventListener('click', () => close(false));
    overlay.querySelector('[data-confirm-yes]')?.addEventListener('click', () => close(true));

    return overlay;
  };

  const confirmDeleteMedicine = () => {
    const overlay = ensureDeleteConfirmModal();
    overlay.classList.add('show');

    return new Promise((resolve) => {
      deleteConfirmResolve = resolve;
    });
  };

  const clearErrors = () => Object.values(errs).forEach((e) => {
    if (e) e.textContent = '';
  });

  const clearFormState = () => {
    if (medicineIdInput) medicineIdInput.value = '';
    if (inventoryIdInput) inventoryIdInput.value = '';
    if (formActionInput) formActionInput.value = 'store';
  };

  const statusFrom = (quantity, exp) => {
    if (!exp) return quantity <= 0 ? 'Out Of Stock' : 'Available';

    const expDateObj = new Date(exp);
    if (Number.isNaN(expDateObj.getTime())) {
      return quantity <= 0 ? 'Out Of Stock' : 'Available';
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    expDateObj.setHours(0, 0, 0, 0);

    const days = Math.ceil((expDateObj - today) / 86400000);
    if (quantity <= 0) return 'Out Of Stock';
    if (days < 0) return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (quantity <= 20) return 'Low Stock';
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    return 'Available';
  };

  const pillFor = (status) => {
    const map = {
<<<<<<< HEAD
      'Available':    ['ok',  'fa-circle-check'],
      'Low Stock':    ['low', 'fa-triangle-exclamation'],
      'Near Expiry':  ['low', 'fa-clock'],
      'Expired':      ['bad', 'fa-ban'],
      'Out of Stock': ['bad', 'fa-circle-xmark'],
=======
      Available: ['ok', 'fa-circle-check'],
      'Low Stock': ['low', 'fa-triangle-exclamation'],
      'Near Expiry': ['low', 'fa-clock'],
      Expired: ['bad', 'fa-ban'],
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
      'Out Of Stock': ['bad', 'fa-circle-xmark'],
    };
    const [cls, icon] = map[status] || ['ok', 'fa-circle-check'];
    return `<span class="s-pill ${cls}"><i class="fa-solid ${icon}"></i> ${status}</span>`;
  };

<<<<<<< HEAD
  const updateComputed = () => {
    const p = parseFloat(unitCost?.value || 0) * parseFloat(purchaseQty?.value || 0);
    if (totalCost) totalCost.value = p > 0 ? p.toFixed(2) : '';
    if (endingBal) endingBal.value = qty?.value !== '' ? qty.value : '';
=======
  const setLoading = (on) => {
    isLoading = on;
    if (saveBtn) {
      saveBtn.disabled = on;
      saveBtn.innerHTML = on
        ? '<span class="spinner"></span> Saving…'
        : '<i class="fa-solid fa-floppy-disk"></i> Save Medicine';
    }
  };

  const updateComputed = () => {
    const purchase = parseFloat(purchaseQty?.value || 0);
    const cost = parseFloat(unitCost?.value || 0);
    const quantity = parseFloat(qty?.value || 0);
    const exp = expDate?.value || '';

    if (totalCost) {
      const total = purchase * cost;
      totalCost.value = total > 0 ? total.toFixed(2) : '';
    }

    if (endingBal) {
      endingBal.value = qty?.value !== '' ? String(Math.max(0, Math.floor(quantity))) : '';
    }

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    if (statusPreview) {
      if (!exp && quantity === 0) {
        statusPreview.innerHTML = '<span class="s-pill ok"><i class="fa-solid fa-circle-info"></i> Fill in quantity & expiry to preview</span>';
      } else {
        statusPreview.innerHTML = pillFor(statusFrom(quantity, exp));
      }
    }
  };

  const updatePeriodUI = () => {
    if (yearLabel) yearLabel.textContent = currentYear;
    monthTabs.forEach((t) => t.classList.toggle('active', parseInt(t.dataset.month, 10) === currentMonth));
    if (summaryPeriodLabel) summaryPeriodLabel.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
  };

<<<<<<< HEAD
  const renderTable = data => {
=======
  const normalizeStatusFilter = (value) => {
    if (value === 'available') return 'Available';
    if (value === 'low') return 'Low Stock';
    if (value === 'near') return 'Near Expiry';
    if (value === 'expired') return 'Expired';
    return '';
  };

  const getDisplayStatus = (m) => m.status_display || statusFrom(m.quantity, m.expiry_date);

  const renderTable = (data) => {
    if (!medTableBody || !medEmpty) return;

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    medTableBody.innerHTML = '';
    medEmpty.style.display = data.length ? 'none' : 'block';

    if (!data.length) {
      updateSummary([]);
      return;
    }

    const frag = document.createDocumentFragment();

    data.forEach((m) => {
      const displayStatus = getDisplayStatus(m);
      const tr = document.createElement('tr');
<<<<<<< HEAD
      const rowClass = { Expired:'r-expired','Near Expiry':'r-near','Low Stock':'r-low' }[m.status] || '';
      if (rowClass) tr.className = rowClass;
      tr.dataset.id = m.id;
      tr.dataset.inventoryId = m.inventory_id ?? '';

      let expDisplay = m.expiration_date || '—';
      if (expDisplay && expDisplay !== '—') {
        const d = new Date(expDisplay);
        if (!isNaN(d)) expDisplay = d.toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
      }

      tr.innerHTML = `
        <td>
          <span class="med-name">${m.medicine_name}</span>
          <span class="med-generic">${m.generic_name || ''}</span>
        </td>
        <td>${m.quantity}</td>
        <td>${m.unit}</td>
        <td>—</td>
        <td>${m.purchase_quantity ?? '—'}</td>
        <td>${m.ending_balance ?? '—'}</td>
        <td>${expDisplay}</td>
        <td>${pillFor(m.status)}</td>
=======
      const rowClass = { Expired: 'r-expired', 'Near Expiry': 'r-near', 'Low Stock': 'r-low', 'Out Of Stock': 'r-low' }[displayStatus] || '';
      if (rowClass) tr.className = rowClass;
      tr.dataset.id = m.medicine_id;
      tr.dataset.inventoryId = m.inventory_id;
      tr.dataset.status = displayStatus;

      tr.innerHTML = `
        <td>
          <span class="med-name">${m.medicine_name || ''}</span>
          <span class="med-generic">${m.generic_name || ''}</span>
        </td>
        <td>${m.quantity ?? 0}</td>
        <td>${m.unit || ''}</td>
        <td>${m.total_cost ?? '—'}</td>
        <td>${m.quantity ?? 0}</td>
        <td>${m.quantity ?? 0}</td>
        <td>${m.expiry_date || '—'}</td>
        <td>${pillFor(displayStatus)}</td>
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
        <td>
          <div class="acts">
            <button class="act-btn edit" data-action="edit" title="Edit" type="button"><i class="fa-solid fa-pen"></i></button>
            <button class="act-btn del" data-action="delete" title="Delete" type="button"><i class="fa-solid fa-trash"></i></button>
          </div>
<<<<<<< HEAD
        </td>`;
=======
        </td>
      `;

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
      frag.appendChild(tr);
    });

    medTableBody.appendChild(frag);
    updateSummary(data);
  };

<<<<<<< HEAD
  const updateSummary = data => {
    const c = { total:data.length, available:0, low:0, expired:0, near:0, purchased:0, ending:0 };
    data.forEach(m => {
      const st = (m.status || '').toLowerCase();
      if (st === 'available')   c.available++;
      if (st === 'low stock')   c.low++;
      if (st === 'expired')     c.expired++;
      if (st === 'near expiry') c.near++;
      c.purchased += Number(m.purchase_quantity || 0);
      c.ending    += Number(m.ending_balance    || 0);
    });
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('sumTotal',         c.total);
    set('sumAvailable',     c.available);
    set('sumLow',           c.low);
    set('sumExpired',       c.expired);
    set('sumNear',          c.near);
    set('sumPurchased',     c.purchased);
    set('sumEndingBalance', c.ending);

    const aLow   = document.getElementById('alertLowStock');
    const aExp   = document.getElementById('alertExpired');
    const aNear  = document.getElementById('alertNearExpiry');
    const alerts = document.getElementById('medAlerts');
    if (aLow)  aLow.style.display  = c.low     ? 'inline-flex' : 'none';
    if (aExp)  aExp.style.display  = c.expired ? 'inline-flex' : 'none';
    if (aNear) aNear.style.display = c.near    ? 'inline-flex' : 'none';
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('alertLowStockText',   `${c.low} low stock`);
    setTxt('alertExpiredText',    `${c.expired} expired`);
=======
  const updateSummary = (data) => {
    const c = { total: data.length, available: 0, low: 0, expired: 0, near: 0, purchased: 0, ending: 0 };

    data.forEach((m) => {
      const displayStatus = getDisplayStatus(m);
      if (displayStatus === 'Available') c.available++;
      if (displayStatus === 'Low Stock') c.low++;
      if (displayStatus === 'Near Expiry') c.near++;
      if (displayStatus === 'Expired' || displayStatus === 'Out Of Stock') c.expired++;

      c.purchased += Number(m.purchase_quantity || m.quantity || 0);
      c.ending += Number(m.ending_balance || m.quantity || 0);
    });

    const set = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.textContent = v;
    };

    set('sumTotal', c.total);
    set('sumAvailable', c.available);
    set('sumLow', c.low);
    set('sumExpired', c.expired);
    set('sumNear', c.near);
    set('sumPurchased', c.purchased);
    set('sumEndingBalance', c.ending);

    const aLow = document.getElementById('alertLowStock');
    const aExp = document.getElementById('alertExpired');
    const aNear = document.getElementById('alertNearExpiry');
    const alerts = document.getElementById('medAlerts');

    if (aLow) aLow.style.display = c.low ? 'inline-flex' : 'none';
    if (aExp) aExp.style.display = c.expired ? 'inline-flex' : 'none';
    if (aNear) aNear.style.display = c.near ? 'inline-flex' : 'none';

    const setTxt = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.textContent = v;
    };

    setTxt('alertLowStockText', `${c.low} low stock`);
    setTxt('alertExpiredText', `${c.expired} expired`);
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    setTxt('alertNearExpiryText', `${c.near} near expiry`);
    if (alerts) alerts.style.display = (c.low || c.expired || c.near) ? 'flex' : 'none';
  };

  const applyFilters = () => {
<<<<<<< HEAD
    const s  = (searchInput?.value  || '').toLowerCase().trim();
    const st = (statusFilter?.value || '').toLowerCase().trim();
    renderTable(medicines.filter(m =>
      ((m.medicine_name || '').toLowerCase().includes(s) ||
       (m.generic_name  || '').toLowerCase().includes(s)) &&
      (!st || m.status.toLowerCase().replace(/\s+/g,'') === st.replace(/\s+/g,'') || m.status.toLowerCase() === st)
    ));
  };

  /* ══════════════════════════════════════
     LOAD FROM DATABASE
     ══════════════════════════════════════ */
  const loadMedicines = async () => {
    try {
      const fd = new FormData();
      fd.append('action', 'fetch');
      const res = await fetch(AJAX_URL, { method: 'POST', body: fd });
=======
    const search = (searchInput?.value || '').toLowerCase().trim();
    const st = normalizeStatusFilter(statusFilter?.value || '');

    filteredMedicines = medicines.filter((m) => {
      const displayStatus = getDisplayStatus(m);
      const textMatch = (m.medicine_name || '').toLowerCase().includes(search)
        || (m.generic_name || '').toLowerCase().includes(search)
        || (m.medicine_type || '').toLowerCase().includes(search)
        || (m.batch_number || '').toLowerCase().includes(search);

      const statusMatch = !st || displayStatus === st;
      return textMatch && statusMatch;
    });

    renderTable(filteredMedicines);
  };
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76

      /* Check if the response is actually JSON before parsing */
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        const rawText = await res.text();
        console.error('Non-JSON response from server (fetch):', rawText);
        toast('Server returned non-JSON. Check PHP path & errors in console.', 'error');
        renderTable([]);
        return;
      }

      const json = await res.json();
      if (json.success) {
        medicines = json.data || [];
        applyFilters();
      } else {
        console.error('Fetch medicines error:', json.message);
        toast('Load error: ' + json.message, 'error');
        renderTable([]);
      }
    } catch (err) {
      console.error('loadMedicines network error:', err);
      toast('Could not reach server. Check AJAX_URL in medicine.js.', 'error');
      renderTable([]);
    }
  };

  /* ── Modal tabs ── */
  const TABS = ['master', 'inventory'];
  const switchTab = tabId => {
    activeTab = tabId;
<<<<<<< HEAD
    modalTabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tabId));
    tabPanels.forEach(p => p.classList.toggle('active', p.id === `tab-${tabId}`));
    const idx = TABS.indexOf(tabId);
    btnTabPrev.style.display = idx > 0              ? 'inline-flex' : 'none';
    btnTabNext.style.display = idx < TABS.length-1  ? 'inline-flex' : 'none';
    saveBtn.style.display    = idx === TABS.length-1 ? 'inline-flex' : 'none';
  };

  const validateMaster = () => {
    clearErrors(); let ok = true;
    const n = document.getElementById('medicine_name')?.value.trim();
    const c = document.getElementById('category')?.value;
    const u = document.getElementById('unit')?.value;
    if (!n) { if(errs.medicine_name) errs.medicine_name.textContent = 'Medicine name is required'; ok=false; }
    if (!c) { if(errs.category)      errs.category.textContent      = 'Category is required';      ok=false; }
    if (!u) { if(errs.unit)          errs.unit.textContent          = 'Unit is required';           ok=false; }
=======
    modalTabs.forEach((t) => t.classList.toggle('active', t.dataset.tab === tabId));
    tabPanels.forEach((p) => p.classList.toggle('active', p.id === `tab-${tabId}`));

    const idx = ['master', 'inventory'].indexOf(tabId);
    if (btnTabPrev) btnTabPrev.style.display = idx > 0 ? 'inline-flex' : 'none';
    if (btnTabNext) btnTabNext.style.display = idx < 1 ? 'inline-flex' : 'none';
    if (saveBtn) saveBtn.style.display = idx === 1 ? 'inline-flex' : 'none';
  };

  const validateMaster = () => {
    clearErrors();
    let ok = true;

    const n = document.getElementById('medicine_name')?.value.trim();
    const c = document.getElementById('category')?.value;
    const u = document.getElementById('unit')?.value;

    if (!n) {
      if (errs.medicine_name) errs.medicine_name.textContent = 'Medicine name is required';
      ok = false;
    }
    if (!c) {
      if (errs.category) errs.category.textContent = 'Category is required';
      ok = false;
    }
    if (!u) {
      if (errs.unit) errs.unit.textContent = 'Unit is required';
      ok = false;
    }

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    return ok;
  };

  const validateInventory = () => {
<<<<<<< HEAD
    clearErrors(); let ok = true;
    const q = parseFloat(qty?.value);
    const e = expDate?.value;
    if (!(q >= 0)) { if(errs.quantity)        errs.quantity.textContent        = 'Enter a valid quantity'; ok=false; }
    if (!e)        { if(errs.expiration_date)  errs.expiration_date.textContent = 'Expiration date required'; ok=false; }
    return ok;
  };

  const openModal = () => { modal.classList.add('show'); switchTab('master'); };
  const closeModal = () => {
    modal.classList.remove('show');
    form.reset(); clearErrors(); updateComputed(); switchTab('master');
=======
    clearErrors();
    let ok = true;

    const q = parseFloat(qty?.value);
    const e = expDate?.value;

    if (!(q >= 0)) {
      if (errs.quantity) errs.quantity.textContent = 'Enter a valid quantity';
      ok = false;
    }

    if (!e) {
      if (errs.expiration_date) errs.expiration_date.textContent = 'Expiration date required';
      ok = false;
    }

    return ok;
  };

  const openModal = () => {
    if (!modal || !form) return;
    modal.classList.add('show');
    clearErrors();
    clearFormState();
    form.reset();
    updateComputed();
    switchTab('master');
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
  };

  const closeModal = () => {
    if (!modal || !form) return;
    modal.classList.remove('show');
    form.reset();
    clearErrors();
    clearFormState();
    updateComputed();
    switchTab('master');
  };

<<<<<<< HEAD
  /* ── Events ── */
  addBtn?.addEventListener('click', openModal);
  document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
  modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  modalTabs.forEach(tab => tab.addEventListener('click', () => {
    if (activeTab === 'master' && tab.dataset.tab === 'inventory' && !validateMaster()) return;
    switchTab(tab.dataset.tab);
  }));
=======
  const populateForm = (row) => {
    if (!row) return;

    if (medicineIdInput) medicineIdInput.value = row.medicine_id || '';
    if (inventoryIdInput) inventoryIdInput.value = row.inventory_id || '';
    if (formActionInput) formActionInput.value = 'update';

    const fields = {
      medicine_name: row.medicine_name || '',
      generic_name: row.generic_name || '',
      category: row.medicine_type || '',
      dosage: row.dosage || '',
      unit: row.unit || '',
      description: row.description || '',
      batch_code: row.batch_number || '',
      quantity: row.quantity ?? '',
      expiration_date: row.expiry_date || '',
      date_received: row.date_received || '',
      reorder_level: row.reorder_level ?? 10,
    };

    Object.entries(fields).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    });

    if (saveBtn) {
      saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update Medicine';
    }

    updateComputed();
    switchTab('master');
    if (modal) modal.classList.add('show');
  };

  const resetToCreateMode = () => {
    clearFormState();
    if (saveBtn) {
      saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Medicine';
    }
  };

  const fetchMedicines = async () => {
    try {
      const fd = new FormData();
      fd.append('action', 'list');

      const res = await fetch(apiUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      });

      const json = await res.json();
      if (!json || !json.success) {
        throw new Error(json?.message || 'Unable to load medicines');
      }

      medicines = Array.isArray(json.data) ? json.data : [];
      applyFilters();
    } catch (err) {
      console.error(err);
      medicines = [];
      applyFilters();
      toast('Could not load medicines from the server', 'error');
    }
  };

  const mapServerRow = (json) => ({
    medicine_id: json.medicineId || json.medicine_id,
    inventory_id: json.inventoryId || json.inventory_id,
    medicine_name: json.medicine_name || '',
    generic_name: json.generic_name || '',
    medicine_type: json.medicine_type || '',
    dosage: json.dosage || '',
    unit: json.unit || '',
    description: json.description || '',
    batch_number: json.batch_number || '',
    quantity: Number(json.quantity || 0),
    expiry_date: json.expiry_date || '',
    date_received: json.date_received || '',
    reorder_level: Number(json.reorder_level || 10),
    status: json.status || statusFrom(Number(json.quantity || 0), json.expiry_date || ''),
  });

  const openExport = () => {
    const params = new URLSearchParams();

    const status = normalizeStatusFilter(statusFilter?.value || '');
    if (status) params.set('status', status);

    params.set('year', String(currentYear));
    params.set('month', String(currentMonth));

    const query = params.toString();
    window.location.href = query
      ? `../../print-output/medicine_output.php?${query}`
      : '../../print-output/medicine_output.php';
  };

  addBtn?.addEventListener('click', openModal);
  exportBtn?.addEventListener('click', openExport);

  document.querySelectorAll('[data-close-modal]').forEach((b) => b.addEventListener('click', closeModal));
  modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  modalTabs.forEach((tab) => tab.addEventListener('click', () => {
    if (activeTab === 'master' && tab.dataset.tab === 'inventory' && !validateMaster()) return;
    switchTab(tab.dataset.tab);
  }));

>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
  btnTabNext?.addEventListener('click', () => {
    if (!validateMaster()) return;
    switchTab('inventory');
  });
<<<<<<< HEAD
  btnTabPrev?.addEventListener('click', () => {
    const idx = TABS.indexOf(activeTab);
    if (idx > 0) switchTab(TABS[idx - 1]);
  });

  btnYearPrev?.addEventListener('click', () => { currentYear--;  updatePeriodUI(); });
  btnYearNext?.addEventListener('click', () => { currentYear++;  updatePeriodUI(); });
  monthTabs.forEach(tab => tab.addEventListener('click', () => {
    currentMonth = parseInt(tab.dataset.month); updatePeriodUI();
  }));

  searchInput?.addEventListener('input',   applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  qty?.addEventListener('input',         updateComputed);
  unitCost?.addEventListener('input',    updateComputed);
=======

  btnTabPrev?.addEventListener('click', () => switchTab('master'));

  btnYearPrev?.addEventListener('click', () => {
    currentYear -= 1;
    updatePeriodUI();
  });

  btnYearNext?.addEventListener('click', () => {
    currentYear += 1;
    updatePeriodUI();
  });

  monthTabs.forEach((tab) => tab.addEventListener('click', () => {
    currentMonth = parseInt(tab.dataset.month, 10);
    updatePeriodUI();
  }));

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);

  qty?.addEventListener('input', updateComputed);
  unitCost?.addEventListener('input', updateComputed);
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
  purchaseQty?.addEventListener('input', updateComputed);
  expDate?.addEventListener('change', updateComputed);

<<<<<<< HEAD
  medTableBody?.addEventListener('click', e => {
=======
  medTableBody?.addEventListener('click', async (e) => {
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const tr = btn.closest('tr');
    const action = btn.dataset.action;

    if (!tr) return;

    const medicineId = parseInt(tr.dataset.id || '0', 10);
    const inventoryId = parseInt(tr.dataset.inventoryId || '0', 10);
    const row = medicines.find((m) => Number(m.medicine_id) === medicineId && Number(m.inventory_id) === inventoryId);

    if (action === 'delete') {
      if (!row) {
        toast('Unable to load medicine details for deletion', 'error');
        return;
      }
<<<<<<< HEAD
    } else if (action === 'deduct') {
      toast('Deduct stock — coming soon', 'warn');
    } else if (action === 'edit') {
      toast('Edit — coming soon', 'warn');
    }
  });

  /* ══════════════════════════════════════
     FORM SUBMIT
     ══════════════════════════════════════ */
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateInventory()) return;
    setLoading(true);

    const fd = new FormData(form);
    fd.append('action', 'add');
    fd.append('status', statusFrom(parseFloat(qty?.value || 0), expDate?.value));

    try {
      const res = await fetch(AJAX_URL, { method: 'POST', body: fd });

      /* Detect non-JSON response (PHP crash / wrong path) */
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        const rawText = await res.text();
        console.error('Non-JSON response from server (add):', rawText);
        toast('Server error — PHP is not returning JSON. Check the browser console for details.', 'error');
        return;
      }

      const json = await res.json();

      if (json.success) {
        toast(json.message || 'Medicine saved!', 'success');
        closeModal();
        /* Reload from DB so table reflects the real saved data */
        await loadMedicines();
      } else {
        /* Show the exact PHP error message in the toast */
        toast(json.message || 'Failed to save medicine', 'error');
        console.error('Save medicine server error:', json.message);
      }
    } catch (err) {
      console.error('Save fetch error:', err);
      toast('Network error — could not reach server', 'error');
=======

      const confirmed = await confirmDeleteMedicine();
      if (!confirmed) return;

      try {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('medicine_id', String(row.medicine_id || ''));
        fd.append('inventory_id', String(row.inventory_id || ''));

        const res = await fetch(apiUrl, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
        });

        const json = await res.json();

        if (!json || typeof json.success === 'undefined') {
          throw new Error('Invalid server response');
        }

        if (!json.success) {
          throw new Error(json.message || 'Failed to delete medicine');
        }

        toast(json.message || 'Medicine deleted successfully', 'success');
        await fetchMedicines();
      } catch (err) {
        console.error(err);
        toast(err.message || 'Failed to delete medicine', 'error');
      }

      return;
    }

    if (action === 'edit') {
      if (!row) {
        toast('Unable to load medicine details for editing', 'error');
        return;
      }

      populateForm(row);
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateMaster() || !validateInventory()) return;
    if (isLoading) return;

    setLoading(true);

    const fd = new FormData(form);
    fd.set('action', formActionInput?.value || 'store');

    const computedStatus = statusFrom(parseFloat(qty?.value || 0), expDate?.value || '');
    fd.set('status', computedStatus);

    try {
      const res = await fetch(apiUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      });

      const json = await res.json();

      if (!json || typeof json.success === 'undefined') {
        throw new Error('Invalid server response');
      }

      if (!json.success) {
        throw new Error(json.message || 'Failed to save medicine');
      }

      toast(json.message || 'Medicine saved successfully', 'success');

      await fetchMedicines();
      closeModal();
      resetToCreateMode();
    } catch (err) {
      console.error(err);
      toast(err.message || 'Failed to save medicine', 'error');
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
    } finally {
      setLoading(false);
    }
  });

<<<<<<< HEAD
  document.getElementById('btnExport')?.addEventListener('click', () => {
    toast('Export — coming soon', 'warn');
  });

  /* ── Init ── */
  updatePeriodUI();
  updateComputed();
  loadMedicines();
});
=======
  updatePeriodUI();
  switchTab('master');
  updateComputed();
  fetchMedicines();
});
>>>>>>> 3e2c35e9e5888132b21522ea1c759343bb7deb76
