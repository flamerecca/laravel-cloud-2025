<div class="p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-4">撲克牌花色</h1>

    <ul class="space-y-2">
        @foreach ($cards as $card)
            <li class="text-xl">
                <span class="mr-2">{{ $card['symbol'] }}</span> {{ $card['name'] }}
            </li>
        @endforeach
    </ul>
</div>
