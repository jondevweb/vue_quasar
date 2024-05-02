<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bionettoyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'transporteur_id',
        'biopointcollecte_id',
        'commentaire',
        'coordonnees',
        'debut',
        'fin',
        'photo',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['transporteur_id' => 'required|integer', 'biopointcollecte_id' => 'required|integer', 'commentaire'
                 , 'coordonnees' => 'required|regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                 , 'debut' => 'required|date', 'fin' => 'nullable|date', 'photo' => 'max:1024'];

        if (!$withRequired)
            $retour = ['transporteur_id' => 'integer', 'biopointcollecte_id' => 'integer', 'commentaire'
                     , 'coordonnees' => 'regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                     , 'debut' => 'date', 'fin' => 'nullable|date', 'photo' => 'max:1024'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
}
