// Filter buttons for popular products
document.querySelectorAll('.home-popular__filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.home-popular__filter-btn').forEach(function (b) {
            b.classList.remove('home-popular__filter-btn--active');
        });
        this.classList.add('home-popular__filter-btn--active');
    });
});

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
        this.classList.toggle('is-active');
    });
});

// Hero Auto Slider (3s)
(function() {
    var sliderImgs = document.querySelectorAll('#hero-slider .hero-slide-img');
    var heroTitle = document.getElementById('hero-title');
    if (sliderImgs.length === 0) return;
    var currentIdx = 0;
    setInterval(function() {
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
        setTimeout(function() {
            sliderImgs[prevIdx].classList.remove('prev-slide');
        }, 800);
        
        // Update title dynamically
        if (heroTitle && sliderImgs[currentIdx].dataset.title) {
            heroTitle.innerText = sliderImgs[currentIdx].dataset.title;
        }
    }, 3000);
})();
