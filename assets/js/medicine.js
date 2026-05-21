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

  /* modal tab controls */
  const modalTabs   = document.querySelectorAll('.modal-tab');
  const tabPanels   = document.querySelectorAll('.tab-panel');
  const btnTabNext  = document.getElementById('btnTabNext');
  const btnTabPrev  = document.getElementById('btnTabPrev');

  /* form fields */
  const qty          = document.getElementById('quantity');
  const unitCost     = document.getElementById('unit_cost');
  const purchaseQty  = document.getElementById('purchase_quantity');
  const totalCost    = document.getElementById('total_cost');
  const expDate      = document.getElementById('expiration_date');
  const endingBal    = document.getElementById('ending_balance');
  const statusPreview= document.getElementById('statusPreview');

  /* error spans */
  const errs = {
    medicine_name:   document.getElementById('err_medicine_name'),
    category:        document.getElementById('err_category'),
    unit:            document.getElementById('err_unit'),
    quantity:        document.getElementById('err_quantity'),
    expiration_date: document.getElementById('err_expiration_date'),
  };

  /* ── State ── */
  let currentYear  = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;
  let activeTab    = 'master';  // 'master' | 'inventory'

  const monthNames = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
  ];

  /* Dummy data */
  let medicines = [
    {id:1, medicine_name:'Paracetamol', generic_name:'Acetaminophen', quantity:120, unit:'Tablet', purchase_quantity:200, ending_balance:120, expiration_date:'2026-12-20', status:'Available'},
    {id:2, medicine_name:'Amoxicillin', generic_name:'Antibiotic', quantity:15, unit:'Capsule', purchase_quantity:40, ending_balance:15, expiration_date:'2026-07-01', status:'Low Stock'},
    {id:3, medicine_name:'Vitamin C', generic_name:'Ascorbic Acid', quantity:0, unit:'Bottle', purchase_quantity:50, ending_balance:0, expiration_date:'2025-01-01', status:'Expired'},
    {id:4, medicine_name:'Cetirizine', generic_name:'Antihistamine', quantity:22, unit:'Tablet', purchase_quantity:35, ending_balance:22, expiration_date:'2026-06-15', status:'Near Expiry'},
  ];

  /* ── Helpers ── */
  const toast = (msg, type='success') => {
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    toastWrap.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  };

  const clearErrors = () => Object.values(errs).forEach(e => e && (e.textContent = ''));

  const statusFrom = (q, exp) => {
    if (!exp) return 'Available';
    const days = Math.ceil((new Date(exp) - new Date()) / 86400000);
    if (q <= 0)   return 'Out of Stock';
    if (days < 0) return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (q <= 20)  return 'Low Stock';
    return 'Available';
  };

  const pillFor = status => {
    const map = {
      'Available':    ['ok',  'fa-circle-check'],
      'Low Stock':    ['low', 'fa-triangle-exclamation'],
      'Near Expiry':  ['low', 'fa-clock'],
      'Expired':      ['bad', 'fa-ban'],
      'Out of Stock': ['bad', 'fa-circle-xmark'],
    };
    const [cls, icon] = map[status] || ['ok','fa-circle-check'];
    return `<span class="s-pill ${cls}"><i class="fa-solid ${icon}"></i> ${status}</span>`;
  };

  /* ── Computed field updates ── */
  const updateComputed = () => {
    const p = parseFloat(unitCost?.value || 0) * parseFloat(purchaseQty?.value || 0);
    if (totalCost) totalCost.value = p > 0 ? p.toFixed(2) : '';
    if (endingBal) endingBal.value = qty?.value !== '' ? qty.value : '';

    /* update status preview */
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

  /* ── Period UI ── */
  const updatePeriodUI = () => {
    yearLabel.textContent = currentYear;
    monthTabs.forEach(t => t.classList.toggle('active', parseInt(t.dataset.month) === currentMonth));
    if (summaryPeriodLabel) summaryPeriodLabel.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
  };

  /* ── Table render ── */
  const renderTable = data => {
    medTableBody.innerHTML = '';
    medEmpty.style.display = data.length ? 'none' : 'block';
    if (!data.length) { updateSummary([]); return; }

    const frag = document.createDocumentFragment();
    data.forEach(m => {
      const tr = document.createElement('tr');
      const stClass = { Available:'ok', 'Low Stock':'low', 'Near Expiry':'low', Expired:'bad', 'Out of Stock':'bad' }[m.status] || 'ok';
      const rowClass = { Expired:'r-expired', 'Near Expiry':'r-near', 'Low Stock':'r-low' }[m.status] || '';
      if (rowClass) tr.className = rowClass;
      tr.dataset.id = m.id;
      tr.innerHTML = `
        <td><span class="med-name">${m.medicine_name}</span><span class="med-generic">${m.generic_name || ''}</span></td>
        <td>${m.quantity}</td>
        <td>${m.unit}</td>
        <td>${m.purchase_quantity ?? '—'}</td>
        <td>${m.purchase_quantity ?? '—'}</td>
        <td>${m.ending_balance ?? '—'}</td>
        <td>${m.expiration_date}</td>
        <td>${pillFor(m.status)}</td>
        <td>
          <div class="acts">
            <button class="act-btn edit"   data-action="edit"   title="Edit"   type="button"><i class="fa-solid fa-pen"></i></button>
            <button class="act-btn deduct" data-action="deduct" title="Deduct" type="button"><i class="fa-solid fa-minus"></i></button>
            <button class="act-btn del"    data-action="delete" title="Delete" type="button"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      `;
      frag.appendChild(tr);
    });
    medTableBody.appendChild(frag);
    updateSummary(data);
  };

  const updateSummary = data => {
    const c = { total:data.length, available:0, low:0, expired:0, near:0, purchased:0, ending:0 };
    data.forEach(m => {
      if (m.status === 'Available')    c.available++;
      if (m.status === 'Low Stock')    c.low++;
      if (m.status === 'Expired')      c.expired++;
      if (m.status === 'Near Expiry')  c.near++;
      c.purchased += Number(m.purchase_quantity || 0);
      c.ending    += Number(m.ending_balance    || 0);
    });

    const set = (id, v) => { const el=document.getElementById(id); if(el) el.textContent=v; };
    set('sumTotal', c.total);
    set('sumAvailable', c.available);
    set('sumLow', c.low);
    set('sumExpired', c.expired);
    set('sumNear', c.near);
    set('sumPurchased', c.purchased);
    set('sumEndingBalance', c.ending);

    const aLow    = document.getElementById('alertLowStock');
    const aExp    = document.getElementById('alertExpired');
    const aNear   = document.getElementById('alertNearExpiry');
    const alerts  = document.getElementById('medAlerts');

    if (aLow)  aLow.style.display  = c.low     ? 'inline-flex' : 'none';
    if (aExp)  aExp.style.display  = c.expired ? 'inline-flex' : 'none';
    if (aNear) aNear.style.display = c.near    ? 'inline-flex' : 'none';

    const setTxt = (id, v) => { const el=document.getElementById(id); if(el) el.textContent=v; };
    setTxt('alertLowStockText', `${c.low} low stock`);
    setTxt('alertExpiredText',  `${c.expired} expired`);
    setTxt('alertNearExpiryText', `${c.near} near expiry`);

    if (alerts) alerts.style.display = (c.low || c.expired || c.near) ? 'flex' : 'none';
  };

  const applyFilters = () => {
    const s  = (searchInput?.value || '').toLowerCase().trim();
    const st = statusFilter?.value || '';
    renderTable(medicines.filter(m =>
      ((m.medicine_name||'').toLowerCase().includes(s) ||
       (m.generic_name ||'').toLowerCase().includes(s)) &&
      (!st || m.status.toLowerCase().replace(/\s/g,'') === st.replace(/\s/g,'') || m.status === st)
    ));
  };

  /* ── Modal Tab Switching ── */
  const TABS = ['master','inventory'];

  const switchTab = (tabId) => {
    activeTab = tabId;
    modalTabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tabId));
    tabPanels.forEach(p => p.classList.toggle('active', p.id === `tab-${tabId}`));

    const idx = TABS.indexOf(tabId);
    btnTabPrev.style.display  = idx > 0              ? 'inline-flex' : 'none';
    btnTabNext.style.display  = idx < TABS.length-1  ? 'inline-flex' : 'none';
    saveBtn.style.display     = idx === TABS.length-1 ? 'inline-flex' : 'none';
  };

  /* ── Validate per tab ── */
  const validateMaster = () => {
    clearErrors();
    let ok = true;
    const n = document.getElementById('medicine_name')?.value.trim();
    const c = document.getElementById('category')?.value;
    const u = document.getElementById('unit')?.value;

    if (!n) { if(errs.medicine_name) errs.medicine_name.textContent = 'Medicine name is required'; ok=false; }
    if (!c) { if(errs.category)      errs.category.textContent      = 'Category is required';      ok=false; }
    if (!u) { if(errs.unit)          errs.unit.textContent          = 'Unit is required';           ok=false; }
    return ok;
  };

  const validateInventory = () => {
    clearErrors();
    let ok = true;
    const q = parseFloat(qty?.value);
    const e = expDate?.value;

    if (!(q >= 0)) { if(errs.quantity)        errs.quantity.textContent        = 'Enter a valid quantity'; ok=false; }
    if (!e)        { if(errs.expiration_date)  errs.expiration_date.textContent = 'Expiration date required'; ok=false; }
    return ok;
  };

  /* ── Modal Open/Close ── */
  const openModal = () => {
    modal.classList.add('show');
    switchTab('master');
  };

  const closeModal = () => {
    modal.classList.remove('show');
    form.reset();
    clearErrors();
    updateComputed();
    switchTab('master');
  };

  const setLoading = on => {
    saveBtn.disabled = on;
    saveBtn.innerHTML = on
      ? '<span class="spinner"></span> Saving…'
      : '<i class="fa-solid fa-floppy-disk"></i> Save Medicine';
  };

  /* ── Event Listeners ── */

  addBtn?.addEventListener('click', openModal);

  document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
  modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  /* Tab clicking */
  modalTabs.forEach(tab => tab.addEventListener('click', () => {
    if (activeTab === 'master' && tab.dataset.tab === 'inventory') {
      if (!validateMaster()) return;
    }
    switchTab(tab.dataset.tab);
  }));

  /* Next / Prev */
  btnTabNext?.addEventListener('click', () => {
    const idx = TABS.indexOf(activeTab);
    if (idx === 0 && !validateMaster()) return;
    if (idx < TABS.length - 1) switchTab(TABS[idx + 1]);
  });

  btnTabPrev?.addEventListener('click', () => {
    const idx = TABS.indexOf(activeTab);
    if (idx > 0) switchTab(TABS[idx - 1]);
  });

  /* Year / Month */
  btnYearPrev?.addEventListener('click', () => { currentYear--;  updatePeriodUI(); });
  btnYearNext?.addEventListener('click', () => { currentYear++;  updatePeriodUI(); });
  monthTabs.forEach(tab => tab.addEventListener('click', () => {
    currentMonth = parseInt(tab.dataset.month);
    updatePeriodUI();
  }));

  /* Filters */
  searchInput?.addEventListener('input',  applyFilters);
  statusFilter?.addEventListener('change', applyFilters);

  /* Computed fields */
  qty?.addEventListener('input',         updateComputed);
  unitCost?.addEventListener('input',    updateComputed);
  purchaseQty?.addEventListener('input', updateComputed);
  expDate?.addEventListener('change',    updateComputed);

  /* Table row actions */
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
      toast('Edit — coming soon', 'success');
    }
  });

  /* Form Submit */
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateInventory()) return;

    setLoading(true);
    const fd = new FormData(form);
    const status = statusFrom(parseFloat(qty.value || 0), expDate.value);
    fd.append('status', status);

    try {
      const res  = await fetch('../../ajax/medicine/add_medicine.ajax.php', { method:'POST', body:fd });
      const json = await res.json();

      if (json.success) {
        toast(json.message || 'Medicine saved!', 'success');
        medicines.unshift({
          id:                Date.now(),
          medicine_name:     fd.get('medicine_name'),
          generic_name:      fd.get('generic_name'),
          quantity:          parseInt(fd.get('quantity') || 0),
          unit:              fd.get('unit'),
          purchase_quantity: parseInt(fd.get('purchase_quantity') || 0),
          ending_balance:    parseInt(fd.get('ending_balance') || fd.get('quantity') || 0),
          expiration_date:   fd.get('expiration_date'),
          status,
        });
        applyFilters();
        closeModal();
      } else {
        toast(json.message || 'Failed to save', 'error');
      }
    } catch {
      /* Dev mode: just add locally */
      medicines.unshift({
        id:                Date.now(),
        medicine_name:     fd.get('medicine_name') || 'New Medicine',
        generic_name:      fd.get('generic_name')  || '',
        quantity:          parseInt(fd.get('quantity') || 0),
        unit:              fd.get('unit') || 'pcs',
        purchase_quantity: parseInt(fd.get('purchase_quantity') || 0),
        ending_balance:    parseInt(fd.get('quantity') || 0),
        expiration_date:   fd.get('expiration_date') || '—',
        status,
      });
      applyFilters();
      toast('Medicine added (local mode)', 'success');
      closeModal();
    } finally {
      setLoading(false);
    }
  });

  /* Export button */
  document.getElementById('btnExport')?.addEventListener('click', () => {
    toast('Export — coming soon', 'warn');
  });

  /* ── Init ── */
  updatePeriodUI();
  renderTable(medicines);
  updateComputed();
});