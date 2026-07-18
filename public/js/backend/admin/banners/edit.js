// Preview và chọn tệp ảnh
function triggerFileInput() {
    document.getElementById('image-input').click();
}

function previewSelectedImage(input) {
    if (input.files && input.files[0]) {
        // Kiểm tra dung lượng (max 10MB)
        if (input.files[0].size > 10 * 1024 * 1024) {
            if (window.AdminAlert) {
                window.AdminAlert.error('Dung lượng tệp ảnh không được vượt quá 10MB.', 'Ảnh quá lớn');
            } else {
                alert('Dung lượng tệp ảnh không được vượt quá 10MB.');
            }
            input.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').setAttribute('src', e.target.result);
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('upload-placeholder').classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImagePreview() {
    document.getElementById('image-input').value = '';
    document.getElementById('image-preview').setAttribute('src', '#');
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('upload-placeholder').classList.remove('hidden');
}

// Initialize flatpickr on date fields when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".banner-date-picker", {
            locale: "vn",
            dateFormat: "Y-m-d H:i:S",
            enableTime: true,
            time_24hr: true,
            disableMobile: true,
            monthSelectorType: "static"
        });
    }
});
