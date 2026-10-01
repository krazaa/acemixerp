@once
<script>
(function () {
    let journalLineIndex = {{ count(old('lines', $entry->lines?->toArray() ?? [])) ?: 2 }};

    window.journalAddLine = function () {
        const container = document.getElementById('journal-lines-container');
        const template = document.getElementById('journal-line-template');
        container.insertAdjacentHTML('beforeend',
            template.innerHTML.replaceAll('__INDEX__', journalLineIndex++));
        recalcJournalTotals();
    };

    window.journalRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) { row.remove(); recalcJournalTotals(); }
    };

    function recalcJournalTotals() {
        let debit = 0, credit = 0;
        document.querySelectorAll('.line-debit').forEach(i => debit  += parseFloat(i.value || 0));
        document.querySelectorAll('.line-credit').forEach(i => credit += parseFloat(i.value || 0));
        const diff = debit - credit;

        document.getElementById('total-debit').textContent = debit.toFixed(4);
        document.getElementById('total-credit').textContent = credit.toFixed(4);
        const diffEl = document.getElementById('total-difference');
        diffEl.textContent = diff.toFixed(4);
        diffEl.className = Math.abs(diff) < 0.00005 ? 'text-success' : 'text-danger';
    }

    // Attach to any existing inputs (server-rendered lines)
    document.querySelectorAll('.line-debit, .line-credit').forEach(inp => {
        inp.addEventListener('input', recalcJournalTotals);
    });

    // Delegate for dynamically added rows
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('line-debit') ||
            e.target.classList.contains('line-credit')) {
            recalcJournalTotals();
        }
    });

    recalcJournalTotals();
})();
</script>
@endonce

<template id="journal-line-template">
    @include('journals._line-row', [
        'index' => '__INDEX__',
        'line'  => [],
        'accounts' => $accounts,
        'costCenters' => $costCenters,
        'departments' => $departments,
        'customers' => $customers,
        'vendors' => $vendors,
    ])
</template>
