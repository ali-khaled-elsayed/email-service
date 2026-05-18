<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkEmailRequest extends FormRequest
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
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.email' => ['required_without:recipients.*', 'email'],
            'subject' => ['required_without:template', 'string'],
            'html' => ['required_without:template', 'string'],
            'template' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:transactional,marketing,notification,system'],
            'meta' => ['sometimes', 'array'],
        ];
    }
}
