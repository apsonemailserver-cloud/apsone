<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengajuan {{ $requestType }} Diterima</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; background:#f4f7fb; margin:0; padding:34px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%; max-width:620px; border-collapse:separate; border-spacing:0;">
                    <tr>
                        <td style="padding:0 0 14px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="font-size:12px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#4a7ebb;">
                                        APSone Notification
                                    </td>
                                    <td align="right" style="font-size:12px; color:#8a97aa;">
                                        Pengajuan {{ $requestType }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="overflow:hidden; border-radius:24px; background:#ffffff; border:1px solid #e6edf5; box-shadow:0 22px 56px rgba(31,41,55,0.10);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:34px 36px 30px; background:linear-gradient(135deg,#4a7ebb 0%,#2f80ed 58%,#5cc7b2 100%);">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <div style="font-size:32px; line-height:1; font-weight:800; color:#ffffff; letter-spacing:0;">APSone</div>
                                                    <div style="margin-top:8px; font-size:13px; font-weight:700; color:rgba(255,255,255,0.85); letter-spacing:0.08em; text-transform:uppercase;">PT. Angkasa Pratama Sejahtera</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:34px 36px 12px;">
                                        <h1 style="margin:0 0 12px; color:#1f2937; font-size:24px; line-height:1.3; font-weight:800; letter-spacing:0;">Pengajuan {{ $requestType }} Anda telah Diterima</h1>
                                        <p style="margin:0; color:#4b5563; font-size:15px; line-height:1.7;">
                                            Halo <strong style="color:#1f2937;">{{ $user->fullname }}</strong>, permohonan <strong>{{ $requestType }}</strong> yang Anda ajukan telah berhasil kami terima dalam sistem.
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 36px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:18px 20px; border-collapse:collapse;">
                                            @foreach($details as $label => $val)
                                            <tr>
                                                <td style="padding:8px 0; color:#64748b; font-size:14px; font-weight:600; width:38%; border-bottom:1px solid #edf2f7;">{{ $label }}</td>
                                                <td style="padding:8px 0; color:#1f2937; font-size:14px; font-weight:700; border-bottom:1px solid #edf2f7;">: {{ $val }}</td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 36px 28px;">
                                        <div style="padding:16px 20px; border-radius:16px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; font-size:14px; line-height:1.65;">
                                            <strong style="display:block; margin-bottom:4px; font-size:15px;">📌 Informasi Penting:</strong>
                                            Permohonan Anda saat ini sedang dalam proses peninjauan atasan. <strong>Anda akan menerima email pemberitahuan kembali setelah permohonan tersebut disetujui (di-approve) atau ditolak.</strong>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 36px 34px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #edf2f7;">
                                            <tr>
                                                <td align="center" style="padding-top:22px; color:#8a97aa; font-size:12px; line-height:1.6;">
                                                    Email ini dikirim otomatis oleh sistem APSone.
                                                    <br>
                                                    &copy; {{ date('Y') }} PT. Angkasa Pratama Sejahtera. All Rights Reserved.
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
