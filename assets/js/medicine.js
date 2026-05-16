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
  const modal = document.getElementById('addMedicineModal');
  const closeBtns = document.querySelectorAll('[data-close-modal]');
  const form = document.getElementById('addMedicineForm');
  const saveBtn = document.getElementById('saveMedicineBtn');
  const toastWrap = document.getElementById('toastWrap');
  const qty = document.getElementById('quantity');
  const unitCost = document.getElementById('unit_cost');
  const purchaseQty = document.getElementById('purchase_quantity');
  const totalCost = document.getElementById('total_cost');
  const expDate = document.getElementById('expiration_date');
  const endingBalance = document.getElementById('ending_balance');
  const err = { medicine_name: document.getElementById('err_medicine_name'), category: document.getElementById('err_category'), quantity: document.getElementById('err_quantity'), unit: document.getElementById('err_unit'), expiration_date: document.getElementById('err_expiration_date') };

  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;
  const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  let medicines = [
    {id:1, medicine_name:'Paracetamol', generic_name:'Acetaminophen', quantity:120, unit:'pcs', purchase_quantity:200, ending_balance:120, expiration_date:'2026-12-20', status:'Available'},
    {id:2, medicine_name:'Amoxicillin', generic_name:'Antibiotic', quantity:15, unit:'capsules', purchase_quantity:40, ending_balance:15, expiration_date:'2026-07-01', status:'Low Stock'},
    {id:3, medicine_name:'Vitamin C', generic_name:'Ascorbic Acid', quantity:0, unit:'bottles', purchase_quantity:50, ending_balance:0, expiration_date:'2025-01-01', status:'Expired'},
    {id:4, medicine_name:'Cetirizine', generic_name:'Antihistamine', quantity:22, unit:'tablets', purchase_quantity:35, ending_balance:22, expiration_date:'2026-06-15', status:'Near Expiry'}
  ];

  const openModal = () => modal.classList.add('show');
  const closeModal = () => { modal.classList.remove('show'); form.reset(); clearErrors(); updateComputed(); };
  const toast = (message, type='success') => { const el = document.createElement('div'); el.className = `toast ${type}`; el.textContent = message; toastWrap.appendChild(el); setTimeout(() => el.remove(), 3000); };
  const clearErrors = () => Object.values(err).forEach(x => x && (x.textContent = ''));
  const setLoading = on => { saveBtn.disabled = on; saveBtn.innerHTML = on ? '<span class="spinner"></span> Saving...' : 'Save Medicine'; };

  const updatePeriodUI = () => {
    yearLabel.textContent = currentYear;
    monthTabs.forEach(t => t.classList.toggle('active', parseInt(t.dataset.month) === currentMonth));
    summaryPeriodLabel.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
  };

  const statusFrom = (quantity, exp) => {
    const days = Math.ceil((new Date(exp) - new Date()) / 86400000);
    if (quantity <= 0) return 'Out of Stock';
    if (days < 0) return 'Expired';
    if (days <= 30) return 'Near Expiry';
    if (quantity <= 20) return 'Low Stock';
    return 'Available';
  };

  const updateComputed = () => {
    const p = parseFloat(unitCost.value || 0) * parseFloat(purchaseQty.value || 0);
    totalCost.value = p.toFixed(2);
    endingBalance.value = qty.value !== '' ? qty.value : '';
  };

  const renderTable = data => {
    medTableBody.innerHTML = '';
    if (!data.length) {
      medEmpty.style.display = 'block';
      updateSummary([]);
      return;
    }

    medEmpty.style.display = 'none';
    const frag = document.createDocumentFragment();

    data.forEach(m => {
      const tr = document.createElement('tr');
      const cls = m.status === 'Available' ? 'ok' : m.status === 'Low Stock' ? 'low' : m.status === 'Near Expiry' ? 'near' : 'bad';
      tr.dataset.id = m.id;
      tr.innerHTML = `
        <td><span class="med-name">${m.medicine_name}</span><span class="med-generic">${m.generic_name || ''}</span></td>
        <td>${m.quantity}</td>
        <td>${m.unit}</td>
        <td>${m.purchase_quantity}</td>
        <td>${m.ending_balance}</td>
        <td>${m.expiration_date}</td>
        <td><span class="status-badge ${cls}">${m.status}</span></td>
        <td>
          <div class="acts">
            <button class="act-btn edit" data-action="edit" type="button"><i class="fa-solid fa-pen"></i></button>
            <button class="act-btn" data-action="deduct" type="button"><i class="fa-solid fa-minus"></i></button>
            <button class="act-btn del" data-action="delete" type="button"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      `;
      frag.appendChild(tr);
    });

    medTableBody.appendChild(frag);
    updateSummary(data);
  };

  const updateSummary = data => {
    const total = data.length,
      available = data.filter(m => m.status === 'Available').length,
      low = data.filter(m => m.status === 'Low Stock').length,
      expired = data.filter(m => m.status === 'Expired').length,
      near = data.filter(m => m.status === 'Near Expiry').length,
      purchased = data.reduce((s,m) => s + Number(m.purchase_quantity || 0), 0),
      ending = data.reduce((s,m) => s + Number(m.ending_balance || 0), 0);

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('sumTotal', total);
    set('sumAvailable', available);
    set('sumLow', low);
    set('sumExpired', expired);
    set('sumNear', near);
    set('sumPurchased', purchased);
    set('sumEndingBalance', ending);

    const aLow = document.getElementById('alertLowStock'),
      aExp = document.getElementById('alertExpired'),
      aNear = document.getElementById('alertNearExpiry'),
      alerts = document.getElementById('medAlerts');

    if (aLow) aLow.style.display = low ? 'inline-flex' : 'none';
    if (aExp) aExp.style.display = expired ? 'inline-flex' : 'none';
    if (aNear) aNear.style.display = near ? 'inline-flex' : 'none';
    if (document.getElementById('alertLowStockText')) document.getElementById('alertLowStockText').textContent = `${low} low stock`;
    if (document.getElementById('alertExpiredText')) document.getElementById('alertExpiredText').textContent = `${expired} expired`;
    if (document.getElementById('alertNearExpiryText')) document.getElementById('alertNearExpiryText').textContent = `${near} near expiry`;
    if (alerts) alerts.style.display = (low || expired || near) ? 'flex' : 'none';
  };

  const applyFilters = () => {
    const s = (searchInput.value || '').toLowerCase().trim();
    const st = statusFilter.value;
    renderTable(medicines.filter(m =>
      ((m.medicine_name || '').toLowerCase().includes(s) || (m.generic_name || '').toLowerCase().includes(s)) &&
      (!st || m.status.toLowerCase().replace(' ', '') === st.replace(' ', '') || m.status === st)
    ));
  };

  const validate = () => {
    clearErrors();
    let ok = true;
    const n = document.getElementById('medicine_name').value.trim();
    const c = document.getElementById('category').value;
    const u = document.getElementById('unit').value;
    const q = parseFloat(qty.value);
    const e = expDate.value;

    if (!n) { err.medicine_name.textContent = 'Medicine name is required'; ok = false; }
    if (!c) { err.category.textContent = 'Category is required'; ok = false; }
    if (!u) { err.unit.textContent = 'Unit is required'; ok = false; }
    if (!(q >= 0)) { err.quantity.textContent = 'Quantity must be a positive number'; ok = false; }
    if (!e) { err.expiration_date.textContent = 'Expiration date is required'; ok = false; }

    return ok;
  };

  addBtn?.addEventListener('click', openModal);
  closeBtns.forEach(b => b.addEventListener('click', closeModal));
  modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  btnYearPrev?.addEventListener('click', () => { currentYear--; updatePeriodUI(); });
  btnYearNext?.addEventListener('click', () => { currentYear++; updatePeriodUI(); });
  monthTabs.forEach(tab => tab.addEventListener('click', () => { currentMonth = parseInt(tab.dataset.month); updatePeriodUI(); }));
  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  qty?.addEventListener('input', updateComputed);
  unitCost?.addEventListener('input', updateComputed);
  purchaseQty?.addEventListener('input', updateComputed);

  medTableBody?.addEventListener('click', e => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    toast(`${btn.dataset.action} clicked`, btn.dataset.action === 'delete' ? 'error' : 'success');
  });

  form?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validate()) return;

    setLoading(true);
    const fd = new FormData(form);
    fd.append('status', statusFrom(parseFloat(qty.value || 0), expDate.value));

    try {
      const res = await fetch('../../ajax/medicine/add_medicine.ajax.php', {
        method: 'POST',
        body: fd
      });
      const json = await res.json();

      if (json.success) {
        toast(json.message, 'success');
        medicines.unshift({
          id: Date.now(),
          medicine_name: fd.get('medicine_name'),
          generic_name: fd.get('generic_name'),
          quantity: parseInt(fd.get('quantity') || 0),
          unit: fd.get('unit'),
          purchase_quantity: parseInt(fd.get('purchase_quantity') || 0),
          ending_balance: parseInt(fd.get('ending_balance') || fd.get('quantity') || 0),
          expiration_date: fd.get('expiration_date'),
          status: fd.get('status')
        });
        renderTable(medicines);
        closeModal();
      } else {
        toast(json.message, 'error');
      }
    } catch (err) {
      toast('Failed to add medicine', 'error');
    } finally {
      setLoading(false);
    }
  });

  updatePeriodUI();
  renderTable(medicines);
  updateComputed();
});