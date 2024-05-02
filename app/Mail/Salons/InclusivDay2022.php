<?php

namespace App\Mail\Salons;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InclusivDay2022 extends Mailable
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
                    ->view('mails.salons.2022_inclusivday', ['mail' => $this->mail])
                    ->attach(public_path().'/data/salons/inclusivday2022/Vimethic.pdf')
                    ->attach(public_path().'/data/salons/inclusivday2022/Casquethic.pdf')
                    ->attach(public_path().'/data/salons/inclusivday2022/Présentation Triethic - Générale.pdf');
    }
}
