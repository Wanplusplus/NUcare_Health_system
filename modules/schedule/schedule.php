<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Schedule', 'access');

$activeSidebarItem = 'schedule';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Schedule</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/schedule.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';
    if (file_exists($sidebarPath)) require_once $sidebarPath;
    ?>

    <main class="main-content">
    <div class="schedule-page">

        <!-- ══ PAGE HEADER ══ -->
        <div class="schedule-page-header">
            <div class="page-header-left">
                <nav class="breadcrumb">
                    <span>Home</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="bc-active">Schedule</span>
                </nav>
                <h1 class="page-title">Doctor Schedule</h1>
                <p class="page-desc">Manage weekly availability and appointment slots for clinic medical professionals.</p>
            </div>
            <div class="page-header-right">
                <button class="btn-outline" id="btnExport">
                    <i class="fa-solid fa-file-export"></i>
                    Export
                </button>
            </div>
        </div>

        <!-- ══ STATS ══ -->
        <div class="schedule-stats">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statBookings">—</div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statAvailable">—</div>
                    <div class="stat-label">Open Slots</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fa-solid fa-ban"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statDisabled">—</div>
                    <div class="stat-label">Blocked Slots</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statProfessionals">—</div>
                    <div class="stat-label">Professionals</div>
                </div>
            </div>
        </div>

        <!-- ══ SCHEDULE CARD ══ -->
        <div class="schedule-card">
            <div class="card-toolbar">
                <div class="card-section-label">
                    <i class="fa-solid fa-calendar-week"></i>
                    Weekly Schedule
                </div>
                <div class="toolbar-right">
                    <select class="professional-select" id="professionalSelect">
                        <!-- Populated by JS via API -->
                    </select>
                    <div class="week-nav">
                        <button class="nav-btn" id="prevWeek" title="Previous Week">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <span class="week-label" id="weekLabel">Loading…</span>
                        <button class="nav-btn" id="nextWeek" title="Next Week">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid -->
            <div class="schedule-grid-wrap">
                <table class="schedule-grid">
                    <thead>
                        <tr>
                            <th class="time-head">TIME</th>
                            <th id="hSun">SUN</th>
                            <th id="hMon">MON</th>
                            <th id="hTue">TUE</th>
                            <th id="hWed">WED</th>
                            <th id="hThu">THU</th>
                            <th id="hFri">FRI</th>
                            <th id="hSat">SAT</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="legend-row">
                <div class="legend-item">
                    <div class="legend-dot dot-available"></div> Available
                </div>
                <div class="legend-item">
                    <div class="legend-dot dot-booked"></div> Booked
                </div>
                <div class="legend-item">
                    <div class="legend-dot dot-blocked"></div> Blocked
                </div>
                <div class="legend-item">
                    <div class="legend-dot dot-today"></div> Today
                </div>
            </div>
        </div>

    </div><!-- /.schedule-page -->


    <!-- ══════════════════════════════════════════
         SLOT MODAL
    ══════════════════════════════════════════ -->
    <div id="slotModal" class="modal-backdrop">
        <div class="modal-box">

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="modal-header-info">
                    <div class="modal-slot-badge">
                        <i class="fa-solid fa-clock"></i>
                        <span id="modalSlotTime">–</span>
                    </div>
                    <div class="modal-title" id="modalDayFull">–</div>
                    <div class="modal-subtitle" id="modalProfName">–</div>
                </div>
                <button class="modal-close" id="modalCloseBtn" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Availability Toggle -->
            <div class="availability-section">
                <div class="avail-label">
                    <i class="fa-solid fa-circle-dot"></i>
                    Slot Availability
                </div>
                <div class="avail-toggle-row">
                    <div class="avail-status">
                        <div class="avail-status-dot" id="availDot"></div>
                        <div class="avail-status-text" id="availText">–</div>
                    </div>
                    <div class="toggle-group">
                        <button class="toggle-btn" id="btnEnableSlot">
                            <i class="fa-solid fa-circle-check"></i> Enable Slot
                        </button>
                        <button class="toggle-btn" id="btnDisableSlot">
                            <i class="fa-solid fa-ban"></i> Block Slot
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Body (scrollable) -->
            <div class="modal-body-scroll">

                <!-- Appointment Info -->
                <div class="modal-section">
                    <div class="modal-section-label">
                        <i class="fa-solid fa-notes-medical"></i>
                        Scheduled Appointment
                    </div>
                    <div id="bookingContent">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Notes -->
                <div class="modal-section" style="padding-top:0">
                    <div class="modal-section-label">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Slot Notes / Remarks
                    </div>
                    <textarea class="slot-notes-textarea" id="slotNotes" placeholder="Add internal notes or remarks for this time slot…"></textarea>
                </div>

            </div><!-- /.modal-body-scroll -->

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button class="btn-outline" id="modalCancelBtn">Cancel</button>
                <button class="btn-success" id="modalSaveBtn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </div><!-- /.modal-box -->
    </div><!-- /.modal-backdrop -->

    <!-- Toast -->
    <div id="scheduleToast" class="schedule-toast"></div>

    </main>
</div><!-- /.app-shell -->

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/schedule.js"></script>
</body>
</html>