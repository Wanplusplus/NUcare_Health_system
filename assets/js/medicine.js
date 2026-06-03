document.addEventListener('DOMContentLoaded', () => {
  const yearLabel = document.getElementById('yearLabel');
  const btnYearPrev = document.getElementById('btnYearPrev');
  const btnYearNext = document.getElementById('btnYearNext');
  const monthTabs = document.querySelectorAll('.month-tab');
  const btnAllStocks = document.getElementById('btnAllStocks');
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

  const errs = {
    medicine_name: document.getElementById('err_medicine_name'),
    category: document.getElementById('err_category'),
    unit: document.getElementById('err_unit'),
    quantity: document.getElementById('err_quantity'),
    expiration_date: document.getElementById('err_expiration_date'),
  };

  const apiUrl = '../../ajax/medicine_ajax.php';
  const currentUrl = new URL(window.location.href);

  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;
  let activeTab = 'master';
  let medicines = [];
  let filteredMedicines = [];
  let isLoading = false;
  let showAllStocks = false;

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];

  const parseLocalDate = (value) => {
    if (!value) return null;

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
      const year = Number(match[1]);
      const month = Number(match[2]);
      const day = Number(match[3]);
      return {
        date: new Date(year, month - 1, day),
        year,
        month,
        day,
      };
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;

    return {
      date,
      year: date.getFullYear(),
      month: date.getMonth() + 1,
      day: date.getDate(),
    };
  };

  const formatDisplayDate = (value) => {
    const parsed = parseLocalDate(value);
    if (!parsed) return '—';

    return parsed.date.toLocaleDateString('en-PH', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const toast = (msg, type = 'success') => {
    if (!toastWrap) return;

    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    toastWrap.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  };

  let deleteConfirmResolve = null;

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

    const parsedExpiry = parseLocalDate(exp);
    if (!parsedExpiry) {
      return quantity <= 0 ? 'Out Of Stock' : 'Available';
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const expDateObj = parsedExpiry.date;
    expDateObj.setHours(0, 0, 0, 0);

    const days = Math.ceil((expDateObj - today) / 86400000);
    if (quantity <= 0) return 'Out Of Stock';
    if (days < 0) return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (quantity <= 50) return 'Low Stock';
    return 'Available';
  };

  const pillFor = (status) => {
    const map = {
      Available: ['ok', 'fa-circle-check'],
      'Low Stock': ['low', 'fa-triangle-exclamation'],
      'Near Expiry': ['low', 'fa-clock'],
      Expired: ['bad', 'fa-ban'],
      'Out Of Stock': ['bad', 'fa-circle-xmark'],
    };
    const [cls, icon] = map[status] || ['ok', 'fa-circle-check'];
    return `<span class="s-pill ${cls}"><i class="fa-solid ${icon}"></i> ${status}</span>`;
  };

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
    monthTabs.forEach((t) => {
      t.classList.toggle('active', !showAllStocks && parseInt(t.dataset.month, 10) === currentMonth);
    });
    if (btnAllStocks) btnAllStocks.classList.toggle('active', showAllStocks);
    if (summaryPeriodLabel) {
      summaryPeriodLabel.textContent = showAllStocks
        ? 'All medicine stocks'
        : `${monthNames[currentMonth - 1]} ${currentYear}`;
    }
  };

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
        <td>${formatDisplayDate(m.expiry_date)}</td>
        <td>${pillFor(displayStatus)}</td>
        <td>
          <div class="acts">
            <button class="act-btn edit" data-action="edit" title="Edit" type="button"><i class="fa-solid fa-pen"></i></button>
            <button class="act-btn del" data-action="delete" title="Delete" type="button"><i class="fa-solid fa-trash"></i></button>
            <button class="act-btn print" data-action="print" title="Print" type="button"><i class="fa-solid fa-print"></i></button>
          </div>
        </td>
      `;

      frag.appendChild(tr);
    });

    medTableBody.appendChild(frag);
    updateSummary(data);
  };

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
    setTxt('alertNearExpiryText', `${c.near} near expiry`);

    if (alerts) alerts.style.display = (c.low || c.expired || c.near) ? 'flex' : 'none';
  };

  const applyFilters = () => {
    const search = (searchInput?.value || '').toLowerCase().trim();
    const st = normalizeStatusFilter(statusFilter?.value || '');

    filteredMedicines = medicines.filter((m) => {
      const displayStatus = getDisplayStatus(m);
      const expiry = parseLocalDate(m.expiry_date);
      const periodMatch = showAllStocks || (expiry
        && expiry.year === currentYear
        && expiry.month === currentMonth);

      const textMatch = (m.medicine_name || '').toLowerCase().includes(search)
        || (m.generic_name || '').toLowerCase().includes(search)
        || (m.medicine_type || '').toLowerCase().includes(search)
        || (m.batch_number || '').toLowerCase().includes(search);

      const statusMatch = !st || displayStatus === st;
      return periodMatch && textMatch && statusMatch;
    });

    renderTable(filteredMedicines);
  };

  const selectedPeriodHasRows = () => medicines.some((m) => {
    if (showAllStocks) return true;
    const expiry = parseLocalDate(m.expiry_date);
    return expiry && expiry.year === currentYear && expiry.month === currentMonth;
  });

  const moveToFirstAvailablePeriod = () => {
    if (!medicines.length || selectedPeriodHasRows()) return;

    const periods = medicines
      .map((m) => parseLocalDate(m.expiry_date))
      .filter(Boolean)
      .sort((a, b) => a.date - b.date);

    if (!periods.length) return;

    currentYear = periods[0].year;
    currentMonth = periods[0].month;
    updatePeriodUI();
  };

  const switchTab = (tabId) => {
    activeTab = tabId;
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

    return ok;
  };

  const validateInventory = () => {
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
      moveToFirstAvailablePeriod();
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

    if (showAllStocks) {
      params.set('all', '1');
    } else {
      params.set('year', String(currentYear));
      params.set('month', String(currentMonth));
    }

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

  btnTabNext?.addEventListener('click', () => {
    if (!validateMaster()) return;
    switchTab('inventory');
  });

  btnTabPrev?.addEventListener('click', () => switchTab('master'));

  btnYearPrev?.addEventListener('click', () => {
    showAllStocks = false;
    currentYear -= 1;
    updatePeriodUI();
    applyFilters();
  });

  btnYearNext?.addEventListener('click', () => {
    showAllStocks = false;
    currentYear += 1;
    updatePeriodUI();
    applyFilters();
  });

  monthTabs.forEach((tab) => tab.addEventListener('click', () => {
    showAllStocks = false;
    currentMonth = parseInt(tab.dataset.month, 10);
    updatePeriodUI();
    applyFilters();
  }));

  btnAllStocks?.addEventListener('click', () => {
    showAllStocks = !showAllStocks;
    updatePeriodUI();
    applyFilters();
  });

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);

  qty?.addEventListener('input', updateComputed);
  unitCost?.addEventListener('input', updateComputed);
  purchaseQty?.addEventListener('input', updateComputed);
  expDate?.addEventListener('change', updateComputed);

  medTableBody?.addEventListener('click', async (e) => {
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

    if (action === 'print') {
  if (!medicineId) {
    toast('Unable to determine medicine ID for printing', 'error');
    return;
  }

  window.open(
    `../../print-output/individual_medicine_output.php?id=${medicineId}`,
    '_blank',
    'noopener,noreferrer',
  );
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
    } finally {
      setLoading(false);
    }
  });

  updatePeriodUI();
  switchTab('master');
  updateComputed();
  fetchMedicines();
});