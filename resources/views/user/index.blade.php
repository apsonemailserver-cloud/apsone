<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT. Angkasa Pratama Sejahtera</title>
    <link rel="icon" href="{{ asset('storage/aps_mini.png') }}" sizes="48x48" type="image/png">

    <!-- Bootstrap & FontAwesome -->
    <link href="{{ asset('vendor/bootstrap3/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome5/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/material-icons/material-icons.css') }}" rel="stylesheet" />

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="/assets/css/style.css">

    <script src="{{ asset('/assets/js/script.js') }}" defer></script>
    <style>
        table {
            width: 1000px;
            min-width: 1000px;
            border-collapse: collapse;
        }

        th,
        td {
            white-space: nowrap;
            padding: 8px;
            text-align: left;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 15%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 15%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 15%;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 15%;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: 15%;
        }

        th:nth-child(6),
        td:nth-child(6) {
            width: 15%;
        }
    </style>
</head>

<body class="with-sidebar">
    @include('app')

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1">
                <div>
                    <h2 class="fas fa-users"> User</h2>
                    <p>Sebuah informasi tentang pegawai yang terdaftar dalam sistem.</p>
                </div>
            </div>
            <div class="text-right">
                <a href="{{ route('users.create') }}" class="btn btn-primary" style="margin-bottom: 10px;">
                    <i class="fa fa-plus-circle"></i> Create
                </a>
            </div>
            <div class="text-right">
                <form action="{{ route('users.index') }}" method="GET" class="form-inline" style="margin-top: 10px;">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari NIP / Nama" value="{{ request('search') }}">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                        </span>
                    </div>
                </form>
            </div>
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-fixed">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Fullname</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user as $users)
                        <tr>
                            <td>{{ $users->id }}</td>
                            <td>{{ $users->fullname }}</td>
                            <td>{{ $users->role }}</td>
                            <td>{{ $users->created_at }}</td>
                            <td>{{ $users->updated_at }}</td>
                            <td>
                                <a href="{{ route('users.show', ['user' => $users->id, 'page' => request('page')]) }}">
                                    <img src="{{ asset('storage/eye.png') }}" width="20" height="20" alt="Show" style="margin-right: 10px;">
                                </a>

                                <a href="{{ route('users.edit', ['user' => $users->id, 'page' => request('page'), 'redirect_to' => url()->full()]) }}">
                                    <img src="{{ asset('storage/edit.png') }}" width="20" height="20" alt="Edit" style="margin-right: 10px;">
                                </a>
                                <form action="{{ route('users.destroy', $users->id) }}" method="POST" style="display:inline;" data-confirm-delete="True" onsubmit="return confirm('Apakah Anda Yakin?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; padding: 0;">
                                        <img src="{{ asset('storage/delete.png') }}" width="20" height="20" alt="Delete" style="margin-right: 10px;">
                                    </button>
                                </form>
                                <form id="resetPasswordForm-{{ $users->id }}" action="{{ route('user.resetPassword', $users->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="button" onclick="confirmReset({{ $users->id }})" style="background: none; border: none; padding: 0;">
                                        <img src="{{ asset('storage/reset.png') }}" width="20" height="20" alt="Reset Password">
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $user->links('vendor.pagination.custom') }}
            </div>
            @yield('konten')
        </div>
    </div>
    <div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3.5 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center p-2" style="width:2.2rem;height:2.2rem;">
                        <i class="ti ti-alert-triangle-filled text-danger fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-body mb-0" style="font-size: 1.05rem;">
                        Blacklist Staff (PHK & Ban)
                    </h5>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-circle" data-bs-dismiss="modal" aria-label="Close" title="Tutup">
                    <i class="ti ti-x fs-5"></i>
                </button>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="user_id" id="ban_user_id">
                    
                    {{-- Warning Banner --}}
                    <div class="alert alert-danger bg-danger-subtle text-danger border-danger border-opacity-25 rounded-3 p-3 mb-3.5" role="alert">
                        <div class="d-flex gap-2.5 align-items-start">
                            <i class="ti ti-shield-x fs-4 flex-shrink-0 mt-0.5 text-danger"></i>
                            <div style="font-size:0.83rem; line-height:1.45;">
                                <strong>PERINGATAN:</strong> Tindakan ini akan <strong>mematikan akun</strong> staff dan mencatat namanya ke dalam daftar hitam (blacklist) perusahaan selamanya.
                            </div>
                        </div>
                    </div>

                    {{-- Staff Info --}}
                    <div class="mb-3.5">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Nama Staff</label>
                        <input type="text" class="form-control fw-bold" id="ban_user_name" readonly style="background-color: var(--bs-tertiary-bg, #f8fafc);">
                    </div>

                    {{-- Alasan Pelanggaran --}}
                    <div class="mb-3.5">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Alasan Pelanggaran <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Terbukti mencuri aset perusahaan pada tanggal..."></textarea>
                    </div>

                    {{-- Lampiran PDF Wajib --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Dokumen Surat / SK Blacklist (Wajib PDF) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-file-text text-danger"></i></span>
                            <input type="file" name="attachment_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="form-text mt-1.5 text-muted" style="font-size:0.75rem;">Unggah berkas Surat Keputusan (SK) atau bukti pelanggaran berformat PDF (Maksimal 2MB).</div>
                    </div>
                </div>
                <div class="modal-footer bg-body-tertiary px-4 py-3 border-top">
                    <button type="button" class="btn btn-label-secondary px-3.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="ti ti-user-x me-1.5"></i> Ya, Blacklist Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBanModal(id, name) {
    // Isi data ke dalam form modal
    document.getElementById('ban_user_id').value = id;
    document.getElementById('ban_user_name').value = name;
    
    // Tampilkan Modal
    var myModal = new bootstrap.Modal(document.getElementById('banModal'));
    myModal.show();
}
</script>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        function confirmReset(userId) {
            Swal.fire({
                title: 'Reset Password?',
                text: 'Apakah Anda ingin mereset password pengguna ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('resetPasswordForm-' + userId).submit();
                }
            });
        }
    </script>


    @include('sweetalert::alert')
    <script src="{{ asset('vendor/bootstrap3/js/bootstrap.min.js') }}"></script>
</body>

</html>
