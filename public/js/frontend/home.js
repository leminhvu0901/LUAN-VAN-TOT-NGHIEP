// ===== Filter buttons for popular products (trang chủ) =====
(function () {
    var pillButtons = document.querySelectorAll('#home-pill-filters .home-popular__filter-btn');
    var grid = document.querySelector('.home-products-grid');
    var currentFilter = 'all';

    function applyHomeFilter() {
        if (!grid) return;

        var cards = Array.from(grid.querySelectorAll('.home-prod-card'));
        var visibleCount = 0;

        // Sort cards based on filter
        cards.sort(function (a, b) {
            if (currentFilter === 'hot') {
                return parseInt(b.getAttribute('data-sold') || 0) - parseInt(a.getAttribute('data-sold') || 0);
            } else if (currentFilter === 'new') {
                return parseInt(b.getAttribute('data-date') || 0) - parseInt(a.getAttribute('data-date') || 0);
            } else {
                // 'all': sort by composite score (60% sales + 40% rating)
                return parseFloat(b.getAttribute('data-score') || 0) - parseFloat(a.getAttribute('data-score') || 0);
            }
        });

        // Filter & show max 6 matching cards
        var shown = 0;
        cards.forEach(function (card) {
            grid.appendChild(card);

            var isMatch = true;
            if (currentFilter === 'hot') {
                isMatch = card.getAttribute('data-is-hot') === '1';
            } else if (currentFilter === 'new') {
                isMatch = card.getAttribute('data-is-new') === '1';
            }

            if (isMatch && shown < 7) {
                card.style.display = '';
                shown++;
                visibleCount++;
            } else {
                card.style.display = 'none';
            }

            // Badge visibility
            var hotBadge = card.querySelector('.home-prod-card__badge--hot');
            var newBadge = card.querySelector('.home-prod-card__badge--new');
            if (currentFilter === 'new') {
                if (hotBadge) hotBadge.style.display = 'none';
                if (newBadge) newBadge.style.display = '';
            } else {
                if (hotBadge) hotBadge.style.display = '';
                if (newBadge) newBadge.style.display = hotBadge ? 'none' : '';
            }
        });

        // Show/hide empty message
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

    pillButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            pillButtons.forEach(function (b) {
                b.classList.remove('home-popular__filter-btn--active');
            });
            this.classList.add('home-popular__filter-btn--active');
            currentFilter = this.getAttribute('data-filter');
            applyHomeFilter();
        });
    });

    // Initial render
    applyHomeFilter();
})();


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

// Wishlist toggle is handled globally in main.js via inline onclick handlers

// Hero Auto Slider (4s) with Dot navigation
(function () {
    var sliderImgs = document.querySelectorAll('#hero-slider .hero-slide-img');
    var heroTitle = document.getElementById('hero-title');
    var dots = document.querySelectorAll('#hero-slider .hero-banner__dot');
    if (sliderImgs.length === 0) return;
    var currentIdx = 0;
    var slideInterval = null;

    function showSlide(nextIdx) {
        if (nextIdx === currentIdx) return;
        var prevIdx = currentIdx;

        // Slide out the current one to the left
        sliderImgs[prevIdx].classList.remove('active');
        sliderImgs[prevIdx].classList.add('prev-slide');

        // Update index
        currentIdx = nextIdx;

        // Slide in the new one from the right
        sliderImgs[currentIdx].classList.remove('prev-slide');
        sliderImgs[currentIdx].classList.add('active');

        // Update active class on dots
        dots.forEach(function (dot, idx) {
            if (idx === currentIdx) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // Remove prev-slide class after animation finishes
        setTimeout(function () {
            sliderImgs[prevIdx].classList.remove('prev-slide');
        }, 800);

        // Update title dynamically
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

        // Update link dynamically
        var primaryBtn = document.querySelector('.home-hero__btn--primary');
        if (primaryBtn) {
            var slideLink = sliderImgs[currentIdx].dataset.link;
            primaryBtn.href = (slideLink && slideLink.trim() !== '') ? slideLink : '/products';
        }
    }

    function startAutoSlide() {
        stopAutoSlide();
        slideInterval = setInterval(function () {
            var next = (currentIdx + 1) % sliderImgs.length;
            showSlide(next);
        }, 4000);
    }

    function stopAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    }

    // Attach click listener to dots
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var targetIdx = parseInt(this.getAttribute('data-slide-index'));
            showSlide(targetIdx);
            startAutoSlide(); // Reset auto slide timer
        });
    });

    // Start auto slider on load
    startAutoSlide();
})();
