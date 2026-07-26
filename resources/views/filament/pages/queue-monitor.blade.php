<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s>
        @php
            $totals = $this->totals();
            $queueStats = $this->queueStats();
            $pendingJobs = $this->pendingJobs();
            $failedJobs = $this->failedJobs();
            $batches = $this->batches();
        @endphp

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ([
                'Pending jobs' => $totals['pending'],
                'Failed jobs' => $totals['failed'],
                'Batches' => $totals['batches'],
                'Queues' => $totals['queues'],
            ] as $label => $value)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $value }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">Queues</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Queue</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Ready</th>
                            <th class="px-3 py-2">Delayed</th>
                            <th class="px-3 py-2">Reserved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($queueStats as $queue)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $queue['queue'] }}</td>
                                <td class="px-3 py-2">{{ $queue['total'] }}</td>
                                <td class="px-3 py-2">{{ $queue['ready'] }}</td>
                                <td class="px-3 py-2">{{ $queue['delayed'] }}</td>
                                <td class="px-3 py-2">{{ $queue['reserved'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-gray-500 dark:text-gray-400" colspan="5">No queued jobs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Pending Jobs</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Queue</th>
                            <th class="px-3 py-2">Job</th>
                            <th class="px-3 py-2">Email Log</th>
                            <th class="px-3 py-2">State</th>
                            <th class="px-3 py-2">Attempts</th>
                            <th class="px-3 py-2">Available</th>
                            <th class="px-3 py-2">Reserved</th>
                            <th class="px-3 py-2">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($pendingJobs as $job)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $job['id'] }}</td>
                                <td class="px-3 py-2">{{ $job['queue'] }}</td>
                                <td class="px-3 py-2">{{ $job['name'] }}</td>
                                <td class="px-3 py-2">{{ $job['email_log_id'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $job['state'] }}</td>
                                <td class="px-3 py-2">{{ $job['attempts'] }}</td>
                                <td class="px-3 py-2">{{ $job['available_at'] }}</td>
                                <td class="px-3 py-2">{{ $job['reserved_at'] }}</td>
                                <td class="px-3 py-2">{{ $job['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-gray-500 dark:text-gray-400" colspan="9">No pending jobs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Failed Jobs</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Queue</th>
                            <th class="px-3 py-2">Job</th>
                            <th class="px-3 py-2">Email Log</th>
                            <th class="px-3 py-2">Failed At</th>
                            <th class="px-3 py-2">Exception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($failedJobs as $job)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $job['id'] }}</td>
                                <td class="px-3 py-2">{{ $job['queue'] }}</td>
                                <td class="px-3 py-2">{{ $job['name'] }}</td>
                                <td class="px-3 py-2">{{ $job['email_log_id'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $job['failed_at'] }}</td>
                                <td class="px-3 py-2">{{ $job['exception'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-gray-500 dark:text-gray-400" colspan="6">No failed jobs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Job Batches</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Progress</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Pending</th>
                            <th class="px-3 py-2">Failed</th>
                            <th class="px-3 py-2">Created</th>
                            <th class="px-3 py-2">Finished</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $batch['id'] }}</td>
                                <td class="px-3 py-2">{{ $batch['name'] }}</td>
                                <td class="px-3 py-2">{{ $batch['status'] }}</td>
                                <td class="px-3 py-2">{{ $batch['progress'] }}%</td>
                                <td class="px-3 py-2">{{ $batch['total_jobs'] }}</td>
                                <td class="px-3 py-2">{{ $batch['pending_jobs'] }}</td>
                                <td class="px-3 py-2">{{ $batch['failed_jobs'] }}</td>
                                <td class="px-3 py-2">{{ $batch['created_at'] }}</td>
                                <td class="px-3 py-2">{{ $batch['finished_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-4 text-gray-500 dark:text-gray-400" colspan="9">No job batches.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
