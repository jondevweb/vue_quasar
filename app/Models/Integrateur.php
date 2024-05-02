<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;

class Integrateur extends Model
{
    use HasFactory;
    protected $fillable = [
        'param_calendrier',
        'param_acces_externe',
        'entreprise_id',
        'email',
        'bioemail',
        'signature_cap',
        'bordereau_passage',
        'telephone',
        'signataire',
    ];
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
    static public function store(Request $request) {
        return DB::transaction(function ()   use (&$request) {
            $entreprise  = new Entreprise;
            $entreprise  = Entreprise::create($request->only($entreprise->getFillable()));
            $integrateur = new Integrateur;
            $integrateur = Integrateur::create(array_merge($request->only($integrateur->getFillable()), ['entreprise_id' =>  $entreprise->id]));
            return $integrateur->id;
        });
    }
}
