<!--
═══════════════════════════════════════════════════════════════
  NUCARE · Daily Logbook PDF Export — Integration Guide
  Summary of all changes applied to medical_staff_dashboard.php
═══════════════════════════════════════════════════════════════

CHANGE 1 ── In <head>, add CDN scripts for jsPDF + html2canvas
            AND link the updated CSS (after the existing <style>):
────────────────────────────────────────────────────────────── -->

<link rel="stylesheet" href="../../assets/css/logbook_print.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!--
CHANGE 2 ── Replace the old Print button in .logbook-savebar
            with the new Export PDF button:
────────────────────────────────────────────────────────────── -->

<!-- OLD (remove this):
<button class="lb-print-btn" id="lbPrintBtn" onclick="printLogbook()" type="button">
    <i class="fa-solid fa-print"></i> Print Logbook
</button>
-->

<!-- NEW (use this instead): -->
<button class="lb-export-btn" id="lbExportBtn" onclick="exportLogbookPDF()" type="button">
    <span class="lb-export-spinner"></span>
    <i class="fa-solid fa-file-pdf lb-export-icon"></i>
    <span class="lb-export-label">Export PDF</span>
</button>

<!--
CHANGE 3 ── Replace old printLogbook() with exportLogbookPDF()
            inside the <script> block at the bottom of the logbook section.
            See medical_staff_dashboard.php for the full function.

CHANGE 4 ── Rename the footer div class from logbook-print-footer
            to logbook-pdf-footer:
────────────────────────────────────────────────────────────── -->

<div class="logbook-pdf-footer">
    <span>NUCARE Clinic Portal · NU Bacolod</span>
    <span id="lbPrintDate"></span>
    <span>Medical Staff Daily Logbook</span>
</div>

<div class="logbook-savebar">
    <div class="logbook-savebar-hint">
        <span class="sparkle">✦</span>
        Click any number to edit · Changes are saved locally until submitted
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <!-- ★ Export PDF button -->
        <button class="lb-export-btn" id="lbExportBtn" onclick="exportLogbookPDF()" type="button">
            <span class="lb-export-spinner"></span>
            <i class="fa-solid fa-file-pdf lb-export-icon"></i>
            <span class="lb-export-label">Export PDF</span>
        </button>
        <!-- Existing: Save button -->
        <button class="lb-save-btn" id="lbSaveBtn" onclick="saveLogbook()">
            <i class="fa-solid fa-floppy-disk"></i> Save Logbook
        </button>
    </div>
</div>