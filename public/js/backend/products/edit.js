function previewImage(event) {
    const imagePreview = document.getElementById('image-preview');
    const imagePlaceholder = document.getElementById('image-placeholder');
    const reader = new FileReader();
    reader.onload = function(){
        imagePreview.src = reader.result;
        imagePreview.classList.remove('hidden');
        if(imagePlaceholder) imagePlaceholder.classList.add('hidden');
    };
    if(event.target.files[0]){
        if (event.target.files[0].size > 2097152) { // 2MB
            alert('Ảnh chính không được vượt quá 2MB!');
            event.target.value = '';
            return;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
}

function previewGallery(event) {
    const container = document.getElementById('gallery-preview-container');
    container.innerHTML = ''; // Xóa preview cũ
    
    const files = event.target.files;
    if (files) {
        const existingCount = document.querySelectorAll('.gallery-item').length;
        if (existingCount + files.length > 5) {
            alert(`Sản phẩm đã có ${existingCount} ảnh phụ. Bạn chỉ có thể chọn thêm tối đa ${5 - existingCount} ảnh nữa!`);
            event.target.value = ''; // Reset input
            return;
        }

        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 2097152) { // 2MB
                alert('Mỗi ảnh phụ không được vượt quá 2MB! Hình: ' + files[i].name);
                event.target.value = '';
                return;
            }
        }

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-16 h-16 object-cover rounded-lg border border-gray-200 opacity-70';
                container.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }
}

function deleteGalleryImage(id) {
    if (confirm('Bạn có chắc chắn muốn xóa ảnh này khỏi bộ sưu tập?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(`/admin/products/gallery/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.getElementById(`gallery-item-${id}`);
                if (item) item.remove();
            } else {
                alert('Có lỗi xảy ra, không thể xóa ảnh!');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const displayPrice = document.getElementById('display_price');
    const rawPrice = document.getElementById('raw_price');

    if (displayPrice) {
        displayPrice.addEventListener('input', function(e) {
            // Chỉ giữ lại số
            let val = this.value.replace(/\D/g, '');
            
            // Giới hạn giá tối đa 50.000.000
            if (parseInt(val) > 50000000) {
                val = '50000000';
                alert('Giá bán không được vượt quá 50.000.000 VNĐ');
            }
            
            // Cập nhật giá trị thật (số nguyên) cho hidden input
            rawPrice.value = val;
            
            // Format lại chuỗi hiển thị
            if (val) {
                this.value = new Intl.NumberFormat('vi-VN').format(val);
            } else {
                this.value = '';
            }
        });
        
        // Xóa form submit validation default for formatted text
        displayPrice.form.addEventListener('submit', function() {
            if(!rawPrice.value) {
                rawPrice.value = 0;
            }
        });
    }
});
