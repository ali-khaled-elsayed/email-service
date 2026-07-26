<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s>
        @php
            $totals = $this->totals();
            $queueStats = $this->queueStats();
            $batches = $this->batches();
        @endphp


        <x-filament::section>
            <x-slot name="heading">Jobs</x-slot>
            <x-slot name="description">Filter by pending or failed jobs, then narrow the list by queue.</x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
