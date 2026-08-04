// ===== Bộ lọc sản phẩm nổi bật trên Trang chủ =====
(function () {
    // Lấy danh sách các nút lọc và lưới hiển thị sản phẩm
    var pillButtons = document.querySelectorAll('#home-pill-filters .home-popular__filter-btn');
    var grid = document.querySelector('.home-products-grid');
    var currentFilter = 'all'; // Bộ lọc hiện tại (mặc định là 'all')

    // Hàm thực hiện lọc và sắp xếp sản phẩm
    function applyHomeFilter() {
        if (!grid) return;

        // Chuyển danh sách NodeList thành Array để sử dụng hàm sort()
        var cards = Array.from(grid.querySelectorAll('.home-prod-card'));
        var visibleCount = 0;

        // Sắp xếp các thẻ sản phẩm theo tiêu chí của bộ lọc đang chọn
        cards.sort(function (a, b) {
            if (currentFilter === 'hot') {
                // Sắp xếp theo số lượng bán (data-sold) giảm dần
                return parseInt(b.getAttribute('data-sold') || 0) - parseInt(a.getAttribute('data-sold') || 0);
            } else if (currentFilter === 'new') {
                // Sắp xếp theo ngày đăng dạng timestamp (data-date) giảm dần
                return parseInt(b.getAttribute('data-date') || 0) - parseInt(a.getAttribute('data-date') || 0);
            } else if (currentFilter === 'sale') {
                // Đẩy các sản phẩm đang giảm giá (data-is-sale = 1) lên trước
                return parseInt(b.getAttribute('data-is-sale') || 0) - parseInt(a.getAttribute('data-is-sale') || 0);
            } else {
                // Chế độ mặc định 'all': Sắp xếp theo điểm tổng hợp (60% lượng bán + 40% điểm đánh giá sao)
                return parseFloat(b.getAttribute('data-score') || 0) - parseFloat(a.getAttribute('data-score') || 0);
            }
        });

        // Lọc hiển thị và giới hạn tối đa số sản phẩm phù hợp
        var shown = 0;
        cards.forEach(function (card) {
            grid.appendChild(card); // Gắn lại thẻ vào grid theo thứ tự mới đã sắp xếp

            // Kiểm tra xem sản phẩm có khớp bộ lọc hay không
            var isMatch = true;
            if (currentFilter === 'hot') {
                isMatch = card.getAttribute('data-is-hot') === '1';
            } else if (currentFilter === 'new') {
                isMatch = card.getAttribute('data-is-new') === '1';
            } else if (currentFilter === 'sale') {
                isMatch = card.getAttribute('data-is-sale') === '1';
            }

            // Hiển thị sản phẩm nếu khớp và chưa vượt quá số lượng tối đa (hiện tối đa 7 sản phẩm)
            if (isMatch && shown < 7) {
                card.style.display = '';
                shown++;
                visibleCount++;
            } else {
                card.style.display = 'none';
            }

            // Xử lý hiển thị huy hiệu (Badge) tương ứng để tránh đè nhãn lên nhau
            var hotBadge = card.querySelector('.home-prod-card__badge--hot');
            var newBadge = card.querySelector('.home-prod-card__badge--new');
            var saleBadge = card.querySelector('.home-prod-card__badge--sale');
            if (currentFilter === 'new') {
                if (hotBadge) hotBadge.style.display = 'none';
                if (saleBadge) saleBadge.style.display = 'none';
                if (newBadge) newBadge.style.display = '';
            } else if (currentFilter === 'sale') {
                if (hotBadge) hotBadge.style.display = 'none';
                if (newBadge) newBadge.style.display = 'none';
                if (saleBadge) saleBadge.style.display = '';
            } else if (currentFilter === 'hot') {
                if (saleBadge) saleBadge.style.display = 'none';
                if (newBadge) newBadge.style.display = 'none';
                if (hotBadge) hotBadge.style.display = '';
            } else {
                // Ở chế độ Tất cả: Ưu tiên hiện nhãn Sale -> Hot -> New
                if (saleBadge) saleBadge.style.display = '';
                if (hotBadge) hotBadge.style.display = saleBadge ? 'none' : '';
                if (newBadge) newBadge.style.display = (saleBadge || hotBadge) ? 'none' : '';
            }
        });

        // Hiển thị hoặc ẩn thông báo khi không có sản phẩm nào
        var emptyMsg = document.getElementById('home-empty-msg');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.id = 'home-empty-msg';
                emptyMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;';
                emptyMsg.textContent = 'Không có sản phẩm nào phù hợp.';
                grid.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
                grid.appendChild(emptyMsg);
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    // Lắng nghe sự kiện click trên các nút lọc danh mục
    pillButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Loại bỏ class kích hoạt cũ và thêm vào nút vừa click
            pillButtons.forEach(function (b) {
                b.classList.remove('home-popular__filter-btn--active');
            });
            this.classList.add('home-popular__filter-btn--active');
            currentFilter = this.getAttribute('data-filter');
            applyHomeFilter(); // Chạy lại bộ lọc
        });
    });

    // Chạy bộ lọc lần đầu khi load trang
    applyHomeFilter();
})();


// ===== Hiệu ứng chạy số tự động (Stat counters) =====
(function () {
    // Hàm thực hiện tăng số dần đều
    // decimals: số chữ số thập phân của giá trị gốc (vd "4.6" -> 1) để không bị Math.round làm tròn mất phần thập phân
    function animateCounter(el, target, suffix, decimals, duration) {
        var startTime = null;
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            // Sử dụng công thức Ease Out Cubic để chuyển động chậm dần về sau
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = eased * target;
            el.textContent = (decimals > 0 ? current.toFixed(decimals) : Math.round(current)) + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // Khởi tạo và kích hoạt đếm số chạy
    function startCounters() {
        document.querySelectorAll('.home-hero__stat-num').forEach(function (el) {
            var raw = el.textContent.trim(); // Lấy văn bản thô, ví dụ: "50+", "4.6★", "30'"
            var match = raw.match(/^([\d.]+)(.*)$/);
            if (!match) return;
            var num = parseFloat(match[1]); // Lấy phần số
            var suffix = match[2];          // Lấy phần hậu tố (+ / ★ / ')
            var decimals = (match[1].split('.')[1] || '').length; // Giữ đúng số chữ số thập phân như văn bản gốc
            el.textContent = (decimals > 0 ? (0).toFixed(decimals) : '0') + suffix;
            animateCounter(el, num, suffix, decimals, 1600); // Thực hiện chạy trong 1.6 giây
        });
    }

    // Trì hoãn một chút để đồng bộ với hiệu ứng xuất hiện ban đầu (hero entrance animation)
    setTimeout(startCounters, 800);
})();

// ===== Hiệu ứng kính mờ (Glassmorphism) cho Thanh điều hướng khi cuộn trang =====
const navbar = document.querySelector('.happy-navbar');
if (navbar) {
    window.addEventListener('scroll', function () {
        if (window.scrollY > 20) {
            navbar.classList.add('navbar--scrolled'); // Thêm class tạo viền mờ khi cuộn xuống
        } else {
            navbar.classList.remove('navbar--scrolled'); // Gỡ class khi cuộn lại lên đầu trang
        }
    }, { passive: true });
}

// Chức năng Thêm vào danh sách yêu thích được xử lý tập trung trong file main.js thông qua sự kiện inline onclick

// ===== Trình chiếu Banner tự động (Hero Auto Slider 4 giây) =====
(function () {
    var sliderImgs = document.querySelectorAll('#hero-slider .hero-slide-img');
    var heroTitle = document.getElementById('hero-title');
    var dots = document.querySelectorAll('#hero-slider .hero-banner__dot');
    if (sliderImgs.length === 0) return;
    var currentIdx = 0;
    var slideInterval = null;

    // Hàm chuyển đổi sang slide mong muốn
    function showSlide(nextIdx) {
        if (nextIdx === currentIdx) return;
        var prevIdx = currentIdx;

        // Đẩy slide hiện tại trượt ra phía bên trái
        sliderImgs[prevIdx].classList.remove('active');
        sliderImgs[prevIdx].classList.add('prev-slide');

        // Cập nhật chỉ số slide hiện hành
        currentIdx = nextIdx;

        // Trượt slide mới vào từ phía bên phải
        sliderImgs[currentIdx].classList.remove('prev-slide');
        sliderImgs[currentIdx].classList.add('active');

        // Cập nhật trạng thái các chấm chỉ báo (Dots indicator)
        dots.forEach(function (dot, idx) {
            if (idx === currentIdx) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // Xóa class của slide cũ sau khi hiệu ứng hoàn tất
        setTimeout(function () {
            sliderImgs[prevIdx].classList.remove('prev-slide');
        }, 800);

        // Cập nhật tiêu đề động cho Banner
        if (heroTitle) {
            heroTitle.innerText = sliderImgs[currentIdx].dataset.title || '';
        }
        
        var heroTitleTag = document.getElementById('hero-title-tag');
        var heroTagContainer = document.getElementById('hero-tag-container');
        var titleTagText = sliderImgs[currentIdx].dataset.titleTag;
        if (heroTitleTag && heroTagContainer) {
            if (titleTagText && titleTagText.trim() !== '') {
                heroTitleTag.innerText = titleTagText;
                heroTagContainer.style.display = '';
            } else {
                heroTitleTag.innerText = '';
                heroTagContainer.style.display = 'none';
            }
        }

        // Cập nhật đường link nút bấm mua sắm của slide đó
        var primaryBtn = document.querySelector('.home-hero__btn--primary');
        if (primaryBtn) {
            var slideLink = sliderImgs[currentIdx].dataset.link;
            primaryBtn.href = (slideLink && slideLink.trim() !== '') ? slideLink : '/products';
        }
    }

    // Bắt đầu tự động chuyển slide
    function startAutoSlide() {
        stopAutoSlide();
        slideInterval = setInterval(function () {
            var next = (currentIdx + 1) % sliderImgs.length;
            showSlide(next);
        }, 4000);
    }

    // Dừng tự động chuyển slide
    function stopAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    }

    // Lắng nghe sự kiện click trên các chấm chỉ báo slide
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var targetIdx = parseInt(this.getAttribute('data-slide-index'));
            showSlide(targetIdx);
            startAutoSlide(); // Reset lại bộ hẹn giờ tự động chuyển slide
        });
    });

    // Kích hoạt tự động trượt khi tải trang
    startAutoSlide();
})();


// ===== Thanh chỉ báo vị trí cuộn ngang tùy chỉnh cho Danh mục nổi bật =====
// Khối danh mục này ẩn thanh cuộn mặc định của trình duyệt để tăng thẩm mỹ,
// do đó ta cần tính toán thủ công độ dài và vị trí thanh cuộn tùy chỉnh để người dùng (đặc biệt là Mobile)
// nhận biết được là danh sách còn tiếp tục kéo ngang qua phải.
(function () {
    var track = document.getElementById('home-categories');
    var scrollbar = document.getElementById('home-categories-scrollbar');
    var thumb = document.getElementById('home-categories-scrollbar-thumb');
    if (!track || !scrollbar || !thumb) return;

    // Cập nhật chiều dài và vị trí của thanh cuộn tùy chỉnh
    function updateThumb() {
        var scrollableWidth = track.scrollWidth - track.clientWidth;

        // Nếu nội dung nằm gọn hoàn toàn trong khung (không cần cuộn) -> Ẩn thanh cuộn
        if (scrollableWidth <= 0) {
            scrollbar.style.visibility = 'hidden';
            return;
        }
        scrollbar.style.visibility = 'visible';

        // Độ rộng nút trượt (thumb) tỉ lệ thuận với lượng hiển thị trong khung so với toàn bộ nội dung
        var thumbWidthPct = Math.max(15, Math.min(100, (track.clientWidth / track.scrollWidth) * 100));
        var maxTranslatePct = 100 - thumbWidthPct;
        var scrolledPct = (track.scrollLeft / scrollableWidth) * maxTranslatePct;

        thumb.style.width = thumbWidthPct + '%';
        thumb.style.transform = 'translateX(' + (scrolledPct / thumbWidthPct * 100) + '%)';
    }

    track.addEventListener('scroll', updateThumb, { passive: true });
    window.addEventListener('resize', updateThumb);
    updateThumb();
})();
