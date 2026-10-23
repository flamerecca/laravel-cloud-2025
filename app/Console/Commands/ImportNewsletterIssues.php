<?php

namespace App\Console\Commands;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportNewsletterIssues extends Command
{
    protected $signature = 'newsletter:import-issues
        {path? : Directory of 第 NN 期：標題.md files, defaults to the folder of the topic under docs}
        {--topic=filament : Topic slug or value, such as filament, fastapi, ktor, exposed or spring-boot}';

    protected $description = 'Import newsletter issues of a topic from Markdown files';

    public function handle(): int
    {
        $topic = NewsletterTopic::fromSlug((string) $this->option('topic'));

        if ($topic === null) {
            $this->components->error("未知的主題：{$this->option('topic')}");

            return self::FAILURE;
        }

        $path = $this->argument('path') ?? $this->defaultPath($topic);

        if ($path === null) {
            $this->components->error("{$topic->title()} 沒有預設目錄，請指定 path");

            return self::FAILURE;
        }

        if (! File::isDirectory($path)) {
            $this->components->error("找不到目錄：{$path}");

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach (File::files($path) as $file) {
            // Files are named either 第 01 期：標題.md or, with underscores for spaces, 第_01_期：標題.md.
            if (! preg_match('/^第([ _])(\d+)[ _]期：(.+)\.md$/u', $file->getFilename(), $matches)) {
                continue;
            }

            $title = $matches[1] === '_' ? str_replace('_', ' ', $matches[3]) : $matches[3];

            [$summary, $body] = $this->parse(File::get($file->getPathname()));

            $number = (int) $matches[2];
            $existing = NewsletterIssue::query()
                ->where('topic', $topic->value)
                ->where('number', $number)
                ->first();

            if ($existing?->isDispatched()) {
                $skipped++;

                continue;
            }

            NewsletterIssue::query()->updateOrCreate(
                ['topic' => $topic->value, 'number' => $number],
                ['title' => $title, 'summary' => $summary, 'body' => $body],
            );

            $imported++;
        }

        $this->components->info("已匯入 {$imported} 期，略過已派送 {$skipped} 期");

        return self::SUCCESS;
    }

    /**
     * Where the planned issues of a topic live under docs, or null when the topic has no folder yet.
     */
    private function defaultPath(NewsletterTopic $topic): ?string
    {
        return match ($topic) {
            NewsletterTopic::Filament => base_path('docs/Newsletter/FilamentNewsletter'),
            NewsletterTopic::Exposed => base_path('docs/Newsletter/ExposedNewsletter'),
            NewsletterTopic::FastApi => base_path('docs/Newsletter/FastAPINewsletter'),
            NewsletterTopic::Ktor => base_path('docs/Newsletter/KtorTipsNewsletter'),
            NewsletterTopic::SpringBoot => base_path('docs/SpringBootNewsletter'),
            NewsletterTopic::Laravel => null,
        };
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
