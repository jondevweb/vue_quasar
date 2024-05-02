<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use App\Models\User;
use App\Models\Contact;
use App\Mail\ClientAccountCreation;
use App\Helpers\DB AS DBHelper;

class ContactController extends Controller
{
    public function exists(Request $request) {
        $request->validate(['email' => 'required|email', 'contact_id' => 'integer']);
        $query = User::where('email', $request->get('email'));

        if ($request->has('contact_id'))
            $query = $query->where('id', '<>', $request->get('contact_id'));
        $match = $query->get();

        if ($match->count() == 0)
            return response()->json(['status' => true, 'message' => '', 'result' => ['exists' => false]], 200);
        else
            return response()->json(['status' => true, 'message' => '', 'result' => ['exists' => true, 'id' => $match[0]->id]] , 200);
    }
    public function contact(Request $request, int $id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => User::join('client_user', 'client_user.user_id'  , '=', 'users.id')
                                                                                    ->join('clients'    , 'client_user.client_id', '=', 'clients.id')
                                                                                    ->where('clients.integrateur_id', $session['integrateurs'][0])
                                                                                    ->where('users.id', $id)
                                                                                    ->select(['users.*'])->first()] , 200);
    }
    public function convertAccount(Request $request, int $id) {
        $request->validate(['email' => 'required|email']);
        $contact = new Contact;
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => $contact->convert($request, $id, 'client')] , 200);
    }
    public function sendPasswordCreation(Request &$request, int $user_id, bool $getnewpassword = false) {
        $user = User::find($user_id);

        if ($user == null) {
          \Log::warning('Account not found: $user_id='.$user_id.', stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
          return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }
        $email = $user->email;
        /*
            // Risque de static::RESET_THROTTLED
            $status = Password::sendResetLink(['email'  => $email], function ($user, $token) use (&$email) {
                Mail::to($email)->send(new ClientAccountCreation($email, $token));
            });
            adaptation de ./vendor/laravel/framework/src/Illuminate/Auth/Passwords/PasswordBroker.php:    public function sendResetLink(array $credentials, Closure $callback = null)
                    et de ./root/vendor/laravel/framework/src/Illuminate/Auth/Passwords/PasswordBrokerManager.php
        */
        $app = app();
        $name   = $app['config']['auth.defaults.passwords'];
        $config = $app['config']["auth.passwords.{$name}"];
        $key = $app['config']['app.key'];
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        $connection = $config['connection'] ?? null;

        $tokens = new DatabaseTokenRepository(
            $app['db']->connection($connection),
            $app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0
        );

        $token = $tokens->create($user);
        Mail::to($email)->send(new ClientAccountCreation($email, $token));
        $user->invitation_envoyee = true;

        if (!$getnewpassword) {
            $user->save();
            return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
        }

        $password = Str::random(12);
        $user->password = Hash::make($password);
        $user->save();
        return response()->json(['status' => true, 'message' => '', 'result' => $password], 200);
    }
}
