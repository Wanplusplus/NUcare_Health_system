document.addEventListener('DOMContentLoaded', () => {

  /* ── DOM refs ── */
  const yearLabel          = document.getElementById('yearLabel');
  const btnYearPrev        = document.getElementById('btnYearPrev');
  const btnYearNext        = document.getElementById('btnYearNext');
  const monthTabs          = document.querySelectorAll('.month-tab');
  const summaryPeriodLabel = document.getElementById('summaryPeriodLabel');
  const medTableBody       = document.getElementById('medTableBody');
  const medEmpty           = document.getElementById('medEmpty');
  const searchInput        = document.getElementById('searchInput');
  const statusFilter       = document.getElementById('statusFilter');
  const addBtn             = document.getElementById('btnAddMedicine');
  const modal              = document.getElementById('addMedicineModal');
  const form               = document.getElementById('addMedicineForm');
  const saveBtn            = document.getElementById('saveMedicineBtn');
  const toastWrap          = document.getElementById('toastWrap');

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

  const errs = {
    medicine_name:   document.getElementById('err_medicine_name'),
    category:        document.getElementById('err_category'),
    unit:            document.getElementById('err_unit'),
    quantity:        document.getElementById('err_quantity'),
    expiration_date: document.getElementById('err_expiration_date'),
  };

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
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    toastWrap.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  };

  const clearErrors = () => Object.values(errs).forEach(e => e && (e.textContent = ''));

  const statusFrom = (q, exp) => {
    if (!exp) return 'Available';
    const days = Math.ceil((new Date(exp) - new Date()) / 86400000);
    if (q <= 0)    return 'Out Of Stock';
    if (days < 0)  return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (q <= 10)   return 'Low Stock';
    return 'Available';
  };

  const pillFor = status => {
    const map = {
      'Available':    ['ok',  'fa-circle-check'],
      'Low Stock':    ['low', 'fa-triangle-exclamation'],
      'Near Expiry':  ['low', 'fa-clock'],
      'Expired':      ['bad', 'fa-ban'],
      'Out of Stock': ['bad', 'fa-circle-xmark'],
      'Out Of Stock': ['bad', 'fa-circle-xmark'],
    };
    const [cls, icon] = map[status] || ['ok', 'fa-circle-check'];
    return `<span class="s-pill ${cls}"><i class="fa-solid ${icon}"></i> ${status}</span>`;
  };

  const updateComputed = () => {
    const p = parseFloat(unitCost?.value || 0) * parseFloat(purchaseQty?.value || 0);
    if (totalCost) totalCost.value = p > 0 ? p.toFixed(2) : '';
    if (endingBal) endingBal.value = qty?.value !== '' ? qty.value : '';
    if (statusPreview) {
      const q = parseFloat(qty?.value || 0);
      const e = expDate?.value || '';
      if (!e && q === 0) {
        statusPreview.innerHTML = `<span class="s-pill ok"><i class="fa-solid fa-circle-info"></i> Fill in quantity &amp; expiry to preview</span>`;
      } else {
        statusPreview.innerHTML = pillFor(statusFrom(q, e));
      }
    }
  };

  const updatePeriodUI = () => {
    yearLabel.textContent = currentYear;
    monthTabs.forEach(t => t.classList.toggle('active', parseInt(t.dataset.month) === currentMonth));
    if (summaryPeriodLabel) summaryPeriodLabel.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
  };

  const renderTable = data => {
    medTableBody.innerHTML = '';
    medEmpty.style.display = data.length ? 'none' : 'block';
    if (!data.length) { updateSummary([]); return; }

    const frag = document.createDocumentFragment();
    data.forEach(m => {
      const tr = document.createElement('tr');
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
        <td>
          <div class="acts">
            <button class="act-btn edit"   data-action="edit"   title="Edit"   type="button"><i class="fa-solid fa-pen"></i></button>
            <button class="act-btn deduct" data-action="deduct" title="Deduct" type="button"><i class="fa-solid fa-minus"></i></button>
            <button class="act-btn del"    data-action="delete" title="Delete" type="button"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>`;
      frag.appendChild(tr);
    });
    medTableBody.appendChild(frag);
    updateSummary(data);
  };

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
    setTxt('alertNearExpiryText', `${c.near} near expiry`);
    if (alerts) alerts.style.display = (c.low || c.expired || c.near) ? 'flex' : 'none';
  };

  const applyFilters = () => {
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
    return ok;
  };

  const validateInventory = () => {
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
  };

  const setLoading = on => {
    saveBtn.disabled = on;
    saveBtn.innerHTML = on
      ? '<span class="spinner"></span> Saving…'
      : '<i class="fa-solid fa-floppy-disk"></i> Save Medicine';
  };

  /* ── Events ── */
  addBtn?.addEventListener('click', openModal);
  document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
  modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  modalTabs.forEach(tab => tab.addEventListener('click', () => {
    if (activeTab === 'master' && tab.dataset.tab === 'inventory' && !validateMaster()) return;
    switchTab(tab.dataset.tab);
  }));
  btnTabNext?.addEventListener('click', () => {
    const idx = TABS.indexOf(activeTab);
    if (idx === 0 && !validateMaster()) return;
    if (idx < TABS.length - 1) switchTab(TABS[idx + 1]);
  });
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
  purchaseQty?.addEventListener('input', updateComputed);
  expDate?.addEventListener('change',    updateComputed);

  medTableBody?.addEventListener('click', e => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'delete') {
      const id = parseInt(btn.closest('tr')?.dataset.id);
      if (id && confirm('Delete this medicine?')) {
        medicines = medicines.filter(m => m.id !== id);
        applyFilters();
        toast('Medicine deleted', 'error');
      }
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
    } finally {
      setLoading(false);
    }
  });

  document.getElementById('btnExport')?.addEventListener('click', () => {
    toast('Export — coming soon', 'warn');
  });

  /* ── Init ── */
  updatePeriodUI();
  updateComputed();
  loadMedicines();
});