<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;

function renderedIssueMail(string $body): array
{
    $issue = NewsletterIssue::factory()->create([
        'topic' => NewsletterTopic::Filament,
        'title' => '卡關不是你不熟框架',
        'body' => $body,
    ]);
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $mail = new NewsletterIssueMail($issue, $subscriber);

    return [$mail, $mail->render(), $subscriber];
}

test('信件主旨是期數標題', function () {
    [$mail] = renderedIssueMail('內文');

    $mail->assertHasSubject('卡關不是你不熟框架');
});

test('Markdown 內文會轉成 HTML', function () {
    [, $html] = renderedIssueMail("## 這是小標\n\n第一段內容");

    expect($html)->toContain('<h2')->toContain('這是小標')->toContain('第一段內容');
});

test('信件底部有該訂閱者的退訂連結', function () {
    [, $html, $subscriber] = renderedIssueMail('內文');

    expect($html)->toContain('/newsletter/unsubscribe')
        ->toContain('signature=')
        ->toContain(e($subscriber->unsubscribeUrl()));
});

test('內文中的 script 與 javascript 連結不會出現在信件中', function () {
    [, $html] = renderedIssueMail("<script>alert(1)</script>\n\n[點我](javascript:alert(1))");

    expect($html)->not->toContain('<script')->not->toContain('javascript:');
});

test('信件帶有 List-Unsubscribe 與一鍵退訂標頭', function () {
    [$mail] = renderedIssueMail('內文');

    $headers = $mail->headers();

    expect($headers->text['List-Unsubscribe'])->toContain('/newsletter/unsubscribe')
        ->and($headers->text['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click');
});

test('電子報信件以 newsletter@reccachao.net 寄出', function () {
    [$mail] = renderedIssueMail('內文');

    $mail->assertFrom('newsletter@reccachao.net', 'Recca Chao');
});
