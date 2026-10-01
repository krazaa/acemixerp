@once
<script>
(function () {
    let soLineIndex = {{ count(old('lines', $quotation->lines?->toArray() ?? [])) ?: 1 }};

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
        let sub    = 0;
        let tax    = 0;
        let whttax = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const q  = parseFloat(row.querySelector('.so-qty')?.value     || 0);
            const p  = parseFloat(row.querySelector('.so-price')?.value   || 0);
            const d  = parseFloat(row.querySelector('.so-disc')?.value    || 0);
            const t  = parseFloat(row.querySelector('.so-tax')?.value     || 0);
            const wt = parseFloat(row.querySelector('.so-whttax')?.value  || 0);

            const gross     = q * p;
            const lineSub   = gross * (1 - d / 100);
            const lineTax   = lineSub * (t / 100);
            const lineWht   = (lineSub + lineTax) * (wt / 100);

            sub    += lineSub;
            tax    += lineTax;
            whttax += lineWht;
        });

        const total = sub + tax - whttax;

        const subEl = document.getElementById('so-subtotal');
        const taxEl = document.getElementById('so-tax');
        const whtEl = document.getElementById('so-whttax');
        const totEl = document.getElementById('so-total');

        if (subEl) subEl.textContent = sub.toFixed(4);
        if (taxEl) taxEl.textContent = tax.toFixed(4);
        if (whtEl) whtEl.textContent = whttax.toFixed(4);
        if (totEl) totEl.textContent = total.toFixed(4);
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.so-qty, .so-price, .so-disc, .so-tax, .so-whttax')) {
            soRecalc();
        }
    });

    soRecalc();
})();
</script>
@endonce

<template id="so-line-template">
    @include('sales::quotations._line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'units' => $units])
</template>
