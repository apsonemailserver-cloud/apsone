@extends('layout.admin')

@section('title', 'Edit PAS Tahunan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Card Form Edit --}}
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="mb-0 fw-bold">Edit PAS Tahunan: {{ $user->fullname }}</h5>
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

                        <form action="{{ route('users.PASUpdate', ['user' => $user->id]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control" name="ID" value="{{ $user->id }}" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="fullname" value="{{ $user->fullname }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Station</label>
                                <input type="text" class="form-control" name="station" value="{{ $user->station }}" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">PAS Terdaftar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="pas_registered" required min="{{ date('Y-m-d') }}" value="{{ old('pas_registered', $user->pas_registered ? \Carbon\Carbon::parse($user->pas_registered)->format('Y-m-d') : '') }}">
                                <small class="text-muted d-block">Tanggal ketika PAS pertama kali terdaftar</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">PAS Berakhir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="pas_expired" required min="{{ date('Y-m-d') }}" value="{{ old('pas_expired', $user->pas_expired ? \Carbon\Carbon::parse($user->pas_expired)->format('Y-m-d') : '') }}">
                                <small class="text-muted d-block">Tanggal berakhirnya masa berlaku PAS</small>
                            </div>

                            {{-- Status Info --}}
                            @php
                                $now = strtotime(date('Y-m-d'));
                                $expired = strtotime($user->pas_expired);
                                $diff = $expired - $now;
                                $months = floor($diff / (30 * 60 * 60 * 24));
                                
                                $statusClass = '';
                                $statusText = '';
                                
                                if ($months <= 2 && $months >= 0) {
                                    $statusClass = 'warning';
                                    $statusText = 'Akan Habis';
                                } elseif ($months < 0) {
                                    $statusClass = 'danger';
                                    $statusText = 'Kadaluarsa';
                                } else {
                                    $statusClass = 'success';
                                    $statusText = 'Aktif';
                                }
                            @endphp
                            
                            <div class="alert alert-{{ $statusClass }} mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="bx 
                                        @if($statusClass == 'success') bx-check-circle 
                                        @elseif($statusClass == 'warning') bx-time-five 
                                        @else bx-x-circle 
                                        @endif 
                                        me-2"></i>
                                    <div>
                                        <strong>Status PAS: {{ $statusText }}</strong>
                                        @if ($months <= 2 && $months >= 0)
                                            <div class="small">Sisa waktu: {{ $months }} bulan</div>
                                        @elseif ($months < 0)
                                            <div class="small">PAS telah kadaluarsa sejak {{ abs($months) }} bulan yang lalu</div>
                                        @else
                                            <div class="small">PAS masih berlaku untuk {{ $months }} bulan ke depan</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <x-form-actions :cancelHref="route('users.pas')" submitText="Update" />
                        </form>
                    </div>
                </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validasi tanggal
        const pasRegistered = document.querySelector('input[name="pas_registered"]');
        const pasExpired = document.querySelector('input[name="pas_expired"]');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        function parseDate(value) {
            if (!value) return null;
            const parts = value.split('-');
            if (parts.length === 3) {
                return new Date(parts[0], parts[1] - 1, parts[2]);
            }
            return new Date(value);
        }
        
        if (pasRegistered && pasExpired) {
            pasRegistered.addEventListener('change', function() {
                const regDate = parseDate(this.value);
                if (regDate && regDate < today) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tanggal PAS terdaftar tidak boleh backdate (kurang dari hari ini)',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    this.value = '';
                    return;
                }
                if (pasExpired.value && this.value > pasExpired.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tanggal terdaftar tidak boleh lebih besar dari tanggal berakhir',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    this.value = '';
                }
            });
            
            pasExpired.addEventListener('change', function() {
                const expDate = parseDate(this.value);
                if (expDate && expDate < today) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tanggal PAS berakhir tidak boleh backdate (kurang dari hari ini)',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    this.value = '';
                    return;
                }
                if (pasRegistered.value && this.value < pasRegistered.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tanggal berakhir tidak boleh lebih kecil dari tanggal terdaftar',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    this.value = '';
                }
            });
        }

        // SweetAlert untuk konfirmasi
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const pasRegisteredValue = pasRegistered.value;
            const pasExpiredValue = pasExpired.value;
            
            if (!pasRegisteredValue || !pasExpiredValue) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Belum Lengkap',
                    text: 'Harap isi kedua tanggal (PAS Terdaftar dan PAS Berakhir)',
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }

            const regDate = parseDate(pasRegisteredValue);
            if (regDate && regDate < today) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Tanggal Tidak Valid',
                    text: 'Tanggal PAS terdaftar tidak boleh backdate (kurang dari hari ini)',
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }
            
            if (pasRegisteredValue > pasExpiredValue) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Tanggal Tidak Valid',
                    text: 'Tanggal terdaftar tidak boleh lebih besar dari tanggal berakhir',
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }
            
            // Konfirmasi sebelum submit
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Update',
                text: 'Apakah Anda yakin ingin memperbarui data PAS ini?',
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

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.classList.contains('alert-important')) {
                    const bootstrapAlert = new bootstrap.Alert(alert);
                    bootstrapAlert.close();
                }
            });
        }, 5000);
    });
</script>
@endsection