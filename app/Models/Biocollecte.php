<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biocollecte extends Model
{
    use HasFactory;
    protected $fillable = [
        'bioconteneur_id',
        'biopointcollecte_id',
        'commentaire',
        'coordonnees',
        'depot',
        'enlevement',
        'pesee',
        'nettoyage',
        'poids',
        'statut',
        'photo',
        'audio',
        'exutoire_id',
        'transporteur_id'
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['bioconteneur_id' => 'required|integer', 'biopointcollecte_id' => 'required|integer', 'commentaire'
                 , 'coordonnees' => 'required|regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                 , 'depot' => 'required|date', 'enlevement' => 'nullable|date', 'pesee' => 'nullable|date', 'nettoyage' => 'nullable|date'
                 , 'poids' => 'integer', 'statut' => 'integer', 'photo', 'audio', 'exutoire_id' => 'nullable|integer', 'transporteur_id' => 'required|integer'];

        if (!$withRequired)
            $retour = ['bioconteneur_id' => 'integer', 'biopointcollecte_id' => 'integer', 'commentaire'
                     , 'coordonnees' => 'regex:/^POINT\\(-?[0-9]+\\.[0-9]+ +-?[0-9]+\\.[0-9]+\\)$/i'
                     , 'depot' => 'date', 'enlevement' => 'nullable|date', 'pesee' => 'nullable|date', 'nettoyage' => 'nullable|date'
                     , 'poids' => 'integer', 'statut' => 'integer', 'photo', 'audio', 'exutoire_id' => 'nullable|integer', 'transporteur_id' => 'integer'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public static function pdfModelLocation() {
        return  \Storage::disk('diversmodel')->path('dac.pdf');
    }
    static public function list(int $integrateur_id, string $startDate, string $endDate, int $offset = 0, int $limit = 10, int $bioclient_id = null, int $biopointcollecte_id = null, bool $weight_notnull = false, bool $asc = false) {
        /*
SELECT C.id as biocollecte_id, C.commentaire, C.attestation, C.enlevement, C.pesee, C.poids
     , PC.bioclient_id, Cl.entreprise_id, E.raison_sociale, E.SIRET, E.adresse_administrative
     , C.biopointcollecte_id, PC.commentaire AS biopointcollecte_commentaire, ST_AsText(PC.coordonnees) AS biopointcollecte_coordonnees, PC.adresse AS biopointcollecte_adresse
     , C.exutoire_id, E.SIRET AS exutoire_siret, E.raison_sociale AS exutoire_raison_sociale, E.adresse_administrative AS exutoire_adresse
FROM biocollectes C
INNER JOIN biopointcollectes PC ON PC.id = C.biopointcollecte_id
INNER JOIN bioclients        Cl ON Cl.id = PC.bioclient_id
INNER JOIN entreprises       E  ON E.id  = Cl.entreprise_id
LEFT  JOIN exutoires         Ex ON Ex.id = C.exutoire_id
LEFT  JOIN entreprises       E2 ON E2.id = Ex.entreprise_id
WHERE statut = 50 AND C.enlevement BETWEEN '2022-09-20 00:00:00' AND '2022-09-30 23:59:59'
        */
        $request = DB::table('biocollectes     AS C')
                     ->join('biopointcollectes AS PC', 'PC.id', 'C.biopointcollecte_id')
                     ->join('bioclients        AS Cl', 'Cl.id', 'PC.bioclient_id')
                     ->join('entreprises       AS E' , 'E.id' , 'Cl.entreprise_id')
                     ->leftJoin('exutoires     AS Ex', 'Ex.id', 'C.exutoire_id')
                     ->leftJoin('entreprises   AS E2', 'E2.id', 'Ex.entreprise_id')
                     ->where('C.statut', 50)
                     ->where('Cl.integrateur_id', $integrateur_id)
                     ->whereBetween('C.enlevement', [$startDate, $endDate]);
        if ($bioclient_id        != null) $request = $request->where('PC.bioclient_id'      , $bioclient_id);
        if ($biopointcollecte_id != null) $request = $request->where('C.biopointcollecte_id', $biopointcollecte_id);
        if ($weight_notnull)              $request = $request->whereNotNull('C.pesee');
        $nb = $request->count();
        $request = $request->select(['C.id AS biocollecte_id', 'C.commentaire', 'C.attestation', 'C.enlevement', 'C.pesee', 'C.poids',
                                     'PC.bioclient_id', 'Cl.entreprise_id', 'E.raison_sociale', 'E.SIRET', 'E.adresse_administrative',
                                     'C.biopointcollecte_id', 'PC.commentaire AS biopointcollecte_commentaire', DB::raw('ST_AsText(PC.coordonnees) AS biopointcollecte_coordonnees'), 'PC.adresse AS biopointcollecte_adresse',
                                     'C.exutoire_id', 'E2.SIRET AS exutoire_siret', 'E2.raison_sociale AS exutoire_raison_sociale', 'E2.adresse_administrative AS exutoire_adresse',]);
        if ($asc) $request = $request->orderBy('C.enlevement', 'ASC');
        else $request = $request->orderBy('C.enlevement', 'DESC');

        return ['total' => $nb, 'rows' => $request->offset($offset)->limit($limit)->get()];
    }
}
