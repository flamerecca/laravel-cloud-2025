<x-mail::message>
# {{ $title }}

{!! $bodyHtml !!}

---

不想再收到這個主題的電子報，可以 [取消訂閱]({{ $unsubscribeUrl }})。

{{ config('mail.newsletter_name') }}
</x-mail::message>
