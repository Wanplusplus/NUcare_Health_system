# TODO_records_patch.md

## Completed
- [x] Adjusted `ajax/records_patient_info_save.ajax.php` to stop using `SchoolID` as a relational identity anchor during user creation.
  - Change: insert into `users` now uses `NULLIF(:school_id, '')` so empty SchoolID becomes NULL.
  - Still preserves nullable metadata behavior without affecting lookups.

## Remaining (A: remove any usage of SchoolID as FK/anchor everywhere)
- [ ] Audit and refactor any other endpoints that lookup/create using `SchoolID` instead of `SchoolPersonID`.
- [ ] Review consultation/records retrieval paths for any `SchoolID`-based joins/where clauses.
- [ ] Ensure Dashboard + notifications + medicine low-stock alerts do not use `SchoolID` as an identity anchor.
- [ ] Ensure Patient Profile joins on `SchoolPersonID` only (no `SchoolID` lookup anchors).
- [ ] Run smoke tests and manually validate core flows:
  - patient search/inline registration -> consultation save -> records load
  - walk-in (NULL SchoolID) does not break any SQL

