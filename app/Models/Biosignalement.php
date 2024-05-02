<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biosignalement extends Model
{
    use HasFactory;
    protected $fillable = [
        'biopointcollecte_id',
        'bioconteneur_id',
        'coordonnees',
        'date',
        'photos',
        'message',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['biopointcollecte_id' => 'nullable|integer', 'bioconteneur_id' => 'nullable|integer',
                   'photo|max:1024', 'message|max:1024'];

        if (!$withRequired)
            $retour = ['biopointcollecte_id' => 'nullable|integer', 'bioconteneur_id' => 'nullable|integer',
                       'photo|max:1024', 'message|max:1024'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $biosignalement = Biosignalement::create($fillable);
            return $biosignalement->id;
        });
    }
    static public function list(int $integrateur_id, string $startDate, string $endDate, int $offset = 0, int $limit = 10, int $bioclient_id = null, int $biopointcollecte_id = null) {
        $sql = <<<EOF
        FROM biosignalements S
        LEFT JOIN biopointcollectes PC ON S.biopointcollecte_id = PC.id
        LEFT JOIN bioclients        Cl ON PC.bioclient_id       = Cl.id AND Cl.integrateur_id = ?
        LEFT JOIN bioconteneurs     C  ON S.bioconteneur_id     = C.id  AND C.integrateur_id  = ?
        WHERE S.date BETWEEN ? AND ?
        EOF;
        if ($bioclient_id        != null) $request .= ' AND PC.bioclient_id = '.$bioclient_id;
        if ($biopointcollecte_id != null) $request .= ' AND C.biopointcollecte_id = '.$biopointcollecte_id;
        $result = DB::select('SELECT COUNT(S.id) as total '. $sql, [$integrateur_id, $integrateur_id, $startDate, $endDate]);
        $nb = $result[0]->total;
        $sql = 'SELECT S.*, S.id AS biosignalement_id, PC.nom AS biopointcollecte_nom, PC.adresse, ST_AsText(S.coordonnees) AS coordonnees, C.nom AS bioconteneur_nom '
               .$sql;
        return ['total' => $nb, 'rows' => DB::select($sql.' LIMIT '.$limit.' OFFSET '.$offset, [$integrateur_id, $integrateur_id, $startDate, $endDate])];
    }
}
