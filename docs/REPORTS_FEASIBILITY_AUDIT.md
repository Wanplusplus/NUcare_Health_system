# NUcare Health System - Reports Module Feasibility Audit

**Date:** June 4, 2026 
**Scope:** System Reports Only (not clinic/medical reports) 
**Status:** AUDIT ONLY - No code changes

---

## DATABASE INVENTORY

### Tables Verified in `nucaredb.sql`

| Table | Purpose | Key Timestamp Columns |
|---|---|---|
| `school_people` | Master identity table | `CreatedAt` |
| `programs` | Academic programs | (none) |
| `student_enrollments` | Enrollment records | `CreatedAt` |
| `employee_assignments` | Employee records | `CreatedAt`, `StartDate`, `EndDate` |
| `users` | Login accounts | `CreatedAt`, `UpdatedAt`, `LastLogin` |
| `patients_info` | Patient demographics | (none) |
| `roles` | Role definitions | (none) |
| `modules` | System modules | (none) |
| `permissions` | Permission definitions | (none) |
| `user_roles` | User Role mapping | (none) |
| `role_permissions` | Role Module Permission mapping | (none) |
| `audit_logs` | System activity log | `ActionTimestamp` |
| `reports` | Report generation log | `GeneratedAt` |

### Tables NOT Found

| Expected Table | Status |
|---|---|
| `user_sessions` | Does not exist (dashboard_stats checks dynamically, falls back to `users.LastLogin`) |
| `login_logs` | Does not exist |
| `failed_login_logs` | Does not exist |

---

## CRITICAL FINDING: Suppressed Audit Actions

In `includes/audit.php`, the following actions are **SUPPRESSED** and **never written** to `audit_logs`:

```php
$suppressed = [
 'login_debug_rbac_loaded',
 'login_debug_school_match',
 'login_hash_debug',
 'failed_login', // <- FAILED LOGINS NOT STORED
 'failed_signup', // <- FAILED SIGNUPS NOT STORED
 'save_slot',
 'respond_booking',
 'create_user', // <- USER CREATION NOT STORED
 'update_user', // <- USER UPDATES NOT STORED
];
```

**Impact:** These actions call `auditLog()` but `auditLog()` returns early via `return;` before the INSERT.

### Actions That ARE Stored

| Raw Action Code | Human-Readable Action | Module |
|---|---|---|
| `login` | "Logged into the system" | User Management (remapped from 'auth') |
| `logout` | "Logged out of the system" | User Management |
| `signup` | "Created user account" | User Management |
| `account_activation` | "Created/activated user account" | User Management |
| `account_deactivation` | "Deactivated user account" | User Management |
| `role_assignment` | "Assigned role" | User Management |
| `role_removal` | "Removed role" | User Management |
| `rbac_role_assignment` | "Updated RBAC permissions" | RBAC Management |
| `role_assignment_permissions` | "Updated RBAC permissions" | RBAC Management |
| `enrollment_change` | "Updated enrollment status" | Admin Panel |
| `booking_created` | "Booked appointment" | Schedule |
| `booking_approved` | "Approved appointment" | Schedule |
| `booking_cancelled` | "Cancelled appointment" | Schedule |
| `booking_completed` | "Completed appointment" | Schedule |
| `availability_set` | "Set availability" | Schedule |
| `availability_updated` | "Updated availability" | Schedule |
| `availability_deleted` | "Deleted availability" | Schedule |
| `consultation_started` | "Started consultation" | Consultation |
| `consultation_updated` | "Updated consultation" | Consultation |
| `consultation_completed` | "Completed consultation" | Consultation |
| `records_opened` | "Opened records" | Records |
| `records_viewed` | "Viewed patient record" | Records |
| `records_updated` | "Updated patient record" | Records |
| `medicine_inventory_added` | "Added medicine inventory" | Medicine |
| `medicine_inventory_updated` | "Updated medicine inventory" | Medicine |
| `medicine_dispensed` | "Dispensed medicine" | Medicine |
| `medicine_received` | "Received medicine" | Medicine |

### Audit Log Call Sites (from source code)

| File | Actions Called |
|---|---|
| `auth/login_ajax.php` | `failed_login` (SUPPRESSED), `login` , debug actions (SUPPRESSED) |
| `auth/logout.php` | `logout` |
| `auth/register_ajax.php` | `failed_signup` (SUPPRESSED), `signup` |
| `ajax/schedule.ajax.php` | `booking_created`, `booking_approved`, `booking_cancelled`, `booking_completed`, availability actions |
| `ajax/medicine_ajax.php` | `medicine_dispensed`, `consultation_started` |
| `ajax/consultation/*.php` | `consultation_started`, `consultation_updated`, `consultation_completed` |
| `ajax/rbac_save_permissions.ajax.php` | `rbac_role_assignment` / `role_assignment_permissions` |
| `admin/user_management.php` | `account_activation`, `account_deactivation`, `role_assignment`, `role_removal` |

---

## REPORT-BY-REPORT AUDIT

---

### 1. USER REPORT

**Tables Used:** `users`, `school_people`, `user_roles`, `roles`

| Requirement | Status | Query Basis |
|---|---|---|
| Total Users | Fully Supported | `SELECT COUNT(*) FROM users` |
| Users by Role | Fully Supported | `users -> user_roles -> roles` JOIN, GROUP BY RoleName |
| Active Accounts | Fully Supported | `WHERE IsActive = TRUE` |
| Blocked Accounts | Fully Supported | `WHERE IsActive = FALSE` |
| Recently Registered Users | Fully Supported | `ORDER BY users.CreatedAt DESC` |
| Users grouped by Person Type | Fully Supported | `users -> school_people`, GROUP BY PersonType |

**Required Columns:**
- `users.IsActive` (BOOLEAN) - active/blocked status
- `users.CreatedAt` (TIMESTAMP) - registration date
- `users.SchoolPersonID` (FK)
- `school_people.PersonType` (ENUM: Student, Faculty, Staff)
- `user_roles.UserID`, `user_roles.RoleID` (junction)
- `roles.RoleName`

**Missing Requirements:** None

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `users.CreatedAt` |
| This Week | | `users.CreatedAt` |
| This Month | | `users.CreatedAt` |
| Custom Range | | `users.CreatedAt` |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Cover page + Summary statistics (total, active, blocked) + Role breakdown table + Person Type breakdown table
- Filters: Date range, Role, Person Type, Status

**Overall Status: FULLY SUPPORTED**

---

### 2. ACCOUNT ACTIVITY REPORT

**Tables Used:** `audit_logs`, `users`

| Requirement | Status | Query Basis |
|---|---|---|
| Login Activity | Fully Supported | `audit_logs WHERE Action = 'Logged into the system'` |
| Logout Activity | Fully Supported | `audit_logs WHERE Action = 'Logged out of the system'` |
| Last Login | Fully Supported | `users.LastLogin` (updated on each login) |
| Most Active Users | Warning Partially Supported | `audit_logs GROUP BY UserID` - only counts non-suppressed actions |

**Required Columns:**
- `audit_logs.UserID`, `audit_logs.Action`, `audit_logs.ActionTimestamp`
- `users.LastLogin`, `users.CreatedAt`

**Missing Requirements:**
- Failed login attempts are SUPPRESSED - cannot track failed activity
- `create_user`, `update_user` actions suppressed - user management activity incomplete
- No `user_sessions` table - cannot track concurrent sessions or session duration

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `audit_logs.ActionTimestamp` |
| This Week | | `audit_logs.ActionTimestamp` |
| This Month | | `audit_logs.ActionTimestamp` |
| Custom Range | | `audit_logs.ActionTimestamp` |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Summary card (total logins, total logouts, active users) + Activity table sorted by timestamp
- Filters: Date range, User, Activity type (login/logout)

**Overall Status: Warning PARTIALLY SUPPORTED** - Login/logout tracked; failed activities suppressed; no session tracking.

---

### 3. ROLE & PERMISSION REPORT

**Tables Used:** `roles`, `modules`, `permissions`, `role_permissions`, `user_roles`

| Requirement | Status | Query Basis |
|---|---|---|
| Roles list | Fully Supported | `SELECT * FROM roles` |
| Modules assigned to each role | Fully Supported | `role_permissions -> modules` GROUP BY RoleID |
| Permissions assigned to each role | Fully Supported | `role_permissions -> permissions` |
| Role distribution statistics | Fully Supported | `user_roles -> roles` GROUP BY RoleName, COUNT(DISTINCT UserID) |

**Required Columns:**
- `roles.RoleID`, `roles.RoleName`, `roles.Description`
- `modules.ModuleID`, `modules.ModuleName`
- `permissions.PermissionID`, `permissions.PermissionName`
- `role_permissions.RoleID`, `role_permissions.ModuleID`, `role_permissions.PermissionID`
- `user_roles.UserID`, `user_roles.RoleID`

**Missing Requirements:** None - all RBAC tables are complete and properly seeded.

**Date Range Support:**
| Preset | Supported | Notes |
|---|---|---|
| N/A | | RBAC data is structural, not time-based. Report shows current state. |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Role list table + Matrix view (Role x Module x Permission) + User count per role
- Filters: Role name, Module name

**Overall Status: FULLY SUPPORTED**

---

### 4. AUDIT LOG REPORT

**Tables Used:** `audit_logs`, `users`, `user_roles`, `roles`

| Requirement | Status | Query Basis |
|---|---|---|
| Activity history by user | Fully Supported | `audit_logs WHERE UserID = ? ORDER BY ActionTimestamp DESC` |
| Activity history by module | Fully Supported | `audit_logs WHERE ModuleName = ?` |
| Activity history by role | Warning Partially Supported | Must JOIN `users -> user_roles -> roles` - user may have multiple roles |
| Date-range audit report | Fully Supported | `WHERE ActionTimestamp BETWEEN ? AND ?` |

**Required Columns:**
- `audit_logs.AuditLogID`, `audit_logs.UserID`, `audit_logs.Action`, `audit_logs.ModuleName`, `audit_logs.TableAffected`, `audit_logs.RecordID`, `audit_logs.ActionTimestamp`
- `users.UserID`, `users.SchoolPersonID`
- `school_people.FirstName`, `school_people.LastName`, `school_people.SchoolID`

**Missing Requirements:**
- The `Action` column stores **human-readable text** (e.g., "Logged into the system") - good for display, but filtering by action category requires LIKE queries or an action category mapping.
- Suppressed actions (failed_login, create_user, update_user) are not available.
- `audit_logs.TableAffected` stores the `details` parameter from callers - inconsistent data (sometimes it's the SchoolID, sometimes it's a table name).

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `audit_logs.ActionTimestamp` |
| This Week | | `audit_logs.ActionTimestamp` |
| This Month | | `audit_logs.ActionTimestamp` |
| Custom Range | | `audit_logs.ActionTimestamp` |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Filter bar summary + Detailed activity table (User, Action, Module, Timestamp) with pagination
- Filters: Date range, User, Module, Action keyword

**Overall Status: Warning PARTIALLY SUPPORTED** - Core audit data present; suppressed actions create gaps.

---

### 5. LOGIN REPORT

**Tables Used:** `audit_logs`, `users`, `school_people`

| Requirement | Status | Query Basis |
|---|---|---|
| Successful logins | Fully Supported | `audit_logs WHERE Action = 'Logged into the system'` |
| Failed logins | Not Supported | `failed_login` is **SUPPRESSED** in audit.php - never stored |
| Login counts per user | Warning Partially Supported | Only successful logins can be counted |
| Login counts by date range | Warning Partially Supported | Only successful logins within date range |

**Required Columns:**
- `audit_logs.Action`, `audit_logs.UserID`, `audit_logs.ActionTimestamp`
- `users.LastLogin`

**Missing Requirements:**
- **Failed login attempts are never stored** - the `failed_login` action is in the `$suppressed` array in `audit.php`
- No `login_attempts` table exists
- No `failed_login_count` field on users table
- `users.LastLogin` stores only the LAST login - no historical login list from this column alone

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | Warning | Successful only (`audit_logs.ActionTimestamp`) |
| This Week | Warning | Successful only |
| This Month | Warning | Successful only |
| Custom Range | Warning | Successful only |

**TCPDF Feasibility:**
- Printable PDF: (with caveat: data incomplete)
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Summary card (total successful, user with most logins) + Login history table
- Filters: Date range, User, Success/Fail toggle (Fail will return empty until fixed)

**Overall Status: Warning PARTIALLY SUPPORTED** - Successful logins only; failed logins require schema/code fix.

---

### 6. ACCOUNT STATUS REPORT

**Tables Used:** `users`, `school_people`, `audit_logs`

| Requirement | Status | Query Basis |
|---|---|---|
| Active accounts | Fully Supported | `users WHERE IsActive = TRUE` |
| Inactive accounts | Fully Supported | `users WHERE IsActive = FALSE` |
| Blocked accounts | Fully Supported | `users WHERE IsActive = FALSE` (same as inactive - single BOOLEAN) |
| Recently blocked accounts | Warning Partially Supported | Can filter `audit_logs WHERE Action = 'Deactivated user account'` - but no `blocked_at` timestamp on users table |

**Required Columns:**
- `users.IsActive` (BOOLEAN)
- `users.CreatedAt`, `users.UpdatedAt`
- `users.SchoolPersonID` (FK)
- `school_people.FirstName`, `school_people.LastName`, `school_people.PersonType`
- `audit_logs.Action` - contains 'Deactivated user account' for blocking events

**Missing Requirements:**
- No `BlockedAt` or `DeactivatedAt` timestamp column on `users` table
- No `BlockedBy` column on `users` table (who blocked the account)
- Workaround: Use `audit_logs WHERE Action = 'Deactivated user account'` for recent blocks, but requires JOIN and is approximate

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `users.UpdatedAt` or `audit_logs.ActionTimestamp` |
| This Week | | Same |
| This Month | | Same |
| Custom Range | | Same |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Summary card (active/blocked counts) + Blocked users table with deactivation info
- Filters: Status (Active/Blocked), Person Type, Date range

**Overall Status: Warning PARTIALLY SUPPORTED** - Current status fully supported; deactivation history partial.

---

### 7. SYSTEM USAGE REPORT

**Tables Used:** `audit_logs`

| Requirement | Status | Query Basis |
|---|---|---|
| Module usage counts | Fully Supported | `SELECT ModuleName, COUNT(*) FROM audit_logs GROUP BY ModuleName` |
| Most accessed modules | Fully Supported | `ORDER BY COUNT(*) DESC` |
| Least accessed modules | Fully Supported | `ORDER BY COUNT(*) ASC` |
| Usage by date range | Fully Supported | `WHERE ActionTimestamp BETWEEN ? AND ?` |

**Required Columns:**
- `audit_logs.ModuleName`, `audit_logs.ActionTimestamp`

**Missing Requirements:**
- No dedicated `page_views` or `screen_views` table - usage is based on audit events only
- Modules without audit events will show zero usage (which is accurate)

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `audit_logs.ActionTimestamp` |
| This Week | | `audit_logs.ActionTimestamp` |
| This Month | | `audit_logs.ActionTimestamp` |
| Custom Range | | `audit_logs.ActionTimestamp` |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Summary statistics + Module usage table (ModuleName, Count, Percentage) + Bar representation via TCPDF text bars
- Filters: Date range, Module name

**Overall Status: FULLY SUPPORTED**

---

### 8. DASHBOARD ANALYTICS REPORT

**Tables Used:** `audit_logs`, `users`

| Requirement | Status | Query Basis |
|---|---|---|
| Daily usage trend | Fully Supported | `SELECT DATE(ActionTimestamp), COUNT(*) FROM audit_logs GROUP BY DATE(ActionTimestamp)` |
| Weekly usage trend | Fully Supported | `GROUP BY YEARWEEK(ActionTimestamp)` |
| Monthly usage trend | Fully Supported | `GROUP BY DATE_FORMAT(ActionTimestamp, '%Y-%m')` |
| User growth trend | Fully Supported | `SELECT DATE(CreatedAt), COUNT(*) FROM users GROUP BY DATE(CreatedAt)` |

**Required Columns:**
- `audit_logs.ActionTimestamp`
- `users.CreatedAt`

**Missing Requirements:**
- No page-view specific tracking - trends are based on audit events
- For accurate daily active users, would need session tracking (not available)

**Date Range Support:**
| Preset | Supported | Timestamp Column |
|---|---|---|
| Today | | `audit_logs.ActionTimestamp` |
| This Week | | `audit_logs.ActionTimestamp` |
| This Month | | `audit_logs.ActionTimestamp` |
| Custom Range | | `audit_logs.ActionTimestamp` |

**TCPDF Feasibility:**
- Printable PDF: 
- Summary PDF: 
- Detailed PDF: 
- Recommended Layout: Trend summary + Date-grouped table (Date, Event Count, New Users) + Text-based bar chart representation
- Filters: Date range, Metric type (usage / user growth)

**Overall Status: FULLY SUPPORTED**

---

## SUMMARY MATRIX

| # | Report | Status | Tables Ready | Date Range | TCPDF Ready |
|---|---|---|---|---|---|
| 1 | User Report | Fully Supported | All | Full | |
| 2 | Account Activity | Warning Partially Supported | Warning Suppressed actions | Full | |
| 3 | Role & Permission | Fully Supported | All | N/A | |
| 4 | Audit Log | Warning Partially Supported | Warning Suppressed actions | Full | |
| 5 | Login Report | Warning Partially Supported | Failed logins suppressed | Warning Partial | |
| 6 | Account Status | Warning Partially Supported | Warning No blocked_at timestamp | Full | |
| 7 | System Usage | Fully Supported | All | Full | |
| 8 | Dashboard Analytics | Fully Supported | All | Full | |

---

## FINAL RECOMMENDATION

### Ranking: Ease of Implementation

| Rank | Report | Rationale |
|---|---|---|
| 1 | Role & Permission Report | Pure RBAC tables, no date filtering, straightforward queries |
| 2 | Account Status Report | Simple boolean check, minimal joins |
| 3 | User Report | Straightforward joins (users -> school_people -> roles) |
| 4 | System Usage Report | Simple audit_logs aggregation by module |
| 5 | Dashboard Analytics Report | Date grouping queries, slightly more complex |
| 6 | Audit Log Report | Needs good filtering, pagination, and user context joins |
| 7 | Account Activity Report | Similar to audit log but focused; suppressed actions limit scope |
| 8 | Login Report | Blocked by failed_login suppression - needs fix first |

### Ranking: Most Useful for Admins

| Rank | Report | Rationale |
|---|---|---|
| 1 | User Report | Essential for day-to-day user management |
| 2 | Account Status Report | Quick view of active vs blocked accounts |
| 3 | Audit Log Report | Security monitoring and compliance |
| 4 | Login Report | Security awareness (once failed logins are tracked) |
| 5 | System Usage Report | Module adoption monitoring |

### Ranking: Most Useful for Super Admins

| Rank | Report | Rationale |
|---|---|---|
| 1 | Role & Permission Report | RBAC governance - critical for Super Admin |
| 2 | Audit Log Report | Full system oversight and accountability |
| 3 | Dashboard Analytics Report | System health and growth trends |
| 4 | System Usage Report | Resource allocation and feature adoption |
| 5 | Account Activity Report | User engagement and session awareness |

---

### Phase 1 Reports - Implement Now (DB Fully Ready)

These reports require NO schema changes and can be implemented immediately.

| Report | Status | Effort |
|---|---|---|
| **User Report** | Fully Supported | Low |
| **Role & Permission Report** | Fully Supported | Low |
| **Account Status Report** | Fully Supported (current status) | Low |
| **System Usage Report** | Fully Supported | Medium |

**Rationale:** All required tables, columns, and timestamps exist. Queries are straightforward. TCPDF templates can be built directly.

---

### Phase 2 Reports - Implement with Minor Gaps

These reports work but have known data limitations that should be documented in the UI.

| Report | Status | Effort | Gap to Document |
|---|---|---|---|
| **Dashboard Analytics Report** | Fully Supported | Medium | Activity-based, not page-view based |
| **Audit Log Report** | Warning Partially Supported | Medium | Suppressed actions create gaps in activity history |
| **Account Activity Report** | Warning Partially Supported | Medium | Failed activities not tracked; no session data |
| **Login Report** | Warning Partially Supported | Medium | Successful logins only; failed logins require fix |

**Rationale:** These reports provide value even with current limitations. Add UI disclaimers where data is incomplete.

---

### Phase 3 Reports - Require Schema/Code Changes

These reports need specific changes before they can be fully implemented.

| Report | Required Change | Priority |
|---|---|---|
| **Login Report (Full)** | Remove `failed_login` from `$suppressed` array in `audit.php`, OR create a dedicated `login_attempts` table with IP, timestamp, success/fail, and reason | HIGH |
| **Account Status (Full)** | Add `BlockedAt DATETIME` and `BlockedBy INT` columns to `users` table | MEDIUM |
| **Account Activity (Full)** | Create `user_sessions` table (UserID, SessionToken, StartedAt, LastActivity, EndedAt) for session tracking | MEDIUM |

**Recommended Schema Additions for Phase 3:**

```sql
-- Option A: Simple fix (remove suppression)
-- In includes/audit.php, remove 'failed_login' from $suppressed array

-- Option B: Dedicated login tracking table
CREATE TABLE login_attempts (
 AttemptID INT AUTO_INCREMENT PRIMARY KEY,
 UserID INT NULL,
 SchoolID VARCHAR(50),
 IPAddress VARCHAR(45),
 Success BOOLEAN NOT NULL DEFAULT FALSE,
 FailReason VARCHAR(255),
 AttemptedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_la_user (UserID),
 INDEX idx_la_time (AttemptedAt),
 INDEX idx_la_success (Success)
);

-- Account status tracking
ALTER TABLE users
 ADD COLUMN BlockedAt DATETIME NULL AFTER IsActive,
 ADD COLUMN BlockedBy INT NULL AFTER BlockedAt;

-- Session tracking
CREATE TABLE user_sessions (
 SessionID INT AUTO_INCREMENT PRIMARY KEY,
 UserID INT NOT NULL,
 SessionToken VARCHAR(255),
 StartedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 LastActivity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 EndedAt TIMESTAMP NULL,
 IPAddress VARCHAR(45),
 INDEX idx_us_user (UserID),
 INDEX idx_us_activity (LastActivity)
);
```

---

## TCPDF RECOMMENDATIONS FOR ALL REPORTS

### Common Elements
- **Cover Page:** NUcare logo, report title, generation date, generated by (admin name), date range filter applied
- **Header:** Report title, page number, NUcare branding
- **Footer:** Confidentiality notice, page number, generation timestamp

### Per-Report Layout Recommendations

| Report | Primary Layout | Secondary Elements |
|---|---|---|
| User Report | Table (UserID, Name, Role, PersonType, Status, Registered) | Summary stats bar (Total / Active / Blocked) |
| Account Activity | Table (User, Action, Module, Timestamp) | Activity type summary counts |
| Role & Permission | Matrix table (Role x Module x Permission) | Role distribution summary with user counts |
| Audit Log | Detailed table (User, Action, Module, Timestamp) | Filter summary header |
| Login Report | Table (User, Date, Time, Status) | Login count summary (by user, by date) |
| Account Status | Table (User, Name, Status, Last Login) | Status distribution pie-chart placeholder |
| System Usage | Table (Module, Count, Percentage) | Bar chart via text representation |
| Dashboard Analytics | Date-grouped table | Trend summary with directional indicators |

### PDF Generation Architecture
- Create `print-output/` PHP files for each report type
- Accept GET parameters for filters (date_from, date_to, role, module, status)
- Use TCPDF library already available in the project
- Each file generates a standalone PDF for browser download or print

---

*This audit was performed by inspecting the actual database schema (`sql/nucaredb.sql`), seed data (`sql/seedme.sql`), audit system (`includes/audit.php`), RBAC system (`includes/rbac.php`), authentication flow (`auth/login_ajax.php`, `auth/logout.php`, `auth/register_ajax.php`), dashboard stats (`ajax/dashboard_stats.ajax.php`), and existing report scaffolds (`admin/reports.php`, `modules/reports/reports.php`). No assumptions were made - all findings are verified from source code.*
