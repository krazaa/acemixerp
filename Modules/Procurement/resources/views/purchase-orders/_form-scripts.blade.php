@once
<script>
(function () {
    @php($lineRows = old('lines', $purchaseOrder->lines?->toArray() ?? []))
    let poLineIndex = {{ empty($lineRows) ? 1 : max(array_keys($lineRows)) + 1 }};

    window.poAddLine = function () {
        const c = document.getElementById('po-lines-container');
        const t = document.getElementById('po-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', poLineIndex++));
        poRecalc();
    };

    window.poRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) { row.remove(); poRecalc(); }
    };

    function poRecalc() {
        let sub = 0, tax = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const q = parseFloat(row.querySelector('.po-qty')?.value || 0);
            const p = parseFloat(row.querySelector('.po-price')?.value || 0);
            const t = parseFloat(row.querySelector('.po-tax')?.value || 0);
            const line = q * p;
            sub += line;
            tax += line * (t / 100);
        });
        const total = sub + tax;
        document.getElementById('po-subtotal').textContent = sub.toFixed(4);
        document.getElementById('po-tax').textContent = tax.toFixed(4);
        document.getElementById('po-total').textContent = total.toFixed(4);
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('po-qty') ||
            e.target.classList.contains('po-price') ||
            e.target.classList.contains('po-tax')) {
            poRecalc();
        }
    });

    poRecalc();
})();
</script>
@endonce

<template id="po-line-template">
    @include('procurement::purchase-orders._line-row', [
        'index' => '__INDEX__',
        'line'  => [],
        'items' => $items,
        'units' => $units,
    ])
</template>
