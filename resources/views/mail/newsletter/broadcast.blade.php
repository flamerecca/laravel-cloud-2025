<x-mail::message>
# {{ $title }}

{!! $bodyHtml !!}

{{ config('mail.newsletter_name') }}
</x-mail::message>
