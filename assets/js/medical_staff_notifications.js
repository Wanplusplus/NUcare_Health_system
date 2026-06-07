(function(){
 const bellBtn = document.getElementById('notifBellBtn');
 const dropdown = document.getElementById('notifDropdown');
 const listEl = document.getElementById('notifList');
 const loadingEl = document.getElementById('notifLoading');
 const emptyEl = document.getElementById('notifEmpty');
 const lastUpdatedEl = document.getElementById('notifLastUpdated');
 const badgeEl = document.getElementById('notifBadge');

 let pollTimer = null;

 function close(){
 if (!dropdown) return;
 dropdown.classList.remove('show');
 dropdown.style.display = 'none';
 if (bellBtn) bellBtn.setAttribute('aria-expanded', 'false');
 }

 function open(){
 if (!dropdown) return;
 dropdown.classList.add('show');
 dropdown.style.display = 'block';
 if (bellBtn) bellBtn.setAttribute('aria-expanded', 'true');
 fetchNotifications(true);
 }

 function toggle(){
 if (!dropdown) return;
 if (dropdown.classList.contains('show')) close();
 else open();
 }

 function formatTimestamp(ts){
 if (!ts) return '-';
 const d = new Date(ts);
 if (isNaN(d.getTime())) return String(ts);
 try{
 return d.toLocaleString('en-PH', {
 year: 'numeric',
 month: 'short',
 day: 'numeric',
 hour: '2-digit',
 minute: '2-digit'
 });
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

 const safeItems = Array.isArray(items) ? items : [];
 updateBadge(safeItems.length);
 listEl.innerHTML = '';

 if (safeItems.length === 0){
 if (emptyEl) emptyEl.style.display = 'block';
 if (loadingEl) loadingEl.style.display = 'none';
 return;
 }

 if (emptyEl) emptyEl.style.display = 'none';

 safeItems.forEach(n => {
 const row = document.createElement('div');
 const targetUrl = getSafeTargetUrl(n.target_url);
 row.className = 'notif-item' + (targetUrl ? ' notif-item--clickable' : '');

 if (targetUrl) {
 row.setAttribute('role', 'button');
 row.setAttribute('tabindex', '0');
 row.setAttribute('aria-label', `${n.title || 'Notification'} - ${n.target_label || 'Open'}`);

 const openTarget = () => {
 window.location.href = targetUrl;
 };
 row.addEventListener('click', openTarget);
 row.addEventListener('keydown', (e) => {
 if (e.key === 'Enter' || e.key === ' ') {
 e.preventDefault();
 openTarget();
 }
 });
 }

 row.innerHTML = `
 <div class="notif-item-main">
 <div class="notif-title">
 <span class="prio-dot ${colorDotClass(n.priority)}" aria-hidden="true"></span>
 <span>${n.title ? escapeHtml(n.title) : 'Notification'}</span>
 </div>
 <div class="notif-subtitle">${escapeHtml(n.message || n.reference || '') || '<span class="muted">-</span>'}</div>
 ${targetUrl ? `<div class="notif-action">${escapeHtml(n.target_label || 'Open')}</div>` : ''}
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
 return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];
 });
 }

 async function fetchNotifications(){
 if (!listEl) return;

 if (loadingEl) loadingEl.style.display = 'block';
 if (emptyEl) emptyEl.style.display = 'none';

 try{
 const url = '/NUcare_Health_system/backend/ajax/medical_staff_notifications.ajax.php';
 const res = await fetch(url, {
 method: 'GET',
 credentials: 'same-origin',
 headers: { 'Accept': 'application/json' }
 });

 const data = await res.json();
 if (!data || data.success !== true) {
 throw new Error((data && data.message) ? data.message : 'Failed');
 }

 if (lastUpdatedEl) {
 lastUpdatedEl.textContent = data.last_updated ? ('Updated: ' + formatTimestamp(data.last_updated)) : '';
 }
 render(data.notifications || []);

 }catch(e){
 if (emptyEl) {
 emptyEl.style.display = 'block';
 emptyEl.innerHTML = '<span class="muted">Unable to load notifications.</span>';
 }
 updateBadge(0);
 }finally{
 if (loadingEl) loadingEl.style.display = 'none';
 }
 }

 function startPolling(){
 if (pollTimer) clearInterval(pollTimer);
 pollTimer = setInterval(() => {
 fetchNotifications();
 }, 15000);
 }

 function updateBadge(count){
 if (!badgeEl) return;
 const safeCount = Number.isFinite(Number(count)) ? Number(count) : 0;
 if (safeCount > 0) {
 badgeEl.textContent = safeCount > 99 ? '99+' : String(safeCount);
 badgeEl.style.display = 'grid';
 } else {
 badgeEl.textContent = '0';
 badgeEl.style.display = 'none';
 }
 }

 function getSafeTargetUrl(url){
 if (!url || typeof url !== 'string') return '';
 try {
 const parsed = new URL(url, window.location.href);
 if (parsed.origin !== window.location.origin) return '';
 return parsed.href;
 } catch (e) {
 return '';
 }
 }

 if (bellBtn && dropdown){
 dropdown.style.display = 'none';

 bellBtn.addEventListener('click', (e) => {
 e.preventDefault();
 e.stopPropagation();
 toggle();
 });

 document.addEventListener('click', (e) => {
 if (!dropdown.classList.contains('show')) return;
 const target = e.target;
 if (target && (bellBtn.contains(target) || dropdown.contains(target))) return;
 close();
 });

 document.addEventListener('keydown', (e) => {
 if (e.key === 'Escape') close();
 });

 fetchNotifications();
 startPolling();
 }
})();

