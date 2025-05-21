<div class="max-w-xl mx-auto p-6 bg-white shadow rounded-lg space-y-4">
    <input type="text" wire:model="originalUrl"
           class="w-full border rounded p-2"
           placeholder="請輸入原始網址..." />

    @error('originalUrl')
    <div class="text-red-500 text-sm">{{ $message }}</div>
    @enderror

    <button wire:click="generateShortUrl"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
        產生短網址
    </button>

    @if ($shortUrl)
        <div class="mt-4">
            <label class="font-semibold">短網址：</label>
            <a href="{{ $shortUrl }}" target="_blank" class="text-blue-600 underline">{{ $shortUrl }}</a>
        </div>
    @endif
</div>
