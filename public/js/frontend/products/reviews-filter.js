/**
 * reviews-filter.js - Điều khiển việc lọc danh sách đánh giá sản phẩm ở Frontend
 * Các tính năng bao gồm:
 * - Lọc đánh giá theo số sao (1-5 sao) hoặc chỉ lấy các bài có hình ảnh minh họa bằng AJAX.
 * - Hỗ trợ nút "Xem thêm đánh giá" (Load more) nối thêm dữ liệu động vào cuối danh sách không tải lại trang.
 * - Sử dụng cơ chế Request Token (đếm thứ tự yêu cầu) nhằm ngăn lỗi tranh chấp dữ liệu khi click lọc quá nhanh.
 * - Khởi tạo thanh cuộn ngang tùy biến chỉ báo vị trí vuốt cho hàng nút lọc sao trên Mobile.
 */
(function () {
    'use strict';

    // Tìm tất cả các phân khu đánh giá có class '.reviews-app' trên trang hiện tại
    document.querySelectorAll('.reviews-app').forEach(function (app) {
        // Đọc thông tin ID sản phẩm và chế độ xem (đầy đủ hoặc thu gọn) từ thuộc tính thẻ bọc HTML
        const productId = app.getAttribute('data-product-id');
        const view = app.getAttribute('data-view') || 'compact'; // mặc định là 'compact'
        
        const itemsFrag = app.querySelector('.review-items-fragment'); // Nơi hiển thị danh sách đánh giá
        const loadMoreFrag = app.querySelector('.review-loadmore-fragment'); // Nơi hiển thị nút "Xem thêm"
        const filterBtns = app.querySelectorAll('.review-filter-btn'); // Các nút lọc sao và ảnh

        // Nếu thiếu các thành phần quan trọng, thoát khỏi hàm để tránh lỗi JS
        if (!productId || !itemsFrag || !loadMoreFrag) return;

        let currentRating = ''; // Điểm sao đang lọc hiện tại (mặc định rỗng là tất cả)
        let currentHasImage = ''; // Có lọc theo ảnh hay không ('1' hoặc '')
        let requestToken = 0; // Biến đếm tăng tiến để định danh duy nhất cho mỗi lượt gửi AJAX

        /**
         * Gỡ bỏ trạng thái hoạt động ở các nút lọc cũ và kích hoạt cho nút vừa chọn
         * @param {HTMLElement} activeBtn - Nút lọc vừa được click
         */
        function setActiveButton(activeBtn) {
            filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
            if (activeBtn) activeBtn.classList.add('is-active');
        }

        /**
         * Xây dựng chuỗi URL chứa các tham số lọc gửi lên server
         * @param {number} page - Số trang cần tải dữ liệu
         * @returns {string} - Đường dẫn hoàn chỉnh kèm tham số query string
         */
        function buildUrl(page) {
            const params = new URLSearchParams();
            params.set('view', view); // Truyền chế độ view để backend render layout tương ứng
            if (currentRating) params.set('rating', currentRating);
            if (currentHasImage) params.set('has_image', '1');
            if (page > 1) params.set('page', String(page));
            return '/products/' + productId + '/reviews?' + params.toString();
        }

        /**
         * Thực hiện gửi request AJAX Fetch lấy danh sách đánh giá mới từ máy chủ
         * @param {number} page - Số trang cần tải dữ liệu
         * @param {boolean} append - true nếu muốn cộng dồn (xem thêm), false nếu muốn thay thế hoàn toàn (khi click nút lọc)
         */
        function fetchReviews(page, append) {
            const myToken = ++requestToken; // Ghi nhận số hiệu của request lần này

            // Nếu là thao tác lọc mới (không phải cộng dồn xem thêm), làm mờ danh sách cũ để báo hiệu đang tải
            if (!append) {
                itemsFrag.style.opacity = '0.5';
            }

            fetch(buildUrl(page), { 
                headers: { Accept: 'text/html' } // Yêu cầu Backend trả về đoạn HTML đã được render sẵn từ Blade
            })
            .then(function (response) { 
                return response.text(); 
            })
            .then(function (html) {
                // CHỐNG TRANH CHẤP BẤT ĐỒNG BỘ:
                // Nếu số hiệu request lúc gửi đi (myToken) khác với số hiệu request mới nhất hiện tại (requestToken)
                // Điều đó nghĩa là trong lúc đang tải, người dùng đã click chọn nút lọc khác. 
                // Ta lập tức bỏ qua kết quả này để tránh đè dữ liệu cũ lên lựa chọn mới.
                if (myToken !== requestToken) return;

                // Tạo một thẻ div ảo để chứa đoạn HTML vừa tải về phục vụ bóc tách
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItems = temp.querySelector('.review-items-fragment');
                const newLoadMore = temp.querySelector('.review-loadmore-fragment');

                if (newItems) {
                    if (append) {
                        // Nối tiếp bài đánh giá mới vào cuối danh sách bài viết cũ
                        itemsFrag.insertAdjacentHTML('beforeend', newItems.innerHTML);
                    } else {
                        // Đè mới hoàn toàn danh sách bài đánh giá (khi bấm lọc sao khác)
                        itemsFrag.innerHTML = newItems.innerHTML;
                    }
                }
                
                // Thay thế nút "Xem thêm" bằng nút mới tải về (để nhận đúng số trang tiếp theo hoặc ẩn đi nếu đã hết)
                if (newLoadMore) {
                    loadMoreFrag.innerHTML = newLoadMore.innerHTML;
                }
                
                itemsFrag.style.opacity = '1'; // Phục hồi độ rõ nét của danh sách
            })
            .catch(function (err) {
                console.error('Lỗi tải đánh giá sản phẩm:', err);
                itemsFrag.style.opacity = '1';
            });
        }

        // Đăng ký click cho các nút lọc sao ở hàng trên đầu phân khu đánh giá
        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentRating = btn.getAttribute('data-rating') || '';
                currentHasImage = btn.getAttribute('data-has-image') || '';
                setActiveButton(btn); // Cập nhật màu viền nút hoạt động
                fetchReviews(1, false); // Tải lại trang 1, thay thế danh sách cũ
            });
        });

        // Đăng ký click cho nút "Xem thêm đánh giá"
        // Sử dụng Event Delegation gắn sự kiện lên thẻ bọc cha `loadMoreFrag` thay vì gắn trực tiếp vào nút
        // Vì nút "Xem thêm" bên trong sẽ bị xóa và đè mới liên tục sau mỗi lần AJAX tải thêm trang mới.
        loadMoreFrag.addEventListener('click', function (event) {
            const btn = event.target.closest('.review-loadmore-btn');
            if (!btn) return;
            const nextPage = parseInt(btn.getAttribute('data-next-page'), 10) || 1;
            fetchReviews(nextPage, true); // Tải trang tiếp theo, nối tiếp vào danh sách cũ
        });
    });

    // === 5. XỬ LÝ THANH CUỘN CHỈ BÁO VUỐT NGANG (MOBILE FILTERS HORIZONTAL SCROLLBAR) ===
    (function () {
        const track = document.getElementById('review-filters-track'); // Hàng chứa các nút lọc (cuộn ngang)
        const scrollbar = document.getElementById('review-filters-scrollbar'); // Thanh chứa đường ray cuộn
        const thumb = document.getElementById('review-filters-scrollbar-thumb'); // Cục chỉ báo di chuyển (Thumb)
        if (!track || !scrollbar || !thumb) return;

        /**
         * Tính toán tỷ lệ chiều rộng và vị trí trượt của cục chỉ báo dựa trên tỷ lệ cuộn thực tế của hàng nút
         */
        function updateScrollIndicator() {
            const scrollWidth = track.scrollWidth; // Tổng chiều dài thực tế bên trong hàng nút
            const clientWidth = track.clientWidth; // Chiều dài phần giao diện nhìn thấy bên ngoài
            const scrollLeft = track.scrollLeft; // Vị trí đã cuộn từ mép trái

            // Nếu tổng chiều rộng thực tế nhỏ hơn hoặc bằng chiều rộng hiển thị (không có thanh cuộn) -> Ẩn chỉ báo đi
            if (scrollWidth <= clientWidth) {
                scrollbar.style.display = 'none';
                return;
            }

            scrollbar.style.display = 'block'; // Hiển thị chỉ báo cuộn

            // Tính toán tỷ lệ chiều rộng của cục Thumb trên thanh cuộn
            const thumbWidthRatio = clientWidth / scrollWidth;
            const thumbWidth = Math.max(20, clientWidth * thumbWidthRatio); // Giới hạn chiều rộng tối thiểu 20px
            thumb.style.width = thumbWidth + 'px';

            // Tính toán khoảng cách trượt từ mép trái của cục Thumb
            const maxScrollLeft = scrollWidth - clientWidth;
            const maxThumbLeft = clientWidth - thumbWidth;
            const thumbLeft = (scrollLeft / maxScrollLeft) * maxThumbLeft;
            thumb.style.transform = `translateX(${thumbLeft}px)`;
        }

        // Lắng nghe sự kiện cuộn (scroll) của hàng nút để đồng bộ vị trí thanh trượt chỉ báo
        track.addEventListener('scroll', updateScrollIndicator);
        
        // Theo dõi thay đổi kích thước cửa sổ trình duyệt để tính toán lại chiều rộng thanh trượt
        window.addEventListener('resize', updateScrollIndicator);
        
        // Chạy tính toán khởi tạo lần đầu
        updateScrollIndicator();
    })();
})();
