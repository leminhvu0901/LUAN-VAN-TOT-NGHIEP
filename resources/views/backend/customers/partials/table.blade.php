@if ($customers->count() > 0)
    <div class="overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="w-12 px-4 py-4 text-center">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer">
                    </th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Liên hệ</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Điểm / Hạng</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                    <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-right rounded-tr-xl w-24">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($customers as $customer)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <!-- Checkbox -->
                        <td class="px-4 py-4 text-center">
                            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-indigo-500 focus:ring-indigo-400 cursor-pointer" value="{{ $customer->id }}">
                        </td>

                        <!-- Thông tin khách hàng -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    if ($customer->avatar) {
                                        $avatarUrl = str_starts_with($customer->avatar, 'http') ? $customer->avatar : asset('images/avatars/' . $customer->avatar);
                                    } else {
                                        $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($customer->name).'&background=random';
                                    }
                                @endphp
                                <img src="{{ $avatarUrl }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&background=random'" alt="{{ $customer->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $customer->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Ngày tham gia: {{ $customer->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Liên hệ -->
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-gray-400">mail</span>
                                    <span>{{ $customer->email }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="material-symbols-outlined text-[16px] text-gray-400">call</span>
                                    <span>{{ $customer->phone ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Hạng thành viên -->
                        <td class="px-6 py-4 text-center">
                            @php
                                $badgeClass = '';
                                $badgeName = '';
                                switch ($customer->membership_level) {
                                    case 'diamond':
                                        $badgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
                                        $badgeName = 'Kim Cương';
                                        break;
                                    case 'gold':
                                        $badgeClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                        $badgeName = 'Vàng';
                                        break;
                                    case 'silver':
                                        $badgeClass = 'bg-gray-200 text-gray-700 border-gray-300';
                                        $badgeName = 'Bạc';
                                        break;
                                    case 'new':
                                    default:
                                        $badgeClass = 'bg-green-100 text-green-700 border-green-200';
                                        $badgeName = 'Mới';
                                        break;
                                }
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                {{ $badgeName }}
                            </span>
                            <p class="text-xs text-gray-500 mt-1 font-medium">{{ number_format($customer->points ?? 0) }} điểm</p>
                        </td>

                        <!-- Trạng thái -->
                        <td class="px-6 py-4 text-center">
                            <label class="relative inline-flex items-center cursor-pointer" title="Nhấp để đổi trạng thái">
                                <input type="checkbox" class="sr-only peer toggle-status" data-id="{{ $customer->id }}" {{ $customer->is_active ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 transition-colors"></div>
                            </label>
                            <p class="text-[11px] font-medium mt-1 {{ $customer->is_active ? 'text-emerald-600' : 'text-rose-500' }}" id="status-text-{{ $customer->id }}">
                                {{ $customer->is_active ? 'Hoạt động' : 'Bị khóa' }}
                            </p>
                        </td>

                        <!-- Thao tác -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.customers.show', $customer->id) }}" 
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                    title="Xem chi tiết">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer->id) }}" 
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-amber-500 hover:bg-amber-50 transition-colors"
                                    title="Chỉnh sửa">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                                <form id="delete-form-{{ $customer->id }}" action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="m-0 hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" 
                                    onclick="deleteCustomer({{ $customer->id }});"
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors"
                                    title="Xóa tài khoản">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
        {{ $customers->links('pagination::tailwind') }}
    </div>

@else
    <div class="p-12 text-center">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
            <span class="material-symbols-outlined text-4xl text-gray-400">group_off</span>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Không tìm thấy khách hàng</h3>
        <p class="text-gray-500 text-sm">Hãy thử thay đổi điều kiện lọc hoặc từ khóa tìm kiếm.</p>
    </div>
@endif
