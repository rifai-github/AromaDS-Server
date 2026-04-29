<?php

namespace App\Mail;

use App\Models\CustomerContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public CustomerContact $contact;
    public string $verificationUrl;
    public string $customerName;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerContact $contact)
    {
        $this->contact = $contact;
        $this->verificationUrl = route('email.verify', ['token' => $contact->email_verification_token]);
        $this->customerName = $contact->customer?->name ?? 'Customer';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Email untuk Penerimaan Invoice - PT Aroma Sehat Sejahtera',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.email-verification',
            with: [
                'contact' => $this->contact,
                'verificationUrl' => $this->verificationUrl,
                'customerName' => $this->customerName,
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
