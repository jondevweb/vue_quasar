<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Collecte;
use App\Models\Dechet;
use App\Models\Document;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class UserCollecteController extends Controller
{
    public function listByPointcollecteNPassage(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listByPointcollecteNPassage($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
    public function listWithWeightNWasteByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];


        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listWithWeightNWasteByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
    public function listWeightedWithWasteByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array'
                        , 'pointcollectes.*' => 'integer'
                        , 'dechets'          => 'array', 'dechets.*' => 'integer'
                        , 'typeDocuments'    => 'array', 'typeDocuments.*' => 'integer'
                    ];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        $dechet_ids = [];
        if (isset($fillable['dechets']) && count($fillable['dechets']['*']) > 0) $dechet_ids = $fillable['dechets']['*'];

        if (isset($fillable['typeDocuments']) && count($fillable['typeDocuments']['*']) > 0)
            return response()->json(['status' => true, 'message' => ''
                   , 'result' => Collecte::listWeightedWithWasteByPointcollecteNDocument($pointcollecte_ids, $fillable['start'], $fillable['end'], $dechet_ids, $fillable['typeDocuments']['*'])], 200);
        else
            return response()->json(['status' => true, 'message' => ''
                   , 'result' => Collecte::listWeightedWithWasteByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'], $dechet_ids)], 200);
    }
    public function listAssociatedDocs(Request &$request, int $collecte_id) {
        $session = $request->session()->get('triethic');
        $result = Collecte::join('passages', 'passages.id', '=', 'collectes.passage_id')
                          ->whereIn('passages.pointcollecte_id', $session['pointcollectes'])
                          ->where('collectes.id', '=', $collecte_id)
                          ->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; collecte_id='.$collecte_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listAssociatedDocs($collecte_id)], 200);
    }
    public function listWithWeightByPointcollecteNMonth(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        $dechets = Dechet::whereIn('dechets.integrateur_id', function ($query) use  (&$session){
            $query->select('C.integrateur_id')
                  ->from('integrateurs AS I')
                  ->join('clients      AS C', 'I.id', '=', 'C.integrateur_id')
                  ->whereRaw('C.id IN('.implode(',', $session['clients']).')');
        })->get();

        return response()->json(['status' => true, 'message' => '', 'result' => [
                                                                        'collectes' => Collecte::listWithWeightByPointcollecteNMonth($pointcollecte_ids, $fillable['start'], $fillable['end']),
                                                                        'dechets'   => $dechets]], 200);
    }
    public function listDateByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listDateByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
    public function listDocumentsByPointcollecte(Request &$request, int $pointcollecte_id) {
        $allowedFields = ['date'   => 'date_format:Y-m-d'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        if (!\in_array($pointcollecte_id,  $session['pointcollectes'])) {
            \Log::warning('Someone attempted to retrieve data that is not his; pointcollecte_id='.$pointcollecte_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $result = DB::table('passages AS P')
                    ->leftJoin('collectes         AS C'  , 'P.id'  , '=', 'C.passage_id')
                    ->leftJoin('dechets           AS D'  , 'D.id'  , '=', 'C.dechet_id')
                    ->leftJoin('collecte_document AS CD' , 'C.id'  , '=', 'CD.collecte_id')
                    ->leftJoin('documents         AS Doc', 'Doc.id', '=', 'CD.document_id')
                    ->where('P.pointcollecte_id'         , '=', $pointcollecte_id)
                    ->where(DB::raw('DATE(P.date_debut)'), '=', $request->get('date'))
                    ->select(['P.date_debut', 'P.statut', 'P.motif_passage_vide', DB::raw('C.id AS collecte_id')
                             , DB::raw('P.id AS passage_id'), DB::raw('D.nom AS dechet'), DB::raw('CD.id AS collecte_document_id')
                             , 'Doc.nom', 'CD.document_id', 'CD.document', 'C.trackdechets_id'])
                    ->orderBy('C.dechet_id', 'desc')
                    ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function downloadDocument(Request &$request, int $collecte_id, int $document_id) {
        $session = $request->session()->get('triethic');
        $result = DB::table('collecte_document')
                    ->join('collectes' , 'collectes.id' , '=', 'collecte_document.collecte_id')
                    ->join('passages'  , 'passages.id'  , '=', 'collectes.passage_id')
                    ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                    ->join('dechets'   , 'dechets.id'   , '=', 'collectes.dechet_id')
                    ->where('collecte_document.collecte_id', '=', $collecte_id)
                    ->where('collecte_document.document_id', '=', $document_id)
                    ->whereIn('passages.pointcollecte_id', $session['pointcollectes'])
                    ->select(['collectes.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut', DB::raw('dechets.nom AS dechet')])->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; collecte_id='.$collecte_id.', document='.$document_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->download(Paths::clientDocuments($result[0]->document)
                                  , Carbon::createFromFormat('Y-m-d H:i:s', $result[0]->date_debut)->format('Y_m_d').'-'.$result[0]->nom.'-'.$result[0]->dechet.'-'.$result[0]->id.'.pdf');
    }
    public function downloadDocuments(Request &$request) {
        $allowedFields = ['collecte_ids'   => 'string', 'typeDocuments'    => 'string'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $query = DB::table('collecte_document')
                    ->join('collectes' , 'collectes.id' , '=', 'collecte_document.collecte_id')
                    ->join('passages'  , 'passages.id'  , '=', 'collectes.passage_id')
                    ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                    ->join('dechets'   , 'dechets.id'   , '=', 'collectes.dechet_id')
                    ->whereIn('collecte_document.collecte_id', json_decode($request->get('collecte_ids')))
                    ->whereIn('passages.pointcollecte_id', $session['pointcollectes']);

        if ($request->has('typeDocuments') && count(json_decode($request->get('typeDocuments'))) > 0)
            $query = $query->whereIn('documents.type', json_decode($request->get('typeDocuments')));

        $result = $query->select(['collectes.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut', DB::raw('dechets.nom AS dechet')])->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $files = [];
        foreach($result AS &$value) {
            array_push($files, ['file' => Paths::clientDocuments($value->document)
                              , 'name' => Carbon::createFromFormat('Y-m-d H:i:s', $value->date_debut)->format('Y_m_d').'-'.$value->nom.'-'.$value->dechet.'-'.$value->id.'.pdf'
                            ]);
        }
        $archive = Document::zip($files, 'document');
        if ($archive === false) {
            \Log::warning('Error while generating the zip archive; UserCollecteController::downloadDocumentssession='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }

        return response()->download($archive, 'documents.zip')->deleteFileAfterSend(true);
    }
    public function export(Request &$request) {
        $allowedFields = ['pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        // Vient de https://stackoverflow.com/questions/26146719/use-laravel-to-download-table-as-csv
        $list = array_map(function($value) {
            unset($value->id);
            unset($value->dechet_id);
            unset($value->pointcollecte_id);
            return (array)$value;
        }, Collecte::listWithWeightNWasteByPointcollecte($pointcollecte_ids, '1970-01-01 00:00:00', Carbon::now()->toDateTimeString())->toArray());

        if (count($list))
            array_unshift($list, array_keys($list[0]));# add headers for each column in the CSV download

        $xlsx = Files::rows2Xlsx($list);
        if ($xlsx == false) return 'Internal Error !';

        return response()->download($xlsx, 'export_brut.xlsx')->deleteFileAfterSend(true);
    }
    public function futureExport(Request &$request) {
        $allowedFields = ['pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        // Vient de https://stackoverflow.com/questions/26146719/use-laravel-to-download-table-as-csv
        $list = array_map(function($value) {
            return (array)$value;
        }, Collecte::listFutureByPointcollecte($pointcollecte_ids)->toArray());

        if (count($list))
            array_unshift($list, array_keys($list[0]));# add headers for each column in the CSV download

        $xlsx = Files::rows2Xlsx($list);
        if ($xlsx == false) return 'Internal Error !';

        return response()->download($xlsx, 'export_brut.xlsx')->deleteFileAfterSend(true);
    }
    public function list(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $session = $request->session()->get('triethic');
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        return response()->json(['status' => true, 'message' => '', 'result' => Collecte::listWithWeightByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
}
