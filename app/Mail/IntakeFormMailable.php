<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IntakeFormMailable extends Mailable
{
    use Queueable, SerializesModels;

    public array $participant;
    public string $pdfContent;
    public string $filename;

    /**
     * Create a new message instance.
     */
    public function __construct(array $participant, string $pdfContent, string $filename)
    {
        $this->participant  = $participant;
        $this->pdfContent   = $pdfContent;
        $this->filename     = $filename;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Intake Form for '.($this->participant->full_name ?? 'Participant')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.intake-form',
            with: [
                'participant' => $this->participant,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                $this->filename
            )->withMime('application/pdf')
        ];
    }
}
