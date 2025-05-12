@foreach ($shortUrls as $shortUrl)
    <p>
        <strong>短網址:</strong> {{ $shortUrl->slug }}<br />
        <strong>原始網址:</strong> {{ $shortUrl->original_url }}<br />
        <strong>點擊數:</strong> {{ $shortUrl->clicks->count() }}<br />
    </p>
@endforeach
