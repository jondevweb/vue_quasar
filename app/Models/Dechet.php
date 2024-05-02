<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Integrateur;
use App\Models\Exutoire;
use App\Helpers\IorModelTraits;

class Dechet extends Model
{
    use HasFactory, IorModelTraits;
    protected $fillable = [
        'nom',
        'trigramme',
        'photo',
        'couleur',
        'ordre_affichage',
        'rubrique',
        'code_traitement',
        'attestation_type',
        'integrateur_id',
        'equivalence_coefficient',
        'equivalence_nom',
        'equivalence_photo',
        'affiche_tri',
        'affiche_communication',
        'affiche_valorisation',
    ];
    public function integrateur()
    {
        return $this->belongsTo(Integrateur::class);
    }
    public function exutoire()
    {
        return $this->belongsToMany(Exutoire::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['nom' => 'required|max:255',
                   'trigramme' => 'required|max:3', 'photo' => 'max:255', 'ordre_affichage' => 'numeric', 'code_traitement' => 'max:45',
                   'rubrique' => 'max:45', 'attestation_type' => 'numeric', 'integrateur_id' => 'numeric', 'couleur' => 'max:45'];

        if(!$withRequired)
            $retour = ['nom' => 'max:255',
                       'trigramme' => 'max:3', 'photo' => 'max:255', 'ordre_affichage' => 'numeric', 'code_traitement' => 'max:45',
                       'rubrique' => 'max:45', 'attestation_type' => 'numeric', 'integrateur_id' => 'numeric', 'couleur' => 'max:45'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function lookForExutoire(int $dechet_id) {
        return Dechet::with(['exutoire', 'exutoire.entreprise'])->where('id', '=', $dechet_id)->get();
    }
    public function lookForIntegrateur(int $integrateur_id) {
        return Dechet::where('integrateur_id', '=', $integrateur_id)->get();
    }
}

