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

        <!-- ══ MAIN LAYOUT (Schedule full width) ══ -->
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
                        <div class="legend-dot dot-pending"></div> Pending
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot dot-blocked"></div> Blocked
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot dot-today"></div> Today
                    </div>
                </div>
            </div><!-- /.schedule-card -->



        </div><!-- /.schedule-main-layout -->

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


    <!-- ══════════════════════════════════════════
         BOOKING RESPOND MODAL (Accept / Decline)
    ══════════════════════════════════════════ -->
    <div id="respondModal" class="modal-backdrop">
        <div class="modal-box modal-box--respond">

            <!-- Header -->
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

            <!-- Body -->
            <div class="modal-body-scroll">
                <div class="respond-info-box">
                    <div class="respond-info-row">
                        <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                        <div>
                            <strong>Accept</strong> — The booking will be confirmed and the patient will be notified that their appointment has been approved.
                        </div>
                    </div>
                    <div class="respond-info-row" style="margin-top:10px">
                        <i class="fa-solid fa-circle-xmark" style="color:var(--danger)"></i>
                        <div>
                            <strong>Decline</strong> — The booking will be rejected, the slot will be released, and the patient will be notified that the doctor is unavailable to accept the appointment.
                        </div>
                    </div>
                </div>

                <!-- Decline reason (shown only when declining) -->
                <div id="declineReasonSection" style="display:none; margin-top:16px;">
                    <div class="modal-section-label" style="margin-bottom:8px;">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Reason for Declining <span style="color:var(--gray-400);font-weight:500">(optional — sent to patient)</span>
                    </div>
                    <textarea
                        id="declineReasonText"
                        class="slot-notes-textarea"
                        rows="3"
                        placeholder="e.g. The doctor is fully booked on that date. Please try booking another available slot…"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button class="btn-outline" id="respondModalCancelBtn">Cancel</button>
                <button class="btn-decline-modal" id="btnDeclineBooking">
                    <i class="fa-solid fa-circle-xmark"></i> Decline
                </button>
                <button class="btn-decline-modal btn-decline-confirm" id="btnDeclineConfirm" style="display:none;">
                    <i class="fa-solid fa-paper-plane"></i> Send Decline
                </button>
                <button class="btn-success" id="btnAcceptBooking">
                    <i class="fa-solid fa-circle-check"></i> Accept Booking
                </button>
            </div>

        </div><!-- /.modal-box -->
    </div><!-- /.modal-backdrop -->


    <!-- Toast -->
    <div id="scheduleToast" class="schedule-toast"></div>

    </main>
</div><!-- /.app-shell -->

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/schedule.js?v=2"></script>
</body>
</html>