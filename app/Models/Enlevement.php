<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Dechet;
use App\Models\Exutoire;
use App\Models\Collecte;
use App\Jobs\GenerationDocumentsEnlevement;


class Enlevement extends Model
{
    use HasFactory;
    protected $fillable = [
        'immatriculation',
        'date',
        'exutoire_id',
        'transporteur_id',
        'dechet_id',
    ];
    public function dechet()
    {
        return $this->belongsTo(Dechet::class);
    }
    public function exutoire()
    {
        return $this->belongsTo(Exutoire::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['immatriculation' => 'required|max:45', 'date' => 'required|date', 'exutoire_id' => 'required|numeric', 'dechet_id' => 'required|numeric', 'transporteur_id' => 'required|numeric'];

        if (!$withRequired)
            $retour = ['immatriculation' => 'max:45', 'date' => 'date', 'exutoire_id' => 'numeric', 'dechet_id' => 'numeric', 'transporteur_id' => 'numeric'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $enlevement = Enlevement::create($fillable);
            return $enlevement->id;
        });
    }
    public static function removalWT(int $dechet_id, array $fillable, int $integrateur_id) {
        $exutoire = DB::table('exutoires')
                        ->join('dechet_exutoire', 'dechet_exutoire.exutoire_id', '=', 'exutoires.id')
                        ->join('dechets', 'dechets.id', '=', 'dechet_exutoire.dechet_id')
                        ->where('exutoires.id', '=', $fillable['exutoire_id'])
                        ->where('dechet_exutoire.dechet_id', '=', $dechet_id)
                        ->select(['dechets.trigramme', 'dechets.id AS dechet_id'])
                        ->first();
        if ($exutoire == null) {
            $message = 'Asked to register a waste to an invalid exutoire! fillable='.\json_encode($fillable).'; dechet_id='.$dechet_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            throw(new \Exception($message));
        }
        $collectes = Collecte::join('passages'      , 'passages.id'      , '=', 'collectes.passage_id')
                                ->join('pointcollectes', 'pointcollectes.id', '=', 'passages.pointcollecte_id')
                                ->join('clients'       , 'clients.id'       , '=', 'pointcollectes.client_id')
                                ->whereNull('collectes.exutoire_id')
                                ->where('clients.integrateur_id', $integrateur_id)
                                ->where('passages.date_debut', '<=', $fillable['end'])
                                ->where('collectes.statut'   , '=' , 50)
                                ->where('collectes.dechet_id', '=' , $dechet_id)
                                ->select('collectes.*')
                                ->get();
        if ($collectes->count() == 0) {
            $message = 'Asked to retrieve waste, but there is no waste! fillable='.\json_encode($fillable).'; dechet_id='.$dechet_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            throw(new \Exception($message));
        }
        $fillable['dechet_id'] = $exutoire->dechet_id;
        $enlevement = Enlevement::create($fillable);
        $collecte_ids = $collectes->reduce(function ($acc, $value) {
            array_push($acc, $value->id);
            return $acc;
        }, []);
        $tmp = $collecte_ids;
        while(count($tmp) > 0)
            Collecte::join('passages', 'passages.id', '=', 'collectes.passage_id')
                    ->whereIn('collectes.id', array_splice($tmp, -100))->update(['collectes.statut' => 90, 'collectes.enlevement_id' => $enlevement->id
            , 'collectes.numero_certificat' => DB::raw("CONCAT('".$exutoire->trigramme."',' ',DATE_FORMAT(CURDATE(),'%y%m'),'-',passages.pointcollecte_id, '-',LPAD(CONV(collectes.id, 10 , 36 ), 8, '0'))")]);

        $sql = <<<'END'
        UPDATE rsds R
        INNER JOIN collectes     C  ON C.id  = R.collecte_id
        INNER JOIN enlevements   E  ON E.id  = C.enlevement_id    AND E.id = ?
        INNER JOIN transporteurs T  ON T.id  = E.transporteur_id
        INNER JOIN entreprises   Et ON Et.id = T.entreprise_id
        INNER JOIN exutoires     EX ON EX.id = E.exutoire_id
        INNER JOIN entreprises   Ex ON Ex.id = EX.entreprise_id
        SET  destination_finale_nom  = Ex.raison_sociale, destination_finale_adresse  = Ex.adresse_administrative, destination_finale_traitement = 'Recyclage'                 -- destination finale
            , transporteur_sortie_nom = Et.raison_sociale, transporteur_sortie_adresse         = Et.adresse_administrative                                                      -- transporteur sortie
                                                        , transporteur_sortie_immatriculation = E.immatriculation, transporteur_sortie_recepisse = T.recepisse
            , numero_bsd              = C.numero_certificat
        END;
        DB::statement($sql, [$enlevement->id]);
        while(count($collecte_ids) > 0)
            GenerationDocumentsEnlevement::dispatch($dechet_id, array_splice($collecte_ids, -5));

        return true;
    }
    public static function removal(int $dechet_id, array $fillable, int $integrateur_id) {
        return DB::transaction(function ()   use (&$dechet_id, &$fillable, &$integrateur_id) {
            return Enlevement::removalWT($dechet_id, $fillable, $integrateur_id);
        });
    }
}
