document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('settingsChangePasswordForm');
  if (!form) return;

  const currentEl = document.getElementById('current_password');
  const newEl = document.getElementById('new_password');
  const confirmEl = document.getElementById('confirm_password');

  const curErr = document.getElementById('currentPasswordError');
  const newErr = document.getElementById('newPasswordError');
  const confErr = document.getElementById('confirmPasswordError');

  const btn = document.getElementById('changePasswordBtn');
  const btnText = document.getElementById('changePasswordBtnText');
  const spinner = document.getElementById('changePasswordSpinner');

  function setLoading(loading) {
    if (btn) btn.disabled = loading;
    if (btnText) btnText.style.display = loading ? 'none' : 'inline';
    if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
  }

  function showModal(message, type = 'success') {
    // Reuse existing modal if present
    let modal = document.getElementById('settingsChangePasswordModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'settingsChangePasswordModal';
      modal.innerHTML = `
        <div class="modal-overlay" id="settingsChangePasswordOverlay" style="position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:2000;">
          <div class="modal" style="background:#fff;border-radius:16px;width:100%;max-width:420px;box-shadow:0 32px 80px rgba(0,0,0,.35);overflow:hidden;">
            <div style="padding:16px 18px;background:${type === 'success' ? 'linear-gradient(135deg, #0f3c76, #0d9488)' : 'linear-gradient(135deg, #b91c1c, #ef4444)'};color:#fff;display:flex;align-items:center;justify-content:space-between;">
              <div style="font-weight:800;">${type === 'success' ? 'Success' : 'Error'}</div>
              <button type="button" id="settingsChangePasswordClose" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;width:34px;height:34px;color:#fff;cursor:pointer;">✕</button>
            </div>
            <div style="padding:18px 18px 20px;">
              <div style="font-size:14px;font-weight:600;color:#1e293b;line-height:1.5;">${message}</div>
              <div style="margin-top:16px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" id="settingsChangePasswordOk" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,.08);background:${type === 'success' ? '#0d9488' : '#dc2626'};color:#fff;font-weight:700;cursor:pointer;">
                  OK
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    // show
    modal.style.display = 'block';
    const overlay = modal.querySelector('#settingsChangePasswordOverlay');
    const closeBtn = modal.querySelector('#settingsChangePasswordClose');
    const okBtn = modal.querySelector('#settingsChangePasswordOk');

    const msgEl = modal.querySelector('div[style*="font-weight:600"]');
    if (msgEl) msgEl.textContent = message;

    function cleanup() {
      modal.remove();
      document.body.style.overflow = '';
    }

    overlay?.addEventListener('click', (e) => {
      if (e.target === overlay) cleanup();
    });
    closeBtn?.addEventListener('click', cleanup, { once: true });
    okBtn?.addEventListener('click', cleanup, { once: true });

    document.body.style.overflow = 'hidden';
  }

  function showToast(message, type = 'success') {
    // Keep toast fallback, but prefer modal for clearer feedback
    showModal(message, type);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (curErr) curErr.textContent = '';
    if (newErr) newErr.textContent = '';
    if (confErr) confErr.textContent = '';

    const currentPassword = (currentEl?.value ?? '').trim();
    const newPassword = (newEl?.value ?? '').trim();
    const confirmPassword = (confirmEl?.value ?? '').trim();

    if (!currentPassword) {
      if (curErr) curErr.textContent = 'Current password is required.';
      return;
    }

    if (newPassword.length < 6) {
      if (newErr) newErr.textContent = 'New password must be at least 6 characters.';
      return;
    }

    if (newPassword !== confirmPassword) {
      if (confErr) confErr.textContent = 'Passwords do not match.';
      return;
    }

    setLoading(true);

    try {
      const fd = new FormData();
      fd.append('current_password', currentPassword);
      fd.append('new_password', newPassword);
      fd.append('confirm_password', confirmPassword);

      const res = await fetch('../../ajax/settings_change_password.ajax.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });

      const data = await res.json().catch(() => null);

      if (!data || data.status !== 'success') {
        const msg = data?.message || 'Unable to update password.';
        showToast(msg, 'error');
        return;
      }

      // Success
      showToast('Updated password saved', 'success');
      form.reset();



    } catch (err) {
      showToast('Something went wrong. Please try again.', 'error');
    } finally {
      setLoading(false);
    }
  });
});

