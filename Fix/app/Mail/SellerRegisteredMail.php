<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $storeName,
        public string $userEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Selamat! Kamu Resmi Jadi Seller - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seller-registered',
            with: [
                'userName'  => $this->userName,
                'storeName' => $this->storeName,
                'userEmail' => $this->userEmail,
            ]
        );
    }
}