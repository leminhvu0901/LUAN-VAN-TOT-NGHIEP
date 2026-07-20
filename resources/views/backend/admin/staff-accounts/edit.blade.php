@extends('backend.layouts.app')

@section('title', 'Sửa Nhân viên - Admin')

@section('content')
    <div class="space-y-6 max-w-3xl mx-auto pb-8">

        {{-- HEADER --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.staff_accounts.index') }}"
                onclick="smartGoBack(event)"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Sửa Nhân viên</h1>
        </div>

        {{-- FORM --}}
        <form action="{{ route('admin.staff_accounts.update', $staff->id) }}" method="POST" enctype="multipart/form-data" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Ảnh đại diện --}}
            <div class="flex flex-col items-center pb-2">
                <div class="relative w-28 h-28 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden mb-3 group">
                    @php
                        if ($staff->avatar) {
                            $avatarUrl = str_starts_with($staff->avatar, 'http') ? $staff->avatar : asset('images/avatars/' . $staff->avatar);
                        } else {
                            $avatarUrl = '';
                        }
                    @endphp
                    <img id="avatar-preview" src="{{ $avatarUrl }}" class="w-full h-full object-cover {{ $avatarUrl ? '' : 'hidden' }}">
                    <div id="avatar-placeholder" class="text-center {{ $avatarUrl ? 'hidden' : '' }}">
                        <span class="material-symbols-outlined text-4xl text-gray-400">account_circle</span>
                    </div>
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" onclick="document.getElementById('avatar-input').click()">
                        <span class="material-symbols-outlined text-white">photo_camera</span>
                    </div>
                </div>
                <input type="file" name="avatar" id="avatar-input" accept="image/*" class="hidden">
                <label for="avatar-input" class="cursor-pointer px-4 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-sm font-medium transition-colors">
                    Thay đổi ảnh
                </label>
                <p class="text-xs text-gray-500 mt-2 text-center">JPG, PNG, GIF, WEBP. Kích thước tối đa 2MB.</p>
                @error('avatar') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-5">
                {{-- Họ và tên --}}
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $staff->name) }}" required maxlength="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="VD: Nguyễn Văn A">
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Email & SĐT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email', $staff->email) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                            placeholder="VD: staff@happytea.com">
                        @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $staff->phone) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                            placeholder="VD: 0901234567">
                        @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Mật khẩu (tùy chọn) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu mới</label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors pr-10"
                                placeholder="Bỏ trống nếu không đổi">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-emerald-600 toggle-password" data-target="password">
                                <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                            </button>
                        </div>
                        @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors pr-10"
                                placeholder="Nhập lại nếu đổi mật khẩu">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-emerald-600 toggle-password" data-target="password_confirmation">
                                <span class="material-symbols-outlined text-[20px] select-none">visibility_off</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Loại nhân viên --}}
                <div>
                    <label for="staff_type" class="block text-sm font-semibold text-gray-700 mb-1">Loại nhân viên <span class="text-red-500">*</span></label>
                    <select name="staff_type" id="staff_type" required class="custom-select-init w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white" data-width-class="w-full">
                        <option value="receptionist" {{ old('staff_type', $staff->staff_type) === 'receptionist' ? 'selected' : '' }}>Nhân viên pha chế</option>
                        <option value="delivery" {{ old('staff_type', $staff->staff_type) === 'delivery' ? 'selected' : '' }}>Nhân viên giao hàng</option>
                    </select>
                    @error('staff_type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Trạng thái --}}
                <div>
                    <label for="is_active" class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái hoạt động</label>
                    <select name="is_active" id="is_active" class="custom-select-init w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white" data-width-class="w-full">
                        <option value="1" {{ old('is_active', $staff->is_active) == '1' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="0" {{ old('is_active', $staff->is_active) == '0' ? 'selected' : '' }}>Khóa</option>
                    </select>
                    @error('is_active') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Nút lưu --}}
            <div class="flex gap-3 pt-4 border-t border-gray-100 justify-end">
                <a href="{{ route('admin.staff_accounts.index') }}"
                    onclick="smartGoBack(event)"
                    class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/backend/admin/staff-accounts/form-common.js') }}"></script>
@endpush
