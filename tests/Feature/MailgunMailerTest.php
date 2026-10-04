<?php

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunHttpTransport;

test('mailgun mailer 使用 Mailgun HTTP transport 並讀取 services 設定', function () {
    config([
        'services.mailgun.domain' => 'mg.example.com',
        'services.mailgun.secret' => 'test-secret',
        'services.mailgun.endpoint' => 'api.eu.mailgun.net',
    ]);

    $transport = Mail::mailer('mailgun')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(MailgunHttpTransport::class)
        ->and((string) $transport)->toContain('api.eu.mailgun.net')
        ->and((string) $transport)->toContain('mg.example.com');
});
