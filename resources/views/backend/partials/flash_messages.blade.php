{{-- Xử lý flash messages từ controller --}}
@if(session('success') || session('warning') || session('error') || $errors->any())
    <script>
        @if(session('success'))
            window.flashSuccessMessage = {!! json_encode(session('success')) !!};
        @endif

        @if(session('warning'))
            window.flashWarningMessage = {!! json_encode(session('warning')) !!};
        @endif

        @if(session('error') || $errors->any())
            window.flashErrorMessages = {!! json_encode(array_merge(
                session('error') ? [session('error')] : [],
                $errors->all()
            )) !!};
        @endif
    </script>
@endif
