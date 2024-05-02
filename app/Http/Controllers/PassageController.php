<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \App\Models\Passage;
use App\Models\Pointcollecte;
use App\Models\Collecte;
use App\Models\Document;
use App\Helpers\Pdf;
use App\Helpers\Paths;
use App\Helpers\Files;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PassageController extends Controller
{
    public function listWithCollecteNPointcollecte(Request &$request) {
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        return response()->json(['status' => true, 'message' => '', 'result' => Passage::with(['collecte', 'pointcollecte:id,nom,adresse'])->whereBetween('date_debut', [$fillable['start'], $fillable['end']])
                                                                                                         ->whereIn('pointcollecte_id', $pointcollecte_ids)->get()], 200);
    }
    public function update(Request &$request, int $passage_id) {
        $passage = new Passage;
        $request->validate($passage->getFillableValidators(false));
        $session = $request->session()->get('triethic');
        $passage = Passage::join('pointcollectes', 'pointcollectes.id', '=', 'passages.pointcollecte_id')
                          ->join('clients'       , 'clients.id'       , '=', 'pointcollectes.client_id')
                          ->where('passages.id','=', $passage_id)->where('clients.integrateur_id', '=', $session['integrateurs'][0])
                          ->select('passages.*')
                          ->first();
        if ($passage == null) {
            \Log::warning('Asked for either a non-existant passage or unallowed one! passage_id='.$passage_id.', session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $fillable = $request->only($passage->getFillable());
        $passage->updateModel($passage, $fillable, $session['integrateurs'][0], $session['config']['bordereau_passage']);

        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function createCollectes(Request &$request, int $passage_id) {
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];
        $allowedFields = ['collectes' => 'array'];
        $request->validate($allowedFields);
        $collectes = $request->get('collectes');

        if ($this->createCollectesWithoutRequest($passage_id, $integrateur_id, $collectes) == false)
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 400);

        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public static function createCollectesWithoutRequest(int $passage_id, int $integrateur_id, array &$collectes) {
        $passage = Passage::join('pointcollectes AS P', 'P.id', '=', 'passages.pointcollecte_id')
                          ->join('clients AS C', 'C.id', '=', 'P.client_id')
                          ->where('passages.id', $passage_id)->where('C.integrateur_id', $integrateur_id)
                          ->first();
        if ($passage == null) {
            \Log::warning('Tried to access to a passage (for creations) that either does not exist or not allowed; passage_id='.$passage_id.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        foreach($collectes AS &$value) {
            $value['passage_id'] = $passage_id;
        }
        return Collecte::massStore($collectes);
    }
    public function deleteCollecte(Request &$request, int $passage_id, int $collecte_id) {
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];
        $passage = new Passage;
        $session = $request->session()->get('triethic');
        $passage = Passage::join('pointcollectes AS P', 'P.id', '=', 'passages.pointcollecte_id')
                          ->join('clients AS C', 'C.id', '=', 'P.client_id')
                          ->where('passages.id', $passage_id)->where('C.integrateur_id', $integrateur_id)
                          ->first();
        if ($passage == null) {
            \Log::warning('Tried to access to a passage (for a delete) that either does not exist or not allowed; passage_id'.$passage_id.'; session:'.\json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 400);
        }

        $collecte = new Collecte;
        $ids = [$collecte_id];
        $collecte->deleteCollectes($ids, $passage_id, $passage_id);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function deleteCollectes(Request &$request, int $passage_id) {
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];
        $passage = new Passage;
        $allowedFields = ['collectes' => 'array'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $passage = Passage::join('pointcollectes AS P', 'P.id', '=', 'passages.pointcollecte_id')
                          ->join('clients AS C', 'C.id', '=', 'P.client_id')
                          ->where('passages.id', $passage_id)->where('C.integrateur_id', $integrateur_id)
                          ->first();
        if ($passage == null) {
            \Log::warning('Tried to access to a passage  (for deletes) that either does not exist or not allowed; passage_id'.$passage_id.'; session:'.\json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 400);
        }
        $collecte = new Collecte;
        $collectes = $request->get('collectes');
        $collecte->deleteCollectes($collectes, $passage_id);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function create(Request &$request) {
        $passage = new Passage;
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $allowedFields = ['event_id' => 'required|max:100', 'pointcollecte_id' => 'required|int'
                        , 'transporteur_id' => 'required|int', 'vehicule_id' => 'required|int'
                        , 'date_debut' => 'required|date_format:Y-m-d H:i:s', 'date_fin' => 'required|date_format:Y-m-d H:i:s'];
        $request->validate($allowedFields);
        $event_id         = $request->get('event_id');
        $pointcollecte_id = $request->get('pointcollecte_id');
        $transporteur_id  = $request->get('transporteur_id');
        $vehicule_id      = $request->get('vehicule_id');
        $date_debut       = $request->get('date_debut');
        $date_fin         = $request->get('date_fin');

        $passage_id = Passage::insertWithCheck($event_id, $transporteur_id, $vehicule_id, $date_debut, $date_fin, $pointcollecte_id, $integrateur_id, $session['config']['bordereau_passage']);

        if ($passage_id === false)
            return response()->json(['status' => false, 'message' => '', 'result' => ['id' => $passage_id]], 400);

        return response()->json(['status' => true, 'message' => '', 'result' => ['lastId' => $passage_id]], 200);
    }
    static public function listWithAllByEventIdAndDateRange(Request &$request) {
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];
        $allowedFields = ['start' => 'required|date', 'end' => 'required|date'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));
        return response()->json(['status' => true, 'message' => '', 'result' => Passage::listWithAllByEventIdAndDateRange($integrateur_id, $fillable['start'], $fillable['end'])], 200);
    }
    static public function listLastPassageDay(Request &$request) {
        $session = $request->session()->get('triethic');
        $allowedFields = ['limit' => 'required|int', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer', 'end' => 'required|date'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        $result = Passage::whereIn('pointcollecte_id', $pointcollecte_ids)
                         ->where('passages.date_debut', '<=', $fillable['end'])
                         ->groupBy(DB::raw('DATE(passages.date_debut)'))
                         ->orderBy('passages.date_debut', 'DESC')
                         ->limit($fillable['limit'])
                         ->select(DB::raw('DATE(passages.date_debut) AS date'))
                         ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    static public function listFuturPassageDay(Request &$request) {
        $session = $request->session()->get('triethic');
        $allowedFields = ['limit' => 'required|int', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        return response()->json(['status' => true, 'message' => '', 'result' => DB::table('gcalendar')
                                                                                  ->whereIn('pointcollecte_id', $pointcollecte_ids)
                                                                                  ->limit($request->get('limit'))
                                                                                  ->orderBy('date', 'ASC')
                                                                                  ->get()], 200);
    }
    static public function listLastDocumentDay(Request &$request) {
        $session = $request->session()->get('triethic');
        $allowedFields = ['limit' => 'required|int', 'pointcollectes'   => 'array', 'pointcollectes.*' => 'integer', 'end' => 'required|date'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        $result = Passage::leftJoin('collectes', 'passages.id', '=', 'collectes.passage_id')
                         ->whereIn('pointcollecte_id', $pointcollecte_ids)
                         ->where('passages.date_debut', '<=', $fillable['end'])
                         ->where(DB::raw('(passages.statut = 1 OR (passages.statut = 2 AND collectes.id IS NOT NULL)) '))
                         ->groupBy(DB::raw('DATE(passages.date_debut)'))
                         ->orderBy('passages.date_debut', 'DESC')
                         ->limit($fillable['limit'])
                         ->select(DB::raw('DATE(passages.date_debut) AS date'))
                         ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function listByEventIdWithCollecte(Request &$request, string $event_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];
        return response()->json(['status' => true, 'message' => '', 'result' => Passage::listByEventIdWithCollecte($integrateur_id, $event_id)], 200);
    }
    /*public function create(Request &$request, bool $useGeneric = false) {
        $passage = new Passage;
        $integrateur_id = $request->session()->get('triethic')['integrateurs'][0];

        $request->validate($cap->getFillableValidators());
        $fillable = $request->only($cap->getFillable());
        $dechet_id = $fillable['dechet_id'];
        unset($fillable['document']);
        unset($fillable['file']);
        $fs->makeDirectory(Storage::disk('capmodel')->path($integrateur_id), intval('0755', 8), true, true);
        if ($useGeneric) {
            $filename = date('YmdHis').'-'.uniqid().'.pdf';
            $integrateur_id = 1;

            $fs->copy(Storage::disk('generic_docs')->path('certificat_acceptation_préalable_generique.pdf'), Storage::disk('capmodel')->path($integrateur_id).'/'.$filename);
            $pdf = Pdf::fromPath(Storage::disk('capmodel')->path($integrateur_id).'/'.$filename);
            $fillable['document'] = $integrateur_id.'/'.$filename;
        } else {
            $pdf = Pdf::fromUpload($request, 'capmodel', $integrateur_id);
            if ($pdf === false ) return response()->json(['status' => false, 'message' => '', 'result' => ''], 200);
            $fillable['document'] = \str_replace(Storage::disk('capmodel')->path(''), '', $pdf->path());
        }

        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $cap->store($fillable, $dechet_id), 'fields' => array_keys($pdf->fields())]], 200);
    }

    public function view(Request &$request, int $passage_id, string $userType = 'integrateur') {
        $session = $request->session()->get('triethic');
        $passage = Passage::find($passage_id);
        return response()->json(['status' => true, 'message' => '', 'result' => $passage], 200);
    }

    public function delete(Request &$request, int $document_id) {
        $fs       = new Filesystem;
        $session  = $request->session()->get('triethic');
        $document = Passage::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                            ->where('dechets.integrateur_id', $session['integrateurs'][0])
                            ->where('documents.id', $document_id)
                            ->select('documents.*')
                            ->first();

        $fs->delete(\Storage::disk('dechetmodel')->path($document->document));
        $document->delete();
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                                                                                        ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                        ->select('documents.*')
                                                                                        ->get()], 200);
    }*/
}
