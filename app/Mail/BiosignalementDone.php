<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BiosignalementDone extends Mailable
{
    use Queueable, SerializesModels;
    private string $target;
    private string $msg;
    private array $pictures;
    private float $latitude;
    private float $longitude;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $target, string $msg, array $pictures, float $latitude, float $longitude)
    {
        $this->target   = $target;
        $this->msg      = $msg;
        $this->pictures = $pictures;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $cpt = 0;
        $retour = $this->subject('Un signalement a été réalisé lors de sa tournée')->view('mails.integrateurs.biosignalementdone', [
            'target'  => $this->target,
            'msg'     => $this->msg,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ]);
        foreach($this->pictures as &$value) {
            $retour = $retour->attach($value, [
                'as' => $cpt.'.jpg',
                'mime' => 'application/jpg',
            ]);
        }
        return $retour;
    }
}
