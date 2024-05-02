<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Pointcollecte;
use App\Models\Collecte;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Paths;
use App\Models\Document;

class Passage extends Model
{
    use HasFactory;
    protected $fillable = [
        'date_debut',
        'date_fin',
        'statut',
        'motif_passage_vide',
        'immatriculation',
        'pointcollecte_id',
        'vehicule_id',
        'transporteur_id',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['date_debut' => 'required|datetime', 'date_fin' => 'required|datetime', 'statut' => 'required|integer',  'immatriculation' => 'max:45',
                   'pointcollecte_id' => 'numeric|nullable', 'vehicule_id' => 'numeric|nullable', 'transporteur_id' => 'numeric|nullable'
                   , 'motif_passage_vide' => 'max:4096'
        ];

        if (!$withRequired)
            $retour = ['date_debut' => 'datetime', 'date_fin' => 'datetime', 'statut' => 'numeric|min:0|max:2',  'immatriculation' => 'max:45',
                       'pointcollecte_id' => 'numeric|nullable', 'vehicule_id' => 'numeric|nullable', 'transporteur_id' => 'numeric|nullable'
                       , 'motif_passage_vide' => 'max:4096'
                      ];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function pointcollecte()
    {
        return $this->belongsTo(Pointcollecte::class);
    }
    public function collecte()
    {
        return $this->hasMany(Collecte::class);
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $passage = Passage::create($fillable);
            return $passage->id;
        });
    }
    public function insert(array &$data) {
        parent::insert($data);
    }
    static public function listPassagesWithOneDocumentByPointcollecte(array $pointcollecte_ids, string $start, string $end) {
        $query = DB::table('passages AS P')
                   ->join('pointcollectes    AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->leftJoin('collecte_document AS CD', 'P.id' , '=', 'CD.passage_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$start, $end])
                   ->groupBy('P.id');
                   ;

        $query = $query->select(['P.id', 'P.statut', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'CD.document_id']);
        return $query->get();
    }
    public function listByPointcollecte(int $pointcollecte_id) {
        return Passage::where('pointcollecte_id', $pointcollecte_id)->get();
    }
    public function listByClients(array $client_ids) {//il faudrait vérifier qui le demande et que les clients lui soient bien associés
        return Passage::join('pointcollectes', 'pointcollectes.id', '=', 'passages.pointcollecte_id')
                      ->join('clients'       , 'clients.id'       , '=', 'pointcollectes.client_id')
                      ->whereRaw('clients.id IN('.implode(',', $session['clients']).')')->select('passages.*')->get();
    }
    public function listByCollecteur(int $integrateur_id) {
        return Passage::join('pointcollectes', 'pointcollectes.id', '=', 'passages.pointcollecte_id')
                      ->join('clients'       , 'clients.id'       , '=', 'pointcollectes.client_id')
                      ->where('clients.integrateur_id', '=', $integrateur_id)->select('passages.*')->get();
    }
    public function deletePassage(int $passage_id) {
        return DB::transaction(function () use (&$passage_id) {
            $fs = new Filesystem;
            if (Collecte::where('passage_id', '=', $passage_id)->count() != 0) {
                \Log::warning('Tried to delete a passage that still has collecte! passage_id='.$passage_id.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            $documents = DB::table('collecte_document')->where('passage_id', '=', $passage_id)->select('document')->get();
            DB::table('collecte_document')->where('passage_id', '=', $passage_id)->delete();
            foreach($documents AS &$value) {
                if (!$fs->delete(Paths::clientDocuments($value->document)))
                    \Log::warning('Failed to delete a file! file='.Paths::clientDocuments($value->document).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            }
            return true;
        });
    }
    public static function insertWithCheck(string $event_id, int $transporteur_id, int $vehicule_id, string $date_debut, string $date_fin, int $pointcollecte_id, int $integrateur_id, string $passageNoteTpl) {
    $sql = <<<EOF
        INSERT INTO passages
        (event_id, pointcollecte_id, transporteur_id, vehicule_id, date_debut, date_fin)
        SELECT ?, p.id AS pointcollecte_id, ?, ?, ?, ?
        FROM clients c
        INNER JOIN pointcollectes p ON p.client_id = c.id
        WHERE c.integrateur_id = $integrateur_id AND p.id IN (?)
    EOF;
    $passage_id = DB::insert($sql, [$event_id, $transporteur_id, $vehicule_id, $date_debut, $date_fin, $pointcollecte_id]);

    if (!$passage_id) {
        \Log::warning('Tried to create a passage associated to a client that is note associated to the integrator; input='.json_encode($request->only($allowedFields)).' session:'.\json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
        return false;
    }
    else
        $passage_id = DB::getPdo()->lastInsertId();
        Passage::generatePassageNote($passage_id, $integrateur_id, $passageNoteTpl);
        return $passage_id;
    }
    private static function generatePassageNote(int $passage_id, int $integrateur_id, string $passageNoteTpl) {
        $passage = Passage::join('pointcollectes AS P', 'P.id', '=', 'passages.pointcollecte_id')
                          ->join('clients        AS C', 'C.id', '=', 'P.client_id')
                          ->join('entreprises    AS E', 'E.id', '=', 'C.entreprise_id')
                          ->where('passages.id', $passage_id)->where('C.integrateur_id', $integrateur_id)
                          ->select(['P.nom AS client_site_nom', 'P.adresse AS client_site_adresse'
                                   , 'passages.date_debut AS collecte_date'
                                   , 'E.siret AS client_SIRET'
                                   , DB::raw("IF(passages.statut = 2, 'X', '') AS collecte_statut_faite"), DB::raw("IF(passages.statut = 1, 'X', '') AS collecte_statut_non_faite")
                                   , DB::raw("IF(passages.statut = 1, passages.motif_passage_vide, '') AS collecte_non_faite_motif")
                                   , 'E.id AS entreprise_id', 'passages.id AS passage_id'
                                   ])
                          ->first();
        if ($passage == null) throw(new \Exception('Asked to generate a Passage\'s Note for an inexistant or not allowed passage; data:'.json_encode(['integrateur_id' => $integrateur_id, 'passage_id' => $passage_id])));
        $users = User::join('parametres'    , 'parametres.user_id'       , '=', 'users.id')
                     ->join('client_user'   , 'client_user.user_id'      , '=', 'users.id')
                     ->join('pointcollectes', 'pointcollectes.client_id' , '=', 'client_user.client_id')
                     ->join('passages'      , 'passages.pointcollecte_id', '=', 'pointcollectes.id')
                     ->where('parametres.notification_passage', '=', 1)
                     ->where('passages.id'                    , '=', $passage_id)
                     ->select('users.*')
                     ->get();
        $passageDate            = Carbon::createFromFormat('Y-m-d H:i:s', $passage->collecte_date);
        $passage->collecte_date = $passageDate->format('d/m/Y');
        $docs = collect([json_decode(json_encode([ 'id' => $integrateur_id, 'document' => $passageNoteTpl ]))]);

        // SI modif de Paths::clientDocuments($passage['entreprise_id']).'/'.$passage->passage_id.'-bordereau_passage.pdf' ALORS faire idem dans
        // - UserPassageController::downloadAttestation

        $result = Document::generate($passage, 0, $users, 0, Paths::integrateurDocuments($passageNoteTpl), Paths::clientDocuments($passage['entreprise_id']).'/'.$passage->passage_id.'-bordereau_passage.pdf');
        if ($result !== true)
            throw(new \Exception($result));
    }
    public function updateModel(Passage $passage, array &$fillable, int $integrateur_id, string $passageNoteTpl) {//il faudrait vérifier qui le demande et que le client lui soit bien associé
        return DB::transaction(function () use (&$fillable, &$passage, &$integrateur_id, &$passageNoteTpl) {
            /*
                Interdiction de modifier la date d'un passage ayant des collectes !!!!
                il faut supprimer les collectes associées et faire le décalage !!!!!

            */
            $previousStatut = $passage->statut;
            $passage->update($fillable);
            $sql = <<<'END'
            UPDATE IGNORE rsds R
            INNER JOIN collectes     C  ON C.id  = R.collecte_id
            INNER JOIN passages      P  ON P.id  = C.passage_id
            INNER JOIN transporteurs T  ON T.id  = P.transporteur_id
            INNER JOIN entreprises   Et ON Et.id = T.entreprise_id
            LEFT  JOIN vehicules     V  ON V.id  = P.vehicule_id
            SET date_debut = P.date_debut, date_dechargement = P.date_debut
            , transporteur_entree_nom = Et.raison_sociale, transporteur_entree_adresse = Et.adresse_administrative                                                                 -- transporteur
                                                        , transporteur_entree_immatriculation = IF (V.id IS NULL, P.immatriculation, V.immatriculation)
                                                        , transporteur_entree_recepisse = T.recepisse
            WHERE P.id = ?
END;
            DB::statement($sql, [$passage->id]);
            if (isset($fillable['statut'])) {
                if ($fillable['statut'] != $previousStatut || $fillable['statut'] == 1) {
                    $this->generatePassageNote($passage->id, $integrateur_id, $passageNoteTpl);
                }
            }
            return true;
        });
    }
    public function lookWithDates(Carbon $start, Carbon $end = null) {
        $query = Passage::with(['pointcollecte'])->where('date_debut', '>=', $start->format('Y-m-d H:i:s'));
        if (!empty($end))
            $query = $query->where('date_fin', '<=', $end->format('Y-m-d H:i:s'));
        return $query->get();
    }
    function documentGenerationWT(int $passage_id, int $integrateur_id) {
/*
SELECT motif_passage_vide AS collecte_non_faite_motif, date_debut AS collecte_date, E.siret AS client_SIRET
        , IF(statut=2, 'X', '') AS collecte_statut_faite, IF(statut=1, 'X', '') AS collecte_statut_non_faite
        , E.raison_sociale AS client_site_nom, PC.adresse AS client_site_adresse, C.integrateur_id
FROM passages P
INNER JOIN pointcollectes PC ON PC.id = P.pointcollecte_id
INNER JOIN clients        C  ON C.id  = PC.client_id
INNER JOIN entreprises    E  ON E.id  = C.entreprise_id
where P.id = 41051
;
*/
        $infos = Passage::join('pointcollectes AS PC', 'PC.id', '=', 'passages.pointcollecte_id')
                        ->join('clients        AS C' , 'C.id' , '=', 'PC.client_id')
                        ->join('entreprises    AS E' , 'E.id' , '=', 'C.entreprise_id')
                        ->where('passages.id', '=', $passage_id)
                        ->select(['motif_passage_vide AS collecte_non_faite_motif', 'date_debut AS collecte_date', 'E.siret AS client_SIRET'
                                , DB::raw("IF(statut=2, 'X', '') AS collecte_statut_faite"), DB::raw("IF(statut=1, 'X', '') AS collecte_statut_non_faite")
                                , 'E.raison_sociale AS client_site_nom', 'PC.adresse AS client_site_adresse', 'C.integrateur_id', 'C.id AS client_id', 'E.id AS entreprise_id'
                                , 'passages.id AS passage_id'
                         ])
                        ->first();
        $docs = Document::where('integrateur_id', '=', $integrateur_id)
                        ->where('generation', '=', 2) //c.f. doc modèle
                        ->get();
        if ($docs->count() == 0) return true;

        $users = User::join('parametres'       , 'parametres.user_id'       , '=', 'users.id')
                     ->join('client_user AS CU', function ($join) use(&$infos) {
                        $join->on('CU.user_id' , '=', 'users.id')
                             ->where('CU.client_id', '=', $infos->client_id);
                     })
                     ->select('users.*')
                     ->get();
        if ($infos == null) {
            $message = 'Nothing found for this passage! passage_id='.$passage_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            throw(new \Exception($message));
        }

        $passageDate          = Carbon::createFromFormat('Y-m-d H:i:s', $infos->collecte_date);
        $infos->collecte_date = $passageDate->format('d/m/Y');

        $result = Document::generate($infos, $docs, $users, 0);
        if ($result !== true)
            throw(new \Exception($result));
    }
    static public function listDateByPointcollecte(array $pointcollecte_ids, string $start, string $end) {
        $query = DB::table('passages      AS P')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$start, $end])
                   //->where('P.statut', '<>', 0) //tous types de passage (planifié, déjà réalisé, etc)
                   ->groupBy([DB::raw('DATE(P.date_debut)')])
                   ->select(['P.date_debut'])
                   ->orderBy('P.date_debut', 'DESC');
        return $query->get();
    }
    static public function listByEventIdWithCollecte(int $integrateur_id, string $event_id) {
        return Passage::listWithAllByEventIdAndDateRange($integrateur_id, '2001-01-01', '3000-01-01', '= \''.$event_id.'\'');
    }
    static public function listWithAllByEventIdAndDateRange(int $integrateur_id, string $start, string $end, string $eventFilter = 'IS NOT NULL') {
        $query = <<<EOF
            SELECT p.event_id, GROUP_CONCAT(
                JSON_OBJECT(
                    'id', p.id, 'event_id', p.event_id, 'pointcollecte_id', p.pointcollecte_id, 'date_debut', p.date_debut
                    , 'statut', p.statut, 'motif_passage_vide', p.motif_passage_vide, 'dangerousWastes', p.dangerousWastes
                    , 'vehicule_id', p.vehicule_id, 'transporteur_id', p.transporteur_id
                    , 'collectes', JSON_ARRAY()
                )
            ) AS passages
            FROM passages p
            INNER JOIN pointcollectes p2 ON p2.id = p.pointcollecte_id
            INNER JOIN clients        c2 ON c2.id = p2.client_id AND c2.integrateur_id = $integrateur_id
            WHERE p.date_debut BETWEEN date('$start') AND date('$end') AND p.event_id $eventFilter
            AND p.id NOT IN (
                SELECT DISTINCT pas.id
                FROM collectes c
                INNER JOIN passages pas ON pas.id = c.passage_id
                WHERE pas.date_debut BETWEEN date('$start') AND date('$end')
            )
            GROUP BY p.event_id, p.id
            ORDER BY date_debut
        EOF;
        $withoutCollecte = DB::select($query,[$start, $end]);
        $query = <<<EOF
            SELECT p.event_id, GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'id', p.id, 'event_id', p.event_id, 'pointcollecte_id', p.pointcollecte_id, 'date_debut', p.date_debut
                                                , 'statut', p.statut, 'motif_passage_vide', p.motif_passage_vide, 'dangerousWastes', p.dangerousWastes
                                                , 'vehicule_id', p.vehicule_id, 'transporteur_id', p.transporteur_id
                                                , 'collectes', JSON_EXTRACT(co.collectes, '$')
                                            )
                                        ) AS passages
            FROM passages p
            INNER JOIN pointcollectes p2 ON p2.id = p.pointcollecte_id
            INNER JOIN clients        c2 ON c2.id = p2.client_id AND c2.integrateur_id = $integrateur_id
            INNER JOIN (
                SELECT CONCAT('[',GROUP_CONCAT(JSON_OBJECT('id', c.id, 'numero_certificat', c.numero_certificat, 'trackdechets_id', c.trackdechets_id
                , 'poids', c.poids, 'statut', c.statut, 'qualite', c.qualite, 'passage_id', c.passage_id
                , 'worker_id', c.worker_id, 'enlevement_id', c.enlevement_id, 'dechet_id', c.dechet_id, 'exutoire_id', c.exutoire_id)), ']') AS collectes, passage_id
                FROM collectes      c
                GROUP BY passage_id
            ) co ON co.passage_id = p.id
                                    -- date('2023-05-01 00:00:00') AND date('2023-05-05 23:59:59')
            WHERE p.date_debut BETWEEN date(?) AND date(?) AND p.event_id $eventFilter
            GROUP BY p.event_id, p.id
            ORDER BY date_debut
        EOF;
        $withCollecte = DB::select($query,[$start, $end]);
        return array_reduce(array_merge($withoutCollecte, $withCollecte), function($acc, $value) {
            if (!isset($acc[$value->event_id]))
                $acc[$value->event_id] = [];
            array_push($acc[$value->event_id], \json_decode($value->passages));
            return $acc;
        }, []);
    }
}
