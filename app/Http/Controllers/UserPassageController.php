<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \App\Models\Passage;
use App\Models\Pointcollecte;
use App\Models\Collecte;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Paths;
use Illuminate\Support\Carbon;
use App\Models\Document;
use App\Helpers\Files;
use App\Helpers\HZip;

class UserPassageController extends Controller
{
    public function listPassagesWithOneDocumentByPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        return response()->json(['status' => true, 'message' => ''
                   , 'result' => Passage::listPassagesWithOneDocumentByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
    public function listDocumentsWithDechet(Request &$request, int $passage_id) {
        $session = $request->session()->get('triethic');

        $query = DB::table('passages AS P')
                   ->join('collectes         AS C'  , 'P.id'   , '=', 'C.passage_id')
                   ->join('dechets           AS D'  , 'D.id'   , '=', 'C.dechet_id')
                   ->join('collecte_document AS CD' , 'C.id'   , '=', 'CD.collecte_id')
                   ->join('documents         AS Doc', 'Doc.id' , '=', 'CD.document_id')
                   ->whereIn('P.pointcollecte_id', $session['pointcollectes'])
                   ->where('P.id', '=', $passage_id)
                   ->select(['C.dechet_id', 'C.id AS collecte_id', 'D.nom AS nom_dechet', 'CD.document', 'CD.document_id', 'Doc.nom'])
                   ;
        return response()->json(['status' => true, 'message' => '', 'result' => $query->get()], 200);
    }
    public function downloadDocument(Request &$request, int $passage_id, int $document_id) {
        $session = $request->session()->get('triethic');
        $result = DB::table('collecte_document')
                    ->join('passages'  , 'passages.id'  , '=', 'collecte_document.passage_id')
                    ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                    ->where('collecte_document.passage_id' , '=', $passage_id)
                    ->where('collecte_document.document_id', '=', $document_id)
                    ->whereIn('passages.pointcollecte_id', $session['pointcollectes'])
                    ->select(['passages.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut'])->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; collecte_id='.$passage_id.', document='.$document_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->download(Paths::clientDocuments($result[0]->document)
                                  , Carbon::createFromFormat('Y-m-d H:i:s', $result[0]->date_debut)->format('Y_m_d').'-'.$result[0]->nom.'-'.$result[0]->id.'.pdf');
    }
    public function downloadAttestation(Request &$request, int $passage_id) {
        $session = $request->session()->get('triethic');
        $result = DB::table('passages')
                    ->join('pointcollectes AS P', 'P.id', '=', 'passages.pointcollecte_id')
                    ->join('clients        AS C', 'C.id', '=', 'P.client_id')
                    ->whereIn('passages.pointcollecte_id', $session['pointcollectes'])
                    ->where('passages.id', $passage_id)
                    ->select(['passages.id', 'passages.date_debut', 'C.entreprise_id'])
                    ->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; collecte_id='.$passage_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->download(Paths::clientDocuments($result[0]->entreprise_id).'/'.$passage_id.'-bordereau_passage.pdf'
                                  , Carbon::createFromFormat('Y-m-d H:i:s', $result[0]->date_debut)->format('Y_m_d').'-attestation_passage-'.$result[0]->id.'.pdf');
    }
    public function downloadDocuments(Request &$request) {
        $allowedFields = ['passage_ids'   => 'string', 'typeDocuments'    => 'string'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');

        // On s'attaque aux documents de collecte
        $query = DB::table('collecte_document')
                    ->join('collectes' , 'collectes.id' , '=', 'collecte_document.collecte_id')
                    ->join('passages'  , 'passages.id'  , '=', 'collectes.passage_id')
                    ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                    ->join('dechets'   , 'dechets.id'   , '=', 'collectes.dechet_id')
                    //->whereIn('collecte_document.collecte_id', json_decode($request->get('collecte_ids')))
                    ->whereIn('collectes.passage_id', json_decode($request->get('passage_ids')))
                    ->whereIn('passages.pointcollecte_id', $session['pointcollectes']);

        if ($request->has('typeDocuments') && count(json_decode($request->get('typeDocuments'))) > 0)
            $query = $query->whereIn('documents.type', json_decode($request->get('typeDocuments')));

        $result = $query->select(['collectes.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut', DB::raw('dechets.nom AS dechet')])->get();
        $files = [];
        foreach($result AS &$value) {
            array_push($files, ['file' => Paths::clientDocuments($value->document)
                              , 'name' => Carbon::createFromFormat('Y-m-d H:i:s', $value->date_debut)->format('Y_m_d').'-'.$value->nom.'-'.$value->dechet.'-'.$value->id.'.pdf'
                            ]);
        }

        // On s'attaque aux documents de passage
        $query = DB::table('collecte_document')
                   ->join('passages'  , 'passages.id'  , '=', 'collecte_document.passage_id')
                   ->join('documents' , 'documents.id' , '=', 'collecte_document.document_id')
                   ->whereIn('collecte_document.passage_id' , json_decode($request->get('passage_ids')))
                   ->whereIn('passages.pointcollecte_id', $session['pointcollectes']);

        if ($request->has('typeDocuments') && count(json_decode($request->get('typeDocuments'))) > 0)
           $query = $query->whereIn('documents.type', json_decode($request->get('typeDocuments')));

        $result = $query->select(['passages.id', 'collecte_document.document', 'documents.nom', 'passages.date_debut'])->get();
        foreach($result AS &$value) {
            array_push($files, ['file' => Paths::clientDocuments($value->document)
                              , 'name' => Carbon::createFromFormat('Y-m-d H:i:s', $value->date_debut)->format('Y_m_d').'-'.$value->nom.'-'.$value->id.'.pdf'
                            ]);
        }

        // On génère l'archive finale
        $archive = Document::zip($files, 'document');
        if ($archive === false) {
            \Log::warning('Error while generating the zip archive; UserCollecteController::downloadDocumentssession='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }

        return response()->download($archive, 'documents.zip')->deleteFileAfterSend(true);
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

        return response()->json(['status' => true, 'message' => '', 'result' => Passage::listDateByPointcollecte($pointcollecte_ids, $fillable['start'], $fillable['end'])], 200);
    }
}
