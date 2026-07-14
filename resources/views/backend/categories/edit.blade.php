@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Danh mục')

@section('content')
<div class="p-6 space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.categories.index') }}" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 hover:bg-gray-50 organic-shadow transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa danh mục</h2>
            <p class="text-gray-500 text-sm mt-1">Cập nhật thông tin cho danh mục <span class="font-bold">"{{ $category->name }}"</span></p>
        </div>
    </div>

    @if($errors->any())
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thông báo',
                        html: `
                            <ul class="text-left text-sm text-gray-600 list-disc pl-5 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        `,
                        width: '320px',
                        padding: '1rem',
                        confirmButtonText: 'Đóng',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            htmlContainer: 'mt-1',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                }
            });
        </script>
        @endpush
    @endif

    <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl organic-shadow p-6 sm:p-8 border border-gray-100">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            <!-- Tên danh mục -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Tên danh mục <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required maxlength="20"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                    placeholder="VD: Trà sữa... (Tối đa 20 ký tự)">
            </div>

            <!-- Thứ tự hiển thị -->
            <div>
                <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
                    Thứ tự hiển thị
                </label>
                <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $category->display_order) }}" min="0"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                <p class="text-xs text-gray-500 mt-1">Số nhỏ hơn sẽ xếp lên trước.</p>
            </div>

            <!-- Trạng thái (is_active) -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" class="sr-only peer" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
                <span class="text-sm font-semibold text-gray-700">Hiển thị danh mục này</span>
            </div>

        </div>

        <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
            <a href="{{ route('admin.categories.index') }}" 
                class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" 
                class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors organic-shadow">
                Cập nhật danh mục
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
