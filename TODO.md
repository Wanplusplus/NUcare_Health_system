# TODO.md — Consultation Module Enhancement

## Consultation backend endpoints
- [ ] Step 1: Create `ajax/consultation/patient_search.ajax.php` (AJAX search by School ID from `school_people`, return patient info)
- [ ] Step 2: Create `ajax/consultation/create_transaction.ajax.php`
  - [ ] detect existing history in `physical_examinations`
  - [ ] if none: create Tx #1 and return consultation_id + transaction_number
  - [ ] if exists: when `mode=auto` return ok:false + historyCount
  - [ ] when `mode=next` create new transaction with incremented `transaction_number`
- [ ] Step 3: Create `ajax/consultation/list_transactions.ajax.php`
  - [ ] return only: SchoolID, Sex, TransactionNumber, CreatedAt
  - [ ] ordered by newest/consistent rule
- [ ] Step 4: Create `ajax/consultation/save_consultation.ajax.php`
  - [ ] validate input server-side
  - [ ] save all consultation fields into `physical_examinations` for the given consultation_id / transaction
  - [ ] optional medicine dispensing: insert medicine dispensing rows (if existing table) and deduct stock from `medicine_inventory` with prepared statements
  - [ ] prevent insufficient stock / negative stock
  - [ ] update medicine status after deduction
  - [ ] optional secure file upload (JPG/PNG/PDF, max 50MB, whitelist MIME+ext, unique filenames, store path in `attachment_path`)

## UI/UX alignment
- [ ] Step 5: Update attachment max-size hint in UI if it conflicts with 50MB requirement

## Testing / verification
- [ ] Step 6: Apply/verify schema (`sql/consultation_schema.sql`) in local MySQL
- [ ] Step 7: Manual end-to-end test: patient search -> Tx creation flow -> save -> inventory deduction -> file upload -> history display

