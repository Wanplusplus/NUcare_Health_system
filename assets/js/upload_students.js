document.addEventListener('DOMContentLoaded', () => {
 const form = document.getElementById('uploadForm');
 const result = document.getElementById('uploadResult');
 const btn = document.getElementById('uploadBtn');

 form.addEventListener('submit', async e => {
 e.preventDefault();
 btn.disabled = true;
 result.innerHTML = '';

 try {
 const fd = new FormData(form);
 const r = await fetch('../admin/import_students.php', { method: 'POST', body: fd });
 const j = await r.json();

 result.innerHTML = `
 <div class="alert alert-${j.status === 'success' ? 'success' : 'danger'}">
 ${j.message}<br>
 Inserted: ${j.inserted || 0} |
 Updated: ${j.updated || 0} |
 Skipped: ${j.skipped || 0}
 </div>`;
 } catch (err) {
 result.innerHTML = '<div class="alert alert-danger">Upload failed</div>';
 } finally {
 btn.disabled = false;
 }
 });
});
