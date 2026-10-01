@once
<script>
(function () {
    const typeSelect = document.getElementById('type');
    const parentSelect = document.getElementById('parent_id');
    const postableCb = document.getElementById('is_postable');

    if (!typeSelect || !parentSelect) return;

    const parentOptions = Array.from(parentSelect.options).map(o => ({
        value: o.value,
        text: o.text,
        type: o.dataset.type ?? null,
    }));

    // Rebuild parent options filtered by the current type.
    function filterParents() {
        const selectedType = typeSelect.value;
        const current = parentSelect.value;

        while (parentSelect.options.length > 0) {
            parentSelect.remove(0);
        }
        const none = new Option('— None (top-level) —', '');
        parentSelect.add(none);

        parentOptions
            .filter(o => o.value !== '' && (!o.type || o.type === selectedType))
            .forEach(o => parentSelect.add(new Option(o.text, o.value)));

        if (parentOptions.some(o => o.value === current)) {
            parentSelect.value = current;
        }
    }

    typeSelect.addEventListener('change', filterParents);
    filterParents();

    // Auto-uncheck postable when a parent is selected
    if (parentSelect && postableCb) {
        parentSelect.addEventListener('change', function () {
            if (this.value && !postableCb.disabled) {
                postableCb.checked = false;
            }
        });
    }
})();
</script>
@endonce
