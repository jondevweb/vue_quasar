<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Integrateur;
use App\Helpers\IorModelTraits;

class Vehicule extends Model
{
    use HasFactory, IorModelTraits;
    protected $fillable = [
        'integrateur_id',
        'immatriculation',
        'nom',
        'description',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['nom' => 'required|max:255', 'immatriculation' => 'required|max:45', 'description' => 'max:255', 'integrateur_id' => 'numeric'];

        if (!$withRequired)
            $retour = ['nom' => 'max:255', 'immatriculation' => 'max:45', 'description' => 'max:255', 'integrateur_id' => 'numeric'];

        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }

    public function integrateur()
    {
        return $this->belongsTo(Integrateur::class);
    }
}
