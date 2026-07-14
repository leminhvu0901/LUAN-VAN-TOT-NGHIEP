@if(session('success') || $errors->any())
    <script>
        @if(session('success'))
            window.flashSuccessMessage = {!! json_encode(session('success')) !!};
        @endif
        @if($errors->any())
            window.flashErrorMessages = {!! json_encode($errors->all()) !!};
        @endif
    </script>
@endif
