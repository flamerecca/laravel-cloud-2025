<?php

namespace App\Mail;

use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsletterIssueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly NewsletterIssue $issue,
        public readonly NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->issue->title);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.issue',
            with: [
                'title' => $this->issue->title,
                'summary' => $this->issue->summary,
                // Written by an admin, but never trusted as HTML: raw HTML and unsafe links are dropped.
                'bodyHtml' => new HtmlString(Str::markdown($this->issue->body, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
