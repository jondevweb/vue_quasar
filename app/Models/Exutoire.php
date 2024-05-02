<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;
use App\Models\Dechet;

class Exutoire extends Model
{
    use HasFactory;
    protected $fillable = [
        'entreprise_id',
        'email',
        'contact',
        'telephone'
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['email' => 'email|max:255', 'contact' => 'max:255', 'telephone' => 'required|max:45'];

        if (!$withRequired)
            $retour = ['email' => 'email', 'contact' => 'max:255', 'telephone' => 'max:45'];

        array_merge($retour, Entreprise::getFillableValidators($withRequired));
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(Request $request, int $integrateur_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id) {
            $entreprise   = new Entreprise;
            $entreprise   = Entreprise::create($request->only($entreprise->getFillable()));
            $gestionnaire = new Exutoire;

            $gestionnaire = Exutoire::create(['entreprise_id' =>  $entreprise->id]);
            return $gestionnaire->id;
        });
    }
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
    public function dechet()
    {
        return $this->belongsToMany(Dechet::class);
    }
    public function look(string $pattern, string $fieldName = null) {
        if(empty($fieldName))
            $fieldName = 'raison_sociale';
        $entreprise = new Entreprise;
        if (!in_array($fieldName, $entreprise->getFillable())) {
            \Log::warning('Asked to look for a field that is not allowed! field='.$fieldName.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        return Exutoire::join('entreprises', 'entreprises.id', '=', 'exutoires.entreprise_id')
                     ->where($fieldName, 'LIKE', '%'.$pattern.'%')->select(['exutoires.*', 'entreprises.*'])->get();
    }
}
