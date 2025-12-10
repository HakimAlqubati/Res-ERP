<?php

namespace App\Mail;

use App\Services\Warnings\WarningPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $data;
    public string $userName;
    public ?Authenticatable $user;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public WarningPayload $payload,
        ?Authenticatable $user = null
    ) {
        $this->user = $user;
        $this->data = $payload->toDatabaseArray();
        $this->userName = $user?->name ?? 'User';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $levelEmoji = match ($this->data['level'] ?? 'info') {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        Log::info('Sending warning email', [
            'user_id' => $this->user?->id ?? null,
            'email' => $this->user?->email ?? null,
            'title' => $this->data['title'] ?? 'System Warning',
            'level' => $this->data['level'] ?? 'info',
        ]);
        return new Envelope(
            subject: $levelEmoji . ' ' . ($this->data['title'] ?? 'System Warning'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.warning',
            with: [
                'title' => $this->data['title'] ?? 'Warning',
                'body' => $this->data['detail'] ?? $this->data['body'] ?? '',
                'level' => $this->data['level'] ?? 'info',
                'context' => $this->data['ctx'] ?? [],
                'userName' => $this->userName,
                'url' => $this->data['url'] ?? $this->data['link'] ?? null,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
