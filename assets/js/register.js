document.addEventListener('DOMContentLoaded', () => {
 const form = document.getElementById('registerForm');
 const successMessage = document.getElementById('successMessage');
 const errorMessage = document.getElementById('errorMessage');
 const emailInput = document.getElementById('email');
 const passwordInput = document.getElementById('password');
 const confirmPasswordInput = document.getElementById('confirm_password');
 const passwordToggle = document.getElementById('passwordToggle');

 const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

 function syncConfirmPassword() {
 if (confirmPasswordInput && passwordInput) {
 confirmPasswordInput.value = passwordInput.value;
 }
 }

 function clearErrors() {
 [
 'firstNameError', 'lastNameError', 'middleNameError',
 'sexError', 'schoolIdError', 'emailError',
 'passwordError', 'confirmPasswordError'
 ].forEach((id) => {
 const el = document.getElementById(id);
 if (el) el.textContent = '';
 });
 }

 function setError(id, message) {
 const el = document.getElementById(id);
 if (el) el.textContent = message;
 }

 function togglePasswordVisibility() {
 if (!passwordInput) return;
 const isPassword = passwordInput.type === 'password';
 passwordInput.type = isPassword ? 'text' : 'password';
 if (passwordToggle) {
 passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
 }
 }

 if (passwordToggle) {
 passwordToggle.addEventListener('click', togglePasswordVisibility);
 }

 if (emailInput) {
 emailInput.addEventListener('input', () => {
 const value = emailInput.value.trim();
 if (value === '') {
 setError('emailError', '');
 } else if (!emailRegex.test(value)) {
 setError('emailError', 'Please enter a valid email address.');
 } else {
 setError('emailError', '');
 }
 });
 }

 if (passwordInput) {
 passwordInput.addEventListener('input', syncConfirmPassword);
 }

 if (form) {
 form.addEventListener('submit', async (e) => {
 e.preventDefault();

 clearErrors();
 if (successMessage) successMessage.style.display = 'none';
 if (errorMessage) errorMessage.style.display = 'none';

 const firstName = document.getElementById('first_name').value.trim();
 const lastName = document.getElementById('last_name').value.trim();
 const middleName = document.getElementById('middle_name').value.trim();
 const sex = document.getElementById('sex').value;
 const schoolId = document.getElementById('school_id').value.trim();
 const email = emailInput ? emailInput.value.trim() : '';
 const password = passwordInput ? passwordInput.value : '';

 let hasError = false;

 if (firstName === '') {
 setError('firstNameError', 'First name is required.');
 hasError = true;
 }

 if (lastName === '') {
 setError('lastNameError', 'Last name is required.');
 hasError = true;
 }

 if (sex === '') {
 setError('sexError', 'Sex is required.');
 hasError = true;
 }

 if (schoolId === '') {
 setError('schoolIdError', 'School ID is required.');
 hasError = true;
 }

 if (email === '') {
 setError('emailError', 'Email address is required.');
 hasError = true;
 } else if (!emailRegex.test(email)) {
 setError('emailError', 'Please enter a valid email address.');
 hasError = true;
 }

 if (password.length < 6) {
 setError('passwordError', 'Password must be at least 6 characters.');
 hasError = true;
 }

 syncConfirmPassword();

 if (confirmPasswordInput && confirmPasswordInput.value !== password) {
 setError('confirmPasswordError', 'Passwords do not match.');
 hasError = true;
 }

 if (hasError) return;

 const formData = new FormData();
 formData.append('first_name', firstName);
 formData.append('last_name', lastName);
 formData.append('middle_name', middleName);
 formData.append('sex', sex);
 formData.append('school_id', schoolId);
 formData.append('email', email);
 formData.append('password', password);
 formData.append('confirm_password', confirmPasswordInput ? confirmPasswordInput.value : password);

 try {
 const response = await fetch('/NUcare_Health_system/frontend/auth/register_ajax.php', {
 method: 'POST',
 body: formData
 });

 const rawText = await response.text();
 const result = JSON.parse(rawText);

 if (result.status === 'success') {
 if (successMessage) {
 successMessage.textContent = result.message;
 successMessage.style.display = 'block';
 }
 form.reset();
 syncConfirmPassword();

 setTimeout(() => {
 window.location.href = 'login.php';
 }, 1500);
 } else {
 if (errorMessage) {
 errorMessage.textContent = result.message || 'Registration failed.';
 errorMessage.style.display = 'block';
 }
 }
 } catch (error) {
 console.error('REGISTER ERROR:', error);
 if (errorMessage) {
 errorMessage.textContent = 'Something went wrong. Please try again.';
 errorMessage.style.display = 'block';
 }
 }
 });
 }
});

