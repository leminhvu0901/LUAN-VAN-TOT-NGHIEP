{{-- Modal xem nhanh sản phẩm --}}
<div id="modal-product-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; max-width:780px; width:90%; max-height:90vh; overflow:auto; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <!-- Close button -->
        <button onclick="document.getElementById('modal-product-overlay').style.display='none'"
                style="position:absolute; top:1rem; right:1rem; background:none; border:none; cursor:pointer; color:#6b7280; z-index:10;"
                aria-label="Đóng">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div style="display:flex; flex-wrap:wrap; padding:2rem; gap:2rem;">
            <!-- Product Image -->
            <div style="flex:1; min-width:260px;">
                <img id="modal-product-img" src="" alt=""
                     style="width:100%; border-radius:12px; object-fit:cover; aspect-ratio:1; background:#f3f4f6;">
            </div>
            <!-- Product Info -->
            <div style="flex:1; min-width:240px; display:flex; flex-direction:column; gap:0.75rem;">
                <h2 id="modal-product-name" style="font-size:1.4rem; font-weight:800; color:#111827; margin:0;"></h2>
                <div style="display:flex; align-items:center; gap:0.35rem; font-size:0.85rem; color:#6b7280;">
                    <svg style="color:#f59e0b; width:16px; height:16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span id="modal-product-rating">4.8 (120+)</span>
                </div>
                <p id="modal-product-price" style="font-size:1.75rem; font-weight:900; color:#10b981; margin:0;"></p>
                <p style="font-size:0.875rem; color:#6b7280; line-height:1.6;">
                    Đồ uống thơm ngon, được pha chế từ nguyên liệu tươi mới, phù hợp với mọi khẩu vị. Thưởng thức ngay hôm nay!
                </p>

                <!-- Quantity -->
                <div style="display:flex; align-items:center; gap:1rem;">
                    <span style="font-weight:600; color:#374151;">Số lượng:</span>
                    <div style="display:flex; align-items:center; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                        <button onclick="changeQty(-1)" style="padding:0.4rem 0.75rem; background:none; border:none; cursor:pointer; font-size:1.1rem; color:#374151;">−</button>
                        <span id="modal-qty" style="padding:0.4rem 1rem; font-weight:700; border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb;">1</span>
                        <button onclick="changeQty(1)" style="padding:0.4rem 0.75rem; background:none; border:none; cursor:pointer; font-size:1.1rem; color:#374151;">+</button>
                    </div>
                </div>

                <!-- Add to cart -->
                <button style="width:100%; padding:0.85rem; background:#10b981; color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; transition:background 0.2s;"
                        onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                    🛒 Thêm vào giỏ hàng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let qty = 1;
    function changeQty(delta) {
        qty = Math.max(1, qty + delta);
        document.getElementById('modal-qty').textContent = qty;
    }

    // Close when clicking overlay background
    document.getElementById('modal-product-overlay').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>
