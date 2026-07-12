@extends('backend.layouts.app')

@section('title', 'Quản lý Kho Vật Tư')

@section('content')

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        <!-- Phần 1: Tiêu đề trang & Nút Thêm mới -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Kho Vật Tư</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi, cập nhật và quản lý tồn kho nguyên liệu chi tiết.</p>
            </div>
            <div class="flex gap-4 w-full sm:w-auto">

                <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm organic-shadow hover:bg-emerald-700 transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">add</span>
                    Thêm vật tư mới
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Phần 2: Khung Thống kê 6 thẻ -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">

            <!-- Card 1 -->
            <div
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng mặt hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ $totalItems }}</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span> mặt hàng
                    </p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">category</span>
                </div>
            </div>

            <!-- Card 2 -->
            <a href="{{ route('admin.materials.low_stock') }}"
                class="bg-red-50 p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-red-100 hover:bg-red-100 transition-all group cursor-pointer gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-red-600 truncate" title="Sắp hết hàng">Sắp hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-red-600 truncate">{{ $lowStockItems }}</p>
                    <p class="text-red-600 font-medium text-[11px] truncate">Cần nhập kho &rarr;</p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">warning</span>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="{{ route('admin.materials.expiring') }}"
                class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-gray-300 transition-all group cursor-pointer gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate" title="Sắp hết hạn">Sắp hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ $expiringItems ?? 0 }}</p>
                    <p class="text-gray-400 font-medium text-[11px] truncate">Trong 30 ngày</p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 group-hover:bg-gray-100 transition-colors flex-shrink-0">
                    <span class="material-symbols-outlined text-lg">event_busy</span>
                </div>
            </a>

            <!-- Card 4 -->
            <a href="{{ route('admin.materials.expired') }}"
                class="{{ $expiredBatches->count() > 0 ? 'bg-red-100 hover:bg-red-200 border-red-200' : 'bg-gray-50 hover:bg-gray-100 border-gray-200' }} p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border transition-all group cursor-pointer gap-2">
                <div class="space-y-1 min-w-0">
                    <p
                        class="font-semibold text-xs {{ $expiredBatches->count() > 0 ? 'text-red-700' : 'text-gray-500' }} truncate">
                        Đã hết hạn</p>
                    <p
                        class="text-2xl sm:text-3xl font-bold {{ $expiredBatches->count() > 0 ? 'text-red-900' : 'text-gray-900' }} truncate">
                        {{ $expiredBatches->count() }}
                    </p>
                    <p
                        class="{{ $expiredBatches->count() > 0 ? 'text-red-600' : 'text-gray-400' }} font-medium text-[11px] truncate">
                        Xem chi tiết &rarr;</p>
                </div>
                <div
                    class="w-10 h-10 rounded-full {{ $expiredBatches->count() > 0 ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-500' }} flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">delete_forever</span>
                </div>
            </a>

            <!-- Card 5 -->
            <a href="{{ route('admin.materials.disposed') }}"
                class="bg-gray-50 p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all group cursor-pointer gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-700 truncate">Đã xuất huỷ</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ $disposedBatches->count() }}</p>
                    <p class="text-gray-500 font-medium text-[11px] truncate">Xem lịch sử →</p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">remove_shopping_cart</span>
                </div>
            </a>

            <!-- Card 6 -->
            <a href="{{ route('admin.materials.inventory_value') }}"
                class="bg-emerald-50 p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-emerald-100 hover:bg-emerald-100 hover:border-emerald-300 transition-all group gap-2 cursor-pointer">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-emerald-700 truncate">Giá trị kho</p>
                    <p class="text-xl sm:text-2xl font-bold text-emerald-800 truncate">
                        {{ number_format($totalValue / 1000000, 1) }}Mđ
                    </p>
                    <p class="text-emerald-600 font-medium text-[11px] truncate">Xem chi tiết →</p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg"
                        style="font-variation-settings: 'FILL' 1;">payments</span>
                </div>
            </a>
        </div>


        <!-- Phần 3: Thanh Tìm kiếm và Lọc dữ liệu -->
        <div
            class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between bg-white p-4 rounded-xl organic-shadow border border-gray-100 mb-6">
            <form action="{{ route('admin.materials.index') }}" method="GET"
                class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center w-full xl:w-auto">
                <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-64 relative">
                    <span class="material-symbols-outlined text-gray-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-4 w-full outline-none"
                        placeholder="Tìm kiếm vật tư...">
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto">
                    Lọc
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.materials.index') }}"
                        class="px-4 py-2 text-gray-500 hover:text-red-500 font-medium text-sm rounded-lg transition-colors text-center w-full sm:w-auto">
                        Xóa lọc
                    </a>
                @endif
            </form>
            <div class="text-sm font-medium text-gray-500 w-full xl:w-auto text-right">
                Hiển thị {{ $materials->count() }} / {{ $totalItems }} vật tư
            </div>
        </div>

        <!-- Phần 4: Bảng danh sách Vật tư -->
        <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Mã VT</th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Tên vật tư
                            </th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Tồn kho</th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Đơn vị</th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Cảnh báo
                            </th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Trạng thái
                            </th>
                            <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">
                                Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($materials as $material)
                            @php
                                $status = 'Còn hàng';
                                $statusColor = 'bg-emerald-100 text-emerald-700';
                                $dotColor = 'bg-emerald-500';
                                $barColor = 'bg-emerald-500';
                                $barWidth = min(100, max(5, ($material->current_stock / 100) * 100));

                                $closestExp = $material->imports->whereNotNull('expiration_date')->where('remaining_quantity', '>', 0)->sortBy('expiration_date')->first();
                                $daysToExpire = $closestExp ? now()->startOfDay()->diffInDays($closestExp->expiration_date->startOfDay(), false) : null;
                                $hasExpired = $closestExp && $daysToExpire < 0;
                                $isExpiringSoon = $closestExp && $daysToExpire >= 0 && $daysToExpire < 15;

                                if ($hasExpired) {
                                    $batchCode = 'LOT-' . $closestExp->id;
                                    $status = "Lô ({$batchCode}) đã hết hạn";
                                    $statusColor = 'bg-red-100 text-red-700 font-bold';
                                    $dotColor = 'bg-red-500 animate-pulse';
                                } elseif ($isExpiringSoon) {
                                    $batchCode = 'LOT-' . $closestExp->id;
                                    $status = "Lô ({$batchCode}) hết hạn sau {$daysToExpire} ngày";
                                    $statusColor = 'bg-amber-100 text-amber-700 font-bold';
                                    $dotColor = 'bg-amber-500 animate-pulse';
                                } elseif ($material->current_stock < 5 && $material->current_stock > 0) {
                                    $status = 'Sắp hết';
                                    $statusColor = 'bg-orange-100 text-orange-700';
                                    $dotColor = 'bg-orange-500';
                                    $barColor = 'bg-orange-500';
                                } elseif ($material->current_stock == 0) {
                                    $status = 'Cần nhập gấp';
                                    $statusColor = 'bg-red-100 text-red-700';
                                    $dotColor = 'bg-red-500';
                                    $barColor = 'bg-red-500';
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-4 py-3 font-semibold text-sm text-gray-500 whitespace-nowrap">
                                    VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-emerald-600">
                                                {{ str_contains(strtolower($material->name), 'ly') || str_contains(strtolower($material->name), 'nắp') ? 'local_cafe' : (str_contains(strtolower($material->name), 'trà') || str_contains(strtolower($material->name), 'cà phê') ? 'eco' : 'bubble_chart') }}
                                            </span>
                                        </div>
                                        <span class="font-semibold text-sm text-gray-900">{{ $material->name }}</span>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 font-semibold text-sm {{ $material->current_stock < 5 ? 'text-red-600' : 'text-gray-900' }} whitespace-nowrap">
                                    {{ $material->current_stock }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-sm text-gray-900 whitespace-nowrap">
                                    {{ $material->unit }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden min-w-[100px]">
                                        <div class="{{ $barColor }} h-full rounded-full" style="width: {{ $barWidth }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-1 {{ $statusColor }} rounded-lg font-medium text-xs">
                                        <span
                                            class="w-2 h-2 rounded-full {{ $dotColor }} {{ $material->current_stock == 0 ? 'animate-pulse' : '' }}"></span>
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex justify-center">
                                        <a href="{{ route('admin.materials.imports', $material->id) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-xs font-bold whitespace-nowrap">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            Kiểm tra
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 font-medium">Không tìm thấy vật tư
                                    nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>








    <!-- Phan 5: Modal Them Vat tu moi -->
    <div id="modal-add" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden mat-modal-panel">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Thêm Vật Tư Mới</h3>
                <button onclick="closeModal('modal-add')" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('admin.materials.store') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên vật tư</label>
                        <input type="text" name="name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn vị (Kg, Bao, Lốc, Cuộn,
                            Cái...)</label>
                        <input type="text" name="unit" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vốn dự kiến (VNĐ / Đơn vị)</label>
                        <input type="number" name="unit_price" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('modal-add')"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Lưu
                        vật tư</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/backend/materials/common.js') }}"></script>
    <script src="{{ asset('js/backend/materials/index.js') }}"></script>
@endpush