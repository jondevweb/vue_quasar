<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;

class Gestionnaire extends Model
{
    use HasFactory;
    protected $fillable = [
        'entreprise_id',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $entreprise = new Entreprise;
        return $entreprise->getFillableValidators($withRequired, $except);
    }
    public function store(Request $request, int $integrateur_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id) {
            $entreprise   = new Entreprise;
            $entreprise   = Entreprise::create($request->only($entreprise->getFillable()));
            $gestionnaire = new Gestionnaire;

            $gestionnaire = Gestionnaire::create(['entreprise_id' =>  $entreprise->id]);
            return $gestionnaire->id;
        });
    }
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
}
