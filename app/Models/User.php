<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Helpers\IorModelTraits;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, IorModelTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'portable',
        'civilite',
        'poste',
        'actif',
        'migration_token',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['nom' => 'max:255', 'prenom' => 'max:255', 'email' => 'required|email', 'password' => 'max:255', 'telephone' => 'max:45'
                 , 'portable' => 'max:45', 'civilite' => 'bool', 'actif' => 'bool', 'poste' => 'max:255'];

        if(!$withRequired)
            $retour = ['nom' => 'max:255', 'prenom' => 'max:255', 'email' => 'email', 'password' => 'max:255', 'telephone' => 'max:45'
                     , 'portable' => 'max:45', 'civilite' => 'bool', 'actif' => 'bool', 'poste' => 'max:255'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
