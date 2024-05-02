<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Pointcollecte;
use App\Models\Biopointcollecte;
use App\Models\Integrateur;
use App\Helpers\Files;
use App\Helpers\Paths;
use App\Helpers\Pdf;
use Illuminate\Filesystem\Filesystem;

class IntegrateurController extends Controller
{
    public function compte(Request &$request) {
        $id = $request->session()->get('triethic')['user']['id'];
        $user   = User::where('id', $id)->first();
        $result = [];
        $result['integrateurs'] = array_reduce(DB::select(
                              'SELECT *
                              FROM integrateur_user
                              WHERE user_id = ?', [$id]), function ($carry, $item) {array_push($carry, $item->integrateur_id); return $carry;}, []);
        if (count($result) != 1)
            \Log::warning('Having not exactly one integrateur associated with this account; this is not supported!!; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
        if (count($result) == 0)
            return response()->json(['status' => false, 'message' => '', 'result' => []], 200);
        $session = $request->session()->get('triethic');

        $result['clients']        =  Client::whereIn('integrateur_id', $result['integrateurs'])->select('id')
                                           ->get()
                                           ->reduce(function($acc, $value){array_push($acc, $value->id);return $acc;}, []);
        $result['pointcollectes'] = array_reduce(Pointcollecte::listByClients($result['clients'])->toArray(), function ($carry, $item) {array_push($carry, $item['id']); return $carry;}, []);
        $result['config']         = Integrateur::find($result['integrateurs'][0])->toArray();

        $session = array_merge($session, $result);
        $request->session()->put('triethic', $session);
        return response()->json(['status' => true, 'message' => '', 'result' => [
            'email'    => $user->email,
            'nom'      => $user->nom,
            'prenom'   => $user->prenom,
            'civilite' => $user->civilite,
            'integrateurs' => $result['integrateurs']
        ]], 200);
    }
    public function upload(Request &$request) {
        $session = $request->session()->get('triethic');
        $id      = $session['user']['id'];
        $path = $request->file('file')->store($id, 'users_tmp');

        return response()->json(['status' => true, 'message' => '', 'result' => substr($path, strpos($path, '/')+1)], 200);
    }
}
