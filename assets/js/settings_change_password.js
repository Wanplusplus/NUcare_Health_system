document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('settingsChangePasswordForm');
  if (!form) return;

  const currentEl = document.getElementById('current_password');
  const newEl = document.getElementById('new_password');
  const confirmEl = document.getElementById('confirm_password');

  const curErr = document.getElementById('currentPasswordError');
  const newErr = document.getElementById('newPasswordError');
  const confErr = document.getElementById('confirmPasswordError');
  const feedback = document.getElementById('passwordFeedback');

  const btn = document.getElementById('changePasswordBtn');
  const btnText = document.getElementById('changePasswordBtnText');
  const spinner = document.getElementById('changePasswordSpinner');

  function setLoading(loading) {
    if (btn) btn.disabled = loading;
    if (btnText) btnText.style.display = loading ? 'none' : 'inline';
    if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
  }

  function clearErrors() {
    if (curErr) curErr.textContent = '';
    if (newErr) newErr.textContent = '';
    if (confErr) confErr.textContent = '';
  }

  function setFeedback(message, type = '') {
    if (!feedback) return;
    feedback.textContent = message || '';
    feedback.className = 'settings-feedback' + (type ? ' ' + type : '');
  }

  function ensureToastStack() {
    let stack = document.getElementById('toastContainer');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toastContainer';
      document.body.appendChild(stack);
    }
    stack.classList.add('toast-stack');
    return stack;
  }

  function showToast(message, type = 'success') {
    const stack = ensureToastStack();
    const el = document.createElement('div');
    el.className = 'settings-toast ' + type;
    el.textContent = message;
    stack.appendChild(el);

    requestAnimationFrame(() => el.classList.add('show'));

    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 220);
    }, 2600);
  }

  function mapBackendError(message) {
    const msg = String(message || '').toLowerCase();
    if (msg.includes('current password is required')) {
      if (curErr) curErr.textContent = 'Current password is required.';
      currentEl?.focus();
      return true;
    }
    if (msg.includes('current password is incorrect')) {
      if (curErr) curErr.textContent = 'Current password is incorrect.';
      currentEl?.focus();
      return true;
    }
    if (msg.includes('new password must be at least')) {
      if (newErr) newErr.textContent = 'New password must be at least 6 characters.';
      newEl?.focus();
      return true;
    }
    if (msg.includes('passwords do not match')) {
      if (confErr) confErr.textContent = 'Passwords do not match.';
      confirmEl?.focus();
      return true;
    }
    return false;
  }

  [currentEl, newEl, confirmEl].forEach(el => {
    el?.addEventListener('input', () => {
      clearErrors();
      setFeedback('');
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    setFeedback('');

    const currentPassword = (currentEl?.value ?? '').trim();
    const newPassword = (newEl?.value ?? '').trim();
    const confirmPassword = (confirmEl?.value ?? '').trim();

    let hasError = false;

    if (!currentPassword) {
      if (curErr) curErr.textContent = 'Current password is required.';
      hasError = true;
    }

    if (!newPassword) {
      if (newErr) newErr.textContent = 'New password is required.';
      hasError = true;
    } else if (newPassword.length < 6) {
      if (newErr) newErr.textContent = 'New password must be at least 6 characters.';
      hasError = true;
    }

    if (!confirmPassword) {
      if (confErr) confErr.textContent = 'Please confirm your new password.';
      hasError = true;
    } else if (newPassword && confirmPassword && newPassword !== confirmPassword) {
      if (confErr) confErr.textContent = 'Passwords do not match.';
      hasError = true;
    }

    if (hasError) {
      setFeedback('Please fix the errors and try again.', 'error');
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
      const ok = data && data.status === 'success';

      if (!ok) {
        const message = data?.message || 'Unable to update password.';
        if (!mapBackendError(message)) {
          setFeedback(message, 'error');
        } else {
          setFeedback(message, 'error');
        }
        showToast(message, 'error');
        return;
      }

      form.reset();
      setFeedback('Update saved!', 'success');
      showToast('Update saved!', 'success');
    } catch (err) {
      const message = 'Something went wrong. Please try again.';
      setFeedback(message, 'error');
      showToast(message, 'error');
    } finally {
      setLoading(false);
    }
  });
});
