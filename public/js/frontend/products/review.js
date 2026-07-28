    const stars = document.querySelectorAll('#star-rating-container span');
    const ratingInput = document.getElementById('rating-input');
    let currentRating = 0;

    stars.forEach(star => {
        star.addEventListener('mouseover', function() { highlightStars(this.getAttribute('data-value')); });
        star.addEventListener('mouseout', function() { highlightStars(currentRating); });
        star.addEventListener('click', function() {
            currentRating = this.getAttribute('data-value');
            ratingInput.value = currentRating;
            highlightStars(currentRating);
            stars.forEach(s => s.classList.replace('text-gray-300', 'text-yellow-400'));
        });
    });

    function highlightStars(val) {
        stars.forEach(star => {
            if (star.getAttribute('data-value') <= val) {
                star.style.fontVariationSettings = "'FILL' 1";
                if(currentRating == 0) star.classList.replace('text-gray-300', 'text-yellow-400');
            } else {
                star.style.fontVariationSettings = "'FILL' 0";
                if(currentRating == 0) star.classList.replace('text-yellow-400', 'text-gray-300');
            }
        });
    }

    function previewImages(input) {
        const previewContainer = document.getElementById('image-preview-container');
        previewContainer.innerHTML = '';
        if (input.files && input.files.length > 0) {
            if (input.files.length > 5) { alert('Chỉ được phép chọn tối đa 5 hình ảnh.'); input.value = ''; return; }
            let hasLargeFile = false;
            Array.from(input.files).forEach(file => { if (file.size > 2 * 1024 * 1024) hasLargeFile = true; });
            if (hasLargeFile) { alert('Dung lượng mỗi hình ảnh không được vượt quá 2MB.'); input.value = ''; return; }
            Array.from(input.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200';
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
            const clearDiv = document.createElement('div');
            clearDiv.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 border-dashed flex items-center justify-center cursor-pointer hover:bg-red-50 text-error transition-colors';
            clearDiv.onclick = () => { input.value = ''; previewContainer.innerHTML = ''; };
            clearDiv.innerHTML = '<span class="material-symbols-outlined text-2xl">delete</span>';
            previewContainer.appendChild(clearDiv);
        }
    }

    // Submit qua fetch thay vì form POST cổ điển — trước đây nhập sai (bình luận quá dài, ảnh quá
    // dung lượng...) thì tải lại cả trang, người dùng phải chọn sao + chọn lại ảnh từ đầu. Lúc THÀNH
    // CÔNG vẫn điều hướng thật sang trang đơn hàng (redirect_url server trả về) — chỉ riêng lúc lỗi
    // mới ở lại trang để sửa, không mất những gì đã chọn.
    const reviewForm = document.querySelector('form[action*="/review"]');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const btn = reviewForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(reviewForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: new FormData(reviewForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status >= 400) {
                        const errors = (result.data && result.data.errors) || {};
                        const firstError = Object.values(errors)[0];
                        alert((firstError && firstError[0]) || (result.data && result.data.message) || 'Không thể gửi đánh giá, vui lòng kiểm tra lại.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (result.data && result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }
                    if (btn) btn.disabled = false;
                })
                .catch(function () {
                    alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                    if (btn) btn.disabled = false;
                });
        });
    }
