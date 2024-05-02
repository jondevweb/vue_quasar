<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\IorModelTraits;

class Entreprise extends Model
{
    use HasFactory, IorModelTraits;
    protected $fillable = [
        'raison_sociale',
        'siret',
        'adresse_administrative'
    ];
    private $defaultLook = 'raison_sociale';
    static public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['raison_sociale' => 'required|max:255', 'siret' => 'required|max:17', 'adresse_administrative' => 'max:255'];
        if (!$withRequired)
            $retour = ['raison_sociale' => 'max:255', 'siret' => 'max:17', 'adresse_administrative' => 'max:255'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);


        return $retour;
    }
    /*public function look(string $pattern, string $fieldName = 'raison_sociale') {
        if (!in_array($fieldName, $this->fillable)) {
            \Log::warning('Asked to look for a field that is not allowed! field='.$fieldName.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        return Entreprise::where($fieldName,'LIKE', '%'.$pattern.'%')->get();
    }*/
}
