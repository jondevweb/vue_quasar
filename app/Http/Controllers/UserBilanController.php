<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Document;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class UserBilanController extends Controller
{
    //
    public function downloadDocument(Request &$request, $bilan_id) {
        $session = $request->session()->get('triethic');
        $result = DB::table('bilans')
                    ->whereIn('bilans.pointcollecte_id', $session['pointcollectes'])
                    ->where('bilans.id', '=', $bilan_id)
                    ->get();

        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        return response()->download(Paths::clientDocuments($result[0]->document), $result[0]->annee.'-bilan.pdf');
    }
    public function list(Request &$request) {
        $allowedFields = ['pointcollecte_ids'   => 'array', 'pointcollecte_ids.*' => 'integer'];
        $request->validate($allowedFields);
        $fillable = $request->only($allowedFields);
        $session = $request->session()->get('triethic');
        $pointcollecte_ids = [];
        if ($request->has('pointcollecte_ids')) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $request->get('pointcollecte_ids')));
        }else
            $pointcollecte_ids = $session['pointcollectes'];
        $result = DB::table('bilans')
                    ->whereIn('bilans.pointcollecte_id', $pointcollecte_ids)
                    ->get();

        return response()->json(['status' => false, 'message' => '', 'result' => $result], 200);
    }
    public function downloadDocuments(Request &$request) {
        $allowedFields = ['bilan_ids'   => 'string'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $result = DB::table('bilans')
                    ->whereIn('bilans.pointcollecte_id', $session['pointcollectes'])
                    ->whereIn('bilans.id', json_decode($request->get('bilan_ids')))

                    ->get();

        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        $files = [];
        foreach($result AS &$value) {
            array_push($files, ['file' => Paths::clientDocuments($value->document)
                              , 'name' => $value->annee.'-bilan.pdf'
                            ]);
        }
        $archive = Document::zip($files, 'document');
        if ($archive === false) {
            \Log::warning('Error while generating the zip archive; UserAttestationController::downloadDocumentssession='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }

        return response()->download($archive, 'documents.zip')->deleteFileAfterSend(true);
    }
}
