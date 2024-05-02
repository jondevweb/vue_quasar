<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Collecte;
use App\Models\Dechet;
use App\Models\Document;
use App\Models\Pointcollecte;
use App\Models\Client;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
require_once 'TrackdechetsCommon.php';

class UserTrackdechetsController extends Controller
{
    public function checkStatusAssociatedPointcollectes(Request &$request) {
        $session = $request->session()->get('triethic');
        $pointcollectes = Pointcollecte::listWithSocieties($session['pointcollectes']);
        $tmp = [];

        foreach($pointcollectes AS &$value) {
            $tmp = TrackdechetsCommon::getStatus([$value['siret']]);
            if (count($tmp) > 0)
                $value['statut'] = $tmp[0];
            else
                $value['statut'] = [];

        }
        return response()->json(['status' => true, 'message' => '', 'result' => $pointcollectes], 200);
    }
    public function downloadBsd(Request &$request, string $trackdechets_id) {
        $session = $request->session()->get('triethic');
        $rows = DB::table('collectes      AS C' )
                  ->join('passages        AS P' , 'P.id'  , '=', 'C.passage_id')
                  ->join('pointcollectes  AS Pc', 'Pc.id' , '=', 'P.pointcollecte_id')
                  ->whereIn('P.pointcollecte_id', $session['pointcollectes'])
                  ->where('C.trackdechets_id'   , $trackdechets_id)
                  ->select(['C.id'])
                  ->get();
        if (count($rows) == 0) {
            \Log::warning('downloadBsd failed because of a used asked for a retrieval that does not exist or is not his; trackdechets_id='.$trackdechets_id.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => []], 401);
        }
        $downloadLink = TrackdechetsCommon::getBSDPDF($trackdechets_id);
        if (!$downloadLink)
            return response()->json(['status' => false, 'message' => '', 'result' => []], 500);

        return redirect()->away($downloadLink);
    }
    public function checkStatus(Request &$request) {
        $allowedFields = ["sirets"    => "required|array|min:1", "sirets.*"  => "required|string",];
        $request->validate($allowedFields);
        $sirets = $request->input('sirets');

        return response()->json(['status' => true, 'message' => '', 'result' => TrackdechetsCommon::getStatus($sirets)], 200);
    }
    public function updateSocietyCode(Request &$request, string $siret, string $code) {
        $allowedFields = ["code"    => "required|size:4|regex:/^[0-9]{4}$/", "siret"  => "required|string"];
        Validator::make(['code' => $code, 'siret' => $siret], $allowedFields)->passes();
        $session = $request->session()->get('triethic');

        Client::join('entreprises AS E', 'E.id', '=', 'clients.entreprise_id')
               ->where('E.siret', $siret)
               ->whereRaw('clients.id IN('.implode(',', $session['clients']).')')
               ->update(['code_trackdechet' => $code]);

        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
}
