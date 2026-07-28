@php
    $isCheckOut = $type === 'out';
    $actionTitle = $isCheckOut ? 'Absen Out' : 'Absen In';
    $actionSub = $isCheckOut ? 'Akhiri shift dengan verifikasi wajah dan GPS.' : 'Mulai shift dengan verifikasi wajah dan GPS.';
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $actionTitle }} - Attendance</title>
    <link rel="icon" href="{{ asset('storage/aps_mini.png') }}" sizes="48x48" type="image/png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --cam-blue: #2f80ed;
            --cam-blue-dark: #2368c8;
            --cam-green: #5cc7b2;
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
            min-height: 620px;
            padding: clamp(1rem, 2vw, 1.35rem);
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            gap: 1rem;
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
            padding: 0.55rem;
        }

        .camera-back,
        .camera-mini-action {
            width: 46px;
            height: 46px;
            border: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--cam-text);
            background: var(--cam-surface);
            text-decoration: none;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .camera-back i,
        .camera-mini-action i {
            font-size: 1.35rem;
        }

        .camera-title {
            min-width: 0;
            flex: 1;
        }

        .camera-title small,
        .camera-title strong {
            display: block;
        }

        .camera-title small {
            color: var(--cam-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .camera-title strong {
            color: var(--cam-text);
            font-size: clamp(0.96rem, 2vw, 1.08rem);
            font-weight: 780;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .camera-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            height: 40px;
            padding: 0 0.85rem;
            border-radius: 999px;
            background: rgba(47, 128, 237, 0.11);
            color: var(--cam-blue);
            font-size: 0.78rem;
            font-weight: 750;
            white-space: nowrap;
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
                linear-gradient(to bottom, rgba(2, 6, 23, 0.54), transparent 24%, transparent 70%, rgba(2, 6, 23, 0.62)),
                radial-gradient(circle at 50% 45%, transparent 0 145px, rgba(2, 6, 23, 0.08) 146px 100%);
            z-index: 1;
        }

        .face-guide {
            position: absolute;
            z-index: 2;
            left: 50%;
            top: 46%;
            width: min(34vw, 260px);
            aspect-ratio: 0.78;
            transform: translate(-50%, -50%);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 46% 46% 42% 42%;
            box-shadow:
                0 0 0 9999px rgba(2, 6, 23, 0.1),
                0 0 38px rgba(47, 128, 237, 0.26);
            opacity: 0.88;
        }

        .camera-hint {
            position: absolute;
            z-index: 3;
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
            background: rgba(15, 23, 42, 0.75);
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
            background: rgba(22, 163, 74, 0.85) !important;
            border-color: rgba(34, 197, 94, 0.4) !important;
            color: #ffffff !important;
        }

        .camera-hint.is-warning {
            background: rgba(220, 38, 38, 0.85) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
            color: #ffffff !important;
        }

        .camera-loader {
            position: absolute;
            inset: 0;
            z-index: 4;
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
            background: rgba(17, 28, 49, 0.78);
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

        .loader-card strong,
        .loader-card span {
            display: block;
        }

        .loader-card strong {
            font-size: 1.08rem;
            font-weight: 780;
        }

        .loader-card span {
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
            padding: 0.62rem;
            border-radius: 28px;
        }

        .camera-meta {
            min-width: 0;
            padding-left: 0.45rem;
        }

        .camera-meta strong,
        .camera-meta span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .camera-meta strong {
            color: var(--cam-text);
            font-size: 0.95rem;
            font-weight: 780;
        }

        .camera-meta span {
            color: var(--cam-muted);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .camera-submit {
            min-width: 148px;
            height: 54px;
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
            box-shadow: 0 16px 32px rgba(47, 128, 237, 0.28);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .camera-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(47, 128, 237, 0.34);
        }

        .camera-submit:disabled {
            opacity: 0.62;
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

        body.aps-camera-dark .swal2-timer-progress-bar {
            background: var(--cam-blue) !important;
        }

        .camera-status-pill.is-success {
            background: rgba(34, 197, 94, 0.15) !important;
            color: #16a34a !important;
        }

        .camera-status-pill.is-warning {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #dc2626 !important;
        }

        .face-guide {
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .face-guide.is-valid {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.1), 0 0 45px rgba(34, 197, 94, 0.5) !important;
        }

        @media (max-width: 767.98px) {
            html,
            body {
                overflow: hidden;
                touch-action: manipulation;
            }

            .camera-page {
                height: 100dvh;
                min-height: 100dvh;
                padding: 0.6rem;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: 0.6rem;
            }

            .camera-topbar {
                border-radius: 20px;
                padding: 0.45rem 0.6rem;
                gap: 0.5rem;
            }

            .camera-back {
                width: 38px;
                height: 38px;
                flex-shrink: 0;
            }

            .camera-back i {
                font-size: 1.15rem;
            }

            .camera-title small {
                font-size: 0.65rem;
            }

            .camera-title strong {
                font-size: 0.88rem;
            }

            .camera-status-pill {
                display: inline-flex;
                height: 32px;
                padding: 0 0.6rem;
                font-size: 0.7rem;
                gap: 0.3rem;
                flex-shrink: 0;
            }

            .camera-status-pill i {
                font-size: 0.9rem;
            }

            .camera-stage {
                border-radius: 24px;
            }

            .face-guide {
                width: min(52vw, 200px);
            }

            .camera-hint {
                bottom: 0.65rem;
                width: calc(100% - 1.2rem);
                padding: 0.5rem 0.75rem;
                border-radius: 14px;
                font-size: 0.75rem;
                gap: 0.4rem;
            }

            .camera-bottom-card {
                grid-template-columns: 1fr;
                border-radius: 22px;
                padding: 0.65rem 0.85rem;
                gap: 0.5rem;
            }

            .camera-meta {
                text-align: center;
                padding: 0;
            }

            .camera-meta strong {
                font-size: 0.88rem;
            }

            .camera-meta span {
                font-size: 0.72rem;
            }

            .camera-submit {
                width: 100%;
                min-width: 0;
                height: 46px;
                border-radius: 14px;
                font-size: 0.88rem;
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
                <small>Attendance Verification</small>
                <strong>{{ $actionTitle }} - {{ $user->fullname ?? 'Staff APS' }}</strong>
            </div>
            <div class="camera-status-pill" id="cameraStatus">
                <i class="bx bx-camera"></i>
                <span>Menyiapkan kamera</span>
            </div>
        </header>

        <section class="camera-stage" aria-label="Preview kamera">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas" class="d-none"></canvas>
            <div class="face-guide" aria-hidden="true"></div>
            <div class="camera-hint">
                <i class="bx bx-face"></i>
                <span>Posisikan wajah di tengah frame, lalu pastikan GPS aktif.</span>
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
                <strong>{{ $actionTitle }} Sekarang</strong>
                <span>{{ $actionSub }}</span>
            </div>
            <button type="submit" id="btnSubmit" class="camera-submit" disabled>
                <i class="bx {{ $isCheckOut ? 'bx-log-out' : 'bx-log-in' }}"></i>
                <span>{{ $actionTitle }}</span>
            </button>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.17.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.0.7/dist/blazeface.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            const btnSubmit = document.getElementById('btnSubmit');
            const photoInput = document.getElementById('photoInput');
            const loader = document.getElementById('cameraLoader');
            const loaderTitle = document.getElementById('loaderTitle');
            const loaderText = document.getElementById('loaderText');
            const cameraStatus = document.getElementById('cameraStatus');
            const faceGuide = document.querySelector('.face-guide');
            const hintText = document.querySelector('.camera-hint span');

            let isFaceDetected = false;
            let isModelReady = false;
            let blazefaceModel = null;
            let nativeDetector = null;

            const setStatus = (icon, text) => {
                if (cameraStatus) {
                    cameraStatus.innerHTML = `<i class="bx ${icon}"></i><span>${text}</span>`;
                }
            };

            const setErrorState = (title, text) => {
                if (loader) loader.classList.remove('is-hidden');
                if (loaderTitle) loaderTitle.textContent = title;
                if (loaderText) loaderText.textContent = text;
                setStatus('bx-error-circle', 'Kamera belum siap');
                btnSubmit.disabled = true;
            };

            const updateFaceStatusUI = (hasFace) => {
                isFaceDetected = hasFace;
                const cameraHint = document.querySelector('.camera-hint');
                const hintIcon = document.querySelector('.camera-hint i');

                if (hasFace) {
                    if (cameraStatus) {
                        cameraStatus.className = 'camera-status-pill is-success';
                        cameraStatus.innerHTML = '<i class="bx bx-check-circle"></i><span>Wajah Terdeteksi</span>';
                    }
                    if (faceGuide) faceGuide.classList.add('is-valid');
                    if (cameraHint) cameraHint.className = 'camera-hint is-success';
                    if (hintIcon) hintIcon.className = 'bx bx-check-circle';
                    if (hintText) hintText.textContent = 'Wajah terdeteksi dengan jelas. Silakan klik {{ $actionTitle }}.';
                    btnSubmit.disabled = false;
                } else {
                    if (cameraStatus) {
                        cameraStatus.className = 'camera-status-pill is-warning';
                        cameraStatus.innerHTML = '<i class="bx bx-error-circle"></i><span>Wajah Tidak Terdeteksi</span>';
                    }
                    if (faceGuide) faceGuide.classList.remove('is-valid');
                    if (cameraHint) cameraHint.className = 'camera-hint is-warning';
                    if (hintIcon) hintIcon.className = 'bx bx-x-circle';
                    if (hintText) hintText.textContent = 'Posisikan wajah di tengah frame hingga indikator berwarna hijau.';
                    btnSubmit.disabled = true;
                }
            };

            setStatus('bx-loader-alt bx-spin', 'Memuat AI Deteksi Wajah...');

            // Load BlazeFace AI Neural Model
            if (typeof blazeface !== 'undefined') {
                blazeface.load().then(model => {
                    blazefaceModel = model;
                    isModelReady = true;
                    console.log('BlazeFace AI model loaded.');
                }).catch(err => {
                    console.warn('BlazeFace model load error:', err);
                    isModelReady = true; // Fallback to Laplacian edge detector
                });
            } else {
                isModelReady = true;
            }

            if ('FaceDetector' in window) {
                try {
                    nativeDetector = new FaceDetector({ fastMode: true, maxFaces: 1 });
                } catch(e) {
                    nativeDetector = null;
                }
            }

            const faceCanvas = document.createElement('canvas');
            const faceCtx = faceCanvas.getContext('2d', { willReadFrequently: true });
            faceCanvas.width = 160;
            faceCanvas.height = 120;

            // Laplacian Sharpness/Edge Test (Prevents hands/fingers covering lens)
            function calculateEdgeSharpness(imageData) {
                const data = imageData.data;
                const w = faceCanvas.width;
                const h = faceCanvas.height;
                let lapSum = 0;
                let lapSqSum = 0;
                let count = 0;

                for (let y = 1; y < h - 1; y += 2) {
                    for (let x = 1; x < w - 1; x += 2) {
                        const getLum = (px, py) => {
                            const i = (py * w + px) * 4;
                            return 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
                        };

                        const center = getLum(x, y);
                        const lap = Math.abs(
                            -4 * center +
                            getLum(x - 1, y) + getLum(x + 1, y) +
                            getLum(x, y - 1) + getLum(x, y + 1)
                        );

                        lapSum += lap;
                        lapSqSum += lap * lap;
                        count++;
                    }
                }

                const meanLap = lapSum / count;
                return (lapSqSum / count) - (meanLap * meanLap);
            }

            async function detectFaceInVideo() {
                if (!video.videoWidth || !video.videoHeight || video.paused || video.ended) {
                    return false;
                }

                const vw = video.videoWidth;
                const vh = video.videoHeight;

                // First check if camera lens is covered by hand/finger (blurry skin surface with zero edges)
                try {
                    faceCtx.drawImage(video, 0, 0, faceCanvas.width, faceCanvas.height);
                    const imgData = faceCtx.getImageData(0, 0, faceCanvas.width, faceCanvas.height);
                    const edgeVariance = calculateEdgeSharpness(imgData);

                    // If camera is covered by hand/object, edge variance is extremely low (< 25)
                    if (edgeVariance < 25) {
                        return false; // Lens covered by hand or object!
                    }
                } catch (e) {}

                // 1. AI Neural Network BlazeFace (Strict Full-Face Landmark Visibility Test)
                if (blazefaceModel) {
                    try {
                        const predictions = await blazefaceModel.estimateFaces(video, false);
                        if (predictions && predictions.length > 0) {
                            const pred = predictions[0];
                            const prob = pred.probability ? pred.probability[0] : 1;
                            const landmarks = pred.landmarks;

                            if (prob > 0.75 && landmarks && landmarks.length >= 4) {
                                const rightEye = landmarks[0]; // [x, y]
                                const leftEye = landmarks[1];  // [x, y]
                                const nose = landmarks[2];     // [x, y]
                                const mouth = landmarks[3];    // [x, y]

                                // CRITICAL VALIDATION: Both Eyes, Nose, AND Mouth MUST be clearly inside video frame!
                                // Prevents neck/chest/chin (where eyes are cut off at the top) from passing
                                const eyesVisible = rightEye[1] > (vh * 0.06) && leftEye[1] > (vh * 0.06) &&
                                                    rightEye[1] < (vh * 0.65) && leftEye[1] < (vh * 0.65);
                                const noseVisible = nose[1] > rightEye[1] && nose[1] < (vh * 0.82);
                                const mouthVisible = mouth[1] > nose[1] && mouth[1] < (vh * 0.95);

                                if (eyesVisible && noseVisible && mouthVisible) {
                                    return true; // Full face with eyes, nose, and mouth present!
                                }
                            }
                        }
                        return false; // Neck, chin, chest, or cut-off face returns false!
                    } catch (e) {
                        console.warn('BlazeFace detection error:', e);
                    }
                }

                // 2. Native Browser FaceDetector (Chromium / Edge / Mac Safari)
                if (nativeDetector) {
                    try {
                        const faces = await nativeDetector.detect(video);
                        if (faces && faces.length > 0) {
                            const box = faces[0].boundingBox;
                            if (box.top > (vh * 0.04) && box.height > (vh * 0.22)) {
                                return true;
                            }
                        }
                        return false;
                    } catch (e) {}
                }

                // 3. Strict Structural Fallback: Eyebrows/Eyes (Dark pixels) in Upper Oval + Skin in Mid Oval
                try {
                    const imgData = faceCtx.getImageData(0, 0, faceCanvas.width, faceCanvas.height);
                    const data = imgData.data;

                    let darkEyePixels = 0;
                    let noseSkinPixels = 0;
                    let totalEyeSamples = 0;
                    let totalNoseSamples = 0;

                    const startX = Math.floor(faceCanvas.width * 0.32);
                    const endX = Math.floor(faceCanvas.width * 0.68);

                    // Upper Oval (Eye & Eyebrow Zone: y = 20..45)
                    for (let y = 20; y < 45; y += 2) {
                        for (let x = startX; x < endX; x += 2) {
                            const idx = (y * faceCanvas.width + x) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b = data[idx + 2];
                            totalEyeSamples++;

                            const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                            // Eyebrows / pupils / eye sockets are significantly darker (lum < 95)
                            if (lum < 95) {
                                darkEyePixels++;
                            }
                        }
                    }

                    // Middle Oval (Nose & Cheek Zone: y = 45..75)
                    for (let y = 45; y < 75; y += 2) {
                        for (let x = startX; x < endX; x += 2) {
                            const idx = (y * faceCanvas.width + x) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b = data[idx + 2];
                            totalNoseSamples++;

                            const maxRGB = Math.max(r, g, b);
                            const minRGB = Math.min(r, g, b);
                            const isSkin = (r > 65) && (g > 45) && (b > 30) &&
                                           (maxRGB - minRGB > 18) &&
                                           (Math.abs(r - g) > 15) &&
                                           (r > g) && (r > b);

                            if (isSkin) noseSkinPixels++;
                        }
                    }

                    const darkEyeRatio = darkEyePixels / totalEyeSamples;
                    const noseSkinRatio = noseSkinPixels / totalNoseSamples;

                    // Neck/Chest has NO eyebrows/eyes at the top (darkEyeRatio < 0.04)
                    // Full face MUST have eyebrows/eyes dark pixels (> 0.065) AND nose skin (> 0.25)
                    return (darkEyeRatio >= 0.065 && noseSkinRatio >= 0.25);
                } catch (err) {
                    return false;
                }
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setErrorState('Browser belum mendukung kamera', 'Gunakan browser modern dan pastikan akses kamera tersedia.');
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
                video.onloadedmetadata = () => {
                    loader.classList.add('is-hidden');
                    
                    setInterval(async () => {
                        if (loader.classList.contains('is-hidden')) {
                            const detected = await detectFaceInVideo();
                            updateFaceStatusUI(detected);
                        }
                    }, 300);
                };
            }).catch(() => {
                setErrorState('Tidak bisa membuka kamera', 'Periksa izin kamera di browser, lalu coba buka halaman ini kembali.');
                Swal.fire({
                    icon: 'error',
                    title: 'Kamera tidak tersedia',
                    text: 'Periksa izin kamera di browser, lalu coba buka halaman ini kembali.',
                    confirmButtonColor: '#2f80ed'
                });
            });

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
                        text: 'Posisikan wajah Anda dengan jelas di tengah kamera sebelum menekan tombol {{ $actionTitle }}.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Mengambil lokasi',
                    text: 'Mohon tunggu, sistem sedang memverifikasi GPS Anda.',
                    allowOutsideClick: false,
                    confirmButtonColor: '#2f80ed',
                    didOpen: () => Swal.showLoading()
                });

                const handleSuccess = (position) => {
                    const context = canvas.getContext('2d');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                    photoInput.value = canvas.toDataURL('image/png');
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;

                    document.getElementById('attendanceForm').submit();
                };

                const handleFailure = (error) => {
                    let title = 'GPS tidak tersedia';
                    let message = 'Aktifkan izin lokasi agar absensi dapat diverifikasi.';

                    if (error.code === error.PERMISSION_DENIED) {
                        title = 'Izin Lokasi Ditolak';
                        message = 'Aktifkan izin lokasi di pengaturan browser untuk melakukan absensi.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        title = 'Sinyal GPS Lemah';
                        message = 'Lokasi tidak dapat ditentukan. Pastikan GPS dan koneksi internet Anda aktif, atau coba di area yang lebih terbuka.';
                    } else if (error.code === error.TIMEOUT) {
                        title = 'Waktu Permintaan Habis';
                        message = 'Gagal mendapatkan lokasi tepat waktu. Silakan coba klik tombol Absen In lagi.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: title,
                        text: message,
                        confirmButtonColor: '#2f80ed'
                    });
                };

                navigator.geolocation.getCurrentPosition(handleSuccess, function(error) {
                    if (error.code === error.PERMISSION_DENIED) {
                        handleFailure(error);
                    } else {
                        navigator.geolocation.getCurrentPosition(handleSuccess, function(error2) {
                            handleFailure(error2);
                        }, {
                            enableHighAccuracy: false,
                            timeout: 10000,
                            maximumAge: 10000
                        });
                    }
                }, {
                    enableHighAccuracy: true,
                    timeout: 6000,
                    maximumAge: 0
                });
            });
        });
    </script>
</body>

</html>
