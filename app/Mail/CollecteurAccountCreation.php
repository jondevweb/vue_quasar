<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollecteurAccountCreation extends Mailable
{
    use Queueable, SerializesModels;
    private $email = '';
    private $token = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $token)
    {   
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        return $this->subject('Bienvenue sur ICI ON RECYCLE !')->view('mails.collecteurs.accountcreation', [
            'token' => $this->token,
            'email' => $this->email,
            'target' => 'collecteurs',
            'base'  => config('app.url')
            ]);
    }
}
