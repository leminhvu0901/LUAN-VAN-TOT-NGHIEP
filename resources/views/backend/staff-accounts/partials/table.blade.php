@if ($staffs->count() > 0)
    <!-- Giao diện Mobile (Card view) -->
    <div class="block md:hidden space-y-4 p-4">
        @foreach($staffs as $staff)
            <div class="bg-white p-4 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3 relative group" id="staff-card-{{ $staff->id }}">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        @php
                            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($staff->name).'&background=random';
                        @endphp
                        <img src="{{ $avatarUrl }}" alt="{{ $staff->name }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 shadow-sm">
                        <div class="flex flex-col min-w-0">
                            <span class="text-base font-bold text-gray-900 truncate">{{ $staff->name }}</span>
                            <span class="text-xs text-gray-500">{{ $staff->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Thông tin liên hệ -->
                <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100 mt-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 text-sm text-gray-700 overflow-hidden">
                        <span class="material-symbols-outlined text-[16px] text-gray-400 shrink-0">mail</span>
                        <span class="truncate" style="overflow-wrap: anywhere; word-break: break-word;">{{ $staff->email }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="material-symbols-outlined text-[16px] text-gray-400 shrink-0">call</span>
                        <span>{{ $staff->phone ?? 'Chưa cập nhật' }}</span>
                    </div>
                </div>

                <hr class="border-gray-100 border-dashed my-1">

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer toggle-status" data-id="{{ $staff->id }}" {{ $staff->is_active ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500 transition-colors"></div>
                        </label>
                        <span class="text-[10px] font-semibold {{ $staff->is_active ? 'text-emerald-600' : 'text-rose-500' }}" id="status-text-mobile-{{ $staff->id }}" {!! !$staff->is_active && $staff->lock_reason ? 'title="Lý do: ' . e($staff->lock_reason) . '"' : '' !!}>
                            {{ $staff->is_active ? 'Hoạt động' : 'Bị khóa' }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Giao diện Desktop (Table view) -->
    <div class="hidden md:block overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Họ và tên</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Số điện thoại</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Ngày tạo</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($staffs as $staff)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <!-- Họ và tên -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($staff->name).'&background=random';
                                @endphp
                                <img src="{{ $avatarUrl }}" alt="{{ $staff->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                <div class="font-bold text-gray-900 group-hover:text-primary transition-colors">{{ $staff->name }}</div>
                            </div>
                        </td>

                        <!-- Email -->
                        <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                            {{ $staff->email }}
                        </td>

                        <!-- SĐT -->
                        <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                            {{ $staff->phone ?? '-' }}
                        </td>

                        <!-- Ngày tạo -->
                        <td class="px-6 py-4 text-center text-sm text-gray-500 whitespace-nowrap">
                            {{ $staff->created_at->format('H:i d/m/Y') }}
                        </td>

                        <!-- Trạng thái -->
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <div class="flex flex-col items-center gap-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer toggle-status" data-id="{{ $staff->id }}" {{ $staff->is_active ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500 transition-colors"></div>
                                </label>
                                <span class="text-[10px] font-bold {{ $staff->is_active ? 'text-emerald-600' : 'text-rose-500' }} transition-colors" id="status-text-{{ $staff->id }}" {!! !$staff->is_active && $staff->lock_reason ? 'title="Lý do: ' . e($staff->lock_reason) . '"' : '' !!}>
                                    {{ $staff->is_active ? 'Hoạt động' : 'Bị khóa' }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    @if ($staffs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-white">
            {{ $staffs->links('pagination::tailwind') }}
        </div>
    @endif
@else
    <div class="text-center py-16 px-4 bg-white w-full">
        <span class="material-symbols-outlined text-6xl text-gray-200 mb-4 select-none">badge</span>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Không tìm thấy tài khoản nhân viên nào</h3>
        <p class="text-gray-500 text-sm max-w-sm mx-auto">Vui lòng thử lại với từ khóa tìm kiếm hoặc bộ lọc khác.</p>
    </div>
@endif
