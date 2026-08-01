// Chatbox trả lời nhanh (quick-reply, không AI) — chỉ xử lý hiển thị/tương tác,
// toàn bộ nội dung câu trả lời đã được render sẵn từ Blade (config/quick_chat.php +
// QuickChatboxComposer). Không có fetch/AJAX, không lưu lịch sử hội thoại.
(function () {
    const $ = (id) => document.getElementById(id);
    const STORAGE_KEY = 'quick_chatbox_open';

    function safeSessionGet(key) {
        try { return sessionStorage.getItem(key); } catch (e) { return null; }
    }
    function safeSessionSet(key, value) {
        try { sessionStorage.setItem(key, value); } catch (e) { /* private mode: bỏ qua */ }
    }

    const root = $('quick-chatbox');
    const toggleBtn = $('quick-chatbox-toggle');
    const panel = $('quick-chatbox-panel');
    const messages = $('quick-chatbox-messages');
    const answerArea = $('quick-chatbox-answer-area');
    if (!root || !toggleBtn || !panel) return;

    // .l-bottom-nav chỉ tồn tại (và chỉ hiện dưới mobile) ở 1 số trang (home/products/orders/profile),
    // KHÔNG suy luận qua class trên <body> vì checkout.blade.php cũng có class 'profile-body' nhưng
    // không có thanh này — kiểm tra thẳng sự tồn tại của phần tử cho chắc chắn.
    if (document.querySelector('.l-bottom-nav')) {
        root.classList.add('quick-chatbox--has-bottom-nav');
    }

    const typingDelay = parseInt(root.dataset.typingDelay, 10) || 400;
    let typingIndicatorEl = null;
    // Khối 5 nút gợi ý danh mục (Cà phê/Trà sữa/...) chỉ nên hiện 1 lần trong phiên chat — hiện lại
    // mỗi lần câu hỏi rơi vào nhánh "mơ hồ" sẽ lặp lại y hệt, gây rối mắt trong luồng hội thoại.
    let categorySuggestionsShown = false;

    function appendMessageTo(container, text, variant) {
        if (!container) return;
        const el = document.createElement('div');
        el.className = 'quick-chatbox__message quick-chatbox__message--' + variant;
        el.textContent = text; // luôn dùng textContent, không innerHTML
        container.appendChild(el);
        // Không tự cuộn xuống cuối ở đây — việc cuộn (nếu cần) chỉ xảy ra đúng 1 lần, ngay sau khi
        // thêm tin nhắn CỦA KHÁCH, qua scrollQuestionIntoView() bên dưới.
        return el;
    }

    // Cuộn vừa đủ để đưa câu hỏi vừa gửi của khách lên gần đầu vùng chat (cách mép trên ~16px) —
    // KHÔNG cuộn xuống đáy. Chỉ gọi đúng 1 lần, ngay sau khi thêm tin nhắn của khách; không gọi lại
    // khi hiện "Đang trả lời...", khi bot trả lời xong, hay khi thêm card sản phẩm/khuyến mãi.
    function scrollQuestionIntoView(container, questionElement) {
        if (!container || !questionElement) return;

        const containerRect = container.getBoundingClientRect();
        const questionRect = questionElement.getBoundingClientRect();

        const targetTop = container.scrollTop + questionRect.top - containerRect.top - 16;

        container.scrollTo({
            top: Math.max(0, targetTop),
            behavior: 'smooth'
        });
    }

    // Giữ tên hàm cũ để không phải sửa các chỗ gọi sẵn có (selectTopic) — luôn nhắm vào khung
    // tin nhắn nhỏ phía trên (lời chào + tên chủ đề đã bấm), KHÔNG dùng cho hội thoại tự do.
    function appendMessage(text, variant) {
        return appendMessageTo(messages, text, variant);
    }

    function showTyping() {
        typingIndicatorEl = appendMessage('Đang trả lời...', 'typing');
    }

    function hideTyping() {
        if (typingIndicatorEl && typingIndicatorEl.parentNode) {
            typingIndicatorEl.parentNode.removeChild(typingIndicatorEl);
        }
        typingIndicatorEl = null;
    }

    // Hiện đúng 1 screen tĩnh (menu hoặc 1 chủ đề), đồng thời ẩn khu vực hội thoại tự do và hiện
    // lại khung tin nhắn nhỏ (lời chào chỉ có ý nghĩa khi đang duyệt menu/chủ đề tĩnh).
    function showScreen(key) {
        if (answerArea) answerArea.hidden = true;
        if (messages) messages.hidden = false;
        document.querySelectorAll('.quick-chatbox__screen').forEach((el) => {
            el.hidden = el.id !== 'quick-chatbox-screen-' + key;
        });
    }

    // Chuyển sang "chế độ hội thoại": ẩn mọi screen tĩnh VÀ ẩn luôn khung tin nhắn nhỏ (không để
    // lời chào bị "ghim cứng" chiếm chỗ trong lúc chat) — lời chào chỉ được chèn 1 lần làm dòng
    // đầu tiên của chính cuộc hội thoại, cuộn tự nhiên theo nội dung như mọi tin nhắn khác.
    function showAnswerArea() {
        document.querySelectorAll('.quick-chatbox__screen').forEach((el) => {
            el.hidden = true;
        });
        if (messages) messages.hidden = true;
        if (answerArea) {
            answerArea.hidden = false;
            if (answerArea.children.length === 0 && root.dataset.greeting) {
                appendMessageTo(answerArea, root.dataset.greeting, 'bot');
            }
        }
    }

    // Khung tin nhắn nhỏ phía trên chỉ nên hiển thị lời chào + tối đa 1 lựa chọn ĐANG XEM —
    // KHÔNG phải nhật ký toàn bộ các lần đã bấm (bấm nhiều lần trước đây sẽ làm bong bóng cộng
    // dồn vô hạn, đẩy nội dung màn hình thật xuống gần như biến mất). Luôn xóa về đúng lời chào
    // gốc (phần tử đầu tiên) trước khi thêm lựa chọn mới.
    function resetMessagesToGreeting() {
        if (!messages) return;
        while (messages.children.length > 1) {
            messages.removeChild(messages.lastChild);
        }
    }

    function selectTopic(topicKey, labelText) {
        resetMessagesToGreeting();
        appendMessage(labelText, 'user');
        showTyping();
        setTimeout(() => {
            hideTyping();
            showScreen(topicKey);
        }, typingDelay);
    }

    function openPanel() {
        panel.hidden = false;
        // display cần được set trước 1 frame rồi mới thêm class animate, giống pattern
        // mở drawer trong navbar.js (tránh transition bị "nhảy" do bắt đầu từ display:none).
        requestAnimationFrame(() => {
            panel.classList.add('is-open');
        });
        toggleBtn.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        safeSessionSet(STORAGE_KEY, '1');
        const firstFocusable = panel.querySelector('.quick-chatbox__option, .quick-chatbox__close');
        if (firstFocusable) firstFocusable.focus();
    }

    function closePanel() {
        panel.classList.remove('is-open');
        toggleBtn.classList.remove('is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        safeSessionSet(STORAGE_KEY, '0');
        setTimeout(() => { panel.hidden = true; }, 250);
        toggleBtn.focus();
    }

    toggleBtn.addEventListener('click', () => {
        if (panel.hidden) openPanel(); else closePanel();
    });

    // Event delegation cho mọi thao tác trong panel — không gắn listener lặp lại cho từng nút.
    panel.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close"]')) {
            closePanel();
            return;
        }
        if (e.target.closest('[data-action="home"]')) {
            resetMessagesToGreeting();
            showScreen('menu');
            return;
        }
        const backBtn = e.target.closest('[data-action="back"]');
        if (backBtn) {
            resetMessagesToGreeting();
            showScreen(backBtn.dataset.back || 'menu');
            return;
        }
        const loginBtn = e.target.closest('[data-action="open-login"]');
        if (loginBtn) {
            const loginModal = document.getElementById('login-modal');
            if (loginModal) {
                loginModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else if (loginBtn.dataset.loginUrl) {
                window.location.href = loginBtn.dataset.loginUrl;
            }
            return;
        }
        const optionBtn = e.target.closest('.quick-chatbox__option');
        if (optionBtn) {
            if (optionBtn.dataset.intent) {
                // Gợi ý động (từ kết quả câu hỏi tự do) — hỏi lại qua endpoint với intent đã biết,
                // bỏ qua bước chấm điểm.
                submitAsk({ intent: optionBtn.dataset.intent }, optionBtn.textContent.trim());
            } else if (optionBtn.dataset.question) {
                // Nút gợi ý danh mục — hỏi lại bằng chính tên danh mục (khớp qua từ khóa sản phẩm).
                submitAsk({ question: optionBtn.dataset.question }, optionBtn.textContent.trim());
            } else if (optionBtn.dataset.topic) {
                // Nút chủ đề tĩnh có sẵn (7 màn hình đã render sẵn từ Blade) — không gọi mạng.
                selectTopic(optionBtn.dataset.topic, optionBtn.textContent.trim());
            }
        }
    });

    // Đóng bằng phím Escape — cùng convention với navbar.js.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) closePanel();
    });

    // Khôi phục trạng thái mở/đóng khi chuyển trang — không khôi phục màn hình con cũ,
    // luôn bắt đầu lại từ menu chính theo đúng yêu cầu.
    if (safeSessionGet(STORAGE_KEY) === '1') {
        panel.hidden = false;
        panel.classList.add('is-open');
        toggleBtn.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }

    // ========================================================================
    // Ô NHẬP CÂU HỎI TỰ DO — rule-based intent (không AI), gọi POST /quick-chat/ask.
    // Không lưu lịch sử hội thoại, không innerHTML với dữ liệu động.
    // ========================================================================
    const askForm = $('quick-chatbox-ask-form');
    const askInput = $('quick-chatbox-ask-input');
    const askSend = $('quick-chatbox-ask-send');

    if (askForm && askInput && askSend) {
        askInput.addEventListener('input', () => {
            askSend.disabled = askInput.value.trim().length === 0;
        });

        askForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const question = askInput.value.trim();
            if (!question) return;
            submitAsk({ question: question }, question);
            askInput.value = '';
            askSend.disabled = true;
        });
    }

    // Chặn gửi trùng lặp khi có 1 request đang bay (double-click nút gợi ý, hoặc gõ Enter liên tục).
    let askRequestInFlight = false;

    function submitAsk(payload, displayText) {
        if (askRequestInFlight) return;
        askRequestInFlight = true;
        if (askSend) askSend.disabled = true;

        showAnswerArea();
        const questionEl = appendMessageTo(answerArea, displayText, 'user');
        scrollQuestionIntoView(answerArea, questionEl);
        const askTypingEl = appendMessageTo(answerArea, 'Đang trả lời...', 'typing');
        const startedAt = Date.now();
        const tokenEl = document.querySelector('meta[name="csrf-token"]');
        if (!tokenEl) {
            if (askTypingEl && askTypingEl.parentNode) askTypingEl.parentNode.removeChild(askTypingEl);
            finishAskRequest();
            return;
        }

        fetch('/quick-chat/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': tokenEl.getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
            .then((res) => res.json())
            .then((data) => {
                const elapsed = Date.now() - startedAt;
                const remain = Math.max(0, typingDelay - elapsed);
                setTimeout(() => {
                    if (askTypingEl && askTypingEl.parentNode) askTypingEl.parentNode.removeChild(askTypingEl);
                    renderAskResult(data);
                    finishAskRequest();
                }, remain);
            })
            .catch(() => {
                if (askTypingEl && askTypingEl.parentNode) askTypingEl.parentNode.removeChild(askTypingEl);
                appendMessageTo(answerArea, 'Có lỗi xảy ra, vui lòng thử lại.', 'bot');
                finishAskRequest();
            });
    }

    function finishAskRequest() {
        askRequestInFlight = false;
        if (askSend) askSend.disabled = askInput ? askInput.value.trim().length === 0 : false;
    }

    function renderAskResult(data) {
        if (!answerArea) return;

        if (data.answer) {
            appendMessageTo(answerArea, data.answer, 'bot');
        }

        if (Array.isArray(data.items) && data.items.length > 0) {
            const wrap = document.createElement('div');
            wrap.className = 'quick-chatbox__result-cards';
            data.items.forEach((item) => {
                wrap.appendChild(item.type === 'product' ? buildProductCard(item) : buildPromotionCard(item));
            });
            answerArea.appendChild(wrap);
        }

        if (data.action_url) {
            const link = document.createElement('a');
            link.href = data.action_url;
            link.className = 'quick-chatbox__action-btn';
            link.textContent = 'Xem thêm';
            answerArea.appendChild(link);
        }

        // "Gợi ý danh mục" (Cà phê/Trà sữa/Sữa chua/...) là khối cố định, giống hệt nhau mỗi lần câu
        // hỏi rơi vào nhánh mơ hồ/không khớp — chỉ hiện 1 lần trong phiên chat để đỡ lặp lại gây rối
        // mắt. Nhận diện qua đặc điểm riêng: mọi gợi ý đều có "question", không có "intent_id".
        const isCategorySuggestionBlock = Array.isArray(data.suggestions)
            && data.suggestions.length > 0
            && data.suggestions.every((s) => s.question && !s.intent_id);

        if (Array.isArray(data.suggestions) && data.suggestions.length > 0
            && !(isCategorySuggestionBlock && categorySuggestionsShown)) {
            if (isCategorySuggestionBlock) categorySuggestionsShown = true;

            const wrap = document.createElement('div');
            wrap.className = 'quick-chatbox__result-suggestions';
            data.suggestions.forEach((s) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'quick-chatbox__option';
                btn.textContent = s.label;
                if (s.intent_id) {
                    btn.dataset.intent = s.intent_id;
                } else if (s.question) {
                    btn.dataset.question = s.question;
                } else if (s.topic_key) {
                    btn.dataset.topic = s.topic_key;
                }
                wrap.appendChild(btn);
            });
            answerArea.appendChild(wrap);
        }
    }

    function buildProductCard(item) {
        const card = document.createElement('div');
        card.className = 'quick-chatbox__product-card';

        const img = document.createElement('img');
        img.className = 'quick-chatbox__product-card-img';
        img.src = item.image_url;
        img.alt = item.name;
        card.appendChild(img);

        const info = document.createElement('div');
        info.className = 'quick-chatbox__product-card-info';

        const name = document.createElement('p');
        name.className = 'quick-chatbox__product-card-name';
        name.textContent = item.name;
        info.appendChild(name);

        const price = document.createElement('p');
        price.className = 'quick-chatbox__product-card-price';
        price.textContent = item.price;
        info.appendChild(price);

        const link = document.createElement('a');
        link.className = 'quick-chatbox__product-card-link';
        link.href = item.url;
        link.textContent = 'Xem sản phẩm';
        info.appendChild(link);

        card.appendChild(info);
        return card;
    }

    function buildPromotionCard(item) {
        const card = document.createElement('div');
        card.className = 'quick-chatbox__promotion-card';

        const code = document.createElement('p');
        code.className = 'quick-chatbox__promotion-card-code';
        code.textContent = item.code;
        card.appendChild(code);

        const value = document.createElement('p');
        value.className = 'quick-chatbox__promotion-card-value';
        value.textContent = item.value;
        card.appendChild(value);

        if (item.min_order_amount) {
            const cond = document.createElement('p');
            cond.className = 'quick-chatbox__promotion-card-condition';
            cond.textContent = 'Đơn tối thiểu ' + item.min_order_amount;
            card.appendChild(cond);
        }

        if (item.min_quantity) {
            const cond = document.createElement('p');
            cond.className = 'quick-chatbox__promotion-card-condition';
            cond.textContent = 'Tối thiểu ' + item.min_quantity + ' món';
            card.appendChild(cond);
        }

        if (item.end_at) {
            const expiry = document.createElement('p');
            expiry.className = 'quick-chatbox__promotion-card-expiry';
            expiry.textContent = 'Hạn dùng: ' + item.end_at;
            card.appendChild(expiry);
        }

        return card;
    }
})();
