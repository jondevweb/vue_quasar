<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Activite;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class ActiviteController extends Controller
{
    //
    public function downloadDocuments(Request &$request) {
        $allowedFields = ['pointcollecte_ids'   => 'required|string', 'dechet_ids'   => 'string', 'start_date' => 'required|date', 'end_date' => 'required|date'];
        $request->validate($allowedFields);
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $request->get('start_date'), 'UTC');
        $endDate   = Carbon::createFromFormat('Y-m-d H:i:s', $request->get('end_date')  , 'UTC');
        $diff = $endDate->diffInMonths($startDate);
        if ($diff < 0) return response()->json(['status' => false, 'message' => 'start must be before end', 'result' => ''], 400);

        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];


        $file = Activite::generateOneRapport($integrateur_id
                                            , $request->has('pointcollecte_ids') ? json_decode($request->get('pointcollecte_ids')) : null
                                            , $request->has('dechet_ids')        ? json_decode($request->get('dechet_ids')       ) : null
                                            , Carbon::createFromFormat('Y-m-d H:i:s', $request->get('start_date'), 'UTC')
                                            , Carbon::createFromFormat('Y-m-d H:i:s', $request->get('end_date')  , 'UTC')
        );

        return response()->file($file, ['filename'=>'rapport_activite.pdf'])->deleteFileAfterSend(true);
    }
}
