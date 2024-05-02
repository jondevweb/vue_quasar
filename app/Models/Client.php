<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;
use App\Models\User;
use App\Models\Mobilier;
use App\Models\Pointcollecte;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'contrat',
        'nb_salarie',
        'entreprise_id',
        'integrateur_id',
        'gestionnaire_id',
        'user_id',
        'email',
        'telephone',
        'contact_juridique',
        'contact_principal',
        'contact_gestionnaire',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['contrat' => 'date', 'nb_salarie' => 'integer', 'gestionnaire_id' => 'nullable|integer', 'user_id' => 'integer', 'email' => 'email'
                              , 'telephone' => 'max:45', 'contact_principal' => 'numeric', 'contact_juridique' => 'numeric', 'contact_gestionnaire' => 'nullable|numeric'];

        if (!$withRequired)
            $retour = ['contrat' => 'date', 'nb_salarie' => 'integer', 'gestionnaire_id' => 'nullable|integer', 'user_id' => 'integer', 'email' => 'email'
                     , 'telephone' => 'max:45', 'contact_principal' => 'numeric', 'contact_juridique' => 'numeric', 'contact_gestionnaire' => 'nullable|numeric'];

        array_merge($retour, Entreprise::getFillableValidators($withRequired));
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
    public function pointcollectes()
    {
        return $this->belongsToMany(Pointcollecte::class);
    }
    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function store(Request $request, int $integrateur_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id) {
            $entreprise  = new Entreprise;
            $entreprise  = Entreprise::create($request->only($entreprise->getFillable()));
            $client      = new Client;

            $fillable    = $request->only($client->getFillable());
            if (!isset($fillable['contrat'])) $fillable['contrat'] = date('Y-m-d');
            $client      = Client::create(array_merge($fillable, ['entreprise_id' =>  $entreprise->id, 'integrateur_id' => $integrateur_id]));
            return $client->id;
        });
    }
    public function localUpdate(Request $request, int $id, int $integrateur_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id, &$id) {
            $entreprise = new Entreprise;
            $client     = new Client;
            Entreprise::join('clients', 'entreprises.id', '=', 'clients.entreprise_id')
                      ->where('clients.id', $id)
                      ->where('integrateur_id', $integrateur_id)
                      ->update($request->only($entreprise->getFillable()));
            Client::where('id', $id)->where('integrateur_id', $integrateur_id)->update($request->only($client->getFillable()));
            return true;
        });
    }
}
