<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Mail\WorkerAccountCreation;
use App\Mail\WorkerResetPassword;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash
;use Illuminate\Support\Facades\Mail;
use App\Models\Integrateur;

class Worker extends Model
{
    use HasFactory;
    protected $fillable = [
        'couleur',
    ];
    public function integrateur()
    {
        return $this->belongsTo(Integrateur::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $user = new User;
        return array_merge($user->getFillableValidators($withRequired, $except), ['couleur' => 'max:45']);
    }
    public function store(Request $request) {
        $worker  = new Worker;
        $request->validate($worker->getFillableValidators());
        return DB::transaction(function ()   use (&$request, &$worker) {
            $session  = $request->session()->get('triethic');
            $user     = new User;
            $data     = $request->only($user->getFillable());
            $couleur  = isset($data['couleur']) ? $data['couleur'] : 'black';
            unset($data['couleur']);

            $email = $data['email'];
            $user = User::create(array_merge($data, ['password' => Hash::make(Str::random(190))]));
            $user->assignRole('worker');
            $worker->couleur        = $couleur;
            $worker->user_id        = $user->id;
            $worker->integrateur_id = $session['integrateurs'][0];
            $worker->save();

            $status = Password::sendResetLink(['email'  => $email], function ($user, $token) use (&$email, &$target) {
                Mail::to($email)->send(new WorkerAccountCreation($email, $token));
            });
            if ($status == Password::RESET_LINK_SENT) {
                $user->invitation_envoyee = true;
                $user->save();
            }
            else {
                $this->warn('An error occured when sending the creation\'s link: '.json_encode(['status' => __($status), 'user' => $user]));
                throw new Exception('Error while trying to send mail');
            }

            return $worker->id;
        });
    }
    public function look(string $pattern, string $fieldName = null) {
        if(empty($fieldName))
            $fieldName = 'nom';
        $user = new User;
        if (!in_array($fieldName, $user->getFillable())) {
            \Log::warning('Asked to look for a field that is not allowed! field='.$fieldName.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        return Worker::join('users', 'users.id', '=', 'workers.user_id')
                     ->where($fieldName, 'LIKE', '%'.$pattern.'%')->select(['workers.*', 'users.*'])->get();
    }
}
