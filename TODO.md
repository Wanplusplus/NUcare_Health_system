# TODO - Medical staff redirect/dashboard fix

- [x] Add redirect logic in `auth/login_ajax.php` for Doctor/Dentist/Nurse users to `modules/dashboard/medical_staff_dashboard.php`
- [x] Restore/fix `modules/dashboard/medical_staff_dashboard.php` to a valid dashboard page

- [ ] Ensure the page passes RBAC checks (use `requireModule('Medical Staff Panel' or correct moduleName, 'access')` matching DB)
- [ ] Run a quick smoke test: login as promoted Doctor -> verify correct session + redirect

