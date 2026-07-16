@extends('backend.layouts.app')

@section('title', 'Thêm Nhân viên mới - Admin')

@section('content')
    <div class="space-y-6 max-w-3xl mx-auto pb-8">

        {{-- HEADER --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.staff_accounts.index') }}"
                onclick="if(document.referrer.includes(window.location.host)) { event.preventDefault(); window.history.back(); }"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Thêm Nhân viên</h1>
        </div>

        {{-- FORM --}}
        <form action="{{ route('admin.staff_accounts.store') }}" method="POST" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden p-6 space-y-6">
            @csrf

            <div class="space-y-5">
                {{-- Họ và tên --}}
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="VD: Nguyễn Văn A">
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Email & SĐT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                            placeholder="VD: staff@happytea.com">
                        @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors pr-10"
                                placeholder="Tối thiểu 8 ký tự">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-emerald-600 toggle-password" data-target="password">
                                <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                            </button>
                        </div>
                        @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors pr-10"
                                placeholder="Nhập lại mật khẩu">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-emerald-600 toggle-password" data-target="password_confirmation">
                                <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Trạng thái --}}
                <div>
                    <label for="is_active" class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái hoạt động</label>
                    <select name="is_active" id="is_active" class="custom-select-init w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white" data-width-class="w-full">
                        <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Khóa</option>
                    </select>
                    @error('is_active') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Nút lưu --}}
            <div class="flex gap-3 pt-4 border-t border-gray-100 justify-end">
                <a href="{{ route('admin.staff_accounts.index') }}"
                    onclick="if(document.referrer.includes(window.location.host)) { event.preventDefault(); window.history.back(); }"
                    class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Lưu nhân viên
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Hiển thị/ẩn mật khẩu
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('.material-symbols-outlined');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.textContent = 'visibility';
                    } else {
                        input.type = 'password';
                        icon.textContent = 'visibility_off';
                    }
                });
            });
        });
    </script>
@endpush
