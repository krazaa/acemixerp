@once
<script>
(function () {
    let soLineIndex = {{ count(old('lines', $salesOrder->lines?->toArray() ?? [])) ?: 1 }};

    window.soAddLine = function () {
        const c = document.getElementById('so-lines-container');
        const t = document.getElementById('so-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', soLineIndex++));
        soRecalc();
    };

    window.soRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) { row.remove(); soRecalc(); }
    };

    function soRecalc() {
        let sub = 0, tax = 0, wht = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const q  = parseFloat(row.querySelector('.so-qty')?.value   || 0);
            const p  = parseFloat(row.querySelector('.so-price')?.value || 0);
            const d  = parseFloat(row.querySelector('.so-disc')?.value  || 0);
            const t  = parseFloat(row.querySelector('.so-tax')?.value   || 0);
            const wt = parseFloat(row.querySelector('.so-wht')?.value   || 0);

            const gross   = q * p;
            const lineSub = gross * (1 - d / 100);
            const lineTax = lineSub * (t / 100);
            const lineWht = (lineSub + lineTax) * (wt / 100);

            sub += lineSub;
            tax += lineTax;
            wht += lineWht;
        });

        const total = sub + tax - wht;

        document.getElementById('so-subtotal').textContent = sub.toFixed(2);
        document.getElementById('so-tax').textContent      = tax.toFixed(2);
        document.getElementById('so-wht').textContent      = wht.toFixed(2);
        document.getElementById('so-total').textContent    = total.toFixed(2);
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.so-qty, .so-price, .so-disc, .so-tax, .so-wht')) soRecalc();
    });

    soRecalc();
})();
</script>
@endonce

<template id="so-line-template">
    @include('sales::sales-orders._line-row', [
        'index' => '__INDEX__',
        'line'  => [],
        'items' => $items,
        'units' => $units,
    ])
</template>
