@once
<script>
(function () {
    @php($lineRows = old('lines', $rfq->lines?->toArray() ?? []))
    let rfqLineIndex = {{ empty($lineRows) ? 1 : max(array_keys($lineRows)) + 1 }};
    let rfqVendorIndex = {{ count(old('vendor_ids', $rfq->vendors?->pluck('vendor_id')->all() ?? [])) ?: 1 }};

    window.rfqAddLine = function () {
        const c = document.getElementById('rfq-lines-container');
        const t = document.getElementById('rfq-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', rfqLineIndex++));
    };
    window.rfqRemoveLine = function (btn) { btn.closest('.line-row')?.remove(); };

    window.rfqAddVendor = function () {
        const c = document.getElementById('rfq-vendors-container');
        const t = document.getElementById('rfq-vendor-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', rfqVendorIndex++));
    };
    window.rfqRemoveVendor = function (btn) { btn.closest('.vendor-row')?.remove(); };
})();
</script>
@endonce

<template id="rfq-line-template">
    @include('procurement::rfqs._line-row', ['index' => '__INDEX__', 'line' => [], 'items' => $items, 'units' => $units])
</template>

<template id="rfq-vendor-template">
    @include('procurement::rfqs._vendor-row', ['index' => '__INDEX__', 'vendor' => [], 'vendors' => $vendors])
</template>
