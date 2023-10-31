<?php

namespace App\Mail;

use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BarberBookingMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user, $barber, $service, $slot, $date;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($metadata)
    {
        $this->user = User::find($metadata->user);
        $this->barber = ServiceSlot::with('service.barber')->find($metadata->slot)->service->barber;
        $this->service = ServiceSlot::find($metadata->slot)->service;
        $this->slot = ServiceSlot::find($metadata->slot);
        $this->date = $metadata->date;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Kaz Appointment',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mail.barber-booking-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
