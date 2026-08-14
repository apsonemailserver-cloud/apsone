@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Edit Kontrak Kerja</h5>
        </div>
        <div class="card-body pt-4">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('users.KontrakUpdate', ['user' => $user->id]) }}" method="POST">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="page" value="{{ $page }}">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">NIP</label>
                    <input type="text" class="form-control" name="ID" value="{{ $user->id }}" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama</label>
                    <input type="text" class="form-control" name="fullname" value="{{ $user->fullname }}" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kontrak Mulai <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="contract_start" required min="{{ date('Y-m-d') }}"
                        value="{{ old('contract_start', $user->contract_start ? \Carbon\Carbon::parse($user->contract_start)->format('Y-m-d') : '') }}">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kontrak Selesai <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="contract_end" required min="{{ date('Y-m-d') }}"
                        value="{{ old('contract_end', $user->contract_end ? \Carbon\Carbon::parse($user->contract_end)->format('Y-m-d') : '') }}">
                </div>
                
                <x-form-actions :cancelHref="route('users.kontrak', ['page' => $page])" submitText="Update" />
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Script khusus untuk halaman edit kontrak
    document.addEventListener('DOMContentLoaded', function() {
        // Validasi tanggal
        const contractStart = document.querySelector('input[name="contract_start"]');
        const contractEnd = document.querySelector('input[name="contract_end"]');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        function parseContractDate(value) {
            if (!value) return null;

            const normalized = value.trim();
            const isoMatch = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            const localMatch = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

            let year;
            let month;
            let day;

            if (isoMatch) {
                year = Number(isoMatch[1]);
                month = Number(isoMatch[2]) - 1;
                day = Number(isoMatch[3]);
            } else if (localMatch) {
                day = Number(localMatch[1]);
                month = Number(localMatch[2]) - 1;
                year = Number(localMatch[3]);
            } else {
                return null;
            }

            const date = new Date(year, month, day);

            if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
                return null;
            }

            return date;
        }

        function validateContractDates(changedInput) {
            const startDate = parseContractDate(contractStart.value);
            const endDate = parseContractDate(contractEnd.value);

            if (changedInput === contractStart && startDate && startDate < today) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Tanggal mulai kontrak tidak boleh backdate (kurang dari hari ini)',
                    timer: 3000,
                    showConfirmButton: false
                });
                contractStart.value = '';
                return;
            }

            if (changedInput === contractEnd && endDate && endDate < today) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Tanggal selesai kontrak tidak boleh backdate (kurang dari hari ini)',
                    timer: 3000,
                    showConfirmButton: false
                });
                contractEnd.value = '';
                return;
            }

            if (!startDate || !endDate) return;

            if (startDate > endDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Tanggal mulai kontrak tidak boleh lebih besar dari tanggal selesai',
                    timer: 3000,
                    showConfirmButton: false
                });
                changedInput.value = '';
            }
        }

        if (contractStart && contractEnd) {
            contractStart.addEventListener('change', function() {
                validateContractDates(this);
            });

            contractEnd.addEventListener('change', function() {
                validateContractDates(this);
            });
        }

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const startDate = parseContractDate(contractStart.value);
                const endDate = parseContractDate(contractEnd.value);

                if (!contractStart.value || !contractEnd.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Data Belum Lengkap',
                        text: 'Tanggal mulai dan selesai kontrak wajib diisi',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                if (startDate && startDate < today) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Tanggal mulai kontrak tidak boleh backdate (kurang dari hari ini)',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                if (endDate && endDate < today) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Tanggal selesai kontrak tidak boleh backdate (kurang dari hari ini)',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                if (startDate && endDate && startDate > endDate) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Tanggal mulai kontrak tidak boleh lebih besar dari tanggal selesai',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                // Konfirmasi sebelum submit
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Update',
                    text: 'Apakah Anda yakin ingin memperbarui data kontrak ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4180c3',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Ya, Update!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endsection
