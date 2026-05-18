<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Repositories;

use App\Modules\EmailService\Enums\ProviderStatus;
use App\Modules\EmailService\Models\Provider;
use Illuminate\Support\Collection;

class ProviderRepository
{
    public function findBySlug(string $slug): ?Provider
    {
        return Provider::query()->where('slug', $slug)->first();
    }

    public function findById(int $id): ?Provider
    {
        return Provider::query()->find($id);
    }

    /**
     * @return Collection<int, Provider>
     */
    public function getAvailableOrdered(): Collection
    {
        return Provider::query()
            ->where('status', ProviderStatus::Active)
            ->orderBy('priority')
            ->orderByDesc('weight')
            ->get()
            ->filter(fn (Provider $p) => $p->isAvailable());
    }
}
