<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * MOM 17 Nov 2026 p1: email ke Finance setelah admin memproses pencairan komisi.
 * Isi minimal sesuai MOM: nama admin, total nominal komisi yang dicentang, jumlah marketing.
 */
class CommissionPayoutProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, count: int, amount: float}>  $marketingRows
     */
    public function __construct(
        public string $adminName,
        public float $totalAmount,
        public int $marketingCount,
        public array $marketingRows,
        public string $paymentDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pencairan Komisi Diproses - '.$this->marketingCount.' marketing, Rp '.number_format($this->totalAmount, 0, ',', '.'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.commission-payout-processed');
    }
}
