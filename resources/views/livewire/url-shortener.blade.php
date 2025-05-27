<div class="p-6 bg-white rounded shadow">
    <input type="text"
           wire:model="originalUrl"
           class="w-full border rounded p-2"
           placeholder="請輸入原始網址..."/>

    @error('originalUrl')
    <div class="text-red-500 text-sm">{{ $message }}</div>
    @enderror
    <br/>
    <button wire:click="generateShortUrl"
            class="px-4 py-2 rounded">
        產生短網址
    </button>
    <br/>
    @if ($shortUrl)
        <div>
            <label for="link" class="font-semibold">短網址：</label>
            <a id="link"
               href="{{ $shortUrl }}"
               target="_blank">{{ $shortUrl }}</a>
        </div>
    @endif
</div>
