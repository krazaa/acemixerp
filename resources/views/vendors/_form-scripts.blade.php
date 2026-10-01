@once
<script>
(function () {
    @php($rows = $addressRows ?? old('addresses', $vendor->addresses?->toArray() ?? []))
    let vendorAddressIndex = {{ empty($rows) ? 0 : max(array_keys($rows)) + 1 }};

    window.vendorAddAddressRow = function () {
        const container = document.getElementById('vendor-addresses-container');
        const empty     = document.getElementById('vendor-no-addresses');
        if (empty) empty.remove();

        const template = document.getElementById('vendor-address-template');
        const html = template.innerHTML.replaceAll('__INDEX__', vendorAddressIndex++);
        container.insertAdjacentHTML('beforeend', html);
    };

    window.vendorRemoveAddressRow = function (btn) {
        const row = btn.closest('.address-row');
        if (row) row.remove();
    };
})();
</script>
@endonce

<template id="vendor-address-template">
    @include('vendors._address-row', ['index' => '__INDEX__', 'address' => []])
</template>
