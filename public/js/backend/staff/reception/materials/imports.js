(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        const page = document.getElementById("materials-imports-page");
        if (!page) return;

        page.addEventListener("click", function (event) {
            const consumeButton = event.target.closest(".js-consume-batch");
            if (!consumeButton) return;

            const quantityInput = document.getElementById("consume-batch-quantity");
            const unitText = document.getElementById("consume-batch-unit");
            const maxText = document.getElementById("consume-batch-max");
            const idText = document.getElementById("consume-batch-id");
            const form = document.getElementById("form-consume-batch");
            const formAction = document.getElementById("consume-form-action");
            const maxQuantity = document.getElementById("consume-max-quantity");

            if (quantityInput) {
                quantityInput.value = "";
                quantityInput.max = consumeButton.dataset.max;
            }
            if (unitText) unitText.textContent = consumeButton.dataset.unit;
            if (maxText) maxText.textContent = consumeButton.dataset.max;
            if (idText) idText.textContent = `LOT-${String(consumeButton.dataset.id).padStart(4, "0")}`;
            if (form) form.action = consumeButton.dataset.action;
            if (formAction) formAction.value = consumeButton.dataset.action;
            if (maxQuantity) maxQuantity.value = consumeButton.dataset.max;
            MaterialsCommon.openModal("modal-consume-batch");
        });
    });
})();
