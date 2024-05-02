<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

class UserEnvironnementController extends Controller
{
    public function globalStats(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
/*
SELECT PC.nom AS pointcollecte, D.nom, D.photo, SUM(R.poids) as masse, CEIL(SUM(D.equivalence_coefficient * R.poids)) as equivalence, D.equivalence_nom, D.equivalence_photo, AVG(R.taux_recyclage) as taux
      , SUM(R.coeff_env_eau * R.poids) as equi_env_eau, SUM(R.coeff_env_energie * R.poids) as equi_env_energie, SUM(R.coeff_env_arbre * R.poids) as equi_env_arbre, SUM(R.coeff_env_co2 * R.poids) as equi_env_co2
FROM (
		SELECT C.id, C.poids, C.passage_id, P.pointcollecte_id, DE.*
		FROM collectes C
		INNER JOIN passages       P  ON P.id  = C.passage_id
		INNER JOIN dechet_exutoire DE ON DE.dechet_id = C.dechet_id AND C.exutoire_id = DE.exutoire_id
		WHERE C.statut >= 50
	union
		SELECT C.id, C.poids, C.passage_id, P.pointcollecte_id, DE.*
		FROM collectes C
        INNER JOIN passages       P  ON P.id  = C.passage_id
		INNER JOIN enlevements     E  ON E.id         = C.enlevement_id
		INNER JOIN dechet_exutoire DE ON DE.dechet_id = C.dechet_id AND E.exutoire_id = DE.exutoire_id
		WHERE C.statut >= 50
) R
INNER JOIN dechets        D  ON D.id  = R.dechet_id
INNER JOIN passages       P  ON P.id  = R.passage_id
INNER JOIN pointcollectes PC ON PC.id = P.pointcollecte_id
GROUP BY R.id, D.id
;
*/
        $first = DB::table('collectes      AS C')
                   ->join('passages        AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('dechet_exutoire AS DE',function($join){$join->on('DE.dechet_id', '=', 'C.dechet_id')->on('C.exutoire_id', '=','DE.exutoire_id');})
                   ->where('C.statut' , '>=', 50)
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$request->get('start'), $request->get('end')])
                   ->select(['C.id', 'C.poids', 'C.passage_id', 'P.pointcollecte_id', 'DE.*']);
        $second = DB::table('collectes   AS C')
                    ->join('passages        AS P' , 'P.id', '=', 'C.passage_id')
                    ->join('enlevements     AS E' , 'E.id', '=', 'C.enlevement_id')
                    ->join('dechet_exutoire AS DE', function($join){$join->on('DE.dechet_id', '=', 'C.dechet_id')->on('E.exutoire_id', '=','DE.exutoire_id');})
                    ->where('C.statut', '>=', 50)
                    ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                    ->whereBetween('P.date_debut', [$request->get('start'), $request->get('end')])
                    ->select(['C.id', 'C.poids', 'C.passage_id', 'P.pointcollecte_id', 'DE.*'])
                    ->union($first);
        $query = DB::query()->fromSub($second, 'R')
                   ->join('dechets        AS D' , 'D.id' , '=', 'R.dechet_id')
                   ->join('passages       AS P' , 'P.id' , '=', 'R.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->groupBy(['PC.id', 'D.id'])
                   ->select([
                    'PC.nom AS pointcollecte', 'D.nom', 'D.photo', 'D.ordre_affichage', DB::raw('SUM(R.poids) as masse'), DB::raw('CEIL(SUM(D.equivalence_coefficient * R.poids)) as equivalence')
                  , 'D.equivalence_nom', 'D.equivalence_photo', DB::raw('AVG(R.taux_recyclage) as taux'), DB::raw('SUM(R.coeff_env_eau * R.poids) as equi_env_eau')
                  , DB::raw('SUM(R.coeff_env_energie * R.poids) as equi_env_energie'), DB::raw('SUM(R.coeff_env_arbre * R.poids) as equi_env_arbre')
                  , DB::raw('SUM(R.coeff_env_co2 * R.poids) as equi_env_co2')
                   ])
                   ;

        return response()->json(['status' => true, 'message' => '', 'result' => $query->get()], 200);
    }
}
