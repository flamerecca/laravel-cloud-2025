<x-mail::message>
# 請確認您的訂閱

感謝您訂閱「{{ $topicTitle }}」。請點擊下方按鈕確認這個 Email 屬於您，完成後才會開始收到電子報。

<x-mail::button :url="$confirmUrl">
確認訂閱
</x-mail::button>

確認連結將在 {{ $expiresInDays }} 天後失效。如果您沒有申請訂閱，請直接忽略這封信，我們不會再寄信給您。

{{ config('app.name') }}
</x-mail::message>
