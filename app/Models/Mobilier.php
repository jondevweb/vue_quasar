<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Integrateur;

class Mobilier extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'nom',
        'caracteristiques',
        'photo',
        'quantite',
        'integrateur_id',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['type' => 'max:255', 'nom' => 'required|max:255', 'caracteristiques' => 'max:4096'
                 , 'photo' => 'max:255', 'quantite' => 'numeric|min:0', 'integrateur_id' => 'numeric'];

        if (!$withRequired)
            $retour = ['type' => 'max:255', 'nom' => 'max:255', 'caracteristiques' => 'max:4096'
                     , 'photo' => 'max:255', 'quantite' => 'numeric|min:0', 'integrateur_id' => 'numeric'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function integrateur()
    {
        return $this->belongsTo(Integrateur::class);
    }
    static public function listByPointcollecte(string $type, array $pointcollecte_ids) {
        $query = DB::table('mobiliers AS M')
                   ->join('mobilier_pointcollecte AS MP', 'M.id' , '=', 'MP.mobilier_id')
                   ->join('pointcollectes         AS PC', 'PC.id', '=', 'MP.pointcollecte_id')
                   ->whereIn('MP.pointcollecte_id', $pointcollecte_ids)
                   ->where('M.type', '=', $type);

        $query = $query->select(['M.nom', 'M.photo', 'PC.nom AS pointcollecte', 'MP.localisation', 'MP.appartient_client', 'MP.quantite']);
        return $query->get();
    }
}
