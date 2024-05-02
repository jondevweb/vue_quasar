<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;
use App\Models\Dechet;
use App\Models\Mobilier;
use App\Helpers\IorModelTraits;

class Transporteur extends Model
{
    use HasFactory, IorModelTraits;
    protected $fillable = [
        'recepisse',
        'limite_recepisse',
        'contact',
        'email',
        'telephone',
        'departement',
        'entreprise_id',
    ];
    private $defaultLook = 'nom';
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['recepisse' => 'max:45', 'limite_recepisse' => 'required|datetime', 'contact' => 'max:255'
                 , 'email' => 'max:255', 'telephone' => 'max:45', 'departement' => 'max:45', 'entreprise_id' => 'required|int'];
        if(!$withRequired)
            $retour = ['recepisse' => 'max:45', 'limite_recepisse' => 'datetime', 'contact' => 'max:255'
                     , 'email' => 'max:255', 'telephone' => 'max:45', 'departement' => 'max:45', 'entreprise_id' => 'int'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

}
