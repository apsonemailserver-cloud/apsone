<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT. Angkasa Pratama Sejahtera</title>
    <link rel="icon" href="{{ asset('storage/aps_mini.png') }}" sizes="48x48" type="image/png">

    <link href="{{ asset('vendor/bootstrap3/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome5/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/material-icons/material-icons.css') }}" rel="stylesheet" />

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="{{ asset('/assets/js/script.js') }}" defer></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Yield for page-specific styles --}}
    @yield('styles')
</head>

<body class="with-sidebar">
    @include('partials.sidebar')

    @include('partials.navbar')

    <div class="main-content">
        <div class="container">

            @yield('content')
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap3/js/bootstrap.min.js') }}"></script>
    @include('sweetalert::alert')
    @yield('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileToggle = document.getElementById('profileToggle');
            const profileCard = document.getElementById('profileCard');

            if (profileToggle && profileCard) {
                profileToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // agar klik tidak lanjut ke body
                    if (profileCard.style.display === 'none' || profileCard.style.display === '') {
                        profileCard.style.display = 'block';
                    } else {
                        profileCard.style.display = 'none';
                    }
                });

                document.addEventListener('click', function(e) {
                    const isClickInside = profileCard.contains(e.target) || profileToggle.contains(e.target);
                    if (!isClickInside) {
                        profileCard.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>

</html>
