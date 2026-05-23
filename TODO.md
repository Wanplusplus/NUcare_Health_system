<<<<<<< HEAD
# TODO - Consultation Module Implementation

## Plan (approved)
- Create consultation table `consultation_transactions`
- Implement AJAX backend endpoints under `ajax/consultation/*`
- Update `modules/consultation/consultation.php` + `assets/js/consultation.js` + `assets/css/consultation.css`
- Implement transaction flow: auto-create Transaction #1; modal confirm for additional transactions
- Implement medicines dispensing deduction + inventory status updates
- Implement secure attachment upload (50MB, JPG/PNG/PDF)
- Update consultation history display fields only: School ID, Sex, Transaction Number, Created At
- Remove Logout button from consultation page

## Steps
1. Create SQL migration: `sql/consultation_schema.sql` with `consultation_transactions`.
2. Create `ajax/consultation/` directory and endpoints:
   2.1 `patient_search.ajax.php`
   2.2 `list_transactions.ajax.php`
   2.3 `create_transaction.ajax.php` (auto-create Transaction #1 OR create next transaction after modal)
   2.4 `save_consultation.ajax.php` (save consultation + medicine deduction + attachment)
3. Update frontend UI in `modules/consultation/consultation.php`:
   3.1 remove Logout button
   3.2 adjust patient banner fields (School ID + Sex only)
   3.3 add modal container
   3.4 add consultation history table with only required columns
   3.5 update upload input (JPG/PNG/PDF, 50MB)
4. Update `assets/js/consultation.js`:
   4.1 implement AJAX search by School ID
   4.2 implement modal flow for transaction creation
   4.3 load history after search
   4.4 ensure hidden transaction fields are submitted
   4.5 ensure medicines rows validated
5. Update `assets/css/consultation.css` for modal + history table responsiveness.
6. Run a quick backend sanity test (lint/quick PHP syntax check) and verify endpoints respond with JSON.
=======
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
>>>>>>> adminside

