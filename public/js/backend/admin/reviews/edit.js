document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hiển thị preview cho ảnh mới upload và giữ lại ảnh đã chọn
    let accumulatedFiles = new DataTransfer();
    const newImagesInput = document.getElementById('new-images-input');
    const previewContainer = document.getElementById('new-images-preview');

    if (newImagesInput) {
        newImagesInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            // Đếm số lượng ảnh cũ hiện có trên giao diện
            const existingImagesCount = document.querySelectorAll('[id^="review-image-"]').length;
            let currentTotal = existingImagesCount + accumulatedFiles.files.length;
            let excessCount = 0;

            // Thêm các file vừa chọn vào danh sách tích lũy (nếu chưa có và chưa vượt quá giới hạn 5)
            Array.from(files).forEach(file => {
                const isDuplicate = Array.from(accumulatedFiles.files).some(f => f.name === file.name && f.size === file.size);
                if (!isDuplicate && file.type.startsWith('image/')) {
                    if (currentTotal < 5) {
                        accumulatedFiles.items.add(file);
                        currentTotal++;
                    } else {
                        excessCount++;
                    }
                }
            });

            // Thông báo nếu vượt quá 5 ảnh
            if (excessCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Đạt giới hạn!',
                        text: `Tối đa 5 ảnh (gồm ảnh cũ). Đã bỏ qua ${excessCount} ảnh.`,
                        width: '320px',
                        padding: '1rem',
                        confirmButtonText: 'Đóng',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            htmlContainer: 'text-sm text-gray-600 mt-1',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                } else {
                    alert(`Tối đa chỉ được 5 ảnh (gồm cả ảnh cũ). Đã tự động bỏ qua ${excessCount} ảnh vượt mức.`);
                }
            }

            // Gán ngược danh sách tích lũy vào input để khi submit form gửi đi tất cả
            this.files = accumulatedFiles.files;

            // Xóa sạch preview cũ để vẽ lại toàn bộ danh sách mới
            previewContainer.innerHTML = ''; 

            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgWrap = document.createElement('div');
                    imgWrap.className = 'relative w-24 h-24 rounded-xl overflow-hidden border-2 border-amber-300 shadow-sm opacity-0 transform scale-95 transition-all duration-300 group';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover';
                    
                    const badge = document.createElement('span');
                    badge.className = 'absolute top-0 right-0 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-bl-lg shadow-sm pointer-events-none';
                    badge.textContent = 'MỚI';
                    
                    // Nút X nhỏ bên trái để xóa riêng ảnh này khỏi danh sách chọn
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute top-1 left-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600';
                    removeBtn.innerHTML = '<span class="material-symbols-outlined text-[14px] font-bold">close</span>';
                    removeBtn.title = 'Bỏ chọn ảnh này';
                    removeBtn.onclick = function() {
                        // Tạo một DataTransfer mới, copy tất cả TRỪ ảnh hiện tại
                        const dt = new DataTransfer();
                        Array.from(accumulatedFiles.files).forEach((f, i) => {
                            if (i !== index) dt.items.add(f);
                        });
                        accumulatedFiles = dt;
                        newImagesInput.files = accumulatedFiles.files; // Cập nhật lại input
                        imgWrap.remove(); // Xóa khỏi giao diện
                    };

                    imgWrap.appendChild(img);
                    imgWrap.appendChild(badge);
                    imgWrap.appendChild(removeBtn);
                    previewContainer.appendChild(imgWrap);

                    // Hiệu ứng fade in
                    setTimeout(() => {
                        imgWrap.classList.remove('opacity-0', 'scale-95');
                    }, 50);
                }
                reader.readAsDataURL(file);
            });
        });
    }

    // Xóa ảnh cũ
    const existingImagesContainer = document.getElementById('existing-images-container');
    if (existingImagesContainer) {
        existingImagesContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-delete-review-image');
            if (!btn) return;

            const imageName = btn.dataset.image;
            const reviewId = btn.dataset.id;
            const index = btn.dataset.index;
            const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

            const executeDelete = () => {
                // Thay nút bằng icon loading
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined text-[16px] font-bold animate-spin">refresh</span>';
                btn.disabled = true;

                fetch(`/admin/reviews/${reviewId}/image`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ image: imageName })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Xóa phần tử ảnh khỏi giao diện
                        const imgElem = document.getElementById('review-image-' + index);
                        if(imgElem) imgElem.remove();
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'bottom-end',
                                icon: 'success',
                                title: 'Đã xóa ảnh thành công!',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Lỗi', 'Xóa ảnh thất bại: ' + (data.message || 'Lỗi không xác định'), 'error');
                        } else {
                            alert('Xóa ảnh thất bại: ' + (data.message || 'Lỗi không xác định'));
                        }
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Lỗi', 'Có lỗi xảy ra khi gọi server.', 'error');
                    } else {
                        alert('Có lỗi xảy ra khi gọi server.');
                    }
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Xác nhận xóa ảnh?',
                    text: "Ảnh này sẽ bị xóa vĩnh viễn.",
                    icon: 'warning',
                    width: '360px',
                    padding: '1.25rem',
                    showCancelButton: true,
                    confirmButtonText: 'Xóa ngay',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-[20px] shadow-2xl border border-gray-100',
                        title: 'text-lg font-bold text-gray-800',
                        htmlContainer: 'text-sm text-gray-500 mt-1',
                        confirmButton: 'px-5 py-2 rounded-xl text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm hover:shadow-md ml-2',
                        cancelButton: 'px-5 py-2 rounded-xl text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all mr-2',
                        icon: 'transform scale-[0.6] -mt-2 -mb-2',
                        actions: 'mt-4 w-full flex justify-center'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeDelete();
                    }
                });
            } else {
                if (confirm('Bạn có chắc chắn muốn xóa ảnh này ngay lập tức không?')) {
                    executeDelete();
                }
            }
        });
    }

    // Xử lý đếm ký tự nhận xét
    const commentTextarea = document.getElementById('review-comment');
    const commentCounter = document.getElementById('comment-counter');
    const commentError = document.getElementById('comment-error');

    if (commentTextarea && commentCounter) {
        const updateCounter = () => {
            const len = commentTextarea.value.length;
            commentCounter.textContent = `${len}/200`;
            
            if (len >= 200) {
                commentCounter.classList.remove('text-gray-400');
                commentCounter.classList.add('text-red-500');
                if(commentError) commentError.classList.remove('hidden');
            } else {
                commentCounter.classList.remove('text-red-500');
                commentCounter.classList.add('text-gray-400');
                if(commentError) commentError.classList.add('hidden');
            }
        };
        
        // Cập nhật ngay lúc load (nếu có old data)
        updateCounter();

        commentTextarea.addEventListener('input', updateCounter);
        
        commentTextarea.addEventListener('paste', function(e) {
            // Delay để chờ text dán vào
            setTimeout(updateCounter, 10);
        });
    }
});
