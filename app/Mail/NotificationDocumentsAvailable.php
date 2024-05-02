<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationDocumentsAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function build()
    {
        return $this->subject('Un ou des documents disponibles sur ICI ON RECYCLE !')->view('mails.clients.notification_documents_available', [
            'base'  => config('app.url')
            ]);
    }
}
