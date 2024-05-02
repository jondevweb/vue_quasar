<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmPassword extends Mailable
{
    use Queueable, SerializesModels;
    private $email    = '';
    private $password = '';
    private $target   = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $password, $target)
    {
        $this->email    = $email;
        $this->password = $password;
        $this->target   = $target;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Création de votre mot de passe sur ICI ON RECYCLE !')->view('mails.account.accountconfirmpassword', [
            'password'  => $this->password,
            'email'     => $this->email,
            'target'    => $this->target,
            'base'      => config('app.url')
            ]);
        return $this->view('view.name');
    }
}
