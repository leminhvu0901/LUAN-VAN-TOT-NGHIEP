(function () {
    "use strict";

    let currentForm = null;
    const backdrop = document.getElementById('fail-reason-backdrop');
    const textarea = document.getElementById('fail-reason-textarea');
    const hint = document.getElementById('fail-reason-hint');

    function openModal(form) {
        currentForm = form;
        textarea.value = '';
        hint.classList.add('hidden');
        backdrop.classList.remove('hidden');
        setTimeout(() => textarea.focus(), 50);
    }

    function closeModal() {
        backdrop.classList.add('hidden');
        currentForm = null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-open-fail-modal]');
            if (!btn) return;
            const orderId = btn.getAttribute('data-open-fail-modal');
            const form = document.getElementById('fail-form-' + orderId);
            if (form) openModal(form);
        });

        const cancelBtn = document.getElementById('fail-reason-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        const closeBtn = document.getElementById('fail-reason-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        if (backdrop) {
            backdrop.addEventListener('click', function (event) {
                if (event.target === backdrop) closeModal();
            });
        }

        const confirmBtn = document.getElementById('fail-reason-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const reason = textarea.value.trim();
                if (reason.length < 5) {
                    hint.classList.remove('hidden');
                    textarea.focus();
                    return;
                }
                if (!currentForm) return;
                currentForm.querySelector('input[name="reason"]').value = reason;
                currentForm.submit();
            });
        }
    });
})();
