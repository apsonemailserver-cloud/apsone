<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequestNotificationMailService
{
    /**
     * Send email notification to creator when request (Lembur, Cuti, Koreksi Absen) is submitted.
     */
    public static function sendSubmissionEmail(User $user, string $requestType, array $details): void
    {
        if (empty($user->email)) {
            Log::info("Request notification email skipped: User {$user->id} does not have an email address.");
            return;
        }

        try {
            Mail::send('emails.request-submitted', [
                'user'        => $user,
                'requestType' => $requestType,
                'details'     => $details,
            ], function ($message) use ($user, $requestType) {
                $fromAddress = config('mail.from.address') ?: 'no-reply@apsone.web.id';
                $fromName    = config('mail.from.name') ?: 'APSone Notification';

                $message->from($fromAddress, $fromName)
                    ->to($user->email)
                    ->subject("APSone - Pengajuan {$requestType} Anda Telah Diterima");
            });

            Log::info("Submission notification email sent successfully for {$requestType} to {$user->email}");
        } catch (\Throwable $e) {
            Log::error("Failed to send {$requestType} submission email to {$user->email}: " . $e->getMessage());
        }
    }

    /**
     * Send email notification to creator when request is decided (Approved or Rejected).
     */
    public static function sendDecisionEmail(User $user, string $requestType, string $status, array $details, ?string $approverName = null): void
    {
        if (empty($user->email)) {
            Log::info("Decision notification email skipped: User {$user->id} does not have an email address.");
            return;
        }

        $isApproved = in_array(strtolower($status), ['approved', 'disetujui'], true);
        $statusLabel = $isApproved ? 'Disetujui' : 'Ditolak';

        try {
            Mail::send('emails.request-decided', [
                'user'         => $user,
                'requestType'  => $requestType,
                'status'       => $status,
                'statusLabel'  => $statusLabel,
                'details'      => $details,
                'approverName' => $approverName,
            ], function ($message) use ($user, $requestType, $statusLabel) {
                $fromAddress = config('mail.from.address') ?: 'no-reply@apsone.web.id';
                $fromName    = config('mail.from.name') ?: 'APSone Notification';

                $message->from($fromAddress, $fromName)
                    ->to($user->email)
                    ->subject("APSone - Pengajuan {$requestType} Anda {$statusLabel}");
            });

            Log::info("Decision notification email sent successfully for {$requestType} ({$statusLabel}) to {$user->email}");
        } catch (\Throwable $e) {
            Log::error("Failed to send {$requestType} decision email to {$user->email}: " . $e->getMessage());
        }
    }
}
