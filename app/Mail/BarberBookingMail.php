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
    public function __construct($data)
    {
        $this->user = auth()->user();
        if(auth()->user()->getTable()=='admins') $this->user = User::find(request()->user);
        $this->barber = ServiceSlot::with('service.barber')->find($data->slot_id)->service->barber;
        $this->service = ServiceSlot::find($data->slot_id)->service;
        $this->slot = ServiceSlot::find($data->slot_id);
        $this->date = $data->date;
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
