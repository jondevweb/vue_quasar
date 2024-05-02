<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Collecte;
use App\Models\Dechet;
use App\Models\Mobilier;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class UserMobilierController extends Controller
{
    private function listByPointcollecteResult(Request &$request) {
        $allowedFields = ['pointcollectes'   => 'array', 'pointcollectes.*' => 'integer', 'type' => 'max:255'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $fillable = $request->only(array_keys($allowedFields));
        $pointcollecte_ids = [];
        if (isset($fillable['pointcollectes']) && count($fillable['pointcollectes']['*']) > 0) {
            $pointcollecte_ids = array_values(\array_intersect($session['pointcollectes'], $fillable['pointcollectes']['*']));
        }else
            $pointcollecte_ids = $session['pointcollectes'];

        return Mobilier::listByPointcollecte($request->input('type', 'tri'), $pointcollecte_ids);
    }
    public function listByPointcollecte(Request &$request) {
        return response()->json(['status' => true, 'message' => '', 'result' => UserMobilierController::listByPointcollecteResult($request)], 200);
    }
    public function export(Request &$request) {
        // Vient de https://stackoverflow.com/questions/26146719/use-laravel-to-download-table-as-csv

        $list = array_map(function($value) {
            $toto = (array)$value;
            unset($toto['photo']);
            $toto['appartient_client'] = $toto['appartient_client'] == 1 ? 'Oui' : 'Non';
            return $toto;
        }, UserMobilierController::listByPointcollecteResult($request)->toArray());

        # add headers for each column in the CSV download
        if (count($list) > 0)
            array_unshift($list, array_keys($list[0]));

        $xlsx = Files::rows2Xlsx($list);
        if ($xlsx == false) return 'Internal Error !';

        return response()->download($xlsx, 'export_brut.xlsx')->deleteFileAfterSend(true);
    }
}
