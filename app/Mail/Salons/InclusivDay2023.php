<?php

namespace App\Mail\Salons;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InclusivDay2023 extends Mailable
{
    use Queueable, SerializesModels;
    private $mail = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mail)
    {
        $this->mail = $mail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Inclusiv'Day - Plaquettes de présentation de Triethic")
                    ->view('mails.salons.2023_inclusivday', ['mail' => $this->mail])
                    ->attach(public_path().'/data/salons/inclusivday2023/Vimethic.pdf')
                    ->attach(public_path().'/data/salons/inclusivday2023/Casquethic.pdf')
                    ->attach(public_path().'/data/salons/inclusivday2023/Chaussethic.pdf')
                    ->attach(public_path().'/data/salons/inclusivday2023/Présentation Triethic - Générale.pdf');
    }
}
