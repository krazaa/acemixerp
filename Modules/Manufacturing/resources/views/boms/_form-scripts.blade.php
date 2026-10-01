@once
<script>
(function () {
    let bomLineIndex = {{ count(old('lines', $bom->lines?->toArray() ?? [])) ?: 1 }};

    window.bomAddLine = function () {
        const c = document.getElementById('bom-lines-container');
        const t = document.getElementById('bom-line-template');
        c.insertAdjacentHTML('beforeend', t.innerHTML.replaceAll('__INDEX__', bomLineIndex++));
        $(c.lastElementChild).find('[data-control="select2"]').select2({
            width: '100%',
            dir: document.body.getAttribute('direction') || 'ltr',
        }).attr('data-kt-initialized', '1');
    };

    window.bomRemoveLine = function (btn) {
        const row = btn.closest('.line-row');
        if (row) {
            $(row).find('.select2-hidden-accessible').select2('destroy');
            row.remove();
        }
    };

    const batchUrlTemplate = @json(route('manufacturing.boms.ingredient-batches', ['ingredient' => '__INGREDIENT__']));

    const setBatchOptions = async (ingredientSelect, selectedBatchId = '') => {
        const row = ingredientSelect.closest('.line-row');
        const batchSelect = row.querySelector('.bom-batch-select');
        const itemId = ingredientSelect.value;

        batchSelect.innerHTML = '<option value="">Select stock batch number</option>';
        batchSelect.disabled = !itemId;

        if (!itemId) {
            return;
        }

        try {
            const response = await fetch(batchUrlTemplate.replace('__INGREDIENT__', encodeURIComponent(itemId)), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Unable to load ingredient batches.');
            }

            const { data } = await response.json();

            if (!data.length) {
                batchSelect.innerHTML = '<option value="">No on-hand batches</option>';
                return;
            }

            data.forEach((batch) => {
                const option = document.createElement('option');
                const expiry = batch.expiry
    ? new Date(batch.expiry).toLocaleDateString('en-GB').replaceAll('/', '/')
    : 'N/A';
                option.value = batch.id;
                option.selected = String(batch.id) === String(selectedBatchId);
                option.textContent = `${batch.number} · Available ${Number(batch.available_quantity).toFixed(2)} · Expiry ${expiry}`;
                batchSelect.append(option);
            });
        } catch (error) {
            batchSelect.innerHTML = '<option value="">Unable to load batches</option>';
        }
    };

    $('#bom-lines-container').on('change', '.bom-ingredient-select', function () {
        setBatchOptions(this);
    });

    document.querySelectorAll('.bom-ingredient-select').forEach((ingredientSelect) => {
        const batchSelect = ingredientSelect.closest('.line-row').querySelector('.bom-batch-select');
        setBatchOptions(ingredientSelect, batchSelect.dataset.selectedBatch);
    });
})();
</script>
@endonce

<template id="bom-line-template">
    @include('manufacturing::boms._line-row', [
        'index' => '__INDEX__', 'line' => [], 'components' => $components, 'units' => $units,
    ])
</template>
