# TODO

## User Management table + Audit Logs deep link

- [ ] Update `admin/user_management.php`
  - [x] Remove PersonType column (header, row cell, filter dropdown, and SQL where/select bits)

  - [x] Format Full Name as `FirstName MiddleName LastName` (middle omitted if empty)
  - [x] Ensure table columns are exactly: Full Name | School ID | Email | Current Roles | Account Status | Actions
  - [x] Remove “Reset Password” button from UI (backend left untouched)
  - [x] Add “Audit Logs” button beside Edit Roles/Deactivate
    - [x] Redirect to `admin/audit_logs.php?school_id={SchoolID}`
    - [x] Styling: make buttons match sidebar admin red `#8b0000`
  - [x] Update “Edit Roles” button styling only to sidebar red `#8b0000`

- [ ] Update `admin/audit_logs.php`
  - [x] Add `school_id` query param handling to automatically filter results to that SchoolID

  - [x] Preserve existing filters: search, module, date range (today/this_week/this_month/custom)

- [ ] Verification
  - [x] Confirm UI renders correct columns and no PersonType references remain
  - [x] Confirm clicking “Audit Logs” applies the `school_id` filter and still supports other filters



