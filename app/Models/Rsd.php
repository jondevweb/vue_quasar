<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Collecte;

class Rsd extends Model
{
    use HasFactory;
    protected $fillable = [
        'collecte_id',
        'date_collecte',
        'date_dechargement',
        'dechet',
        'code_dechet',
        'unite',
        'quantite',
        'transporteur_entree_nom',
        'transporteur_entree_adresse',
        'transporteur_entree_immatriculation',
        'transporteur_entree_recepisse',
        'transporteur_sortie_nom',
        'transporteur_sortie_adresse',
        'transporteur_sortie_immatriculation',
        'transporteur_sortie_recepisse',
        'destination_regroupement_nom',
        'destination_regroupement_adresse',
        'destination_regroupement_code_traitement',
        'destination_finale_nom',
        'destination_finale_adresse',
        'destination_finale_traitement',
        'numero_bsd',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = [
            'collecte_id'       => 'required|integer',
            'date_collecte'     => 'required|date',
            'date_dechargement' => 'required|date',
            'dechet'            => 'required|max:255',
            'code_dechet'       => 'required|max:255',
            'unite'             => 'max:255',
            'quantite'          => 'required|integer',
            'numero_bsd'        => 'max:255',
            'transporteur_entree_nom'             => 'max:255',
            'transporteur_entree_adresse'         => 'max:255',
            'transporteur_entree_immatriculation' => 'max:255',
            'transporteur_entree_recepisse'       => 'max:255',
            'transporteur_sortie_nom'             => 'max:255',
            'transporteur_sortie_adresse'         => 'max:255',
            'transporteur_sortie_immatriculation' => 'max:255',
            'transporteur_sortie_recepisse'       => 'max:255',
            'destination_regroupement_nom'             => 'max:255',
            'destination_regroupement_adresse'         => 'max:255',
            'destination_regroupement_code_traitement' => 'max:255',
            'destination_finale_nom'        => 'max:255',
            'destination_finale_adresse'    => 'max:255',
            'destination_finale_traitement' => 'max:255',
        ];

        if (!$withRequired)
            $retour = [
                'collecte_id'       => 'integer',
                'date_collecte'     => 'date',
                'date_dechargement' => 'date',
                'dechet'            => 'max:255',
                'code_dechet'       => 'max:255',
                'unite'             => 'max:255',
                'quantite'          => 'integer',
                'numero_bsd'        => 'max:255',
                'transporteur_entree_nom'             => 'max:255',
                'transporteur_entree_adresse'         => 'max:255',
                'transporteur_entree_immatriculation' => 'max:255',
                'transporteur_entree_recepisse'       => 'max:255',
                'transporteur_sortie_nom'             => 'max:255',
                'transporteur_sortie_adresse'         => 'max:255',
                'transporteur_sortie_immatriculation' => 'max:255',
                'transporteur_sortie_recepisse'       => 'max:255',
                'destination_regroupement_nom'             => 'max:255',
                'destination_regroupement_adresse'         => 'max:255',
                'destination_regroupement_code_traitement' => 'max:255',
                'destination_finale_nom'        => 'max:255',
                'destination_finale_adresse'    => 'max:255',
                'destination_finale_traitement' => 'max:255',
            ];

        array_merge($retour, Entreprise::getFillableValidators($withRequired));
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function collecte()
    {
        return $this->belongsTo(Collecte::class);
    }
    public function store(Request $request, int $integrateur_id) {
        return DB::transaction(function ()   use (&$request, &$integrateur_id) {
            $rsd  = new Rsd;
            $rsd  = Rsd::create($request->only($rsd->getFillable()));
            return $rsd->id;
        });
    }
}
