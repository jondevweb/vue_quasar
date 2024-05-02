<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dechet;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Exutoire;
use App\Models\Entreprise;

class ExutoireController extends Controller

{
/*
select entreprises.raison_sociale, concat(users.prenom, ' ', users.nom)
from clients
inner join entreprises on entreprises.id = clients.entreprise_id
inner join users       on users.id       = clients.contact_exutoire
*/
    public function dechetList(Request &$request, int $exutoire_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::join('dechet_exutoire', 'dechet_exutoire.dechet_id'  , '=', 'dechets.id')
                                                                                    ->where('dechet_exutoire.exutoire_id', $exutoire_id)
                                                                                    ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                    ->where('dechet_exutoire.active', 1)
                                                                                    ->select(['dechets.*'
                                                                                            , 'dechet_exutoire.coeff_env_eau'
                                                                                            , 'dechet_exutoire.coeff_env_energie'
                                                                                            , 'dechet_exutoire.coeff_env_arbre'
                                                                                            , 'dechet_exutoire.coeff_env_co2'
                                                                                            , 'dechet_exutoire.taux_recyclage'
                                                                                            ])->get()], 200);
    }
    public function dechetAssociate(Request &$request, int $exutoire_id, int $dechet_id) {
        $session = $request->session()->get('triethic');
        //Normalement il faudrait vérifier si le exutoire et le déchet sont associés au collecteur
        DB::table('dechet_exutoire')->upsert(['dechet_id' => $dechet_id, 'exutoire_id' => $exutoire_id, 'active' => 1], ['dechet_id', 'exutoire_id']);
        return response()->json(['status' => true, 'message' => '', 'result' => '']);
    }
    public function dechetDissociate(Request &$request, int $exutoire_id, int $dechet_id) {
        //TODO ajouter une vérification du fait que le compte a le droit de faire cela
        $session = $request->session()->get('triethic');
        //Normalement il faudrait vérifier si le exutoire et le déchet sont associés au collecteur
        DB::table('dechet_exutoire')->where('dechet_id', $dechet_id)->where('exutoire_id', $exutoire_id)->update(['active' => 0]);
        return response()->json(['status' => true, 'message' => '', 'result' => '']);
    }
    public function dechetUpdate(Request &$request, int $exutoire_id, int $dechet_id) {
        $validators = ['coeff_env_eau'     => 'numeric',
                       'coeff_env_energie' => 'numeric',
                       'coeff_env_arbre'   => 'numeric',
                       'coeff_env_co2'     => 'numeric',
                       'taux_recyclage'    => 'numeric|min:0|max:1',
                    ];
        $request->validate($validators);
        $session = $request->session()->get('triethic');

        DB::table('dechet_exutoire') // il faudrait normalement vérifier que le dechet_exutoire est bien associé à l'intégrateur
            ->where('dechet_exutoire.exutoire_id', $exutoire_id)
            ->where('dechet_exutoire.dechet_id',   $dechet_id)
            ->update($request->only(array_keys($validators)));
        return response()->json(['status' => true, 'message' => '', 'result' =>''], 200);
    }
    public function dechetView(Request &$request, int $exutoire_id, int $dechet_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::join('dechet_exutoire', 'dechet_exutoire.dechet_id'  , '=', 'dechets.id')
                                                                                    ->where('dechet_exutoire.exutoire_id', $exutoire_id)
                                                                                    ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                    ->where('dechets.id', $dechet_id)
                                                                                    ->select(['dechets.*'
                                                                                            , 'dechet_exutoire.coeff_env_eau'
                                                                                            , 'dechet_exutoire.coeff_env_energie'
                                                                                            , 'dechet_exutoire.coeff_env_arbre'
                                                                                            , 'dechet_exutoire.coeff_env_co2'
                                                                                            , 'dechet_exutoire.taux_recyclage'
                                                                                            ])->get()], 200);
    }
    public function contactList(Request &$request, int $exutoire_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('exutoire_user', 'exutoire_user.user_id'  , '=', 'users.id')
                                                                                    ->whereIn('exutoire_user.exutoire_id',  function ($query) use(&$session) {
                                                                                        $query->selectRaw('i.exutoire_id')
                                                                                            ->from('exutoire_integrateur as i')
                                                                                            ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                    })
                                                                                    ->where('exutoire_user.exutoire_id', $exutoire_id)
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
                                                                                              'users.updated_at'])->get()], 200);
    }
    public function contactUpdate(Request &$request, int $exutoire_id, int $contact_id) {
        $user = new User;
        $request->validate($user->getFillableValidators(false));
        $session = $request->session()->get('triethic');
        $contact_juridique = $request->has('contact_juridique') ? $request->get('contact_juridique') : null;
        $contact_principal = $request->has('contact_principal') ? $request->get('contact_principal') : null;

        User::join('exutoire_user', 'exutoire_user.user_id'  , '=', 'users.id')
            ->whereIn('exutoire_user.exutoire_id',  function ($query) use(&$session) {
                $query->selectRaw('i.exutoire_id')
                    ->from('exutoire_integrateur as i')
                    ->where('i.integrateur_id', $session['integrateurs'][0]);
            })
            ->where('exutoire_user.exutoire_id', $exutoire_id)
            ->where('users.id', $contact_id)
            ->update($request->only($user->getFillable()));
        return response()->json(['status' => true, 'message' => '', 'result' =>''], 200);
    }
    public function contactCreate(Request &$request, int $exutoire_id) {
        // il faudrait normalement vérifier que le collecteur est bien associé
        // au exutoire
        $session = $request->session()->get('triethic');
        $contact = new Contact;

        // creation de l'utilisateur
        $request->validate($contact->getFillableValidators(false));
        $user->id = $contact->store($request, $exutoire_id, 'exutoire', $request->get('regularUser'));
        return response()->json(['status' => true, 'message' => '', 'result' =>['id' => $user->id]], 200);
    }
    public function contactView(Request &$request, int $exutoire_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('exutoire_user', 'exutoire_user.user_id'  , '=', 'users.id')
                                                                                    ->whereIn('exutoire_user.exutoire_id',  function ($query) use(&$session) {
                                                                                        $query->selectRaw('i.exutoire_id')
                                                                                            ->from('exutoire_integrateur as i')
                                                                                            ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                    })
                                                                                    ->where('exutoire_user.exutoire_id', $exutoire_id)
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
                                                                                              'users.updated_at'])->first()], 200);
    }
    public function contactAssociate(Request &$request, int $exutoire_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        //Normalement il faudrait vérifier si le exutoire est associé au collecteur
        $user = User::find($contact_id);
        if ($user != null) {
            $contact = new Contact;
            $contact->associate($user, $exutoire_id, 'exutoire');
            return response()->json(['status' => true, 'message' => '', 'result' => '']);
        }
        return response()->json(['status' => false, 'message' => 'Contact not found', 'result' => '']);
    }
    public function create(Request &$request) {
        $exutoire     = new Exutoire;
        $request->validate($exutoire->getFillableValidators());
        $session = $request->session()->get('triethic');
        $id = $exutoire->store($request, $session['integrateurs'][0]);

        DB::table('exutoire_integrateur')->insert(['integrateur_id' => $session['integrateurs'][0], 'exutoire_id' => $id]);
        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $id]], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => ['exutoires' => Exutoire::join('entreprises', 'entreprises.id', '=', 'exutoires.entreprise_id')
                                                                                                                ->whereIn('exutoires.id', function ($query) use(&$session) {
                                                                                                                    $query->selectRaw('i.exutoire_id')
                                                                                                                        ->from('exutoire_integrateur as i')
                                                                                                                        ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                                                })
                                                                                                                ->orderBy('entreprises.raison_sociale')
                                                                                                                ->select('exutoires.id as id','entreprise_id', 'raison_sociale')
                                                                                                                ->get()]], 200);
    }
    public function view(Request &$request, int $exutoire_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Exutoire::with('entreprise')
                                                                                            ->whereIn('exutoires.id',  function ($query) use(&$session)  {
                                                                                                $query->selectRaw('i.exutoire_id')
                                                                                                    ->from('exutoire_integrateur as i')
                                                                                                    ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                            })
                                                                                            ->where('id', $exutoire_id)
                                                                                            ->first()], 200);
    }
    public function update(Request &$request, int $exutoire_id) {
        return DB::transaction(function ()   use (&$request, &$exutoire_id) {
            $exutoire   = new Exutoire;
            $entreprise = new Entreprise;
            $request->validate($exutoire->getFillableValidators(false));
            $session = $request->session()->get('triethic');
            $fillable = $request->only($entreprise->getFillable());
            if(count($fillable) > 0)
                Entreprise::join('exutoires', 'entreprises.id', '=', 'exutoires.entreprise_id')
                        ->join('exutoire_integrateur', 'exutoire_integrateur.exutoire_id', '=', 'exutoires.id')
                        ->where('exutoire_integrateur.exutoire_id', '=', $exutoire_id)
                        ->where('exutoire_integrateur.integrateur_id', $session['integrateurs'][0])
                        ->update($fillable);

            $fillable = $request->only($exutoire->getFillable());
            unset($fillable['entreprise_id']);
            if(count($fillable) > 0)
                Exutoire::join('exutoire_integrateur', 'exutoire_integrateur.exutoire_id', '=', 'exutoires.id')
                        ->where('exutoire_integrateur.exutoire_id', '=', $exutoire_id)
                        ->where('exutoire_integrateur.integrateur_id', $session['integrateurs'][0])
                        ->update($fillable);
            return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
        });

    }
}
