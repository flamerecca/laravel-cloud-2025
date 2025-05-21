<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\Models\ShortUrl;
use Illuminate\Support\Str;

class UrlShortener extends Component
{
    public string $originalUrl = '';
    public ?string $shortUrl = null;

    public function generateShortUrl(): void
    {
        $this->validate([
            'originalUrl' => ['required', 'url']
        ]);

        // 檢查是否已存在
        $existing = ShortUrl::where('original_url', $this->originalUrl)->first();
        if ($existing) {
            $this->shortUrl = url('/s/' . $existing->slug);
            return;
        }

        $slug = Str::random(10);
        while (ShortUrl::where('slug', $slug)->exists()) {
            $slug = Str::random(10);
        }

        ShortUrl::create([
            'original_url' => $this->originalUrl,
            'slug' => $slug,
        ]);

        $this->shortUrl = url('/s/' . $slug);
    }

    public function render(): View
    {
        return view('livewire.url-shortener');
    }
}
