@once
<script>
(function () {
    const typeSelect    = document.getElementById('item_type');
    const inventoryCard = document.getElementById('item-inventory-card');
    const sellableSw    = document.getElementById('is_sellable');
    const purchasableSw = document.getElementById('is_purchasable');

    function applyTypeRules() {
        const type = typeSelect ? typeSelect.value : 'stock';

        const tracksInventory = (type === 'stock' || type === 'consumable');

        if (inventoryCard) {
            inventoryCard.style.display = tracksInventory ? '' : 'none';
        }

        // Services cannot be purchased directly? They can — but not stocked.
        // Assets and services cannot be "sellable" in the POS sense; leave the
        // switch alone here so the server remains authoritative.

        if (!tracksInventory) {
            document.querySelectorAll('#item-inventory-card input[type="checkbox"]').forEach(cb => {
                // Do not submit contradictory inventory flags for non-stock items.
                cb.disabled = false; // keep disabled=false so validation messages still surface
            });
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', applyTypeRules);
        applyTypeRules(); // run once on load
    }

    // Image preview
    const imageInput = document.getElementById('image');
    const preview    = document.getElementById('item-image-preview');
    if (imageInput && preview) {
        imageInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) { preview.src = ''; preview.style.display = 'none'; return; }
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; preview.style.display = ''; };
            reader.readAsDataURL(file);
        });
    }
})();
</script>
@endonce
