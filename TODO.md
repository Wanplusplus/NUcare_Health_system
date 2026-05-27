# TODO — NUCARE Records Module (3NF read-only)

## Step 1 (done)
Inspect and fix backend list endpoint: `ajax/get_records.ajax.php`
- Remove non-allowed joins/tables (employee_assignments)
- Ensure latest enrollment only
- Keep output fields compatible with `assets/js/records.js`

## Step 2 (partial)
Inspect and fix backend patient detail endpoint: `ajax/get_patient_record.ajax.php`
- Remove non-allowed tables for diseases and emergencies
- DONE: derive Known Medical Conditions + Emergencies from `clinic_transactions` (with minimal, read-only logic)

## Step 3 (pending)
Match/align frontend JS to backend JSON keys (transactions/emergencies/certificates/attachments)
- Verify that `certificates` array used by `assets/js/records.js` matches expected category/upload date fields
- Filter certificates strictly from `consultation_attachments` via `clinic_transactions` behavior

## Step 4 (pending)
Ensure empty states:
- "No clinic visits on record yet."
- "No emergency records found."
- "No medical certificates issued."

## Step 5 (pending)
Run smoke test(s)
- `php ../../../tmp/records_endpoint_smoke.php`

## Step 6 (pending)
Manual verification checklist
- Records list loads
- View opens modal
- Clinic History timeline renders correctly
- Emergencies empty state works
- Certificates empty state works
- Transaction detail modal physical exam/meds/attachments renders

