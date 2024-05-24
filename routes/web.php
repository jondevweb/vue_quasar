<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use App\Models\Integrateur;
use App\Models\Entreprise;
use App\Models\User;
use App\Models\Contact;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GestionnaireController;
use App\Http\Controllers\ExutoireController;
use App\Http\Controllers\IntegrateurController;
use App\Http\Controllers\MobilierController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\DechetController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CapController;
use App\Http\Controllers\CollecteController;
use App\Http\Controllers\PassageController;
use App\Http\Controllers\PointcollecteController;
use App\Http\Controllers\UserCompteController;
use App\Http\Controllers\UserCollecteController;
use App\Http\Controllers\UserAttestationController;
use App\Http\Controllers\UserBilanController;
use App\Http\Controllers\UserDechetController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserMobilierController;
use App\Http\Controllers\UserEnvironnementController;
use App\Http\Controllers\UserClientController;
use App\Http\Controllers\UserCapController;
use App\Http\Controllers\TransporteurController;
use App\Http\Controllers\WebClientController;
use App\Http\Controllers\UserPassageController;
use App\Http\Controllers\UserTrackdechetsController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AttestationController;


Route::get('/', function () {
    return view('welcome');
});
Route::prefix('/api/v1.0')->middleware(['throttle:meapi'])->group(function () {
    Route::prefix('/recup')->middleware(['throttle:mestrong'])->group(function () {
        Route::post('resetpassword'   , [AccountController::class, 'resetpassword']);
        Route::post('/'               , [AccountController::class, 'recup']);
    });
    Route::prefix('account')->group(function () {
        Route::post('login'   , [AccountController::class, 'login']  )->middleware(['throttle:mestrong']);
        Route::post('migrate' , [AccountController::class, 'migrate'])->middleware(['throttle:mestrong']);
        Route::post('logout'  , [AccountController::class, 'logout'] )->middleware('auth');
        Route::post('ping'    , [AccountController::class, 'ping']   )->middleware('auth');
    });
    Route::prefix('/client')->middleware(['roleAfterSession:client'])->group(function () {
        Route::prefix('compte')->group(function () {
            Route::post('/'                    , [UserCompteController::class, 'compte']);
            Route::post('parametres/update'    , [UserCompteController::class, 'parametresUpdate']);
            Route::post('update'               , [UserCompteController::class, 'update']);
            Route::post('password'             , [UserCompteController::class, 'password']);
            Route::post('contact'              , [UserCompteController::class, 'contact']);
        });
        Route::prefix('collecte')->group(function () {
            Route::post('{pointcollecte_id}/documents'             , [UserCollecteController::class, 'listDocumentsByPointcollecte']);
            Route::post('par_pointcollecte_et_passage'             , [UserCollecteController::class, 'listByPointcollecteNPassage']);
            Route::post('par_pointcollecte_avec_poids_et_dechet'   , [UserCollecteController::class, 'listWithWeightNWasteByPointcollecte']);
            Route::post('a_partir_pesee_par_pointcollecte_avec_dechet'   , [UserCollecteController::class, 'listWeightedWithWasteByPointcollecte']);
            Route::post('{collecte_id}/document'                   , [UserCollecteController::class, 'listAssociatedDocs']);
            Route::post('par_pointcollecte_et_mois_avec_poids'     , [UserCollecteController::class, 'listWithWeightByPointcollecteNMonth']);
            Route::post('date_par_pointcollecte'                   , [UserCollecteController::class, 'listDateByPointcollecte']);
            Route::get('{collecte_id}/document/{document_id}'      , [UserCollecteController::class, 'downloadDocument']);
            Route::get('documents'                                 , [UserCollecteController::class, 'downloadDocuments']);
            Route::get('export'                                    , [UserCollecteController::class, 'export']);
            Route::get('futur/export'                              , [UserCollecteController::class, 'futureExport']);
        });
        Route::prefix('passage')->group(function () {
            Route::post('avec_un_document_par_pointcollecte'          , [UserPassageController::class, 'listPassagesWithOneDocumentByPointcollecte']);
            Route::post('{passage_id}/collectes/documents_avec_dechet', [UserPassageController::class, 'listDocumentsWithDechet']);
            Route::get('{passage_id}/document/{document_id}'          , [UserPassageController::class, 'downloadDocument']);
            Route::get('{passage_id}/attestation'                     , [UserPassageController::class, 'downloadAttestation']);
            Route::get('documents'                                    , [UserPassageController::class, 'downloadDocuments']);
            Route::post('date_par_pointcollecte'                      , [UserPassageController::class, 'listDateByPointcollecte']);
        });
        Route::prefix('attestation')->group(function () {
            Route::post('/'               , [UserAttestationController::class, 'list']);
            Route::get('documents'        , [UserAttestationController::class, 'downloadDocuments']);
            Route::get('{attestation_id}' , [UserAttestationController::class, 'downloadDocument']);
        });
        Route::prefix('pointcollecte')->group(function () {
            Route::post('/'                            , [UserCompteController::class, 'listPointcollecte']); // Pour être bien marquer qu'il faut être cohérent avec ce qui est en session
            Route::post('passage/liste_derniers_jours' , [PassageController::class   , 'listLastPassageDay']);
            Route::post('passage/liste_futurs_jours'   , [PassageController::class   , 'listFuturPassageDay']);
            Route::post('document/liste_derniers_jours', [PassageController::class   , 'listLastDocumentDay']);
            Route::post('{pointcollecte_id}/view'      , [UserClientController::class, 'pointcollecteView']);
            Route::post('{pointcollecte_id}/rsd'       , [UserClientController::class, 'pointcollecteRsd']);
            Route::get('{pointcollecte_id}/rsd/export' , [UserClientController::class, 'pointcollecteRsdExport']);
        });
        Route::prefix('dechet')->group(function () {
            Route::post('/'               , [UserDechetController::class, 'list']);
        });
        Route::prefix('document')->group(function () {
            Route::post('/'               , [UserDocumentController::class, 'list']);
        });
        Route::prefix('environnement')->group(function () {
            Route::post('/'                     , [UserEnvironnementController::class, 'globalStats']);
        });
        Route::prefix('client')->group(function () {
            Route::post('pointcollecte/{pointcollecte_id}/dechet/list' , [UserClientController::class, 'pointCollecteDechets']);
            Route::post('{client_id}/gestionnaire_et_contact'          , [UserClientController::class, 'gestionnaireViewNContact']);
            Route::post('{client_id}/contact/list'                     , [UserClientController::class, 'contactList']);
            Route::post('{client_id}/contact/juridique'                , [UserClientController::class, 'contactJuridiqueView']);
            Route::post('{client_id}/contact/{contact_id}'             , [UserClientController::class, 'contactView']);
            Route::post('{id}'                                         , [UserClientController::class, 'view']);
        });
    });
    Route::prefix('/integrateur')->middleware(['roleAfterSession:collecteur'])->group(function () {
        Route::prefix('compte')->group(function () {
            Route::post('/'                  , [IntegrateurController::class, 'compte']);
        });
        Route::prefix('client')->group(function () {
            Route::post('list'                                      , [ClientController::class, 'list']);
            Route::post('pointcollecte/{id}'                        , [ClientController::class, 'pointCollecteView']);
            Route::post('{id}/pointcollecte/list'                   , [ClientController::class, 'pointCollecteList']);
            Route::post('{id}'                                      , [ClientController::class, 'view']);
        });
        Route::prefix('pointcollecte')->group(function () {
            Route::post('/'                                         , [PointcollecteController::class, 'list']);
            Route::post('attestations'                              , [PointcollecteController::class, 'attestations']);
            Route::post('{pointcollecte_id}/attestations'           , [PointcollecteController::class, 'pointcollecteAttestations']);
        });
        Route::prefix('attestation')->group(function () {
            Route::post('{annee}/status'                            , [AttestationController::class, 'status']);
            Route::post('{annee}/generate'                          , [AttestationController::class, 'generateAttestations']);
            Route::post('{pointcollecte_id}/{annee}/generate'       , [AttestationController::class, 'generateAttestation']);
            Route::get('{pointcollecte_id}/{annee}/download'        , [AttestationController::class, 'download']);
        });
    });
});
Route::prefix('/clients')->group(function () {
    Route::get('recup/{token}', [WebClientController::class, 'recup']);
    Route::get('migration'    , [WebClientController::class, 'migration']);
    Route::get('/'            , [WebClientController::class, 'root']);
    Route::get('{path}'       , [WebClientController::class, 'path'])->where('path', '.+')->middleware(['roleAfterSession:client', 'auth']);
});
Route::prefix('/collecteurs')->group(function () {
    Route::get('/', function (Request $request) {
        if (!Auth::check())
            return view('layouts.collecteurs.account-login', ['path' => '']);
        if (!$request->session()->get('triethic')['roles']->containsStrict('collecteur'))
            return response()->json(['status' => false, 'message' => 'Access forbidden', 'result' => []], 401);
        return view('layouts.collecteurs.welcome', ['path' => '']);
    });
    Route::get('/{path}', function (Request $request, $path) {
        if (!Auth::check())
            return view('layouts.collecteurs.account-login', ['path' => $path]);
        return view('layouts.collecteurs.welcome', ['path' => $path]);
    })->where('path', '.+')->middleware(['roleAfterSession:collecteur', 'auth']);
});


