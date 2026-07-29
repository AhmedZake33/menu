<?php

namespace App\Mail;

use App\Models\MenuOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MenuOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MenuOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تأكيد طلبك من '.$this->order->restaurant->name.' - #'.$this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.menu-order-confirmation');
    }
}
