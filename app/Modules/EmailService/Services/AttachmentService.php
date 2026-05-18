<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailLog;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     * @return array<int, array<string, mixed>>
     */
    public function store(array $attachments): array
    {
        $stored = [];
        $disk = config('email_service.attachments.disk');
        $path = config('email_service.attachments.path');

        foreach ($attachments as $attachment) {
            if (isset($attachment['path']) && isset($attachment['content'])) {
                $filename = $attachment['name'] ?? basename((string) $attachment['path']);
                $fullPath = $path.'/'.uniqid('att_', true).'_'.$filename;
                Storage::disk($disk)->put($fullPath, base64_decode((string) $attachment['content']));
                $stored[] = [
                    'disk' => $disk,
                    'path' => $fullPath,
                    'name' => $filename,
                    'mime' => $attachment['mime'] ?? null,
                ];
            } elseif (isset($attachment['path'])) {
                $stored[] = $attachment;
            }
        }

        return $stored;
    }

    /**
     * @return array<int, Attachment>
     */
    public function resolveForMailable(EmailLog $emailLog): array
    {
        $attachments = [];

        foreach ($emailLog->attachments ?? [] as $attachment) {
            if (isset($attachment['disk'], $attachment['path'])) {
                $attachments[] = Attachment::fromStorageDisk(
                    $attachment['disk'],
                    $attachment['path'],
                )->as($attachment['name'] ?? basename((string) $attachment['path']));
            }
        }

        return $attachments;
    }
}
