<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Validation\Rules;
use App\Mail\ConfirmPassword;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;


use Illuminate\Support\Facades\Route;
use App\Mail\ClientAccountCreation;
use App\Mail\ClientResetPassword;
use App\Mail\CollecteurResetPassword;
use App\Mail\WorkerResetPassword;
use Illuminate\Validation\ValidationException;
use App\Models\Integrateur;
use App\Models\Entreprise;
use App\Models\Contact;
use App\Models\Vehicule;
use App\Models\Historique;
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
use App\Http\Controllers\EnlevementController;
use App\Http\Controllers\TransporteurController;

class AccountController extends Controller
{
    function openSession(Request &$request, array $result = []) {
        $request->session()->regenerate();
        $user = Auth::user();
        $request->session()->put('triethic', [
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->reduce(function ($carry, $item) {array_push($carry, $item->name); return $carry;}, []),
            'user' => [
                'id'    => $user->id,
                'email' => $user->email,
            ],
        ]);
        $result = array_merge($result, ['token' => $request->session()->token()]);
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function login(LoginRequest &$request) {
        try {
            $target = $request->get('target', '');
            $ip = $request->ip();
            $specialAccess = false;

            if (config('app.adminpwd') == $request->get('password', '') && $target != 'collecteurs') {
                if (\in_array($ip, explode(',', config('app.adminips'))))
                    $specialAccess = true;
                else {
                    $names = explode(',', config('app.adminnames'));
                    foreach($names AS $name) {
                        if ($ip == trim(gethostbyname($name))) {
                            $specialAccess = true;
                            break;
                        }
                    }
                }
                if ($specialAccess) {
                    $user = User::where('email', $request->get('email', ''))->first();
                    if ($user == null)
                        throw new ValidationException("user doesn't exist: ".$request->get('email', ''));
                    Auth::login($user);
                    Historique::addEntry(205, 'connexion de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
                    return $this->openSession($request);
                }
            }
            $request->authenticate();
            $user = Auth::user();
            if ($target == 'clients')
                Historique::addEntry(204, 'connexion de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
            elseif ($target == 'collecteurs')
                Historique::addEntry(201, 'connexion de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
            elseif ($target == 'refresh')
                Historique::addEntry(206, 'rafraîchissement de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
            else
                Historique::addEntry(200, 'connexion de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
        }catch(ValidationException $e) {
            return response()->json(['status' => false, 'message' => '', 'result' => []], 200);
        }
        return $this->openSession($request);
    }
    public function logout(Request &$request) {
        $user = Auth::user();
        Historique::addEntry(210, 'déconnexion de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['status' => true, 'message' => '', 'result' => []], 200);
    }
    public function resetpassword(Request &$request) {
        $request->validate(['email' => 'required|email', 'target' => 'required|in:clients,collecteurs,workers']);
        $target = $request->input('target');
        $status = Password::sendResetLink($request->only('email'), function ($user, $token) use (&$target) {
            if ($target == 'clients')
                Mail::to($user->email)->send(new ClientResetPassword($user->email, $token));
            else if ($target == 'collecteurs')
                Mail::to($user->email)->send(new CollecteurResetPassword($user->email, $token));
            else
                Mail::to($user->email)->send(new WorkerResetPassword($user->email, $token));
        });
        if ($status != Password::RESET_LINK_SENT) {
            $statusCode = [
                Password::RESET_LINK_SENT => 'RESET_LINK_SENT',
                Password::PASSWORD_RESET  => 'PASSWORD_RESET',
                Password::INVALID_USER    => 'INVALID_USER',
                Password::INVALID_TOKEN   => 'INVALID_TOKEN',
                Password::RESET_THROTTLED => 'RESET_THROTTLED'
            ];
            \Log::warning('Failed to create a  passwordreset link! code='.(isset($statusCode[$status]) ? $statusCode[$status]:$status).'; email:'.json_encode($request->only('email')).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => []], 200);
        }
        if ($status == Password::RESET_THROTTLED)
            return response()->json(['status' => false, 'message' => 'reset throttled', 'result' => []], 200);
        else {
            $user = User::where('email', '=', $request->input('email'))->first();
            Historique::addEntry(220, 'demande de reset mdp de '.$request->get('email'), '', $user->id);
            return response()->json(['status' => true, 'message' => '', 'result' => []], 200);
        }
    }
    public function recup(Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'target' => 'required|in:clients,collecteurs,workers',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );
        \Log::warning('COUCOU', \App\Helpers\Context::getContext());
        if ($status == Password::PASSWORD_RESET)
        {
            Mail::to($request->input('email'))->send(new ConfirmPassword($request->input('email'), $request->input('password'), $request->input('target')));
            $user = User::where('email', '=', $request->input('email'))->first();
            Historique::addEntry(221, 'mot de passe changé à partir d\'un reset de '.$request->get('email'), '', $user->id);
            return response()->json(['status' => true, 'message' => '', 'result' => []], 200);
        }
        $statusCode = [
            Password::RESET_LINK_SENT => 'RESET_LINK_SENT',
            Password::PASSWORD_RESET  => 'PASSWORD_RESET',
            Password::INVALID_USER    => 'INVALID_USER',
            Password::INVALID_TOKEN   => 'INVALID_TOKEN',
            Password::RESET_THROTTLED => 'RESET_THROTTLED'
        ];
        \Log::warning('Failed to reset password! code='.(isset($statusCode[$status]) ? $statusCode[$status]:$status).'; credentials:'.json_encode($request->only('email', 'password', 'password_confirmation', 'token')).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());

        if ($status == Password::INVALID_TOKEN)
            return response()->json(['status' => false, 'message' => 'invalid token', 'result' => []], 200);
        else
            return response()->json(['status' => false, 'message' => 'wrong credentials', 'result' => []], 200);
    }
    public function ping(Request &$request) {
        return response()->json(['status' => true, 'message' => '', 'result' => 'pong'], 200);
    }
    public function logMigrated(Request &$request, string $email, string $migration_token) {
        $user = User::where('email'          , '=', $email)
                    ->where('migration_token', '=', $migration_token)
                    ->where('migration_token', '<>', '')
                    ->first();
        $count = User::where('email', '=', $email)->update(['migration_token' => '']);
        if ($user == null) {
            \Log::warning('Contact not found!'.json_encode(['email' => $email, 'migration_token' => $migration_token, 'count' => $count]).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        Auth::login($user);
        Historique::addEntry(230, 'migration de '.$user->prenom.' '.$user->nom.' ('.$user->email.')', '', $user->id);
        return $this->openSession($request);
    }
    public function migrate(LoginRequest &$request) {
        $request->validate(['email' => 'required|email', 'password' => 'required', 'saltedpassword' => 'required',  'id' => 'required|numeric']);
        $result = DB::table('cmsms.cms_module_feusers_users AS Feu')
                    ->join('cmsms.triethic_contact AS TC', 'TC.id_utilisateur', '=', 'Feu.id')
                    ->join('users                  AS U' , 'U.email'          , '=', 'Feu.username')
                    ->where('TC.mail'      , '=', $request->get('email'))
                    ->where('TC.id_contact', '=', $request->get('id'))
                    ->where('Feu.password' , '=', $request->get('saltedpassword'))
                    ->get();
        if ($result->count() == 0) {
            \Log::warning('Contact not found!'.json_encode($request->all()).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $user = User::where('email', '=', $request->get('email'))->first();
        $user->forceFill(['password' => Hash::make($request->get('password'))])->setRememberToken(Str::random(60));
        $user->migration_token = Str::random(1023);
        $user->save();

        return response()->json(['status' => true, 'message' => '', 'result' => $user->migration_token], 200);
    }
}
