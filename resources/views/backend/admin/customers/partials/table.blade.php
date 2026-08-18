@if ($customers->count() > 0)
    <!-- Giao diện Mobile -->
    <div class="block md:hidden space-y-4 p-4">
        <div class="flex items-center justify-between mb-2">
            <label class="flex items-center gap-2 text-sm text-gray-600 font-medium cursor-pointer">
                <input type="checkbox" id="selectAll-mobile" class="js-select-all rounded border-gray-300 text-emerald-500 focus:ring-emerald-400">
                <span>Chọn tất cả</span>
            </label>
        </div>

        @foreach($customers as $customer)
            <div class="bg-white p-4 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3 relative group" id="customer-card-{{ $customer->id }}">
                <!-- Header -->
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        @php
                            if ($customer->avatar) {
                                $avatarUrl = avatar_url($customer->avatar);
                            } else {
                                $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($customer->name).'&background=random';
                            }
                        @endphp
                        <img src="{{ $avatarUrl }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&background=random'" alt="{{ $customer->name }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 shadow-sm">
                        <div class="flex flex-col min-w-0">
                            <span class="text-base font-bold text-gray-900 truncate">{{ $customer->name }}</span>
                            <span class="text-xs text-gray-500">{{ $customer->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-500 focus:ring-emerald-400 cursor-pointer" value="{{ $customer->id }}">
                </div>

                <!-- Thông tin liên hệ -->
                <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100 mt-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 text-sm text-gray-700 overflow-hidden">
                        <i class="fa-regular fa-envelope text-[13px] text-gray-400 shrink-0"></i>
                        <span class="truncate" style="overflow-wrap: anywhere; word-break: break-word;">{{ $customer->email }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <i class="fa-solid fa-phone text-[13px] text-gray-400 shrink-0"></i>
                        <span>{{ $customer->phone ?? 'Chưa cập nhật' }}</span>
                    </div>
                </div>

                <!-- Hạng & Điểm -->
                <div class="flex items-center justify-between mt-1 px-1">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Hạng</span>
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
                                    $badgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                    $badgeName = 'Mới';
                                    break;
                            }
                        @endphp
                        <span class="inline-flex justify-center items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                            {{ $badgeName }}
                        </span>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Điểm tích lũy</span>
                        <span class="text-sm font-semibold text-gray-800">{{ number_format($customer->points ?? 0) }} đ</span>
                    </div>
                </div>

                <hr class="border-gray-100 border-dashed my-1">

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <form method="POST" action="{{ route('admin.customers.toggle_status', $customer->id) }}">
                            @csrf
                            <input type="hidden" name="is_active" value="{{ $customer->is_active }}">
                            <input type="hidden" name="lock_reason" value="">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer toggle-status" {{ $customer->is_active ? 'checked' : '' }}>
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500 transition-colors"></div>
                            </label>
                        </form>
                        <span class="text-[10px] font-semibold {{ $customer->is_active ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $customer->is_active ? 'Hoạt động' : 'Bị khóa' }}
                        </span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.customers.show', $customer->id) }}"
                            class="px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1" title="Xem">
                            <i class="fa-solid fa-eye text-[13px]"></i>
                            Xem
                        </a>
                        <a href="{{ route('admin.customers.edit', $customer->id) }}"
                            class="px-3 py-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1" title="Sửa">
                            <i class="fa-solid fa-pen text-[13px]"></i>
                            Sửa
                        </a>
                        <button type="button"
                            onclick="deleteCustomer({{ $customer->id }});"
                            class="px-3 py-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1" title="Xóa">
                            <i class="fa-solid fa-trash-can text-[13px]"></i>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Giao diện Desktop -->
    <div class="hidden md:block overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="w-12 px-4 py-4 text-center">
                        <input type="checkbox" id="selectAll" class="js-select-all rounded border-gray-300 text-emerald-500 focus:ring-emerald-400 cursor-pointer">
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
                            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-500 focus:ring-emerald-400 cursor-pointer" value="{{ $customer->id }}">
                        </td>

                        <!-- Thông tin khách hàng -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    if ($customer->avatar) {
                                        $avatarUrl = avatar_url($customer->avatar);
                                    } else {
                                        $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($customer->name).'&background=random';
                                    }
                                @endphp
                                <img src="{{ $avatarUrl }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&background=random'" alt="{{ $customer->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors">{{ $customer->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Ngày tham gia: {{ $customer->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Liên hệ -->
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-envelope text-[13px] text-gray-400"></i>
                                    <span>{{ $customer->email }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <i class="fa-solid fa-phone text-[13px] text-gray-400"></i>
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
                                        $badgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
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
                            <form method="POST" action="{{ route('admin.customers.toggle_status', $customer->id) }}">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $customer->is_active }}">
                                <input type="hidden" name="lock_reason" value="">
                                <label class="relative inline-flex items-center cursor-pointer" title="Nhấp để đổi trạng thái">
                                    <input type="checkbox" class="sr-only peer toggle-status" {{ $customer->is_active ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 transition-colors"></div>
                                </label>
                            </form>
                            <p class="text-[11px] font-medium mt-1 {{ $customer->is_active ? 'text-emerald-600' : 'text-rose-500' }}" {!! !$customer->is_active && $customer->lock_reason ? 'title="Lý do: '.e($customer->lock_reason).'"' : '' !!}>
                                {{ $customer->is_active ? 'Hoạt động' : 'Bị khóa' }}
                            </p>
                        </td>

                        <!-- Thao tác -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.customers.show', $customer->id) }}" 
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                    title="Xem chi tiết">
                                    <i class="fa-solid fa-eye text-[14px]"></i>
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer->id) }}" 
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen text-[14px]"></i>
                                </a>
                                <form id="delete-form-{{ $customer->id }}" action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="m-0 hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" 
                                    onclick="deleteCustomer({{ $customer->id }});"
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors"
                                    title="Xóa tài khoản">
                                    <i class="fa-solid fa-trash-can text-[14px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <div class="pagination-container px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
        {{ $customers->links('pagination::tailwind') }}
    </div>

@else
    <div class="p-12 text-center bg-white rounded-b-2xl">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
            <i class="fa-solid fa-users-slash text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Không tìm thấy khách hàng</h3>
        <p class="text-gray-500 text-sm">Hãy thử thay đổi điều kiện lọc hoặc từ khóa tìm kiếm.</p>
    </div>
@endif
