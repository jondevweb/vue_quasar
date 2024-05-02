<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Pointcollecte;
use Illuminate\Validation\Rules;
use App\Mail\ConfirmPassword;
use App\Mail\Html;
use App\Models\Historique;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;

class UserCompteController extends Controller
{
    public function compte(Request &$request) {
        $id = $request->session()->get('triethic')['user']['id'];
        $user   = User::where('id', $id)->first();
        $result = [];
        $result['clients'] = array_reduce(DB::select(
                                                    'SELECT *
                                                    FROM client_user
                                                    WHERE user_id = ?', [$id]), function ($carry, $item) {array_push($carry, $item->client_id); return $carry;}, []);
        if (count($result) == 0)
            return response()->json(['status' => false, 'message' => '', 'result' => []], 200);

        $result['integrateurs']   =  Client::whereIn('id', $result['clients'])->select(DB::raw('DISTINCT integrateur_id'))
                                           ->get()
                                           ->reduce(function($acc, $value){array_push($acc, $value->integrateur_id);return $acc;}, []);
        $result['pointcollectes'] = Pointcollecte::listByClients($result['clients']);
        $result['parametres'] = DB::select('SELECT * FROM parametres WHERE user_id = ?', [$id])[0];

        $session = $request->session()->get('triethic');
        $session['clients']      = $result['clients'];
        $session['integrateurs'] = $result['integrateurs'];
        $session['pointcollectes'] = array_reduce($result['pointcollectes']->toArray(), function ($carry, $item) {array_push($carry, $item['id']); return $carry;}, []);
        $request->session()->put('triethic', $session);
        $user->clients        = $result['clients'];
        $user->pointcollectes = $result['pointcollectes'];
        $user->parametres     = $result['parametres'];
        $user->ip             = $request->ip();
        return response()->json(['status' => true, 'message' => '', 'result' => $user], 200);
    }
    public function listPointcollecte(Request &$request) {
        $session = $request->session()->get('triethic');
        $query = Pointcollecte::whereIn('id', $session['pointcollectes'])->select(['id', 'nom']);
        return response()->json(['status' => true, 'message' => '', 'result' => $query->get()], 200);
    }
    public function parametresUpdate(Request &$request) {
        $allowedFields = ['notification_pesee' => 'boolean', 'notification_enlevement' => 'boolean'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));

        $id   = $request->session()->get('triethic')['user']['id'];
        DB::table('parametres')->where('user_id', $id)->update($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' => '', 'user_id' => $id], 200);
    }
    public function update(Request &$request) {
        $allowedFields = ['nom' => 'max:255', 'prenom' => 'max:255', 'password' => 'max:255', 'telephone' => 'max:45', 'portable' => 'max:45', 'civilite' => 'bool', 'poste' => 'max:255'];
        $request->validate($allowedFields);
        $fillable = $request->only(array_keys($allowedFields));

        $id   = $request->session()->get('triethic')['user']['id'];
        $user = User::where('id', $id)->first();
        $user->update($fillable);
        $user->save();
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function contact(Request &$request) {
        $allowedFields = ['motif' => 'required|max:255', 'message' => 'required|max:4096', 'pointcollecte_id' => 'required|integer'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');

        $fillable = $request->only(array_keys($allowedFields));
        $message = \str_replace("\n", '<BR>', $request->input('message'));

        $result = DB::table('pointcollectes AS PC')
                    ->join('clients         AS C', 'C.id', '=', 'PC.client_id')
                    ->join('integrateurs    AS I', 'I.id', '=', 'C.integrateur_id')
                    ->where('PC.id', '=', $request->input('pointcollecte_id'))
                    ->select('I.email')
                    ->get();
        if ($result->count() != 1) {
            \Log::warning('A pointccollecte is not associated to an integrateur! pointcollecte_id='.$pointcollecte_id.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }


        Mail::to($result[0]->email)->cc($session['user']['email'])->send(new Html($request->input('motif'), $message));
        Historique::addEntry(600, $request->input('motif'), $request->input('message'), $session['user']['id'], $request->input('pointcollecte_id'));

        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function password(Request &$request) {
        $allowedFields = [
            'password' => ['required', Rules\Password::min(8)->uncompromised()],
            'oldPassword' => ['required'],
        ];

        $request->validate($allowedFields);

        $fillable = $request->only(array_keys($allowedFields));
        $id   = $request->session()->get('triethic')['user']['id'];
        $user = User::where('id', $id)->first();
        $fillable['password_confirmation'] = $fillable['password'];
        $fillable['token'] = $request->session()-> token();
        $fillable['email'] = $user->email;
        if (!Auth::attempt(['email' => $user->email, 'password' => $fillable['oldPassword']])) {
            \Log::warning('Failed to reset password because of wrong old password! ; credentials:'.json_encode($fillable).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => 'bad password', 'result' => []], 200);
        }
        unset($fillable['oldPassword']);
        $userCanReset = Password::getUser($fillable);
        $token = Password::createToken($userCanReset);
        $fillable['token'] = $token;
        $status = Password::reset(
            $fillable,
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET)
        {
            Mail::to($user->email)->send(new ConfirmPassword($user->email, $fillable['password'],'client'));
            return response()->json(['status' => true, 'message' => '', 'result' => []], 200);
        }
        $statusCode = [
            Password::RESET_LINK_SENT => 'RESET_LINK_SENT',
            Password::PASSWORD_RESET => 'PASSWORD_RESET',
            Password::INVALID_USER => 'INVALID_USER',
            Password::INVALID_TOKEN => 'INVALID_TOKEN',
            Password::RESET_THROTTLED => 'RESET_THROTTLED'
        ];
        \Log::warning('Failed to reset password! code='.(isset($statusCode[$status]) ? $statusCode[$status]:$status).'; credentials:'.json_encode($request->only('email', 'password', 'password_confirmation', 'token')).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());

        if ($status == Password::INVALID_TOKEN)
            return response()->json(['status' => false, 'message' => 'invalid token', 'result' => []], 200);
        else
            return response()->json(['status' => false, 'message' => 'wrong credentials', 'result' => []], 200);
    }
}
