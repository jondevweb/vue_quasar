<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IntegrateurCAPUpdated extends Mailable
{
    use Queueable, SerializesModels;
    private $raison_sociale = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($raison_sociale)
    {
        $this->raison_sociale = $raison_sociale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Un client a déposé un CAP : « '.$this->raison_sociale.' »')->view('mails.integrateurs.capupdated', [
            'raison_sociale' => $this->raison_sociale,
        ]);
    }
}
