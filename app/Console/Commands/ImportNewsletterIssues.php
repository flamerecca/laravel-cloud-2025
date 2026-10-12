<?php

namespace App\Console\Commands;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportNewsletterIssues extends Command
{
    protected $signature = 'newsletter:import-issues {path? : Directory of 第 NN 期：標題.md files}';

    protected $description = 'Import Filament newsletter issues from Markdown files';

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('docs/FilamentNewsletter');

        if (! File::isDirectory($path)) {
            $this->components->error("找不到目錄：{$path}");

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach (File::files($path) as $file) {
            if (! preg_match('/^第 (\d+) 期：(.+)\.md$/u', $file->getFilename(), $matches)) {
                continue;
            }

            [$summary, $body] = $this->parse(File::get($file->getPathname()));

            $number = (int) $matches[1];
            $existing = NewsletterIssue::query()
                ->where('topic', NewsletterTopic::Filament->value)
                ->where('number', $number)
                ->first();

            if ($existing?->isDispatched()) {
                $skipped++;

                continue;
            }

            NewsletterIssue::query()->updateOrCreate(
                ['topic' => NewsletterTopic::Filament->value, 'number' => $number],
                ['title' => $matches[2], 'summary' => $summary, 'body' => $body],
            );

            $imported++;
        }

        $this->components->info("已匯入 {$imported} 期，略過已派送 {$skipped} 期");

        return self::SUCCESS;
    }

    /**
     * Split a file into its front matter summary and the body without the leading title.
     *
     * @return array{0: string|null, 1: string}
     */
    private function parse(string $contents): array
    {
        $summary = null;
        $body = $contents;

        if (preg_match('/\A---\R(.*?)\R---\R?(.*)\z/su', $contents, $parts)) {
            if (preg_match('/^summary:\s*(.+)$/mu', $parts[1], $match)) {
                $summary = trim($match[1]);
            }

            $body = $parts[2];
        }

        $body = preg_replace('/\A\s*#\s+[^\n]*\R/u', '', ltrim($body)) ?? $body;

        return [$summary, Str::of($body)->trim()->toString()];
    }
}
