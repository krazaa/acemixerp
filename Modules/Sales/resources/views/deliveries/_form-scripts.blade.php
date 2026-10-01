@once
<script>
(function () {
    // Validate that each entered quantity does not exceed the open quantity.
    document.addEventListener('input', function (e) {
        if (! e.target.matches('input[name$="[quantity]"]')) return;
        const row = e.target.closest('.line-row');
        if (! row) return;

        const open = parseFloat(row.querySelector('div:nth-child(4) div')?.textContent || 0);
        const qty  = parseFloat(e.target.value || 0);

        if (qty > open) {
            e.target.classList.add('is-invalid');
            e.target.title = `Cannot exceed open quantity (${open}).`;
        } else {
            e.target.classList.remove('is-invalid');
            e.target.removeAttribute('title');
        }
    });
})();
</script>
@endonce
