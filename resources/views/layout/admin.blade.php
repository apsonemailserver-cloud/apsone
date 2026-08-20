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

    <link rel="stylesheet" href="{{ asset('vendor/public-sans/public-sans.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome6/css/all.min.css') }}">
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />


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

                    @if (Auth::user()->canAccess('schedule', 'view') || Auth::user()->canAccess('shift', 'view'))
                    <li class="menu-item {{ request()->is('schedule*') || request()->routeIs('schedule.*') || request()->routeIs('shift.*') ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-calendar-week"></i>
                            <div data-i18n="Schedule">Schedule</div>
                        </a>
                        <ul class="menu-sub">
                            @if (Auth::user()->canAccess('schedule', 'view'))
                            <li class="menu-item {{ request()->routeIs('schedule.now') ? 'active' : '' }}">
                                <a href="{{ route('schedule.now') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-calendar-check"></i>
                                    <div data-i18n="Today's Schedule">Today's Schedule</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('schedule.index') ? 'active' : '' }}">
                                <a href="{{ route('schedule.index') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-calendar"></i>
                                    <div data-i18n="Calendar View">Calendar View</div>
                                </a>
                            </li>
                            @if (Auth::user()->isAdmin() || strtolower((string) Auth::user()->role) === 'admin')
                                <li
                                    class="menu-item {{ request()->routeIs('schedule.create') || request()->routeIs('schedule.edit') || request()->routeIs('schedule.view') || request()->routeIs('schedule.show') ? 'active' : '' }}">
                                    <a href="{{ route('schedule.view') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-calendar-plus"></i>
                                        <div data-i18n="Create/Update">Create / Update</div>
                                    </a>
                                </li>
                            @endif
                            @endif

                            @if (Auth::user()->canAccess('shift', 'view'))
                            <li class="menu-item {{ request()->routeIs('shift.*') ? 'active' : '' }}">
                                <a href="{{ route('shift.index') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-clock"></i>
                                    <div data-i18n="Shift">Shift</div>
                                </a>
                            </li>
                            @endif
                        </ul>
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
                    <li class="menu-item {{ request()->routeIs('work_results.*') || request()->routeIs('work_orders.*') || request()->routeIs('assignments.*') ? 'active' : '' }}">
                        <a href="{{ route('assignments.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-plane-arrival"></i>
                            <div data-i18n="Assignment">Assignment</div>
                        </a>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('station', 'view') || Auth::user()->canAccess('user', 'view') || Auth::user()->canAccess('blacklist', 'view') || Auth::user()->canAccess('role', 'view') || Auth::user()->canAccess('job_title', 'view') || Auth::user()->canAccess('unit', 'view') || Auth::user()->canAccess('sub_unit', 'view') || Auth::user()->canAccess('cluster', 'view'))
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

                        @if(Auth::user()->canAccess('user', 'view') || Auth::user()->canAccess('blacklist', 'view') || Auth::user()->canAccess('role', 'view') || Auth::user()->canAccess('job_title', 'view') || Auth::user()->canAccess('unit', 'view') || Auth::user()->canAccess('sub_unit', 'view') || Auth::user()->canAccess('cluster', 'view'))
                        <li
                            class="menu-item {{ request()->routeIs('employees.*') || request()->routeIs('users.*') || request()->routeIs('staff.*') || request()->routeIs('blacklist.*') || request()->routeIs('roles.*') || request()->routeIs('master_data.*') || request()->routeIs('master.clusters.*') ? 'active open' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                                <i class="menu-icon tf-icons ti ti-users"></i>
                                <div data-i18n="User Management">User Management</div>
                            </a>
                            <ul class="menu-sub">
                                @if(Auth::user()->canAccess('user', 'view'))
                                <li class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                                    <a href="{{ route('employees.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-users"></i>
                                        <div data-i18n="Employees">Employees</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('staff.*') || request()->routeIs('users.*') ? 'active' : '' }}">
                                    <a href="{{ route('staff.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-device-desktop"></i>
                                        <div data-i18n="Station Monitoring">Station Monitoring</div>
                                    </a>
                                </li>

                                <li class="menu-header small text-uppercase mt-3 mb-1" style="padding-left: 2.5rem !important;">
                                    <span class="menu-header-text" style="font-size: 0.68rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.05em;">DATA OPERATIONAL</span>
                                </li>
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

                                @if(Auth::user()->canAccess('role', 'view') || Auth::user()->canAccess('job_title', 'view') || Auth::user()->canAccess('unit', 'view') || Auth::user()->canAccess('sub_unit', 'view') || Auth::user()->canAccess('cluster', 'view'))
                                <li class="menu-header small text-uppercase mt-3 mb-1" style="padding-left: 2.5rem !important;">
                                    <span class="menu-header-text" style="font-size: 0.68rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.05em;">DATA MASTER</span>
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

                                @if(Auth::user()->canAccess('job_title', 'view'))
                                <li class="menu-item {{ request()->routeIs('master_data.job_titles.*') ? 'active' : '' }}">
                                    <a href="{{ route('master_data.job_titles.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-briefcase"></i>
                                        <div data-i18n="Job Titles">Job Titles</div>
                                    </a>
                                </li>
                                @endif

                                @if(Auth::user()->canAccess('unit', 'view'))
                                <li class="menu-item {{ request()->routeIs('master_data.units.*') ? 'active' : '' }}">
                                    <a href="{{ route('master_data.units.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-building"></i>
                                        <div data-i18n="Units">Units</div>
                                    </a>
                                </li>
                                @endif

                                @if(Auth::user()->canAccess('sub_unit', 'view'))
                                <li class="menu-item {{ request()->routeIs('master_data.sub_units.*') ? 'active' : '' }}">
                                    <a href="{{ route('master_data.sub_units.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-hierarchy-2"></i>
                                        <div data-i18n="Sub Units">Sub Units</div>
                                    </a>
                                </li>
                                @endif

                                @if(Auth::user()->canAccess('cluster', 'view') || Auth::user()->canAccess('user', 'view'))
                                <li class="menu-item {{ request()->routeIs('master_data.clusters.*') || request()->routeIs('master.clusters.*') ? 'active' : '' }}">
                                    <a href="{{ route('master_data.clusters.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-layout-grid"></i>
                                        <div data-i18n="Clusters">Clusters</div>
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
                    <li class="menu-item {{ (request()->is('training*') || request()->is('my-certificates*') || request()->routeIs('my.certificates*') || request()->routeIs('training.*')) ? 'active open' : '' }}">
                        <a href="#" class="menu-link menu-toggle" role="button" aria-expanded="false">
                            <i class="menu-icon tf-icons ti ti-award"></i>
                            <div data-i18n="Training">Training</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('my.certificates') ? 'active' : '' }}">
                                <a href="{{ route('my.certificates') }}" class="menu-link">
                                    <i class="menu-icon tf-icons ti ti-certificate"></i>
                                    <div data-i18n="My Certificates">Sertifikat Saya</div>
                                </a>
                            </li>
                            @if (Auth::user()->canAccess('training', 'create') || Auth::user()->canAccess('training', 'edit') || Auth::user()->isAdmin())
                                <li class="menu-item {{ request()->routeIs('admin.training.certificates.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.training.certificates.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-book"></i>
                                        <div data-i18n="Training Management">Training Management</div>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if (Auth::user()->canAccess('leave', 'view') || Auth::user()->canAccess('leave', 'create') || Auth::user()->canAccess('leave', 'approve') || Auth::user()->canAccess('leave', 'export') || Auth::user()->canAccess('master_leave', 'view'))
                    <li class="menu-item {{ request()->is('leaves*') || request()->is('master-leaves*') ? 'active open' : '' }}">
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
                            @if (Auth::user()->canAccess('leave', 'view') || Auth::user()->canAccess('leave', 'approve') || Auth::user()->isAdmin())
                                <li class="menu-item {{ request()->routeIs('leaves.balances') ? 'active' : '' }}">
                                    <a href="{{ route('leaves.balances') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-chart-bar"></i>
                                        <div data-i18n="Leave Balance">Leave Balance</div>
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
                            @if (Auth::user()->canAccess('master_leave', 'view'))
                                <li class="menu-item {{ request()->routeIs('master_leaves.*') ? 'active' : '' }}">
                                    <a href="{{ route('master_leaves.index') }}" class="menu-link">
                                        <i class="menu-icon tf-icons ti ti-settings"></i>
                                        <div data-i18n="Master Cuti">Master Cuti</div>
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
                    $canManageSchedule = $currentUser->isAdmin() || strtolower((string) $currentUser->role) === 'admin';
                    $canViewAdminAttendance = in_array($currentUser->role, ['Admin', 'Head Of Airport Service']);
                    $canApproveOvertime = in_array($currentUser->role, ['Admin', 'LEADER', 'Head Of Airport Service', 'ASS LEADER']);
                    $canApproveAttendanceCorrections = in_array('Admin', array_map('trim', explode(',', (string) $currentUser->role)), true)
                        || \App\Models\User::where('manager', $currentUser->fullname)->exists();
                    $canManageTraining = in_array($currentUser->role, ['Admin', 'HSE', 'Head Of Airport Service']);
                    $canManageLeave = in_array($currentUser->role, ['Admin', 'Head Of Airport Service']);
                    $topbarMenuLinks = [];
                    if ($currentUser->canAccess('dashboard', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Dashboard', 'category' => 'Menu', 'icon' => 'ti-layout-dashboard', 'url' => route('home'), 'keywords' => 'dashboard beranda overview'];
                    }
                    $topbarMenuLinks[] = ['label' => 'Profile', 'category' => 'Menu', 'icon' => 'ti-user-circle', 'url' => route('users.profile', $currentUser->id), 'keywords' => 'profile profil biodata staff user'];

                    if ($currentUser->canAccess('schedule', 'view')) {
                        $topbarMenuLinks[] = ['label' => "Today's Schedule", 'category' => 'Schedule', 'icon' => 'ti-calendar-check', 'url' => route('schedule.now'), 'keywords' => 'jadwal hari ini schedule now'];
                        $topbarMenuLinks[] = ['label' => 'Calendar View', 'category' => 'Schedule', 'icon' => 'ti-calendar', 'url' => route('schedule.index'), 'keywords' => 'data schedule jadwal bulanan calendar view'];
                    }

                    if ($canManageSchedule && ($currentUser->canAccess('schedule', 'create') || $currentUser->canAccess('schedule', 'edit'))) {
                        $topbarMenuLinks[] = ['label' => 'Create / Update', 'category' => 'Schedule', 'icon' => 'ti-calendar-plus', 'url' => route('schedule.view'), 'keywords' => 'create update schedule buat jadwal tambah'];
                    }

                    if ($currentUser->canAccess('shift', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Shift', 'category' => 'Schedule', 'icon' => 'ti-clock', 'url' => route('shift.index'), 'keywords' => 'shift kerja jam data shift'];
                    }

                    if ($currentUser->canAccess('attendance', 'view')) {
                        $topbarMenuLinks[] = ['label' => "Today's Attendance", 'category' => 'Attendance', 'icon' => 'ti-stopwatch', 'url' => route('attendance.index'), 'keywords' => 'absensi hari ini presensi'];
                    }

                    if ($canViewAdminAttendance || $currentUser->canAccess('attendance', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Attendance Report', 'category' => 'Attendance', 'icon' => 'ti-file-text', 'url' => route('attendance.reports'), 'keywords' => 'laporan absensi rekap export'];
                    }

                    if ($canApproveAttendanceCorrections || $currentUser->canAccess('attendance', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Correction Approvals', 'category' => 'Attendance', 'icon' => 'ti-user-check', 'url' => route('attendance.corrections.approval'), 'keywords' => 'approval koreksi absensi validasi'];
                    }

                    if ($currentUser->canAccess('overtime', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'My Overtime', 'category' => 'Attendance', 'icon' => 'ti-hourglass', 'url' => route('overtime.index'), 'keywords' => 'lembur saya pengajuan lembur overtime'];
                    }

                    if ($canApproveOvertime || $currentUser->canAccess('overtime', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Overtime Approvals', 'category' => 'Attendance', 'icon' => 'ti-circle-check', 'url' => route('overtime.approval'), 'keywords' => 'approval lembur validasi persetujuan'];
                    }

                    if ($currentUser->role === 'Admin' || $currentUser->canAccess('overtime', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Overtime Report', 'category' => 'Attendance', 'icon' => 'ti-chart-line', 'url' => route('overtime.report'), 'keywords' => 'laporan lembur rekap export overtime'];
                    }

                    if ($currentUser->canAccess('assignment', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Assignment', 'category' => 'Menu', 'icon' => 'ti-plane-arrival', 'url' => route('assignments.index'), 'keywords' => 'assignment tugas penerbangan pekerjaan order'];
                    }

                    if ($currentUser->canAccess('station', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Station Management', 'category' => 'Administrator', 'icon' => 'ti-building-store', 'url' => route('stations.index'), 'keywords' => 'station management kelola status stasiun'];
                    }
                    if ($currentUser->canAccess('user', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Station Monitoring', 'category' => 'Administrator', 'icon' => 'ti-device-desktop', 'url' => route('staff.index'), 'keywords' => 'station monitoring staff pantau station'];
                        $topbarMenuLinks[] = ['label' => 'Contracts', 'category' => 'Administrator', 'icon' => 'ti-file-text', 'url' => route('users.kontrak'), 'keywords' => 'contracts kontrak staff masa kerja'];
                        $topbarMenuLinks[] = ['label' => 'Airport PAS', 'category' => 'Administrator', 'icon' => 'ti-id', 'url' => route('users.pas'), 'keywords' => 'airport pas bandara masa aktif'];
                        $topbarMenuLinks[] = ['label' => 'Airport TIM', 'category' => 'Administrator', 'icon' => 'ti-badge', 'url' => route('users.tim'), 'keywords' => 'airport tim bandara tanda izin'];
                    }
                    if ($currentUser->canAccess('role', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Role & Permissions', 'category' => 'Administrator', 'icon' => 'ti-shield-lock', 'url' => route('roles.index'), 'keywords' => 'role permissions hak akses'];
                    }
                    if ($currentUser->canAccess('job_title', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Job Titles', 'category' => 'Administrator', 'icon' => 'ti-briefcase', 'url' => route('master_data.job_titles.index'), 'keywords' => 'job titles jabatan master data'];
                    }
                    if ($currentUser->canAccess('unit', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Units', 'category' => 'Administrator', 'icon' => 'ti-building', 'url' => route('master_data.units.index'), 'keywords' => 'units unit master data'];
                    }
                    if ($currentUser->canAccess('sub_unit', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Sub Units', 'category' => 'Administrator', 'icon' => 'ti-hierarchy-2', 'url' => route('master_data.sub_units.index'), 'keywords' => 'sub units master data'];
                    }
                    if ($currentUser->canAccess('cluster', 'view') || $currentUser->canAccess('user', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Clusters', 'category' => 'Administrator', 'icon' => 'ti-layout-grid', 'url' => route('master_data.clusters.index'), 'keywords' => 'clusters cluster master data'];
                    }
                    if ($currentUser->canAccess('blacklist', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Blacklist Staff', 'category' => 'Administrator', 'icon' => 'ti-user-x', 'url' => route('blacklist.index'), 'keywords' => 'blacklist staff blokir'];
                    }

                    if ($currentUser->canAccess('document', 'view')) {
                        $topbarDokumenUrl = ($currentUser->hasPermission('document.edit') || $currentUser->role === 'Admin') ? route('admin.documents.index') : route('document');
                        $topbarMenuLinks[] = ['label' => 'Documents', 'category' => 'General', 'icon' => 'ti-file-text', 'url' => $topbarDokumenUrl, 'keywords' => 'documents dokumen cetak surat berkas'];
                    }

                    if ($currentUser->canAccess('training', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'My Certificates', 'category' => 'Training', 'icon' => 'ti-certificate', 'url' => route('my.certificates'), 'keywords' => 'sertifikat saya my certificates training'];
                        if ($canManageTraining || $currentUser->canAccess('training', 'create') || $currentUser->canAccess('training', 'edit') || $currentUser->isAdmin()) {
                            $topbarMenuLinks[] = ['label' => 'Training Management', 'category' => 'Training', 'icon' => 'ti-book', 'url' => route('admin.training.certificates.index'), 'keywords' => 'training management kelola sertifikat tambah'];
                        }
                    }

                    if ($currentUser->canAccess('leave', 'view') || $currentUser->canAccess('leave', 'create')) {
                        $topbarMenuLinks[] = ['label' => 'Leave Request', 'category' => 'Apply Leave', 'icon' => 'ti-send', 'url' => route('leaves.pengajuan'), 'keywords' => 'leave request pengajuan cuti izin sakit'];
                    }

                    if ($canManageLeave || $currentUser->canAccess('leave', 'approve')) {
                        $topbarMenuLinks[] = ['label' => 'Leave Approvals', 'category' => 'Apply Leave', 'icon' => 'ti-circle-check', 'url' => route('leaves.index'), 'keywords' => 'leave approvals persetujuan cuti approval'];
                    }

                    if ($currentUser->canAccess('leave', 'view') || $currentUser->canAccess('leave', 'approve') || $currentUser->isAdmin()) {
                        $topbarMenuLinks[] = ['label' => 'Leave Balance', 'category' => 'Apply Leave', 'icon' => 'ti-chart-bar', 'url' => route('leaves.balances'), 'keywords' => 'leave balance sisa saldo cuti'];
                    }

                    if ($canManageLeave || $currentUser->canAccess('leave', 'export')) {
                        $topbarMenuLinks[] = ['label' => 'Leave Report', 'category' => 'Apply Leave', 'icon' => 'ti-file-text', 'url' => route('leaves.laporan'), 'keywords' => 'leave report laporan rekap cuti'];
                    }

                    if ($currentUser->canAccess('master_leave', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Master Cuti', 'category' => 'Apply Leave', 'icon' => 'ti-settings', 'url' => route('master_leaves.index'), 'keywords' => 'master cuti tipe aturan masa kerja'];
                    }

                    if ($currentUser->canAccess('announcement', 'view')) {
                        $topbarMenuLinks[] = ['label' => 'Announcements', 'category' => 'General', 'icon' => 'ti-speakerphone', 'url' => route('announcements.index'), 'keywords' => 'announcements pengumuman berita informasi'];
                    }

                    $topbarMenuLinks[] = ['label' => 'FAQ', 'category' => 'Support', 'icon' => 'ti-help-circle', 'url' => route('faq'), 'keywords' => 'faq tanya jawab bantuan support'];
                    $topbarMenuLinks[] = ['label' => 'Privacy Policy', 'category' => 'Support', 'icon' => 'ti-shield-check', 'url' => route('kebijakan'), 'keywords' => 'privacy policy kebijakan privasi privasi support'];
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
                            <input class="aps-menu-search-input" id="apsMenuSearchInput" type="text"
                                placeholder="Cari menu, fitur, atau halaman..." autocomplete="off">
                            <button class="aps-menu-search-close" type="button" id="apsMenuSearchClose"
                                aria-label="Tutup pencarian">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="aps-menu-search-body">
                            <!-- Initial state (when input is empty) -->
                            <div class="aps-menu-search-initial" id="apsMenuSearchInitial">
                                <span class="aps-menu-search-initial-icon"><i class="ti ti-search"></i></span>
                                <p>Ketik minimal 1 atau 2 huruf untuk mencari menu...</p>
                            </div>

                            <!-- Empty / Not found state -->
                            <div class="aps-menu-search-empty" id="apsMenuSearchEmpty" style="display: none;">
                                <span class="aps-menu-search-empty-icon"><i class="ti ti-file-search"></i></span>
                                <p>Menu tidak ditemukan. Coba kata kunci lain.</p>
                            </div>

                            <!-- Flat vertical list of items (hidden initially until user types) -->
                            <div class="aps-menu-search-list w-100" id="apsMenuSearchList" style="display: none; width: 100%;">
                                @foreach ($topbarMenuLinks as $item)
                                    <a class="aps-menu-search-item w-100" href="{{ $item['url'] }}"
                                        style="width: 100%;"
                                        data-search-item
                                        data-title="{{ $item['label'] }}"
                                        data-category="{{ $item['category'] }}"
                                        data-keywords="{{ $item['keywords'] ?? '' }}">
                                        <div class="aps-menu-search-item-main">
                                            <span class="aps-menu-search-item-icon">
                                                <i class="ti {{ $item['icon'] }}"></i>
                                            </span>
                                            <span class="aps-menu-search-title">{{ $item['label'] }}</span>
                                        </div>
                                        <div class="aps-menu-search-item-meta">
                                            <span class="aps-menu-search-category">{{ $item['category'] }}</span>
                                            <i class="ti ti-chevron-right aps-menu-search-arrow" aria-hidden="true"></i>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="aps-menu-search-foot">
                            <span>Pro tip: Use <kbd>&uarr;</kbd> <kbd>&darr;</kbd> to navigate, <kbd>Enter</kbd> to select, and <kbd>Esc</kbd> to close.</span>
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
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
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

        .aps-picker-day.is-disabled,
        .aps-picker-day[disabled],
        .aps-picker-nav[disabled],
        .aps-picker-nav.is-disabled {
            opacity: 0.25 !important;
            pointer-events: none !important;
            cursor: not-allowed !important;
        }

        /* Global Toggle Switch Styling (Light & Dark Mode) */
        .form-check-input:checked,
        .form-switch .form-check-input:checked {
            background-color: #2f80ed !important;
            border-color: #2f80ed !important;
        }

        html.aps-dark .form-check-input:checked,
        html.aps-dark .form-switch .form-check-input:checked {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
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
	        @include('sweetalert::alert')
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

	                const minDateVal = input.getAttribute('min') || input.dataset.apsMin || (input.min || '');
	                const maxDateVal = input.getAttribute('max') || input.dataset.apsMax || (input.max || '');

	                for (let day = 1; day <= daysInMonth; day += 1) {
	                    const itemDate = new Date(data.viewYear, data.viewMonth, day);
	                    const key = dateKey(itemDate);
	                    let isDisabled = false;
	                    if (minDateVal && key < minDateVal) isDisabled = true;
	                    if (maxDateVal && key > maxDateVal) isDisabled = true;

	                    const classes = [
	                        'aps-picker-day',
	                        key === selected ? 'is-selected' : '',
	                        key === today ? 'is-today' : '',
	                        isDisabled ? 'is-disabled' : ''
	                    ].filter(Boolean).join(' ');
	                    daysHtml += '<button type="button" class="' + classes + '" ' + (isDisabled ? 'disabled' : '') + ' data-picker-date="' + key + '">' + day + '</button>';
	                }

	                const isNextMonthDisabled = maxDateVal && dateKey(new Date(data.viewYear, data.viewMonth + 1, 1)) > maxDateVal;
	                const isNextYearDisabled = maxDateVal && dateKey(new Date(data.viewYear + 1, data.viewMonth, 1)) > maxDateVal;
	                const isPrevMonthDisabled = minDateVal && dateKey(new Date(data.viewYear, data.viewMonth, 0)) < minDateVal;
	                const isPrevYearDisabled = minDateVal && dateKey(new Date(data.viewYear - 1, data.viewMonth + 1, 0)) < minDateVal;

	                return [
	                    '<div class="aps-picker-head">',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="-12"' + (isPrevYearDisabled ? ' disabled style="opacity:0.25;cursor:not-allowed;pointer-events:none;"' : '') + ' aria-label="Tahun sebelumnya"><i class="ti ti-chevrons-left"></i></button>',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="-1"' + (isPrevMonthDisabled ? ' disabled style="opacity:0.25;cursor:not-allowed;pointer-events:none;"' : '') + ' aria-label="Bulan sebelumnya"><i class="ti ti-chevron-left"></i></button>',
	                    '</div>',
	                    '<div class="aps-picker-title">' + monthNames[data.viewMonth] + ' ' + data.viewYear + '</div>',
	                    '<div class="aps-picker-nav-group">',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="1"' + (isNextMonthDisabled ? ' disabled style="opacity:0.25;cursor:not-allowed;pointer-events:none;"' : '') + ' aria-label="Bulan berikutnya"><i class="ti ti-chevron-right"></i></button>',
	                    '<button type="button" class="aps-picker-nav" data-picker-nav="12"' + (isNextYearDisabled ? ' disabled style="opacity:0.25;cursor:not-allowed;pointer-events:none;"' : '') + ' aria-label="Tahun berikutnya"><i class="ti ti-chevrons-right"></i></button>',
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
	                    input.dataset.apsMin = input.getAttribute('min') || (input.min || '');
	                    input.dataset.apsMax = input.getAttribute('max') || (input.max || '');
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

	                        if (day && !day.disabled && !day.classList.contains('is-disabled')) {
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
	        (function() {
	            if (window.__apsSidebarInitialized) return;
	            window.__apsSidebarInitialized = true;

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

	            function bindMenuHover() {
	                const layoutMenu = document.getElementById('layout-menu');
	                if (layoutMenu && !layoutMenu.__apsHoverBound) {
	                    layoutMenu.__apsHoverBound = true;
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
	            }

	            // Delegated click handler on document - immune to PJAX DOM re-renders & prevents duplicate listeners
	            document.addEventListener('click', function(event) {
	                const toggleBtn = event.target.closest('#custom-sidebar-toggle');
	                if (toggleBtn) {
	                    event.preventDefault();
	                    event.stopPropagation();
	                    toggleSidebar();
	                    return;
	                }

	                const mobileCloseBtn = event.target.closest('#custom-sidebar-close-mobile');
	                if (mobileCloseBtn) {
	                    event.preventDefault();
	                    htmlTag.classList.remove('sidebar-mobile-open');
	                    return;
	                }

	                const overlay = event.target.closest('#custom-layout-overlay');
	                if (overlay) {
	                    event.preventDefault();
	                    htmlTag.classList.remove('sidebar-mobile-open');
	                    return;
	                }
	            });

	            // Prevent drag on controls
	            function preventDrag() {
	                document.querySelectorAll('#custom-sidebar-toggle, .dropdown-user .nav-link, .dropdown-user img')
	                    .forEach((element) => {
	                        element.setAttribute('draggable', 'false');
	                        element.addEventListener('dragstart', (event) => event.preventDefault());
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

	            if (document.readyState === 'loading') {
	                document.addEventListener('DOMContentLoaded', function() {
	                    bindMenuHover();
	                    preventDrag();
	                }, { once: true });
	            } else {
	                bindMenuHover();
	                preventDrag();
	            }

	            window.addEventListener('aps:content-loaded', function() {
	                bindMenuHover();
	                preventDrag();
	            });
	        })();
	    </script>
	    <script>
	        (function() {
	            function initTopbarSearch() {
	                const modal = document.getElementById('apsMenuSearch');
	                const input = document.getElementById('apsMenuSearchInput');
	                const initial = document.getElementById('apsMenuSearchInitial');
	                const empty = document.getElementById('apsMenuSearchEmpty');
	                const list = document.getElementById('apsMenuSearchList');
	                if (!modal || !input || !list) return;

	                let visibleItems = [];
	                let selectedIndex = -1;

	                function getItems() {
	                    return Array.from(modal.querySelectorAll('[data-search-item]'));
	                }

	                function updateSelection() {
	                    visibleItems.forEach(function(item, idx) {
	                        const isSelected = (idx === selectedIndex);
	                        item.classList.toggle('is-selected', isSelected);
	                        if (isSelected) {
	                            item.scrollIntoView({ block: 'nearest' });
	                        }
	                    });
	                }

	                function getMatchScore(item, query) {
	                    const title = (item.getAttribute('data-title') || '').toLowerCase().trim();
	                    const category = (item.getAttribute('data-category') || '').toLowerCase().trim();
	                    const keywords = (item.getAttribute('data-keywords') || '').toLowerCase().trim();

	                    if (!query) return 0;

	                    // 1. Exact match on title (Highest priority)
	                    if (title === query) return 10000;

	                    // 2. Title starts with query (e.g. 'dash' -> 'Dashboard')
	                    if (title.startsWith(query)) return 5000;

	                    // 3. Word in title starts with query (e.g. 'att' -> "Today's Attendance")
	                    const titleWords = title.split(/[\s\/\-_]+/);
	                    for (let word of titleWords) {
	                        if (word.startsWith(query)) return 3000;
	                    }

	                    // 4. Keyword exact or word starts with query (e.g. 'jadwal' -> "Today's Schedule")
	                    if (keywords) {
	                        if (keywords === query || keywords.startsWith(query)) return 2000;
	                        const kwWords = keywords.split(/[\s\/\-_]+/);
	                        for (let kw of kwWords) {
	                            if (kw.startsWith(query)) return 1500;
	                        }
	                    }

	                    // 5. Title contains query anywhere
	                    if (title.includes(query)) return 1000;

	                    // 6. Category starts with query (e.g. 'sched' -> all Schedule menus)
	                    if (category === query || category.startsWith(query)) return 600;
	                    const catWords = category.split(/[\s\/\-_]+/);
	                    for (let word of catWords) {
	                        if (word.startsWith(query)) return 400;
	                    }

	                    return 0; // Not matched
	                }

	                function filterItems() {
	                    const items = getItems();
	                    const query = input.value.trim().toLowerCase();

	                    if (query.length < 1) {
	                        if (initial) initial.style.setProperty('display', 'flex', 'important');
	                        if (empty) empty.style.setProperty('display', 'none', 'important');
	                        if (list) list.style.setProperty('display', 'none', 'important');
	                        items.forEach(function(item) {
	                            item.classList.remove('is-visible', 'is-selected');
	                        });
	                        visibleItems = [];
	                        selectedIndex = -1;
	                        updateSelection();
	                        return;
	                    }

	                    if (initial) initial.style.setProperty('display', 'none', 'important');

	                    // Calculate score and filter
	                    const scoredItems = [];
	                    items.forEach(function(item) {
	                        const score = getMatchScore(item, query);
	                        if (score > 0) {
	                            scoredItems.push({ item, score });
	                        } else {
	                            item.classList.remove('is-visible', 'is-selected');
	                        }
	                    });

	                    scoredItems.sort((a, b) => b.score - a.score);

	                    visibleItems = scoredItems.map(si => si.item);

	                    // Re-append in sorted order to the list container
	                    visibleItems.forEach(function(item) {
	                        item.classList.add('is-visible');
	                        list.appendChild(item);
	                    });

	                    if (visibleItems.length === 0) {
	                        if (empty) empty.style.setProperty('display', 'flex', 'important');
	                        if (list) list.style.setProperty('display', 'none', 'important');
	                        selectedIndex = -1;
	                    } else {
	                        if (empty) empty.style.setProperty('display', 'none', 'important');
	                        if (list) list.style.setProperty('display', 'flex', 'important');
	                        selectedIndex = 0; // Highlight the top ranked match by default
	                    }

	                    updateSelection();
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

	                input.oninput = filterItems;

	                document.addEventListener('click', function(event) {
	                    const trigger = event.target.closest('#topbarSearchTrigger');
	                    if (trigger) {
	                        event.preventDefault();
	                        openSearch();
	                        return;
	                    }

	                    const closeBtn = event.target.closest('#apsMenuSearchClose');
	                    if (closeBtn) {
	                        event.preventDefault();
	                        closeSearch();
	                        return;
	                    }

	                    if (event.target === modal) {
	                        closeSearch();
	                        return;
	                    }

	                    if (event.target.closest('[data-search-item]')) {
	                        closeSearch();
	                    }
	                });

	                // Hover on search item updates selection
	                modal.addEventListener('mousemove', function(event) {
	                    const item = event.target.closest('[data-search-item]');
	                    if (item) {
	                        const idx = visibleItems.indexOf(item);
	                        if (idx !== -1 && idx !== selectedIndex) {
	                            selectedIndex = idx;
	                            updateSelection();
	                        }
	                    }
	                });

	                document.addEventListener('keydown', function(event) {
	                    const key = event.key;
	                    if ((event.ctrlKey || event.metaKey) && key.toLowerCase() === 'k') {
	                        event.preventDefault();
	                        openSearch();
	                        return;
	                    }

	                    if (!modal.classList.contains('is-open')) return;

	                    if (key === 'Escape') {
	                        event.preventDefault();
	                        closeSearch();
	                        return;
	                    }

	                    if (key === 'ArrowDown') {
	                        event.preventDefault();
	                        if (visibleItems.length > 0) {
	                            selectedIndex = (selectedIndex + 1) % visibleItems.length;
	                            updateSelection();
	                        }
	                        return;
	                    }

	                    if (key === 'ArrowUp') {
	                        event.preventDefault();
	                        if (visibleItems.length > 0) {
	                            selectedIndex = (selectedIndex - 1 + visibleItems.length) % visibleItems.length;
	                            updateSelection();
	                        }
	                        return;
	                    }

	                    if (key === 'Enter') {
	                        if (selectedIndex >= 0 && visibleItems[selectedIndex]) {
	                            event.preventDefault();
	                            const targetLink = visibleItems[selectedIndex];
	                            closeSearch();
	                            targetLink.click();
	                        }
	                        return;
	                    }
	                });

	                window.apsCloseMenuSearch = closeSearch;
	            }

	            if (document.readyState === 'loading') {
	                document.addEventListener('DOMContentLoaded', initTopbarSearch);
	            } else {
	                initTopbarSearch();
	            }
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
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    @include('sweetalert::alert')
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
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                reverseButtons: false,
                focusCancel: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    logoutForm.submit();
                }
            });
        });

        // Universal SweetAlert Delete Confirmation Helper
        window.apsConfirmDelete = function(options) {
            return Swal.fire({
                title: options.title || 'Apakah Anda yakin?',
                text: options.text || 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: options.confirmButtonText || 'Ya, Hapus!',
                cancelButtonText: options.cancelButtonText || 'Batal',
                reverseButtons: false,
                focusCancel: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    if (options.formId) {
                        const form = document.getElementById(options.formId);
                        if (form) form.submit();
                    } else if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                    }
                }
                return result;
            });
        };

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

        // Guard flag: only fire flash notifications ONCE (prevents PJAX synthetic DOMContentLoaded re-triggering)
        var __apsNotifShown = false;
        document.addEventListener('DOMContentLoaded', function() {
            if (__apsNotifShown) return;
            __apsNotifShown = true;
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

            // GLOBAL FILE UPLOAD SIZE & EXTENSION VALIDATOR (MAX 2MB)
            document.addEventListener('change', function(e) {
                if (e.target && e.target.type === 'file' && e.target.files && e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const maxBytes = 2 * 1024 * 1024; // 2MB in bytes
                    if (file.size > maxBytes) {
                        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                        e.target.value = ''; // Instantly clear input so file is NOT sent to server
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ukuran File Terlalu Besar!',
                                text: `Ukuran file (${fileSizeMB} MB) melebihi batas maksimal 2MB. File dibatalkan dan tidak diunggah ke server.`,
                                confirmButtonColor: '#2f80ed'
                            });
                        } else {
                            alert(`Ukuran file (${fileSizeMB} MB) melebihi batas maksimal 2MB. File tidak diunggah!`);
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
