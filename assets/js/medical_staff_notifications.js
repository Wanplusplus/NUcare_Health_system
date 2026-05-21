(function(){
  function $(id){ return document.getElementById(id); }

  const bellBtn = document.getElementById('notifBellBtn');
  const dropdown = document.getElementById('notifDropdown');
  const listEl = document.getElementById('notifList');
  const loadingEl = document.getElementById('notifLoading');
  const emptyEl = document.getElementById('notifEmpty');
  const lastUpdatedEl = document.getElementById('notifLastUpdated');

  let pollTimer = null;
  let isOpen = false;

  function close(){
    if (!dropdown) return;
    dropdown.classList.remove('show');
    dropdown.style.display = 'none';
    
    isOpen = false;
  }

  function open(){
    if (!dropdown) return;
    dropdown.classList.add('show');
    dropdown.style.display = 'block';

    isOpen = true;
    fetchNotifications(true);
  }

  function toggle(){
    if (!dropdown) return;
    if (dropdown.classList.contains('show')) close();
    else open();
  }

  function formatTimestamp(ts){
    // ts expected from server. If not parsable, return as-is.
    if (!ts) return '—';
    const d = new Date(ts);
    if (isNaN(d.getTime())) return String(ts);
    try{
      return d.toLocaleString('en-PH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    }catch(e){
      return String(ts);
    }
  }

  function priorityClass(p){
    const v = (p || '').toLowerCase();
    if (v === 'high' || v === 'critical') return 'prio-high';
    if (v === 'medium' || v === 'warning') return 'prio-medium';
    return 'prio-low';
  }

  function priorityLabel(p){
    const v = (p || '').toLowerCase();
    if (v === 'high' || v === 'critical') return 'High';
    if (v === 'medium' || v === 'warning') return 'Medium';
    if (v === 'low') return 'Low';
    return p ? String(p) : 'Low';
  }

  function colorDotClass(p){
    const v = (p || '').toLowerCase();
    if (v === 'high' || v === 'critical') return 'dot-red';
    if (v === 'medium' || v === 'warning') return 'dot-yellow';
    return 'dot-blue';
  }

  function render(items){
    if (!listEl) return;

    listEl.innerHTML = '';

    if (!items || items.length === 0){
      if (emptyEl) emptyEl.style.display = 'block';
      if (loadingEl) loadingEl.style.display = 'none';
      return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    items.forEach(n => {
      const row = document.createElement('div');
      row.className = 'notif-item';

      row.innerHTML = `
        <div class="notif-item-main">
          <div class="notif-title">
            <span class="prio-dot ${colorDotClass(n.priority)}" aria-hidden="true"></span>
            <span>${n.title ? escapeHtml(n.title) : 'Notification'}</span>
          </div>
          <div class="notif-subtitle">${escapeHtml(n.message || n.reference || '') || '<span class="muted">—</span>'}</div>
        </div>
        <div class="notif-meta">
          <span class="notif-prio ${priorityClass(n.priority)}">${escapeHtml(priorityLabel(n.priority))}</span>
          <div class="notif-ts">${escapeHtml(formatTimestamp(n.timestamp))}</div>
        </div>
      `;

      listEl.appendChild(row);
    });
  }

  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g, function(m){
      return ({'&':'&amp;','<':'<','>':'>','"':'"',"'":'&#039;'})[m];
    });
  }

  async function fetchNotifications(isOpenNow){
    if (!listEl) return;

    if (loadingEl) loadingEl.style.display = 'block';
    if (emptyEl) emptyEl.style.display = 'none';

    try{
      const url = '../../ajax/medical_staff_notifications.ajax.php';
      const res = await fetch(url, { method: 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });

      const data = await res.json();
      if (!data || data.success !== true) throw new Error((data && data.message) ? data.message : 'Failed');

      if (lastUpdatedEl) lastUpdatedEl.textContent = data.last_updated ? ('Updated: ' + formatTimestamp(data.last_updated)) : '';
      render(data.notifications || []);

    }catch(e){
      // On error, show empty state without crashing UI
      if (emptyEl) {
        emptyEl.style.display = 'block';
        emptyEl.innerHTML = '<span class="muted">Unable to load notifications.</span>';
      }
    }finally{
      if (loadingEl) loadingEl.style.display = 'none';
    }

    if (isOpenNow && bellBtn && dropdown){
      // Keep dropdown open
    }
  }

  function startPolling(){
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
      // Light polling: fetch only if dropdown is open; otherwise reduce overhead
      if (dropdown && dropdown.classList.contains('show')) fetchNotifications(true);
    }, 45000);
  }

  if (bellBtn && dropdown){
    // Ensure it's hidden initially
    dropdown.style.display = 'none';

    bellBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); toggle(); });


    document.addEventListener('click', (e) => {
      if (!dropdown.classList.contains('show')) return;
      const t = e.target;
      if (t && (bellBtn.contains(t) || dropdown.contains(t))) return;
      close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });

    // Initial fetch (so badge count / first view is populated)
    fetchNotifications(false);
    startPolling();
  }
})();

