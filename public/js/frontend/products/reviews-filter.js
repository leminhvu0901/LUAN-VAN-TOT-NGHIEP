/**
 * Xử lý lọc theo sao/có hình ảnh + "Xem thêm đánh giá" (load more) cho danh sách đánh giá sản phẩm.
 * Dùng chung cho cả trang chi tiết sản phẩm (products/show.blade.php, view=full) và trang "Xem đánh
 * giá" (products/review.blade.php, view=compact) — cả 2 trang chỉ cần bọc khối đánh giá trong
 * `.reviews-app` kèm data-product-id/data-view, phần còn lại (nút lọc, khung danh sách, nút xem thêm)
 * đều dùng chung 1 bộ class nên không cần viết riêng JS cho từng trang.
 */
(function () {
    'use strict';

    document.querySelectorAll('.reviews-app').forEach(function (app) {
        const productId = app.getAttribute('data-product-id');
        const view = app.getAttribute('data-view') || 'compact';
        const itemsFrag = app.querySelector('.review-items-fragment');
        const loadMoreFrag = app.querySelector('.review-loadmore-fragment');
        const filterBtns = app.querySelectorAll('.review-filter-btn');

        if (!productId || !itemsFrag || !loadMoreFrag) return;

        let currentRating = '';
        let currentHasImage = '';
        let requestToken = 0; // hủy kết quả của request cũ nếu người dùng bấm lọc liên tiếp nhanh

        function setActiveButton(activeBtn) {
            filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
            if (activeBtn) activeBtn.classList.add('is-active');
        }

        function buildUrl(page) {
            const params = new URLSearchParams();
            params.set('view', view);
            if (currentRating) params.set('rating', currentRating);
            if (currentHasImage) params.set('has_image', '1');
            if (page > 1) params.set('page', String(page));
            return '/products/' + productId + '/reviews?' + params.toString();
        }

        function fetchReviews(page, append) {
            const myToken = ++requestToken;

            if (!append) {
                itemsFrag.style.opacity = '0.5';
            }

            fetch(buildUrl(page), { headers: { Accept: 'text/html' } })
                .then(function (response) { return response.text(); })
                .then(function (html) {
                    if (myToken !== requestToken) return; // đã có request mới hơn, bỏ kết quả cũ

                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItems = temp.querySelector('.review-items-fragment');
                    const newLoadMore = temp.querySelector('.review-loadmore-fragment');

                    if (newItems) {
                        if (append) {
                            itemsFrag.insertAdjacentHTML('beforeend', newItems.innerHTML);
                        } else {
                            itemsFrag.innerHTML = newItems.innerHTML;
                        }
                    }
                    if (newLoadMore) {
                        loadMoreFrag.innerHTML = newLoadMore.innerHTML;
                    }
                    itemsFrag.style.opacity = '1';
                })
                .catch(function () {
                    itemsFrag.style.opacity = '1';
                });
        }

        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentRating = btn.getAttribute('data-rating') || '';
                currentHasImage = btn.getAttribute('data-has-image') || '';
                setActiveButton(btn);
                fetchReviews(1, false);
            });
        });

        // Ủy quyền sự kiện click cho nút "Xem thêm đánh giá" — nút này bị thay mới sau mỗi lần fetch
        // (đổi data-next-page hoặc biến mất khi hết trang) nên không gắn listener trực tiếp vào nút.
        loadMoreFrag.addEventListener('click', function (event) {
            const btn = event.target.closest('.review-loadmore-btn');
            if (!btn) return;
            const nextPage = parseInt(btn.getAttribute('data-next-page'), 10) || 1;
            fetchReviews(nextPage, true);
        });
    });

    // Thanh chỉ báo vị trí cuộn ngang cho hàng nút lọc (trang "Xem đánh giá", view=compact) — hàng nút
    // ẩn thanh cuộn gốc của trình duyệt (.hide-scrollbar) nên không còn gợi ý nào cho biết còn nút lọc
    // ở bên phải để vuốt sang. Cùng cơ chế đã dùng cho khối "Danh mục nổi bật" ở trang chủ (xem
    // home.js) — chỉ khác là ở đây tự ẩn thanh chỉ báo khi không có gì để cuộn (vd trên desktop hàng
    // nút tự xuống dòng thay vì cuộn ngang).
    (function () {
        const track = document.getElementById('review-filters-track');
        const scrollbar = document.getElementById('review-filters-scrollbar');
        const thumb = document.getElementById('review-filters-scrollbar-thumb');
        if (!track || !scrollbar || !thumb) return;

        function updateThumb() {
            const scrollableWidth = track.scrollWidth - track.clientWidth;

            if (scrollableWidth <= 0) {
                scrollbar.style.visibility = 'hidden';
                return;
            }
            scrollbar.style.visibility = 'visible';

            const thumbWidthPct = Math.max(15, Math.min(100, (track.clientWidth / track.scrollWidth) * 100));
            const maxTranslatePct = 100 - thumbWidthPct;
            const scrolledPct = (track.scrollLeft / scrollableWidth) * maxTranslatePct;

            thumb.style.width = thumbWidthPct + '%';
            thumb.style.transform = 'translateX(' + (scrolledPct / thumbWidthPct * 100) + '%)';
        }

        track.addEventListener('scroll', updateThumb, { passive: true });
        window.addEventListener('resize', updateThumb);
        updateThumb();
    })();
})();
