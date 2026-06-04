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
    <link rel="stylesheet" href="../../assets/css/schedule.css?v=2">
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

        <!-- ══ MAIN LAYOUT ══ -->
        <div class="schedule-main-layout" style="display:block;">

            <!-- ══ SCHEDULE CARD ══ -->
            <div class="schedule-card" style="width:100%;max-width:100%;">
                <div class="card-toolbar">
                    <div class="card-section-label">
                        <i class="fa-solid fa-calendar-week"></i>
                        Weekly Schedule
                    </div>
                    <div class="toolbar-right">
                        <select class="professional-select" id="professionalSelect">
                            <!-- Populated by JS -->
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
                        <div class="legend-dot dot-pending"></div> Pending
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot dot-blocked"></div> Blocked
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot dot-today"></div> Today
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- ══════════════════════════════════════════
         SLOT MODAL
    ══════════════════════════════════════════ -->
    <div id="slotModal" class="modal-backdrop">
        <div class="modal-box">
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

            <div class="modal-body-scroll">
                <div class="modal-section">
                    <div class="modal-section-label">
                        <i class="fa-solid fa-notes-medical"></i>
                        Scheduled Appointment
                    </div>
                    <div id="bookingContent">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <div class="modal-section" style="padding-top:0">
                    <div class="modal-section-label">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Slot Notes / Remarks
                    </div>
                    <textarea class="slot-notes-textarea" id="slotNotes" placeholder="Add internal notes or remarks for this time slot…"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-outline" id="modalCancelBtn">Cancel</button>
                <button class="btn-success" id="modalSaveBtn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════
         BOOKING RESPOND MODAL
    ══════════════════════════════════════════ -->
    <div id="respondModal" class="modal-backdrop">
        <div class="modal-box modal-box--respond">

            <div class="modal-header">
                <div class="modal-header-info">
                    <div class="modal-slot-badge respond-badge">
                        <i class="fa-solid fa-inbox"></i>
                        Booking Request
                    </div>
                    <div class="modal-title">Respond to Appointment Request</div>
                    <div class="modal-subtitle">
                        Booking <strong id="respondBookingId">#—</strong> is awaiting your decision.
                    </div>
                </div>
                <button class="modal-close" id="respondModalCloseBtn" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body-scroll">
                <div class="respond-info-box">
                    <div class="respond-info-row">
                        <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                        <div>
                            <strong>Accept</strong> — The booking will be confirmed and the patient will be notified.
                        </div>
                    </div>
                    <div class="respond-info-row" style="margin-top:10px">
                        <i class="fa-solid fa-clock" style="color:var(--blue-500)"></i>
                        <div>
                            <strong>Reschedule</strong> — Propose a new time slot. The patient must approve the new time.
                        </div>
                    </div>
                    <div class="respond-info-row" style="margin-top:10px">
                        <i class="fa-solid fa-circle-xmark" style="color:var(--danger)"></i>
                        <div>
                            <strong>Decline</strong> — The booking will be rejected.
                        </div>
                    </div>
                </div>

<!-- Reschedule Section - Weekly Calendar Grid -->
<div id="rescheduleSection" style="display:none; margin-top:15px;">
    <div style="font-weight:700; margin-bottom:10px; color:var(--blue-600); font-family:'Poppins',sans-serif; font-size:.82rem; display:flex; align-items:center; gap:7px;">
        <i class="fa-solid fa-calendar-week"></i> Select New Date &amp; Time
    </div>

    <!-- Week navigator -->
    <div class="reschedule-week-nav">
        <button type="button" class="nav-btn" id="reschedulePrevWeek" title="Previous Week">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span class="week-label" id="rescheduleWeekLabel">Loading…</span>
        <button type="button" class="nav-btn" id="rescheduleNextWeek" title="Next Week">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <!-- Schedule Grid -->
    <div class="reschedule-grid-wrap">
        <table class="reschedule-grid">
            <thead>
                <tr>
                    <th class="time-head">TIME</th>
                    <th id="rsSun">SUN</th>
                    <th id="rsMon">MON</th>
                    <th id="rsTue">TUE</th>
                    <th id="rsWed">WED</th>
                    <th id="rsThu">THU</th>
                    <th id="rsFri">FRI</th>
                    <th id="rsSat">SAT</th>
                </tr>
            </thead>
            <tbody id="rescheduleBody">
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>

    <!-- Legend -->
    <div class="reschedule-legend">
        <div class="legend-item">
            <div class="legend-dot dot-available"></div>
            <span>Available (click to select)</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot dot-unavailable"></div>
            <span>Unavailable</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot dot-selected"></div>
            <span>Selected</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot dot-today"></div>
            <span>Today</span>
        </div>
    </div>

    <!-- Selected slot display -->
    <div id="rescheduleSelectedSlot">
        <i class="fa-solid fa-circle-check" style="color:#16a34a; flex-shrink:0;"></i>
        <strong>Selected:</strong>
        <span id="rescheduleSelectedText">—</span>
        <button type="button" class="bk-clear-slot" id="rescheduleClearSlot" title="Clear selection">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div style="margin-top:10px; font-size:.78rem; color:var(--gray-500); display:flex; align-items:center; gap:6px;">
        <i class="fa-solid fa-info-circle" style="color:var(--blue-500);"></i>
        The patient will be notified and must approve this new time.
    </div>
</div>

                <!-- Decline Reason Section -->
                <div id="declineReasonSection" style="display:none; margin-top:16px;">
                    <div class="modal-section-label" style="margin-bottom:8px;">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Reason for Declining <span style="color:var(--gray-400);font-weight:500">(optional)</span>
                    </div>
                    <textarea id="declineReasonText" class="slot-notes-textarea" rows="3" placeholder="e.g. Doctor is fully booked..."></textarea>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer" style="flex-wrap: wrap; gap: 8px;">
                <button class="btn-outline" id="respondModalCancelBtn">Cancel</button>
                
                <!-- Primary Actions -->
                <div style="display: flex; gap: 8px; flex: 1;">
                    <button class="btn-success" id="btnAcceptBooking" style="flex: 1;">
                        <i class="fa-solid fa-check"></i> Accept
                    </button>
                    
                    <button class="btn-decline-modal" id="btnDeclineBooking">
                        <i class="fa-solid fa-xmark"></i> Decline
                    </button>
                    <button class="btn-decline-modal btn-decline-confirm" id="btnDeclineConfirm" style="display:none;">
                        <i class="fa-solid fa-paper-plane"></i> Send Decline
                    </button>
                </div>

                <!-- Reschedule Row -->
                <div style="display: flex; gap: 8px; width: 100%;" id="rescheduleRow">
                    <button class="btn-outline" id="btnRescheduleBooking" style="flex: 1; justify-content: center; background: var(--blue-50); color: var(--blue-600); border-color: var(--blue-200);">
                        <i class="fa-solid fa-clock"></i> Reschedule
                    </button>
                    
                    <button class="btn-success" id="btnRescheduleConfirm" style="display: none; flex: 1; justify-content: center; background: var(--blue-600);">
                        <i class="fa-solid fa-paper-plane"></i> Send Reschedule
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Toast -->
    <div id="scheduleToast" class="schedule-toast"></div>

    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/schedule.js?v=2"></script>
</body>
</html>