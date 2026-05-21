# NUcare_Health_system - Task Checklist

- [x] Identify password reset flow (send_reset → reset_password → reset_password.ajax → update_password)
- [x] Fix hard-coded reset URL in `auth/send_reset.php` (replace localhost absolute link)
- [ ] Fix remaining “Something went wrong. Please try again.” by exposing the real JSON error (token invalid/DB error) in UI

