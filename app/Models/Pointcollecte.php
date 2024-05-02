<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\Dechet;
use App\Models\Mobilier;
use App\Helpers\IorModelTraits;

class Pointcollecte extends Model
{
    use HasFactory, IorModelTraits;
    protected $fillable = [
        'client_id',
        'nom',
        'telephone',
        'adresse',
        'commentaire',
        'ascenseur',
        'parking',
        'badge_acces',
        'hauteur',
        'batiment',
        'code_acces',
        'creneaux',
        'producteur_dechet',
        'coordonnees'
    ];
    private $defaultLook = 'nom';
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['client_id' => 'int', 'nom' => 'required|max:255', 'adresse' => 'required|max:255', 'telephone' => 'max:45'
                 , 'commentaire' => 'max:1024', 'ascenseur' => 'max:1024', 'parking' => 'max:1024', 'badge_acces' => 'max:1024'
                 , 'hauteur' => 'max:1024', 'batiment' => 'max:1024', 'code_acces' => 'max:1024', 'creneaux' => 'max:1024'
                 , 'producteur_dechet' => 'bool', 'coordonnees' => 'required|regex:/^POINT\(-?[0-9]+\.[0-9]+ -?+[0-9]+\.[0-9]+\)$/'];
        if(!$withRequired)
            $retour = ['client_id' => 'int', 'nom' => 'max:255', 'adresse' => 'max:255', 'telephone' => 'max:45'
                    , 'commentaire' => 'max:1024', 'ascenseur' => 'max:1024', 'parking' => 'max:1024', 'badge_acces' => 'max:1024'
                    , 'hauteur' => 'max:1024', 'batiment' => 'max:1024', 'code_acces' => 'max:1024', 'creneaux' => 'max:1024'
                    , 'producteur_dechet' => 'bool', 'coordonnees' => 'regex:/^POINT\(-?[0-9]+\.[0-9]+ -?+[0-9]+\.[0-9]+\)$/'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function mobiliers()
    {
        return $this->belongsToMany(Mobilier::class);
    }
    public function dechet()
    {
        return $this->belongsToMany(Dechet::class);
    }
    public function store(Request &$request, int $integrateur_id, $client_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id, &$client_id) {
            // Il faudrait normalement vérifier que le client est bien associé à l'integrateur
            $pointcollecte = new Pointcollecte;
            $fillable = $request->only($pointcollecte->getFillable());
            if (isset($fillable['coordonnees']))
                $fillable['coordonnees'] = DB::raw('GeomFromText(\''.$fillable['coordonnees'].'\')');
            else
                $fillable['coordonnees'] = DB::raw('GeomFromText(\'POINT(0 0)\')');
            $pointcollecte = Pointcollecte::create(array_merge($fillable
                                                              , ['client_id' =>  $client_id/*, 'coordonnees' => 'GeomFromText(\'POINT(0 0)\')'*/]));
            return $pointcollecte->id;
        });
    }
    static public function associateToDechets(int $pointcollecte_id, array $dechets) {
        DB::table('dechet_pointcollecte')->insertOrIgnore(array_reduce($dechets, function($acc, $value) use (&$pointcollecte_id) {
            array_push($acc, ['dechet_id' => $value, 'pointcollecte_id' => $pointcollecte_id]);
            return $acc;
        }, []));
        return true;
    }
    static public function listByClients(array $client_ids)
    {
        return Pointcollecte::join('clients'    , 'pointcollectes.client_id', '=', 'clients.id')
                            ->join('entreprises', 'clients.entreprise_id'   , '=', 'entreprises.id')
                            ->whereRaw('client_id IN('.implode(',', $client_ids).')')->select(['pointcollectes.id', 'pointcollectes.nom', 'pointcollectes.client_id', 'entreprises.raison_sociale', 'entreprises.siret'])->get();
    }
    static public function listWithSocieties(array $pointcollecte_ids)
    {
/*
SELECT P.id AS pointcollecte_id, P.client_id, C.entreprise_id, P.nom AS pointcollecte, P.adresse, E.siret, E.raison_sociale, E.adresse_administrative
FROM pointcollectes P
INNER JOIN clients     C ON C.id = P.client_id
INNER JOIN entreprises E ON E.id = C.entreprise_id
*/
        return Pointcollecte::join('clients     AS C', 'pointcollectes.client_id', '=', 'C.id')
                            ->join('entreprises AS E', 'C.entreprise_id'         , '=', 'E.id')
                            ->whereIn('pointcollectes.id', $pointcollecte_ids)
                            ->select([DB::raw('pointcollectes.id  AS pointcollecte_id'), 'pointcollectes.client_id', 'C.entreprise_id'
                                    , DB::raw('pointcollectes.nom AS pointcollecte'   ), 'pointcollectes.adresse'
                                    , 'E.siret', 'E.raison_sociale', 'E.adresse_administrative', 'C.code_trackdechet'])
                            ->get();
    }

}
