<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttestationsGenerated extends Mailable
{
    use Queueable, SerializesModels;
    private $year   = 0;
    private $status = false;
    private $stats  = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(int $year, bool $status, string $stats)
    {
        $this->year   = $year;
        $this->status = $status;
        $this->stats  = $stats;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = "Génération des attestations de l'année ".$this->year.' terminée avec succès';
        if(!$this->status)
            $message = "La génération des attestations de l'année ".$this->year.' a échoué';
        return $this->subject($message)->view('mails.integrateurs.attestationfinished', [
            'status' => $this->status ? 'Succès' : 'Échec',
            'stats'  => $this->stats
            ]);
    }
}
