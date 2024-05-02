<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bioconteneur extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom',
        'coordonnees',
        'statut',
        'biopointcollecte_id',
        'integrateur_id'
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['nom' => 'required', 'coordonnees' => 'required|regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                 , 'statut' => 'integer',  'biopointcollecte_id' => 'nullable|integer',  'integrateur_id' => 'required|integer'];

        if (!$withRequired)
            $retour = ['nom', 'coordonnees' => 'regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                     , 'statut' => 'integer',  'biopointcollecte_id' => 'nullable|integer',  'integrateur_id' => 'integer'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $bioconteneur = Bioconteneur::create($fillable);
            return $bioconteneur->id;
        });
    }
    public function list(int $integrateur_id) {
        return Bioconteneur::where('integrateur_id', $integrateur_id)
                           ->select(['nom', 'statut', 'biopointcollecte_id'
                                  , DB::raw('ST_AsText(coordonnees) AS coordonnees'), DB::raw('X(coordonnees) AS x'), DB::raw('Y(coordonnees) AS y')])->get();
    }
    static public function listWithBiopointcollecte(int $integrateur_id) {
        return Bioconteneur::leftJoin('biopointcollectes AS BPC', 'bioconteneurs.biopointcollecte_id', 'BPC.id')
                            ->where('integrateur_id', $integrateur_id)
                            ->select(['bioconteneurs.id AS bioconteneur_id', 'bioconteneurs.nom', 'statut', 'biopointcollecte_id'
                                  , DB::raw('ST_AsText(bioconteneurs.coordonnees) AS coordonnees')
                                  , DB::raw('X(bioconteneurs.coordonnees) AS x'), DB::raw('Y(bioconteneurs.coordonnees) AS y')
                                  , 'bioclient_id', 'commentaire', 'adresse', 'date_coordonnees'
                                  , DB::raw('ST_AsText(BPC.coordonnees) AS biopointcollecte_coordonnees')
                                  , DB::raw('X(BPC.coordonnees) AS biopointcollecte_x'), DB::raw('Y(BPC.coordonnees) AS biopointcollecte_y')])->get();
    }
    static public function collectes(int $integrateur_id, int $bioconteneur_id) {
        return Bioconteneur::join('biocollectes      AS BCl', 'BCl.bioconteneur_id'   , 'bioconteneurs.id')
                           ->join('biopointcollectes AS BPC', 'BCl.biopointcollecte_id', 'BPC.id')
                           ->where('bioconteneurs.integrateur_id', $integrateur_id)
                           ->where('bioconteneurs.id', $bioconteneur_id)
                           ->select([ DB::raw('ST_AsText(BCl.coordonnees) AS coordonnees')
                                    , 'BPC.adresse', 'BCl.commentaire', 'BCl.nettoyage', 'BCl.poids', 'BCl.pesee', 'BCl.enlevement', 'BCl.depot'])
                           ->get();
    }
}
