function applyFallbackImage(image) {
    if (image.dataset.fallbackApplied === "true") return;

    image.dataset.fallbackApplied = "true";
    image.src = image.dataset.fallbackSrc;
}

function initOrderShowPage() {
    const printButton = document.getElementById("order-print-btn");

    if (printButton) {
        printButton.addEventListener("click", function () {
            window.print();
        });
    }

    document.querySelectorAll("img[data-fallback-src]").forEach((image) => {
        image.addEventListener("error", function () {
            applyFallbackImage(this);
        });

        if (image.complete && image.naturalWidth === 0) {
            applyFallbackImage(image);
        }
    });
}

initOrderShowPage();
