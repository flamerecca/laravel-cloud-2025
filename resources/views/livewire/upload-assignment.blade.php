<div>
    @if ($successMessage)
        <div class="alert alert-success">{{ $successMessage }}</div>
    @endif

    <form wire:submit.prevent="submit">
        <div>
            <label for="name">姓名</label>
            <input type="text" id="name" wire:model="name">
            @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" wire:model="email">
            @error('email') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="file">作業檔案（PDF）</label>
            <input type="file" id="file" wire:model="file" accept="application/pdf">
            @error('file') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit">上傳</button>
        </div>
    </form>
</div>
