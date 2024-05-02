<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biopointcollecte extends Model
{
    use HasFactory;
    protected $fillable = [
        'bioclient_id',
        'adresse',
        'coordonnees',
        'commentaire',
        'nom'
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['bioclient_id' => 'required|integer', 'commentaire', 'adresse' => 'required'
                 , 'coordonnees' => 'required|regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'];

        if (!$withRequired)
            $retour = ['bioclient_id' => 'integer', 'commentaire', 'adresse'
                     , 'coordonnees' => 'regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $biopointcollecte = Biopointcollecte::create($fillable);
            return $biopointcollecte->id;
        });
    }
    static public function listByClients(array $client_ids)
    {
        return Biopointcollecte::join('bioclients' , 'biopointcollectes.bioclient_id', '=', 'bioclients.id')
                               ->join('entreprises', 'bioclients.entreprise_id'   , '=', 'entreprises.id')
                               ->whereRaw('bioclient_id IN('.implode(',', $client_ids).')')
                               ->select(['biopointcollectes.*', DB::raw('ST_AsText(biopointcollectes.coordonnees) AS coordonnees')
                                       , 'entreprises.raison_sociale', 'entreprises.siret'])
                               ->get();
    }
    static public function list(int $integrateur_id) {
        return  Biopointcollecte::join('bioclients', 'biopointcollectes.bioclient_id', 'bioclients.id')
                                ->where('bioclients.integrateur_id', $integrateur_id)
                                ->select('biopointcollectes.*', DB::raw('ST_AsText(biopointcollectes.coordonnees) AS coordonnees'))
                                ->get();
    }
}
