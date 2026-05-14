document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearErrors();
        successMessage.style.display = 'none';
        errorMessage.style.display = 'none';

        const fullName = document.getElementById('full_name').value.trim();
        const sex = document.getElementById('sex').value;
        const schoolId = document.getElementById('school_id').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        let hasError = false;

        if (fullName === '') {
            document.getElementById('fullNameError').textContent = 'Full name is required.';
            hasError = true;
        }

        if (sex === '') {
            document.getElementById('sexError').textContent = 'Sex is required.';
            hasError = true;
        }

        if (schoolId === '') {
            document.getElementById('schoolIdError').textContent = 'School ID is required.';
            hasError = true;
        }

        if (password.length < 6) {
            document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
            hasError = true;
        }

        if (password !== confirmPassword) {
            document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
            hasError = true;
        }

        if (hasError) return;

        const formData = new FormData();
        formData.append('full_name', fullName);
        formData.append('sex', sex);
        formData.append('school_id', schoolId);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);

        try {
            const response = await fetch('register_ajax.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                successMessage.textContent = result.message;
                successMessage.style.display = 'block';
                form.reset();

                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                errorMessage.textContent = result.message;
                errorMessage.style.display = 'block';
            }
        } catch (error) {
            errorMessage.textContent = 'Something went wrong. Please try again.';
            errorMessage.style.display = 'block';
        }
    });

    function clearErrors() {
        document.getElementById('fullNameError').textContent = '';
        document.getElementById('sexError').textContent = '';
        document.getElementById('schoolIdError').textContent = '';
        document.getElementById('passwordError').textContent = '';
        document.getElementById('confirmPasswordError').textContent = '';
    }
});