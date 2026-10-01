@php
    $outstandingJson = isset($outstandingInvoices)
        ? $outstandingInvoices->map(function ($inv) {
            return [
                'id'          => $inv->id,
                'number'      => $inv->number,
                'vendor_ref'  => $inv->vendor_invoice_number,
                'due_date'    => $inv->due_date->format('Y-m-d'),
                'outstanding' => (string) $inv->outstanding(),
            ];
        })->values()->all()
        : [];
@endphp

@once
<script>
(function () {
    // Outstanding invoices per vendor (client-side cache).
    const outstandingCache = @json($outstandingJson);

    let allocationIndex = {{ count($existingAllocations ?? []) }};

    const outstandingUrlTemplate = "{{ route('procurement.vendor-payments.outstanding-invoices', ['vendor' => '__VID__']) }}";

    window.paymentLoadOutstanding = function (vendorId) {
        if (! vendorId) { outstandingCache.length = 0; return; }

        fetch(outstandingUrlTemplate.replace('__VID__', vendorId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(json => {
            outstandingCache.length = 0;
            (json.data || []).forEach(row => outstandingCache.push({
                id:          row.id,
                number:      row.number,
                vendor_ref:  row.vendor_invoice_number,
                due_date:    row.due_date,
                outstanding: row.outstanding,
            }));
        })
        .catch(() => { /* silent — user can still add rows manually */ });
    };

    window.paymentAddAllocation = function (invoiceId, amount) {
        const container = document.getElementById('payment-allocations-container');
        const emptyMsg  = document.getElementById('payment-no-allocations');
        if (emptyMsg) emptyMsg.remove();

        const invoice = invoiceId
            ? outstandingCache.find(i => i.id == invoiceId)
            : null;

        const optionsHtml = outstandingCache.map(i => {
            const sel = invoice && i.id == invoice.id ? 'selected' : '';
            return `<option value="${i.id}" data-outstanding="${i.outstanding}" ${sel}>
                        ${i.number} · ${i.vendor_ref} · Due ${i.due_date} · Outstanding ${i.outstanding}
                    </option>`;
        }).join('');

        const html = `
            <div class="allocation-row border rounded p-2 mb-2 bg-light-subtle">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Invoice</label>
                        <select class="form-select form-select-sm allocation-invoice-select"
                                onchange="paymentSelectInvoice(this)">
                            <option value="">— Select invoice —</option>
                            ${optionsHtml}
                        </select>
                        <input type="hidden" name="allocations[${allocationIndex}][supplier_invoice_id]"
                               value="${invoice ? invoice.id : ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Outstanding</label>
                        <div class="form-control form-control-sm bg-white text-end allocation-outstanding">
                            ${invoice ? invoice.outstanding : '—'}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Allocate</label>
                        <input name="allocations[${allocationIndex}][amount]"
                               type="number" step="0.0001" min="0.0001"
                               class="form-control form-control-sm allocation-amount"
                               value="${amount ?? (invoice ? invoice.outstanding : '')}" required>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="paymentRemoveAllocation(this)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', html);
        allocationIndex++;
        paymentRecalcTotals();
    };

    window.paymentRemoveAllocation = function (btn) {
        const row = btn.closest('.allocation-row');
        if (row) { row.remove(); paymentRecalcTotals(); }
    };

    window.paymentSelectInvoice = function (selectEl) {
        const row = selectEl.closest('.allocation-row');
        const opt = selectEl.options[selectEl.selectedIndex];
        const outstanding = opt.dataset.outstanding || '';

        row.querySelector('input[name*="[supplier_invoice_id]"]').value = selectEl.value;
        row.querySelector('.allocation-outstanding').textContent = outstanding || '—';

        const amountInput = row.querySelector('.allocation-amount');
        if (! amountInput.value && outstanding) amountInput.value = outstanding;

        paymentRecalcTotals();
    };

    window.paymentAutoAllocate = function () {
        const amount = parseFloat(document.getElementById('amount')?.value || 0);
        if (amount <= 0) { alert('Enter a payment amount first.'); return; }
        if (! outstandingCache.length) { alert('Pick a vendor with open invoices first.'); return; }

        document.querySelectorAll('.allocation-row').forEach(r => r.remove());

        const sorted = [...outstandingCache].sort((a, b) => a.due_date.localeCompare(b.due_date));

        let remaining = amount;
        for (const inv of sorted) {
            if (remaining <= 0) break;
            const portion = Math.min(remaining, parseFloat(inv.outstanding));
            if (portion <= 0) continue;
            window.paymentAddAllocation(inv.id, portion.toFixed(4));
            remaining -= portion;
        }
        paymentRecalcTotals();
    };

    function paymentRecalcTotals() {
        const amount = parseFloat(document.getElementById('amount')?.value || 0);
        let allocated = 0;
        document.querySelectorAll('.allocation-amount').forEach(el => {
            allocated += parseFloat(el.value || 0);
        });
        const unallocated = amount - allocated;

        const aEl = document.getElementById('allocated-total');
        const uEl = document.getElementById('unallocated-total');
        if (aEl) aEl.textContent = allocated.toFixed(4);
        if (uEl) {
            uEl.textContent = unallocated.toFixed(4);
            uEl.parentElement.classList.toggle('text-danger', unallocated < -0.0001);
        }
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.allocation-amount, #amount')) paymentRecalcTotals();
    });

    paymentRecalcTotals();
})();
</script>
@endonce
