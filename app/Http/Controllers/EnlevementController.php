<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \App\Models\Passage;
use App\Models\Pointcollecte;
use App\Models\Collecte;
use App\Models\Enlevement;
use Illuminate\Support\Facades\Storage;

class EnlevementController extends Controller {
    public function list(Request &$request) {
        $allowedFields = ['skip' => 'numeric', 'take' => 'numeric'];
        $request->validate($allowedFields);
        $skip = $request->input('skip', 0);
        $take = $request->input('take', 9999999);
        $result = Enlevement::select([DB::raw('SQL_CALC_FOUND_ROWS *')])->orderBy('id', 'desc')->skip($skip)->take($take)->get();
        $count  = DB::select(<<<END
          SELECT FOUND_ROWS() AS total
        END)[0]->total; //code highlight failed in there, so the weird use of HERE DOC syntax
        return response()->json(['status' => true, 'message' => '', 'result' => ['enlevements' => $result, 'count' => $count]], 200);
    }
    public function listCollectesExpected(Request &$request, int $dechet_id) {
        $allowedFields = ['end' => 'required|date'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::join('passages       AS P' , 'P.id' , '=', 'collectes.passage_id')
                                                                                        ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                                                                                        ->join('clients        AS C' , 'C.id' , '=', 'PC.client_id')
                                                                                        ->whereNull('collectes.exutoire_id')
                                                                                        ->where('C.integrateur_id'   , $integrateur_id)
                                                                                        ->where('P.date_debut'       , '<=', $request->get('end'))
                                                                                        ->where('P.date_debut'       , '<=', $request->get('end'))
                                                                                        ->where('collectes.statut'   , 50)
                                                                                        ->where('collectes.dechet_id', $dechet_id)
                                                                                        ->select(['collectes.id', 'P.date_debut', 'P.pointcollecte_id', 'collectes.poids', 'collectes.trackdechets_id'])
                                                                                        ->get()], 200);
    }
    public function create(Request &$request, int $dechet_id) {
        $allowedFields = ['end' => 'required|date', 'exutoire_id' => 'required|integer', 'transporteur_id' => 'required|integer', 'immatriculation' => 'required|max:20'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $fillable = $request->only(array_keys($allowedFields));

        if (Enlevement::removal($dechet_id, $fillable, $integrateur_id) == false)
            return response()->json(['status' => false, 'message' => '', 'result' => []], 401);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public static function createWithoutRequestWT(int $dechet_id, int $exutoire_id, int $transporteur_id, string $end, int $integrateur_id, string $immatriculation) {
        $fillable = [
            'end'             => $end,
            'exutoire_id'     => $exutoire_id,
            'transporteur_id' => $transporteur_id,
            'immatriculation' => $immatriculation
        ];
        return Enlevement::removalWT($dechet_id, $fillable, $integrateur_id);
    }
}
