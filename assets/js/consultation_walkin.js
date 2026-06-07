/* ---
 consultation_walkin.js -> PLACE IN: assets/js/
 Load AFTER consultation.js in consultation.php:
 <script src="/NUcare_Health_system/assets/js/consultation.js?v=..."></script>
 <script src="/NUcare_Health_system/assets/js/consultation_walkin.js?v=2"></script>

 Spec MODULE 1 (CRITICAL) front-end:
 * When search returns "no patient found", surfaces
 "Patient not found. You may register a new record."
 * Opens a modal with all 6 PersonTypes.
 * On success, re-runs search by new SchoolPersonID -> existing
 loadPatient() -> startTransaction() -> consultation form.

 100% additive - does NOT modify consultation.js.
--- */
(function () {
 'use strict';

 var ENDPOINT = '/NUcare_Health_system/backend/ajax/register_walkin.ajax.php';

 document.addEventListener('DOMContentLoaded', function () {
 var feedback = document.getElementById('searchFeedback');
 var searchInput = document.getElementById('consultSearchInput');
 if (!feedback || !searchInput) return;

 injectModal();
 injectRegisterButton(feedback);

 var observer = new MutationObserver(function () {
 toggleRegisterButton(shouldOfferRegistration());
 });
 observer.observe(feedback, { childList: true, characterData: true, subtree: true, attributes: true });
 });

 function exceptionTypeFromSearch(value) {
 var normalized = String(value || '').trim().toLowerCase();
 if (normalized === 'guard') return 'Guard';
 if (normalized === 'visitor' || normalized === 'visitors' || normalized === 'vistors') return 'Visitor';
 if (normalized === 'romac' || normalized === 'janitor') return 'ROMAC';
 return '';
 }

 function shouldOfferRegistration() {
 var feedback = document.getElementById('searchFeedback');
 var searchInput = document.getElementById('consultSearchInput');
 if (!feedback || !searchInput) return false;

 return exceptionTypeFromSearch(searchInput.value) !== '';
 }

 /* -- "Register new patient" button --- */
 function injectRegisterButton(feedback) {
 if (document.getElementById('walkinRegisterBtn')) return;

 var wrap = document.createElement('div');
 wrap.id = 'walkinRegisterWrap';
 wrap.style.cssText = 'margin-top:10px;display:none;';
 wrap.innerHTML =
 '<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 14px;' +
 'display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
 '<span style="color:#9a3412;font-weight:600;">' +
 '<i class="fa-solid fa-user-plus"></i> Register another Guard, Visitor, or ROMAC record.' +
 '</span>' +
 '<button type="button" id="walkinRegisterBtn" ' +
 'style="border:0;border-radius:8px;padding:9px 14px;font-weight:700;cursor:pointer;' +
 'background:#ea580c;color:#fff;display:inline-flex;align-items:center;gap:8px;">' +
 '<i class="fa-solid fa-plus"></i> Register Another' +
 '</button>' +
 '</div>';

 feedback.parentNode.insertBefore(wrap, feedback.nextSibling);
 document.getElementById('walkinRegisterBtn').addEventListener('click', openModal);
 }

 function toggleRegisterButton(show) {
 var wrap = document.getElementById('walkinRegisterWrap');
 if (wrap) wrap.style.display = show ? 'block' : 'none';
 }

 /* -- Modal --- */
 function injectModal() {
 if (document.getElementById('walkinModal')) return;

 var el = document.createElement('div');
 el.id = 'walkinModal';
 el.setAttribute('role', 'dialog');
 el.setAttribute('aria-modal', 'true');
 el.style.cssText =
 'position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;' +
 'align-items:center;justify-content:center;padding:20px;z-index:2000;';

 var personTypes = ['Guard', 'Visitor', 'ROMAC'];

 el.innerHTML =
 '<div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:92vh;overflow:auto;box-shadow:0 30px 80px rgba(0,0,0,.35);">' +
 '<div style="background:linear-gradient(135deg,#0f3c76,#0d9488);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">' +
 '<div style="color:#fff;font-weight:800;font-size:1.05rem;"><i class="fa-solid fa-user-plus"></i> Register New Patient</div>' +
 '<button type="button" id="walkinClose" aria-label="Close" style="background:rgba(255,255,255,.18);border:0;color:#fff;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;">&times;</button>' +
 '</div>' +
 '<form id="walkinForm" autocomplete="off" style="padding:22px;">' +
 '<p style="margin:0 0 16px;color:#64748b;font-size:.9rem;">School ID is optional - leave it blank for guards, visitors, or walk-ins.</p>' +
 '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">' +
 field('First Name', 'wk_first_name', 'text', true) +
 field('Last Name', 'wk_last_name', 'text', true) +
 field('Middle Name','wk_middle_name','text', false) +
 selectField('Sex', 'wk_sex', ['Male', 'Female'], true) +
 selectField('Person Type', 'wk_person_type', personTypes, true) +
 field('Email (optional)', 'wk_email', 'email', false) +
 '<input id="wk_school_id" name="wk_school_id" type="hidden" value="">' +
 '</div>' +
 '<div id="walkinFormError" style="display:none;margin-top:14px;color:#dc2626;font-weight:600;font-size:.85rem;"></div>' +
 '<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">' +
 '<button type="button" id="walkinCancel" style="border:1px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:11px 16px;font-weight:700;cursor:pointer;">Cancel</button>' +
 '<button type="submit" id="walkinSubmit" style="border:0;background:#0f3c76;color:#fff;border-radius:10px;padding:11px 18px;font-weight:800;cursor:pointer;">' +
 '<i class="fa-solid fa-floppy-disk"></i> Register &amp; Start Consultation' +
 '</button>' +
 '</div>' +
 '</form>' +
 '</div>';

 document.body.appendChild(el);

 document.getElementById('walkinClose').addEventListener('click', closeModal);
 document.getElementById('walkinCancel').addEventListener('click', closeModal);
 el.addEventListener('click', function (e) { if (e.target === el) closeModal(); });
 document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
 document.getElementById('walkinForm').addEventListener('submit', submitForm);
 }

 function field(label, id, type, required, full) {
 return '<div style="display:flex;flex-direction:column;gap:6px;' + (full ? 'grid-column:1 / -1;' : '') + '">' +
 '<label for="' + id + '" style="font-size:.74rem;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.04em;">' +
 label + (required ? ' <span style="color:#dc2626;">*</span>' : '') + '</label>' +
 '<input id="' + id + '" name="' + id + '" type="' + type + '" ' +
 'style="border:1.5px solid #e2e8f0;border-radius:9px;padding:10px 12px;font-weight:600;">' +
 '</div>';
 }

 function selectField(label, id, options, required) {
 var opts = '<option value="">Select...</option>' + options.map(function (o) {
 return '<option value="' + o + '">' + o + '</option>';
 }).join('');
 return '<div style="display:flex;flex-direction:column;gap:6px;">' +
 '<label for="' + id + '" style="font-size:.74rem;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.04em;">' +
 label + (required ? ' <span style="color:#dc2626;">*</span>' : '') + '</label>' +
 '<select id="' + id + '" name="' + id + '" style="border:1.5px solid #e2e8f0;border-radius:9px;padding:10px 12px;font-weight:600;background:#fff;">' +
 opts + '</select>' +
 '</div>';
 }

 /* -- open / close --- */
 function openModal(prefillType) {
 var modal = document.getElementById('walkinModal');
 if (!modal) return;
 var typed = (document.getElementById('consultSearchInput') || {}).value || '';
 var exceptionType = prefillType || exceptionTypeFromSearch(typed);
 var typeField = document.getElementById('wk_person_type');
 if (typeField && exceptionType) typeField.value = exceptionType;
 var schoolField = document.getElementById('wk_school_id');
 if (schoolField) schoolField.value = '';
 showError('');
 modal.style.display = 'flex';
 document.body.style.overflow = 'hidden';
 var first = document.getElementById('wk_first_name');
 if (first) first.focus();
 }

 window.openWalkinRegistrationModal = function (searchValue) {
 var exceptionType = exceptionTypeFromSearch(searchValue);
 if (!exceptionType) return;
 openModal(exceptionType);
 };

 function closeModal() {
 var modal = document.getElementById('walkinModal');
 if (modal) modal.style.display = 'none';
 document.body.style.overflow = '';
 }

 function showError(msg) {
 var box = document.getElementById('walkinFormError');
 if (!box) return;
 box.textContent = msg || '';
 box.style.display = msg ? 'block' : 'none';
 }

 /* -- submit --- */
 function submitForm(e) {
 e.preventDefault();
 showError('');

 var payload = new URLSearchParams({
 first_name: val('wk_first_name'),
 last_name: val('wk_last_name'),
 middle_name: val('wk_middle_name'),
 sex: val('wk_sex'),
 person_type: val('wk_person_type'),
 email: val('wk_email'),
 school_id: val('wk_school_id')
 });

 var btn = document.getElementById('walkinSubmit');
 if (btn) btn.disabled = true;

 fetch(ENDPOINT, {
 method: 'POST',
 headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
 credentials: 'same-origin',
 body: payload
 })
 .then(function (r) {
 return r.text().then(function (raw) {
 try {
 return JSON.parse(raw);
 } catch (e) {
 console.error('Walk-in registration raw response:', raw);
 return { ok: false, message: 'Server returned invalid response while registering.' };
 }
 });
 })
 .then(function (resp) {
 if (!resp || !resp.ok) {
 showError((resp && (resp.message || resp.debug)) || 'Registration failed.');
 return;
 }
 var spid = resp.SchoolPersonID || (resp.patient && resp.patient.SchoolPersonID);
 closeModal();
 var input = document.getElementById('consultSearchInput');
 if (input && spid) {
 input.value = String(spid);
 if (typeof window.searchPatient === 'function') {
 window.searchPatient();
 }
 }
 document.getElementById('walkinForm').reset();
 })
 .catch(function () { showError('Server error while registering. Please try again.'); })
 .finally(function () { if (btn) btn.disabled = false; });
 }

 function val(id) {
 var el = document.getElementById(id);
 return el ? el.value.trim() : '';
 }
})();

