        function toggleFilter() {
            const sidebar = document.querySelector('.p-sidebar');
            if (sidebar) sidebar.classList.toggle('open');
        }

        function updatePriceLabel(val) {
            const formatted = parseInt(val).toLocaleString('vi-VN');
            document.getElementById('price-label').textContent = '10.000đ \u2013 ' + formatted + '\u0111';
            const slider = document.getElementById('price-slider');
            const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${pct}%, #d1d5db ${pct}%, #d1d5db 100%)`;
        }

        window.addEventListener('DOMContentLoaded', () => {
            const slider = document.getElementById('price-slider');
            if (slider) {
                const formatted = parseInt(slider.value).toLocaleString('vi-VN');
                document.getElementById('price-label').textContent = '10.000đ \u2013 ' + formatted + '\u0111';
            }
        });

        function clearSearchAndSubmit() {
            const searchInput = document.getElementById('filter-search');
            if (searchInput) searchInput.value = '';
            const navSearchInput = document.getElementById('search-input');
            if (navSearchInput) navSearchInput.value = '';
            if (window.innerWidth > 640) document.getElementById('filter-form').submit();
        }

        function submitFilterForm() {
            if (window.innerWidth > 640) document.getElementById('filter-form').submit();
        }

        const sortSelect = document.getElementById('sort-select');
        const grid = document.getElementById('product-grid');
        const pillButtons = document.querySelectorAll('#product-pill-filters .home-popular__filter-btn');
        let currentPillFilter = 'all';

        function applySortAndFilter() {
            if (!sortSelect || !grid) return;
            const sortBy = sortSelect.value;
            const cards = Array.from(grid.querySelectorAll('.p-product-card'));

            cards.sort((a, b) => {
                if (sortBy === 'popular') return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0);
                if (sortBy === 'price-asc') return parseFloat(a.dataset.priceVal || 0) - parseFloat(b.dataset.priceVal || 0);
                if (sortBy === 'price-desc') return parseFloat(b.dataset.priceVal || 0) - parseFloat(a.dataset.priceVal || 0);
                if (sortBy === 'newest') return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0);
                if (sortBy === 'rating') return parseFloat(b.dataset.ratingVal || 0) - parseFloat(a.dataset.ratingVal || 0);
                return 0;
            });

            let visibleCount = 0;
            cards.forEach(card => {
                grid.appendChild(card);
                let isMatch = true;
                if (currentPillFilter === 'hot') isMatch = card.dataset.isHot === '1';
                else if (currentPillFilter === 'new') isMatch = card.dataset.isNew === '1';
                card.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;

                const hotBadge = card.querySelector('.home-prod-card__badge--hot');
                const newBadge = card.querySelector('.home-prod-card__badge--new');
                if (sortBy === 'newest') {
                    if (newBadge) { newBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; }
                    else if (hotBadge) hotBadge.style.display = '';
                } else {
                    if (hotBadge) { hotBadge.style.display = ''; if (newBadge) newBadge.style.display = 'none'; }
                    else if (newBadge) newBadge.style.display = '';
                }
            });

            let emptyMsg = document.getElementById('empty-product-msg');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.id = 'empty-product-msg';
                    emptyMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;';
                    emptyMsg.textContent = 'Không tìm thấy sản phẩm nào phù hợp với bộ lọc.';
                    grid.appendChild(emptyMsg);
                } else { emptyMsg.style.display = ''; grid.appendChild(emptyMsg); }
            } else if (emptyMsg) { emptyMsg.style.display = 'none'; }
        }

        if (sortSelect && grid) {
            sortSelect.addEventListener('change', applySortAndFilter);
            pillButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    pillButtons.forEach(b => b.classList.remove('home-popular__filter-btn--active'));
                    this.classList.add('home-popular__filter-btn--active');
                    currentPillFilter = this.dataset.filter;
                    applySortAndFilter();
                });
            });
            applySortAndFilter();
        }
