<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Historique;
use App\Helpers\DB AS DBHelper;

class MonitoringController extends Controller
{
    function historyTypes(Request &$request) {
        return response()->json(['status' => true, 'message' => '', 'result' => DB::table('historiquetypes')->where('public', true)->get()], 200);
    }
    function usersActivity(Request &$request) {
        $request->validate(['start' => 'required|date', 'end' => 'required|date']);
        $session = $request->session()->get('triethic');
        $result = Historique::join('users'          , 'users.id'          , '=', 'historiques.user_id')
                            ->join('client_user'    , 'users.id'          , '=', 'client_user.user_id')
                            ->join('clients'        , 'clients.id'        , '=', 'client_user.client_id')
                            ->join('historiquetypes', 'historiquetypes.id', '=', 'historiques.historiquetype_id')
                            ->whereBetween('historiques.created_at', [$request->get('start'), $request->get('end')])
                            ->where('clients.integrateur_id', $session['integrateurs'][0])
                            ->select([DB::raw('DISTINCT historiques.*'), 'users.prenom', 'users.nom', 'users.email'])
                            ->orderBy('historiques.created_at', 'DESC')
                            ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
}
