'use strict';

(function () {
	// Multi level menu dropdown
	const dropdownLinks = document.querySelectorAll('.dropdown-menu a.dropdown-toggle');
	dropdownLinks.forEach(function (dropdownLink) {
		dropdownLink.addEventListener('click', function (e) {
			if (!this.nextElementSibling.classList.contains('show')) {
				const parentDropdownMenu = this.closest('.dropdown-menu');
				const currentlyOpenSubMenus = parentDropdownMenu.querySelectorAll('.show');
				currentlyOpenSubMenus.forEach(function (openSubMenu) {
					openSubMenu.classList.remove('show');
				});
			}

			const subMenu = this.nextElementSibling;
			subMenu.classList.toggle('show');

			const parentDropdown = this.closest('li.nav-item.dropdown.show');
			if (parentDropdown) {
				parentDropdown.addEventListener('hidden.bs.dropdown', function (e) {
					const dropdownSubMenus = document.querySelectorAll('.dropdown-submenu .show');
					dropdownSubMenus.forEach(function (dropdownSubMenu) {
						dropdownSubMenu.classList.remove('show');
					});
				});
			}

			e.stopPropagation();
		});
	});

	// Default Tooltip
	var tooltipTriggerElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');

	if (tooltipTriggerElements.length) {
		tooltipTriggerElements.forEach(function (element) {
			new bootstrap.Tooltip(element);
		});
	}

	// Increment Value
	function incrementValue(e) {
		e.preventDefault();
		var target = e.target;
		var fieldName = target.getAttribute('data-field');
		var parent = target.closest('div');
		var inputField = parent.querySelector('input[name="' + fieldName + '"]');
		var currentVal = parseInt(inputField.value, 10) || 0;

		inputField.value = currentVal + 1;
	}

	function decrementValue(e) {
		e.preventDefault();
		var target = e.target;
		var fieldName = target.getAttribute('data-field');
		var parent = target.closest('div');
		var inputField = parent.querySelector('input[name="' + fieldName + '"]');
		var currentVal = parseInt(inputField.value, 10) || 0;

		if (currentVal > 0) {
			inputField.value = currentVal - 1;
		}
	}

	document.querySelectorAll('.input-group').forEach(function (group) {
		group.addEventListener('click', function (e) {
			if (e.target.classList.contains('button-plus')) {
				incrementValue(e);
			} else if (e.target.classList.contains('button-minus')) {
				decrementValue(e);
			}
		});
	});

	// Default Popover
	var popoverTriggerElements = document.querySelectorAll('[data-bs-toggle="popover"]');

	if (popoverTriggerElements.length) {
		popoverTriggerElements.forEach(function (element) {
			new bootstrap.Popover(element);
		});
	}

	// Rater
	var starRating1;
	var raterElements = document.querySelectorAll('.rater');

	if (typeof window.raterJs === 'function' && raterElements.length) {
		raterElements.forEach(function (element, index) {
			starRating1 = raterJs({
				starSize: 20,
				element: element,
				rateCallback: function rateCallback(rating, done) {
					this.setRating(rating);
					done();
				},
			});
		});
	}

	// Flatpickr
	var flatpickrElements = document.querySelectorAll('.flatpickr');

	if (flatpickrElements.length && typeof window.flatpickr === 'function') {
		flatpickrElements.forEach(function (element) {
			flatpickr(element, {
				disableMobile: true,
				// You can specify a default date here if needed
				// defaultDate: '2023-08-01',
			});
		});
	}

	// Stop event for dropdown
	var stopeventElements = document.querySelectorAll('.stopevent');

	if (stopeventElements.length) {
		stopeventElements.forEach(function (element) {
			element.addEventListener('off.bs.collapse.data-api', function (e) {
				e.stopPropagation();
			});
		});
	}

	// Check all for checkbox
	const checkAll = document.querySelector('#checkAll');

	if (checkAll) {
		checkAll.addEventListener('click', function () {
			var checkboxes = document.querySelectorAll('input[type="checkbox"]');
			checkboxes.forEach(function (checkbox) {
				if (checkbox !== checkAll) {
					checkbox.checked = checkAll.checked;
				}
			});
		});
	}

	// Live Alert Placeholder
	var liveAlertPlaceholder = document.getElementById('liveAlertPlaceholder');

	if (liveAlertPlaceholder) {
		var alert = function (message, type) {
			var wrapper = document.createElement('div');
			wrapper.innerHTML = `
        <div class="alert alert-${type} alert-dismissible" role="alert">
          <div>${message}</div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
			liveAlertPlaceholder.append(wrapper);
		};

		var alertTrigger = document.getElementById('liveAlertBtn');

		if (alertTrigger) {
			alertTrigger.addEventListener('click', function () {
				alert('Nice, you triggered this alert message!', 'success');
			});
		}
	}

	// Price Range Slider
	var priceRangeSlider = document.getElementById('priceRange');

	if (priceRangeSlider && window.noUiSlider && window.wNumb) {
		noUiSlider.create(priceRangeSlider, {
			connect: true,
			behaviour: 'tap',
			start: [49, 199],
			range: {
				min: [6],
				max: [300],
			},
			format: wNumb({
				decimals: 1,
				thousand: '.',
				prefix: '$',
			}),
		});

		var priceRangeValueElement = document.getElementById('priceRange-value');

		priceRangeSlider.noUiSlider.on('update', function (values) {
			priceRangeValueElement.innerHTML = values.join(' - ');
		});
	}

	// File Input
	var fileInputs = document.querySelectorAll('.file-input');

	if (fileInputs.length) {
		fileInputs.forEach(function (input) {
			input.addEventListener('change', function () {
				var curElement = input.parentElement.parentElement.querySelector('.image');
				var reader = new FileReader();

				reader.onload = function (e) {
					curElement.setAttribute('src', e.target.result);
				};

				reader.readAsDataURL(input.files[0]);
			});
		});
	}

	const toastTrigger = document.getElementById('liveToastBtn');
	const toastLiveExample = document.getElementById('liveToast');

	// Check if both elements exist
	if (toastTrigger && toastLiveExample) {
		toastTrigger.addEventListener('click', () => {
			// Show the toast
			toastLiveExample.classList.add('show');
			toastLiveExample.classList.remove('hide');

			// Hide the toast after a certain time (e.g., 5 seconds)
			setTimeout(() => {
				toastLiveExample.classList.remove('show');
				toastLiveExample.classList.add('hide');
			}, 5000);
		});
	}
})();

// Input tags (Tagify) Manasvi
const tagsInput = document.querySelector('input[name=tags]');
if (tagsInput && window.Tagify) {
	new Tagify(tagsInput);
}

// Wishlist & Cart Global Functions
window.updateWishlistUI = function(data) {
    if (!data || !data.success) return;
    
    // 1. Update badges and counters
    var badge = document.getElementById('wishlist-badge');
    var subtitle = document.querySelector('.wl-drawer__subtitle');
    if (badge) badge.innerText = data.count;
    if (subtitle) subtitle.innerText = data.count + ' sản phẩm đã lưu';
    
    // 2. Update drawer list
    var listBody = document.getElementById('wishlist-list');
    if (listBody) {
        if (data.count === 0) {
            listBody.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 2rem 1rem;"><p>Bạn chưa lưu sản phẩm nào.</p></div>';
        } else {
            let html = '';
            data.items.forEach(item => {
                let formattedPrice = new Intl.NumberFormat('vi-VN').format(item.base_price) + 'đ';
                html += `
                <div class="wl-item">
                    <img src="/images/${item.image}" alt="${item.name}" class="wl-item__img" onerror="this.src='/images/products/placeholder.jpg'">
                    <div class="wl-item__info">
                        <p class="wl-item__name">${item.name}</p>
                        <div class="wl-item__rating">
                            <span class="wl-item__stars">★★★★★</span>
                            <span class="wl-item__rating-value">5.0</span>
                        </div>
                        <span class="wl-item__price">${formattedPrice}</span>
                    </div>
                    <div class="wl-item__actions">
                        <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn" onclick="removeFromWishlist(${item.id})">
                            <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button title="Thêm vào giỏ" class="wl-item__cart-btn" onclick="addToCart(${item.id})">
                            <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                            </svg>
                        </button>
                    </div>
                </div>`;
            });
            listBody.innerHTML = html;
        }
    }
};

window.removeFromWishlist = function(productId) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    fetch('/favorite/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.success) {
            updateWishlistUI(data);
            
            // Untoggle heart icon on homepage if exists
            var heartBtn = document.querySelector('.home-prod-card__wishlist[data-id="' + productId + '"]');
            if (heartBtn) {
                heartBtn.classList.remove('is-active');
            }
            
            // Untoggle heart icon on product detail page if exists
            var pdHeartBtn = document.querySelector('#pd-wishlist-btn[data-id="' + productId + '"]');
            if (pdHeartBtn) {
                pdHeartBtn.classList.remove('is-active');
            }
        }
    })
    .catch(error => console.error('Error:', error));
};

window.toggleFavorite = function(btn, productId) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    fetch('/favorite/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => {
        if (res.status === 401) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }
        return res.json();
    })
    .then(data => {
        if (data && data.success) {
            btn.classList.toggle('is-active');
            
            // Pop animation
            btn.classList.remove('badge-pop');
            void btn.offsetWidth;
            btn.classList.add('badge-pop');
            
            if (typeof updateWishlistUI === 'function') {
                updateWishlistUI(data);
            }
        }
    })
    .catch(error => console.error('Error:', error));
};

// --- CART LOGIC ---
const updateCartUI = (data) => {
    if (!data || !data.success) return;
    
    // Update badge
    var badge = document.getElementById('cart-badge');
    if (badge) badge.innerText = data.count;
    
    // Update drawer subtitle
    var subtitle = document.getElementById('cart-drawer-subtitle');
    if (subtitle) subtitle.innerText = data.count + ' sản phẩm';
    
    // Update total
    var totalEl = document.getElementById('cart-drawer-total');
    if (totalEl) totalEl.innerText = data.formatted_total;
    
    // Update list
    var list = document.getElementById('cart-list');
    if (list) {
        if (data.count === 0) {
            list.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 2rem 1rem;"><p>Giỏ hàng của bạn đang trống.</p></div>';
        } else {
            let html = '';
            data.items.forEach(item => {
                let formattedPrice = new Intl.NumberFormat('vi-VN').format(item.unit_price) + 'đ';

                // Build option tags (size, đường, đá)
                let optionTags = '';
                if (item.size_name) {
                    optionTags += `<span style="display:inline-flex;align-items:center;gap:3px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:600;">Size ${item.size_name}</span>`;
                }
                const sugarMap = {'100':'100% đường','70':'70% đường','50':'50% đường','0':'Không đường'};
                const iceMap = {'full':'Đá riêng','normal':'Đá chung','less':'Ít đá','none':'Không đá'};
                if (item.sugar_level && sugarMap[item.sugar_level]) {
                    optionTags += `<span style="display:inline-flex;align-items:center;gap:3px;background:#fefce8;color:#92400e;border:1px solid #fde68a;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:600;">${sugarMap[item.sugar_level]}</span>`;
                }
                if (item.ice_level && iceMap[item.ice_level]) {
                    optionTags += `<span style="display:inline-flex;align-items:center;gap:3px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:600;">${iceMap[item.ice_level]}</span>`;
                }
                
                // Hiển thị toppings
                if (item.toppings && item.toppings.length > 0) {
                    let tops = item.toppings.map(t => t.name).join(', ');
                    optionTags += `<span style="display:inline-flex;align-items:center;gap:3px;background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:600;">+ ${tops}</span>`;
                }

                html += `
                <div style="display: flex; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
                    <img src="/images/${item.image}" alt="${item.name}" onerror="this.src='/images/products/placeholder.jpg'" style="width: 72px; height: 72px; object-fit: cover; border-radius: 0.5rem; flex-shrink: 0; background: #f9fafb;">
                    <div style="flex: 1; display: flex; flex-direction: column;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0; margin-bottom: 0.25rem;">${item.name}</h4>
                        ${optionTags ? `<div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:0.35rem;">${optionTags}</div>` : ''}
                        <span style="font-size: 0.875rem; font-weight: 600; color: #10b981;">${formattedPrice}</span>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            <div style="display: flex; align-items: center; border: 1px solid #e5e7eb; border-radius: 0.375rem; overflow: hidden;">
                                <button onclick="updateCartItem(${item.id}, ${item.quantity - 1})" style="width: 28px; height: 28px; background: #f9fafb; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center;">-</button>
                                <span style="width: 32px; text-align: center; font-size: 0.875rem;">${item.quantity}</span>
                                <button onclick="updateCartItem(${item.id}, ${item.quantity + 1})" style="width: 28px; height: 28px; background: #f9fafb; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center;">+</button>
                            </div>
                            <button onclick="removeFromCart(${item.id})" style="border: none; background: transparent; color: #ef4444; font-size: 0.875rem; cursor: pointer; text-decoration: underline;">Xóa</button>
                        </div>
                    </div>
                </div>`;
            });
            list.innerHTML = html;
        }
    }
};

window.loadCart = function() {
    fetch('/cart')
        .then(res => res.json())
        .then(updateCartUI)
        .catch(err => console.error(err));
};

window.addToCart = function(productId, quantity = 1, options = {}) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity,
            size_name: options.size_name || null,
            sugar_level: options.sugar_level || null,
            ice_level: options.ice_level || null,
            toppings: options.toppings || []
        })
    })
    .then(res => {
        if (res.status === 401) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        updateCartUI(data);
        // Pop animation on cart icon
        var badge = document.getElementById('cart-badge');
        if (badge) {
            badge.classList.remove('badge-pop');
            void badge.offsetWidth;
            badge.classList.add('badge-pop');
        }
    })
    .catch(err => console.error(err));
};

window.addAllToCart = function() {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    fetch('/cart/add-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        }
    })
    .then(res => {
        if (res.status === 401) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        if (data.success === false) {
            alert(data.message || 'Có lỗi xảy ra');
            return;
        }
        updateCartUI(data);
        alert('Đã thêm tất cả sản phẩm yêu thích vào giỏ hàng!');
        
        // Pop animation on cart icon
        var badge = document.getElementById('cart-badge');
        if (badge) {
            badge.classList.remove('badge-pop');
            void badge.offsetWidth;
            badge.classList.add('badge-pop');
        }
    })
    .catch(err => console.error(err));
};

window.removeFromCart = function(itemId) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    fetch('/cart/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(res => {
        if (res.status === 401) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        updateCartUI(data);
    })
    .catch(err => console.error(err));
};

window.updateCartItem = function(itemId, quantity) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;
    
    if (quantity < 1) {
        removeFromCart(itemId);
        return;
    }

    fetch('/cart/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token.getAttribute('content')
        },
        body: JSON.stringify({ item_id: itemId, quantity: quantity })
    })
    .then(res => {
        if (res.status === 401) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        updateCartUI(data);
    })
    .catch(err => console.error(err));
};

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
});
