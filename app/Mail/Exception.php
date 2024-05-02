<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Raw extends Mailable
{
    use Queueable, SerializesModels;
    private $_from = '';
    private $_subject = '';
    private $_body = '';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $from, string $subject, string $body)
    {
        if ($from == '') 
            $this->_from = config('mail.from.address');
        else
            $this->_from    = $from;
        $this->_subject = $subject;
        $this->_body    = $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->_from === '')
            return $this->subject($this->_subject)->view('mails.raw')->with(['body' => $this->_body]);
        else
            return $this->from($this->_from)->subject($this->_subject)->view('mails.raw')->with(['body' => $this->_body]);
    }
}
