# TODO - NUcare Health System | Medicine Feature

- [x] 0) Collected repo context (Add Medicine UI, current ajax endpoint, current export stub)
- [x] 1) Replace backend endpoint `ajax/medicine_ajax.php` to insert into `medicine` and `medicine_inventory` (transactional, prepared statements, proper JSON response)
- [x] 2) Fix frontend Add Medicine form submission URL to call the correct backend file
- [x] 3) Replace `print-output/medicine_output.php` to generate professional PDF report for medicines + inventory (blue/gold theme) via TCPDF
- [x] 4) Wire Export button in `assets/js/medicine.js` to navigate to `print-output/medicine_output.php`

- [ ] 5) Smoke test: Add Medicine -> verify both tables get rows
- [ ] 6) Smoke test: Export -> verify PDF downloads and shows correct joined data

