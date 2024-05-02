<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Parametre extends Model
{
    use HasFactory;
    protected $fillable = [
        'notification_pesee',
    ];
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['notification_pesee' => 'bool'];

        if (!$withRequired)
            $retour = ['notification_pesee' => 'bool'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
}
