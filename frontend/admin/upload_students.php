<?php ?>
<!doctype html>
<html>
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Upload Students</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
 <div class="card shadow-sm">
 <div class="card-body p-4">
 <h3 class="mb-3">Import Enrolled Students</h3>
 <form id="uploadForm" enctype="multipart/form-data">
 <div class="mb-3">
 <label class="form-label">CSV File</label>
 <input class="form-control" type="file" name="students_file" accept=".csv" required>
 </div>
 <div class="mb-3">
 <label class="form-label">Academic Year</label>
 <input class="form-control" name="academic_year" placeholder="2025-2026" required>
 </div>
 <div class="mb-3">
 <label class="form-label">Semester</label>
 <select class="form-select" name="semester" required>
 <option value="1st Semester">1st Semester</option>
 <option value="2nd Semester">2nd Semester</option>
 <option value="Summer">Summer</option>
 </select>
 </div>
 <button class="btn btn-primary" id="uploadBtn">Upload & Import</button>
 </form>
 <div class="mt-3" id="uploadResult"></div>
 </div>
 </div>
</div>
<script src="/NUcare_Health_system/assets/js/upload_students.js"></script>
</body>
</html>



