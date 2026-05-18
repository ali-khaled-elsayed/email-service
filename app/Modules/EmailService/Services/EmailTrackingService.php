<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailLog;
use Illuminate\Support\Facades\URL;

class EmailTrackingService
{
    public function injectTrackingPixel(string $html, EmailLog $emailLog): string
    {
        $url = URL::signedRoute('email.track.open', ['emailLog' => $emailLog->id]);
        $pixel = '<img src="'.e($url).'" width="1" height="1" alt="" style="display:none" />';

        if (str_contains($html, '</body>')) {
            return str_replace('</body>', $pixel.'</body>', $html);
        }

        return $html.$pixel;
    }

    public function wrapLinks(string $html, EmailLog $emailLog): string
    {
        return preg_replace_callback(
            '/href="([^"]+)"/',
            function (array $matches) use ($emailLog) {
                $url = URL::signedRoute('email.track.click', [
                    'emailLog' => $emailLog->id,
                    'url' => base64_encode($matches[1]),
                ]);

                return 'href="'.e($url).'"';
            },
            $html
        ) ?? $html;
    }
}
