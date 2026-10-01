@once
<script>
(function () {
    @php($lineRows = old('lines', $requisition->lines?->toArray() ?? []))
    let prLineIndex = {{ empty($lineRows) ? 1 : max(array_keys($lineRows)) + 1 }};

    window.prAddLine = function () {
        const c = document.getElementById('pr-lines-container');
        const t = document.getElementById('pr-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', prLineIndex++));
        prRecalc();
    };

    window.prRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) { row.remove(); prRecalc(); }
    };

    function prRecalc() {
        let total = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const q = parseFloat(row.querySelector('.line-qty')?.value || 0);
            const p = parseFloat(row.querySelector('.line-price')?.value || 0);
            total += q * p;
        });
        const el = document.getElementById('pr-total');
        if (el) el.textContent = total.toFixed(4);
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('line-qty') ||
            e.target.classList.contains('line-price')) {
            prRecalc();
        }
    });
    prRecalc();
})();
</script>
@endonce

<template id="pr-line-template">
    @include('procurement::purchase-requisitions._line-row', [
        'index' => '__INDEX__',
        'line'  => [],
        'items' => $items,
        'units' => $units,
    ])
</template>
