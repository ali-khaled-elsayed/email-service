<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SystemLogReader
{
    private const LOG_LINE_PATTERN = '/^\[(?<datetime>[^\]]+)\]\s+(?<env>[^.]+)\.(?<level>[A-Z]+):\s+(?<message>.*)$/';

    public function logsDirectory(): string
    {
        return storage_path('logs');
    }

    /**
     * @return list<array{key: string, filename: string, date: ?string, size: int, size_human: string, error_count: int, modified_at: int}>
     */
    public function listFiles(): array
    {
        $paths = glob($this->logsDirectory().DIRECTORY_SEPARATOR.'*.log') ?: [];
        $files = [];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $filename = basename($path);
            $size = (int) filesize($path);

            $files[] = [
                'key' => $filename,
                'filename' => $filename,
                'date' => $this->extractDateFromFilename($filename),
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'error_count' => $this->countErrorsInFile($path),
                'modified_at' => (int) filemtime($path),
            ];
        }

        usort($files, fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $files;
    }

    /**
     * @return list<array{key: string, datetime: string, environment: string, level: string, message: string, context: ?string}>
     */
    public function readEntries(
        string $filename,
        ?string $level = null,
        ?string $search = null,
        int $limit = 500,
    ): array {
        $path = $this->resolveFilePath($filename);
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read log file [{$filename}].");
        }

        $entries = $this->parseLogContents($contents);

        if ($level !== null && $level !== '') {
            $level = strtoupper($level);
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry): bool => strtoupper($entry['level']) === $level,
            ));
        }

        if (filled($search)) {
            $search = Str::lower($search);
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry): bool => Str::contains(Str::lower($entry['message']), $search)
                    || Str::contains(Str::lower((string) $entry['context']), $search),
            ));
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    public function getFileSummary(string $filename): array
    {
        $path = $this->resolveFilePath($filename);

        return [
            'filename' => basename($path),
            'date' => $this->extractDateFromFilename(basename($path)),
            'size' => (int) filesize($path),
            'size_human' => $this->formatBytes((int) filesize($path)),
            'error_count' => $this->countErrorsInFile($path),
            'modified_at' => (int) filemtime($path),
        ];
    }

    public function resolveFilePath(string $filename): string
    {
        $filename = basename($filename);

        if (! preg_match('/^[A-Za-z0-9._-]+\.log$/', $filename)) {
            throw new InvalidArgumentException('Invalid log file name.');
        }

        $path = $this->logsDirectory().DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            throw new InvalidArgumentException("Log file [{$filename}] was not found.");
        }

        return $path;
    }

    /**
     * @return list<array{key: string, datetime: string, environment: string, level: string, message: string, context: ?string}>
     */
    private function parseLogContents(string $contents): array
    {
        $entries = [];
        $current = null;
        $index = 0;

        foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
            if (preg_match(self::LOG_LINE_PATTERN, $line, $matches)) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $index++;
                $current = [
                    'key' => (string) $index,
                    'datetime' => $matches['datetime'],
                    'environment' => $matches['env'],
                    'level' => $matches['level'],
                    'message' => $matches['message'],
                    'context' => null,
                ];

                continue;
            }

            if ($current === null || trim($line) === '') {
                continue;
            }

            $current['context'] = trim(($current['context'] ?? '').PHP_EOL.$line);
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return $entries;
    }

    private function countErrorsInFile(string $path): int
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return 0;
        }

        $count = 0;

        while (($line = fgets($handle)) !== false) {
            if (preg_match(self::LOG_LINE_PATTERN, $line, $matches)
                && in_array($matches['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
                $count++;
            }
        }

        fclose($handle);

        return $count;
    }

    private function extractDateFromFilename(string $filename): ?string
    {
        if (preg_match('/-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
