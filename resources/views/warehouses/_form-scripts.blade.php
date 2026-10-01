@once
<script>
(function () {
    let warehouseAddressIndex = {{ count(old('addresses', $warehouse->addresses?->toArray() ?? [])) }};

    window.warehouseAddAddressRow = function () {
        const container = document.getElementById('warehouse-addresses-container');
        const empty     = document.getElementById('warehouse-no-addresses');
        if (empty) empty.remove();

        const template = document.getElementById('warehouse-address-template');
        const html = template.innerHTML.replaceAll('__INDEX__', warehouseAddressIndex++);
        container.insertAdjacentHTML('beforeend', html);
    };

    window.warehouseRemoveAddressRow = function (btn) {
        const row = btn.closest('.address-row');
        if (row) row.remove();
    };
})();
</script>
@endonce

<template id="warehouse-address-template">
    @include('warehouses._address-row', ['index' => '__INDEX__', 'address' => []])
</template>
