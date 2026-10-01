@once
<script>
(function () {
    // ── Address rows (existing) ────────────────────────────────────────────
    let bankAddressIndex = {{ count(old('addresses', $bank->addresses?->toArray() ?? [])) }};

    window.bankAddAddressRow = function () {
        const container = document.getElementById('bank-addresses-container');
        const empty     = document.getElementById('bank-no-addresses');
        if (empty) empty.remove();
        const template = document.getElementById('bank-address-template');
        container.insertAdjacentHTML('beforeend',
            template.innerHTML.replaceAll('__INDEX__', bankAddressIndex++));
    };

    window.bankRemoveAddressRow = function (btn) {
        const row = btn.closest('.address-row');
        if (row) row.remove();
    };

    // ── Contact rows (new) ─────────────────────────────────────────────────
    let bankContactIndex = {{ count(old('bank_contacts', $bank->bank_contacts ?? [])) }};

    window.bankAddContactRow = function () {
        const container = document.getElementById('bank-contacts-container');
        const empty     = document.getElementById('bank-no-contacts');
        if (empty) empty.remove();
        const template = document.getElementById('bank-contact-template');
        container.insertAdjacentHTML('beforeend',
            template.innerHTML.replaceAll('__INDEX__', bankContactIndex++));
    };

    window.bankRemoveContactRow = function (btn) {
        const row = btn.closest('.contact-row');
        if (row) row.remove();
    };
})();
</script>
@endonce

<template id="bank-address-template">
    @include('banks._address-row', ['index' => '__INDEX__', 'address' => []])
</template>

<template id="bank-contact-template">
    @include('banks._contact-row', ['index' => '__INDEX__', 'contact' => []])
</template>
