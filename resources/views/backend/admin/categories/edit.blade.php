@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Danh mục')

@section('content')
<div class="p-6 space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.categories.index') }}"
            onclick="smartGoBack(event)"
            class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 hover:bg-gray-50 organic-shadow transition-colors">
            <i class="fa-solid fa-arrow-left text-[14px]"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa danh mục</h2>
            <p class="text-gray-500 text-sm mt-1">Cập nhật thông tin cho danh mục <span class="font-bold">"{{ $category->name }}"</span></p>
        </div>
    </div>

    @if($errors->any())
        @push('scripts')
        <script>
            window.flashErrorMessages = {!! json_encode($errors->all()) !!};
            window.flashErrorTitle = 'Thông báo';
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

            <!-- Trạng thái -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" class="sr-only peer" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
                <span class="text-sm font-semibold text-gray-700">Hiển thị danh mục này</span>
            </div>

        </div>

        <div class="mt-8 flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100">
            <a href="{{ route('admin.categories.index') }}" 
                onclick="smartGoBack(event)"
                class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" 
                class="w-full sm:w-auto text-center px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors organic-shadow">
                Cập nhật danh mục
            </button>
        </div>
    </form>
</div>
@endsection

