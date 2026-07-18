@extends('backend.layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    <div class="p-4 sm:p-6 max-w-xl mx-auto space-y-4">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Hồ sơ cá nhân</h2>

        <div class="bg-amber-50 text-amber-700 border border-amber-200 rounded-xl p-4 flex items-start gap-3 text-sm">
            <span class="material-symbols-outlined text-[20px] mt-0.5">lock</span>
            <span>Bạn chỉ có thể xem thông tin tài khoản. Nếu cần đổi tên, số điện thoại, mật khẩu hoặc loại nhân viên, vui lòng liên hệ quản trị viên (admin) để được cập nhật.</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên</label>
                <input type="text" value="{{ $user->name }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="text" value="{{ $user->email }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" value="{{ $user->phone ?: 'Chưa cập nhật' }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Loại nhân viên</label>
                <input type="text" value="{{ $user->staff_type === 'delivery' ? 'Nhân viên giao hàng' : 'Nhân viên pha chế' }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái tài khoản</label>
                <input type="text" value="{{ $user->is_active ? 'Đang hoạt động' : 'Đã bị khóa' }}" disabled
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 {{ $user->is_active ? 'text-emerald-600' : 'text-rose-500' }}">
            </div>
        </div>
    </div>
@endsection
