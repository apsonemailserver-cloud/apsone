<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('template/assets') }}/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>PT. Angkasa Pratama Sejahtera</title>

    <meta name="description" content="PT. Angkasa Pratama Sejahtera - Layanan Operational & Management System Terpadu" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('storage/aps_mini.png') }}" sizes="48x48" type="image/png">

    <link rel="dns-prefetch" href="//fonts.googleapis.com" />
    <link rel="dns-prefetch" href="//fonts.gstatic.com" />
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net" />
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/fonts/boxicons.css') }}" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css" media="print" onload="this.media='all'">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap">
        <link rel="stylesheet" href="{{ asset('template/assets/vendor/fonts/boxicons.css') }}">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    </noscript>

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/core.min.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/theme-default.min.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/demo.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/custom-admin.min.css') }}?v={{ filemtime(public_path('template/assets/css/custom-admin.min.css')) }}" />

    <script src="{{ asset('template/assets/vendor/js/helpers.js') }}" defer></script>
    <script src="{{ asset('template/assets/js/config.js') }}" defer></script>

    <!-- pjax-page-styles-start -->
    @yield('styles')
    <!-- pjax-page-styles-end -->

    <!-- PJAX Event Listener Shim: Ensures DOMContentLoaded callbacks execute even after initial page load -->
    <script>
        (function() {
            const origDocAdd = Document.prototype.addEventListener;
            const origWinAdd = Window.prototype.addEventListener;

            Document.prototype.addEventListener = function(type, listener, options) {
                if (type === 'DOMContentLoaded' && (document.readyState === 'interactive' || document.readyState === 'complete')) {
                    if (typeof listener === 'function') {
                        setTimeout(function() { listener.call(document, new Event('DOMContentLoaded')); }, 0);
                    }
                    return;
                }
                return origDocAdd.call(this, type, listener, options);
            };

            Window.prototype.addEventListener = function(type, listener, options) {
                if (type === 'DOMContentLoaded' && (document.readyState === 'interactive' || document.readyState === 'complete')) {
                    if (typeof listener === 'function') {
                        setTimeout(function() { listener.call(window, new Event('DOMContentLoaded')); }, 0);
                    }
                    return;
                }
                return origWinAdd.call(this, type, listener, options);
            };
        })();
    </script>

    <!-- 4. State Management (Anti-Refresh/Flicker/FOUC Prevention) -->
    <script>
        // Eksekusi LANGSUNG sebelum body dirender untuk mencegah Flash/Layout Shift saat Refresh
        (function() {
            document.documentElement.classList.add('no-transitions');

            const theme = localStorage.getItem('apsTheme') || 'light';
            document.documentElement.classList.toggle('aps-dark', theme === 'dark');
            document.documentElement.setAttribute('data-aps-theme', theme);

            const state = localStorage.getItem('customSidebarState');
            if (state === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }

            // Hapus kelas no-transitions secara mulus setelah render awal selesai
            window.addEventListener('DOMContentLoaded', function() {
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        document.documentElement.classList.remove('no-transitions');
                    });
                });
            });
        })();
    </script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="{{ route('home') }}" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <img src="{{ asset('storage/aps_mini.png') }}" alt="APS Logo" width="70" height="70" loading="eager" fetchpriority="high" decoding="async"
                                style="width: 70px; height: auto;">
                        </span>
                    </a>

                    <button type="button" id="custom-sidebar-close-mobile"
                        class="menu-link text-large ms-auto d-block d-xl-none border-0 bg-transparent p-0"
                        aria-label="Tutup menu sidebar">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                        <span class="visually-hidden">Tutup</span>
                    </button>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Menu</span>
                    </li>

                    @if (Auth::user()->canAccess('dashboard', 'view'))
                    <li class="menu-item {{ request()->routeIs('home') ? 'active' : '' }}">
                        <a href="{{ route('home') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    @endif

                    <li class="menu-item {{ request()->routeIs('users.profile') ? 'active' : '' }}">
                        <a href="{{ route('users.profile', Auth::user()->id) }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-user-circle"></i>
                            <div data-i18n="Profile">Profile</div>
                        </a>
                    </li>

                    @if (Auth::user()->canAccess('schedule', 'view'))
                    <li class="menu-item {{ request()->is('schedule*') || request()->routeIs('schedule.*') ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-calendar-week"></i>
                            <div data-i18n="Schedule">Schedule</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('schedule.now') ? 'active' : '' }}">
                                <a href="{{ route('schedule.now') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-calendar-check"></i>
                                    <div data-i18n="Today's Schedule">Today's Schedule</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('schedule.index') ? 'active' : '' }}">
                                <a href="{{ route('schedule.index') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-calendar"></i>
                                    <div data-i18n="Schedule List">Schedule List</div>
                                </a>
                            </li>
                            @if (Auth::user()->canAccess('schedule', 'create') || Auth::user()->canAccess('schedule', 'edit'))
                                <li
                                    class="menu-item {{ request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') || request()->routeIs('schedule.view') || request()->routeIs('schedule.show') ? 'active' : '' }}">
                                    <a href="{{ route('schedule.view') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-calendar-plus"></i>
                                        <div data-i18n="Create/Update">Create / Update</div>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('shift', 'view'))
                    <li class="menu-item {{ request()->routeIs('shift.*') ? 'active' : '' }}">
                        <a href="{{ route('shift.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-clock"></i>
                            <div data-i18n="Shift">Shift</div>
                        </a>
                    </li>
                    @endif


                    @if (Auth::user()->canAccess('attendance', 'view') || Auth::user()->canAccess('overtime', 'view') || Auth::user()->canAccess('attendance', 'approve') || Auth::user()->canAccess('overtime', 'approve'))
                    <li
                        class="menu-item {{ request()->is('attendance*') || request()->is('overtime*') ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-circle-check"></i>
                            <div data-i18n="Attendance">Attendance</div>
                        </a>

                        <ul class="menu-sub">

                            @if (Auth::user()->canAccess('attendance', 'view'))
                            <li class="menu-item {{ request()->routeIs('attendance.index') || request()->routeIs('attendance.history') || request()->routeIs('attendance.camera') || request()->routeIs('attendance.corrections.create') || request()->routeIs('attendance.corrections.store') ? 'active' : '' }}">
                                <a href="{{ route('attendance.index') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-stopwatch"></i>
                                    <div data-i18n="Today's Attendance">Today's Attendance</div>
                                </a>
                            </li>
                            @endif

                            @if (Auth::user()->canAccess('attendance', 'export'))
                                <li class="menu-item {{ request()->routeIs('attendance.reports') ? 'active' : '' }}">
                                    <a href="{{ route('attendance.reports') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-file-text"></i>
                                        <div data-i18n="Attendance Report">Attendance Report</div>
                                    </a>
                                </li>
                            @endif

                            @if (Auth::user()->canAccess('attendance', 'approve') || \App\Models\User::where('manager', Auth::user()->fullname)->exists())
                                <li class="menu-item {{ request()->routeIs('attendance.corrections.approval') ? 'active' : '' }}">
                                    <a href="{{ route('attendance.corrections.approval') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-user-check"></i>
                                        <div data-i18n="Correction Approvals">Correction Approvals</div>
                                    </a>
                                </li>
                            @endif


                            @if (Auth::user()->canAccess('overtime', 'view'))
                            <li
                                class="menu-item {{ request()->routeIs('overtime.index') || request()->routeIs('overtime.create') ? 'active' : '' }}">
                                <a href="{{ route('overtime.index') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-hourglass"></i>
                                    <div data-i18n="My Overtime">My Overtime</div>
                                </a>
                            </li>
                            @endif


                            @if (Auth::user()->canAccess('overtime', 'approve'))
                                <li class="menu-item {{ request()->routeIs('overtime.approval') ? 'active' : '' }}">
                                    <a href="{{ route('overtime.approval') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-circle-check"></i>
                                        <div data-i18n="Overtime Approvals">Overtime Approvals</div>
                                    </a>
                                </li>
                            @endif

                            @if (Auth::user()->canAccess('overtime', 'export'))
                                <li class="menu-item {{ request()->routeIs('overtime.report') ? 'active' : '' }}">
                                    <a href="{{ route('overtime.report') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-chart-line"></i>
                                        <div data-i18n="Overtime Report">Overtime Report</div>
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </li>
                    @endif

                    {{-- MENU ASSIGNMENT --}}
                    @if(Auth::user()->canAccess('assignment', 'view'))
                    <li class="menu-item {{ request()->routeIs('work_results.*') || request()->routeIs('work_orders.*') ? 'active' : '' }}">
                        <a href="{{ route('work_results.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-plane-arrival"></i>
                            <div data-i18n="Assignment">Assignment</div>
                        </a>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('station', 'view') || Auth::user()->canAccess('user', 'view') || Auth::user()->canAccess('blacklist', 'view') || Auth::user()->canAccess('role', 'view'))
                        {{-- HEADER KHUSUS ADMIN --}}
                        <li class="menu-header small text-uppercase">
                            <span class="menu-header-text">Administrator</span>
                        </li>

                        {{-- MENU BARU: STATION MANAGEMENT (ON/OFF) --}}
                        @if(Auth::user()->canAccess('station', 'view'))
                        <li class="menu-item {{ request()->routeIs('stations.*') ? 'active' : '' }}">
                            <a href="{{ route('stations.index') }}" class="menu-link">
                                <i class="menu-icon tf-icons ti ti-building-store"></i>
                                <div data-i18n="Station Management">Station Management</div>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->canAccess('user', 'view') || Auth::user()->canAccess('blacklist', 'view') || Auth::user()->canAccess('role', 'view'))
                        <li
                            class="menu-item {{ request()->routeIs('staff.*') || request()->routeIs('blacklist.*') || request()->routeIs('roles.*') || request()->routeIs('users.kontrak*') || request()->routeIs('users.Kontrak*') || request()->routeIs('users.pas*') || request()->routeIs('users.PAS*') || request()->routeIs('users.tim*') || request()->routeIs('users.TIM*') || (request()->routeIs('users.edit') && (str_contains(request('redirect_to', ''), 'staff') || str_contains(url()->previous(), 'staff-data'))) ? 'active open' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                                <i class="menu-icon tf-icons ti ti-users"></i>
                                <div data-i18n="User Management">User Management</div>
                            </a>
                            <ul class="menu-sub">
                                @if(Auth::user()->canAccess('user', 'view'))
                                <li class="menu-item {{ request()->routeIs('staff.*') || (request()->routeIs('users.edit') && (str_contains(request('redirect_to', ''), 'staff') || str_contains(url()->previous(), 'staff-data'))) ? 'active' : '' }}">
                                    <a href="{{ route('staff.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-device-desktop"></i>
                                        <div data-i18n="Station Monitoring">Station Monitoring</div>
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->canAccess('role', 'view'))
                                <li class="menu-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                    <a href="{{ route('roles.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                                        <div data-i18n="Role & Permissions">Role & Permissions</div>
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->canAccess('blacklist', 'view'))
                                <li class="menu-item {{ request()->routeIs('blacklist.*') ? 'active' : '' }}">
                                    <a href="{{ route('blacklist.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-user-x"></i>
                                        <div data-i18n="Blacklist Staff">Blacklist Staff</div>
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->canAccess('user', 'view'))
                                <li class="menu-item {{ request()->routeIs('users.kontrak*') || request()->routeIs('users.Kontrak*') ? 'active' : '' }}">
                                    <a href="{{ route('users.kontrak') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-file-text"></i>
                                        <div data-i18n="Contracts">Contracts</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('users.pas*') || request()->routeIs('users.PAS*') ? 'active' : '' }}">
                                    <a href="{{ route('users.pas') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-id"></i>
                                        <div data-i18n="Airport PAS">Airport PAS</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('users.tim*') || request()->routeIs('users.TIM*') ? 'active' : '' }}">
                                    <a href="{{ route('users.tim') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-badge"></i>
                                        <div data-i18n="Airport TIM">Airport TIM</div>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif
                    @endif

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">General</span>
                    </li>

                    {{-- MENU DOKUMEN --}}
                    @if(Auth::user()->canAccess('document', 'view'))
                    @php
                        $dokumenRoute = (Auth::user()->hasPermission('document.edit') || Auth::user()->role === 'Admin') ? route('admin.documents.index') : route('document');
                    @endphp
                    <li class="menu-item {{ request()->routeIs('document') || request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                        <a href="{{ $dokumenRoute }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-file-text"></i>
                            <div data-i18n="Documents">Documents</div>
                        </a>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('training', 'view'))
                    <li class="menu-item {{ (request()->is('training*') || request()->is('my-certificates*') || request()->routeIs('my.certificates*')) ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-award"></i>
                            <div data-i18n="Training">Training</div>
                        </a>
                        <ul class="menu-sub">
                            @if (Auth::user()->canAccess('training', 'create') || Auth::user()->canAccess('training', 'edit'))
                                <li class="menu-item {{ request()->routeIs('admin.training.certificates.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.training.certificates.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-book"></i>
                                        <div data-i18n="Training Management">Training Management</div>
                                    </a>
                                </li>
                            @else
                                <li class="menu-item {{ request()->routeIs('my.certificates') ? 'active' : '' }}">
                                    <a href="{{ route('my.certificates') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-certificate"></i>
                                        <div data-i18n="My Certificates">My Certificates</div>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('leave', 'view') || Auth::user()->canAccess('leave', 'create') || Auth::user()->canAccess('leave', 'approve') || Auth::user()->canAccess('leave', 'export'))
                    <li class="menu-item {{ request()->is('leaves*') ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-logout-2"></i>
                            <div data-i18n="Apply Leave">Apply Leave</div>
                        </a>
                        <ul class="menu-sub">
                            @if (Auth::user()->canAccess('leave', 'view') || Auth::user()->canAccess('leave', 'create'))
                            <li class="menu-item {{ request()->routeIs('leaves.pengajuan') || request()->routeIs('leaves.create') ? 'active' : '' }}">
                                <a href="{{ route('leaves.pengajuan') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-send"></i>
                                    <div data-i18n="Leave Request">Leave Request</div>
                                </a>
                            </li>
                            @endif
                            @if (Auth::user()->canAccess('leave', 'approve'))
                                <li class="menu-item {{ request()->routeIs('leaves.index') ? 'active' : '' }}">
                                    <a href="{{ route('leaves.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-circle-check"></i>
                                        <div data-i18n="Leave Approvals">Leave Approvals</div>
                                    </a>
                                </li>
                            @endif
                            @if (Auth::user()->canAccess('leave', 'export'))
                                <li class="menu-item {{ request()->routeIs('leaves.laporan') ? 'active' : '' }}">
                                    <a href="{{ route('leaves.laporan') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-file-text"></i>
                                        <div data-i18n="Leave Report">Leave Report</div>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('announcement', 'view'))
                    <li class="menu-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                        <a href="{{ route('announcements.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-speakerphone"></i>
                            <div data-i18n="Announcements">Announcements</div>
                            @if(isset($unreadAnnouncementsCount) && $unreadAnnouncementsCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto">{{ $unreadAnnouncementsCount > 99 ? '99+' : $unreadAnnouncementsCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endif

                    <li class="menu-item {{ request()->routeIs('faq') ? 'active' : '' }}">
                        <a href="{{ route('faq') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-help-circle"></i>
                            <div data-i18n="FAQ">FAQ</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('kebijakan') ? 'active' : '' }}">
                        <a href="{{ route('kebijakan') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-shield-check"></i>
                            <div data-i18n="Privacy Policy">Privacy Policy</div>
                        </a>
                    </li>

                    <li class="menu-item mt-3 sidebar-time">
                        <div class="menu-link disabled">
                            <i class="menu-icon tf-icons ti ti-clock"></i>
                            <div id="tanggalSekarang">Loading...</div>
                        </div>
                    </li>
                </ul>
            </aside>
            <div class="layout-page">
                @php
                    $currentUser = Auth::user();
                    $canManageSchedule = in_array(strtolower((string) $currentUser->role), [
                        'admin',
                        'spv bge',
                        'ass leader bge',
                        'leader bge',
                        'spv apron',
                        'ass leader apron',
                        'leader apron',
                    ]);
                    $canViewAdminAttendance = in_array($currentUser->role, ['Admin', 'Head Of Airport Service']);
                    $canApproveOvertime = in_array($currentUser->role, ['Admin', 'LEADER', 'Head Of Airport Service', 'ASS LEADER']);
                    $canApproveAttendanceCorrections = in_array('Admin', array_map('trim', explode(',', (string) $currentUser->role)), true)
                        || \App\Models\User::where('manager', $currentUser->fullname)->exists();
                    $canManageTraining = in_array($currentUser->role, ['Admin', 'HSE', 'Head Of Airport Service']);
                    $canManageLeave = in_array($currentUser->role, ['Admin', 'Head Of Airport Service']);
                    $topbarMenuLinks = [];
                    if ($currentUser->canAccess('dashboard', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Dashboard', 'category' => 'Menu', 'hint' => 'Overview operasional dan statistik', 'icon' => 'ti-layout-dashboard', 'url' => route('home')];
                    }
                    $topbarMenuLinks[] = ['label' => 'Profile', 'category' => 'Menu', 'hint' => 'Data akun dan biodata staff', 'icon' => 'ti-user-circle', 'url' => route('users.profile', $currentUser->id)];

                    if ($currentUser->canAccess('schedule', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Jadwal Hari Ini', 'category' => 'Schedule', 'hint' => 'Lihat jadwal aktif hari ini', 'icon' => 'ti-calendar-check', 'url' => route('schedule.now')];
                        $topbarMenuLinks[] = ['label' => 'Data Schedule', 'category' => 'Schedule', 'hint' => 'Kalender dan data jadwal bulanan', 'icon' => 'ti-calendar', 'url' => route('schedule.index')];
                    }

                    if ($canManageSchedule && ($currentUser->canAccess('schedule', 'create') || $currentUser->canAccess('schedule', 'edit'))) {
                        $topbarMenuLinks[] = ['label' => 'Create / Update Schedule', 'category' => 'Schedule', 'hint' => 'Kelola pembuatan jadwal staff', 'icon' => 'ti-calendar-plus', 'url' => route('schedule.view')];
                    }

                    if ($currentUser->canAccess('shift', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Shift', 'category' => 'Menu', 'hint' => 'Data shift kerja', 'icon' => 'ti-clock', 'url' => route('shift.index')];
                    }

                    if ($currentUser->canAccess('attendance', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Absensi Hari Ini', 'category' => 'Attendance', 'hint' => 'Monitoring absensi staff', 'icon' => 'ti-stopwatch', 'url' => route('attendance.index')];
                    }

                    if ($canViewAdminAttendance || $currentUser->canAccess('attendance', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Laporan Absensi', 'category' => 'Attendance', 'hint' => 'Rekap dan export absensi', 'icon' => 'ti-file-text', 'url' => route('attendance.reports')];
                    }

                    if ($canApproveAttendanceCorrections || $currentUser->canAccess('attendance', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Approval Koreksi Absensi', 'category' => 'Attendance', 'hint' => 'Validasi koreksi waktu absensi', 'icon' => 'ti-user-check', 'url' => route('attendance.corrections.approval')];
                    }

                    if ($currentUser->canAccess('overtime', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Lembur Saya', 'category' => 'Attendance', 'hint' => 'Pengajuan dan status lembur', 'icon' => 'ti-hourglass', 'url' => route('overtime.index')];
                    }

                    if ($canApproveOvertime || $currentUser->canAccess('overtime', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Approval Lembur', 'category' => 'Attendance', 'hint' => 'Validasi pengajuan lembur', 'icon' => 'ti-circle-check', 'url' => route('overtime.approval')];
                    }

                    if ($currentUser->role === 'Admin' || $currentUser->canAccess('overtime', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Laporan Lembur', 'category' => 'Attendance', 'hint' => 'Rekap lembur operasional', 'icon' => 'ti-chart-line', 'url' => route('overtime.report')];
                    }

                    if ($currentUser->canAccess('station', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Manajemen Station', 'category' => 'Administrator', 'hint' => 'Kelola status dan koordinat station', 'icon' => 'ti-building-store', 'url' => route('stations.index')];
                    }
                    if ($currentUser->canAccess('user', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Monitor Station', 'category' => 'Administrator', 'hint' => 'Pantau staff tiap station', 'icon' => 'ti-device-desktop', 'url' => route('staff.index')];
                        $topbarMenuLinks[] = ['label' => 'Kontrak', 'category' => 'Administrator', 'hint' => 'Masa kontrak staff', 'icon' => 'ti-file-text', 'url' => route('users.kontrak')];
                        $topbarMenuLinks[] = ['label' => 'PAS Bandara', 'category' => 'Administrator', 'hint' => 'Masa aktif PAS bandara', 'icon' => 'ti-id', 'url' => route('users.pas')];
                        $topbarMenuLinks[] = ['label' => 'TIM Bandara', 'category' => 'Administrator', 'hint' => 'Data TIM bandara', 'icon' => 'ti-badge', 'url' => route('users.tim')];
                    }
                    if ($currentUser->canAccess('blacklist', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Blacklist', 'category' => 'Administrator', 'hint' => 'Data staff blacklist', 'icon' => 'ti-user-x', 'url' => route('blacklist.index')];
                    }

                    if ($currentUser->canAccess('document', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Cetak Dokumen', 'category' => 'General', 'hint' => 'Dokumen dan surat', 'icon' => 'ti-file-text', 'url' => route('document')];
                    }

                    if ($currentUser->role === 'Admin' || $currentUser->canAccess('document', 'create')) {
                        $topbarMenuLinks[] = ['label' => 'Manajemen Dokumen', 'category' => 'General', 'hint' => 'Kelola file dan akses dokumen', 'icon' => 'ti-folders', 'url' => route('admin.documents.index')];
                    }

                    if ($currentUser->canAccess('training', 'view')) {
                        if ($canManageTraining || $currentUser->canAccess('training', 'create')) {
                            $topbarMenuLinks[] = ['label' => 'Manajemen Training', 'category' => 'Training', 'hint' => 'Kelola data sertifikat', 'icon' => 'ti-book', 'url' => route('admin.training.certificates.index')];
                            $topbarMenuLinks[] = ['label' => 'Tambah Sertifikat', 'category' => 'Training', 'hint' => 'Input sertifikat baru', 'icon' => 'ti-circle-plus', 'url' => route('admin.training.certificates.create')];
                        } else {
                            $topbarMenuLinks[] = ['label' => 'Sertifikat Saya', 'category' => 'Training', 'hint' => 'Lihat sertifikat pribadi', 'icon' => 'ti-certificate', 'url' => route('my.certificates')];
                        }
                    }

                    if ($currentUser->canAccess('leave', 'view') || $currentUser->canAccess('leave', 'create')) {
                        $topbarMenuLinks[] = ['label' => 'Pengajuan Leave', 'category' => 'Apply Leave', 'hint' => 'Ajukan izin atau cuti', 'icon' => 'ti-send', 'url' => route('leaves.pengajuan')];
                    }

                    if ($canManageLeave || $currentUser->canAccess('leave', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Approval Leave', 'category' => 'Apply Leave', 'hint' => 'Review pengajuan leave', 'icon' => 'ti-circle-check', 'url' => route('leaves.index')];
                    }

                    if ($canManageLeave || $currentUser->canAccess('leave', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Laporan Leave', 'category' => 'Apply Leave', 'hint' => 'Rekap leave staff', 'icon' => 'ti-file-text', 'url' => route('leaves.laporan')];
                    }

                    if ($currentUser->canAccess('announcement', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Pengumuman', 'category' => 'General', 'hint' => 'Informasi pengumuman perusahaan', 'icon' => 'ti-speakerphone', 'url' => route('announcements.index')];
                    }

                    $topbarMenuLinks[] = ['label' => 'FAQ', 'category' => 'Support', 'hint' => 'Pertanyaan umum sistem', 'icon' => 'ti-help-circle', 'url' => route('faq')];
                    $topbarMenuLinks[] = ['label' => 'Kebijakan Privasi', 'category' => 'Support', 'hint' => 'Informasi privasi aplikasi', 'icon' => 'ti-shield-check', 'url' => route('kebijakan')];
                    $topbarMenuGroups = collect($topbarMenuLinks)->groupBy('category');
                @endphp
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="aps-topbar">
                        <div class="navbar-nav align-items-xl-center">
                            <button type="button" class="nav-item nav-link px-0 border-0 bg-transparent"
                                id="custom-sidebar-toggle" aria-label="Toggle sidebar" draggable="false">
                                <i class="ti ti-menu-2"></i>
                            </button>
                        </div>

                        <div class="topbar-date">
                            <span>Today</span>
                            <strong id="topbarToday">{{ now()->format('D, M d, Y') }}</strong>
                        </div>

                        <button class="topbar-search-trigger" type="button" id="topbarSearchTrigger"
                            aria-haspopup="dialog" aria-controls="apsMenuSearch">
                            <i class="ti ti-search"></i>
                            <span>Search menu ...</span>
                            <kbd>Ctrl K</kbd>
                        </button>

                        <div class="topbar-right">
	                            <div class="topbar-theme-switch" id="apsThemeSwitch" aria-label="Toggle tema tampilan">
	                                <button class="topbar-theme-option topbar-theme-toggle is-active" type="button"
	                                    data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false"
                                        title="Switch to dark mode">
	                                    <i class="ti ti-moon"></i>
	                                </button>
	                            </div>

	                            @if (Auth::user()->canAccess('announcement', 'view'))
	                            <div class="dropdown topbar-notification-dropdown ms-2">
	                                <div class="topbar-notification-switch">
	                                    <button class="topbar-notification-btn" type="button" id="topbarNotificationBell" data-bs-toggle="dropdown" aria-expanded="false" title="Pengumuman">
	                                        <i class="ti ti-bell"></i>
	                                        @if(isset($unreadAnnouncementsCount) && $unreadAnnouncementsCount > 0)
	                                            <span class="notification-badge-count" id="bellNotificationBadge">{{ $unreadAnnouncementsCount > 99 ? '99+' : $unreadAnnouncementsCount }}</span>
	                                        @endif
	                                    </button>
	                                    <div class="dropdown-menu dropdown-menu-end shadow-lg p-0 notification-dropdown-menu" aria-labelledby="topbarNotificationBell" style="width: 290px; max-width: 86vw; border-radius: 0.75rem; border: 1px solid rgba(226, 232, 240, 0.8); overflow: hidden;">
	                                        <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between">
	                                            <div class="d-flex align-items-center gap-2">
	                                                <i class="ti ti-bell text-primary fs-6"></i>
	                                                <strong class="text-dark" style="font-size: 0.82rem;">Pengumuman</strong>
	                                            </div>
	                                            <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 0.65rem;" id="dropdownUnreadLabel">
	                                                {{ isset($unreadAnnouncementsCount) && $unreadAnnouncementsCount > 0 ? $unreadAnnouncementsCount . ' Belum Dibaca' : 'Semua Dibaca' }}
	                                            </span>
	                                        </div>
	                                        <div class="notification-list-body" style="max-height: 280px; overflow-y: auto;">
	                                            @forelse(collect($topbarAnnouncements ?? [])->take(3) as $announcement)
	                                                @php
	                                                    $isRead = in_array($announcement->id, $readAnnouncementIds ?? []);
	                                                @endphp
	                                                <a href="{{ route('announcements.index', ['select' => $announcement->id]) }}" 
	                                                   class="d-block px-3 py-2 text-decoration-none border-bottom notification-item-link {{ !$isRead ? 'bg-light-subtle-unread' : '' }}"
	                                                   onclick="markSingleRead('{{ route('announcements.read', $announcement->id) }}', event, '{{ route('announcements.index', ['select' => $announcement->id]) }}')">
	                                                    <div class="d-flex justify-content-between align-items-start mb-1">
	                                                        <span class="fw-bold text-dark text-truncate d-inline-block" style="max-width: 165px; font-size: 0.8rem;">{{ $announcement->title }}</span>
	                                                        <small class="text-muted" style="font-size: 0.65rem;">{{ $announcement->created_at->diffForHumans(null, true) }}</small>
	                                                    </div>
	                                                    <p class="mb-1 text-secondary text-truncate-2" style="font-size: 0.73rem; line-height: 1.25;">{{ Str::limit(strip_tags($announcement->content), 60) }}</p>
	                                                    <div class="d-flex align-items-center justify-content-between">
	                                                        <span class="badge bg-label-info text-capitalize py-1 px-2" style="font-size: 0.6rem;">
	                                                            {{ is_array($announcement->target_stations) ? (in_array('ALL', $announcement->target_stations) ? 'Semua Station' : implode(', ', $announcement->target_stations)) : 'Semua Station' }}
	                                                        </span>
	                                                        @if(!$isRead)
	                                                            <span class="badge bg-danger rounded-circle p-0" style="width: 7px; height: 7px; display: inline-block;" title="Belum Dibaca"></span>
	                                                        @endif
	                                                    </div>
	                                                </a>
	                                            @empty
	                                                <div class="p-3 text-center text-muted">
	                                                    <i class="ti ti-bell-off fs-4 d-block mb-1 opacity-50"></i>
	                                                    <span style="font-size: 0.78rem;">Tidak ada pengumuman</span>
	                                                </div>
	                                            @endforelse
	                                        </div>
	                                        <div class="p-2 border-top bg-light text-center">
	                                            <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
	                                                Lihat Semua Pengumuman <i class="ti ti-chevron-right ms-1"></i>
	                                            </a>
	                                        </div>
	                                    </div>
	                                </div>
	                            </div>
	                            @endif
                            <ul class="navbar-nav flex-row align-items-center">
                                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                    <button type="button" class="topbar-user-chip nav-link dropdown-toggle hide-arrow border-0 bg-transparent"
                                        data-bs-toggle="dropdown" draggable="false" aria-label="User menu" aria-expanded="false">
                                        @if (!empty($currentUser->profile_picture))
                                            <img class="topbar-user-mini-avatar"
                                                src="{{ asset('storage/photo/' . $currentUser->profile_picture) }}"
                                                alt="{{ $currentUser->fullname }}"
                                                width="36" height="36"
                                                loading="eager" decoding="async"
                                                onerror="this.onerror=null; this.src='{{ asset('storage/photo/user.jpg') }}';"
                                                draggable="false">
                                        @else
                                            <img class="topbar-user-mini-avatar"
                                                src="{{ asset('storage/photo/user.jpg') }}" alt="Profile"
                                                width="36" height="36"
                                                loading="eager" decoding="async"
                                                draggable="false">
                                        @endif
                                        <div class="topbar-user-text">
                                            <strong>{{ $currentUser->fullname }}</strong>
                                            <span>{{ $currentUser->id }} &mdash; {{ $currentUser->role }}@if (!empty($currentUser->station)) ({{ $currentUser->station }}) @endif</span>
                                        </div>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown-card">
                                    <li>
                                        <div class="profile-dropdown-header">
                                            <div class="profile-dropdown-avatar">
                                                @if (!empty(Auth::user()->profile_picture))
                                                    <img src="{{ asset('storage/photo/' . Auth::user()->profile_picture) }}"
                                                        alt="{{ Auth::user()->fullname }}"
                                                        width="64" height="64"
                                                        loading="lazy" decoding="async"
                                                        onerror="this.onerror=null; this.src='{{ asset('storage/photo/user.jpg') }}';"
                                                        style="width:64px;height:64px;object-fit:cover;border-radius:50%;" />
                                                @else
                                                    <img src="{{ asset('storage/photo/user.jpg') }}" alt="Profile"
                                                        width="64" height="64"
                                                        loading="lazy" decoding="async"
                                                        style="width:64px;height:64px;object-fit:cover;border-radius:50%;" />
                                                @endif
                                                <span class="profile-dropdown-status"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="profile-dropdown-name text-truncate">{{ Auth::user()->fullname }}</div>
                                                <div class="profile-dropdown-meta">
                                                    <span class="profile-role-badge">
                                                        <i class="ti ti-shield-check"></i>
                                                        {{ Auth::user()->role }}
                                                    </span>
                                                    @if (!empty(Auth::user()->station))
                                                        <span>{{ Auth::user()->station }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li class="profile-dropdown-menu-group">
                                        <a class="dropdown-item"
                                            href="{{ route('users.profile', Auth::user()->id) }}">
                                            <i class="ti ti-user-circle"></i>
                                            <span class="align-middle">Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item profile-logout-item" href="{{ route('logout') }}"
                                            id="profile-logout-link">
                                            <i class="ti ti-logout-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            style="display: none;">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
                <div class="aps-menu-search" id="apsMenuSearch" role="dialog" aria-modal="true"
                    aria-labelledby="apsMenuSearchTitle">
                    <div class="aps-menu-search-panel">
                        <h2 class="visually-hidden" id="apsMenuSearchTitle">Pencarian Menu</h2>
                        <div class="aps-menu-search-head">
                            <span class="aps-menu-search-icon"><i class="ti ti-search"></i></span>
                            <input class="aps-menu-search-input" id="apsMenuSearchInput" type="search"
                                placeholder="Cari menu, fitur, atau halaman..." autocomplete="off">
                            <button class="aps-menu-search-close" type="button" id="apsMenuSearchClose"
                                aria-label="Tutup pencarian">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="aps-menu-search-body">
                            <div class="aps-menu-search-empty" id="apsMenuSearchEmpty">
                                Menu tidak ditemukan. Coba kata kunci lain.
                            </div>
                            @foreach ($topbarMenuGroups as $category => $items)
                                <div class="aps-menu-search-group" data-search-group>
                                    <div class="aps-menu-search-group-title">{{ $category }}</div>
                                    @foreach ($items as $item)
                                        <a class="aps-menu-search-item" href="{{ $item['url'] }}"
                                            data-search-item
                                            data-keywords="{{ \Illuminate\Support\Str::lower($item['label'] . ' ' . $item['category'] . ' ' . $item['hint']) }}">
                                            <span class="aps-menu-search-item-icon">
                                                <i class="ti {{ $item['icon'] }}"></i>
                                            </span>
                                            <span class="aps-menu-search-copy">
                                                <strong>{{ $item['label'] }}</strong>
                                                <span>{{ $item['hint'] }}</span>
                                            </span>
                                            <span class="aps-menu-search-arrow" aria-hidden="true">
                                                <i class="ti ti-chevron-right"></i>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="aps-menu-search-foot">
                            <span><kbd>Ctrl</kbd> + <kbd>K</kbd> buka pencarian</span>
                            <span><kbd>Esc</kbd> tutup</span>
                        </div>
                    </div>
                </div>
                <div class="content-wrapper">
                    <div id="pjax-content" class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl d-flex flex-wrap justify-content-center py-3">
                            <div class="text-center">
                                <p class="mb-0" style="font-size: 0.85rem; color: #a1acb8;">
                                    © 2025 <span class="fw-semibold" style="color: #697a8d;">PT. Angkasa Pratama Sejahtera</span>. All Rights Reserved. <span class="ms-1 font-monospace" style="font-size: 0.8rem; color: #a1acb8;">v 3.2</span>
                                </p>
                            </div>
                        </div>
                    </footer>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay" id="custom-layout-overlay"></div>
    </div>
    <script src="{{ asset('template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('template/assets/vendor/libs/popper/popper.js') }}" defer></script>
    <script src="{{ asset('template/assets/vendor/js/bootstrap.js') }}" defer></script>
    <script src="{{ asset('template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}" defer></script>
    <script src="{{ asset('template/assets/vendor/js/menu.js') }}" defer></script>

    <script src="{{ asset('template/assets/js/main.js') }}?v={{ filemtime(public_path('template/assets/js/main.js')) }}" defer></script>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            const el = document.getElementById('tanggalSekarang');
            if (el) el.textContent = formattedDate;

            const topbarDate = document.getElementById('topbarToday');
            if (topbarDate) {
                topbarDate.textContent = now.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
    <style>
        /* View Transitions API Ultra-Smooth Circle Animation */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none;
            mix-blend-mode: normal;
            display: block;
        }

        ::view-transition-old(root) {
            z-index: 1;
        }

        ::view-transition-new(root) {
            z-index: 99999;
        }

        .aps-dark::view-transition-old(root) {
            z-index: 99999;
        }

        .aps-dark::view-transition-new(root) {
            z-index: 1;
        }

        /* Disable individual element transitions while View Transition API is snapshotting to prevent frame stutter */
        html.in-view-transition *,
        html.in-view-transition *::before,
        html.in-view-transition *::after {
            transition: none !important;
            animation: none !important;
        }

        /* Smooth Fallback CSS Transitions for UI Layout & Components (Only active when View Transition API is NOT running) */
        html:not(.in-view-transition):not(.no-transitions) body,
        html:not(.in-view-transition):not(.no-transitions) .layout-wrapper,
        html:not(.in-view-transition):not(.no-transitions) .layout-container,
        html:not(.in-view-transition):not(.no-transitions) .layout-menu,
        html:not(.in-view-transition):not(.no-transitions) .aps-topbar,
        html:not(.in-view-transition):not(.no-transitions) .card,
        html:not(.in-view-transition):not(.no-transitions) .modal-content,
        html:not(.in-view-transition):not(.no-transitions) .dropdown-menu,
        html:not(.in-view-transition):not(.no-transitions) .table,
        html:not(.in-view-transition):not(.no-transitions) .form-control,
        html:not(.in-view-transition):not(.no-transitions) .form-select {
            transition: background-color 0.35s cubic-bezier(0.25, 1, 0.5, 1),
                        border-color 0.35s cubic-bezier(0.25, 1, 0.5, 1),
                        color 0.35s cubic-bezier(0.25, 1, 0.5, 1),
                        box-shadow 0.35s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        html.no-transitions *,
        html.no-transitions *::before,
        html.no-transitions *::after {
            transition: none !important;
            animation: none !important;
        }
    </style>

    <script>
        (function() {
            function applyTheme(theme) {
                const nextTheme = theme === 'dark' ? 'dark' : 'light';
                document.documentElement.classList.toggle('aps-dark', nextTheme === 'dark');
                document.documentElement.setAttribute('data-aps-theme', nextTheme);
                localStorage.setItem('apsTheme', nextTheme);

                document.querySelectorAll('[data-theme-option]').forEach(function(button) {
                    const isActive = button.getAttribute('data-theme-option') === nextTheme;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                document.querySelectorAll('[data-theme-toggle]').forEach(function(button) {
                    const icon = button.querySelector('i');
                    const isDark = nextTheme === 'dark';
                    const targetTheme = isDark ? 'light' : 'dark';

                    button.dataset.themeTarget = targetTheme;
                    button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                    button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
                    button.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');

                    if (icon) {
                        icon.className = isDark ? 'ti ti-sun' : 'ti ti-moon';
                    }
                });

                window.dispatchEvent(new CustomEvent('aps:theme-changed', {
                    detail: {
                        theme: nextTheme
                    }
                }));
            }

            window.apsApplyTheme = applyTheme;

            function applyThemeWithTransition(nextTheme, event) {
                const currentTheme = document.documentElement.classList.contains('aps-dark') ? 'dark' : 'light';
                if (currentTheme === nextTheme) return;

                if (!document.startViewTransition || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    applyTheme(nextTheme);
                    return;
                }

                let x = window.innerWidth / 2;
                let y = window.innerHeight / 2;

                if (event && (event.clientX || event.clientY)) {
                    x = event.clientX;
                    y = event.clientY;
                } else {
                    const toggleBtn = document.querySelector('[data-theme-toggle]');
                    if (toggleBtn) {
                        const rect = toggleBtn.getBoundingClientRect();
                        x = rect.left + rect.width / 2;
                        y = rect.top + rect.height / 2;
                    }
                }

                const endRadius = Math.hypot(
                    Math.max(x, window.innerWidth - x),
                    Math.max(y, window.innerHeight - y)
                );

                const isDark = nextTheme === 'dark';
                document.documentElement.classList.add('in-view-transition');

                const transition = document.startViewTransition(function() {
                    applyTheme(nextTheme);
                });

                transition.ready.then(function() {
                    const clipPath = [
                        `circle(0px at ${x}px ${y}px)`,
                        `circle(${endRadius}px at ${x}px ${y}px)`
                    ];

                    const anim = document.documentElement.animate(
                        {
                            clipPath: isDark ? clipPath : [...clipPath].reverse()
                        },
                        {
                            duration: 520,
                            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                            pseudoElement: isDark ? '::view-transition-new(root)' : '::view-transition-old(root)'
                        }
                    );

                    anim.onfinish = function() {
                        document.documentElement.classList.remove('in-view-transition');
                    };
                }).catch(function() {
                    document.documentElement.classList.remove('in-view-transition');
                });

                transition.finished.then(function() {
                    document.documentElement.classList.remove('in-view-transition');
                });
            }

            document.addEventListener('click', function(event) {
                const toggleBtn = event.target.closest('[data-theme-toggle]');
                if (toggleBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const currentTheme = document.documentElement.classList.contains('aps-dark') ? 'dark' : 'light';
                    const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    applyThemeWithTransition(nextTheme, event);
                    return;
                }

                const optionBtn = event.target.closest('[data-theme-option]');
                if (optionBtn && !optionBtn.hasAttribute('data-theme-toggle')) {
                    event.preventDefault();
                    event.stopPropagation();
                    const targetTheme = optionBtn.getAttribute('data-theme-option');
                    if (targetTheme) applyThemeWithTransition(targetTheme, event);
                    return;
                }
            });

            function syncCurrentTheme() {
                const storedTheme = localStorage.getItem('apsTheme') || 'light';
                applyTheme(storedTheme);
            }

            document.addEventListener('DOMContentLoaded', syncCurrentTheme);
            window.addEventListener('aps:content-loaded', syncCurrentTheme);
        })();
    </script>

	    <div id="pjax-page-scripts" hidden>
	        @yield('scripts')
	    </div>
	    <script>
	        (function() {
	            const enhancedSelects = 'select:not([multiple]):not([size]):not([data-aps-native]):not(.select2):not(.swal2-select)';
	            const temporalInputs = 'input[type="date"]:not([data-aps-native]), input[type="time"]:not([data-aps-native]), input[type="datetime-local"]:not([data-aps-native]), input[type="month"]:not([data-aps-native])';
	            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
	            const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

	            function pad(value) {
	                return String(value).padStart(2, '0');
	            }

	            function escapeKey(value) {
	                return String(value || '').replace(/"/g, '&quot;');
	            }

	            function closeAllComboboxes(except) {
	                document.querySelectorAll('.aps-combobox.is-open').forEach(function(combo) {
	                    if (combo !== except) {
	                        combo.classList.remove('is-open');
	                        combo.querySelector('.aps-combobox-trigger')?.setAttribute('aria-expanded', 'false');
	                        setControlHost(combo, false);
	                    }
	                });
	            }

	            function closeAllPickers(except) {
	                document.querySelectorAll('.aps-picker.is-open').forEach(function(picker) {
	                    if (picker !== except) {
	                        picker.classList.remove('is-open');
	                        picker.querySelector('.aps-picker-trigger')?.setAttribute('aria-expanded', 'false');
	                        setControlHost(picker, false);
	                    }
	                });
	            }

	            function setControlHost(wrapper, isOpen) {
	                wrapper.closest('.card, .form-card, .modal-content, .offcanvas, .dropdown-menu')?.classList.toggle('aps-control-host-open', isOpen);
	            }

	            function placePanel(wrapper, panel, estimatedHeight, options = {}) {
	                wrapper.classList.remove('is-dropup');
	                if (!panel) return;
	                panel.style.removeProperty('--aps-panel-max-height');

	                const rect = wrapper.getBoundingClientRect();
	                const below = window.innerHeight - rect.bottom;
	                const above = rect.top;
	                const needed = estimatedHeight || 320;
	                const allowDropup = !(options.preferBelow && window.innerWidth <= 767);
	                if (allowDropup && below < needed && above > below) {
	                    wrapper.classList.add('is-dropup');
	                }
	                const available = wrapper.classList.contains('is-dropup') ? above : below;
	                const minHeight = options.minHeight || 160;
	                const maxHeight = Math.max(minHeight, Math.min(needed, available - 12));
	                panel.style.setProperty('--aps-panel-max-height', maxHeight + 'px');
	            }

	            function getSelectedText(select) {
	                const selected = select.options[select.selectedIndex];
	                return selected ? selected.text.trim() : '';
	            }

	            function syncCombobox(select) {
	                const data = select._apsCombobox;
	                if (!data) return;

	                const text = getSelectedText(select);
	                const hasValue = select.value !== '';
	                data.value.textContent = text || data.placeholder;
	                data.value.classList.toggle('aps-combobox-placeholder', !hasValue);
	                data.trigger.disabled = select.disabled;
	                data.combo.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
	            }

	            function shouldEnhanceSelect(select) {
	                return !select.closest('.swal2-container, .swal2-popup');
	            }

	            function renderComboboxOptions(select, query) {
	                const data = select._apsCombobox;
	                if (!data) return;

	                const normalized = (query || '').trim().toLowerCase();
	                data.options.innerHTML = '';
	                let visible = 0;

	                Array.from(select.options).forEach(function(option) {
	                    const text = option.text.trim();
	                    const matches = !normalized || text.toLowerCase().includes(normalized) || String(option.value).toLowerCase().includes(normalized);
	                    if (!matches) return;

	                    visible += 1;
	                    const item = document.createElement('button');
	                    item.type = 'button';
	                    item.className = 'aps-combobox-option';
	                    item.dataset.value = option.value;
	                    item.disabled = option.disabled;
	                    item.classList.toggle('is-selected', option.selected);
	                    item.classList.toggle('is-active', option.selected);
	                    item.innerHTML = '<span class="aps-combobox-check"><i class="ti ti-check"></i></span><span class="text-truncate"></span>';
	                    item.querySelector('.text-truncate').textContent = text;
	                    data.options.appendChild(item);
	                });

	                data.empty.classList.toggle('is-visible', visible === 0);
	            }

	            function openCombobox(select) {
	                const data = select._apsCombobox;
	                if (!data || select.disabled) return;

	                closeAllComboboxes(data.combo);
	                closeAllPickers();
	                renderComboboxOptions(select, '');
	                placePanel(data.combo, data.combo.querySelector('.aps-combobox-panel'), 240, {
	                    preferBelow: true,
	                    minHeight: 140
	                });
	                data.search.value = '';
	                data.combo.classList.add('is-open');
	                setControlHost(data.combo, true);
	                data.trigger.setAttribute('aria-expanded', 'true');
	                window.setTimeout(function() {
	                    data.search.focus();
	                }, 20);
	            }

	            function initComboboxes(root) {
	                root.querySelectorAll(enhancedSelects).forEach(function(select) {
	                    if (!shouldEnhanceSelect(select)) return;
	                    if (select.dataset.apsEnhanced === 'combobox') return;
	                    if (select.closest('.aps-combobox')) return;

	                    select.dataset.apsEnhanced = 'combobox';
	                    select.classList.add('aps-control-hidden');

	                    const combo = document.createElement('div');
	                    combo.className = 'aps-combobox';
	                    combo.innerHTML = [
	                        '<button type="button" class="aps-combobox-trigger" aria-haspopup="listbox" aria-expanded="false">',
	                        '<span class="aps-combobox-value"></span>',
	                        '<span class="aps-combobox-icon"><i class="ti ti-chevron-down"></i></span>',
	                        '</button>',
	                        '<div class="aps-combobox-panel">',
	                        '<div class="aps-combobox-search-wrap">',
	                        '<i class="ti ti-search"></i>',
	                        '<input type="search" class="aps-combobox-search" placeholder="Cari pilihan..." autocomplete="off">',
	                        '</div>',
	                        '<div class="aps-combobox-options" role="listbox"></div>',
	                        '<div class="aps-combobox-empty">Pilihan tidak ditemukan.</div>',
	                        '</div>'
	                    ].join('');

	                    select.insertAdjacentElement('afterend', combo);

	                    const data = {
	                        combo: combo,
	                        trigger: combo.querySelector('.aps-combobox-trigger'),
	                        value: combo.querySelector('.aps-combobox-value'),
	                        search: combo.querySelector('.aps-combobox-search'),
	                        options: combo.querySelector('.aps-combobox-options'),
	                        empty: combo.querySelector('.aps-combobox-empty'),
	                        placeholder: getSelectedText(select) || 'Pilih data'
	                    };

	                    select._apsCombobox = data;
	                    syncCombobox(select);

	                    data.trigger.addEventListener('click', function() {
	                        if (data.combo.classList.contains('is-open')) {
	                            data.combo.classList.remove('is-open');
	                            setControlHost(data.combo, false);
	                            data.trigger.setAttribute('aria-expanded', 'false');
	                        } else {
	                            openCombobox(select);
	                        }
	                    });

	                    data.search.addEventListener('input', function() {
	                        renderComboboxOptions(select, data.search.value);
	                    });

	                    data.search.addEventListener('keydown', function(event) {
	                        if (event.key === 'Escape') {
	                            event.preventDefault();
	                            data.combo.classList.remove('is-open');
	                            setControlHost(data.combo, false);
	                            data.trigger.focus();
	                        }
	                    });

	                    data.options.addEventListener('click', function(event) {
	                        const item = event.target.closest('.aps-combobox-option');
	                        if (!item || item.disabled) return;

	                        select.value = item.dataset.value;
	                        data.combo.classList.remove('is-invalid', 'is-open');
	                        setControlHost(data.combo, false);
	                        data.trigger.setAttribute('aria-expanded', 'false');
	                        select.dispatchEvent(new Event('input', { bubbles: true }));
	                        select.dispatchEvent(new Event('change', { bubbles: true }));
	                        syncCombobox(select);
	                        data.trigger.focus();
	                    });

	                    select.addEventListener('change', function() {
	                        syncCombobox(select);
	                    });

	                    select.addEventListener('invalid', function() {
	                        data.combo.classList.add('is-invalid');
	                        data.trigger.focus();
	                    });
	                });
	            }

	            function parseDate(value) {
	                if (!value) return null;
	                const datePart = value.split('T')[0];
	                const parts = datePart.split('-').map(Number);
	                if (parts.length < 3 || parts.some(Number.isNaN)) return null;
	                return new Date(parts[0], parts[1] - 1, parts[2]);
	            }

	            function parseMonth(value) {
	                if (!value) return null;
	                const parts = value.split('-').map(Number);
	                if (parts.length < 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) return null;
	                return {
	                    year: parts[0],
	                    month: parts[1] - 1
	                };
	            }

	            function parseTime(value) {
	                if (!value) return null;
	                const timePart = value.includes('T') ? value.split('T')[1] : value;
	                const parts = timePart.split(':').map(Number);
	                if (parts.length < 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) return null;
	                return {
	                    hour: Math.min(Math.max(parts[0], 0), 23),
	                    minute: Math.min(Math.max(parts[1], 0), 59)
	                };
	            }

	            function dateKey(date) {
	                return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
	            }

	            function formatDateDisplay(value) {
	                const parsed = parseDate(value);
	                if (!parsed) return '';
	                return pad(parsed.getDate()) + '/' + pad(parsed.getMonth() + 1) + '/' + parsed.getFullYear();
	            }

	            function formatMonthDisplay(value) {
	                const parsed = parseMonth(value);
	                if (!parsed) return '';
	                return monthNames[parsed.month] + ' ' + parsed.year;
	            }

	            function formatTimeDisplay(value) {
	                const parsed = parseTime(value);
	                if (!parsed) return '';
	                return pad(parsed.hour) + ':' + pad(parsed.minute);
	            }

	            function pickerPlaceholder(type) {
	                if (type === 'month') return 'Pilih bulan';
	                if (type === 'time') return 'Pilih jam';
	                if (type === 'datetime-local') return 'Pilih tanggal & jam';
	                return 'Pilih tanggal';
	            }

	            function pickerIcon(type) {
	                if (type === 'month') return 'ti-calendar';
	                if (type === 'time') return 'ti-clock-hour-4';
	                if (type === 'datetime-local') return 'ti-calendar-time';
	                return 'ti-calendar';
	            }

	            function formatPickerDisplay(input) {
	                const type = input.dataset.apsPickerType;
	                if (!input.value) return '';
	                if (type === 'month') return formatMonthDisplay(input.value);
	                if (type === 'time') return formatTimeDisplay(input.value);
	                if (type === 'datetime-local') {
	                    const date = formatDateDisplay(input.value);
	                    const time = formatTimeDisplay(input.value);
	                    return [date, time].filter(Boolean).join(' ');
	                }
	                return formatDateDisplay(input.value);
	            }

	            function syncPicker(input) {
	                const data = input._apsPicker;
	                if (!data) return;

	                const display = formatPickerDisplay(input);
	                data.value.textContent = display || pickerPlaceholder(data.type);
	                data.value.classList.toggle('aps-picker-placeholder', !display);
	                data.picker.classList.toggle('is-invalid', input.classList.contains('is-invalid') || input.dataset.apsInvalid === 'true');
	            }

	            function readPickerState(input) {
	                const data = input._apsPicker;
	                const now = new Date();
	                if (data.type === 'month') {
	                    const parsed = parseMonth(input.value) || {
	                        year: now.getFullYear(),
	                        month: now.getMonth()
	                    };
	                    data.viewYear = parsed.year;
	                    data.month = parsed.month;
	                    return;
	                }
	                const parsedDate = parseDate(input.value) || now;
	                const parsedTime = parseTime(input.value) || {
	                    hour: now.getHours(),
	                    minute: now.getMinutes()
	                };

	                data.date = new Date(parsedDate.getFullYear(), parsedDate.getMonth(), parsedDate.getDate());
	                data.viewYear = data.date.getFullYear();
	                data.viewMonth = data.date.getMonth();
	                data.hour = parsedTime.hour;
	                data.minute = parsedTime.minute;
	            }

	            function getPickerValue(input) {
	                const data = input._apsPicker;
	                if (data.type === 'month') {
	                    return data.viewYear + '-' + pad(data.month + 1);
	                }
	                const includeSeconds = data.step === '1' || data.step === 'any';
	                const time = pad(data.hour) + ':' + pad(data.minute) + (includeSeconds ? ':00' : '');
	                const date = dateKey(data.date);

	                if (data.type === 'time') return time;
	                if (data.type === 'datetime-local') return date + 'T' + time;
	                return date;
	            }

	            function setPickerValue(input, value) {
	                input.value = value;
	                input.dataset.apsInvalid = 'false';
	                input.dispatchEvent(new Event('input', { bubbles: true }));
	                input.dispatchEvent(new Event('change', { bubbles: true }));
	                syncPicker(input);
	            }

	            function clampTimePart(field, value, wrap = false) {
	                const max = field === 'hour' ? 23 : 59;
	                let next = Number(value);
	                if (Number.isNaN(next)) next = 0;

	                if (wrap) {
	                    const range = max + 1;
	                    return ((next % range) + range) % range;
	                }

	                return Math.min(Math.max(next, 0), max);
	            }

	            function setTimePart(input, field, value, wrap = false) {
	                const data = input._apsPicker;
	                if (!data) return;
	                data[field] = clampTimePart(field, value, wrap);
	            }

	            function readTimeInputs(input) {
	                const data = input._apsPicker;
	                if (!data) return;
	                const hourInput = data.panel.querySelector('[data-time-input="hour"]');
	                const minuteInput = data.panel.querySelector('[data-time-input="minute"]');
	                if (hourInput) setTimePart(input, 'hour', hourInput.value);
	                if (minuteInput) setTimePart(input, 'minute', minuteInput.value);
	            }

	            function pickerFooterText(input, mode) {
	                const data = input._apsPicker;
	                if (mode === 'month') {
	                    return monthNames[data.month] + ' ' + data.viewYear;
	                }
	                const current = data.date ? formatDateDisplay(dateKey(data.date)) : '';
	                const time = pad(data.hour) + ':' + pad(data.minute);
	                if (mode === 'date') return current;
	                if (mode === 'time') return time;
	                return current + ' ' + time;
	            }

	            function updatePickerCurrent(input, mode) {
	                const current = input._apsPicker?.panel.querySelector('[data-picker-current]');
	                if (!current) return;
	                current.innerHTML = '<i class="ti ti-point-filled"></i>' + pickerFooterText(input, mode);
	            }

	            function renderCalendar(input, includeTime) {
	                const data = input._apsPicker;
	                const first = new Date(data.viewYear, data.viewMonth, 1);
	                const daysInMonth = new Date(data.viewYear, data.viewMonth + 1, 0).getDate();
	                const selected = data.date ? dateKey(data.date) : '';
	                const today = dateKey(new Date());
	                let daysHtml = '';

	                for (let i = 0; i < first.getDay(); i += 1) {
	                    daysHtml += '<span class="aps-picker-day is-empty"></span>';
	                }

	                for (let day = 1; day <= daysInMonth; day += 1) {
	                    const itemDate = new Date(data.viewYear, data.viewMonth, day);
	                    const key = dateKey(itemDate);
	                    const classes = [
	                        'aps-picker-day',
	                        key === selected ? 'is-selected' : '',
	                        key === today ? 'is-today' : ''
	                    ].filter(Boolean).join(' ');
	                    daysHtml += '<button type="button" class="' + classes + '" data-picker-date="' + key + '">' + day + '</button>';
	                }

	                return [
	                    '<div class="aps-picker-head">',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="-12" aria-label="Tahun sebelumnya"><i class="ti ti-chevrons-left"></i></button>',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="-1" aria-label="Bulan sebelumnya"><i class="ti ti-chevron-left"></i></button>',
	                    '</div>',
	                    '<div class="aps-picker-title">' + monthNames[data.viewMonth] + ' ' + data.viewYear + '</div>',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="1" aria-label="Bulan berikutnya"><i class="ti ti-chevron-right"></i></button>',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="12" aria-label="Tahun berikutnya"><i class="ti ti-chevrons-right"></i></button>',
	                    '</div>',
	                    '</div>',
	                    '<div class="aps-picker-body">',
	                    '<div class="aps-picker-weekdays">' + dayNames.map(function(day) {
	                        return '<span class="aps-picker-weekday">' + day + '</span>';
	                    }).join('') + '</div>',
	                    '<div class="aps-picker-days mt-2">' + daysHtml + '</div>',
	                    includeTime ? '<div class="aps-datetime-time">' + renderTimeBoard(input) + '</div>' : '',
	                    '</div>',
	                    renderPickerFooter(input, includeTime ? 'datetime' : 'date')
	                ].join('');
	            }

	            function renderTimeBoard(input) {
	                const data = input._apsPicker;
	                const quickMinutes = [0, 15, 30, 45].map(function(minute) {
	                    const selected = minute === data.minute ? ' is-selected' : '';
	                    return '<button type="button" class="aps-time-cell' + selected + '" data-picker-minute="' + minute + '">' + pad(minute) + '</button>';
	                }).join('');

	                function unit(label, field, value) {
	                    return [
	                        '<div class="aps-time-unit">',
	                        '<div class="aps-time-column-title">' + label + '</div>',
	                        '<div class="aps-time-control">',
	                        '<button type="button" class="aps-time-step" data-time-field="' + field + '" data-time-delta="-1" aria-label="Kurangi ' + label.toLowerCase() + '"><i class="ti ti-minus"></i></button>',
	                        '<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="aps-time-input" data-time-input="' + field + '" value="' + pad(value) + '" aria-label="' + label + '">',
	                        '<button type="button" class="aps-time-step" data-time-field="' + field + '" data-time-delta="1" aria-label="Tambah ' + label.toLowerCase() + '"><i class="ti ti-plus"></i></button>',
	                        '</div>',
	                        '</div>'
	                    ].join('');
	                }

	                return [
	                    '<div class="aps-time-board">',
	                    unit('Jam', 'hour', data.hour),
	                    unit('Menit', 'minute', data.minute),
	                    '</div>',
	                    '<div class="aps-time-quick">' + quickMinutes + '</div>'
	                ].join('');
	            }

	            function renderTimePicker(input) {
	                return [
	                    '<div class="aps-picker-head">',
	                    '<span class="aps-picker-title">Pilih Jam</span>',
	                    '<button type="button" class="aps-picker-chip" data-picker-now><i class="ti ti-clock"></i>&nbsp;Sekarang</button>',
	                    '</div>',
	                    '<div class="aps-picker-body">',
	                    renderTimeBoard(input),
	                    '</div>',
	                    renderPickerFooter(input, 'time')
	                ].join('');
	            }

	            function renderPickerFooter(input, mode) {
	                const data = input._apsPicker;
	                const text = pickerFooterText(input, mode);
	                const clear = data.required ? '' : '<button type="button" class="aps-picker-action" data-picker-clear>Bersihkan</button>';

	                return [
	                    '<div class="aps-picker-foot">',
	                    '<span class="aps-picker-current" data-picker-current><i class="ti ti-point-filled"></i>' + text + '</span>',
	                    '<span class="d-inline-flex gap-2">',
	                    clear,
	                    mode === 'date' ? '<button type="button" class="aps-picker-action" data-picker-now>Hari ini</button>' : '',
	                    mode === 'month' ? '<button type="button" class="aps-picker-action" data-picker-now>Bulan ini</button>' : '',
	                    (mode === 'date' || mode === 'month') ? '' : '<button type="button" class="aps-picker-action btn-primary text-white border-0" data-picker-apply>Terapkan</button>',
	                    '</span>',
	                    '</div>'
	                ].join('');
	            }

	            function renderMonthCalendar(input) {
	                const data = input._apsPicker;
	                const isSelectedVal = input.value ? parseMonth(input.value) : null;
	                const today = new Date();
	                const todayYear = today.getFullYear();
	                const todayMonth = today.getMonth();

	                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

	                let monthsHtml = '';
	                for (let m = 0; m < 12; m++) {
	                    const isSel = isSelectedVal && isSelectedVal.year === data.viewYear && isSelectedVal.month === m;
	                    const isTod = todayYear === data.viewYear && todayMonth === m;
	                    const classes = [
	                        'aps-picker-month-btn',
	                        isSel ? 'is-selected' : '',
	                        isTod ? 'is-today' : ''
	                    ].filter(Boolean).join(' ');
	                    monthsHtml += '<button type="button" class="' + classes + '" data-picker-month-val="' + m + '">' + shortMonths[m] + '</button>';
	                }

	                return [
	                    '<div class="aps-picker-head">',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-year-nav="-1" aria-label="Tahun sebelumnya"><i class="ti ti-chevron-left"></i></button>',
	                    '</div>',
	                    '<div class="aps-picker-title">' + data.viewYear + '</div>',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-year-nav="1" aria-label="Tahun berikutnya"><i class="ti ti-chevron-right"></i></button>',
	                    '</div>',
	                    '</div>',
	                    '<div class="aps-picker-body">',
	                    '<div class="aps-picker-months-grid">' + monthsHtml + '</div>',
	                    '</div>',
	                    renderPickerFooter(input, 'month')
	                ].join('');
	            }

	            function renderPicker(input) {
	                const data = input._apsPicker;
	                data.picker.classList.toggle('is-time-picker', data.type === 'time');
	                data.picker.classList.toggle('is-datetime-picker', data.type === 'datetime-local');
	                data.picker.classList.toggle('is-month-picker', data.type === 'month');
	                if (data.type === 'time') {
	                    data.panel.innerHTML = renderTimePicker(input);
	                } else if (data.type === 'month') {
	                    data.panel.innerHTML = renderMonthCalendar(input);
	                } else {
	                    data.panel.innerHTML = renderCalendar(input, data.type === 'datetime-local');
	                }
	            }

	            function pickerPanelHeight(type) {
	                if (type === 'time') return 320;
	                if (type === 'datetime-local') return 420;
	                if (type === 'month') return 250;
	                return 350;
	            }

	            function openPicker(input) {
	                const data = input._apsPicker;
	                if (!data || input.disabled) return;
	                closeAllPickers(data.picker);
	                closeAllComboboxes();
	                readPickerState(input);
	                renderPicker(input);
	                placePanel(data.picker, data.panel, pickerPanelHeight(data.type));
	                data.picker.classList.add('is-open');
	                setControlHost(data.picker, true);
	                data.trigger.setAttribute('aria-expanded', 'true');
	            }

	            function initTemporalControls(root) {
	                root.querySelectorAll(temporalInputs).forEach(function(input) {
	                    if (input.dataset.apsEnhanced === 'picker') return;

	                    const type = input.type;
	                    input.dataset.apsEnhanced = 'picker';
	                    input.dataset.apsPickerType = type;
	                    input.dataset.apsRequired = input.required ? 'true' : 'false';
	                    input.dataset.apsStep = input.getAttribute('step') || '';
	                    input.required = false;
	                    input.type = 'hidden';
	                    input.classList.add('aps-control-hidden');

	                    const picker = document.createElement('div');
	                    picker.className = 'aps-picker';
	                    picker.innerHTML = [
	                        '<button type="button" class="aps-picker-trigger" aria-haspopup="dialog" aria-expanded="false">',
	                        '<span class="aps-picker-value"></span>',
	                        '<span class="aps-picker-icon"><i class="ti ' + pickerIcon(type) + '"></i></span>',
	                        '</button>',
	                        '<div class="aps-picker-panel"></div>'
	                    ].join('');
	                    input.insertAdjacentElement('afterend', picker);

	                    input._apsPicker = {
	                        picker: picker,
	                        trigger: picker.querySelector('.aps-picker-trigger'),
	                        value: picker.querySelector('.aps-picker-value'),
	                        panel: picker.querySelector('.aps-picker-panel'),
	                        type: type,
	                        required: input.dataset.apsRequired === 'true',
	                        step: input.dataset.apsStep
	                    };

	                    syncPicker(input);

	                    input._apsPicker.trigger.addEventListener('click', function() {
	                        if (picker.classList.contains('is-open')) {
	                            picker.classList.remove('is-open');
	                            setControlHost(picker, false);
	                            input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                        } else {
	                            openPicker(input);
	                        }
	                    });

	                    input.addEventListener('change', function() {
	                        input.dataset.apsInvalid = 'false';
	                        syncPicker(input);
	                    });
 	                    input._apsPicker.panel.addEventListener('click', function(event) {
	                        event.stopPropagation();

	                        const nav = event.target.closest('[data-picker-nav]');
	                        const yearNav = event.target.closest('[data-picker-year-nav]');
	                        const day = event.target.closest('[data-picker-date]');
	                        const monthVal = event.target.closest('[data-picker-month-val]');
	                        const step = event.target.closest('[data-time-delta]');
	                        const hour = event.target.closest('[data-picker-hour]');
	                        const minute = event.target.closest('[data-picker-minute]');
	                        const apply = event.target.closest('[data-picker-apply]');
	                        const clear = event.target.closest('[data-picker-clear]');
	                        const now = event.target.closest('[data-picker-now]');

	                        if (nav) {
	                            const direction = Number(nav.dataset.pickerNav);
	                            const nextView = new Date(input._apsPicker.viewYear, input._apsPicker.viewMonth + direction, 1);
	                            input._apsPicker.viewYear = nextView.getFullYear();
	                            input._apsPicker.viewMonth = nextView.getMonth();
	                            renderPicker(input);
	                            return;
	                        }

	                        if (yearNav) {
	                            const direction = Number(yearNav.dataset.pickerYearNav);
	                            input._apsPicker.viewYear = input._apsPicker.viewYear + direction;
	                            renderPicker(input);
	                            return;
	                        }

	                        if (day) {
	                            readTimeInputs(input);
	                            input._apsPicker.date = parseDate(day.dataset.pickerDate);
	                            if (input._apsPicker.type === 'date') {
	                                setPickerValue(input, getPickerValue(input));
	                                picker.classList.remove('is-open');
	                                setControlHost(picker, false);
	                                input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                            } else {
	                                renderPicker(input);
	                            }
	                            return;
	                        }

	                        if (monthVal) {
	                            const m = Number(monthVal.dataset.pickerMonthVal);
	                            input._apsPicker.month = m;
	                            setPickerValue(input, getPickerValue(input));
	                            picker.classList.remove('is-open');
	                            setControlHost(picker, false);
	                            input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                            return;
	                        }

	                        if (step) {
	                            const field = step.dataset.timeField;
	                            const delta = Number(step.dataset.timeDelta || 0);
	                            if (field === 'hour' || field === 'minute') {
	                                setTimePart(input, field, input._apsPicker[field] + delta, true);
	                                renderPicker(input);
	                            }
	                            return;
	                        }

	                        if (hour) {
	                            input._apsPicker.hour = Number(hour.dataset.pickerHour);
	                            renderPicker(input);
	                            return;
	                        }

	                        if (minute) {
	                            input._apsPicker.minute = Number(minute.dataset.pickerMinute);
	                            renderPicker(input);
	                            return;
	                        }

	                        if (now) {
	                            const current = new Date();
	                            if (input._apsPicker.type === 'month') {
	                                input._apsPicker.viewYear = current.getFullYear();
	                                input._apsPicker.month = current.getMonth();
	                            } else {
	                                input._apsPicker.date = new Date(current.getFullYear(), current.getMonth(), current.getDate());
	                                input._apsPicker.viewYear = current.getFullYear();
	                                input._apsPicker.viewMonth = current.getMonth();
	                                input._apsPicker.hour = current.getHours();
	                                input._apsPicker.minute = current.getMinutes();
	                            }
	                            setPickerValue(input, getPickerValue(input));
	                            picker.classList.remove('is-open');
	                            setControlHost(picker, false);
	                            input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                            return;
	                        }

	                        if (clear) {
	                            setPickerValue(input, '');
	                            picker.classList.remove('is-open');
	                            setControlHost(picker, false);
	                            input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                            return;
	                        }

	                        if (apply) {
	                            readTimeInputs(input);
	                            setPickerValue(input, getPickerValue(input));
	                            picker.classList.remove('is-open');
	                            setControlHost(picker, false);
	                            input._apsPicker.trigger.setAttribute('aria-expanded', 'false');
	                        }
	                    });

	                    input._apsPicker.panel.addEventListener('input', function(event) {
	                        const timeInput = event.target.closest('[data-time-input]');
	                        if (!timeInput) return;

	                        const field = timeInput.dataset.timeInput;
	                        timeInput.value = timeInput.value.replace(/\D/g, '').slice(0, 2);
	                        setTimePart(input, field, timeInput.value);
	                        updatePickerCurrent(input, input._apsPicker.type === 'time' ? 'time' : 'datetime');
	                    });

	                    input._apsPicker.panel.addEventListener('blur', function(event) {
	                        const timeInput = event.target.closest('[data-time-input]');
	                        if (!timeInput) return;

	                        const field = timeInput.dataset.timeInput;
	                        setTimePart(input, field, timeInput.value);
	                        timeInput.value = pad(input._apsPicker[field]);
	                        updatePickerCurrent(input, input._apsPicker.type === 'time' ? 'time' : 'datetime');
	                    }, true);
	                });
	            }

	            function initTemporalValidation(root) {
	                root.querySelectorAll('form:not([data-aps-picker-validation])').forEach(function(form) {
	                    form.dataset.apsPickerValidation = 'true';
	                    form.addEventListener('submit', function(event) {
	                        const invalid = Array.from(form.querySelectorAll('input[data-aps-picker-type]')).find(function(input) {
	                            const required = input.dataset.apsRequired === 'true';
	                            const missing = required && !input.value;
	                            const customError = input.validationMessage && input.validity && input.validity.customError;
	                            input.dataset.apsInvalid = missing || customError ? 'true' : 'false';
	                            syncPicker(input);
	                            return missing || customError;
	                        });

	                        if (!invalid) return;

	                        event.preventDefault();
	                        event.stopImmediatePropagation();

	                        const data = invalid._apsPicker;
	                        if (data) {
	                            data.trigger.focus();
	                            openPicker(invalid);
	                        }

	                        const message = invalid.validationMessage || 'Lengkapi tanggal atau jam terlebih dahulu.';
	                        if (typeof Swal !== 'undefined') {
	                            Swal.fire({
	                                title: 'Data belum lengkap',
	                                text: message,
	                                icon: 'warning',
	                                timer: 2800,
	                                showConfirmButton: false
	                            });
	                        }
	                    }, true);
	                });
	            }

	            function initApsCustomControls(root) {
	                const scope = root || document;
	                initComboboxes(scope);
	                initTemporalControls(scope);
	                initTemporalValidation(scope);
	            }

	            document.addEventListener('click', function(event) {
	                if (!event.target.closest('.aps-combobox')) closeAllComboboxes();
	                if (!event.target.closest('.aps-picker')) closeAllPickers();
	            });

	            document.addEventListener('keydown', function(event) {
	                if (event.key === 'Escape') {
	                    closeAllComboboxes();
	                    closeAllPickers();
	                }
	            });

	            document.addEventListener('DOMContentLoaded', function() {
	                initApsCustomControls(document);
	            });

	            window.addEventListener('aps:content-loaded', function(event) {
	                const content = document.getElementById('pjax-content') || document;
	                initApsCustomControls(content);
	            });

	            window.apsInitCustomControls = initApsCustomControls;
	        })();
	    </script>
	    <script>
	        document.addEventListener('DOMContentLoaded', function() {
	            const toggleBtn = document.getElementById('custom-sidebar-toggle');
            const mobileCloseBtn = document.getElementById('custom-sidebar-close-mobile');
            const overlay = document.getElementById('custom-layout-overlay');
            const layoutMenu = document.getElementById('layout-menu');
            const htmlTag = document.documentElement;
            let sidebarPeekTimer = null;

            function isDesktopSidebar() {
                return window.innerWidth >= 1200;
            }

            function closeSidebarPeek() {
                window.clearTimeout(sidebarPeekTimer);
                sidebarPeekTimer = null;
                htmlTag.classList.remove('sidebar-peeking');
            }

            function scheduleSidebarPeek() {
                if (!isDesktopSidebar() || !htmlTag.classList.contains('sidebar-collapsed')) {
                    return;
                }

                window.clearTimeout(sidebarPeekTimer);
                sidebarPeekTimer = window.setTimeout(function() {
                    if (isDesktopSidebar() && htmlTag.classList.contains('sidebar-collapsed')) {
                        htmlTag.classList.add('sidebar-peeking');
                    }
                }, 130);
            }

            function toggleSidebar() {
                const isMobile = window.innerWidth < 1200;
                closeSidebarPeek();

                if (isMobile) {
                    // Logika Mobile: Toggle class sidebar-mobile-open
                    htmlTag.classList.toggle('sidebar-mobile-open');
                } else {
                    // Logika Desktop: Toggle class sidebar-collapsed & simpan ke localStorage
                    htmlTag.classList.toggle('sidebar-collapsed');

                    if (htmlTag.classList.contains('sidebar-collapsed')) {
                        localStorage.setItem('customSidebarState', 'collapsed');
                    } else {
                        localStorage.setItem('customSidebarState', 'expanded');
                    }
                }
            }

            if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if (layoutMenu) {
                layoutMenu.addEventListener('pointerenter', scheduleSidebarPeek);
                layoutMenu.addEventListener('pointerleave', closeSidebarPeek);
                layoutMenu.addEventListener('mouseenter', scheduleSidebarPeek);
                layoutMenu.addEventListener('mouseleave', closeSidebarPeek);
                layoutMenu.addEventListener('focusin', scheduleSidebarPeek);
                layoutMenu.addEventListener('focusout', function(event) {
                    if (!layoutMenu.contains(event.relatedTarget)) {
                        closeSidebarPeek();
                    }
                });
            }

            document.querySelectorAll('#custom-sidebar-toggle, .dropdown-user .nav-link, .dropdown-user img')
                .forEach((element) => {
                    element.setAttribute('draggable', 'false');
                    element.addEventListener('dragstart', (event) => event.preventDefault());
                });

            if (mobileCloseBtn) {
                mobileCloseBtn.addEventListener('click', function() {
                    htmlTag.classList.remove('sidebar-mobile-open');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    htmlTag.classList.remove('sidebar-mobile-open');
                });
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                closeSidebarPeek();

                if (window.innerWidth >= 1200) {
                    htmlTag.classList.remove('sidebar-mobile-open');
                }
            });

            // State Restoration (Desktop)
            if (window.innerWidth >= 1200) {
                const state = localStorage.getItem('customSidebarState');
                if (state === 'expanded' || !state) {
                    htmlTag.classList.remove('sidebar-collapsed');
                }
            }
        });
    </script>
    <script>
        (function() {
            function initTopbarSearch() {
                const trigger = document.getElementById('topbarSearchTrigger');
                const modal = document.getElementById('apsMenuSearch');
                const input = document.getElementById('apsMenuSearchInput');
                const closeBtn = document.getElementById('apsMenuSearchClose');
                const empty = document.getElementById('apsMenuSearchEmpty');
                if (!trigger || !modal || !input) return;

                const items = Array.from(modal.querySelectorAll('[data-search-item]'));
                const groups = Array.from(modal.querySelectorAll('[data-search-group]'));

                function filterItems() {
                    const query = input.value.trim().toLowerCase();
                    let visibleCount = 0;

                    items.forEach(function(item) {
                        const keywords = item.getAttribute('data-keywords') || '';
                        const isVisible = !query || keywords.includes(query);
                        item.style.display = isVisible ? '' : 'none';
                        if (isVisible) visibleCount += 1;
                    });

                    groups.forEach(function(group) {
                        const hasVisible = Array.from(group.querySelectorAll('[data-search-item]'))
                            .some(function(item) {
                                return item.style.display !== 'none';
                            });
                        group.style.display = hasVisible ? '' : 'none';
                    });

                    if (empty) empty.classList.toggle('is-visible', visibleCount === 0);
                }

                function openSearch() {
                    modal.classList.add('is-open');
                    document.documentElement.classList.add('aps-search-open');
                    input.value = '';
                    filterItems();
                    window.setTimeout(function() {
                        input.focus();
                    }, 40);
                }

                function closeSearch() {
                    modal.classList.remove('is-open');
                    document.documentElement.classList.remove('aps-search-open');
                }

                trigger.addEventListener('click', openSearch);
                if (closeBtn) closeBtn.addEventListener('click', closeSearch);
                input.addEventListener('input', filterItems);

                modal.addEventListener('click', function(event) {
                    if (event.target === modal) closeSearch();
                    if (event.target.closest('[data-search-item]')) closeSearch();
                });

                document.addEventListener('keydown', function(event) {
                    const key = event.key.toLowerCase();
                    if ((event.ctrlKey || event.metaKey) && key === 'k') {
                        event.preventDefault();
                        openSearch();
                    }

                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        event.preventDefault();
                        closeSearch();
                    }
                });

                window.apsCloseMenuSearch = closeSearch;
            }

            document.addEventListener('DOMContentLoaded', initTopbarSearch);
        })();
    </script>
    <script>
        (function() {
            const contentSelector = '#pjax-content';
            const scriptsSelector = '#pjax-page-scripts';
            const sidebarScrollKey = 'apsSidebarScrollTop';
            let activeRequest = null;

            function getSidebarScroller() {
                return document.querySelector('#layout-menu .menu-inner');
            }

            function saveSidebarScroll() {
                const scroller = getSidebarScroller();
                if (scroller) {
                    sessionStorage.setItem(sidebarScrollKey, String(scroller.scrollTop || 0));
                }
            }

            function restoreSidebarScroll() {
                const scroller = getSidebarScroller();
                const saved = Number(sessionStorage.getItem(sidebarScrollKey) || 0);
                if (!scroller || Number.isNaN(saved)) return;

                scroller.scrollTop = saved;
                requestAnimationFrame(function() {
                    scroller.scrollTop = saved;
                });
                setTimeout(function() {
                    scroller.scrollTop = saved;
                }, 120);
            }

            function isSameOriginPage(url) {
                return url.origin === window.location.origin &&
                    (url.pathname !== window.location.pathname || url.search !== window.location.search);
            }

            function shouldHandleLink(event, link) {
                if (!link || event.defaultPrevented) return false;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return false;
                if (link.target && link.target !== '_self') return false;
                if (link.hasAttribute('download')) return false;

                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;

                const url = new URL(link.href, window.location.href);
                return isSameOriginPage(url);
            }

            function syncSidebarState(newDocument) {
                const currentItems = document.querySelectorAll('#layout-menu li.menu-item');
                const nextItems = newDocument.querySelectorAll('#layout-menu li.menu-item');
                currentItems.forEach(function(item, index) {
                    const next = nextItems[index];
                    if (next) item.className = next.className;
                });
            }

            function findComment(root, marker) {
                if (!root) return null;
                const walker = document.createTreeWalker(
                    root,
                    NodeFilter.SHOW_COMMENT,
                    null,
                    false
                );
                let node;
                while ((node = walker.nextNode())) {
                    if (node.nodeValue && node.nodeValue.trim() === marker) {
                        return node;
                    }
                }
                return null;
            }

            function nodesBetween(startMarker, endMarker) {
                if (!startMarker || !endMarker) return [];
                const parent = startMarker.parentNode;
                if (!parent || parent !== endMarker.parentNode) return [];
                const nodes = Array.from(parent.childNodes);
                const startIndex = nodes.indexOf(startMarker);
                const endIndex = nodes.indexOf(endMarker);
                if (startIndex === -1 || endIndex === -1 || endIndex <= startIndex) return [];
                return nodes.slice(startIndex + 1, endIndex);
            }

            async function replacePageStyles(newDocument) {
                const currentStart = findComment(document, 'pjax-page-styles-start');
                const currentEnd = findComment(document, 'pjax-page-styles-end');
                const nextStart = findComment(newDocument, 'pjax-page-styles-start');
                const nextEnd = findComment(newDocument, 'pjax-page-styles-end');

                if (currentStart && currentEnd) {
                    nodesBetween(currentStart, currentEnd).forEach(function(node) {
                        node.remove();
                    });
                } else {
                    document.querySelectorAll('head [data-aps-pjax-style]').forEach(function(el) {
                        el.remove();
                    });
                }

                let styleNodes = [];
                if (nextStart && nextEnd) {
                    styleNodes = nodesBetween(nextStart, nextEnd);
                } else {
                    styleNodes = Array.from(newDocument.querySelectorAll('head link[rel="stylesheet"], head style'));
                }

                const insertTarget = currentEnd || document.head.lastChild;
                const loadPromises = [];

                styleNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.tagName === 'LINK' && (node.getAttribute('rel') || '').toLowerCase() === 'stylesheet') {
                        const href = node.getAttribute('href');
                        if (!href) return;

                        const isStatic = Array.from(document.head.children).some(function(child) {
                            return child !== currentStart && child !== currentEnd &&
                                   child.tagName === 'LINK' &&
                                   child.getAttribute('href') === href &&
                                   !child.hasAttribute('data-aps-pjax-style');
                        });
                        if (isStatic) return;

                        const newLink = document.createElement('link');
                        Array.from(node.attributes).forEach(function(attr) {
                            newLink.setAttribute(attr.name, attr.value);
                        });
                        newLink.setAttribute('data-aps-pjax-style', 'true');

                        const p = new Promise(function(resolve) {
                            const timer = setTimeout(resolve, 2000);
                            newLink.onload = function() { clearTimeout(timer); resolve(); };
                            newLink.onerror = function() { clearTimeout(timer); resolve(); };
                        });
                        loadPromises.push(p);

                        if (insertTarget && insertTarget.parentNode === document.head) {
                            document.head.insertBefore(newLink, insertTarget);
                        } else {
                            document.head.appendChild(newLink);
                        }
                    } else if (node.nodeType === 1 && node.tagName === 'STYLE') {
                        const newStyle = document.createElement('style');
                        Array.from(node.attributes).forEach(function(attr) {
                            newStyle.setAttribute(attr.name, attr.value);
                        });
                        newStyle.setAttribute('data-aps-pjax-style', 'true');
                        newStyle.textContent = node.textContent;

                        if (insertTarget && insertTarget.parentNode === document.head) {
                            document.head.insertBefore(newStyle, insertTarget);
                        } else {
                            document.head.appendChild(newStyle);
                        }
                    }
                });

                await Promise.all(loadPromises);
            }

            async function replacePageScripts(newDocument) {
                const currentScripts = document.querySelector(scriptsSelector);
                const nextScripts = newDocument.querySelector(scriptsSelector);
                if (!currentScripts || !nextScripts) return;

                currentScripts.innerHTML = '';
                const scripts = Array.from(nextScripts.querySelectorAll('script'));

                for (const script of scripts) {
                    await new Promise(function(resolve) {
                        const next = document.createElement('script');

                        Array.from(script.attributes).forEach(function(attribute) {
                            next.setAttribute(attribute.name, attribute.value);
                        });

                        if (script.src) {
                            const timer = setTimeout(resolve, 3000);
                            next.onload = function() { clearTimeout(timer); resolve(); };
                            next.onerror = function() { clearTimeout(timer); resolve(); };
                            next.src = script.src;
                            currentScripts.appendChild(next);
                            return;
                        }

                        next.textContent = script.textContent;
                        currentScripts.appendChild(next);
                        resolve();
                    });
                }
            }

            function executeScriptsInContainer(container) {
                if (!container) return;
                const scripts = Array.from(container.querySelectorAll('script'));
                scripts.forEach(function(oldScript) {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            }

            function processStylesInContainer(container) {
                if (!container) return;
                const styles = Array.from(container.querySelectorAll('style'));
                styles.forEach(function(oldStyle) {
                    const newStyle = document.createElement('style');
                    Array.from(oldStyle.attributes).forEach(function(attr) {
                        newStyle.setAttribute(attr.name, attr.value);
                    });
                    newStyle.textContent = oldStyle.textContent;
                    oldStyle.parentNode.replaceChild(newStyle, oldStyle);
                });
            }

            async function loadContent(url, options) {
                const target = new URL(url, window.location.href);
                const shouldPush = !options || options.push !== false;
                const currentContent = document.querySelector(contentSelector);
                if (!currentContent) {
                    window.location.href = target.href;
                    return;
                }

                saveSidebarScroll();

                if (activeRequest) activeRequest.abort();
                activeRequest = new AbortController();
                const request = activeRequest;
                document.documentElement.classList.add('sidebar-pjax-loading');

                try {
                    const response = await fetch(target.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-PJAX': 'true'
                        },
                            signal: request.signal
                        });

                    if (!response.ok) throw new Error('Navigation failed');

                    const html = await response.text();
                    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                    const nextContent = nextDocument.querySelector(contentSelector);
                    if (!nextContent) throw new Error('Missing PJAX content');

                    document.title = nextDocument.title || document.title;
                    await replacePageStyles(nextDocument);
                    currentContent.innerHTML = nextContent.innerHTML;
                    processStylesInContainer(currentContent);
                    executeScriptsInContainer(currentContent);
                    syncSidebarState(nextDocument);
                    await replacePageScripts(nextDocument);

                    if (shouldPush) {
                        history.pushState({
                            pjax: true
                        }, '', target.href);
                    }

                    if (document.scrollingElement) {
                        document.scrollingElement.scrollTop = 0;
                    }
                    restoreSidebarScroll();
                    document.dispatchEvent(new Event('DOMContentLoaded'));
                    window.dispatchEvent(new CustomEvent('aps:content-loaded', {
                        detail: {
                            url: target.href
                        }
                    }));
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        window.location.href = target.href;
                    }
                } finally {
                    if (activeRequest === request) activeRequest = null;
                    document.documentElement.classList.remove('sidebar-pjax-loading');
                }
            }

            document.addEventListener('click', function(event) {
                const link = event.target.closest('#layout-menu a.menu-link, .aps-menu-search-item');
                if (!shouldHandleLink(event, link)) return;
                event.preventDefault();
                // Auto-close sidebar on mobile when menu link clicked
                if (window.innerWidth < 1200) {
                    document.documentElement.classList.remove('sidebar-mobile-open');
                }
                if (typeof window.apsCloseMenuSearch === 'function') {
                    window.apsCloseMenuSearch();
                }
                loadContent(link.href);
            });

            window.addEventListener('popstate', function() {
                loadContent(window.location.href, {
                    push: false
                });
            });

            window.addEventListener('beforeunload', saveSidebarScroll);
            document.addEventListener('DOMContentLoaded', restoreSidebarScroll);
        })();
    </script>
    @include('sweetalert::alert')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script>
        document.addEventListener('click', function(event) {
            const logoutLink = event.target.closest('#profile-logout-link');
            if (!logoutLink) return;

            event.preventDefault();

            const logoutForm = document.getElementById('logout-form');
            if (!logoutForm) return;

            if (typeof Swal === 'undefined') {
                if (window.confirm('Yakin ingin logout?')) {
                    logoutForm.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Yakin ingin logout?',
                text: 'Sesi akun akan diakhiri dan Anda perlu login kembali.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    popup: 'logout-confirm-popup',
                    confirmButton: 'btn logout-confirm-button',
                    cancelButton: 'btn logout-cancel-button'
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    logoutForm.submit();
                }
            });
        });

        function markSingleRead(readUrl, event, targetUrl) {
            if (event) event.preventDefault();
            fetch(readUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).finally(function() {
                window.location.href = targetUrl;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if(session('error'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: {!! json_encode(session('error')) !!},
                        confirmButtonText: 'OK'
                    });
                }
            @endif
            @if(session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: {!! json_encode(session('success')) !!},
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            @endif
            @if(session('warning'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: {!! json_encode(session('warning')) !!},
                        confirmButtonText: 'OK'
                    });
                }
            @endif
        });
    </script>
</body>

</html>
