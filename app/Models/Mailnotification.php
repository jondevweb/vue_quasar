<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Mailnotification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['user_id' => 'required|numeric', 'type' => 'numeric', 'realise' => 'date|nullable'
        ];

        if (!$withRequired)
            $retour = ['user_id' => 'numeric', 'type' => 'numeric', 'realise' => 'date|nullable'];

        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $mailnotification = Mailnotification::create($fillable);
            return $mailnotification->id;
        });
    }
}
