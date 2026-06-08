@extends('layouts.app')

@section('content')


<div class="ecommerce-profile-wrapper">
    <div class="ecommerce-profile-container">
        
        {{-- Sidebar --}}
        <div class="profile-sidebar">
            <div class="sidebar-user-brief">
                @if(Auth::user()->avatar)
                    <img src="{{ asset('images/avatars/' . Auth::user()->avatar) }}" class="sidebar-user-avatar" alt="Avatar">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10b981&color=fff" class="sidebar-user-avatar" alt="Avatar">
                @endif
                <div class="sidebar-user-info">
                    <p class="sidebar-user-name">{{ Auth::user()->name }}</p>
                    <a href="#" class="sidebar-user-edit">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Sửa Hồ Sơ
                    </a>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('profile') }}" class="active">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #10b981;"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Tài khoản của tôi
                    </a>
                </li>
                <li>
                    <a href="{{ route('orders') }}">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #10b981;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Đơn Mua
                    </a>
                </li>
            </ul>
        </div>

        {{-- Main Content --}}
        <div style="flex: 1;">
            @if(session('success'))
            <div class="alert alert-success" style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                {{ session('success') }}
            </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="profile-main-card">
                    <div class="card-header">
                        <h2 class="card-title">Hồ Sơ Của Tôi</h2>
                        <p class="card-subtitle">Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
                    </div>
                    <div class="card-body">
                        
                        {{-- Form Section --}}
                        <div class="form-section">
                            <div class="form-row">
                                <label class="form-label">Tên</label>
                                <div class="form-input-wrapper">
                                    <input type="text" name="name" value="{{ Auth::user()->name }}" class="form-input" required>
                                    @error('name') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <label class="form-label">Email</label>
                                <div class="form-input-wrapper">
                                    <input type="email" value="{{ Auth::user()->email }}" class="form-input" disabled>
                                </div>
                            </div>

                            <div class="form-row">
                                <label class="form-label">Số điện thoại</label>
                                <div class="form-input-wrapper">
                                    <input type="tel" name="phone" value="{{ Auth::user()->phone ?? '' }}" class="form-input">
                                    @error('phone') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
                                </div>
                            </div>

                            <div class="form-row" style="margin-bottom: 0;">
                                <label class="form-label">Hạng thành viên</label>
                                <div class="form-input-wrapper" style="padding-top: 10px; color: #555; font-size: 14px;">
                                    @switch(Auth::user()->membership_level ?? 'new')
                                        @case('silver') <span style="font-weight: 500; color: #64748b;">Bạc</span> @break
                                        @case('gold') <span style="font-weight: 500; color: #eab308;">Vàng</span> @break
                                        @case('diamond') <span style="font-weight: 500; color: #3b82f6;">Kim Cương</span> @break
                                        @default <span style="font-weight: 500;">Mới</span>
                                    @endswitch
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-save">Lưu</button>
                            </div>
                        </div>

                        {{-- Avatar Section --}}
                        <div class="avatar-section">
                            <div class="avatar-display">
                                @if(Auth::user()->avatar)
                                    <img id="avatarPreview" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}" alt="Avatar">
                                @else
                                    <img id="avatarPreview" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10b981&color=fff" alt="Avatar">
                                @endif
                            </div>
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;" onchange="previewAvatar(event)">
                            <input type="hidden" name="cropped_avatar" id="croppedAvatarInput">
                            <button type="button" class="btn-select-image" onclick="document.getElementById('avatarInput').click()">Chọn Ảnh</button>
                            <div class="avatar-note">
                                Dụng lượng file tối đa 2 MB<br>
                                Định dạng: .JPEG, .PNG
                            </div>
                            @error('avatar') <small class="text-danger mt-2 d-block text-center">{{ $message }}</small> @enderror
                        </div>

                    </div>
                </div>
            </form>

            {{-- Address Book Section --}}
            <div class="address-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #efefef;">
                    <div>
                        <h2 class="card-title" style="font-size: 16px;">Địa Chỉ Của Tôi</h2>
                    </div>
                    <button type="button" class="btn-save" onclick="openAddressModal()">+ Thêm địa chỉ mới</button>
                </div>
                
                <div>
                    @forelse($addresses as $addr)
                    <div class="address-item">
                        <div class="shopee-address-info">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #333; font-size: 16px;">{{ $addr->fullname }}</span>
                                <span style="color: #e5e5e5;">|</span>
                                <span style="color: #888; font-size: 14px;">{{ $addr->phone }}</span>
                            </div>
                            <div style="color: #555; font-size: 14px; margin-bottom: 4px;">
                                {{ $addr->specific_address }}
                            </div>
                            @if(!($addr->province && str_contains($addr->specific_address, $addr->province)))
                            <div style="color: #555; font-size: 14px; margin-bottom: 12px;">
                                {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->province }}
                            </div>
                            @endif
                            <div style="display: flex; gap: 8px;">
                                @if($addr->is_default)
                                    <span style="border: 1px solid #10b981; color: #10b981; padding: 2px 6px; font-size: 12px; border-radius: 2px;">Mặc định</span>
                                @endif
                                @if($addr->type == 'home')
                                    <span style="border: 1px solid #999; color: #777; padding: 2px 6px; font-size: 12px; border-radius: 2px;">Nhà Riêng</span>
                                @else
                                    <span style="border: 1px solid #999; color: #777; padding: 2px 6px; font-size: 12px; border-radius: 2px;">Văn Phòng</span>
                                @endif
                            </div>
                        </div>
                        <div class="shopee-address-actions" style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                            <div style="display: flex; gap: 15px;">
                                <button type="button" style="color: #0b5bbb; background: none; border: none; cursor: pointer; padding: 0; font-size: 14px;" onclick='editAddress(@json($addr))'>Cập nhật</button>
                                @if(!$addr->is_default)
                                <button type="button" style="color: #333; background: none; border: none; cursor: pointer; padding: 0; font-size: 14px;" onclick="deleteAddress({{ $addr->id }})">Xóa</button>
                                @endif
                            </div>
                            @if(!$addr->is_default)
                            <button type="button" style="border: 1px solid #e5e5e5; background: #fff; color: #333; padding: 6px 12px; border-radius: 2px; font-size: 13px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='#fff'" onclick="setDefaultAddress({{ $addr->id }})">Thiết lập mặc định</button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div style="text-align: center; padding: 40px; color: #888; font-size: 15px;">
                        Bạn chưa có địa chỉ nào.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Address Modal UI -->


<div class="addr-modal-overlay" id="addressModal">
    <div class="addr-modal">
        <div class="addr-header" id="addressModalTitle">Địa chỉ mới</div>
        <div class="addr-body">
            <input type="hidden" id="addr_id">
            
            <div class="addr-row">
                <input type="text" id="addr_fullname" class="addr-input" placeholder="Họ và tên">
                <input type="tel" id="addr_phone" class="addr-input" placeholder="Số điện thoại">
            </div>

            <div class="loc-picker-container" id="locPickerContainer">
                <div class="loc-picker-input" id="locPickerInput" onclick="toggleLocPanel()">
                    <span id="locPickerText">Tỉnh/Thành Phố, Quận/Huyện, Phường/Xã</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor"><path d="M2 4L6 8L10 4"></path></svg>
                </div>
                <div class="loc-panel" id="locPanel">
                    <div class="loc-tabs">
                        <div class="loc-tab active" id="tab_province" onclick="switchLocTab('province')">Tỉnh/Thành Phố</div>
                        <div class="loc-tab" id="tab_district" onclick="switchLocTab('district')">Quận/Huyện</div>
                        <div class="loc-tab" id="tab_ward" onclick="switchLocTab('ward')">Phường/Xã</div>
                    </div>
                    <div class="loc-list" id="locList">
                        <!-- Items injected here -->
                    </div>
                </div>
                <!-- Hidden inputs to store selected data -->
                <input type="hidden" id="addr_province">
                <input type="hidden" id="addr_district">
                <input type="hidden" id="addr_ward">
            </div>

            <div class="addr-row">
                <textarea id="addr_specific" class="addr-input" rows="3" placeholder="Địa chỉ cụ thể" style="resize: none;"></textarea>
            </div>
            
            <button type="button" id="resetGpsBtn" style="display:none; margin-bottom: 15px; color: #dc2626; background: none; border: none; font-size: 14px; font-weight: 500; cursor: pointer; align-items: center; padding: 0;" onclick="resetToManual()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Hủy vị trí GPS, nhập lại thủ công
            </button>

            <div class="fake-map-box" id="fakeMapBox">
                <button type="button" class="fake-map-btn" id="btn-get-gps" onclick="getCurrentLocation(this)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span id="gps-btn-text">Thêm vị trí</span>
                </button>
            </div>

            <div style="margin-bottom: 10px; font-size: 14px; color: #555;">Loại địa chỉ:</div>
            <div class="addr-type-btns">
                <button type="button" class="addr-type-btn active" id="btnTypeHome" onclick="setAddrType('home')">Nhà Riêng</button>
                <button type="button" class="addr-type-btn" id="btnTypeOffice" onclick="setAddrType('office')">Văn Phòng</button>
            </div>
            <input type="hidden" id="addr_type" value="home">

            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="addr_default" style="width: 16px; height: 16px; cursor: pointer;">
                <label for="addr_default" style="font-size: 14px; cursor: pointer; color: #555; user-select: none; margin: 0;">Đặt làm địa chỉ mặc định</label>
            </div>
        </div>
        <div class="addr-footer">
            <button type="button" class="btn-cancel" onclick="closeAddressModal()">Trở Lại</button>
            <button type="button" class="btn-submit" onclick="saveAddress()">Hoàn thành</button>
        </div>
    </div>
</div>

<!-- Cropper JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<!-- Cropper Modal -->
<div id="cropperModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:12px; width:90%; max-width:500px; text-align:center; position: relative;">
        <h4 style="margin-bottom:15px; font-weight: 600; color: #374151;">Chỉnh sửa ảnh đại diện</h4>
        <div style="width:100%; max-height:400px; overflow:hidden; margin-bottom:15px; background: #f3f4f6; border-radius: 8px;">
            <img id="imageToCrop" style="max-width:100%; display:block;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="closeCropperModal()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #374151; font-weight: 500; cursor:pointer;">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="cropImage()" style="padding: 8px 16px; border-radius: 6px; background: #10b981; color:#fff; border:none; font-weight: 500; cursor:pointer;">Cắt & Lưu</button>
        </div>
    </div>
</div>

<script>
let cropper;

// ----- Address Location Logic -----
let locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };

async function fetchProvinces() {
    renderLocLoading();
    try {
        const res = await fetch('https://provinces.open-api.vn/api/p/');
        const data = await res.json();
        renderLocItems(data, 'province');
    } catch(e) { console.error(e); }
}

async function fetchDistricts(provinceCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.districts, 'district');
    } catch(e) { console.error(e); }
}

async function fetchWards(districtCode) {
    renderLocLoading();
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`);
        const data = await res.json();
        renderLocItems(data.wards, 'ward');
    } catch(e) { console.error(e); }
}

function renderLocLoading() {
    document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Đang tải...</div>';
}

function renderLocItems(items, type) {
    const list = document.getElementById('locList');
    list.innerHTML = '';
    if (!items || items.length === 0) {
        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">Không có dữ liệu</div>';
        return;
    }
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'loc-item';
        div.textContent = item.name;
        div.onclick = () => selectLocItem(item, type);
        list.appendChild(div);
    });
}

function selectLocItem(item, type) {
    if (type === 'province') {
        locState.province = item.code; locState.province_name = item.name;
        locState.district = null; locState.district_name = '';
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_province').value = item.name;
        document.getElementById('addr_district').value = '';
        document.getElementById('addr_ward').value = '';
        switchLocTab('district');
    } else if (type === 'district') {
        locState.district = item.code; locState.district_name = item.name;
        locState.ward = null; locState.ward_name = '';
        document.getElementById('addr_district').value = item.name;
        document.getElementById('addr_ward').value = '';
        switchLocTab('ward');
    } else if (type === 'ward') {
        locState.ward = item.code; locState.ward_name = item.name;
        document.getElementById('addr_ward').value = item.name;
        updateLocPickerText();
        document.getElementById('locPanel').style.display = 'none';
    }
}

function updateLocPickerText() {
    const textEl = document.getElementById('locPickerText');
    const inputEl = document.getElementById('locPickerInput');
    if (locState.province_name && locState.district_name && locState.ward_name) {
        textEl.textContent = `${locState.province_name}, ${locState.district_name}, ${locState.ward_name}`;
        inputEl.classList.add('has-value');
    } else if (locState.province_name) {
        textEl.textContent = `${locState.province_name}` + (locState.district_name ? `, ${locState.district_name}` : '');
        inputEl.classList.add('has-value');
    } else {
        textEl.textContent = 'Tỉnh/Thành Phố, Quận/Huyện, Phường/Xã';
        inputEl.classList.remove('has-value');
    }
}

function switchLocTab(tab) {
    locState.currentTab = tab;
    document.getElementById('tab_province').classList.toggle('active', tab === 'province');
    document.getElementById('tab_district').classList.toggle('active', tab === 'district');
    document.getElementById('tab_ward').classList.toggle('active', tab === 'ward');
    
    if (tab === 'province') {
        fetchProvinces();
    } else if (tab === 'district') {
        if (locState.province) fetchDistricts(locState.province);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Tỉnh/Thành Phố trước</div>';
    } else if (tab === 'ward') {
        if (locState.district) fetchWards(locState.district);
        else document.getElementById('locList').innerHTML = '<div style="padding: 20px; text-align: center; color: #10b981;">Vui lòng chọn Quận/Huyện trước</div>';
    }
}

function toggleLocPanel() {
    const panel = document.getElementById('locPanel');
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        panel.style.display = 'block';
        if (!locState.province) switchLocTab('province');
        else if (!locState.district) switchLocTab('district');
        else if (!locState.ward) switchLocTab('ward');
        else switchLocTab('province'); // if fully selected, re-open from start
    }
}

document.addEventListener('click', function(e) {
    const container = document.getElementById('locPickerContainer');
    if (container && !container.contains(e.target) && document.body.contains(e.target)) {
        document.getElementById('locPanel').style.display = 'none';
    }
});

// ----- Modal & CRUD Logic -----
function openAddressModal() {
    document.getElementById('addressModalTitle').textContent = 'Địa chỉ mới';
    document.getElementById('addr_id').value = '';
    document.getElementById('addr_fullname').value = '';
    document.getElementById('addr_phone').value = '';
    document.getElementById('addr_specific').value = '';
    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';
    
    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();
    
    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('fakeMapBox').style.display = 'flex';
    document.getElementById('resetGpsBtn').style.display = 'none';
    document.getElementById('addr_specific').readOnly = false;
    
    setAddrType('home');
    document.getElementById('addr_default').checked = false;
    document.getElementById('addressModal').style.display = 'flex';
}

function editAddress(addr) {
    document.getElementById('addressModalTitle').textContent = 'Cập nhật địa chỉ';
    document.getElementById('addr_id').value = addr.id;
    document.getElementById('addr_fullname').value = addr.fullname;
    document.getElementById('addr_phone').value = addr.phone;
    document.getElementById('addr_specific').value = addr.specific_address;
    document.getElementById('addr_province').value = addr.province;
    document.getElementById('addr_district').value = addr.district;
    document.getElementById('addr_ward').value = addr.ward;
    
    locState.province_name = addr.province;
    locState.district_name = addr.district;
    locState.ward_name = addr.ward;
    // We only have names stored in DB, not open-api codes. 
    // To allow re-selecting cleanly, reset codes. If user wants to change, they pick from scratch.
    locState.province = null; locState.district = null; locState.ward = null;
    updateLocPickerText();
    
    if (addr.specific_address && addr.province && addr.specific_address.includes(addr.province)) {
        document.getElementById('locPickerContainer').style.display = 'none';
        document.getElementById('fakeMapBox').style.display = 'none';
        document.getElementById('resetGpsBtn').style.display = 'flex';
        document.getElementById('addr_specific').readOnly = true;
    } else {
        document.getElementById('locPickerContainer').style.display = 'block';
        document.getElementById('fakeMapBox').style.display = 'flex';
        document.getElementById('resetGpsBtn').style.display = 'none';
        document.getElementById('addr_specific').readOnly = false;
    }
    
    setAddrType(addr.type);
    document.getElementById('addr_default').checked = addr.is_default == 1;
    document.getElementById('addressModal').style.display = 'flex';
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
}

function setAddrType(type) {
    document.getElementById('addr_type').value = type;
    document.getElementById('btnTypeHome').classList.toggle('active', type === 'home');
    document.getElementById('btnTypeOffice').classList.toggle('active', type === 'office');
}

async function saveAddress() {
    const id = document.getElementById('addr_id').value;
    const data = {
        _token: '{{ csrf_token() }}',
        fullname: document.getElementById('addr_fullname').value,
        phone: document.getElementById('addr_phone').value,
        province: document.getElementById('addr_province').value,
        district: document.getElementById('addr_district').value,
        ward: document.getElementById('addr_ward').value,
        specific_address: document.getElementById('addr_specific').value,
        type: document.getElementById('addr_type').value,
        is_default: document.getElementById('addr_default').checked ? 1 : 0
    };
    
    if (!data.fullname || !data.phone || !data.province || !data.district || !data.ward || !data.specific_address) {
        alert("Vui lòng điền đầy đủ thông tin.");
        return;
    }

    const url = id ? `/profile/address/${id}` : '/profile/address';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Có lỗi xảy ra");
        }
    } catch(e) { alert("Có lỗi xảy ra"); }
}

async function deleteAddress(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) return;
    try {
        const res = await fetch(`/profile/address/${id}/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ _token: '{{ csrf_token() }}' })
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        }
    } catch(e) { alert("Có lỗi xảy ra"); }
}

async function setDefaultAddress(id) {
    try {
        const res = await fetch(`/profile/address/${id}/default`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ _token: '{{ csrf_token() }}' })
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        }
    } catch(e) { alert("Có lỗi xảy ra"); }
}

// ----- GPS Location Logic -----
async function getCurrentLocation(btn) {
    const currentProvince = document.getElementById('addr_province').value;
    const currentSpecific = document.getElementById('addr_specific').value.trim();
    
    if (currentProvince || currentSpecific) {
        if (!confirm("Bạn đã nhập địa chỉ thủ công. Bạn có chắc muốn dùng địa chỉ GPS để thay thế không?")) {
            return;
        }
    }

    const textSpan = document.getElementById('gps-btn-text');
    const originalText = textSpan.innerText;

    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    btn.disabled = true;
    textSpan.innerText = "Đang lấy vị trí...";
    const originalBg = btn.style.backgroundColor;
    btn.style.backgroundColor = '#f3f4f6';
    btn.style.cursor = 'wait';

    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        try {
            // Sử dụng Nominatim API (OpenStreetMap) để lấy địa chỉ từ tọa độ
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=vi`);
            const data = await res.json();

            if (data && data.display_name) {
                const addr = data.address || {};
                const province = addr.city || addr.state || addr.province || '';
                const district = addr.county || addr.city_district || addr.suburb || addr.town || '';
                const ward = addr.village || addr.quarter || addr.neighbourhood || addr.residential || '';

                document.getElementById('addr_province').value = province;
                document.getElementById('addr_district').value = district;
                document.getElementById('addr_ward').value = ward;
                document.getElementById('addr_specific').value = data.display_name;
                
                // Hide manual input and GPS button, show Reset button
                document.getElementById('locPickerContainer').style.display = 'none';
                document.getElementById('fakeMapBox').style.display = 'none';
                document.getElementById('resetGpsBtn').style.display = 'flex';
                document.getElementById('addr_specific').readOnly = true;

                alert("Đã tự động điền địa chỉ dựa trên GPS!");
            } else {
                alert("Không thể chuyển đổi tọa độ thành địa chỉ.");
            }
        } catch (error) {
            console.error(error);
            alert("Có lỗi xảy ra khi gọi API bản đồ.");
        } finally {
            btn.disabled = false;
            textSpan.innerText = originalText;
            btn.style.backgroundColor = originalBg;
            btn.style.cursor = 'pointer';
        }
    }, (error) => {
        let msg = "Không thể lấy vị trí.";
        switch(error.code) {
            case error.PERMISSION_DENIED:
                msg = "Bạn đã từ chối quyền truy cập vị trí.";
                break;
            case error.POSITION_UNAVAILABLE:
                msg = "Thông tin vị trí không khả dụng.";
                break;
            case error.TIMEOUT:
                msg = "Yêu cầu lấy vị trí quá thời gian.";
                break;
        }
        alert(msg);
        btn.disabled = false;
        textSpan.innerText = originalText;
        btn.style.backgroundColor = originalBg;
        btn.style.cursor = 'pointer';
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
}

function resetToManual() {
    // Hiển thị lại nhập thủ công
    document.getElementById('locPickerContainer').style.display = 'block';
    document.getElementById('fakeMapBox').style.display = 'flex';
    document.getElementById('resetGpsBtn').style.display = 'none';
    document.getElementById('addr_specific').readOnly = false;
    
    // Xóa dữ liệu GPS
    document.getElementById('addr_province').value = '';
    document.getElementById('addr_district').value = '';
    document.getElementById('addr_ward').value = '';
    document.getElementById('addr_specific').value = '';
    
    locState = { province: null, district: null, ward: null, province_name: '', district_name: '', ward_name: '', currentTab: 'province' };
    updateLocPickerText();
}

// ----- Avatar Logic -----


function previewAvatar(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Cắt ảnh ở máy khách nên có thể nới lỏng dung lượng 5MB
        if (file.size > 5 * 1024 * 1024) {
            alert('Dung lượng ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.');
            input.value = ''; // Xóa file đã chọn
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imageToCrop').src = e.target.result;
            document.getElementById('cropperModal').style.display = 'flex';
            
            if (cropper) {
                cropper.destroy();
            }
            cropper = new Cropper(document.getElementById('imageToCrop'), {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        }
        reader.readAsDataURL(file);
    }
}

function closeCropperModal() {
    document.getElementById('cropperModal').style.display = 'none';
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    document.getElementById('avatarInput').value = '';
}

function cropImage() {
    if (!cropper) return;
    
    // Lấy canvas sau khi cắt
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    // Convert sang base64
    const base64Image = canvas.toDataURL('image/jpeg', 0.9);
    
    // Cập nhật ảnh hiển thị
    document.getElementById('avatarPreview').src = base64Image;
    
    // Gắn vào input ẩn để gửi lên server
    document.getElementById('croppedAvatarInput').value = base64Image;
    
    closeCropperModal();
}
</script>
@endsection
