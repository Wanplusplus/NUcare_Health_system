(function () {
    const qInput = document.getElementById('consultSearchInput');
    const feedback = document.getElementById('searchFeedback');

    function setFeedback(html, cls) {
        if (!feedback) return;
        feedback.className = 'search-feedback ' + (cls || '');
        feedback.innerHTML = html;
    }

    window.searchPatient = function () {
        if (!qInput) return;
        const query = qInput.value.trim();
        if (!query) {
            setFeedback('<i class="fa-solid fa-circle-exclamation"></i> Please enter a patient School ID.', 'not-found');
            return;
        }

        setFeedback('<i class="fa-solid fa-spinner fa-spin"></i> Searching…', '');

        fetch('../../ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(query))
            .then(r => r.text().then(t => { try { return JSON.parse(t); } catch(e){ return null; }}))
            .then(data => {
                if (!data || !data.found) {
                    setFeedback('<i class="fa-solid fa-circle-exclamation"></i> No patient found. Check the ID and try again.', 'not-found');
                    return;
                }

document.getElementById('cpcID').textContent = data.SchoolID ?? '—';
                document.getElementById('cpcSex').textContent = data.Sex ?? '—';
                document.getElementById('cpcTime').textContent = data.LoadedAt ?? '—';


                const spid = data.SchoolPersonID;

                document.getElementById('consultPatientID').value = spid;

                loadHistory(spid);

                // Auto-create first transaction (backend will enforce modal when history exists)
                fetch('../../ajax/consultation/create_transaction.ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({ school_person_id: spid, mode: 'auto' })
                })
                    .then(r => r.json())
                    .then(resp => {
                        if (!resp.ok) {
                            if (resp.historyCount && resp.historyCount > 0) {
                                const modal = document.getElementById('txConfirmModal');
                                if (modal) modal.style.display = 'block';
                                const overlay = document.getElementById('disabledOverlay');
                                if (overlay) overlay.classList.add('show');
                            } else {
                                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> ' + (resp.message || 'Unable to start transaction'), 'not-found');
                            }
                            return;
                        }

                        document.getElementById('consultationID').value = resp.consultation_id;

                        const form = document.getElementById('consultationForm');
                        if (form) form.classList.remove('disabled');
                        const overlay = document.getElementById('disabledOverlay');
                        if (overlay) overlay.classList.remove('show');

                        setFeedback('<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.', '');
                    })
                    .catch(() => {
                        setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while starting transaction.', 'not-found');
                    });
            })
            .catch(() => {
                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while searching.', 'not-found');
            });
    };

    window.confirmAddAnother = function (yes) {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'none';

        if (!yes) {
            document.getElementById('consultationID').value = '';
            const form = document.getElementById('consultationForm');
            if (form) form.classList.add('disabled');
            const overlay = document.getElementById('disabledOverlay');
            if (overlay) overlay.classList.add('show');
            return;
        }

        const spidEl = document.getElementById('consultPatientID');
if (!spidEl || !spidEl.value) {
    alert("Patient ID missing. Please search again.");
    return;
}

const spid = parseInt(spidEl.value, 10);

        fetch('../../ajax/consultation/create_transaction.ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ school_person_id: spid, mode: 'next' })
        })
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok) {
                    const form = document.getElementById('consultationForm');
                    if (form) form.classList.add('disabled');
                    return;
                }

                document.getElementById('consultationID').value = resp.consultation_id;

                const form = document.getElementById('consultationForm');
                if (form) form.classList.remove('disabled');
                const overlay = document.getElementById('disabledOverlay');
                if (overlay) overlay.classList.remove('show');

                setFeedback('<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.', '');
            })
            .catch(() => {
                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error creating transaction.', 'not-found');
            });
    };

    window.closeTxConfirm = function () {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'none';
    };

    function loadHistory(spid) {
        const tbody = document.getElementById('consultHistoryTbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="4" class="muted">Loading consultation history…</td></tr>';

        fetch('../../ajax/consultation/list_transactions.ajax.php?school_person_id=' + encodeURIComponent(spid))
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok || !Array.isArray(resp.transactions) || resp.transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="muted">No consultation history yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                resp.transactions.forEach(tx => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${tx.SchoolID ?? ''}</td>
                        <td>${tx.Sex ?? ''}</td>
                        <td>${tx.TransactionNumber ?? ''}</td>
                        <td>${tx.CreatedAt ?? ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="4" class="muted">Failed to load history.</td></tr>';
            });
    }

})();

