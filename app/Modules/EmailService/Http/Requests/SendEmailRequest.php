<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['required', 'email'],
            'cc' => ['sometimes', 'array'],
            'cc.*' => ['email'],
            'bcc' => ['sometimes', 'array'],
            'bcc.*' => ['email'],
            'subject' => ['required_without:template', 'string', 'max:998'],
            'html' => ['required_without_all:template,text', 'string'],
            'text' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'in:high,default,low,bulk'],
            'type' => ['sometimes', 'in:transactional,marketing,notification,system'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.name' => ['required_with:attachments', 'string'],
            'attachments.*.content' => ['sometimes', 'string'],
            'attachments.*.path' => ['sometimes', 'string'],
            'meta' => ['sometimes', 'array'],
            'idempotency_key' => ['sometimes', 'string', 'max:255'],
            'template' => ['sometimes', 'string'],
            'template_data' => ['sometimes', 'array'],
        ];
    }
}
