@once
<script>
(function () {
    // Prefill a new line when the user submits an empty new line,
    // defaulting quantity to the PO line's open quantity and price to PO price.
    let siLineIndex = {{ count(old('lines', $invoice->lines?->toArray() ?? [])) }};

    window.siAddLine = function () {
        const c = document.getElementById('si-lines-container');
        const t = document.getElementById('si-line-template');
        if (! c || ! t) return;
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', siLineIndex++));
    };

    window.siRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) row.remove();
    };

    // Live per-line total display under each row (unit_price * qty * (1 + tax/100))
    function siRecalc() {
        let subtotal = 0;
        let taxTotal = 0;
        let whtTotal = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const qtyEl  = row.querySelector('input[name$="[quantity]"]');
            const priceEl= row.querySelector('input[name$="[unit_price]"]');
            const taxEl  = row.querySelector('input[name$="[tax_rate]"]');
            const whtEl  = row.querySelector('input[name$="[wht_rate]"]');
            const out    = row.querySelector('.si-line-total');
            if (! qtyEl || ! priceEl || ! out) return;
            const qty = parseFloat(qtyEl.value || 0);
            const price = parseFloat(priceEl.value || 0);
            const tax = parseFloat(taxEl?.value || 0);
            const wht = parseFloat(whtEl?.value || 0);
            const lineSubtotal = qty * price;
            const lineTax = lineSubtotal * (tax / 100);
            const lineTotal = lineSubtotal + lineTax;
            const lineWht = lineTotal * (wht / 100);
            const total = lineTotal - lineWht;

            subtotal += lineSubtotal;
            taxTotal += lineTax;
            whtTotal += lineWht;
            out.textContent = total.toFixed(4);
        });

        const subtotalOutput = document.getElementById('si-subtotal');
        const taxOutput = document.getElementById('si-tax');
        const whtOutput = document.getElementById('si-wht');
        const totalOutput = document.getElementById('si-total');

        if (subtotalOutput) {
            subtotalOutput.textContent = subtotal.toFixed(4);
        }
        if (taxOutput) {
            taxOutput.textContent = taxTotal.toFixed(4);
        }
        if (whtOutput) {
            whtOutput.textContent = whtTotal.toFixed(4);
        }
        if (totalOutput) {
            totalOutput.textContent = (subtotal + taxTotal - whtTotal).toFixed(4);
        }
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('input[name$="[quantity]"], input[name$="[unit_price]"], input[name$="[tax_rate]"], input[name$="[wht_rate]"]')) {
            siRecalc();
        }
    });

    siRecalc();
})();
</script>
@endonce
