<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\SettingsResource\Pages;

use App\Modules\EmailService\Filament\Resources\SettingsResource;
use App\Modules\EmailService\Models\EmailSetting;
use App\Modules\EmailService\Services\EmailSettingsService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class ManageSettings extends EditRecord
{
    protected static string $resource = SettingsResource::class;

    protected static ?string $title = 'Email Service Settings';

    public function mount(int|string $record = 0): void
    {
        parent::mount(EmailSetting::instance()->getKey());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['retry_delays'] = $this->formatDelaysForForm(
            $data['retry_delays'] ?? [],
            (int) ($data['max_attempts'] ?? 5),
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $maxAttempts = max(1, (int) ($data['max_attempts'] ?? 5));
        $retryDelays = [];

        foreach ($data['retry_delays'] ?? [] as $item) {
            if (isset($item['attempt'], $item['delay'])) {
                $retryDelays[(int) $item['attempt']] = (int) $item['delay'];
            }
        }

        $data['max_attempts'] = $maxAttempts;
        $data['retry_delays'] = app(EmailSettingsService::class)->normalizeDelays($maxAttempts, $retryDelays);

        return $data;
    }

    protected function afterSave(): void
    {
        app(EmailSettingsService::class)->clearCache();
        app(EmailSettingsService::class)->syncRuntimeConfig();
    }

    protected function getSavedNotification(): ?Notification
    {
        $settings = app(EmailSettingsService::class);

        return Notification::make()
            ->success()
            ->title('Settings saved')
            ->body(sprintf(
                'Active: %d retries. Delays: %s',
                $settings->getMaxAttempts(),
                collect($settings->getRetryDelays())
                    ->map(fn (int $delay, int $attempt) => "#{$attempt}={$delay}s")
                    ->implode(', '),
            ));
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * @param  array<int|string, int>|array<int, array{attempt: int, delay: int}>  $delays
     * @return array<int, array{attempt: int, delay: int}>
     */
    private function formatDelaysForForm(array $delays, int $maxAttempts): array
    {
        $defaults = app(EmailSettingsService::class)->defaultRetryDelays();
        $formatted = [];

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $delay = $defaults[$i] ?? (int) (end($defaults) ?: 60);

            foreach ($delays as $attempt => $value) {
                if (is_array($value) && (int) ($value['attempt'] ?? 0) === $i) {
                    $delay = (int) $value['delay'];
                    break;
                }
                if (! is_array($value) && (int) $attempt === $i) {
                    $delay = (int) $value;
                    break;
                }
            }

            $formatted[] = ['attempt' => $i, 'delay' => $delay];
        }

        return $formatted;
    }
}
