<?php

namespace App\Mail;

use App\Models\NewsletterBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsletterBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly NewsletterBroadcast $broadcast) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.newsletter_from.address'), config('mail.newsletter_from.name')),
            subject: $this->broadcast->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.broadcast',
            with: [
                'title' => $this->broadcast->title,
                'summary' => $this->broadcast->summary,
                // Written by an admin, but never trusted as HTML: raw HTML and unsafe links are dropped.
                'bodyHtml' => new HtmlString(Str::markdown($this->broadcast->body, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])),
            ],
        );
    }
}
