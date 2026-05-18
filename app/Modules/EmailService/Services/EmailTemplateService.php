<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailTemplate;

class EmailTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(Application $application, string $slug, array $data): array
    {
        $template = EmailTemplate::query()
            ->where('application_id', $application->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $subject = $this->replaceVariables($template->subject, $data);
        $html = $this->replaceVariables($template->html_template, $data);

        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return $content;
    }

    public function preview(EmailTemplate $template, array $data = []): string
    {
        return $this->replaceVariables($template->html_template, $data);
    }
}
