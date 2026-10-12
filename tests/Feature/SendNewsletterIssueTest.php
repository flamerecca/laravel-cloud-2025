<?php

use App\Enums\NewsletterTopic;
use App\Jobs\SendNewsletterIssue;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->issue = NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament]);
    $this->subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
});

test('寄出信件並寫入寄送紀錄', function () {
    (new SendNewsletterIssue($this->issue->id, $this->subscriber->id))->handle();

    Mail::assertSent(NewsletterIssueMail::class, fn (NewsletterIssueMail $mail) => $mail->hasTo($this->subscriber->email));
    expect(NewsletterDelivery::where('newsletter_issue_id', $this->issue->id)->count())->toBe(1);
});

test('重試時已有寄送紀錄就不再寄送', function () {
    $job = new SendNewsletterIssue($this->issue->id, $this->subscriber->id);

    $job->handle();
    $job->handle();

    Mail::assertSent(NewsletterIssueMail::class, 1);
    expect(NewsletterDelivery::count())->toBe(1);
});

test('寄出前已退訂的訂閱者不會收到信', function () {
    $this->subscriber->update(['unsubscribed_at' => now()]);

    (new SendNewsletterIssue($this->issue->id, $this->subscriber->id))->handle();

    Mail::assertNothingSent();
    expect(NewsletterDelivery::count())->toBe(0);
});

test('期數或訂閱者已被刪除時直接結束', function () {
    (new SendNewsletterIssue(999999, $this->subscriber->id))->handle();
    (new SendNewsletterIssue($this->issue->id, 999999))->handle();

    Mail::assertNothingSent();
});
