@if (config('sweetalert.alwaysLoadJS') === true || Session::has('alert.config') || Session::has('alert.delete') || Session::has('success') || Session::has('error') || Session::has('warning') || Session::has('info') || (isset($errors) && $errors->any()))
    @if (config('sweetalert.animation.enable'))
        <link rel="stylesheet" href="{{ config('sweetalert.animatecss') }}">
    @endif

    @if (config('sweetalert.theme') != 'default')
        <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-{{ config('sweetalert.theme') }}" rel="stylesheet">
    @endif

    @if (config('sweetalert.neverLoadJS') === false)
        <script src="{{ $cdn ?? asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
    @endif

    <script>
        document.addEventListener('click', function(event) {
            var target = event.target;
            var confirmDeleteElement = target.closest('[data-confirm-delete]');

            if (confirmDeleteElement) {
                event.preventDefault();
                @if (Session::has('alert.delete'))
                Swal.fire({!! Session::pull('alert.delete') !!}).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.action = confirmDeleteElement.href;
                        form.method = 'POST';
                        form.innerHTML = `@csrf @method('DELETE')`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
                @endif
            }
        });

        @if (Session::has('alert.config'))
            Swal.fire({!! Session::pull('alert.config') !!});
        @elseif (Session::has('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: {!! json_encode(Session::get('success')) !!},
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        @elseif (Session::has('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: {!! json_encode(Session::get('error')) !!},
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        @elseif (Session::has('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: {!! json_encode(Session::get('warning')) !!},
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        @elseif (Session::has('info'))
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: {!! json_encode(Session::get('info')) !!},
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        @elseif (isset($errors) && $errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal!',
                html: '<ul class="text-start mb-0 ps-3">' +
                    {!! json_encode($errors->all()) !!}.map(err => '<li>' + err + '</li>').join('') +
                    '</ul>',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        @endif
    </script>
@endif
