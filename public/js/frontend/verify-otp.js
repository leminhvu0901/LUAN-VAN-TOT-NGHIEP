document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 4);
            if (pastedData) {
                for (let i = 0; i < pastedData.length; i++) {
                    if (inputs[i]) {
                        inputs[i].value = pastedData[i];
                        if (i < 3) inputs[i + 1].focus();
                    }
                }
            }
        });
    });

    let timeLeft = 58;
    const timerEl = document.getElementById('timer');
    const timerText = document.getElementById('timer-text');
    const resendBtn = document.getElementById('resend-btn');
    let countdownInterval = null;

    if (document.getElementById('otp-modal') && document.getElementById('otp-modal').style.display !== 'none') {
        startTimer();
    }

    function startTimer() {
        if (countdownInterval) clearInterval(countdownInterval);
        timeLeft = 58;
        resendBtn.classList.remove('active');
        timerText.style.display = 'block';
        
        countdownInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                timerText.style.display = 'none';
                resendBtn.classList.add('active');
            } else {
                const seconds = timeLeft < 10 ? '0' + timeLeft : timeLeft;
                if(timerEl) timerEl.textContent = '00:' + seconds;
            }
        }, 1000);
    }
});

// Close modal logic
document.addEventListener('click', function(e) {
    const modal = document.getElementById('otp-modal');
    if (!modal) return;

    const closeBtn = e.target.closest('#close-otp');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    const overlay = e.target.closest('#otp-overlay');
    if (overlay) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }
});
