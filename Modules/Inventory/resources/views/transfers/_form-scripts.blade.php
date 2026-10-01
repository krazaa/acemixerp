@once
<script>
(function () {
    let invLineIndex = {{ count(old('lines', $transfer->lines?->toArray() ?? [])) ?: 1 }};

    window.invAddLine = function () {
        const c = document.getElementById('inv-lines-container');
        const t = document.getElementById('inv-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', invLineIndex++));
    };

    window.invRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) row.remove();
    };
})();
</script>
@endonce

<template id="inv-line-template">
    @include('inventory::transfers._line-row', [
        'index' => '__INDEX__', 'line' => [], 'items' => $items, 'units' => $units,
    ])
</template>
