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

