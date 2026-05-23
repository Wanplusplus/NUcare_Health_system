# TODO (Auth + RBAC Stabilization)

## Step 1: SHA2-compatible password hashing (login/signup/reset/update)
- [ ] Update `auth/login_ajax.php` to verify password using `SHA2(password, 256)` (compare against stored hash).
- [ ] Update `auth/register_ajax.php` to store passwords as `SHA2(password, 256)` hex, not `password_hash()`.
- [ ] Update `auth/send_reset.php`, `auth/reset_password.php`, and `auth/update_password.php` to store new passwords using SHA2 hex.
- [ ] Remove any remaining `password_verify()` / `password_hash()` usage in auth flows (keep UI unchanged).

## Step 2: Centralize RBAC + session structure in `includes/rbac.php`
- [ ] Standardize required session keys: `UserID`, `SchoolPersonID`, `SchoolID`, `Roles`, `Permissions`.
- [ ] Ensure `rbacLoadSessionPermissions()` populates these keys consistently.
- [ ] Ensure role retrieval and permission retrieval use only `user_roles` + `role_permissions`.
- [ ] Ensure module access validation uses correct module/permission names.
- [ ] Implement dashboard landing resolution with priority:
  1) Super Admin
  2) Admin
  3) Doctor/Dentist/Nurse
  4) Student/Faculty/Staff

## Step 3: Remove legacy authorization/session logic
- [ ] Remove reliance on `patient_id` and `PersonType`-based authorization from RBAC path.
- [ ] Update `includes/auth_guard.php` and `modules/dashboard/*_dashboard.php` to rely on `$_SESSION['Roles']` and `UserID` only.
- [ ] Ensure no hardcoded redirects based on legacy patient session variables.

## Step 4: Promotion correctness
- [ ] Verify/adjust `admin/user_management.php` role promotion flow so that promoted users redirect to `medical_staff_dashboard.php` immediately.
- [ ] Ensure medical professionals row creation is compatible with your RBAC session load.

## Step 5: Sidebar loading
- [ ] Ensure sidebars load only using `$_SESSION['Roles']`.
- [ ] Remove any PersonType checks.

## Step 6: Verification
- [ ] Test seeded accounts login.
- [ ] Test newly signed up accounts login.
- [ ] Test promoted Staff -> Doctor/Nurse/Dentist redirect + module access.
- [ ] Test Admin/Super Admin redirect + Admin Panel access.

