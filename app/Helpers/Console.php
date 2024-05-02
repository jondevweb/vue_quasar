<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as LDB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
class Console {
    static public function statsEnvironnement(string $startDate, string $endDate, array $pointcollecte_ids) {
        $first = DB::table('collectes      AS C')
            ->join('passages        AS P' , 'P.id' , '=', 'C.passage_id')
            ->join('dechet_exutoire AS DE',function($join){$join->on('DE.dechet_id', '=', 'C.dechet_id')->on('C.exutoire_id', '=','DE.exutoire_id');})
            ->where('C.statut' , '>=', 100)
            ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
            ->whereBetween('P.date_debut', [$startDate, $endDate])
            ->select(['C.id', 'C.poids', 'C.passage_id', 'P.pointcollecte_id', 'DE.*']);
        $second = DB::table('collectes   AS C')
            ->join('passages        AS P' , 'P.id', '=', 'C.passage_id')
            ->join('enlevements     AS E' , 'E.id', '=', 'C.enlevement_id')
            ->join('dechet_exutoire AS DE', function($join){$join->on('DE.dechet_id', '=', 'C.dechet_id')->on('E.exutoire_id', '=','DE.exutoire_id');})
            ->where('C.statut', '>=', 100)
            ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
            ->whereBetween('P.date_debut', [$startDate, $endDate])
            ->select(['C.id', 'C.poids', 'C.passage_id', 'P.pointcollecte_id', 'DE.*'])
            ->union($first);
        $query = DB::query()->fromSub($second, 'R')
            ->join('dechets        AS D' , 'D.id' , '=', 'R.dechet_id')
            ->join('passages       AS P' , 'P.id' , '=', 'R.passage_id')
            ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
            ->groupBy('D.id')
            ->select([
                'PC.nom AS pointcollecte', 'D.nom', 'D.photo', 'D.ordre_affichage', DB::raw('SUM(R.poids) as masse'), DB::raw('CEIL(SUM(D.equivalence_coefficient * R.poids)) as equivalence')
                , 'D.equivalence_nom', 'D.equivalence_photo', DB::raw('AVG(R.taux_recyclage) as taux'), DB::raw('SUM(R.coeff_env_eau * R.poids) as equi_env_eau')
                , DB::raw('SUM(R.coeff_env_energie * R.poids) as equi_env_energie'), DB::raw('SUM(R.coeff_env_arbre * R.poids) as equi_env_arbre')
                , DB::raw('SUM(R.coeff_env_co2 * R.poids) as equi_env_co2')
            ]);
        return $query->get();
    }
}
