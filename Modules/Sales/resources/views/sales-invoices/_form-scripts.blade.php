@once
<script>
(function () {
    let siLineIndex = {{ count(old('lines', $invoice->lines?->toArray() ?? [])) ?: 1 }};

    window.siAddLine = function () {
        const c = document.getElementById('si-lines-container');
        const t = document.getElementById('si-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', siLineIndex++));
        siRecalc();
    };

    window.siRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) { row.remove(); siRecalc(); }
    };

    function siRecalc() {
        let sub = 0, tax = 0, wht = 0;
        document.querySelectorAll('.line-row').forEach(row => {
            const q  = parseFloat(row.querySelector('.si-qty')?.value   || 0);
            const p  = parseFloat(row.querySelector('.si-price')?.value || 0);
            const d  = parseFloat(row.querySelector('.si-disc')?.value  || 0);
            const t  = parseFloat(row.querySelector('.si-tax')?.value   || 0);
            const wt = parseFloat(row.querySelector('.si-wht')?.value   || 0);

            const gross   = q * p;
            const lineSub = gross * (1 - d / 100);
            const lineTax = lineSub * (t / 100);
            const lineWht = lineSub * (wt / 100);

            sub += lineSub;
            tax += lineTax;
            wht += lineWht;
        });

        const total = sub + tax - wht;

        document.getElementById('si-subtotal').textContent = sub.toFixed(4);
        document.getElementById('si-tax').textContent      = tax.toFixed(4);
        document.getElementById('si-wht').textContent      = wht.toFixed(4);
        document.getElementById('si-total').textContent    = total.toFixed(4);
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.si-qty, .si-price, .si-disc, .si-tax, .si-wht')) siRecalc();
    });

    siRecalc();
})();
</script>
@endonce

<template id="si-line-template">
    @include('sales::sales-invoices._line-row', [
        'index' => '__INDEX__',
        'line'  => [],
        'items' => $items,
        'units' => $units,
    ])
</template>
