@once
<script>
(function () {
    // Auto-fill accepted = received while the user hasn't touched accepted.
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('grn-received')) {
            const row = e.target.closest('.grn-line');
            const accepted = row?.querySelector('.grn-accepted');
            if (accepted && ! accepted.dataset.touched) {
                accepted.value = e.target.value;
            }
        }
        if (e.target.classList.contains('grn-accepted')) {
            e.target.dataset.touched = '1';
        }
    });

    window.grnRemoveLine = function (btn) {
        const row = btn.closest('.grn-line');
        if (row) row.remove();
    };

    // Add a new line (used when PO is chosen from a dropdown on standalone GRN create).
    window.grnAddLine = function () {
        const c = document.getElementById('grn-lines-container');
        const t = document.getElementById('grn-line-template');
        if (! c || ! t) return;
        const idx = c.querySelectorAll('.grn-line').length;
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', idx));
    };
})();
</script>
@endonce
