(function () {

 const qInput = document.getElementById('consultSearchInput');
 const feedback = document.getElementById('searchFeedback');
 const acList = document.getElementById('searchAutocomplete');

 let debounceTimer = null;

 /* -- helpers --- */
 function setFeedback(html, cls) {
 if (!feedback) return;
 feedback.className = 'search-feedback ' + (cls || '');
 feedback.innerHTML = html;
 }

 function closeAutocomplete() {
 if (acList) { acList.innerHTML = ''; acList.classList.remove('open'); }
 }

 function escapeHtml(value) {
 return String(value ?? '')
 .replace(/&/g, '&amp;')
 .replace(/</g, '&lt;')
 .replace(/>/g, '&gt;')
 .replace(/"/g, '&quot;')
 .replace(/'/g, '&#39;');
 }

 /* -- Populate patient banner --- */
 function populateBanner(p) {
 const fullName = p.FullName
 || [p.FirstName, p.MiddleName ? p.MiddleName[0] + '.' : '', p.LastName]
 .filter(Boolean).join(' ')
 || '-';

 const set = (id, val) => {
 const el = document.getElementById(id);
 if (el) el.textContent = val || '-';
 };

 set('cpcName', fullName);
 set('cpcID', p.SchoolID);
 set('cpcSex', p.Sex);
 set('cpcType', p.PersonType);
 set('cpcTime', p.LoadedAt);
 set('cpcAge', p.Age != null ? p.Age : null);

 const avatarEl = document.getElementById('patientAvatarInitials');
 if (avatarEl) {
 const parts = fullName.trim().split(/\s+/);
 const initials = ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase();
 avatarEl.innerHTML = `<span class="avatar-text">${initials}</span>`;
 }

 const banner = document.getElementById('consultPatientCard');
 if (banner) banner.classList.add('visible');
 }

 /* -- Module-level patient ID - never wiped by form.reset() -- */
 let _currentSpid = 0;

 /* -- Load patient then start transaction --- */
 function loadPatient(p) {
 populateBanner(p);

 _currentSpid = parseInt(p.SchoolPersonID, 10);

 const spidInput = document.getElementById('consultPatientID');
 if (spidInput) spidInput.value = _currentSpid;

 unlockForm();

 // Guard, Visitor, and ROMAC can consult without a School ID.
 // Default them into the normal general consultation flow.
 if (!p.SchoolID && ['Guard', 'Visitor', 'ROMAC'].includes(p.PersonType || '')) {
 const serviceSelect = document.getElementById('consultService');
 if (serviceSelect) {
 serviceSelect.value = 'General Consultation';
 window.onServiceTypeChange?.('General Consultation');
 }
 }

 loadHistory(_currentSpid);
 startTransaction(_currentSpid);
 }

 /* -- Safe helper: always returns current patient SPID -- */
 function getCurrentSpid() {
 if (_currentSpid > 0) return _currentSpid;
 const v = parseInt(document.getElementById('consultPatientID')?.value || '0', 10);
 return v > 0 ? v : 0;
 }

 /* -- Main search --- */
 window.searchPatient = function () {
 const query = qInput ? qInput.value.trim() : '';

 if (!query) {
 setFeedback('<i class="fa-solid fa-circle-exclamation"></i> Please enter a School ID or name.', 'not-found');
 return;
 }

 setFeedback('<i class="fa-solid fa-spinner fa-spin"></i> Searching...', '');
 closeAutocomplete();

 fetch('/NUcare_Health_system/backend/ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(query))
 .then(r => r.text())
 .then(raw => {
 let data;
 try { data = JSON.parse(raw); } catch (e) {
 console.error('Patient search raw response:', raw);
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server returned invalid response.', 'not-found');
 return;
 }

 if (data.ok && data.multiple && Array.isArray(data.results) && data.results.length > 0) {
 renderAutocomplete(data.results);
 setFeedback('<i class="fa-solid fa-list"></i> Multiple patients found - select one below.', '');
 return;
 }

 if (!data || !data.ok || !data.found) {
 setFeedback('<i class="fa-solid fa-circle-exclamation"></i> No patient found. Check the ID and try again.', 'not-found');
 if (typeof window.openWalkinRegistrationModal === 'function') {
 window.openWalkinRegistrationModal(query);
 }
 return;
 }

 const p = data.patient || data;
 setFeedback('<i class="fa-solid fa-circle-check"></i> Patient found.', 'found');
 loadPatient(p);
 })
 .catch(err => {
 console.error('Patient search fetch error:', err);
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while searching.', 'not-found');
 });
 };

 /* -- Autocomplete live search (debounced) --- */
 window.onSearchInput = function (val) {
 clearTimeout(debounceTimer);
 const q = val.trim();
 if (q.length < 2) { closeAutocomplete(); return; }

 debounceTimer = setTimeout(() => {
 fetch('/NUcare_Health_system/backend/ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(q))
 .then(r => r.json())
 .then(data => {
 if (!data.ok) { closeAutocomplete(); return; }
 if (data.found) {
 renderAutocomplete([data.patient || data]);
 return;
 }
 if (data.multiple && Array.isArray(data.results)) {
 renderAutocomplete(data.results);
 } else {
 closeAutocomplete();
 }
 })
 .catch(() => closeAutocomplete());
 }, 280);
 };

 function renderAutocomplete(results) {
 if (!acList) return;
 acList.innerHTML = '';

 results.forEach(p => {
 const li = document.createElement('li');
 li.className = 'ac-item';
 li.setAttribute('tabindex', '0');
 const displayId = p.SchoolID || 'No School ID';
 li.innerHTML = `
 <span class="ac-name">${p.FullName || p.LastName}</span>
 <span class="ac-id">${displayId}</span>
 <span class="ac-meta">${p.PersonType || ''}</span>
 `;
 li.addEventListener('click', () => {
 if (qInput) qInput.value = p.SchoolID || p.FullName || p.PersonType || '';
 closeAutocomplete();
 setFeedback('<i class="fa-solid fa-circle-check"></i> Patient loaded.', 'found');
 loadPatient(p);
 });
 li.addEventListener('keydown', e => { if (e.key === 'Enter') li.click(); });
 acList.appendChild(li);
 });

 acList.classList.add('open');
 }

 document.addEventListener('click', e => {
 if (acList && !acList.contains(e.target) && e.target !== qInput) closeAutocomplete();
 });

 /* ---
 5. SERVICE TYPE DYNAMIC UI
 --- */

 /**
 * Section keys -> maps to id="section-{key}" OR data-section="{key}".
 * Hidden sections still exist in DOM but are invisible - they do NOT
 * submit data because the server ignores fields for other service types.
 */
 const ALL_SECTION_KEYS = [
 'vitals',
 'consultation-details',
 'medicines',
 'attachment',
 'physical-exam',
 'firstaid'
 ];

 /**
 * Per service type: list of section keys to HIDE.
 * Everything not listed stays visible.
 */
 const SERVICE_HIDE_MAP = {
 // consultation-details is NEVER hidden - always visible on all service types
 'General Consultation': ['physical-exam', 'firstaid'],
 'Dental': ['physical-exam', 'firstaid'],
 'First Aid': ['physical-exam', 'attachment'],
 'Wound Care': ['physical-exam'],
 'Sent Home': ['physical-exam', 'firstaid'],
 'Medical Certificate': ['vitals', 'physical-exam', 'medicines', 'firstaid'],
 'Physical Examination': ['vitals', 'firstaid'],
 'Other': [],
 };

 function getSectionEl(key) {
 return document.getElementById('section-' + key)
 || document.querySelector('[data-section="' + key + '"]');
 }

 function setSectionVisible(key, visible) {
 const el = getSectionEl(key);
 if (!el) return;
 if (visible) {
 el.classList.remove('hidden');
 el.style.display = '';
 } else {
 el.classList.add('hidden');
 el.style.display = 'none';
 }
 }

 function applyServiceType(val) {
 const hideList = SERVICE_HIDE_MAP[val] ?? [];

 // First show ALL sections
 ALL_SECTION_KEYS.forEach(key => setSectionVisible(key, true));

 // Then hide only what this service type doesn't need
 hideList.forEach(key => setSectionVisible(key, false));

 // Show/hide attachment required badge for Medical Certificate
 const attachBadge = document.getElementById('attachmentRequiredBadge');
 if (attachBadge) attachBadge.style.display = (val === 'Medical Certificate') ? '' : 'none';

 const attachmentCategory = document.getElementById('attachmentCategory');
 if (val === 'Medical Certificate' && attachmentCategory) {
 attachmentCategory.value = 'Medical Certificate';
 const mcFields = document.getElementById('mcExtraFields');
 if (mcFields) mcFields.style.display = '';
 }

 // Update page-header service badge
 const badge = document.getElementById('serviceTypeBadge');
 if (badge) {
 const badgeMap = {
 'General Consultation': ['general', 'General'],
 'First Aid': ['firstaid', 'First Aid'],
 'Medical Certificate': ['medcert', 'Med Cert'],
 'Physical Examination': ['physexam', 'Physical Exam']
 };
 const [cls, label] = badgeMap[val] || ['general', val || 'General'];
 badge.className = 'service-badge ' + cls;
 badge.textContent = label;
 badge.style.display = val ? '' : 'none';
 }
 }

 window.onServiceTypeChange = function (val) {
 const otherWrap = document.getElementById('otherServiceWrap');
 if (otherWrap) otherWrap.style.display = (val === 'Other') ? '' : 'none';
 applyServiceType(val);
 };

 // Attach directly to select for reliability
 (function initServiceType() {
 const sel = document.getElementById('consultService');
 if (!sel) return;

 sel.addEventListener('change', function () {
 window.onServiceTypeChange(this.value);
 });

 if (sel.value) {
 applyServiceType(sel.value);
 } else {
 applyServiceType('General Consultation');
 }
 })();


 /* ---
 START TRANSACTION
 --- */
 function startTransaction(spid) {
 if (!spid) {
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Missing patient ID.', 'not-found');
 return;
 }

 fetch('/NUcare_Health_system/backend/ajax/consultation/create_transaction.ajax.php', {
 method: 'POST',
 headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
 body: new URLSearchParams({ school_person_id: spid, mode: 'auto' })
 })
 .then(r => r.json())
 .then(resp => {
 if (!resp.ok) {
 if (resp.historyCount && resp.historyCount > 0) {
 const modal = document.getElementById('txConfirmModal');
 if (modal) modal.style.display = 'block';
 showOverlay();
 } else {
 console.error('Create transaction failed:', resp);
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> ' + (resp.debug || resp.message || 'Unable to start transaction.'), 'not-found');
 }
 return;
 }
 document.getElementById('consultationID').value = resp.consultation_id;
 unlockForm();
 setFeedback(
 '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.',
 'found'
 );
 })
 .catch(err => {
 console.error('Create transaction fetch error:', err);
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while starting transaction.', 'not-found');
 });
 }

 window.confirmAddAnother = function (yes) {
 const modal = document.getElementById('txConfirmModal');
 if (modal) modal.style.display = 'none';

 if (!yes) {
 document.getElementById('consultationID').value = '';
 lockForm();
 return;
 }

 const spid = getCurrentSpid();
 if (!spid) {
 alert('Patient ID missing. Please search again.');
 return;
 }

 fetch('/NUcare_Health_system/backend/ajax/consultation/create_transaction.ajax.php', {
 method: 'POST',
 headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
 body: new URLSearchParams({ school_person_id: spid, mode: 'next' })
 })
 .then(r => r.json())
 .then(resp => {
 if (!resp.ok) {
 console.error('confirmAddAnother failed:', resp);
 lockForm();
 return;
 }
 document.getElementById('consultationID').value = resp.consultation_id;
 unlockForm();
 setFeedback(
 '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.',
 'found'
 );
 })
 .catch(() => {
 setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error creating transaction.', 'not-found');
 });
 };

 window.closeTxConfirm = function () {
 const modal = document.getElementById('txConfirmModal');
 if (modal) modal.style.display = 'none';
 };

 /* ---
 PATIENT HISTORY MODAL
 --- */

 let _histModalData = []; // cached transactions for the modal
 let _histModalPatient = ''; // patient name for header

 window.openPatientHistoryModal = function () {
 // Close the choice modal if open
 const choiceModal = document.getElementById('txConfirmModal');
 if (choiceModal) choiceModal.style.display = 'none';

 const modal = document.getElementById('patientHistoryModal');
 if (!modal) return;
 modal.style.display = 'block';
 document.body.classList.add('modal-open');

 // Set patient name in header
 const patientName = document.getElementById('cpcName')?.textContent || '';
 const patientID = document.getElementById('cpcID')?.textContent || '';
 const nameEl = document.getElementById('histModalPatientName');
 if (nameEl) nameEl.textContent = patientName + (patientID ? ' * ' + patientID : '');

 // Clear search input
 const searchInput = document.getElementById('modalHistSearchInput');
 if (searchInput) searchInput.value = '';

 // Always fetch fresh - never trust stale cache (patient may have changed,
 // or a new transaction may have just been saved)
 const spid = getCurrentSpid();
 if (!spid) return;

 const bodyEl = document.getElementById('modalHistBody');
 if (bodyEl) bodyEl.innerHTML = '<div class="modal-hist-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading records...</div>';

 fetch('/NUcare_Health_system/backend/ajax/consultation/list_transactions.ajax.php?school_person_id=' + encodeURIComponent(spid))
 .then(r => r.json())
 .then(resp => {
 if (!resp.ok || !Array.isArray(resp.transactions) || !resp.transactions.length) {
 if (bodyEl) bodyEl.innerHTML = '<div class="modal-hist-empty"><i class="fa-solid fa-folder-open"></i><span>No consultation history found for this patient.</span></div>';
 _histModalData = [];
 return;
 }
 _histModalData = resp.transactions;
 renderModalHistoryList(_histModalData);
 })
 .catch(() => {
 if (bodyEl) bodyEl.innerHTML = '<div class="modal-hist-empty" style="color:var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i><span>Failed to load history. Check your connection and try again.</span></div>';
 });
 };

 window.closePatientHistoryModal = function () {
 const modal = document.getElementById('patientHistoryModal');
 if (modal) modal.style.display = 'none';
 document.body.classList.remove('modal-open');
 };

 function renderModalHistoryList(transactions) {
 const bodyEl = document.getElementById('modalHistBody');
 if (!bodyEl) return;
 if (!transactions.length) {
 bodyEl.innerHTML = '<div class="modal-hist-empty"><i class="fa-solid fa-folder-open"></i><span>No records match your search.</span></div>';
 return;
 }
 bodyEl.innerHTML = '';
 transactions.forEach(tx => {
 const ctid = tx.TransactionNumber ?? tx.ClinicTransactionID;
 const statusClass = {
 'Completed' : 'hist-status-done',
 'Cancelled' : 'hist-status-cancel',
 'Waiting' : 'hist-status-wait',
 'Consulting': 'hist-status-active',
 }[tx.Status] || '';
 const medCount = Array.isArray(tx.medicines) ? tx.medicines.length : 0;

 const row = document.createElement('div');
 row.className = 'mhist-row';
 row.setAttribute('data-ctid', ctid);
 row.innerHTML = `
 <div class="mhist-row-left">
 <div class="mhist-dot"></div>
 <div class="mhist-info">
 <div class="mhist-top">
 <span class="mhist-txnum">#${ctid}</span>
 <span class="mhist-date"><i class="fa-regular fa-calendar"></i> ${tx.VisitDate ?? tx.CreatedAt ?? '-'}</span>
 <span class="hist-status ${statusClass}">${tx.Status ?? ''}</span>
 </div>
 <div class="mhist-service"><i class="fa-solid fa-stethoscope"></i> ${tx.ServiceType ?? 'General'}</div>
 <div class="mhist-complaint">${tx.Complaint ?? '<em style="color:var(--gray-400);">No chief complaint recorded</em>'}</div>
 ${medCount ? `<div class="mhist-med-count"><i class="fa-solid fa-pills"></i> ${medCount} medicine${medCount > 1 ? 's' : ''} dispensed</div>` : ''}
 </div>
 </div>
 <div class="mhist-row-right">
 <button class="btn-mhist-open" title="Open record">
 <i class="fa-solid fa-chevron-right"></i>
 </button>
 </div>
 `;
 row.addEventListener('click', () => openTxDetailModal(tx));
 bodyEl.appendChild(row);
 });
 }

 let _histSearchTimer = null;
 window.onModalHistSearch = function (val) {
 clearTimeout(_histSearchTimer);
 _histSearchTimer = setTimeout(() => {
 const q = val.trim().toLowerCase();
 if (!q) { renderModalHistoryList(_histModalData); return; }
 const filtered = _histModalData.filter(tx =>
 (tx.Complaint ?? '').toLowerCase().includes(q) ||
 (tx.ServiceType ?? '').toLowerCase().includes(q) ||
 String(tx.TransactionNumber ?? tx.ClinicTransactionID ?? '').includes(q) ||
 (tx.Status ?? '').toLowerCase().includes(q)
 );
 renderModalHistoryList(filtered);
 }, 200);
 };

 /* ---
 TRANSACTION DETAIL MODAL (READ-ONLY)
 --- */

 window.openTxDetailModal = function (tx) {
 // Hide history list modal, show detail modal
 const histModal = document.getElementById('patientHistoryModal');
 if (histModal) histModal.style.display = 'none';

 const modal = document.getElementById('txDetailModal');
 if (!modal) return;
 modal.style.display = 'block';
 document.body.classList.add('modal-open');

 const ctid = tx.TransactionNumber ?? tx.ClinicTransactionID;

 const titleEl = document.getElementById('txDetailModalTitle');
 if (titleEl) titleEl.textContent = `Transaction #${ctid} - ${tx.ServiceType ?? 'Consultation'}`;

 const subEl = document.getElementById('txDetailModalSub');
 if (subEl) subEl.textContent = `Visit Date: ${tx.VisitDate ?? tx.CreatedAt ?? '-'} * Status: ${tx.Status ?? '-'}`;

 const bodyEl = document.getElementById('txDetailModalBody');
 if (!bodyEl) return;

 // Build the detail body from the tx object we already have
 // (same data the timeline uses - no extra AJAX needed)
 bodyEl.innerHTML = buildTxDetailHTML(tx);
 };

 window.closeTxDetailModal = function () {
 const modal = document.getElementById('txDetailModal');
 if (modal) modal.style.display = 'none';
 document.body.classList.remove('modal-open');
 };

 /* -- Image lightbox preview --- */
 window.openAttachmentPreview = function (url, encodedName) {
 const modal = document.getElementById('attachPreviewModal');
 if (!modal) return;
 const img = document.getElementById('attachPreviewImg');
 const name = document.getElementById('attachPreviewName');
 const dl = document.getElementById('attachPreviewDl');
 if (img) { img.src = url; img.style.display = 'block'; }
 if (name) name.textContent = decodeURIComponent(encodedName);
 if (dl) dl.href = url + '&dl=1';
 modal.style.display = 'flex';
 };

 /* -- Fetch attachment as blob and open/download - bypasses any server redirects -- */
 window.fetchAndOpenAttachment = function (url, encodedName, forceDownload) {
 fetch(url, { credentials: 'same-origin' })
 .then(r => {
 if (!r.ok) throw new Error('Server returned ' + r.status);
 return r.blob();
 })
 .then(blob => {
 const objectUrl = URL.createObjectURL(blob);
 if (forceDownload) {
 const a = document.createElement('a');
 a.href = objectUrl;
 a.download = decodeURIComponent(encodedName) || 'attachment';
 document.body.appendChild(a);
 a.click();
 document.body.removeChild(a);
 } else {
 window.open(objectUrl, '_blank');
 }
 setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
 })
 .catch(err => {
 alert('Could not open file. ' + err.message);
 });
 };

 window.closeAttachmentPreview = function () {
 const modal = document.getElementById('attachPreviewModal');
 if (modal) modal.style.display = 'none';
 const img = document.getElementById('attachPreviewImg');
 if (img) img.src = '';
 };

 function buildTxDetailHTML(tx) {
 /* -- Header info -- */
 const statusClass = {
 'Completed' : 'hist-status-done',
 'Cancelled' : 'hist-status-cancel',
 'Waiting' : 'hist-status-wait',
 'Consulting': 'hist-status-active',
 }[tx.Status] || '';

 /* -- Vitals -- */
 const v = tx.vitals ?? tx; // support both nested (get_transaction) and flat (list_transactions)
 const hasVitals = v.BloodPressure || v.Temperature || v.PulseRate || v.Weight || v.Height;
 let vitalsHtml = '';
 if (hasVitals) {
 vitalsHtml = `
 <div class="txd-section">
 <div class="txd-section-title"><i class="fa-solid fa-heart-pulse"></i> Vital Signs</div>
 <div class="txd-grid">
 ${txdField('Height', v.Height ? v.Height + ' cm' : '-')}
 ${txdField('Weight', v.Weight ? v.Weight + ' kg' : '-')}
 ${txdField('Blood Pressure',v.BloodPressure || '-')}
 ${txdField('Temperature', v.Temperature ? v.Temperature + ' degC' : '-')}
 ${txdField('Pulse Rate', v.PulseRate ? v.PulseRate + ' bpm' : '-')}
 </div>
 </div>`;
 }

 /* -- Physical Exam -- */
 let peHtml = '';
 const pe = tx.physicalExam ?? tx.transaction?.physicalExam;
 if (pe) {
 const peRows = [
 ['Exam Date', pe.ExamDate],
 ['Ears', pe.Ears],
 ['Eyes / Pupil', pe.EyesPupil],
 ['Heart', pe.Heart],
 ['Nose', pe.Nose],
 ['Thorax', pe.Thorax],
 ['Abdomen', pe.Abdomen],
 ['Lungs', pe.Lungs],
 ['Skin', pe.Skin],
 ['Extremities', pe.Extremities],
 ['Deformities', pe.Deformities],
 ].filter(([,v]) => v).map(([l,v]) => txdField(l, v)).join('');

 const clearanceCls = {
 'Fit' : 'txd-clearance-fit',
 'Unfit' : 'txd-clearance-unfit',
 'Pending': 'txd-clearance-pending',
 }[pe.CardioClearance] || '';

 peHtml = `
 <div class="txd-section">
 <div class="txd-section-title"><i class="fa-solid fa-clipboard-list"></i> Physical Examination</div>
 <div class="txd-grid">${peRows}</div>
 ${pe.CardioClearance ? `<div class="txd-clearance ${clearanceCls}">
 <i class="fa-solid fa-shield-halved"></i>
 Cardio-Pulmonary Clearance: <strong>${pe.CardioClearance}</strong>
 </div>` : ''}
 ${pe.Remarks ? `<div class="txd-remarks"><span class="ro-label">Remarks</span><div>${pe.Remarks}</div></div>` : ''}
 </div>`;
 }

 /* -- Medicines -- */
 let medsHtml = '';
 const meds = tx.medicines ?? tx.transaction?.medicines ?? [];
 if (Array.isArray(meds) && meds.length) {
 const rows = meds.map(m => `
 <div class="txd-med-row">
 <div class="txd-med-icon"><i class="fa-solid fa-pills"></i></div>
 <div class="txd-med-info">
 <div class="txd-med-name">${m.MedicineName}${m.Dosage ? ' <span style="color:var(--gray-400);">'+m.Dosage+'</span>' : ''}</div>
 ${m.GenericName ? `<div class="txd-med-generic">${m.GenericName}</div>` : ''}
 ${m.Instructions ? `<div class="txd-med-sig"><i class="fa-solid fa-file-prescription"></i> ${m.Instructions}</div>` : ''}
 </div>
 <div class="txd-med-qty">
 <span class="txd-med-qty-num">${m.QuantityDispensed ?? '-'}</span>
 <span class="txd-med-qty-label">dispensed</span>
 </div>
 </div>
 `).join('');
 medsHtml = `
 <div class="txd-section">
 <div class="txd-section-title"><i class="fa-solid fa-pills"></i> Medicines Dispensed</div>
 <div class="txd-med-list">${rows}</div>
 </div>`;
 }

 /* -- Attachments -- */
 let attachHtml = '';
 const attachments = tx.attachments ?? tx.transaction?.attachments ?? [];
 if (Array.isArray(attachments) && attachments.length) {
 const rows = attachments.map(a => {
 const isPDF = (a.FileType ?? '').includes('pdf');
 const isImage = (a.FileType ?? '').startsWith('image/');
 const iconClass = isPDF ? 'fa-file-pdf' : 'fa-image';
 const iconStyle = isPDF ? 'background:#fee2e2;color:#dc2626;' : 'background:var(--blue-50);color:var(--blue-600);';
 const sizeKB = a.FileSizeBytes ? (a.FileSizeBytes / 1024).toFixed(0) + ' KB' : '';
 const aid = a.AttachmentID ?? '';

 // Serve endpoint - absolute URL from data-approot on <body>
 // (set by consultation.php so it never depends on JS path parsing)
 // Resolve to absolute URL NOW (at render time) so onclick strings are never relative
 const serveBase = new URL('/NUcare_Health_system/backend/ajax/consultation/serve_attachment.ajax.php?id=' + aid, document.baseURI).href;

 const openFn = isImage
 ? `openAttachmentPreview('${serveBase}','${encodeURIComponent(a.FileName ?? '')}')`
 : `fetchAndOpenAttachment('${serveBase}','${encodeURIComponent(a.FileName ?? '')}',false)`;
 const dlFn = `fetchAndOpenAttachment('${serveBase}&dl=1','${encodeURIComponent(a.FileName ?? '')}',true)`;

 return `
 <div class="ro-attach-item">
 <div class="ro-attach-icon" style="${iconStyle}">
 <i class="fa-solid ${iconClass}"></i>
 </div>
 <div class="ro-attach-info">
 <div class="ro-attach-name">${a.FileName ?? a.StoredName ?? 'File'}</div>
 <div class="ro-attach-meta">${a.AttachmentCategory ?? ''} ${sizeKB ? ' * ' + sizeKB : ''}</div>
 ${a.Notes ? `<div class="ro-attach-meta" style="font-style:italic;">${a.Notes}</div>` : ''}
 </div>
 <div class="ro-attach-actions">
 <button class="ro-attach-link" onclick="${openFn}" title="${isPDF ? 'View PDF' : isImage ? 'Preview image' : 'Open file'}">
 <i class="fa-solid ${isImage ? 'fa-magnifying-glass' : 'fa-arrow-up-right-from-square'}"></i>
 </button>
 <button class="ro-attach-link ro-attach-dl" onclick="${dlFn}" title="Download">
 <i class="fa-solid fa-download"></i>
 </button>
 </div>
 </div>`;
 }).join('');
 attachHtml = `
 <div class="txd-section">
 <div class="txd-section-title"><i class="fa-solid fa-paperclip"></i> Attachments</div>
 <div class="ro-attach-list">${rows}</div>
 </div>`;
 }

 const notesHtml = tx.Notes
 ? `<div class="txd-notes"><span class="ro-label">Clinical Notes / Assessment</span><div>${tx.Notes}</div></div>`
 : '';

 return `
 <div class="txd-readonly-banner">
 <i class="fa-solid fa-lock"></i>
 This record is <strong>read-only</strong>. Past consultations cannot be edited or deleted.
 </div>

 <div class="txd-section txd-section--overview">
 <div class="txd-grid">
 ${txdField('Transaction #', '#' + (tx.TransactionNumber ?? tx.ClinicTransactionID ?? '-'))}
 ${txdField('Visit Date', tx.VisitDate ?? tx.CreatedAt ?? '-')}
 ${txdField('Service Type', tx.ServiceType ?? '-')}
 ${txdField('Status', `<span class="hist-status ${statusClass}" style="font-size:.75rem;">${tx.Status ?? '-'}</span>`)}
 </div>
 ${tx.Complaint ? `<div class="txd-complaint"><span class="ro-label">Chief Complaint</span><div>${tx.Complaint}</div></div>` : ''}
 ${notesHtml}
 </div>

 ${vitalsHtml}
 ${peHtml}
 ${medsHtml}
 ${attachHtml}
 `;
 }

 function txdField(label, value) {
 return `<div class="ro-field"><span class="ro-label">${label}</span><span class="ro-value">${value}</span></div>`;
 }

 function unlockForm() {
 const area = document.getElementById('consultFormArea') || document.getElementById('consultationForm');
 const overlay = document.getElementById('disabledOverlay');
 if (area) { area.classList.remove('disabled'); area.style.pointerEvents = ''; area.style.opacity = ''; }
 if (overlay) { overlay.classList.remove('show'); overlay.style.display = 'none'; }
 }

 function lockForm() {
 const area = document.getElementById('consultFormArea') || document.getElementById('consultationForm');
 const overlay = document.getElementById('disabledOverlay');
 if (area) area.classList.add('disabled');
 if (overlay) { overlay.classList.add('show'); overlay.style.display = 'flex'; }
 }

 function showOverlay() {
 const overlay = document.getElementById('disabledOverlay');
 if (overlay) { overlay.classList.add('show'); overlay.style.display = 'flex'; }
 }

 /* ---
 3. TRANSACTIONAL FORM SUBMIT
 The server-side PHP must wrap everything in a DB
 transaction. This JS collects all visible section
 data and POSTs it in a single FormData request.
 --- */
 const form = document.getElementById('consultationForm');

 /**
 * Called by the Save button (which lives OUTSIDE the form to avoid
 * the pointer-events:none lockout from .consult-form-area.disabled).
 */
 function showRequirementsModal(items, intro = 'Complete these requirements before saving this consultation.') {
 const modal = document.getElementById('consultRequirementsModal');
 const list = document.getElementById('consultRequirementsList');
 const introEl = document.getElementById('consultRequirementsIntro');

 if (!modal || !list) {
 showToast(items.join(' '), 'error');
 return;
 }

 if (introEl) introEl.textContent = intro;
 list.innerHTML = items.map(item => `<li>${escapeHtml(String(item))}</li>`).join('');
 modal.style.display = 'block';
 document.body.classList.add('modal-open');
 }

 window.closeConsultRequirementsModal = function () {
 const modal = document.getElementById('consultRequirementsModal');
 if (modal) modal.style.display = 'none';
 document.body.classList.remove('modal-open');
 };

 function collectSaveRequirements() {
 const requirements = [];
 const consultationID = document.getElementById('consultationID')?.value;
 const serviceType = document.getElementById('consultService')?.value || '';
 const complaint = document.getElementById('consultConcern')?.value.trim() || '';

 if (!getCurrentSpid()) {
 requirements.push('Search and load a patient first.');
 }

 if (!consultationID) {
 requirements.push('Start an active consultation transaction by searching/selecting a patient.');
 }

 if (!serviceType) {
 requirements.push('Select a Service Type.');
 }

 if (serviceType === 'Other') {
 const otherService = document.getElementById('consultServiceOther')?.value.trim() || '';
 if (!otherService) requirements.push('Specify the Other service type.');
 }

 if (!['Medical Certificate', 'Physical Examination', 'Dental'].includes(serviceType) && !complaint) {
 requirements.push('Enter the Chief Complaint / Concern.');
 }

 if (serviceType === 'Medical Certificate') {
 const fileInput = document.getElementById('consultAttachmentFile');
 const category = document.getElementById('attachmentCategory')?.value || '';
 if (!fileInput || !fileInput.files?.length) {
 requirements.push('Attach a JPG, PNG, or PDF document for the Medical Certificate.');
 }
 if (category !== 'Medical Certificate') {
 requirements.push('Set Document Category to Medical Certificate so the certificate record will be stored.');
 }
 }

 if (serviceType === 'Physical Examination') {
 const examDate = document.getElementById('examDate')?.value;
 const clearance = document.getElementById('examCardioClearance')?.value;
 if (!examDate) requirements.push('Enter the Physical Examination date.');
 if (!clearance) requirements.push('Select the Medical Clearance result: Fit, Unfit, or Pending.');
 }

 const vitalsSection = document.getElementById('section-vitals');
 const vitalsVisible = vitalsSection && !vitalsSection.classList.contains('hidden');
 if (vitalsVisible) {
 const vErr = validateVitals();
 if (vErr) requirements.push(vErr);
 }

 document.querySelectorAll('.med-entry').forEach((row, idx) => {
 const name = row.querySelector('.med-name-group input[type="text"]')?.value.trim() || '';
 const inv = row.querySelector('input[name="med_inventory_id[]"]')?.value || '';
 const qty = Number(row.querySelector('input[name="med_qty[]"]')?.value || 0);

 if (name && !inv) {
 requirements.push(`Medicine row ${idx + 1}: select a medicine from the dropdown, not typed text only.`);
 }
 if (name && qty <= 0) {
 requirements.push(`Medicine row ${idx + 1}: enter a quantity greater than 0.`);
 }
 });

 return requirements;
 }

 window.submitConsultForm = function () {
 if (!form) { showToast('Form not found.', 'error'); return; }

 const requirements = collectSaveRequirements();
 if (requirements.length) {
 showRequirementsModal(requirements);
 return;
 }

 const serviceType = document.getElementById('consultService')?.value;

 const cErr = document.getElementById('consultConcernErr');
 const sErr = document.getElementById('consultServiceErr');
 if (cErr) cErr.textContent = '';
 if (sErr) sErr.textContent = '';

 const btn = document.getElementById('btnSaveConsult');
 if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...'; }


 const formData = new FormData(form);

 /* Append PE fields if Physical Exam is active */
 if (serviceType === 'Physical Examination') {
 appendPhysExamData(formData);
 }

 /* Mark which sections are active so server can skip irrelevant ones */
 formData.set('active_service_type', serviceType);

 
 fetch('/NUcare_Health_system/backend/ajax/consultation/save_consultation.ajax.php', {
 method: 'POST',
 body: formData
 })
 .then(r => r.json())
 .then(resp => {
 if (resp.ok) {
 showToast(
 `Saved! ${resp.service_type ?? serviceType}` +
 (resp.medicines_given ? ` * ${resp.medicines_given} medicine(s) dispensed` : ''),
 'success'
 );
 const spid = getCurrentSpid();
 if (spid) {
 loadHistory(spid);
 // Clear form - form.reset() wipes hidden inputs, but _currentSpid is safe
 autoClearForm();
 document.getElementById('consultationID').value = '';
 // Re-apply service type to reset sections
 const sel = document.getElementById('consultService');
 if (sel) applyServiceType(sel.value || 'General Consultation');
 // Start new transaction
 startTransaction(spid);
 }
 } else {
 // Show the real server-side error so staff can act on it
 const errMsg = resp.message || 'Save failed. Check your connection and try again.';
 showRequirementsModal([errMsg], 'The consultation could not be saved. Please resolve this requirement.');
 showToast(errMsg, 'error');
 console.error('Save failed:', resp);
 }
 })
 .catch(err => {
 console.error('Save error:', err);
 showRequirementsModal(['Server error while saving. Check the connection and try again.'], 'The consultation could not be saved.');
 showToast('Server error while saving.', 'error');
 })
 .finally(() => {
 if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Consultation'; }
 });
 };

 /* -- Clear form with confirmation --- */
 window.clearForm = function () {
 if (!confirm('Clear the form? Unsaved data will be lost.')) return;
 autoClearForm();
 };

 /* -- Auto-clear form without confirmation (used after save) --- */
 window.autoClearForm = function () {
 // _currentSpid module variable is the authoritative source - never wiped by form.reset()
 if (form) form.reset();

 // Restore the hidden input too, just for any code that still reads the DOM directly
 const pidEl = document.getElementById('consultPatientID');
 if (pidEl && _currentSpid) pidEl.value = _currentSpid;

 document.getElementById('medsList')?.replaceChildren();
 medRowIndex = 0;

 // Reset BMI
 const valEl = document.getElementById('bmiValue');
 const catEl = document.getElementById('bmiCategory');
 if (valEl) valEl.textContent = '-';
 if (catEl) { catEl.textContent = ''; catEl.className = 'bmi-display-cat'; }

 resetPhysExam();
 removeAttachment();

 // Re-apply service type (reset any leftover section visibility)
 const sel = document.getElementById('consultService');
 if (sel) applyServiceType(sel.value || 'General Consultation');
 };

 // Maps each exam field ID to its "normal" <option> value in the <select>.
 // Clicking the Normal badge auto-sets the dropdown to this value so the
 // finding is non-empty and actually written to physical_examinations.
 const EXAM_NORMAL_VALUES = {
 examEars: 'Normal',
 examEyesPupil: 'PERRLA',
 examHeart: 'Regular rate and rhythm',
 examNose: 'Normal',
 examThorax: 'Symmetric expansion',
 examAbdomen: 'Soft, non-tender',
 examLungs: 'Clear to auscultation',
 examSkin: 'Normal color and turgor',
 examExtremities: 'Full range of motion',
 examDeformities: 'None',
 };

 const EXAM_FIELDS = [
 { id: 'examEars', label: 'Ears' },
 { id: 'examEyesPupil', label: 'Eyes / Pupil' },
 { id: 'examHeart', label: 'Heart' },
 { id: 'examNose', label: 'Nose' },
 { id: 'examThorax', label: 'Thorax' },
 { id: 'examAbdomen', label: 'Abdomen' },
 { id: 'examLungs', label: 'Lungs' },
 { id: 'examSkin', label: 'Skin' },
 { id: 'examExtremities', label: 'Extremities' },
 { id: 'examDeformities', label: 'Deformities' }
 ];

 /**
 * Toggle Normal / Abnormal badge on a PE field.
 * Called by onclick="toggleExamBadge('examEars','normal')"
 *
 * BUG FIX: previously this only toggled CSS classes and never set
 * select.value, so appendPhysExamData always sent "" and PHP saved NULL.
 * Now clicking Normal auto-sets the select to the correct normal option
 * value so it is actually persisted to physical_examinations.
 * Clicking Abnormal resets the select to "" so staff must pick a finding.
 */
 window.toggleExamBadge = function (fieldId, status) {
 const normalBtn = document.querySelector(`[data-field="${fieldId}"][data-status="normal"]`);
 const abnormalBtn = document.querySelector(`[data-field="${fieldId}"][data-status="abnormal"]`);
 const inputEl = document.getElementById(fieldId);

 if (!normalBtn || !abnormalBtn) return;

 if (status === 'normal') {
 normalBtn.classList.add('active');
 abnormalBtn.classList.remove('active');
 if (inputEl) {
 inputEl.classList.remove('is-abnormal');
 // Set the select to the normal finding so the value is saved
 const normalVal = EXAM_NORMAL_VALUES[fieldId];
 if (normalVal) inputEl.value = normalVal;
 }
 } else {
 abnormalBtn.classList.add('active');
 normalBtn.classList.remove('active');
 if (inputEl) {
 inputEl.classList.add('is-abnormal');
 inputEl.value = ''; // force staff to pick a specific abnormal finding
 }
 }
 };

 /**
 * Select cardio-pulmonary clearance result.
 * Called by onclick="selectClearance('Fit')"
 */
 window.selectClearance = function (value) {
 document.querySelectorAll('.clearance-option').forEach(opt => {
 opt.classList.remove('selected-fit', 'selected-unfit', 'selected-pending');
 });
 const cls = { 'Fit': 'selected-fit', 'Unfit': 'selected-unfit', 'Pending': 'selected-pending' }[value];
 const target = document.querySelector(`.clearance-option[data-value="${value}"]`);
 if (target && cls) target.classList.add(cls);

 const hiddenInput = document.getElementById('examCardioClearance');
 if (hiddenInput) hiddenInput.value = value;
 };

 /**
 * Collect PE dropdown values + Normal/Abnormal status into FormData.
 * Inserts into physical_examinations on the server side.
 */
 function appendPhysExamData(formData) {
 // Body system exam fields (dropdowns + Normal/Abnormal status)
 EXAM_FIELDS.forEach(f => {
 const el = document.getElementById(f.id);
 if (el) formData.set(f.id, el.value || '');

 const abnormalBtn = document.querySelector(`[data-field="${f.id}"][data-status="abnormal"]`);
 if (abnormalBtn) {
 formData.set(f.id + '_status', abnormalBtn.classList.contains('active') ? 'Abnormal' : 'Normal');
 }
 });

 // PE-specific meta fields
 const remarksEl = document.getElementById('examRemarks');
 const clearanceEl = document.getElementById('examCardioClearance');
 const dateEl = document.getElementById('examDate');

 if (remarksEl) formData.set('exam_remarks', remarksEl.value || '');
 if (clearanceEl) formData.set('exam_cardio_clearance', clearanceEl.value || '');
 if (dateEl) formData.set('exam_date', dateEl.value || '');

 // Vitals - the PE form has its own inputs (peHeight, peBP etc, named pe_*)
 // that the user fills in when Physical Examination is active.
 // Remap those to the server-side field names (blood_pressure, height, etc).
 // Fall back to the standard vitals inputs if the PE inputs are empty
 // (shouldn't happen, but defensive).
 const peVitalsMap = [
 { postKey: 'blood_pressure', peId: 'peBP', stdId: 'consultBP' },
 { postKey: 'temperature', peId: 'peTemp', stdId: 'consultTemp' },
 { postKey: 'pulse_rate', peId: 'pePulse', stdId: 'consultPulse' },
 { postKey: 'weight', peId: 'peWeight', stdId: 'consultWeight' },
 { postKey: 'height', peId: 'peHeight', stdId: 'consultHeight' },
 ];
 peVitalsMap.forEach(({ postKey, peId, stdId }) => {
 const peEl = document.getElementById(peId);
 const stdEl = document.getElementById(stdId);
 const val = (peEl && peEl.value) ? peEl.value : (stdEl ? stdEl.value : '');
 formData.set(postKey, val || '');
 });
 }

 /** Validate required Physical Exam fields before submit */
 function validatePhysExam() {
 const examDate = document.getElementById('examDate')?.value;
 if (!examDate) return 'Examination date is required.';
 const clearance = document.getElementById('examCardioClearance')?.value;
 if (!clearance) return 'Please select a medical clearance result (Fit / Unfit / Pending).';
 return null;
 }

 /** Reset all physical exam fields and badges */
 function resetPhysExam() {
 EXAM_FIELDS.forEach(f => {
 const el = document.getElementById(f.id);
 if (el) { el.value = ''; el.classList.remove('is-abnormal'); }

 const normalBtn = document.querySelector(`[data-field="${f.id}"][data-status="normal"]`);
 const abnormalBtn = document.querySelector(`[data-field="${f.id}"][data-status="abnormal"]`);
 if (normalBtn) normalBtn.classList.remove('active');
 if (abnormalBtn) abnormalBtn.classList.remove('active');
 });
 document.querySelectorAll('.clearance-option').forEach(opt => {
 opt.classList.remove('selected-fit', 'selected-unfit', 'selected-pending');
 });
 const hiddenClearance = document.getElementById('examCardioClearance');
 if (hiddenClearance) hiddenClearance.value = '';

 const remarksEl = document.getElementById('examRemarks');
 if (remarksEl) remarksEl.value = '';
 }

 /* ---
 BMI AUTO-CALCULATION
 --- */
 window.calcBMI = function () {
 const wEl = document.getElementById('consultWeight');
 const hEl = document.getElementById('consultHeight');

 if (!wEl || !hEl) return;

 const w = parseFloat(wEl.value);
 const hm = parseFloat(hEl.value) / 100;

 const valEl = document.getElementById('bmiValue');
 const catEl = document.getElementById('bmiCategory');

 if (!w || !hm || hm <= 0) {
 if (valEl) valEl.textContent = '-';
 if (catEl) { catEl.textContent = ''; catEl.className = 'bmi-display-cat'; }
 return;
 }

 const bmi = w / (hm * hm);
 if (valEl) valEl.textContent = bmi.toFixed(1);

 let label = '', cls = '';
 if (bmi < 18.5) { label = 'Underweight'; cls = 'bmi-low'; }
 else if (bmi < 25) { label = 'Normal'; cls = 'bmi-ok'; }
 else if (bmi < 30) { label = 'Overweight'; cls = 'bmi-warn'; }
 else { label = 'Obese'; cls = 'bmi-bad'; }

 if (catEl) { catEl.textContent = label; catEl.className = 'bmi-display-cat ' + cls; }
 };

 // Wire up height/weight inputs to BMI calc
 ['consultWeight', 'consultHeight'].forEach(id => {
 const el = document.getElementById(id);
 if (el) el.addEventListener('input', window.calcBMI);
 });

 /* -- Validate numeric vitals --- */
 function validateVitals() {
 const checks = [
 { id: 'consultWeight', label: 'Weight', min: 1, max: 300 },
 { id: 'consultHeight', label: 'Height', min: 50, max: 250 },
 { id: 'consultTemp', label: 'Temperature', min: 30, max: 45 },
 { id: 'consultPulse', label: 'Pulse Rate', min: 20, max: 300 },

 ];
 for (const f of checks) {
 const el = document.getElementById(f.id);
 if (!el || !el.value) continue;
 const v = parseFloat(el.value);
 if (isNaN(v) || v < f.min || v > f.max) {
 return `${f.label} value (${el.value}) is out of range (${f.min}-${f.max}).`;
 }
 }
 return null;
 }

 /* ---
 2. MEDICINE DISPENSING (3NF)
 Tables: medicines -> medicine_inventory -> medicine_dispensing
 Logic:
 - Search queries medicine_inventory (FIFO, in-stock only)
 - On select: show available stock, enforce max qty
 - On save: server deducts from inventory, prevents negatives
 --- */
 let medRowIndex = 0;
 let medDebounce = {};

 window.addMedRow = function () {
 const container = document.getElementById('medsList');
 if (!container) return;

 const idx = medRowIndex++;
 const div = document.createElement('div');
 div.className = 'med-entry';
 div.id = 'medRow_' + idx;
 div.innerHTML = `
 <div class="form-group med-name-group">
 <label>Medicine / Drug</label>
 <input type="text"
 id="medNameInput_${idx}"
 placeholder="Search medicine name or generic..."
 autocomplete="off"
 oninput="onMedInput(this, ${idx})"
 data-row="${idx}">
 <input type="hidden" name="med_inventory_id[]" id="medInvID_${idx}" value="">
 <ul class="med-autocomplete" id="medAC_${idx}"></ul>
 <div id="medStock_${idx}" style="margin-top:4px;"></div>
 </div>
 <div class="form-group">
 <label>Qty to Dispense</label>
 <input type="number" name="med_qty[]" id="medQty_${idx}" min="1" value="1" style="max-width:110px;">
 </div>
 <div class="form-group" style="grid-column:1/-1;">
 <label>Instructions / Sig</label>
 <input type="text" name="med_instructions[]"
 placeholder="e.g. Take 1 tablet after meals, TID for 5 days">
 </div>
 <div style="display:flex; justify-content:flex-end; grid-column:1/-1;">
 <button type="button" class="remove-med" onclick="removeMedRow(${idx})" title="Remove medicine">
 <i class="fa-solid fa-xmark"></i>
 </button>
 </div>
 `;
 container.appendChild(div);
 };

 window.removeMedRow = function (idx) {
 const row = document.getElementById('medRow_' + idx);
 if (row) row.remove();
 };

 /**
 * Medicine search autocomplete.
 * Queries: search_medicine.ajax.php
 * Returns: MedicineName, GenericName, MedicineType, Dosage, InventoryID,
 * AvailableQty (FIFO earliest non-expired batch), ExpiryDate
 */
 window.onMedInput = function (inputEl, idx) {
 clearTimeout(medDebounce[idx]);
 const q = inputEl.value.trim();
 const ac = document.getElementById('medAC_' + idx);
 const inv = document.getElementById('medInvID_' + idx);

 if (inv) inv.value = '';
 if (!ac) return;
 if (q.length < 2) { ac.innerHTML = ''; ac.classList.remove('open'); return; }

 medDebounce[idx] = setTimeout(() => {
 fetch('/NUcare_Health_system/backend/ajax/consultation/search_medicine.ajax.php?q=' + encodeURIComponent(q))
 .then(r => r.json())
 .then(data => {
 ac.innerHTML = '';
 if (!data.ok || !data.results?.length) { ac.classList.remove('open'); return; }

 data.results.forEach(m => {
 const li = document.createElement('li');
 li.className = 'ac-item';
 li.setAttribute('tabindex', '0');

 const qty = parseInt(m.AvailableQty ?? 0);
 const isLow = qty > 0 && qty <= (m.ReorderLevel ?? 10);
 const isOut = qty <= 0;
 const stockCls = isOut ? 'ac-stock-out' : isLow ? 'ac-stock-low' : 'ac-stock-ok';
 const stockLabel = isOut ? 'Out of stock' : `${qty} in stock`;

 li.innerHTML = `
 <span class="ac-name">${m.MedicineName}${m.Dosage ? ' - ' + m.Dosage : ''}</span>
 <span class="ac-meta">${m.GenericName ?? ''} * ${m.MedicineType ?? ''}</span>
 <span class="ac-stock ${stockCls}">${stockLabel}</span>
 `;

 if (isOut) li.style.opacity = '.5';

 li.addEventListener('mousedown', (e) => { e.preventDefault(); // prevent blur closing dropdown before value is set
 if (isOut) { showToast('This medicine is currently out of stock.', 'error'); return; }

 inputEl.value = m.MedicineName + (m.Dosage ? ' ' + m.Dosage : '');
 if (inv) inv.value = m.InventoryID;

 // Show stock badge
 const stockEl = document.getElementById('medStock_' + idx);
 if (stockEl) {
 const badgeCls = isLow ? 'stock-low' : 'stock-ok';
 stockEl.innerHTML = `
 <span class="stock-badge ${badgeCls}">
 <i class="fa-solid fa-box"></i> ${qty} in stock
 </span>
 `;
 }

 // Enforce max qty = available stock (prevent over-dispensing)
 const qtyEl = document.getElementById('medQty_' + idx);
 if (qtyEl) {
 qtyEl.max = qty;
 if (parseInt(qtyEl.value) > qty) qtyEl.value = qty;
 }

 ac.innerHTML = '';
 ac.classList.remove('open');
 });

 li.addEventListener('keydown', e => { if (e.key === 'Enter') li.click(); });
 ac.appendChild(li);
 });

 ac.classList.add('open');
 })
 .catch(() => ac.classList.remove('open'));
 }, 260);
 };

 // Close medicine autocomplete on outside click
 document.addEventListener('click', e => {
 document.querySelectorAll('.med-autocomplete.open').forEach(ac => {
 if (!ac.contains(e.target)) {
 ac.innerHTML = '';
 ac.classList.remove('open');
 }
 });
 });

 /* ---
 ATTACHMENT UPLOAD / PREVIEW
 --- */
 window.handleAttachmentSelect = function (input) {
 const file = input.files?.[0];
 const errEl = document.getElementById('pdfErrMsg');
 const errText = document.getElementById('pdfErrText');
 const preview = document.getElementById('pdfFilePreview');
 const zone = document.getElementById('pdfUploadZone');
 const nameEl = document.getElementById('pdfFileName');
 const sizeEl = document.getElementById('pdfFileSize');

 if (errEl) errEl.style.display = 'none';
 if (!file) return;

 const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
 if (!allowed.includes(file.type)) {
 if (errText) errText.textContent = 'Invalid file type. Allowed: JPG, PNG, PDF.';
 if (errEl) errEl.style.display = 'flex';
 input.value = '';
 return;
 }
 if (file.size > 50 * 1024 * 1024) {
 if (errText) errText.textContent = 'File too large. Maximum 50 MB.';
 if (errEl) errEl.style.display = 'flex';
 input.value = '';
 return;
 }

 if (nameEl) nameEl.textContent = file.name;
 if (sizeEl) sizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
 if (zone) zone.style.display = 'none';
 if (preview) preview.style.display = 'flex';

 const metaRow = document.getElementById('attachMetaRow');
 if (metaRow) metaRow.style.display = '';
 };

 window.removeAttachment = function () {
 const input = document.getElementById('consultAttachmentFile');
 const preview = document.getElementById('pdfFilePreview');
 const zone = document.getElementById('pdfUploadZone');
 const metaRow = document.getElementById('attachMetaRow');
 if (input) input.value = '';
 if (preview) preview.style.display = 'none';
 if (zone) zone.style.display = '';
 if (metaRow) metaRow.style.display = 'none';
 // Also reset MC fields
 const mcFields = document.getElementById('mcExtraFields');
 if (mcFields) mcFields.style.display = 'none';
 const catSel = document.getElementById('attachmentCategory');
 if (catSel) catSel.value = 'Other';
 };

 // Show/hide Medical Certificate extra fields based on selected category
 window.onAttachCategoryChange = function (val) {
 const mcFields = document.getElementById('mcExtraFields');
 if (!mcFields) return;
 mcFields.style.display = (val === 'Medical Certificate') ? '' : 'none';
 };

 // Drag-and-drop support for attachment zone
 (function initDragDrop() {
 const zone = document.getElementById('pdfUploadZone');
 if (!zone) return;

 zone.addEventListener('dragover', e => {
 e.preventDefault();
 zone.classList.add('dragover');
 });
 zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
 zone.addEventListener('drop', e => {
 e.preventDefault();
 zone.classList.remove('dragover');
 const dt = e.dataTransfer;
 const input = document.getElementById('consultAttachmentFile');
 if (!dt || !input) return;
 // Assign dropped file to the file input and trigger handler
 try {
 const dataTransfer = new DataTransfer();
 if (dt.files[0]) dataTransfer.items.add(dt.files[0]);
 input.files = dataTransfer.files;
 window.handleAttachmentSelect(input);
 } catch (err) {
 console.warn('DataTransfer assignment not supported:', err);
 }
 });
 })();

 let historyData = [];
 let historyFilterTimer = null;

 function loadHistory(spid) {
 const container = document.getElementById('historyTimeline');
 const tableFallback = document.getElementById('consultHistoryTbody');

 if (container) {
 container.innerHTML = `<div style="padding:20px 0;text-align:center;color:var(--gray-400);">
 <i class="fa-solid fa-spinner fa-spin"></i> Loading history...
 </div>`;
 }

 fetch('/NUcare_Health_system/backend/ajax/consultation/list_transactions.ajax.php?school_person_id=' + encodeURIComponent(spid))
 .then(r => r.json())
 .then(resp => {
 if (!resp.ok || !Array.isArray(resp.transactions) || resp.transactions.length === 0) {
 if (container) container.innerHTML = `<div style="padding:24px 0;text-align:center;color:var(--gray-400);font-weight:700;font-size:.85rem;">
 <i class="fa-solid fa-clock-rotate-left" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
 No consultation history yet.
 </div>`;
 if (tableFallback) tableFallback.innerHTML = '<tr><td colspan="6" class="muted">No consultation history yet.</td></tr>';
 historyData = [];
 _histModalData = []; // clear stale data from previous patient
 return;
 }

 historyData = resp.transactions;
 _histModalData = resp.transactions; // also cache for the history modal
 renderHistoryTimeline(historyData);
 if (tableFallback) renderHistoryTable(historyData, tableFallback);
 })
 .catch(err => {
 console.error('Load history error:', err);
 if (container) container.innerHTML = `<div style="padding:20px 0;text-align:center;color:var(--danger);">
 Failed to load history.
 </div>`;
 });
 }

 function renderHistoryTimeline(transactions) {
 const container = document.getElementById('historyTimeline');
 if (!container) return;

 container.innerHTML = '';

 if (!transactions.length) {
 container.innerHTML = `<div style="padding:16px 0;text-align:center;color:var(--gray-400);font-size:.84rem;font-weight:700;">
 No records match your filter.
 </div>`;
 return;
 }

 transactions.forEach(tx => {
 const statusClass = {
 'Completed' : 'hist-status-done',
 'Cancelled' : 'hist-status-cancel',
 'Waiting' : 'hist-status-wait',
 'Consulting': 'hist-status-active',
 }[tx.Status] || '';

 const medBadges = Array.isArray(tx.medicines) && tx.medicines.length
 ? tx.medicines.map(m =>
 `<span class="hist-med">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>`
 ).join('')
 : '<span class="muted-sm">No medicines</span>';

 const item = document.createElement('div');
 item.className = 'history-item';
 item.setAttribute('data-tx-id', tx.ClinicTransactionID || tx.TransactionNumber);
 item.innerHTML = `
 <div class="history-dot"></div>
 <div class="history-item-card">
 <div class="history-item-top">
 <span class="hist-tx-num">#${tx.TransactionNumber ?? tx.ClinicTransactionID ?? ''}</span>
 <span class="hist-date">${tx.VisitDate ?? tx.CreatedAt ?? ''}</span>
 <span class="hist-status ${statusClass}">${tx.Status ?? ''}</span>
 </div>
 <div class="hist-service-tag">
 <i class="fa-solid fa-stethoscope"></i> ${tx.ServiceType ?? 'General'}
 </div>
 <div class="hist-complaint">${tx.Complaint ?? '-'}</div>
 <div class="hist-meds">${medBadges}</div>
 </div>
 `;

 // Click -> open READ-ONLY detail panel (no editing allowed)
 item.addEventListener('click', () => {
 document.querySelectorAll('.history-item.active').forEach(i => i.classList.remove('active'));
 item.classList.add('active');
 openHistoryDetail(tx);
 });

 container.appendChild(item);
 });
 }

 /** History detail panel - READ ONLY. Cannot be edited, overwritten, or deleted. */
 function openHistoryDetail(tx) {
 const panel = document.getElementById('historyDetailPanel');
 if (!panel) return;
 panel.classList.add('visible');

 const titleEl = document.getElementById('historyDetailTitle');
 if (titleEl) titleEl.textContent = `Transaction #${tx.TransactionNumber ?? tx.ClinicTransactionID} - ${tx.ServiceType ?? 'Consultation'}`;

 const bodyEl = document.getElementById('historyDetailBody');
 if (!bodyEl) return;

 // -- Vitals --
 let vitalsHtml = '';
 if (tx.Height || tx.Weight || tx.BloodPressure || tx.Temperature || tx.PulseRate) {
 vitalsHtml = `
 <div>
 <div class="card-section-label" style="margin-bottom:10px;">
 <i class="fa-solid fa-heart-pulse"></i> Vital Signs
 </div>
 <div class="ro-grid">
 ${roField('Height', tx.Height ? tx.Height + ' cm' : '-')}
 ${roField('Weight', tx.Weight ? tx.Weight + ' kg' : '-')}
 ${roField('Blood Pressure', tx.BloodPressure || '-')}
 ${roField('Temperature', tx.Temperature ? tx.Temperature + ' degC' : '-')}
 ${roField('Pulse Rate', tx.PulseRate ? tx.PulseRate + ' bpm' : '-')}
 </div>
 </div>
 `;
 }

 // -- Physical Exam --
 let peHtml = '';
 if (tx.physicalExam) {
 const pe = tx.physicalExam;
 const peStatus = (fieldVal, statusVal) => {
 if (!statusVal) return fieldVal || '-';
 const cls = statusVal === 'Abnormal'
 ? 'style="color:var(--danger);font-weight:700;"'
 : 'style="color:var(--success);font-weight:700;"';
 return `<span ${cls}>${fieldVal || '-'}</span>`;
 };
 peHtml = `
 <div>
 <div class="card-section-label" style="margin-bottom:10px;">
 <i class="fa-solid fa-clipboard-list"></i> Physical Examination
 </div>
 <div class="ro-grid">
 ${roField('Exam Date', pe.ExamDate || '-')}
 ${roField('Ears', pe.Ears || '-')}
 ${roField('Eyes / Pupil', pe.EyesPupil || '-')}
 ${roField('Heart', pe.Heart || '-')}
 ${roField('Nose', pe.Nose || '-')}
 ${roField('Thorax', pe.Thorax || '-')}
 ${roField('Abdomen', pe.Abdomen || '-')}
 ${roField('Lungs', pe.Lungs || '-')}
 ${roField('Skin', pe.Skin || '-')}
 ${roField('Extremities', pe.Extremities || '-')}
 ${roField('Deformities', pe.Deformities || '-')}
 ${roField('Cardio Clearance', pe.CardioClearance || '-')}
 </div>
 ${pe.Remarks ? `<div style="margin-top:12px;"><div class="ro-label">Remarks</div><div style="font-size:.87rem;font-weight:600;color:var(--text);margin-top:4px;line-height:1.5;">${pe.Remarks}</div></div>` : ''}
 </div>
 `;
 }

 // -- Medicines Dispensed --
 let medsHtml = '';
 if (Array.isArray(tx.medicines) && tx.medicines.length) {
 const rows = tx.medicines.map(m => `
 <div class="ro-field" style="background:var(--gray-50);border:1.5px solid var(--gray-200);border-radius:var(--r-md);padding:10px 14px;gap:4px;">
 <span class="ro-label">Medicine</span>
 <span class="ro-value">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>
 <span style="font-size:.75rem;color:var(--gray-500);">${m.Instructions ?? ''}</span>
 <span style="font-size:.7rem;color:var(--gray-400);">Qty: ${m.QuantityDispensed ?? '-'}</span>
 </div>
 `).join('');
 medsHtml = `
 <div>
 <div class="card-section-label" style="margin-bottom:10px;">
 <i class="fa-solid fa-pills"></i> Medicines Dispensed
 </div>
 <div class="ro-grid">${rows}</div>
 </div>
 `;
 }

 const notesHtml = tx.Notes
 ? `<div style="margin:8px 0 12px;"><span class="ro-label">Clinical Notes</span>
 <div style="font-size:.87rem;font-weight:600;color:var(--text);margin-top:4px;line-height:1.55;">${tx.Notes}</div></div>`
 : '';

 bodyEl.innerHTML = `
 <div class="readonly-notice">
 <i class="fa-solid fa-lock"></i>
 This record is <strong>read-only</strong>. Past consultations cannot be edited or deleted.
 </div>
 <div class="ro-grid" style="margin:12px 0;">
 ${roField('Date', tx.VisitDate ?? tx.CreatedAt ?? '-')}
 ${roField('Service Type', tx.ServiceType ?? '-')}
 ${roField('Status', tx.Status ?? '-')}
 ${roField('Chief Complaint', tx.Complaint ?? '-')}
 </div>
 ${notesHtml}
 ${vitalsHtml}
 ${peHtml}
 ${medsHtml}
 `;
 }

 function roField(label, value) {
 return `<div class="ro-field"><span class="ro-label">${label}</span><span class="ro-value">${value}</span></div>`;
 }

 window.closeHistoryDetail = function () {
 const panel = document.getElementById('historyDetailPanel');
 if (panel) panel.classList.remove('visible');
 document.querySelectorAll('.history-item.active').forEach(i => i.classList.remove('active'));
 };

 /** History search filter */
 window.onHistorySearch = function (val) {
 clearTimeout(historyFilterTimer);
 historyFilterTimer = setTimeout(() => {
 const q = val.trim().toLowerCase();
 if (!q) { renderHistoryTimeline(historyData); return; }
 const filtered = historyData.filter(tx =>
 (tx.Complaint ?? '').toLowerCase().includes(q) ||
 (tx.ServiceType ?? '').toLowerCase().includes(q) ||
 String(tx.TransactionNumber ?? '').includes(q)
 );
 renderHistoryTimeline(filtered);
 }, 200);
 };

 /** History date range filter */
 window.filterHistoryByDate = function () {
 const from = document.getElementById('historyDateFrom')?.value;
 const to = document.getElementById('historyDateTo')?.value;

 if (!from && !to) { renderHistoryTimeline(historyData); return; }

 const filtered = historyData.filter(tx => {
 const d = new Date(tx.VisitDate ?? tx.CreatedAt ?? '');
 if (isNaN(d)) return true;
 const dStr = d.toISOString().slice(0, 10);
 if (from && dStr < from) return false;
 if (to && dStr > to) return false;
 return true;
 });
 renderHistoryTimeline(filtered);
 };

 window.printHistory = function () { window.print(); };

 /**
 * Export history detail as PDF.
 * Future-ready: sends the current open transaction to a PDF-generation endpoint.
 * For now, falls back to print dialog.
 */
 window.exportHistoryPDF = function () {
 const titleEl = document.getElementById('historyDetailTitle');
 const bodyEl = document.getElementById('historyDetailBody');
 if (!bodyEl) { window.print(); return; }

 window.print();
 };

 /** Legacy table fallback renderer (for pages still using old table HTML) */
 function renderHistoryTable(transactions, tbody) {
 tbody.innerHTML = '';
 transactions.forEach(tx => {
 const statusClass = {
 'Completed' : 'hist-status-done',
 'Cancelled' : 'hist-status-cancel',
 'Waiting' : 'hist-status-wait',
 'Consulting': 'hist-status-active',
 }[tx.Status] || '';

 const medBadges = Array.isArray(tx.medicines) && tx.medicines.length
 ? tx.medicines.map(m =>
 `<span class="hist-med">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>`
 ).join('')
 : '<span class="muted-sm">None</span>';

 const tr = document.createElement('tr');
 tr.style.cursor = 'pointer';
 tr.innerHTML = `
 <td>#${tx.TransactionNumber ?? ''}</td>
 <td>${tx.VisitDate ?? tx.CreatedAt ?? ''}</td>
 <td>${tx.ServiceType ?? '-'}</td>
 <td class="hist-complaint" title="${tx.Complaint ?? ''}">${tx.Complaint ?? '-'}</td>
 <td><span class="hist-status ${statusClass}">${tx.Status ?? ''}</span></td>
 <td><div class="hist-meds">${medBadges}</div></td>
 `;
 tr.addEventListener('click', () => openHistoryDetail(tx));
 tbody.appendChild(tr);
 });
 }

 /* -- Toast --- */
 function showToast(msg, type) {
 const toast = document.getElementById('consultToast');
 if (!toast) return;
 toast.textContent = msg;
 toast.className = 'consult-toast ' + (type === 'error' ? 'error-toast' : type || '');
 // Errors stay visible longer so staff can read them
 const duration = (type === 'error') ? 6000 : 3500;
 setTimeout(() => toast.className = 'consult-toast', duration);
 }

 window.showConsultToast = showToast;

})();

