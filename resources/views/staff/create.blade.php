@extends('backend.layouts.app')

@section('title', 'Tạo đơn hàng mới (POS)')

{{-- CSS cho trang POS đã được đặt trong public/css/admin/admin.css (section POS PAGE) --}}

@section('content')
<div class="flex flex-col xl:flex-row gap-4 h-full min-h-0">
    
    {{-- Left Column: Menu (7 parts out of 12) --}}
    <div class="w-full xl:w-7/12 2xl:w-8/12 flex flex-col gap-3 h-full min-h-0">
        
        {{-- POS Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 shrink-0">
            {{-- Order Types --}}
            <div class="flex items-center bg-gray-50 p-1 rounded-full border border-gray-200">
                <button class="order-type-btn active flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold bg-primary text-white shadow-md transition-all" data-type="dine-in">
                    <span class="material-symbols-outlined text-[20px]">restaurant</span>
                    Dùng tại quán
                </button>
                <button class="order-type-btn flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium text-gray-500 hover:text-gray-700 transition-all" data-type="takeaway">
                    <span class="material-symbols-outlined text-[20px]">takeout_dining</span>
                    Mang đi
                </button>
                <button class="order-type-btn flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium text-gray-500 hover:text-gray-700 transition-all" data-type="delivery">
                    <span class="material-symbols-outlined text-[20px]">two_wheeler</span>
                    Giao hàng
                </button>
            </div>
            
            {{-- Search --}}
            <div class="relative w-full md:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">search</span>
                <input type="text" id="product-search" placeholder="Tìm tên món hoặc mã..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
            </div>
        </div>

        {{-- Categories --}}
        <div class="flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2 flex-1" id="category-tabs">
                <button class="cat-btn active whitespace-nowrap px-4 py-2 rounded-xl text-sm font-bold text-primary border-b-2 border-primary" data-id="all">Tất cả</button>
                @foreach($categories as $cat)
                <button class="cat-btn whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors" data-id="{{ $cat->id }}">{{ $cat->name }}</button>
                @endforeach
            </div>
        </div>

        {{-- Products Grid --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar pr-2">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-3 2xl:grid-cols-4 gap-4 pb-10">
                
                {{-- If no products loaded, show mock data to match UI --}}
                @if($products->isEmpty())
                    @php
                        $mockProducts = [
                            ['id'=>1, 'name'=>'Matcha Đá Xay', 'price'=>55000, 'img'=>'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=500&q=80'],
                            ['id'=>2, 'name'=>'Cà Phê Sữa Đá', 'price'=>35000, 'img'=>'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=500&q=80'],
                            ['id'=>3, 'name'=>'Trà Đào Cam Sả', 'price'=>49000, 'img'=>'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&q=80'],
                            ['id'=>4, 'name'=>'Trà Sữa Khoai Môn', 'price'=>52000, 'img'=>'https://images.unsplash.com/photo-1558160074-4d7d8bdf4256?w=500&q=80'],
                            ['id'=>5, 'name'=>'Chocolate Macchiato', 'price'=>58000, 'img'=>'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=500&q=80'],
                            ['id'=>6, 'name'=>'Trà Ổi Hồng', 'price'=>45000, 'img'=>'https://images.unsplash.com/photo-1498804103079-a6351b050096?w=500&q=80'],
                        ];
                    @endphp
                    @foreach($mockProducts as $p)
                    <div class="product-card bg-white rounded-2xl p-3 border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group flex flex-col" onclick="addToCart({{ $p['id'] }}, '{{ $p['name'] }}', {{ $p['price'] }})">
                        <div class="w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-50 relative">
                            <img src="{{ $p['img'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <h4 class="font-bold text-gray-900 text-sm mb-1 line-clamp-2 flex-1">{{ $p['name'] }}</h4>
                        <div class="flex items-center justify-between mt-auto">
                            <span class="font-bold text-primary text-sm">{{ number_format($p['price'], 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                    @endforeach
                @else
                    @foreach($products as $p)
                    <div class="product-card bg-white rounded-2xl p-3 border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group flex flex-col" data-category="{{ $p->category_id }}" data-name="{{ strtolower($p->name) }}" onclick="addToCart({{ $p->id }}, '{{ $p->name }}', {{ $p->base_price ?? 0 }}, '{{ $p->image }}')">
                        <div class="w-full aspect-square rounded-xl overflow-hidden mb-3 bg-gray-50 relative">
                            <img src="{{ asset('images/' . $p->image) }}" alt="{{ $p->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <h4 class="font-bold text-gray-900 text-sm mb-1 line-clamp-2 flex-1">{{ $p->name }}</h4>
                        <div class="flex items-center justify-between mt-auto">
                            <span class="font-bold text-primary text-sm">{{ number_format($p->base_price ?? 0, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Right Column: Cart (5 parts out of 12) --}}
    <div class="pos-right-col w-full xl:w-5/12 2xl:w-4/12 flex flex-col gap-3 h-full min-h-0">
        
        {{-- Customer Info - Compact --}}
        <div class="bg-white rounded-xl px-4 py-3 shadow-sm border border-gray-100 shrink-0">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-gray-400 text-[18px]">person</span>
                <span class="font-bold text-gray-900 text-sm flex-1">Khách hàng</span>
                <span class="text-xs font-bold text-primary cursor-pointer hover:underline">THÀNH VIÊN MỚI</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[16px]">call</span>
                    <input type="text" id="customer-phone" placeholder="Số điện thoại..." class="w-full pl-8 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-medium">
                </div>
                <div class="flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg flex-shrink-0">
                    <div id="customer-name" class="font-bold text-gray-900 text-xs">Khách lẻ</div>
                    <button class="w-4 h-4 flex items-center justify-center text-emerald-600 hover:text-emerald-800 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">close</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Cart List --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col overflow-hidden min-h-0">
            <div class="px-3 py-2 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="font-bold text-gray-900 text-sm">Giỏ hàng (<span id="cart-count">0</span>)</h3>
                <button onclick="clearCart()" class="text-xs font-bold text-red-500 flex items-center gap-1 hover:bg-red-50 px-2 py-1 rounded transition-colors">
                    <span class="material-symbols-outlined text-[15px]">delete_sweep</span> Xóa tất cả
                </button>
            </div>
            
            <div id="cart-items" class="flex-1 overflow-y-auto custom-scrollbar p-2 divide-y divide-gray-50">
                {{-- Items will be rendered here via JS. Adding initial items for UI matching --}}
            </div>

            {{-- Summary --}}
            <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 shrink-0 space-y-1">
                <div class="flex justify-between text-gray-600 text-xs">
                    <span>Tạm tính</span>
                    <span class="font-bold text-gray-900" id="subtotal">0đ</span>
                </div>
                <div id="discount-row" class="flex justify-between text-red-500 text-xs hidden">
                    <span id="discount-label">Giảm giá</span>
                    <span class="font-bold" id="discount-amount">-0đ</span>
                </div>
                <div class="flex justify-between pt-1.5 border-t border-gray-200">
                    <span class="font-bold text-gray-900 text-sm">Tổng cộng</span>
                    <span class="font-bold text-primary text-base" id="total">0đ</span>
                </div>
            </div>
        </div>

        {{-- Payment Block - Compact --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 shrink-0">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="material-symbols-outlined text-gray-400 text-[18px]">payments</span>
                <h3 class="font-bold text-gray-900 text-xs uppercase tracking-wide">Nghiệp vụ tiền mặt</h3>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tiền khách đưa</label>
                    <input type="text" id="cash-tendered" value="200.000" inputmode="numeric" class="w-full px-3 py-1.5 bg-white border border-green-300 focus:border-primary focus:ring-1 focus:ring-primary rounded-lg text-green-700 font-bold text-right outline-none transition-all text-sm">
                </div>
                <div class="text-right">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tiền thừa</label>
                    <div id="change-amount" class="text-base font-bold text-gray-900">0đ</div>
                </div>
            </div>
        </div>

        {{-- Actions - Compact --}}
        <div class="grid grid-cols-3 gap-2 shrink-0">
            <button class="col-span-1 flex items-center justify-center gap-1.5 py-2.5 bg-red-50 text-red-600 border border-red-200 rounded-xl hover:bg-red-100 transition-colors font-bold text-sm">
                <span class="material-symbols-outlined text-[18px]">cancel</span>
                Hủy đơn
            </button>
            <button onclick="submitOrder()" class="col-span-2 flex items-center justify-center gap-2 py-2.5 bg-primary text-white rounded-xl hover:bg-emerald-700 transition-colors shadow-md shadow-primary/30 font-bold text-base">
                <span class="material-symbols-outlined text-[22px]">check_circle</span>
                Tạo đơn hàng
            </button>
        </div>

    </div>
</div>

{{-- Inline JS for POS Interactions --}}
<script>
    // State
    let cart = [];
    let discount = 0;
    let currentCategoryId = 'all';

    // Format Currency
    const formatVND = (num) => new Intl.NumberFormat('vi-VN').format(num) + 'đ';

    // Xóa dấu tiếng Việt cho Live Search
    function removeVietnameseTones(str) {
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a"); 
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e"); 
        str = str.replace(/ì|í|ị|ỉ|ĩ/g,"i"); 
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o"); 
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u"); 
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y"); 
        str = str.replace(/đ/g,"d");
        return str;
    }

    // Filter Products
    function filterProducts() {
        const searchTerm = removeVietnameseTones(document.getElementById('product-search').value.toLowerCase());
        document.querySelectorAll('.product-card').forEach(card => {
            const name = removeVietnameseTones(card.getAttribute('data-name'));
            const catId = card.getAttribute('data-category');
            
            const matchSearch = name.includes(searchTerm);
            const matchCategory = currentCategoryId === 'all' || catId === currentCategoryId;
            
            if(matchSearch && matchCategory) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.getElementById('product-search').addEventListener('input', filterProducts);
    
    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cat-btn').forEach(b => {
                b.classList.remove('active', 'font-bold', 'text-primary', 'border-b-2', 'border-primary');
                b.classList.add('font-medium', 'text-gray-500');
            });
            this.classList.remove('font-medium', 'text-gray-500');
            this.classList.add('active', 'font-bold', 'text-primary', 'border-b-2', 'border-primary');
            
            currentCategoryId = this.getAttribute('data-id');
            filterProducts();
        });
    });

    // Render Cart
    function renderCart() {
        const container = document.getElementById('cart-items');
        container.innerHTML = '';
        
        let subtotal = 0;
        let count = 0;

        cart.forEach((item, index) => {
            subtotal += item.price * item.qty;
            count += item.qty;

            container.innerHTML += `
            <div class="p-3 hover:bg-gray-50 transition-colors flex gap-3 group relative">
                <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 text-emerald-700 font-bold overflow-hidden">
                    <img src="${item.image ? '/images/' + item.image : '/images/products/placeholder.jpg'}" class="w-full h-full object-cover opacity-80" onerror="this.src='/images/products/placeholder.jpg'">
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-gray-900 text-sm leading-tight">${item.name}</h4>
                        <span class="font-bold text-gray-900 text-sm">${formatVND(item.price)}</span>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1 line-clamp-1">${item.options || ''}</div>
                    
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-full px-2 py-1 shadow-sm">
                            <button onclick="updateQty(${index}, -1)" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-600 font-bold">-</button>
                            <span class="text-sm font-bold w-4 text-center">${item.qty}</span>
                            <button onclick="updateQty(${index}, 1)" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-600 font-bold">+</button>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        document.getElementById('cart-count').innerText = count;
        document.getElementById('subtotal').innerText = formatVND(subtotal);
        
        const total = Math.max(0, subtotal - discount);
        document.getElementById('total').innerText = formatVND(total);

        calculateChange();
    }

    // Actions
    function addToCart(id, name, price, image) {
        const existing = cart.find(i => i.id === id);
        if(existing) {
            existing.qty++;
        } else {
            cart.push({ id, name, price, qty: 1, options: 'Size M, Mặc định', image: image });
        }
        renderCart();
    }

    function updateQty(index, change) {
        cart[index].qty += change;
        if(cart[index].qty <= 0) {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function clearCart() {
        if(confirm('Bạn có chắc chắn muốn xóa tất cả món trong giỏ hàng?')) {
            cart = [];
            renderCart();
        }
    }

    function calculateChange() {
        const rawTotalStr = document.getElementById('total').innerText.replace(/\./g,'').replace('đ','');
        const total = parseInt(rawTotalStr) || 0;
        
        // Lấy giá trị thuần số từ input đã được format (bỏ dấu chấm)
        const tenderedStr = document.getElementById('cash-tendered').value.replace(/\./g,'');
        const tendered = parseInt(tenderedStr) || 0;
        
        const change = tendered - total;
        
        const changeEl = document.getElementById('change-amount');
        if(change >= 0) {
            changeEl.innerText = formatVND(change);
            changeEl.className = 'text-base font-bold text-gray-900';
        } else {
            changeEl.innerText = 'Thiếu tiền!';
            changeEl.className = 'text-base font-bold text-red-500';
        }
    }

    function submitOrder() {
        if(cart.length === 0) {
            alert('Giỏ hàng đang trống!');
            return;
        }

        const rawTotalStr = document.getElementById('total').innerText.replace(/\./g,'').replace('đ','');
        const finalAmount = parseInt(rawTotalStr) || 0;

        // Get phone and name
        const customerPhone = document.getElementById('customer-phone').value;
        const customerName = document.getElementById('customer-name').innerText; // Using the suggest box text
        
        // Get active order type
        const activeTypeBtn = document.querySelector('.order-type-btn.active');
        let orderType = 'dine-in';
        if(activeTypeBtn) orderType = activeTypeBtn.getAttribute('data-type');
        
        const payload = {
            _token: '{{ csrf_token() }}',
            customer_phone: customerPhone,
            customer_name: customerName,
            order_type: orderType,
            items: cart,
            final_amount: finalAmount
        };

        // Gửi AJAX POST đến Controller
        fetch('{{ route("admin.orders.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Tạo đơn hàng thành công! Mã đơn: ' + data.order_code);
                cart = [];
                document.getElementById('cash-tendered').value = '';
                renderCart();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối máy chủ!');
        });
    }

    // Initialize Order Type Toggle
    document.querySelectorAll('.order-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.order-type-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white', 'shadow-md');
                b.classList.add('text-gray-500');
            });
            this.classList.remove('text-gray-500');
            this.classList.add('bg-primary', 'text-white', 'shadow-md');
        });
    });

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        // Thêm class page-pos vào body để kích hoạt CSS riêng cho trang POS
        document.body.classList.add('page-pos');
        renderCart();
        
        // Auto-format input tiền khách đưa
        const cashInput = document.getElementById('cash-tendered');
        
        cashInput.addEventListener('input', function() {
            // Lưu vị trí cursor
            const selectionStart = this.selectionStart;
            const prevLen = this.value.length;
            
            // Chỉ giữ lại chữ số
            let raw = this.value.replace(/[^0-9]/g, '');
            
            // Giới hạn 10 chữ số (~9.999.999.999đ)
            if (raw.length > 10) raw = raw.slice(0, 10);
            
            // Format với dấu chấm ngăn cách
            const formatted = raw === '' ? '' : new Intl.NumberFormat('vi-VN').format(parseInt(raw));
            this.value = formatted;
            
            // Giữ cursor ở đúng vị trí sau khi thêm dấu chấm
            const newLen = this.value.length;
            const diff = newLen - prevLen;
            const newPos = Math.max(0, selectionStart + diff);
            this.setSelectionRange(newPos, newPos);
            
            calculateChange();
        });
        
        // Tính tiền thừa khi load trang
        calculateChange();
    });
</script>

{{-- Styles moved to public/css/admin/admin.css --}}
@endsection
