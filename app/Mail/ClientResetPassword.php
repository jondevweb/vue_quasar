<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientResetPassword extends Mailable
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
        return $this->subject('Réinitialisation de mot de passe sur ICI ON RECYCLE !')->view('mails.account.accountresetpassword', [
            'token'  => $this->token,
            'email'  => $this->email,
            'target' => 'clients',
            'base'   => config('app.url')
            ]);
    }
}
