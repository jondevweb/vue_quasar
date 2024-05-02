<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Helpers\DB AS DBH;
use App\Models\User;
use App\Models\Historiquetype;

class Historique extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'pointcollecte_id',
        'historiquetype_id',
        'complement_id',
        'resume',
        'description',
        'created_at',
        'updated_at'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function historiquetype()
    {
        return $this->belongsTo(Historiquetype::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['user_id' => 'integer|nullable', 'pointcollecte_id' => 'integer|nullable', 'historiquetype_id' => 'integer|nullable',  'resume' => 'max:255',
                   'description' => 'max:4096', 'created_at' => 'date', 'updated_at' => 'date'
        ];

        if (!$withRequired)
            $retour = ['user_id' => 'integer|nullable', 'pointcollecte_id' => 'integer|nullable', 'historiquetype_id' => 'integer|nullable',  'resume' => 'max:255',
                       'description' => 'max:4096', 'created_at' => 'date', 'updated_at' => 'date'
            ];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $historique = Historique::create($fillable);
            return $historique->id;
        });
    }
    static public function addEntry(int $historiquetype_id, string $resume, string $description, int $user_id = null, int $pointcollecte_id = null, int $complement_id = null) {
        return DB::transaction(function ()   use (&$historiquetype_id, &$resume, &$description, &$user_id, &$pointcollecte_id, &$complement_id) {
            $fillable = ['historiquetype_id' => $historiquetype_id, 'resume' => $resume, 'description' => $description];
            if ($user_id)     $fillable['user_id']     = $user_id;
            if ($description) $fillable['description'] = $description;
            if ($complement_id) $fillable['complement_id'] = $complement_id;

            $historique = Historique::create($fillable);
            return $historique->id;
        });
    }
    static public function list(HistoriqueTypes $type = null, int $complement_id = null, Carbon $start = null, Carbon $end = null) {
        $user = new User;
        $historique = new Historique;
        $historiquetype = new Historiquetype;
        $query = Historique::leftJoin('users'          , 'users.id'          , 'historiques.user_id')
                           ->leftJoin('historiquetypes', 'historiquetypes.id', 'historiques.historiquetype_id');
        if ($type != null)
            $query = $query->whereBetween('historiquetype_id', [$type->getInf(), $type->getSup()]);
        if ($start != null)
            $query = $query->where('historiques.created_at'   , '>=', $start->format('Y-m-d H:i:s'));
        if ($end != null)
            $query = $query->where('historiques.created_at'   , '<=', $end->format('Y-m-d H:i:s'));
        if ($complement_id != null)
            $query = $query->where('historiques.complement_id', '=', $complement_id);
        $query = $query->orderBy('historiques.updated_at', 'desc');
        $collect = $query->select(array_merge(DBH::getColumnsForSelect($historique)
                                        , DBH::getColumnsForSelect($user)
                                        , DBH::getColumnsForSelect($historiquetype)))->get();
        $collect = DBH::convertToObject($collect, $historique, ['historiquetype' => ['instance' => $historiquetype], 'user' => ['instance' => $user]]);
        foreach ($collect as &$historique) {
            $historique->user->password = null;
        }
        return $collect;
    }
}
