<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Mail extends Model
{
    use HasFactory;
    protected $fillable = [
        'soumission',
        'sent',
        'from',
        'to',
        'cc',
        'bcc',
        'replyto',
        'subject',
        'body',
        'history',
        'attachments',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['soumission' => 'date', 'sent' => 'date', 'from' => 'required|max:255', 'cc' => 'max:255', 'bcc' => 'max:255', 'subject' => 'max:255', 'replyto' => 'max:255'
                   , 'subject' => 'required|max:255', 'body' => 'required|max:1024', 'history' => 'max:1024', 'attachments' => 'max:255'
        ];

        if (!$withRequired)
            $retour = ['soumission' => 'date', 'sent' => 'date', 'from' => 'max:255', 'cc' => 'max:255', 'bcc' => 'max:255', 'subject' => 'max:255', 'replyto' => 'max:255'
            , 'subject' => 'max:255', 'body' => 'max:1024', 'history' => 'max:1024', 'attachments' => 'max:255'
        ];

        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $mail = Mail::create($fillable);
            return $mail->id;
        });
    }
}
