<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobilierPointcollecte extends Model
{
    use HasFactory;
    protected $fillable = [
        'pointcollecte_id',
        'mobilier_id',
        'localisation',
        'appartient_client',
        'quantite',
    ];
    protected $table = 'mobilier_pointcollecte';
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['pointcollecte_id' => 'required|numeric', 'mobilier_id' => 'required|numeric', 'localisation' => 'max:255', 'appartient_client' => 'boolean', 'quantite' => 'numeric'];

        if (!$withRequired)
            $retour = ['pointcollecte_id' => 'numeric', 'mobilier_id' => 'numeric', 'localisation' => 'max:255', 'appartient_client' => 'boolean', 'quantite' => 'numeric'];

        array_merge($retour, Entreprise::getFillableValidators($withRequired));
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }

    public function store(Request $request) {
        $mp  = new MobilierPointcollecte;
        $request->validate($mp->getFillableValidators());
        DB::unprepared('SET autocommit=0');

        DB::unprepared('LOCK TABLES mobiliers WRITE, mobilier_pointcollecte WRITE');
        $mobilier_id = $request->get('mobilier_id');
        $mobilier = Mobilier::where('quantite', '>=', $request->get('quantite'))->where('id', $mobilier_id)->first();
        if ($mobilier == null) {
            DB::unprepared('ROLLBACK');
            DB::unprepared('UNLOCK TABLES');
            return -1;
        }
        $mobilier->quantite = $mobilier->quantite - $request->get('quantite');
        $mp  = MobilierPointcollecte::create($request->only($mp->getFillable()));
        $mobilier->save();

        DB::unprepared('COMMIT');
        DB::unprepared('UNLOCK TABLES');
        return $mp->id;
    }
}
