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
use App\Models\Gestionnaire;
use App\Models\Dechet;
use App\Models\Rsd;
use App\Helpers\Files;

class UserClientController extends Controller
{
    public function contactList(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id'.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => '', $session, $client_id], 401);
        }
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
    public function contactView(Request &$request, int $client_id, int $contact_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id'.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => '', $session, $client_id], 401);
        }
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
    public function contactJuridiqueView(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id'.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => '', $session, $client_id], 401);
        }
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('clients', 'users.id', '=', 'clients.contact_juridique')
                                                                                    ->where('clients.id', '=', $client_id)
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
    public function gestionnaireViewNContact(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id'.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $client = Client::where('id', $client_id)->first();
        if ($client->gestionnaire_id == null)
            return response()->json(['status' => true, 'message' => '', 'result' => null], 200);

        return response()->json(['status' => true, 'message' => '', 'result' => ['gestionnaire' => Gestionnaire::with('entreprise')
                                                                                                               ->where('id', $client->gestionnaire_id)
                                                                                                               ->first()
                                                                                , 'contact_gestionnaire' => User::find($client->contact_gestionnaire)
                                                                                ]], 200);
    }
    public function view(Request &$request, int $client_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($client_id, $session['clients'])) {
            \Log::warning('Tried to access to an account that is not his; client_id'.$client_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->json(['status' => true, 'message' => '', 'result' => Client::with('entreprise')
                                                                                      ->where('id', $client_id)
                                                                                      ->first()], 200);
    }
    public function pointcollecteView(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->json(['status' => true, 'message' => '', 'result' => Pointcollecte::where('id', $pointcollecte_id)
                                                                                             ->select('pointcollectes.*', DB::raw('AsText(pointcollectes.coordonnees) as coordonnees'))
                                                                                             ->get()], 200);
    }
    public function pointCollecteDechets(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::join('dechet_pointcollecte', 'dechet_pointcollecte.dechet_id', '=', 'dechets.id')
                                                                                      ->where('dechet_pointcollecte.pointcollecte_id', '=', $pointcollecte_id)
                                                                                      ->select('dechets.*')
                                                                                      ->orderBy('dechets.ordre_affichage')
                                                                                      ->get()], 200);
    }
    function pointcollecteRsdRaw(int $pointcollecte_id, bool $restricted = true) {
        //requête dangereuse car peu efficace ; il faudrait copier le pointcollecte_id directement dans rsds
        $query = Rsd::join('collectes', 'rsds.collecte_id'   , '=', 'collectes.id')
                    ->join('passages', 'collectes.passage_id', '=', 'passages.id')
                    ->where('passages.pointcollecte_id'      , '=', $pointcollecte_id);
        if ($restricted)
            $query = $query->select('rsds.*'); // si besoin pour optimiser
        else
            $query = $query->select('rsds.*');
        return $query->get();
    }
    public function pointcollecteRsd(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        //requête dangereuse car peu efficace ; il faudrait copier le pointcollecte_id directement dans rsd
        return response()->json(['status' => true, 'message' => '', 'result' => $this->pointcollecteRsdRaw($pointcollecte_id, false)], 200);
    }
    public function pointcollecteRsdExport(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        // Vient de https://stackoverflow.com/questions/26146719/use-laravel-to-download-table-as-csv
        $list = array_map(function($value) {
            $retour = (array)$value;
            $retour['recepisse'] = $retour['transporteur_entree_recepisse'];
            $retour['nom_adresse_destination']  = $retour['destination_regroupement_nom'].' | '.$retour['destination_regroupement_adresse'];
            $retour['nom_adresse_transporteur'] = $retour['transporteur_entree_nom']     .' | '.$retour['transporteur_entree_adresse'];
            $retour['code_traitement'] = $retour['destination_regroupement_code_traitement'];
            $retour['traitement_finale'] = $retour['destination_finale_traitement'];

            foreach(['id', 'collecte_id', 'date_dechargement', 'transporteur_entree_nom', 'transporteur_entree_adresse', 'transporteur_entree_immatriculation'
            , 'transporteur_entree_recepisse', 'transporteur_sortie_nom', 'transporteur_sortie_adresse', 'transporteur_sortie_immatriculation', 'transporteur_sortie_recepisse'
            , 'destination_regroupement_nom', 'destination_regroupement_adresse', 'destination_regroupement_code_traitement', 'destination_finale_nom'
            , 'destination_finale_adresse', 'destination_finale_traitement'] as &$val)
                unset($retour[$val]);
            return $retour;
        }, $this->pointcollecteRsdRaw($pointcollecte_id, true)->toArray());

        if (count($list))
            array_unshift($list, array_keys($list[0]));# add headers for each column in the CSV download

        $xlsx = Files::rows2Xlsx($list);
        if ($xlsx == false) return 'Internal Error !';

        return response()->download($xlsx, 'export_brut.xlsx')->deleteFileAfterSend(true);
    }
}
