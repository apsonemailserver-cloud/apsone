@extends('layout.admin')

@section('styles')
<style>
    /* Enforce uniform borders, shadows, and rounded corners for all FAQ card items */
    #faqAccordion .accordion-item.card {
        border: 1px solid #d9dee3 !important;
        border-radius: 0.5rem !important;
        overflow: hidden !important;
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12) !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    /* Dark mode border and shadow adjustments */
    html.aps-dark #faqAccordion .accordion-item.card {
        border-color: #263653 !important;
        box-shadow: 0 20px 48px rgba(0, 0, 0, 0.24) !important;
    }

    /* Force all sides to have border even if Bootstrap tries to collapse it */
    #faqAccordion .accordion-item.card:not(:first-of-type) {
        border-top: 1px solid #d9dee3 !important;
    }
    html.aps-dark #faqAccordion .accordion-item.card:not(:first-of-type) {
        border-top: 1px solid #263653 !important;
    }
    
    /* Interactive hover state */
    #faqAccordion .accordion-item.card:hover {
        border-color: rgba(47, 128, 237, 0.45) !important;
        box-shadow: 0 4px 12px 0 rgba(47, 128, 237, 0.15) !important;
    }

    /* Guide Box styling for side-by-side cards */
    .guide-box {
        border-radius: 0.5rem;
        padding: 1rem;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    html.aps-dark .guide-box {
        background-color: #16233b !important;
        border-color: #243552 !important;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Bantuan /</span> FAQ
    </h4>

    <div class="card">
        <h5 class="card-header">Frequently Asked Questions</h5>
        <div class="card-body">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item card">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            Bagaimana cara mengajukan cuti?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Anda dapat mengajukan cuti melalui menu <strong>Apply Leave</strong> > <strong>Pengajuan Leave</strong> di sidebar sebelah kiri. Pastikan melengkapi dokumen pendukung jika diperlukan.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Bagaimana cara melakukan absensi?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <p class="mb-3">Buka menu <strong>Attendance</strong> &gt; <strong>Absensi Hari Ini</strong>. Klik tombol <strong>Check-In</strong> saat mulai bertugas, dan <strong>Check-Out</strong> setelah selesai.</p>

                            <div class="row g-3 mt-1">
                                {{-- Card Kiri: HP Android --}}
                                <div class="col-md-6">
                                    <div class="guide-box h-100">
                                        <h6 class="fw-bold mb-2 text-primary">
                                            <i class="bx bxl-android me-1"></i> HP Android (Google Chrome):
                                        </h6>
                                        <ol class="mb-0 ps-3 small">
                                            <li>Aktifkan GPS / Lokasi pada panel notifikasi atas HP Anda.</li>
                                            <li>Buka Pengaturan HP &gt; Aplikasi &gt; Chrome &gt; Izin (Permissions).</li>
                                            <li>Setel Lokasi &amp; Kamera ke "Izinkan saat aplikasi digunakan".</li>
                                            <li>Saat di web, tekan Izinkan (Allow) pada pop-up izin yang muncul.</li>
                                        </ol>
                                    </div>
                                </div>

                                {{-- Card Kanan: iPhone / iOS --}}
                                <div class="col-md-6">
                                    <div class="guide-box h-100">
                                        <h6 class="fw-bold mb-2 text-primary">
                                            <i class="bx bxl-apple me-1"></i> iPhone / iOS (Safari):
                                        </h6>
                                        <ol class="mb-0 ps-3 small">
                                            <li>Buka Pengaturan &gt; Privasi &amp; Keamanan &gt; Layanan Lokasi &gt; Pastikan ON.</li>
                                            <li>Buka Pengaturan &gt; Safari &gt; opsi Kamera &amp; Lokasi pilih Izinkan (Allow).</li>
                                            <li>Tekan Izinkan (Allow) ketika Safari meminta konfirmasi lokasi &amp; kamera.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Bagaimana jika saya lupa password?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Anda dapat menggunakan fitur <strong>Lupa Password</strong> di halaman login. Sistem akan mengirimkan kode <strong>OTP (One-Time Password)</strong> ke email Anda yang terdaftar. Masukkan kode OTP tersebut pada aplikasi untuk melakukan verifikasi dan mengatur ulang password Anda.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Bagaimana cara menambahkan lembur?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Buka menu <strong>Attendance & Lembur</strong> > <strong>Lembur Saya</strong>. Kemudian klik tombol <strong>Tambah Lembur</strong> dan isi detail jam lembur serta alasannya.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            Apa ketentuan untuk mengganti foto profil?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Untuk mengganti foto profil, buka halaman <strong>Profile</strong>. Harap diingat bahwa Anda <strong>wajib menggunakan foto formal</strong> yang jelas untuk keperluan identitas di sistem.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                            (Admin) Bagaimana cara menambahkan schedule?
                        </button>
                    </h2>
                    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Buka menu <strong>Schedule</strong> > <strong>Create / Update</strong>. Anda bisa memilih tanggal, pengguna, shift, dan station untuk menjadwalkan staf tersebut.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingSeven">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                            (Admin) Bagaimana cara menambahkan data shift?
                        </button>
                    </h2>
                    <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Buka menu utama <strong>Shift</strong>, lalu klik tombol tambah. Isi nama shift beserta rentang jam mulai (start) dan jam selesai (end).
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item card mt-2">
                    <h2 class="accordion-header" id="headingEight">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                            (Admin) Bagaimana cara menginput Latitude dan Longitude saat menambah Station?
                        </button>
                    </h2>
                    <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Buka menu <strong>Manajemen Station</strong> > <strong>Tambah Station</strong>. Untuk mendapatkan titik kordinat (Latitude & Longitude), buka Google Maps, klik kanan pada lokasi spesifik, lalu copy titik koordinat desimalnya (contoh Latitude: <code>-6.123456</code>, Longitude: <code>106.123456</code>). Paste pada kolom yang disediakan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
