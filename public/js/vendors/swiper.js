// =========================================================================
// KHỞI TẠO BỘ TRÌNH CHIẾU CAROUSEL (SWIPER JS INITIALIZATION)
// =========================================================================

/**
 * Hàm tự động phát hiện và cấu hình các khối Swiper Slider trên giao diện
 */
function initializeSwiperCarousels() {
	// Tìm tất cả các đối tượng HTML có class là swiper-container
	const swiperContainers = document.querySelectorAll('.swiper-container');

	swiperContainers.forEach((swiperContainer) => {
		// Đọc các tham số cấu hình linh hoạt được thiết lập qua thuộc tính data-* của thẻ HTML
		const speed = swiperContainer.getAttribute('data-speed') || 400; // Tốc độ trượt
		const spaceBetween = swiperContainer.getAttribute('data-space-between') || 100; // Khoảng cách giữa các slide
		const paginationEnabled = swiperContainer.getAttribute('data-pagination') === 'true'; // Bật phân trang dạng chấm tròn
		const navigationEnabled = swiperContainer.getAttribute('data-navigation') === 'true'; // Bật nút chuyển Tiếp/Lùi
		const autoplayEnabled = swiperContainer.getAttribute('data-autoplay') === 'true'; // Bật tự động chuyển slide
		const autoplayDelay = swiperContainer.getAttribute('data-autoplay-delay') || 3000; // Độ trễ tự động chuyển (mili-giây)
		const paginationType = swiperContainer.getAttribute('data-pagination-type') || 'bullets'; // Định dạng chấm tròn hoặc số
		const effect = swiperContainer.getAttribute('data-effect') || 'slide'; // Hiệu ứng chuyển động (trượt/mờ dần)

		// Phân tách cấu hình hiển thị responsive (Breakpoints) dạng chuỗi JSON
		const breakpointsData = swiperContainer.getAttribute('data-breakpoints');
		let breakpoints = {};
		if (breakpointsData) {
			try {
				breakpoints = JSON.parse(breakpointsData);
			} catch (error) {
				console.error('Lỗi phân tích cú pháp breakpoints JSON:', error);
			}
		}

		// Khởi tạo đối tượng tùy chọn cấu hình cơ bản cho Swiper
		const swiperOptions = {
			speed: parseInt(speed),
			spaceBetween: parseInt(spaceBetween),
			breakpoints: breakpoints,
			effect: effect,
		};

		// Cấu hình hiệu ứng mờ dần (Fade) nếu được yêu cầu
		if (effect === 'fade') {
			swiperOptions.fadeEffect = {
				crossFade: true,
			};
		}

		// Khởi tạo thanh phân trang (Pagination) dưới các slide
		if (paginationEnabled) {
			const paginationEl = swiperContainer.querySelector('.swiper-pagination');
			if (paginationEl) {
				swiperOptions.pagination = {
					el: paginationEl,
					type: paginationType,
					dynamicBullets: true, // Tự động co nhỏ chấm khi có quá nhiều slide
					clickable: true, // Cho phép click vào chấm để chuyển đến slide đó
				};

				// Tự thiết kế phân trang hiển thị dạng số thứ tự (vd: 1 2 3 4...)
				if (paginationType === 'custom') {
					swiperOptions.pagination.renderCustom = function (swiper, current, total) {
						var text = '';
						for (let i = 1; i <= total; i++) {
							if (current == i) {
								text += `<span class="swiper-pagination-numbers swiper-pagination-numbers-active">${i}</span>`;
							} else {
								text += `<span class="swiper-pagination-numbers">${i}</span>`;
							}
						}
						return text;
					};
				}
			}
		}

		// Khởi tạo các nút điều hướng bấm chuyển slide
		if (navigationEnabled) {
			swiperOptions.navigation = {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev',
			};
		} else {
			// Nếu không kích hoạt thì ẩn hoàn toàn khung điều hướng bằng CSS class
			const navigationEl = swiperContainer.querySelector('.swiper-navigation');
			if (navigationEl) {
				navigationEl.classList.add('swiper-navigation-hidden');
			}
		}

		// Khởi tạo tự động chuyển slide theo thời gian
		if (autoplayEnabled) {
			swiperOptions.autoplay = {
				delay: parseInt(autoplayDelay),
			};
		}

		// Khởi chạy Swiper thật sự cho thẻ container đó
		new Swiper(swiperContainer, swiperOptions);
	});
}

// Kích hoạt chạy khởi tạo sau khi nạp trang
initializeSwiperCarousels();
