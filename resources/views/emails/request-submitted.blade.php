<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pengajuan {{ $requestType }} Diterima</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; background-color:#f8fafc; margin:0; padding:40px 16px;">
        <tr>
            <td align="center">
                <!-- Main Card Container -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; max-width:600px; border-collapse:separate; border-spacing:0;">
                    
                    <!-- Pre-header Bar -->
                    <tr>
                        <td style="padding:0 4px 14px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="font-size:11px; font-weight:800; letter-spacing:0.15em; text-transform:uppercase; color:#2563eb;">
                                        APSone System Notification
                                    </td>
                                    <td align="right" style="font-size:12px; font-weight:600; color:#64748b;">
                                        {{ date('d M Y') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Body Container -->
                    <tr>
                        <td style="overflow:hidden; border-radius:20px; background-color:#ffffff; border:1px solid #e2e8f0; box-shadow:0 20px 40px -15px rgba(15, 23, 42, 0.08);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                
                                <!-- Brand Header -->
                                <tr>
                                    <td style="padding:36px 40px 32px; background:linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #2563eb 100%);">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="left" valign="middle">
                                                    <div style="font-size:32px; line-height:1; font-weight:900; color:#ffffff; letter-spacing:-0.5px;">APS<span style="color:#60a5fa;">one</span></div>
                                                    <div style="margin-top:6px; font-size:11px; font-weight:700; color:rgba(255,255,255,0.75); letter-spacing:0.12em; text-transform:uppercase;">PT. Angkasa Pratama Sejahtera</div>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <span style="display:inline-block; padding:6px 14px; border-radius:30px; background:rgba(255,255,255,0.15); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,0.25); color:#ffffff; font-size:12px; font-weight:700; letter-spacing:0.05em;">
                                                        PENDING
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Welcome & Main Heading -->
                                <tr>
                                    <td style="padding:36px 40px 16px;">
                                        <div style="display:inline-block; padding:4px 12px; border-radius:6px; background:#eff6ff; color:#2563eb; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:12px;">
                                            Konfirmasi Pengajuan
                                        </div>
                                        <h1 style="margin:0 0 12px; color:#0f172a; font-size:22px; line-height:1.35; font-weight:800; letter-spacing:-0.3px;">
                                            Pengajuan {{ $requestType }} Berhasil Diterima
                                        </h1>
                                        <p style="margin:0; color:#475569; font-size:15px; line-height:1.7;">
                                            Halo <strong style="color:#0f172a;">{{ $user->fullname }}</strong>, permohonan <strong>{{ $requestType }}</strong> Anda telah berhasil kami catat dalam sistem. Berikut adalah rincian pengajuan Anda:
                                        </p>
                                    </td>
                                </tr>

                                <!-- Structured Detail Table -->
                                <tr>
                                    <td style="padding:12px 40px 24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; border-collapse:separate; border-spacing:0; overflow:hidden;">
                                            @foreach($details as $label => $val)
                                            <tr>
                                                <td style="padding:12px 18px; color:#64748b; font-size:13px; font-weight:700; width:40%; border-bottom:1px solid #edf2f7; text-transform:uppercase; letter-spacing:0.03em;">
                                                    {{ $label }}
                                                </td>
                                                <td style="padding:12px 18px; color:#0f172a; font-size:14px; font-weight:700; border-bottom:1px solid #edf2f7;">
                                                    {{ $val }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>

                                <!-- Informative Notice Card -->
                                <tr>
                                    <td style="padding:0 40px 32px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:14px; padding:20px;">
                                            <tr>
                                                <td style="vertical-align:top; width:28px; padding-right:12px;">
                                                    <div style="width:24px; height:24px; border-radius:50%; background:#0284c7; color:#ffffff; text-align:center; line-height:24px; font-weight:bold; font-size:13px;">ℹ</div>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <div style="font-size:14px; font-weight:800; color:#0369a1; margin-bottom:4px;">Proses Peninjauan Atasan</div>
                                                    <div style="font-size:13px; line-height:1.6; color:#0c4a6e;">
                                                        Permohonan Anda saat ini sedang dalam antrean verifikasi atasan. <strong>Anda akan menerima email pemberitahuan otomatis kembali begitu pengajuan ini disetujui (di-approve) atau ditolak.</strong>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Footer / Sign-off -->
                                <tr>
                                    <td style="padding:0 40px 34px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #edf2f7;">
                                            <tr>
                                                <td align="center" style="padding-top:22px; color:#8a97aa; font-size:12px; line-height:1.6;">
                                                    Email ini dikirim otomatis oleh sistem APSone. Mohon tidak membalas email ini secara langsung.
                                                    <br>
                                                    &copy; 2025 PT. Angkasa Pratama Sejahtera. All Rights Reserved.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
