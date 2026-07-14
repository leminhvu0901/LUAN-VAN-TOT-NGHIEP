(function () {
    const maxImageBytes = 2 * 1024 * 1024;

    function showError(message) {
        if (window.Swal) Swal.fire({ icon: 'error', title: 'Thông báo', text: message, confirmButtonText: 'Đóng' });
        else window.alert(message);
    }

    function createSizeRow() {
        const row = document.createElement('div');
        row.className = 'product-size-row grid grid-cols-[1fr_1fr_40px] gap-2';
        row.innerHTML = '<input name="size_names[]" maxlength="50" placeholder="Tên size" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<input name="size_price_adjustments[]" type="number" min="0" max="50000000" step="1000" value="0" placeholder="Giá cộng thêm" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<button type="button" class="js-remove-size w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa kích thước"><span class="material-symbols-outlined">delete</span></button>';
        return row;
    }

    function initSizes() {
        const container = document.getElementById('product-sizes');
        const addButton = document.getElementById('add-product-size');
        if (!container || !addButton) return;
        addButton.addEventListener('click', function () {
            if (container.querySelectorAll('.product-size-row').length < 10) container.appendChild(createSizeRow());
        });
        container.addEventListener('click', function (event) {
            const button = event.target.closest('.js-remove-size');
            if (!button) return;
            const rows = container.querySelectorAll('.product-size-row');
            if (rows.length === 1) rows[0].querySelectorAll('input').forEach((input) => input.value = input.type === 'number' ? '0' : '');
            else button.closest('.product-size-row').remove();
        });
    }

    function initImages() {
        const trigger = document.querySelector('.js-image-upload-trigger');
        const input = document.getElementById('image-upload');
        const gallery = document.getElementById('gallery-input');
        if (trigger && input) {
            trigger.addEventListener('click', (event) => { if (event.target !== input) input.click(); });
            input.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                if (file.size > maxImageBytes) { showError('Ảnh chính không được vượt quá 2MB.'); this.value = ''; return; }
                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = document.getElementById('image-preview');
                    preview.src = event.target.result;
                    preview.classList.remove('hidden');
                    document.getElementById('image-placeholder')?.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            });
        }
        if (gallery) gallery.addEventListener('change', function () {
            const files = Array.from(this.files || []);
            if (document.querySelectorAll('.gallery-item').length + files.length > 5 || files.some((file) => file.size > maxImageBytes)) {
                showError('Tối đa 5 ảnh phụ và mỗi ảnh không được vượt quá 2MB.');
                this.value = '';
                return;
            }
            const container = document.getElementById('gallery-preview-container');
            container.innerHTML = '';
            files.forEach((file) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    const image = document.createElement('img');
                    image.src = event.target.result;
                    image.className = 'w-16 h-16 object-cover rounded-lg border border-gray-200';
                    container.appendChild(image);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    function initPrice() {
        const display = document.getElementById('display_price');
        const raw = document.getElementById('raw_price');
        if (!display || !raw) return;
        display.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (Number(value) > 50000000) { value = '50000000'; showError('Giá bán không được vượt quá 50.000.000 VNĐ.'); }
            raw.value = value;
            this.value = value ? new Intl.NumberFormat('vi-VN').format(value) : '';
        });
    }

    function initGalleryDelete() {
        document.addEventListener('click', async function (event) {
            const button = event.target.closest('.js-delete-gallery-image');
            if (!button) return;
            const confirmed = window.Swal
                ? (await Swal.fire({ icon: 'warning', title: 'Xóa ảnh này?', showCancelButton: true, confirmButtonText: 'Xóa', cancelButtonText: 'Hủy' })).isConfirmed
                : window.confirm('Xóa ảnh này?');
            if (!confirmed) return;
            const response = await fetch('/admin/products/gallery/' + button.dataset.galleryImageId, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', Accept: 'application/json' },
            });
            if (response.ok) document.getElementById('gallery-item-' + button.dataset.galleryImageId)?.remove();
            else showError('Không thể xóa ảnh.');
        });
    }

    window.ProductForm = { init: function () { initSizes(); initImages(); initPrice(); initGalleryDelete(); } };
})();
