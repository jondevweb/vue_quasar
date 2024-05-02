<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Transporteur;

class TransporteurController extends Controller
{
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');

        return response()->json(['status' => true, 'message' => '', 'result' => Transporteur::join('integrateur_transporteur', 'integrateur_transporteur.transporteur_id', '=', 'transporteurs.id')
                                                                                            ->join('entreprises', 'entreprises.id', '=', 'transporteurs.entreprise_id')
                                                                                            ->where('integrateur_transporteur.integrateur_id', '=', $session['integrateurs'][0])
                                                                                            ->select('transporteurs.id as id','transporteurs.entreprise_id', 'raison_sociale')
                                                                                            ->get()], 200);
    }
    public function update(Request &$request, int $collecte_id) {
        $collecte = new Collecte;
        $request->validate($collecte->getFillableValidators(false));
        $session = $request->session()->get('triethic');

        Collecte::join('passages', 'passages.id', '=', 'collectes.passage_id')
                ->where('passages.integrateur_id', $session['integrateurs'][0])
                ->where('passages.id', $collecte_id)
                ->update($request->only($passage->getFillable()));
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
}
