<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Gestionnaire;
use App\Models\Entreprise;

class GestionnaireController extends Controller

{
/*
select entreprises.raison_sociale, concat(users.prenom, ' ', users.nom)
from clients
inner join entreprises on entreprises.id = clients.entreprise_id
inner join users       on users.id       = clients.contact_gestionnaire
*/
    public function clientList(Request &$request, int $gest_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Client::join('entreprises', 'entreprises.id'  , '=', 'clients.entreprise_id')
                                                                                      ->join('users'      , 'users.id'        , '=', 'clients.contact_gestionnaire')
                                                                                      ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                      ->where('clients.gestionnaire_id', $gest_id)
                                                                                      ->select(['clients.id', 'entreprises.raison_sociale', DB::raw("concat(users.prenom, ' ', users.nom) as contact")])->get()], 200);
    }
    public function contactList(Request &$request, int $gest_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('gestionnaire_user', 'gestionnaire_user.user_id'  , '=', 'users.id')
                                                                                    ->whereIn('gestionnaire_user.gestionnaire_id',  function ($query) use(&$session) {
                                                                                        $query->selectRaw('i.gestionnaire_id')
                                                                                            ->from('gestionnaire_integrateur as i')
                                                                                            ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                    })
                                                                                    ->where('gestionnaire_user.gestionnaire_id', $gest_id)
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
    public function contactUpdate(Request &$request, int $gest_id, int $contact_id) {
        $user = new User;
        $request->validate($user->getFillableValidators(false));
        $session = $request->session()->get('triethic');
        $contact_juridique = $request->has('contact_juridique') ? $request->get('contact_juridique') : null;
        $contact_principal = $request->has('contact_principal') ? $request->get('contact_principal') : null;

        User::join('gestionnaire_user', 'gestionnaire_user.user_id'  , '=', 'users.id')
            ->whereIn('gestionnaire_user.gestionnaire_id',  function ($query) use(&$session) {
                $query->selectRaw('i.gestionnaire_id')
                    ->from('gestionnaire_integrateur as i')
                    ->where('i.integrateur_id', $session['integrateurs'][0]);
            })
            ->where('gestionnaire_user.gestionnaire_id', $gest_id)
            ->where('users.id', $contact_id)
            ->update($request->only($user->getFillable()));
        return response()->json(['status' => true, 'message' => '', 'result' =>''], 200);
    }
    public function contactCreate(Request &$request, int $gest_id) {
        // il faudrait normalement vérifier que le collecteur est bien associé
        // au gestionnaire
        $session = $request->session()->get('triethic');
        $contact = new Contact;

        // creation de l'utilisateur
        $request->validate($contact->getFillableValidators(false));
        $user->id = $contact->store($request, $gest_id, 'gestionnaire', $request->get('regularUser'));
        return response()->json(['status' => true, 'message' => '', 'result' =>['id' => $user->id]], 200);
    }
    public function contactView(Request &$request, int $gest_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('gestionnaire_user', 'gestionnaire_user.user_id'  , '=', 'users.id')
                                                                                    ->whereIn('gestionnaire_user.gestionnaire_id',  function ($query) use(&$session) {
                                                                                        $query->selectRaw('i.gestionnaire_id')
                                                                                            ->from('gestionnaire_integrateur as i')
                                                                                            ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                    })
                                                                                    ->where('gestionnaire_user.gestionnaire_id', $gest_id)
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
    public function contactAssociate(Request &$request, int $gest_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        //Normalement il faudrait vérifier si le gestionnaire est associé au collecteur
        $user = User::find($contact_id);
        if ($user != null) {
            $contact = new Contact;
            $contact->associate($user, $gest_id, 'gestionnaire');
            return response()->json(['status' => true, 'message' => '', 'result' => '']);
        }
        return response()->json(['status' => false, 'message' => 'Contact not found', 'result' => '']);
    }
    public function create(Request &$request) {
        $gestionnaire     = new Gestionnaire;
        $request->validate($gestionnaire->getFillableValidators());
        $session = $request->session()->get('triethic');
        $id = $gestionnaire->store($request, $session['integrateurs'][0]);

        DB::table('gestionnaire_integrateur')->insert(['integrateur_id' => $session['integrateurs'][0], 'gestionnaire_id' => $id]);
        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $id]], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => ['gestionnaires' => Gestionnaire::join('entreprises', 'entreprises.id', '=', 'gestionnaires.entreprise_id')
                                                                                                                ->whereIn('gestionnaires.id', function ($query) use(&$session) {
                                                                                                                    $query->selectRaw('i.gestionnaire_id')
                                                                                                                        ->from('gestionnaire_integrateur as i')
                                                                                                                        ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                                                })
                                                                                                                ->orderBy('entreprises.raison_sociale')
                                                                                                                ->select('gestionnaires.id as id','entreprise_id', 'raison_sociale')
                                                                                                                ->get()]], 200);
    }
    public function view(Request &$request, int $gest_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Gestionnaire::with('entreprise')
                                                                                            ->whereIn('gestionnaires.id',  function ($query) use(&$session)  {
                                                                                                $query->selectRaw('i.gestionnaire_id')
                                                                                                    ->from('gestionnaire_integrateur as i')
                                                                                                    ->where('i.integrateur_id', $session['integrateurs'][0]);
                                                                                            })
                                                                                            ->where('id', $gest_id)
                                                                                            ->first()], 200);
    }
    public function update(Request &$request, int $gest_id) {
        $entreprise     = new Entreprise;
        $request->validate($entreprise->getFillableValidators(false));
        $session = $request->session()->get('triethic');

        Entreprise::join('gestionnaires', 'entreprises.id', '=', 'gestionnaires.entreprise_id')
                  ->join('gestionnaire_integrateur', 'gestionnaire_integrateur.gestionnaire_id', '=', 'gestionnaires.id')
                  ->where('gestionnaire_integrateur.gestionnaire_id', '=', $gest_id)
                  ->where('gestionnaire_integrateur.integrateur_id', $session['integrateurs'][0])
                  ->update($request->only($entreprise->getFillable()));
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
}
