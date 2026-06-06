// Filter buttons for popular products
document.querySelectorAll('.home-popular__filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.home-popular__filter-btn').forEach(function (b) {
            b.classList.remove('home-popular__filter-btn--active');
        });
        this.classList.add('home-popular__filter-btn--active');

        var filterType = this.innerText.trim();
        var grid = document.querySelector('.home-products-grid');
        if (!grid) return;
        
        var cards = Array.from(grid.querySelectorAll('.home-prod-card'));

        cards.sort(function(a, b) {
            if (filterType === 'Bán chạy') {
                return parseInt(b.getAttribute('data-sold')) - parseInt(a.getAttribute('data-sold'));
            } else if (filterType === 'Mới nhất') {
                return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            } else {
                return parseInt(a.getAttribute('data-original-order')) - parseInt(b.getAttribute('data-original-order'));
            }
        });

        // Re-append sorted cards and only show the first 6
        cards.forEach(function(card, index) {
            if (index < 6) {
                card.style.display = '';
                
                // Toggle badges based on filter
                var hotBadge = card.querySelector('.home-prod-card__badge--hot');
                var newBadge = card.querySelector('.home-prod-card__badge--new');
                
                if (filterType === 'Mới nhất') {
                    if (newBadge) {
                        newBadge.style.display = '';
                        if (hotBadge) hotBadge.style.display = 'none';
                    } else if (hotBadge) {
                        hotBadge.style.display = '';
                    }
                } else {
                    if (hotBadge) {
                        hotBadge.style.display = '';
                        if (newBadge) newBadge.style.display = 'none';
                    } else if (newBadge) {
                        newBadge.style.display = '';
                    }
                }
            } else {
                card.style.display = 'none';
            }
            grid.appendChild(card);
        });
    });
});

// ===== Animated stat counters =====
(function () {
    function animateCounter(el, target, suffix, duration) {
        var start = 0;
        var startTime = null;
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            // Ease out cubic
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.round(eased * target);
            el.textContent = current + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function startCounters() {
        document.querySelectorAll('.home-hero__stat-num').forEach(function (el) {
            var raw = el.textContent.trim(); // e.g. "50+", "4.9★", "30'"
            var match = raw.match(/^([\d.]+)(.*)$/);
            if (!match) return;
            var num = parseFloat(match[1]);
            var suffix = match[2];
            el.textContent = '0' + suffix;
            animateCounter(el, num, suffix, 1600);
        });
    }

    // Delay to match the entrance animation (0.5s delay + some time)
    setTimeout(startCounters, 800);
})();

// Navbar scroll glassmorphism effect
const navbar = document.querySelector('.happy-navbar');
if (navbar) {
    window.addEventListener('scroll', function () {
        if (window.scrollY > 20) {
            navbar.classList.add('navbar--scrolled');
        } else {
            navbar.classList.remove('navbar--scrolled');
        }
    }, { passive: true });
}

// Wishlist toggle
document.querySelectorAll('.home-prod-card__wishlist').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var productId = this.getAttribute('data-id');
        var token = document.querySelector('meta[name="csrf-token"]');
        var _this = this;

        if (!token) {
            alert('Vui lòng đăng nhập để sử dụng tính năng này.');
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = '/login';
            }
            return;
        }

        fetch('/favorite/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content')
            },
            body: JSON.stringify({ product_id: productId })
        })
            .then(response => {
                if (response.status === 401) {
                    const loginModal = document.getElementById('login-modal');
                    if (loginModal) {
                        loginModal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    } else {
                        window.location.href = '/login';
                    }
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    _this.classList.toggle('is-active');

                    // Update drawer UI and badge
                    if (typeof window.updateWishlistUI === 'function') {
                        window.updateWishlistUI(data);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    });
});

// Hero Auto Slider (3s)
(function () {
    var sliderImgs = document.querySelectorAll('#hero-slider .hero-slide-img');
    var heroTitle = document.getElementById('hero-title');
    if (sliderImgs.length === 0) return;
    var currentIdx = 0;
    setInterval(function () {
        var prevIdx = currentIdx;

        // Slide out the current one to the left
        sliderImgs[prevIdx].classList.remove('active');
        sliderImgs[prevIdx].classList.add('prev-slide');

        // Determine next slide
        currentIdx = (currentIdx + 1) % sliderImgs.length;

        // Slide in the new one from the right
        sliderImgs[currentIdx].classList.remove('prev-slide'); // Just in case
        sliderImgs[currentIdx].classList.add('active');

        // Remove prev-slide after transition so it resets position
        setTimeout(function () {
            sliderImgs[prevIdx].classList.remove('prev-slide');
        }, 800);

        // Update title dynamically
        if (heroTitle && sliderImgs[currentIdx].dataset.title) {
            heroTitle.innerText = sliderImgs[currentIdx].dataset.title;
        }
    }, 3000);
})();
