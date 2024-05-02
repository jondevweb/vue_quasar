<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Pointcollecte;
use App\Models\MobilierPointcollecte;
use App\Models\Mobilier;
use App\Helpers\DB AS DBHelper;

class ClientController extends Controller
{
    public function contactList(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('client_user', 'client_user.user_id'  , '=', 'users.id')
                                                                                    ->join('clients'    , 'client_user.client_id', '=', 'clients.id')
                                                                                    ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                    ->where('clients.id', $client_id)
                                                                                    ->select(['users.id',
                                                                                              'users.actif',
                                                                                              'users.civilite',
                                                                                              'users.prenom',
                                                                                              'users.nom',
                                                                                              'users.poste',
                                                                                              'users.telephone',
                                                                                              'users.portable',
                                                                                              'users.email',
                                                                                              'users.email_verified_at',
                                                                                              'users.invitation_envoyee',
                                                                                              'users.created_at',
                                                                                              'users.updated_at'
                                                                                             , DB::raw("clients.contact_juridique = users.id as 'contact_juridique'")
                                                                                             , DB::raw("clients.contact_principal = users.id as 'contact_principal'")
                                                                                            ])->get()], 200);
    }
    public function contactUpdate(Request &$request, int $client_id, int $contact_id) {
        $user = new User;
        $request->validate($user->getFillableValidators(false));
        $session = $request->session()->get('triethic');
        $contact_juridique = $request->has('contact_juridique') ? $request->get('contact_juridique') : null;
        $contact_principal = $request->has('contact_principal') ? $request->get('contact_principal') : null;

        $fillable = $request->only($user->getFillable());
        $fillable = DBHelper::prefixesKeys('users.', $fillable);
        $cpt = User::join('client_user', 'client_user.user_id'  , '=', 'users.id')
            ->join('clients'    , 'client_user.client_id', '=', 'clients.id')
            ->where('clients.integrateur_id', $session['integrateurs'][0])
            ->where('clients.id', $client_id)
            ->where('users.id', $contact_id)
            ->update($fillable);
        if($cpt == 1) {
            $sql = Client::where('id', $client_id);
            $update = [];
            if ($contact_juridique !== null && $contact_juridique == 1) $update['contact_juridique'] = $contact_id;
            if ($contact_principal !== null && $contact_principal == 1) $update['contact_principal'] = $contact_id;
            $cpt = $sql->update($update);

        }
        return response()->json(['status' => true, 'message' => '', 'result' =>''], 200);
    }
    public function contactCreate(Request &$request, int $client_id) {
        // il faudrait normalement vérifier que le collecteur est bien associé
        // au client
        $session = $request->session()->get('triethic');
        $contact = new Contact;

        // creation de l'utilisateur
        $request->validate($contact->getFillableValidators(false));
        $user = $contact->store($request, $client_id, 'client', $request->get('regularUser'));
        DB::table('parametres')->insertOrIgnore([['user_id' => $user->id]]);
        $user->assignRole('client');

        // Les histoires de contacts juridique/principal
        $contact_juridique = $request->has('contact_juridique') ? $request->get('contact_juridique') : null;
        $contact_principal = $request->has('contact_principal') ? $request->get('contact_principal') : null;


        $sql = Client::where('id', $client_id);
        $update = [];
        if ($contact_juridique !== null && $contact_juridique == 1) $update['contact_juridique'] = $user->id;
        if ($contact_principal !== null && $contact_principal == 1) $update['contact_principal'] = $user->id;
        $cpt = $sql->update($update);

        return response()->json(['status' => true, 'message' => '', 'result' =>['id' => $user->id]], 200);
    }
    public function contactView(Request &$request, int $client_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('client_user', 'client_user.user_id'  , '=', 'users.id')
                                                                                    ->join('clients'    , 'client_user.client_id', '=', 'clients.id')
                                                                                    ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                    ->where('clients.id', $client_id)
                                                                                    ->where('users.id', $contact_id)
                                                                                    ->select(['users.id',
                                                                                              'users.actif',
                                                                                              'users.civilite',
                                                                                              'users.prenom',
                                                                                              'users.nom',
                                                                                              'users.poste',
                                                                                              'users.telephone',
                                                                                              'users.portable',
                                                                                              'users.email',
                                                                                              'users.email_verified_at',
                                                                                              'users.invitation_envoyee',
                                                                                              'users.created_at',
                                                                                              'users.updated_at'
                                                                                             , DB::raw("IF(clients.contact_juridique = users.id, 1, 0) as 'contact_juridique'")
                                                                                             , DB::raw("IF(clients.contact_principal = users.id, 1, 0) as 'contact_principal'")
                                                                                            ])->first()], 200);
    }
    public function contactAssociate(Request &$request, int $client_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        //Normalement il faudrait vérifier si le client est associé au collecteur
        $user = User::find($contact_id);
        if ($user != null) {
            $contact = new Contact;
            $contact->associate($user, $client_id, 'client');
            return response()->json(['status' => true, 'message' => '', 'result' => '']);
        }
        return response()->json(['status' => false, 'message' => 'Contact not found', 'result' => '']);
    }
    public function create(Request &$request) {
        $client     = new Client;
        $request->validate($client->getFillableValidators());
        $session = $request->session()->get('triethic');
        $id = $client->store($request, $session['integrateurs'][0]);
        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $id]], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => ['clients' => Client::join('entreprises', 'entreprises.id', '=', 'clients.entreprise_id')->where('clients.integrateur_id', $session['integrateurs'][0])->orderBy('entreprises.raison_sociale')->select('clients.id as id','entreprise_id', 'raison_sociale')->get()]], 200);
    }
/*
    SELECT R.user_id, R.client_id, R.pointcollecte_id
        , CONCAT(U.nom, ' ', U.prenom, ' (', U.email, ' ; ', U.telephone, ' ', U.portable) AS user
        , CONCAT(E.raison_sociale, ' sis au ', E.adresse_administrative)                   AS client
        , CONCAT(PC.nom, ' sis au ', PC.adresse)                                           AS pointcollecte
    FROM      recherches R
    LEFT JOIN users          U  ON R.user_id = U.id
    LEFT JOIN clients        C  ON C.id = R.client_id
    LEFT JOIN entreprises    E  ON E.id = C.entreprise_id
    LEFT JOIN pointcollectes PC ON PC.id = R.pointcollecte_id
    WHERE MATCH(text) AGAINST('bateg' IN BOOLEAN MODE)
    GROUP BY  R.user_id, R.client_id, R.pointcollecte_id
*/
    public function search(Request &$request) {
        $session = $request->session()->get('triethic');
        $request->validate(['pattern' => 'required']);
        $result = DB::table('recherches AS R')
                    ->leftJoin('users          AS U' , 'U.id' , '=', 'R.user_id')
                    ->leftJoin('clients        AS C' , 'C.id' , '=', 'R.client_id')
                    ->leftJoin('entreprises    AS E' , 'E.id' , '=', 'C.entreprise_id')
                    ->leftJoin('pointcollectes AS PC', 'PC.id', '=', 'R.pointcollecte_id')
                    ->whereRaw("MATCH(text) AGAINST(? IN BOOLEAN MODE)", [$request->input('pattern')])
                    ->groupBy(['R.user_id', 'R.client_id', 'R.pointcollecte_id'])
                    ->select([
                        'R.user_id', 'R.client_id', 'R.pointcollecte_id', 'R.text'
                        , DB::raw("CONCAT(U.nom, ' ', U.prenom, ' (', U.email, ' ; ', U.telephone, ' ', U.portable) AS user")
                        , DB::raw("CONCAT(E.raison_sociale, ' sis au ', E.adresse_administrative)                   AS client")
                        , DB::raw("CONCAT(PC.nom, ' sis au ', PC.adresse)                                           AS pointcollecte")
                        , 'PC.adresse'
                        , 'PC.nom'
                    ])
                    ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function searchPc(Request &$request) {
        $session = $request->session()->get('triethic');
        $request->validate(['pattern' => 'required']);
        $result = DB::table('recherches_pc AS R')
                    ->leftJoin('users          AS U' , 'U.id' , '=', 'R.user_id')
                    ->leftJoin('clients        AS C' , 'C.id' , '=', 'R.client_id')
                    ->leftJoin('entreprises    AS E' , 'E.id' , '=', 'C.entreprise_id')
                    ->leftJoin('pointcollectes AS PC', 'PC.id', '=', 'R.pointcollecte_id')
                    ->whereRaw("MATCH(text) AGAINST(? IN BOOLEAN MODE)", [$request->input('pattern')])
                    ->groupBy(['R.user_id', 'R.client_id', 'R.pointcollecte_id'])
                    ->select([
                        'R.user_id', 'R.client_id', 'R.pointcollecte_id', 'R.text'
                        , DB::raw("CONCAT(U.nom, ' ', U.prenom, ' (', U.email, ' ; ', U.telephone, ' ', U.portable) AS user")
                        , DB::raw("CONCAT(E.raison_sociale, ' sis au ', E.adresse_administrative)                   AS client")
                        , DB::raw("CONCAT(PC.nom, ' sis au ', PC.adresse)                                           AS pointcollecte")
                        , 'PC.adresse'
                        , 'PC.nom'
                    ])
                    ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function pointCollecteList(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Pointcollecte::join('clients', 'client_id', '=', 'clients.id')
                                                                                            ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                            ->where('pointcollectes.client_id', $client_id)
                                                                                            ->select(['pointcollectes.id AS id', 'pointcollectes.nom', 'pointcollectes.adresse'])->get()
                                ], 200);
    }
    public function mobilierList(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        $request->validate(['pointcollecte_ids'   => 'array','pointcollecte_ids.*' => 'integer']);
        $query = Pointcollecte::join('mobilier_pointcollecte', 'pointcollectes.id', '=', 'mobilier_pointcollecte.pointcollecte_id')
                              ->join('mobiliers', 'mobiliers.id', '=', 'mobilier_pointcollecte.mobilier_id')
                              ->join('clients', 'client_id', '=', 'clients.id')
                              ->where('clients.integrateur_id', $session['integrateurs'][0])
                              ->where('pointcollectes.client_id', $client_id);

        if ($request->has('pointcollecte_ids')) {
            $pointcollecte_ids = $request->get('pointcollecte_ids');
            if (count($pointcollecte_ids) > 0)
                $query = $query->whereIn('pointcollectes.id', $pointcollecte_ids);
        }

        $query = $query->select(['mobilier_pointcollecte.*', 'pointcollectes.nom as point_collecte'
                                        , 'mobiliers.nom', 'mobiliers.photo', 'mobiliers.type', 'mobiliers.nom'
                                ]);
        return response()->json(['status' => true, 'message' => '', 'result' => $query->get()], 200);
    }
    public function mobilierCreate(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        $mp = new MobilierPointcollecte;
        $request->validate($mp->getFillableValidators());
        $id = $mp->store($request);

        return response()->json(['status' => ($id == -1 ? false : true), 'message' => '', 'result' => ['id' => $id]], 200);
    }
    public function mobilierDelete(Request &$request, int $client_id, int $mobilier_pointcollecte_id) {
        $session = $request->session()->get('triethic');
        //il faudrait normalement vérifier que le client est géré par le collecteur et que le mobilier lui appartient

        DB::unprepared('SET autocommit=0');

        DB::unprepared('LOCK TABLES mobiliers WRITE, mobilier_pointcollecte WRITE');
        $mp = MobilierPointcollecte::find($mobilier_pointcollecte_id);
        if ($mp == null) {
            DB::unprepared('UNLOCK TABLES');
            return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
        }
        if (!$mp->appartient_client)
            DB::table('mobiliers')->where('id', $mp->mobilier_id)->increment('quantite', $mp->quantite);

        $mp->delete();

        DB::unprepared('COMMIT');
        DB::unprepared('UNLOCK TABLES');
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function mobilierUpdate(Request &$request, int $client_id, int $mobilier_pointcollecte_id) {
        $session = $request->session()->get('triethic');
        $mp = new MobilierPointcollecte;
        $request->validate($mp->getFillableValidators(false));
        $count = MobilierPointcollecte::where('id', $mobilier_pointcollecte_id)->update($request->only($mp->getFillable()));

        return response()->json(['status' => true, 'message' => '', 'result' => $count], 200);
    }
    public function pointCollecteView(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Pointcollecte::join('clients', 'client_id', '=', 'clients.id')
                                                                                             ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                             ->where('pointcollectes.id', $pointcollecte_id)
                                                                                            // petit hack pas cher : on « écrase » une colonne avec une représentation prise en charge par eloquent
                                                                                            // on verra combien de temps cela sera supporté
                                                                                            // solution alternative à étudier : https://github.com/grimzy/laravel-mysql-spatial
                                                                                             ->select('pointcollectes.*', DB::raw('AsText(pointcollectes.coordonnees) as coordonnees'))->first()
                                ], 200);
    }
    public function pointCollecteUpdate(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        $pointcollecte = new Pointcollecte;
        $request->validate($pointcollecte->getFillableValidators(false));
        $fillable = $request->only($pointcollecte->getFillable());
        if (isset($fillable['coordonnees']))
            $fillable['coordonnees'] = DB::raw('GeomFromText(\''.$fillable['coordonnees'].'\')');

        $fillable = DBHelper::prefixesKeys('pointcollectes.', $fillable);
        Pointcollecte::join('clients', 'client_id', '=', 'clients.id')
                        ->where('clients.integrateur_id', $session['integrateurs'][0])
                        ->where('pointcollectes.id', $pointcollecte_id)
                        ->update($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' =>''], 200);
    }
    public function pointCollecteCreate(Request &$request, int $client_id) {
        $pointcollecte = new Pointcollecte;
        $request->validate($pointcollecte->getFillableValidators());
        $session = $request->session()->get('triethic');
        $id = $pointcollecte->store($request, $session['integrateurs'][0], $client_id);
        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $id]], 200);
    }
    public function view(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Client::with('entreprise')
                                                                                      ->where('integrateur_id', $session['integrateurs'][0])
                                                                                      ->where('id', $client_id)
                                                                                      ->first()], 200);
    }
    public function update(Request &$request, int $client_id) {
        $client     = new Client;
        $request->validate($client->getFillableValidators(false, 'integrateur_id'));
        $session = $request->session()->get('triethic');
        $client->localUpdate($request, $client_id, $session['integrateurs'][0]);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function caps(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        $sql = <<<EOM
        SELECT C.dechet_id, CC.statut, CC.annee
        FROM caps C
        INNER JOIN cap_client CC ON CC.cap_id = C.id AND CC.client_id = ?
        WHERE C.dechet_id IN (
            SELECT D.id
            FROM `track.icionrecycle.fr`.dechets D
            WHERE INSTR(D.rubrique, '*') > 0 AND D.integrateur_id = ?
        )
        EOM;
        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[
                                                                                /*'client_id'      => */$client_id,
                                                                                /*'integrateur_id' => */$session['integrateurs'][0],
                                                                                ])], 200);
    }
}
