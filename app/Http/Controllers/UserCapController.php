<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\Paths;
use App\Models\User;
use App\Models\Client;
use App\Models\Pointcollecte;
use App\Models\Cap;
use App\Models\Dechet;
use App\Models\Document;
use App\Mail\IntegrateurCAPUpdated;
use App\Helpers\Pdf;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Mail;



class UserCapController extends Controller
{
    public function listCAPByPointcollecte(Request &$request) {
        /*$request->validate(['pointcollecte_ids' => 'required|array', 'pointcollecte_ids.*'=> 'numeric']);
        $session = $request->session()->get('triethic');

        $pointcollecte_ids = [];
        $pointcollecte_ids = \array_intersect($session['pointcollectes'], $request->get('pointcollecte_ids'));*/
        $request->validate(['pointcollecte_id' => 'required|numeric']);
        $pointcollecte_id = $request->get('pointcollecte_id');
        $session = $request->session()->get('triethic');
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id='.$pointcollecte_id.'; session:'.\json_encode($session['pointcollectes']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $query = DB::table('cap_client')
                   ->join('caps'          , 'cap_client.cap_id'       , '=', 'caps.id')
                   ->join('clients'       , 'cap_client.client_id'    , '=', 'clients.id')
                   ->join('dechets'       , 'caps.dechet_id'          , '=', 'dechets.id')
                   ->join('pointcollectes', 'pointcollectes.client_id', '=', 'clients.id')
                   ->where('pointcollectes.id', '=', $pointcollecte_id)
                   ->select(['cap_client.*', 'dechets.nom AS dechet', 'caps.dechet_id']);

        return response()->json(['status' => true, 'message' => '', 'result' => $query->get()], 200);
    }
    public function missingNExpected(Request &$request, int $client_id, int $annee, int $annee_reference) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id='.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $sql=<<<END
        SELECT *
        FROM dechets
        WHERE id IN (
            SELECT DISTINCT C.dechet_id
            FROM       collectes C
            INNER JOIN passages       P  ON P.id  = C.passage_id AND YEAR(P.date_debut) = $annee_reference
            INNER JOIN pointcollectes PC ON PC.id = P.pointcollecte_id AND PC.client_id = $client_id
            WHERE C.dechet_id IN (
                SELECT C.dechet_id
                FROM caps C
                INNER JOIN dechets   D  ON D.id = C.dechet_id AND INSTR(D.rubrique, '*') > 0
                LEFT JOIN cap_client CC ON CC.cap_id = C.id AND CC.client_id = $client_id AND CC.annee = $annee
                WHERE CC.client_id IS NULL
            )
        )
END;
        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql)], 200);
    }
    public function generateCap(Request &$request, int $client_id, int $annee, int $dechet_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id='.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $dechet = Dechet::find($dechet_id);
        $cap    = Cap::where('dechet_id', '=', $dechet_id)->first();
        $client = DB::table('clients')
                    ->join('entreprises', 'entreprises.id', '=', 'clients.entreprise_id')
                    ->where('clients.id', $client_id)->select('entreprises.*')->first();
        $integrateur = DB::table('clients')
                         ->join('integrateurs', 'integrateurs.id', '=', 'clients.integrateur_id')
                         ->join('entreprises' , 'entreprises.id' , '=', 'integrateurs.entreprise_id')
                         ->where('clients.id', $client_id)->select('entreprises.*')->first();

        if ($cap == null || $client == null || $dechet == null) {
            \Log::warning('invalid id: ($cap, $client, $dechet)=('
                                                               .($cap === null ? 'null' : 'ok').','.($client === null ? 'null' : 'ok').','.($dechet === null ? 'null' : 'ok')
                                                               .'), (client_id, dechet_id)=('
                                                               .$client_id.', '.$dechet_id
                                                               .'); session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $validite_debut = '01/01/'.$annee;
        $validite_fin   = '31/12/'.$annee;
        $numero = ($annee%100).'-'.str_pad($client_id, 6, "0", STR_PAD_LEFT) . '-' . str_pad($dechet_id, 5, "0", STR_PAD_LEFT);
        $pdf = Pdf::fromPath(Cap::capModelsLocation().$cap->document);
        $generationInfo = [
              'numero' => $numero
            , 'raison_sociale_collecteur' => $integrateur->raison_sociale
            , 'adresse_collecteur' => $integrateur->adresse_administrative
            , 'designation' => $dechet->nom
            , 'ced' => $dechet->rubrique
            , 'validite_debut' => $validite_debut
            , 'validite_fin' => $validite_fin
            , 'raison_sociale' => $client->raison_sociale
            , 'adresse' => $client->adresse_administrative
        ];
        if (($filename = Paths::newClientDocument($client->id)) === false) {
            \Log::warning('Impossible to create new document for client for the CAP PDF! id='.$client->id.'/'.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        if ($pdf->fill($generationInfo, $filename) === false) {
            \Log::warning('Impossible to fill the CAP PDF! path='.$filepath.'/'.$filename.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        DB::table('cap_client')->insert(['cap_id' => $cap->id, 'client_id' => $client_id
                                       , 'annee' => $annee, 'numero' => $numero
                                       , 'document' => $client->id.'/'.\basename($filename)
        ]);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function downloadDocument(Request &$request, int $cap_id, int $client_id, int $annee) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id='.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $result = DB::table('cap_client')
                    ->join('caps'   , 'caps.id'   , '=', 'cap_client.cap_id')
                    ->join('dechets', 'dechets.id', '=', 'caps.dechet_id')
                    ->where('cap_client.client_id', '=', $client_id)
                    ->where('cap_client.cap_id'          , '=', $cap_id)
                    ->where('cap_client.annee'           , '=', $annee)
                    ->select(['cap_client.*', 'dechets.nom AS dechet'])
                    ->get();

        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        return response()->download(Paths::clientDocuments($result[0]->document), $result[0]->annee.'-cap-'.$result[0]->dechet.'.pdf');
    }
    public function downloadDocuments(Request &$request) {
        $session = $request->session()->get('triethic');
        $allowedFields = ['pairs'   => 'required|string', 'client_id' => 'required|numeric'];
        $request->validate($allowedFields);

        $client_id = $request->get('client_id');
        $pairs = json_decode($request->get('pairs'));

        if (!\is_array($pairs) || !\in_array($client_id, $session['clients'])) {
            \Log::warning('Someone attempted something fishy; session='.json_encode(['session' => $session, 'client_id' => $client_id, 'pairs' => $request->get('pairs')]).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $where = [];
        foreach($pairs AS &$value) {
            if (!\is_array($value) || count($value) != 2) {
                \Log::warning('Someone attempted something fishy; session='.json_encode(['session' => $session, 'client_id' => $client_id, 'pairs' => $request->get('pairs')]).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
            }
            array_push($where, '('.$client_id.', '.intval($value[0]).', '.intval($value[1]).')');
        }
        $result = DB::table('cap_client AS CC')
                    ->join('caps'   , 'caps.id'   , '=', 'CC.cap_id')
                    ->join('dechets', 'dechets.id', '=', 'caps.dechet_id')
                    ->whereRaw('(CC.client_id, CC.cap_id, CC.annee) IN ('.\implode(',', $where).')')
                    ->select(['CC.*', 'dechets.nom AS dechet'])
                    ->get();
        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        $files = [];
        foreach($result AS &$value) {
            array_push($files, ['file' => Paths::clientDocuments($value->document)
                              , 'name' => $value->annee.'-cap-'.$value->dechet.'.pdf'
                            ]);
        }
        $archive = Document::zip($files, 'document');
        if ($archive === false) {
            \Log::warning('Error while generating the zip archive; UserCapController::downloadDocuments session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }

        return response()->download($archive, 'documents.zip')->deleteFileAfterSend(true);
    }
    public function update(Request &$request, $cap_id, $client_id, $annee) {
        $session  = $request->session()->get('triethic');
        $user_id        = $session['user']['id'];

        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id='.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        if (!$request->has('file') || \is_null($request->file('file'))) {
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 415);
        }

        $result = DB::table('cap_client')
                    ->join('clients'     , 'clients.id'     , '=', 'cap_client.client_id')
                    ->join('entreprises' , 'entreprises.id' , '=', 'clients.entreprise_id')
                    ->join('integrateurs', 'integrateurs.id', '=', 'clients.integrateur_id')
                    ->where('cap_client.client_id', '=', $client_id)
                    ->where('cap_client.cap_id'   , '=', $cap_id)
                    ->where('cap_client.annee'    , '=', $annee)
                    ->select(['cap_client.*', 'integrateurs.email', 'entreprises.raison_sociale'])
                    ->first();
        if ($result == null) {
            \Log::warning('Tried to access data that does nos exist; '.json_encode(['pointcollecte_id' => $client_id, 'cap_id' => $cap_id, 'annee' => $annee,'session' => \json_encode($session)]).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        $path = \Storage::disk('users_tmp')->path('/').$request->file('file')->store($user_id, 'users_tmp');
        if (@mime_content_type($path) != 'application/pdf') {
            \Log::warning('Wrong type of document given: expected PDF got something else; '.json_encode(['client_id' => $client_id, 'cap_id' => $cap_id, 'annee' => $annee,'session' => \json_encode($session)]).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 200);
        }

        $fs = new Filesystem;
        if (!$fs->move($path, Paths::clientDocuments($result->document))) {
            \Log::warning('Error while trying to copy a file; '.json_encode(['client_id' => $client_id, 'cap_id' => $cap_id, 'annee' => $annee,'session' => \json_encode($session), 'src' => $path, 'dst' => Paths::clientDocuments($result->document)]).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }
        Mail::to($result->email)->send(new IntegrateurCAPUpdated($result->raison_sociale));
        DB::table('cap_client')
            ->where('cap_client.client_id', '=', $client_id)
            ->where('cap_client.cap_id'   , '=', $cap_id)
            ->where('cap_client.annee'    , '=', $annee)
            ->update(['updated_at' => DB::raw('NOW()'), 'statut' => 10]);

        return response()->json(['status' => true, 'message' => '', 'result' => [Paths::clientDocuments($result->document), $path]], 200);
    }
}
