@php
    $isCheckOut = $type === 'out';
    $actionTitle = $isCheckOut ? 'Presensi Out' : 'Presensi In';
    $actionSub = $isCheckOut ? 'Akhiri shift dengan verifikasi wajah dan GPS.' : 'Mulai shift dengan verifikasi wajah dan GPS.';
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $actionTitle }} - Attendance</title>
    <link rel="icon" href="{{ asset('storage/aps_mini.png') }}" sizes="48x48" type="image/png">
    <link href="{{ asset('template/assets/vendor/fonts/boxicons.css') }}" rel="stylesheet">
    <style>
        :root {
            --cam-blue: #2f80ed;
            --cam-blue-dark: #2368c8;
            --cam-green: #22c55e;
            --cam-green-glow: rgba(34, 197, 94, 0.4);
            --cam-bg: #f9fafb;
            --cam-surface: #ffffff;
            --cam-card: rgba(255, 255, 255, 0.88);
            --cam-text: #1f2937;
            --cam-muted: #7b8aa0;
            --cam-border: #e6edf5;
            --cam-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            background: var(--cam-bg);
            color: var(--cam-text);
            font-family: Inter, "Public Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow: hidden;
        }

        body.aps-camera-dark {
            --cam-bg: #0b1220;
            --cam-surface: #111c31;
            --cam-card: rgba(17, 28, 49, 0.82);
            --cam-text: #eaf1fb;
            --cam-muted: #94a3b8;
            --cam-border: #24324a;
            --cam-shadow: 0 26px 80px rgba(0, 0, 0, 0.32);
        }

        .camera-page {
            position: relative;
            width: 100vw;
            height: 100dvh;
            min-height: 560px;
            padding: clamp(0.75rem, 2vw, 1.25rem);
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            gap: 0.85rem;
            background:
                radial-gradient(circle at 12% 8%, rgba(47, 128, 237, 0.12), transparent 27%),
                radial-gradient(circle at 90% 92%, rgba(92, 199, 178, 0.12), transparent 25%),
                var(--cam-bg);
        }

        .camera-topbar,
        .camera-bottom-card {
            z-index: 5;
            border: 1px solid var(--cam-border);
            border-radius: 999px;
            background: var(--cam-card);
            box-shadow: var(--cam-shadow);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .camera-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0.85rem;
        }

        .camera-back {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--cam-text);
            background: var(--cam-surface);
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease;
        }

        .camera-back:hover {
            transform: scale(1.05);
        }

        .camera-back i {
            font-size: 1.35rem;
        }

        .camera-title {
            min-width: 0;
            flex: 1;
        }

        .camera-title small {
            display: block;
            color: var(--cam-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .camera-title strong {
            display: block;
            color: var(--cam-text);
            font-size: clamp(0.96rem, 2vw, 1.08rem);
            font-weight: 780;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .camera-topbar-pills {
            display: flex;
            gap: 0.45rem;
            align-items: center;
            min-width: 0;
            flex-shrink: 0;
            margin-left: auto;
        }

        .camera-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            height: 38px;
            padding: 0 0.85rem;
            border-radius: 999px;
            background: rgba(47, 128, 237, 0.11);
            color: var(--cam-blue);
            font-size: 0.78rem;
            font-weight: 750;
            white-space: nowrap;
            transition: all 0.25s ease;
        }

        .camera-status-pill.is-success {
            background: rgba(34, 197, 94, 0.15) !important;
            color: #16a34a !important;
        }

        .camera-status-pill.is-warning {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #dc2626 !important;
        }

        .camera-stage {
            position: relative;
            min-height: 0;
            border: 1px solid var(--cam-border);
            border-radius: 32px;
            overflow: hidden;
            background: #030712;
            box-shadow: var(--cam-shadow);
            isolation: isolate;
        }

        #video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            background: #030712;
        }

        .camera-stage::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(to bottom, rgba(2, 6, 23, 0.45), transparent 22%, transparent 72%, rgba(2, 6, 23, 0.65));
            z-index: 1;
        }

        /* Single Unified Biometric Frame (Clean & Non-distracting) */
        .biometric-scanner-frame {
            position: absolute;
            z-index: 2;
            left: 50%;
            top: 48%;
            transform: translate(-50%, -50%);
            width: min(70vw, 280px);
            aspect-ratio: 0.78;
            border-radius: 46% 46% 42% 42%;
            border: 2.5px solid rgba(255, 255, 255, 0.45);
            box-shadow:
                0 0 0 9999px rgba(3, 7, 18, 0.20),
                0 0 28px rgba(47, 128, 237, 0.18);
            pointer-events: none;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .biometric-scanner-frame.is-detected {
            border-color: var(--cam-green) !important;
            box-shadow:
                0 0 0 9999px rgba(3, 7, 18, 0.20),
                0 0 42px var(--cam-green-glow) !important;
        }

        .biometric-scanner-frame.is-outside {
            border-color: rgba(245, 158, 11, 0.75) !important;
            box-shadow:
                0 0 0 9999px rgba(3, 7, 18, 0.20),
                0 0 25px rgba(245, 158, 11, 0.35) !important;
        }

        .camera-hint {
            position: absolute;
            z-index: 4;
            left: 50%;
            bottom: 1.1rem;
            transform: translateX(-50%);
            width: min(520px, calc(100% - 2rem));
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 0.72rem 1rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 650;
            text-align: center;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: background 0.25s ease, border-color 0.25s ease, color 0.25s ease;
        }

        .camera-hint.is-success {
            background: rgba(22, 163, 74, 0.88) !important;
            border-color: rgba(34, 197, 94, 0.4) !important;
            color: #ffffff !important;
        }

        .camera-hint.is-warning {
            border-color: rgba(239, 68, 68, 0.4) !important;
            color: #ffffff !important;
        }

        /* Gojek-style Enrollment Wizard Overlay */
        .enrollment-wizard {
            position: absolute;
            z-index: 5;
            left: 50%;
            bottom: 1.1rem;
            transform: translateX(-50%);
            width: min(480px, calc(100% - 1.5rem));
            padding: 1.1rem 1.2rem;
            border-radius: 24px;
            background: rgba(11, 18, 32, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            color: #ffffff;
            text-align: center;
            transition: all 0.3s ease;
        }

        .wizard-step-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.28rem 0.75rem;
            border-radius: 999px;
            background: rgba(47, 128, 237, 0.2);
            color: #8fc2ff;
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .wizard-title {
            margin: 0 0 0.25rem 0;
            font-size: 1.05rem;
            font-weight: 780;
            color: #ffffff;
        }

        .wizard-sub {
            margin: 0 0 0.85rem 0;
            font-size: 0.8rem;
            color: #94a3b8;
            line-height: 1.45;
        }

        .wizard-poses {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }

        .pose-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 0.55rem 0.35rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 650;
            transition: all 0.25s ease;
        }

        .pose-pill i {
            font-size: 1.25rem;
        }

        .pose-pill.active {
            background: rgba(47, 128, 237, 0.22);
            border-color: rgba(47, 128, 237, 0.6);
            color: #ffffff;
            box-shadow: 0 0 16px rgba(47, 128, 237, 0.3);
        }

        .pose-pill.completed {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.5);
            color: #4ade80;
        }

        .wizard-btn {
            width: 100%;
            height: 46px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #2f80ed, #1f64c8);
            color: #ffffff;
            font-size: 0.88rem;
            font-weight: 750;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(47, 128, 237, 0.3);
            transition: all 0.2s ease;
        }

        .wizard-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(47, 128, 237, 0.4);
        }

        .wizard-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .camera-loader {
            position: absolute;
            inset: 0;
            z-index: 6;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background:
                radial-gradient(circle at 50% 38%, rgba(47, 128, 237, 0.16), transparent 28%),
                #030712;
            color: #eaf1fb;
            text-align: center;
            transition: opacity 0.24s ease, visibility 0.24s ease;
        }

        .camera-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .loader-card {
            width: min(380px, 100%);
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 26px;
            background: rgba(17, 28, 49, 0.85);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.34);
        }

        .loader-icon {
            width: 64px;
            height: 64px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            border-radius: 999px;
            background: rgba(47, 128, 237, 0.18);
            color: #8fc2ff;
            font-size: 1.9rem;
        }

        .loader-card strong {
            display: block;
            font-size: 1.08rem;
            font-weight: 780;
        }

        .loader-card span {
            display: block;
            margin-top: 0.35rem;
            color: #94a3b8;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .camera-bottom-card {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 0.9rem;
            padding: 0.62rem 0.85rem;
            border-radius: 28px;
        }

        .camera-meta {
            min-width: 0;
            padding-left: 0.45rem;
        }

        .camera-meta strong {
            display: block;
            color: var(--cam-text);
            font-size: 0.95rem;
            font-weight: 780;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .camera-meta span {
            display: block;
            color: var(--cam-muted);
            font-size: 0.78rem;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .camera-submit {
            min-width: 154px;
            height: 52px;
            border: 0;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.52rem;
            background: linear-gradient(135deg, var(--cam-blue), var(--cam-blue-dark));
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 780;
            box-shadow: 0 14px 30px rgba(47, 128, 237, 0.28);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .camera-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(47, 128, 237, 0.35);
        }

        .camera-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .d-none {
            display: none !important;
        }

        /* SweetAlert Custom Theme Integration */
        .swal2-container {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .swal2-popup {
            background-color: var(--cam-surface) !important;
            color: var(--cam-text) !important;
            border: 1px solid var(--cam-border) !important;
            border-radius: 28px !important;
            font-family: inherit !important;
            box-shadow: var(--cam-shadow) !important;
        }

        .swal2-title {
            color: var(--cam-text) !important;
            font-weight: 780 !important;
        }

        .swal2-html-container {
            color: var(--cam-muted) !important;
            font-size: 0.88rem !important;
            line-height: 1.55 !important;
        }

        .swal2-styled.swal2-confirm {
            background: linear-gradient(135deg, var(--cam-blue), var(--cam-blue-dark)) !important;
            border-radius: 16px !important;
            font-weight: 750 !important;
            font-size: 0.9rem !important;
            padding: 0.65rem 1.8rem !important;
            box-shadow: 0 10px 24px rgba(47, 128, 237, 0.22) !important;
            border: 0 !important;
        }

        .swal2-loader {
            border-color: var(--cam-blue) transparent var(--cam-blue) transparent !important;
        }

        @media (max-width: 767.98px) {
            html,
            body {
                overflow: hidden;
                touch-action: manipulation;
            }

            .camera-page {
                padding: 0.5rem;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: 0.5rem;
            }

            .camera-topbar {
                border-radius: 20px;
                padding: 0.4rem 0.55rem;
                gap: 0.35rem;
            }

            .camera-back {
                width: 36px;
                height: 36px;
                min-width: 36px;
            }

            .camera-back i {
                font-size: 1.1rem;
            }

            .camera-title {
                max-width: clamp(100px, 32vw, 180px);
            }

            .camera-title small {
                display: none;
            }

            .camera-title strong {
                font-size: 0.82rem;
            }

            .camera-topbar-pills {
                gap: 0.25rem;
            }

            .camera-status-pill {
                height: 28px;
                padding: 0 0.45rem;
                font-size: 0.68rem;
                gap: 0.22rem;
                max-width: clamp(90px, 24vw, 130px);
            }

            .camera-status-pill span {
                max-width: clamp(70px, 18vw, 100px);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .camera-stage {
                border-radius: 22px;
            }

            .biometric-scanner-frame {
                width: min(78vw, 240px);
            }

            .camera-hint {
                bottom: 0.6rem;
                width: calc(100% - 1rem);
                padding: 0.45rem 0.65rem;
                border-radius: 16px;
                font-size: clamp(0.68rem, 2.6vw, 0.78rem);
            }

            .camera-bottom-card {
                grid-template-columns: 1fr;
                border-radius: 20px;
                padding: 0.55rem 0.75rem;
                gap: 0.4rem;
            }

            .camera-meta {
                text-align: center;
                padding: 0;
            }

            .camera-meta strong {
                font-size: 0.85rem;
            }

            .camera-meta span {
                font-size: 0.72rem;
            }

            .camera-submit {
                width: 100%;
                min-width: 0;
                height: 44px;
                border-radius: 14px;
                font-size: 0.86rem;
            }
        }
    </style>
</head>

<body>
    <script>
        (function() {
            document.body.classList.toggle('aps-camera-dark', (localStorage.getItem('apsTheme') || 'light') === 'dark');
        })();
    </script>

    <main class="camera-page">
        <header class="camera-topbar">
            <a href="{{ route('attendance.index') }}" class="camera-back" aria-label="Kembali">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="camera-title">
                <small id="cameraTitleSmall">Attendance Verification</small>
                <strong id="cameraTitleStrong">{{ $actionTitle }} - {{ $user->fullname ?? 'Staff APS' }}</strong>
            </div>
            <div class="camera-topbar-pills">
                <div class="camera-status-pill" id="cameraStatus">
                    <i class="bx bx-camera"></i>
                    <span>Kamera...</span>
                </div>
                <div class="camera-status-pill" id="gpsStatusPill" title="Klik untuk segarkan GPS">
                    <i class="bx bx-target-lock"></i>
                    <span id="gpsStatusText">GPS...</span>
                </div>
            </div>
        </header>

        <section class="camera-stage" id="cameraStage" aria-label="Preview kamera">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas" class="d-none"></canvas>
            
            <!-- Single Clean Biometric Framing Guide (Face-ID style) -->
            <div class="biometric-scanner-frame" id="biometricFrame" aria-hidden="true"></div>

            <div class="camera-hint" id="cameraHint">
                <i class="bx bx-face"></i>
                <span id="hintText">Posisikan wajah Anda di tengah area kamera.</span>
            </div>

            <!-- Gojek Style Interactive Face Enrollment Wizard -->
            <div class="enrollment-wizard d-none" id="enrollmentWizard">
                <div class="wizard-step-badge">
                    <i class="bx bx-shield-quarter"></i>
                    <span id="wizardStepBadge">Registrasi Wajah NIP</span>
                </div>
                <h4 class="wizard-title" id="wizardTitle">1. Tatap Lurus ke Depan</h4>
                <p class="wizard-sub" id="wizardSub">Posisikan wajah Anda tepat di tengah frame untuk mendaftarkan foto referensi NIP Anda.</p>
                
                <div class="wizard-poses">
                    <div class="pose-pill active" id="poseFront">
                        <i class="bx bx-face"></i>
                        <span>1. Depan</span>
                    </div>
                    <div class="pose-pill" id="poseRight">
                        <i class="bx bx-right-arrow-circle"></i>
                        <span>2. Kanan</span>
                    </div>
                    <div class="pose-pill" id="poseLeft">
                        <i class="bx bx-left-arrow-circle"></i>
                        <span>3. Kiri</span>
                    </div>
                </div>

                <button type="button" class="wizard-btn" id="btnWizardAction">
                    <i class="bx bx-camera"></i>
                    <span id="btnWizardActionText">Ambil Foto Wajah Depan</span>
                </button>
            </div>

            <div class="camera-loader" id="cameraLoader">
                <div class="loader-card">
                    <div class="loader-icon"><i class="bx bx-camera"></i></div>
                    <strong id="loaderTitle">Membuka kamera</strong>
                    <span id="loaderText">Izinkan akses kamera agar sistem dapat mengambil foto verifikasi.</span>
                </div>
            </div>
        </section>

        <form id="attendanceForm" method="POST" action="{{ route('attendance.process') }}" class="camera-bottom-card">
            @csrf
            <input type="hidden" name="photo" id="photoInput">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="camera-meta">
                <strong id="cameraMetaTitle">{{ $actionTitle }} Sekarang</strong>
                <span id="gpsInfoText">
                    <i class="bx bx-map-pin"></i> Mengunci lokasi GPS...
                </span>
            </div>
            <button type="submit" id="btnSubmit" class="camera-submit" disabled>
                <i class="bx {{ $isCheckOut ? 'bx-log-out' : 'bx-log-in' }}"></i>
                <span id="btnSubmitText">{{ $actionTitle }}</span>
            </button>
        </form>
    </main>

    <script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Absensi',
                text: "{{ session('error') }}",
                confirmButtonColor: '#2f80ed'
            });
        </script>
    @endif

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: "{{ session('success') }}",
                confirmButtonColor: '#2f80ed'
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const cameraStage = document.getElementById('cameraStage');
            const biometricFrame = document.getElementById('biometricFrame');
            const btnSubmit = document.getElementById('btnSubmit');
            const photoInput = document.getElementById('photoInput');
            const loader = document.getElementById('cameraLoader');
            const loaderTitle = document.getElementById('loaderTitle');
            const loaderText = document.getElementById('loaderText');
            const cameraStatus = document.getElementById('cameraStatus');
            const gpsStatusPill = document.getElementById('gpsStatusPill');
            const gpsStatusText = document.getElementById('gpsStatusText');
            const gpsInfoText = document.getElementById('gpsInfoText');
            const cameraHint = document.getElementById('cameraHint');
            const hintText = document.getElementById('hintText');
            const hintIcon = cameraHint ? cameraHint.querySelector('i') : null;

            let isFaceDetected = false;
            let isFaceApiLoaded = false;
            let nativeDetector = null;
            let cachedPosition = null;
            let gpsWatchId = null;

            const hasFaceSamples = @json($hasFaceSamples ?? false);
            const isStrictMode = @json($strictMode ?? config('attendance.face_recognition_strict', true));
            let userFaceRegistered = !isStrictMode || hasFaceSamples;
            let refDescriptors = [];
            let capturedPoses = { front: null, right: null, left: null };
            let wizardStep = 'front';
            let holdTimer = null;
            let lastFaceBox = null;
            let isDetecting = false;

            const CSRF = '{{ csrf_token() }}';
            const faceVerifyUrl = '{{ route("attendance.face-verify") }}';

            const btnWizardAction = document.getElementById('btnWizardAction');
            const btnWizardActionText = document.getElementById('btnWizardActionText');
            const wizardTitle = document.getElementById('wizardTitle');
            const wizardSub = document.getElementById('wizardSub');
            const poseFront = document.getElementById('poseFront');
            const poseRight = document.getElementById('poseRight');
            const poseLeft = document.getElementById('poseLeft');
            const enrollmentWizard = document.getElementById('enrollmentWizard');

            // --- GPS Management (Non-blocking background lookup) ---
            if (gpsStatusPill) gpsStatusPill.addEventListener('click', requestFreshGpsLocation);
            if (gpsInfoText) gpsInfoText.addEventListener('click', requestFreshGpsLocation);

            function updateGpsUI(position) {
                cachedPosition = position;
                const accuracy = Math.round(position.coords.accuracy);

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                if (latInput) latInput.value = position.coords.latitude;
                if (lngInput) lngInput.value = position.coords.longitude;

                if (gpsStatusText) {
                    gpsStatusText.innerHTML = `GPS ${accuracy}m`;
                    if (accuracy <= 25) {
                        if (gpsStatusPill) gpsStatusPill.className = 'camera-status-pill is-success';
                    } else if (accuracy <= 60) {
                        if (gpsStatusPill) gpsStatusPill.className = 'camera-status-pill';
                    } else {
                        if (gpsStatusPill) gpsStatusPill.className = 'camera-status-pill is-warning';
                    }
                }

                if (gpsInfoText) {
                    gpsInfoText.innerHTML = `<i class="bx bx-map-pin"></i> Akurasi GPS: <b>${accuracy} meter</b>`;
                }
            }

            function requestFreshGpsLocation() {
                if (gpsStatusText) gpsStatusText.innerHTML = 'Memuat...';
                if (gpsInfoText) gpsInfoText.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Memperbarui lokasi GPS...';

                navigator.geolocation.getCurrentPosition(
                    (pos) => updateGpsUI(pos),
                    (err) => {
                        navigator.geolocation.getCurrentPosition(
                            (pos2) => updateGpsUI(pos2),
                            (err2) => console.warn('GPS refresh error:', err2),
                            { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 }
                        );
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => updateGpsUI(pos),
                    (err) => {
                        navigator.geolocation.getCurrentPosition(
                            (pos2) => updateGpsUI(pos2),
                            (err2) => console.warn('GPS init error:', err2),
                            { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
                        );
                    },
                    { enableHighAccuracy: false, timeout: 5000, maximumAge: 300000 }
                );

                gpsWatchId = navigator.geolocation.watchPosition(
                    (pos) => updateGpsUI(pos),
                    (err) => console.warn('Watch GPS error:', err),
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 5000 }
                );
            }

            const setStatus = (icon, text, type = '') => {
                if (cameraStatus) {
                    cameraStatus.className = 'camera-status-pill ' + type;
                    cameraStatus.innerHTML = `<i class="bx ${icon}"></i><span>${text}</span>`;
                }
            };

            const setErrorState = (title, text) => {
                if (loader) loader.classList.remove('is-hidden');
                if (loaderTitle) loaderTitle.textContent = title;
                if (loaderText) loaderText.textContent = text;
                setStatus('bx-error-circle', 'Kamera belum siap', 'is-warning');
                btnSubmit.disabled = true;
            };

            const setContextMode = (isRegistration) => {
                const cameraTitleSmall = document.getElementById('cameraTitleSmall');
                const cameraTitleStrong = document.getElementById('cameraTitleStrong');
                const cameraMetaTitle = document.getElementById('cameraMetaTitle');
                const btnSubmitText = document.getElementById('btnSubmitText');

                if (isRegistration) {
                    if (cameraTitleSmall) cameraTitleSmall.textContent = 'FACE ID REGISTRATION';
                    if (cameraTitleStrong) cameraTitleStrong.textContent = 'Pendaftaran Wajah NIP - {{ $user->fullname ?? "Staff APS" }}';
                    if (cameraMetaTitle) cameraMetaTitle.textContent = 'Registrasi Wajah NIP';
                    if (btnSubmitText) btnSubmitText.textContent = 'Registrasi Wajah Diperlukan';
                    btnSubmit.disabled = true;

                    if (enrollmentWizard) enrollmentWizard.classList.remove('d-none');
                    if (cameraHint) cameraHint.classList.add('d-none');
                } else {
                    if (cameraTitleSmall) cameraTitleSmall.textContent = 'Attendance Verification';
                    if (cameraTitleStrong) cameraTitleStrong.textContent = '{{ $actionTitle }} - {{ $user->fullname ?? "Staff APS" }}';
                    if (cameraMetaTitle) cameraMetaTitle.textContent = '{{ $actionTitle }} Sekarang';
                    if (btnSubmitText) btnSubmitText.textContent = '{{ $actionTitle }}';

                    if (enrollmentWizard) enrollmentWizard.classList.add('d-none');
                    if (cameraHint) cameraHint.classList.remove('d-none');
                }
            };

            setContextMode(!userFaceRegistered);

            // --- Native FaceDetector & FaceAPI AI Init ---
            if ('FaceDetector' in window) {
                try {
                    nativeDetector = new FaceDetector({ fastMode: true, maxFaces: 1 });
                } catch(e) {
                    nativeDetector = null;
                }
            }

            async function initFaceApi() {
                if (typeof faceapi !== 'undefined') {
                    try {
                        const MODEL_URL = '{{ asset("vendor/face-api/models") }}';
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL)
                        ]);
                        isFaceApiLoaded = true;

                        // Async prefetch descriptors in background without blocking UI
                        if (userFaceRegistered) {
                            fetch('{{ route("attendance.face-samples.api") }}')
                                .then(r => r.json())
                                .then(data => {
                                    if (data && data.descriptors && Array.isArray(data.descriptors)) {
                                        refDescriptors = data.descriptors.map(arr => new Float32Array(arr));
                                    }
                                })
                                .catch(e => console.debug('Descriptor cache fetch skip:', e));
                        }
                    } catch(err) {
                        console.warn('FaceAPI load warning:', err);
                    }
                }
            }
            initFaceApi();

            // --- Real-time Face Pose Checker (For Registration Wizard) ---
            async function detectFacePoseInVideo(expectedPose) {
                if (!video.videoWidth || !video.videoHeight || video.paused || video.ended) {
                    return { valid: false, reason: 'Kamera belum siap' };
                }

                if (typeof faceapi === 'undefined' || !isFaceApiLoaded) {
                    return { valid: true, reason: 'OK' };
                }

                try {
                    const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.35 })).withFaceLandmarks(true);
                    if (!det) {
                        return { valid: false, reason: 'Wajah Tidak Terdeteksi' };
                    }

                    const landmarks = det.landmarks;
                    const leftEyePts = landmarks.getLeftEye();
                    const rightEyePts = landmarks.getRightEye();
                    const nosePts = landmarks.getNose();

                    if (!leftEyePts.length || !rightEyePts.length || !nosePts.length) {
                        return { valid: false, reason: 'Posisikan Wajah di Frame' };
                    }

                    const leftEyeX = leftEyePts.reduce((sum, p) => sum + p.x, 0) / leftEyePts.length;
                    const leftEyeY = leftEyePts.reduce((sum, p) => sum + p.y, 0) / leftEyePts.length;
                    const rightEyeX = rightEyePts.reduce((sum, p) => sum + p.x, 0) / rightEyePts.length;
                    const rightEyeY = rightEyePts.reduce((sum, p) => sum + p.y, 0) / rightEyePts.length;
                    const noseX = (nosePts[3] || nosePts[0] || {x: 0}).x;

                    const dx = Math.abs(leftEyeX - rightEyeX);
                    const dy = Math.abs(leftEyeY - rightEyeY);
                    const tiltRatio = dx > 0 ? (dy / dx) : 0;

                    if (tiltRatio > 0.40) {
                        return { valid: false, reason: 'Tegakkan Kepala' };
                    }

                    const eyeCenter = (rightEyeX + leftEyeX) / 2;
                    const noseOffset = dx > 0 ? ((noseX - eyeCenter) / dx) : 0;

                    if (expectedPose === 'front') {
                        if (Math.abs(noseOffset) > 0.35) return { valid: false, reason: 'Tatap Lurus ke Depan' };
                        return { valid: true, reason: 'Pose Depan Pas!' };
                    } else if (expectedPose === 'right') {
                        if (noseOffset > -0.10) return { valid: false, reason: 'Tengokkan Wajah ke Kanan (~30°)' };
                        return { valid: true, reason: 'Pose Kanan Pas!' };
                    } else if (expectedPose === 'left') {
                        if (noseOffset < 0.10) return { valid: false, reason: 'Tengokkan Wajah ke Kiri (~30°)' };
                        return { valid: true, reason: 'Pose Kiri Pas!' };
                    }

                    return { valid: true, reason: 'OK' };
                } catch(e) {
                    return { valid: true, reason: 'OK' };
                }
            }

            if (btnWizardAction) {
                btnWizardAction.addEventListener('click', async function() {
                    const poseCheck = await detectFacePoseInVideo(wizardStep);
                    if (!poseCheck.valid) {
                        if (wizardSub) {
                            wizardSub.style.color = '#f87171';
                            wizardSub.textContent = '⚠️ ' + poseCheck.reason + '. Posisikan wajah Anda di tengah oval.';
                        }
                        return;
                    }

                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = 640;
                    tempCanvas.height = 480;
                    const tempCtx = tempCanvas.getContext('2d');
                    
                    // Maintain aspect ratio with center-crop to prevent face stretching (lonjong)
                    const vw = video.videoWidth || 640;
                    const vh = video.videoHeight || 480;
                    const targetRatio = 640 / 480;
                    let sx = 0, sy = 0, sw = vw, sh = vh;
                    if (vw / vh > targetRatio) {
                        sw = vh * targetRatio;
                        sx = (vw - sw) / 2;
                    } else {
                        sh = vw / targetRatio;
                        sy = (vh - sh) / 2;
                    }
                    tempCtx.drawImage(video, sx, sy, sw, sh, 0, 0, 640, 480);
                    // 1.0 = Tanpa kompresi (kualitas asli maksimal untuk akurasi face recognition)
                    const b64 = tempCanvas.toDataURL('image/jpeg', 1.0);

                    if (wizardStep === 'front') {
                        capturedPoses.front = b64;
                        if (poseFront) {
                            poseFront.className = 'pose-pill completed';
                            poseFront.innerHTML = '<i class="bx bx-check-circle"></i><span>1. Depan ✓</span>';
                        }
                        if (poseRight) poseRight.className = 'pose-pill active';
                        if (wizardTitle) wizardTitle.textContent = '2. Tengok Perlahan ke Kanan (~30°)';
                        if (wizardSub) wizardSub.textContent = 'Putar posisi wajah Anda sedikit ke kanan untuk foto referensi sudut kanan.';
                        if (btnWizardActionText) btnWizardActionText.textContent = 'Ambil Foto Tengok Kanan';
                        wizardStep = 'right';
                    } else if (wizardStep === 'right') {
                        capturedPoses.right = b64;
                        if (poseRight) {
                            poseRight.className = 'pose-pill completed';
                            poseRight.innerHTML = '<i class="bx bx-check-circle"></i><span>2. Kanan ✓</span>';
                        }
                        if (poseLeft) poseLeft.className = 'pose-pill active';
                        if (wizardTitle) wizardTitle.textContent = '3. Tengok Perlahan ke Kiri (~30°)';
                        if (wizardSub) wizardSub.textContent = 'Putar posisi wajah Anda sedikit ke kiri untuk foto referensi sudut kiri.';
                        if (btnWizardActionText) btnWizardActionText.textContent = 'Ambil Foto Tengok Kiri';
                        wizardStep = 'left';
                    } else if (wizardStep === 'left') {
                        capturedPoses.left = b64;
                        if (poseLeft) {
                            poseLeft.className = 'pose-pill completed';
                            poseLeft.innerHTML = '<i class="bx bx-check-circle"></i><span>3. Kiri ✓</span>';
                        }

                        btnWizardAction.disabled = true;
                        if (btnWizardActionText) btnWizardActionText.textContent = 'Mendaftarkan Foto NIP...';

                        fetch('{{ route("attendance.face-samples.save-self") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify(capturedPoses)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                userFaceRegistered = true;
                                setContextMode(false);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Registrasi Wajah Berhasil!',
                                    text: 'Foto referensi 3 pose wajah NIP Anda telah disimpan. Sekarang Anda dapat melakukan presensi.',
                                    confirmButtonColor: '#2f80ed'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Pendaftaran',
                                    text: data.message || 'Terjadi kesalahan saat menyimpan foto referensi.',
                                    confirmButtonColor: '#2f80ed'
                                });
                                btnWizardAction.disabled = false;
                                if (btnWizardActionText) btnWizardActionText.textContent = 'Coba Lagi';
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Pendaftaran',
                                text: 'Koneksi terputus. Silakan coba kembali.',
                                confirmButtonColor: '#2f80ed'
                            });
                            btnWizardAction.disabled = false;
                            if (btnWizardActionText) btnWizardActionText.textContent = 'Coba Lagi';
                        });
                    }
                });
            }

            // --- Lightweight, Local-Only Face Detector (ZERO Network Lag) ---
            async function detectLiveFace() {
                if (!video.videoWidth || !video.videoHeight || video.paused || video.ended) {
                    return null;
                }

                // 1. Try Native FaceDetector first (Fastest / 0% CPU overhead)
                if (nativeDetector) {
                    try {
                        const faces = await nativeDetector.detect(video);
                        if (faces && faces.length > 0) {
                            const box = faces[0].boundingBox;
                            if (box && box.width > 40) {
                                return {
                                    x: box.x || box.left,
                                    y: box.y || box.top,
                                    width: box.width,
                                    height: box.height
                                };
                            }
                        }
                    } catch(e) {}
                }

                // 2. Fallback to TinyFaceDetector
                if (isFaceApiLoaded && typeof faceapi !== 'undefined') {
                    try {
                        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.35 }));
                        if (det && det.box) {
                            return {
                                x: det.box.x,
                                y: det.box.y,
                                width: det.box.width,
                                height: det.box.height
                            };
                        }
                    } catch(e) {}
                }

                return null;
            }

            // --- Check if Face is actually inside the Center Oval ---
            function isFaceInsideOval(faceBox) {
                if (!faceBox || !video.videoWidth || !video.videoHeight) return { inside: false, reason: 'none' };

                const faceCenterX = (faceBox.x + faceBox.width / 2) / video.videoWidth;
                const faceCenterY = (faceBox.y + faceBox.height / 2) / video.videoHeight;
                const faceWidthRatio = faceBox.width / video.videoWidth;

                // Center oval is positioned at (0.50, 0.48)
                const distX = Math.abs(faceCenterX - 0.50);
                const distY = Math.abs(faceCenterY - 0.48);

                // Check horizontal and vertical boundaries
                if (distX > 0.19) {
                    return { inside: false, reason: 'off-center' };
                }

                if (distY > 0.22) {
                    return { inside: false, reason: 'off-center' };
                }

                // Minimum size check (must not be too far/tiny)
                if (faceWidthRatio < 0.12) {
                    return { inside: false, reason: 'too-far' };
                }

                return { inside: true };
            }

            // --- Smooth UI Updater with Strict Spatial Oval Check ---
            function updateDetectionUI(faceBox) {
                if (!faceBox) {
                    isFaceDetected = false;
                    if (biometricFrame) {
                        biometricFrame.className = 'biometric-scanner-frame';
                    }
                    setStatus('bx-error-circle', 'Wajah Belum Terdeteksi', 'is-warning');
                    if (cameraHint) cameraHint.className = 'camera-hint is-warning';
                    if (hintIcon) hintIcon.className = 'bx bx-x-circle';
                    if (hintText) hintText.textContent = 'Posisikan wajah Anda di dalam bulatan kamera.';
                    btnSubmit.disabled = true;
                    return;
                }

                // Check if face is actually positioned inside the center oval
                const ovalCheck = isFaceInsideOval(faceBox);

                if (!ovalCheck.inside) {
                    isFaceDetected = false;
                    if (biometricFrame) {
                        biometricFrame.className = 'biometric-scanner-frame is-outside';
                    }
                    setStatus('bx-scan', 'Arahkan ke Tengah Bulatan', 'is-warning');
                    if (cameraHint) cameraHint.className = 'camera-hint is-warning';
                    if (hintIcon) hintIcon.className = 'bx bx-scan';

                    if (ovalCheck.reason === 'too-far') {
                        if (hintText) hintText.textContent = 'Dekatkan wajah Anda ke arah bulatan kamera.';
                    } else {
                        if (hintText) hintText.textContent = 'Posisikan wajah Anda tepat di dalam bulatan tengah.';
                    }
                    btnSubmit.disabled = true;
                    return;
                }

                // Face IS properly aligned inside the center oval!
                isFaceDetected = true;
                if (biometricFrame) {
                    biometricFrame.className = 'biometric-scanner-frame is-detected';
                }

                if (!userFaceRegistered) {
                    setContextMode(true);
                    btnSubmit.disabled = true;
                    return;
                }

                setStatus('bx-check-circle', 'Wajah Terdeteksi', 'is-success');
                if (cameraHint) cameraHint.className = 'camera-hint is-success';
                if (hintIcon) hintIcon.className = 'bx bx-check-circle';
                if (hintText) hintText.textContent = 'Wajah pas di dalam bulatan. Klik tombol {{ $actionTitle }} untuk absen.';
                btnSubmit.disabled = false;
            }

            // --- Start Camera Stream ---
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setErrorState('Browser tidak mendukung kamera', 'Gunakan browser modern dan pastikan izin kamera aktif.');
                return;
            }

            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            }).then((stream) => {
                video.srcObject = stream;
                
                let isStreamReady = false;
                const startCameraPreview = () => {
                    if (isStreamReady) return;
                    isStreamReady = true;
                    video.play().catch(e => console.warn('Video play error:', e));
                    loader.classList.add('is-hidden');
                    
                    // Smooth local loop running every 200ms without network load
                    setInterval(async () => {
                        if (isDetecting || !loader.classList.contains('is-hidden')) return;
                        isDetecting = true;
                        try {
                            const face = await detectLiveFace();
                            updateDetectionUI(face);

                            // Wizard pose feedback
                            if (!userFaceRegistered && face) {
                                const poseCheck = await detectFacePoseInVideo(wizardStep);
                                if (poseCheck.valid) {
                                    if (wizardSub) {
                                        wizardSub.style.color = '#4ade80';
                                        wizardSub.textContent = '✓ ' + poseCheck.reason + ' Silakan klik tombol di bawah untuk mengambil foto.';
                                    }
                                    if (btnWizardAction) btnWizardAction.disabled = false;
                                } else {
                                    if (wizardSub) {
                                        wizardSub.style.color = '#f87171';
                                        wizardSub.textContent = '⚠️ ' + poseCheck.reason;
                                    }
                                }
                            }
                        } catch(err) {
                            console.debug('Detection tick error:', err);
                        } finally {
                            isDetecting = false;
                        }
                    }, 200);
                };

                video.onloadedmetadata = startCameraPreview;
                video.onloadeddata = startCameraPreview;
                if (video.readyState >= 1) startCameraPreview();
            }).catch((err) => {
                console.error('Camera access error:', err);
                setErrorState('Tidak bisa membuka kamera', 'Periksa izin kamera di browser, lalu buka kembali.');
            });

            // --- Form Submission & Fast AI Verification on Button Tap ---
            btnSubmit.addEventListener('click', function(e) {
                e.preventDefault();

                if (!video.videoWidth || !video.videoHeight) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kamera belum siap',
                        text: 'Tunggu preview kamera muncul sebelum melakukan absensi.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                if (!isFaceDetected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Wajah Tidak Terdeteksi',
                        text: 'Posisikan wajah Anda dengan jelas di dalam kamera sebelum menekan tombol {{ $actionTitle }}.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                const processFormSubmission = (position) => {
                    const context = canvas.getContext('2d');
                    const maxDim = 640;
                    let w = video.videoWidth || 640;
                    let h = video.videoHeight || 480;
                    if (w > maxDim) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    }
                    canvas.width = w;
                    canvas.height = h;
                    context.drawImage(video, 0, 0, w, h);

                    // Kompresi foto absensi harian (0.65 JPEG) agar hemat penyimpanan & cepat terunggah
                    photoInput.value = canvas.toDataURL('image/jpeg', 0.65);
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;

                    document.getElementById('attendanceForm').submit();
                };

                const proceedWithGps = () => {
                    if (cachedPosition) {
                        processFormSubmission(cachedPosition);
                        return;
                    }

                    Swal.fire({
                        title: 'Mengunci Lokasi GPS...',
                        text: 'Mohon tunggu sejenak, sistem sedang mengunci posisi GPS Anda.',
                        allowOutsideClick: false,
                        confirmButtonColor: '#2f80ed',
                        didOpen: () => Swal.showLoading()
                    });

                    navigator.geolocation.getCurrentPosition(
                        (pos) => processFormSubmission(pos),
                        (err) => {
                            navigator.geolocation.getCurrentPosition(
                                (pos2) => processFormSubmission(pos2),
                                (err2) => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'GPS Tidak Tersedia',
                                        text: 'Pastikan izin lokasi GPS aktif pada browser Anda.',
                                        confirmButtonColor: '#2f80ed'
                                    });
                                },
                                { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
                            );
                        },
                        { enableHighAccuracy: false, timeout: 4000, maximumAge: 300000 }
                    );
                };

                // Jika strict mode aktif & memiliki sampel referensi, lakukan verifikasi AI cepat
                if (isStrictMode && hasFaceSamples) {
                    Swal.fire({
                        title: 'Memverifikasi Wajah AI...',
                        text: 'Mencocokkan biometrik wajah Anda dengan data NIP terdaftar.',
                        allowOutsideClick: false,
                        confirmButtonColor: '#2f80ed',
                        didOpen: () => Swal.showLoading()
                    });

                    const snapCanvas = document.createElement('canvas');
                    snapCanvas.width = 480;
                    snapCanvas.height = 360;
                    const snapCtx = snapCanvas.getContext('2d');
                    const vvw = video.videoWidth || 480;
                    const vvh = video.videoHeight || 360;
                    const tRatio = 480 / 360;
                    let lsx = 0, lsy = 0, lsw = vvw, lsh = vvh;
                    if (vvw / vvh > tRatio) {
                        lsw = vvh * tRatio;
                        lsx = (vvw - lsw) / 2;
                    } else {
                        lsh = vvw / tRatio;
                        lsy = (vvh - lsh) / 2;
                    }
                    snapCtx.drawImage(video, lsx, lsy, lsw, lsh, 0, 0, 480, 360);
                    const liveB64 = snapCanvas.toDataURL('image/jpeg', 0.85);

                    fetch(faceVerifyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ live_b64: liveB64 })
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.matched === true) {
                            proceedWithGps();
                        } else {
                            const matchPct = result.match_pct ?? 0;
                            const errDetail = result.error ? `<br><small class="text-danger">${result.error}</small>` : '';
                            Swal.fire({
                                icon: 'error',
                                title: 'Verifikasi Wajah Gagal',
                                html: `Wajah di kamera tidak cocok dengan foto referensi NIP terdaftar.<br><small class="text-muted">Tingkat Kemiripan: <b>${matchPct}%</b></small>${errDetail}<br><br><small class="text-muted">Tips: Pastikan pencahayaan cukup terang dan hadap lurus ke kamera.</small>`,
                                confirmButtonColor: '#2f80ed'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Face verify error:', err);
                        // Fallback submit to attendance.process
                        proceedWithGps();
                    });
                } else {
                    proceedWithGps();
                }
            });
        });
    </script>
</body>

</html>
