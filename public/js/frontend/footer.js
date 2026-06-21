document.addEventListener('DOMContentLoaded', function () {
    const headers = document.querySelectorAll('.footer-column h6');
    headers.forEach(header => {
        header.addEventListener('click', function () {
            if (window.innerWidth <= 640) {
                const column = this.closest('.footer-column');
                if (column) column.classList.toggle('open');
            }
        });
    });
});
