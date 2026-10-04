<?php

namespace App\Mail;

use App\Enums\NewsletterTopic;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * How many days the confirmation link stays valid.
     */
    public const LINK_EXPIRES_IN_DAYS = 7;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly string $email,
        public readonly ?NewsletterTopic $topic = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "請確認訂閱「{$this->topicTitle()}」",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.confirmation',
            with: [
                'topicTitle' => $this->topicTitle(),
                'confirmUrl' => $this->confirmUrl(),
                'expiresInDays' => self::LINK_EXPIRES_IN_DAYS,
            ],
        );
    }

    /**
     * The signed link that confirms this subscription.
     */
    public function confirmUrl(): string
    {
        return URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addDays(self::LINK_EXPIRES_IN_DAYS),
            array_filter(['email' => $this->email, 'topic' => $this->topic?->value]),
        );
    }

    private function topicTitle(): string
    {
        return $this->topic?->title() ?? config('app.name').' 電子報';
    }
}
