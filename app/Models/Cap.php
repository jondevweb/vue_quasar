<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Dechet;
use App\Models\Client;

class Cap extends Model
{
    use HasFactory;
    protected $fillable = [
        'document',
        'dechet_id'
    ];
    public function dechet()
    {
        return $this->belongsTo(Dechet::class);
    }
    public function client()
    {
        return $this->belongsToMany(Client::class);
    }
    public static function capModelsLocation(string $subDir = '') {
        return  \Storage::disk('capmodel')->path($subDir);
    }

    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['document' => 'max:255', 'dechet_id' => 'numeric|required'];

        if (!$withRequired)
            $retour = ['document' => 'max:255', 'dechet_id' => 'numeric'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(&$fillable, &$dechet_id) {
        return DB::transaction(function ()   use (&$fillable, &$dechet_id) {
            $doc = Cap::create(array_merge($fillable, ['dechet_id' => $dechet_id]));
            return $doc->id;
        });
    }
}
