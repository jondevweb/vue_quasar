<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Mail\ClientAccountCreation;
use App\Mail\ClientResetPassword;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash
;use Illuminate\Support\Facades\Mail;

class Contact extends Model
{
    use HasFactory;
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $user = new User;
        return $user->getFillableValidators($withRequired, $except);
    }
    public function associate(User &$user, int $target_id, string $target) {
        if ($target == 'client')     {
            $user->assignRole('client');
            DB::table('client_user')->insert(['client_id' => $target_id, 'user_id' => $user->id]);
        }
        if ($target == 'collecteur') {
            $user->assignRole('collecteur');
            DB::table('integrateur_user')->insert(['integrateur_id' => $target_id, 'user_id' => $user->id]);
        }
        if ($target == 'gestionnaire') {
            $user->assignRole('collecteur');
            DB::table('gestionnaire_user')->insert(['gestionnaire_id' => $target_id, 'user_id' => $user->id]);
        }
    }
    public function convert(Request $request, int $contact_id, string $target) {
        return DB::transaction(function ()   use (&$request, &$contact_id, &$target) {
            $user  = User::find($contact_id);
            if ($user == null) return null;
            $user->email = $request->get('email');
            $email = $user->email;

            $user->password = Hash::make(Str::random(190));
            $user->actif    = true;
            $user->invitation_envoyee = true;
            $user->save();

            $status = Password::sendResetLink(['email'  => $email], function ($user, $token) use (&$email, &$target) {
                if ($target == 'client'    ) Mail::to($email)->send(new ClientAccountCreation($email, $token));
                if ($target == 'collecteur') Mail::to($email)->send(new CollecteurAccountCreation($email, $token));
            });
            if ($status != Password::RESET_LINK_SENT) {
                \Log::warning('An error occured when sending the creation\'s link: '.json_encode(['status' => __($status), 'user' => $user, '$email'=>$email]), \App\Helpers\Context::getContext());
                throw new \Exception('Error while trying to send mail');
            }
            return $user->id;
        });

    }
    public function store(Request $request, int $target_id, string $target, bool $regularUser) {
        return DB::transaction(function ()   use (&$request, &$target_id, &$target, &$regularUser) {
            $user  = new User;
            $data = $request->only($user->getFillable());
            $email = $data['email'];
            unset($data['contact_juridique']);unset($data['contact_principal']);//doit être fait en amont car l'ID associé (Client ou autre) n'est pas reçu ici

            if ($email == '')
                $data['email'] = 'random-ior-'.Str::random(140).'@icionrecycle.fr';

            $user = User::create(array_merge($data, ['password' => Hash::make(Str::random(190))]));
            $this->associate($user, $target_id, $target);
            if ($regularUser) {
                $status = Password::sendResetLink(['email'  => $email], function ($user, $token) use (&$email, &$target) {
                    if ($target == 'client'    ) Mail::to($email)->send(new ClientAccountCreation($email, $token));
                    if ($target == 'collecteur') Mail::to($email)->send(new CollecteurAccountCreation($email, $token));
                });
                if ($status == Password::RESET_LINK_SENT) {
                    $user->invitation_envoyee = true;
                    $user->save();
                }
                else {
                    $this->warn('An error occured when sending the creation\'s link: '.json_encode(['status' => __($status), 'user' => $user]));
                    throw new Exception('Error while trying to send mail');
                }
            } else {
                $user->actif = false;
                $user->save();
            }
            return $user;
        });
    }
}
