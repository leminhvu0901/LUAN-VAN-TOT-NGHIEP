document.addEventListener('DOMContentLoaded', function() {
    // Accumulate selected files
    let accumulatedFiles = new DataTransfer();
    const newImagesInput = document.getElementById('new-images-input');
    const previewContainer = document.getElementById('new-images-preview');

    if (newImagesInput) {
        newImagesInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            // Cảnh báo nếu chọn quá 5 ảnh
            if (accumulatedFiles.files.length + files.length > 5) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Đạt giới hạn!',
                        text: 'Bạn chỉ được chọn tối đa 5 ảnh.',
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
                    alert('Bạn chỉ được chọn tối đa 5 ảnh.');
                }
                e.target.value = ''; // Reset input for current selection
                return;
            }

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                
                // Add file to accumulated list
                accumulatedFiles.items.add(file);

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Remove hidden class if first image
                    previewContainer.classList.remove('empty:hidden');

                    const imgWrapper = document.createElement('div');
                    imgWrapper.className = 'relative group w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-white border border-gray-200 shadow-sm animate-fade-in-up';
                    // Lấy index của file vừa được thêm vào
                    const fileIndex = accumulatedFiles.files.length - 1;
                    imgWrapper.dataset.index = fileIndex;
                    
                    imgWrapper.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button type="button" class="btn-remove-new-img p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors" title="Xóa ảnh này">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </div>
                    `;
                    
                    previewContainer.appendChild(imgWrapper);
                }
                reader.readAsDataURL(file);
            });

            // Update the file input to contain all accumulated files
            newImagesInput.files = accumulatedFiles.files;
        });
    }

    if (previewContainer) {
        // Handle removing new images from preview
        previewContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.btn-remove-new-img');
            if (removeBtn) {
                const wrapper = removeBtn.closest('.relative');
                const indexToRemove = parseInt(wrapper.dataset.index);
                
                // Remove from DataTransfer
                const newAccumulatedFiles = new DataTransfer();
                for (let i = 0; i < accumulatedFiles.files.length; i++) {
                    if (i !== indexToRemove) {
                        newAccumulatedFiles.items.add(accumulatedFiles.files[i]);
                    }
                }
                accumulatedFiles = newAccumulatedFiles;
                
                // Update input
                newImagesInput.files = accumulatedFiles.files;
                
                // Remove from UI
            wrapper.remove();
            
            // Update indices of remaining wrappers
            const wrappers = previewContainer.children;
            for (let i = 0; i < wrappers.length; i++) {
                wrappers[i].dataset.index = i;
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
