@extends('backend.layouts.app')

@section('title', 'Thêm Khách hàng mới')

@section('content')
    <div class="space-y-6 max-w-5xl mx-auto pb-8">

        {{-- HEADER --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.customers.index') }}"
                onclick="smartGoBack(event)"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Thêm Khách hàng</h1>
        </div>

        {{-- FORM --}}
        <form action="{{ route('admin.customers.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf

            {{-- Cột trái (Chiếm 2/3): Thông tin cá nhân --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900">Thông tin cá nhân</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        {{-- Tên --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                placeholder="VD: Nguyễn Văn A">
                            <p id="name-error" class="text-red-500 text-sm mt-1 hidden"></p>
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Email & SĐT --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="VD: email@example.com">
                                <p id="email-error" class="text-red-500 text-sm mt-1 hidden"></p>
                                @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="VD: 0901234567">
                                @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Mật khẩu --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="password" id="password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10"
                                        placeholder="Tối thiểu 8 ký tự">
                                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-indigo-600 toggle-password" data-target="password">
                                        <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                                    </button>
                                </div>
                                @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10"
                                        placeholder="Nhập lại mật khẩu">
                                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-indigo-600 toggle-password" data-target="password_confirmation">
                                        <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                                    </button>
                                </div>
                                <p id="password-match-error" class="text-red-500 text-sm mt-1 hidden"></p>
                            </div>
                        </div>

                        {{-- Điểm & Địa chỉ --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="md:col-span-1">
                                <label for="points" class="block text-sm font-semibold text-gray-700 mb-1">Điểm tích lũy</label>
                                <input type="number" name="points" id="points" value="{{ old('points', 0) }}" min="0" max="900000" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <p id="points-error" class="text-red-500 text-sm mt-1 hidden"></p>
                                @error('points') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Nhập địa chỉ đầy đủ">
                                @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột phải (Chiếm 1/3): Avatar & Cấu hình phụ --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Ảnh đại diện --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900">Ảnh đại diện</h3>
                    </div>
                    <div class="p-6 flex flex-col items-center">
                        <div class="relative w-32 h-32 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden mb-4 group">
                            <img id="avatar-preview" src="" class="w-full h-full object-cover hidden">
                            <div id="avatar-placeholder" class="text-center">
                                <span class="material-symbols-outlined text-4xl text-gray-400">account_circle</span>
                            </div>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" onclick="document.getElementById('avatar-input').click()">
                                <span class="material-symbols-outlined text-white">photo_camera</span>
                            </div>
                        </div>
                        <input type="file" name="avatar" id="avatar-input" accept="image/*" class="hidden">
                        <label for="avatar-input" class="cursor-pointer px-4 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-sm font-medium transition-colors">
                            Chọn ảnh
                        </label>
                        <p class="text-xs text-gray-500 mt-2 text-center">JPG, PNG, GIF. Kích thước tối đa 2MB.</p>
                        @error('avatar') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Hạng & Trạng thái --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900">Thiết lập</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        {{-- Hạng thành viên --}}
                        <div>
                            <label for="membership_level" class="block text-sm font-semibold text-gray-700 mb-1">Hạng thành viên</label>
                            <select name="membership_level" id="membership_level" class="custom-select-init w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white" data-width-class="w-full">
                                <option value="new" {{ old('membership_level') == 'new' ? 'selected' : '' }}>Mới (New)</option>
                                <option value="silver" {{ old('membership_level') == 'silver' ? 'selected' : '' }}>Bạc (Silver)</option>
                                <option value="gold" {{ old('membership_level') == 'gold' ? 'selected' : '' }}>Vàng (Gold)</option>
                                <option value="diamond" {{ old('membership_level') == 'diamond' ? 'selected' : '' }}>Kim cương (Diamond)</option>
                            </select>
                            @error('membership_level') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Trạng thái --}}
                        <div>
                            <label for="is_active" class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái tài khoản</label>
                            <select name="is_active" id="is_active" class="custom-select-init w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white" data-width-class="w-full">
                                <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Khóa</option>
                            </select>
                            @error('is_active') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Nút Submit --}}
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('admin.customers.index') }}"
                        onclick="smartGoBack(event)"
                        class="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-center font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        Hủy
                    </a>
                    <button type="submit" class="flex-[2] px-4 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Lưu khách hàng
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPlaceholder = document.getElementById('avatar-placeholder');

    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    if (window.AdminAlert) {
                        window.AdminAlert.error('Kích thước ảnh không được vượt quá 2MB.', 'Lỗi tải ảnh');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Lỗi', 'Kích thước ảnh không được vượt quá 2MB.', 'error');
                    } else {
                        alert('Kích thước ảnh không được vượt quá 2MB.');
                    }
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('hidden');
                    if (avatarPlaceholder) {
                        avatarPlaceholder.classList.add('hidden');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    const nameInput = document.getElementById('name');
    const nameError = document.getElementById('name-error');
    if (nameInput && nameError) {
        nameInput.addEventListener('input', function () {
            if (this.value.length >= 100) {
                nameError.textContent = 'Tên không được vượt quá 100 ký tự.';
                nameError.classList.remove('hidden');
            } else {
                nameError.classList.add('hidden');
            }
        });
    }

    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|info)(\.vn)?$/i;

    if (emailInput && emailError) {
        emailInput.addEventListener('input', function () {
            if (this.value.length > 0 && !emailRegex.test(this.value)) {
                emailError.textContent = 'Email không hợp lệ (Vui lòng dùng đuôi phổ biến như .com, .vn, .net...).';
                emailError.classList.remove('hidden');
            } else {
                emailError.classList.add('hidden');
            }
        });
    }

    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('span');
            
            if (input && input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
                icon.classList.add('text-indigo-600');
            } else if (input) {
                input.type = 'password';
                icon.textContent = 'visibility_off';
                icon.classList.remove('text-indigo-600');
            }
        });
    });

    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const passwordMatchError = document.getElementById('password-match-error');

    // So khớp ô mật khẩu và ô nhập lại, báo lệch ngay lúc gõ
    function checkPasswordMatch() {
        if (passwordConfirmInput && passwordConfirmInput.value.length > 0 && passwordInput && passwordInput.value !== passwordConfirmInput.value) {
            passwordMatchError.textContent = 'Xác nhận mật khẩu không khớp.';
            passwordMatchError.classList.remove('hidden');
        } else if (passwordMatchError) {
            passwordMatchError.classList.add('hidden');
        }
    }

    if (passwordInput && passwordConfirmInput && passwordMatchError) {
        passwordInput.addEventListener('input', checkPasswordMatch);
        passwordConfirmInput.addEventListener('input', checkPasswordMatch);
    }

    const pointsInput = document.getElementById('points');
    const pointsError = document.getElementById('points-error');
    if (pointsInput && pointsError) {
        pointsInput.addEventListener('input', function () {
            const pointsValue = parseInt(this.value, 10);
            if (pointsValue > 900000) {
                pointsError.textContent = 'Điểm tích lũy không được vượt quá 900.000.';
                pointsError.classList.remove('hidden');
            } else {
                pointsError.classList.add('hidden');
            }
        });
    }
});
</script>
@endpush

