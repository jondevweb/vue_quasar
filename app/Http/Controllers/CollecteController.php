<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Pointcollecte;
use App\Models\Collecte;
use App\Models\Document;
use Illuminate\Support\Carbon;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class CollecteController extends Controller
{
    public function listWithWeightNWasteByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'
                        , 'dechets' => 'array'      , 'dechets.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        $dechet_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }
        if (isset($fillable['dechets']) && count($fillable['dechets']['*']) > 0) {
            $dechet_ids = $fillable['dechets']['*'];
        }

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listWithWeightNWasteByPointcollecteI($integrateur_id, $pointcollecte_ids, $dechet_ids, $fillable['start'], $fillable['end'])], 200);
    }
    public function listDocumentsByCollecte(Request &$request, int $collecte_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $result = DB::table('collectes AS C')
                    ->join('passages          AS P'  , 'P.id'  , '=', 'C.passage_id')
                    ->join('pointcollectes    AS PC' , 'PC.id' , '=', 'P.pointcollecte_id')
                    ->join('clients           AS Cl' , 'Cl.id' , '=', 'PC.client_id')
                    ->join('collecte_document AS CD' , 'C.id'  , '=', 'CD.collecte_id')
                    ->join('documents         AS Doc', 'Doc.id', '=', 'CD.document_id')
                    ->where('Cl.integrateur_id', '=', $integrateur_id)
                    ->where('C.id'             , '=', $collecte_id)
                    ->select([DB::raw('C.id AS collecte_id'), 'Doc.nom', 'CD.document_id', 'CD.document', 'C.trackdechets_id'])
                    ->orderBy('C.dechet_id', 'desc')
                    ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function downloadDocument(Request &$request, int $collecte_id, int $document_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $result = DB::table('collecte_document')
                    ->join('collectes' , 'collectes.id' , '=', 'collecte_document.collecte_id')
                    ->join('passages'  , 'passages.id'  , '=', 'collectes.passage_id')
                    ->join('pointcollectes    AS PC' , 'PC.id' , '=', 'passages.pointcollecte_id')
                    ->join('clients           AS Cl' , 'Cl.id' , '=', 'PC.client_id')
                    ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                    ->join('dechets'   , 'dechets.id'   , '=', 'collectes.dechet_id')
                    ->where('Cl.integrateur_id'            , '=', $integrateur_id)
                    ->where('collecte_document.collecte_id', '=', $collecte_id)
                    ->where('collecte_document.document_id', '=', $document_id)
                    ->select(['collectes.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut', DB::raw('dechets.nom AS dechet')])->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; collecte_id='.$collecte_id.', document='.$document_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->download(Paths::clientDocuments($result[0]->document)
                                  , Carbon::createFromFormat('Y-m-d H:i:s', $result[0]->date_debut)->format('Y_m_d').'-'.$result[0]->nom.'-'.$result[0]->dechet.'-'.$result[0]->id.'.pdf');
    }
    public function listWithWeightNDangerousWasteByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];


        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listWithWeightNDangerousWasteByPointcollecte($integrateur_id, $fillable['start'], $fillable['end'])], 200);
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
    public function listWithAllByPointcollecteAndDateRange(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $fillable = $request->only(array_keys($allowedFields));

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listWithAllByPointcollecteAndDateRange($integrateur_id, $fillable['start'], $fillable['end'])], 200);
    }
    public function regenerate(Request &$request, int $collecte_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $collecte = new Collecte;
        return response()->json(['status' => $collecte->regenerate($collecte_id, $integrateur_id), 'message' => '', 'result' => ''], 200);
    }
}
