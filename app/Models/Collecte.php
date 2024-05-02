<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use App\Models\Passage;
use App\Models\Dechet;
use App\Models\Document;
use App\Models\Parametre;
use App\Models\Rsd;
use App\Helpers\Pdf;
use App\Helpers\Paths;
use App\Helpers\Files;
use Illuminate\Support\Carbon;

class Collecte extends Model
{
    use HasFactory;
    protected $fillable = [
        'numero_certificat',
        'trackdechets_id',
        'poids',
        'passage_id',
        'worker_id',
        'enlevement_id',
        'dechet_id',
        'exutoire_id',
        'statut',
        'id'
    ];
    public function passage() {
        return $this->belongsTo(Passage::class);
    }
    public function document() {
        return $this->belongsToMany(Document::class);
    }
    public function dechet() {
        return $this->belongsTo(Dechet::class);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['numero_certificat' => 'max:45', 'poids' => 'numeric|min:1', 'passage_id' => 'required|numeric', 'worker_id' => 'numeric|nullable'
                 , 'enlevement_id' => 'numeric', 'dechet_id' => 'required|numeric', 'exutoire_id' => 'numeric|nullable', 'statut' => 'numeric'
        ];

        if (!$withRequired)
            $retour = ['numero_certificat' => 'max:45', 'poids' => 'numeric|min:1', 'passage_id' => 'numeric|nullable', 'worker_id' => 'numeric|nullable'
                     , 'enlevement_id' => 'numeric', 'dechet_id' => 'numeric', 'exutoire_id' => 'numeric|nullable', 'statut' => 'numeric'
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
            $collecte = Collecte::create(['dechet_id' => $fillable['dechet_id'], 'passage_id' => $fillable['passage_id']]);
            $collecte->updateModelViaModel($fillable);
            return $collecte->id;
        });
    }
    public function listByPassage(int $passage_id) {
        return Collecte::join('passages', 'passages.id', '=', 'collectes.passage_id')
                      ->where('passages.id', '=', $passage_id)->select('collectes.*')->get();
    }
    static public function listWithWeightNWasteByPointcollecte(array $pointcollecte_ids, string $start, string $end, string $orderCriteria = 'P.date_debut', string $orderWay = 'desc', bool $strip = false) {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->join('dechets        AS D' , 'D.id' , '=', 'C.dechet_id');
        if (count($pointcollecte_ids) > 0) $query->whereIn('P.pointcollecte_id', $pointcollecte_ids);
        $query = $query->whereBetween('P.date_debut', [$start, $end])
                       ->where('C.statut', '>=', 21);
        if ($strip)
            $query = $query->select(['P.id', 'P.date_debut AS date'                 , 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id', 'trackdechets_id', 'C.id AS collecte_id', 'P.id AS passage_id']);
        else
            $query = $query->select(['P.id', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id', 'trackdechets_id', 'C.id AS collecte_id', 'P.id AS passage_id']);

            $query = $query->orderBy($orderCriteria, $orderWay);
        return $query->get();
    }
    static public function listWithWeightNWasteByPointcollecteI(int $integrateur_id, array $pointcollecte_ids, array $dechet_ids, string $start, string $end, string $orderCriteria = 'P.date_debut', string $orderWay = 'desc', bool $strip = false) {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->join('clients        AS Cl', 'Cl.id', '=', 'PC.client_id')
                   ->join('dechets        AS D' , 'D.id' , '=', 'C.dechet_id')
                   ->where('Cl.integrateur_id', $integrateur_id);
        $query = $query->whereBetween('P.date_debut', [$start, $end])
                       ->where('C.statut', '>=', 21);
        if (count($pointcollecte_ids) > 0)
            $query = $query->whereIn('PC.id', $pointcollecte_ids);
        if (count($dechet_ids) > 0)
            $query = $query->whereIn('D.id', $dechet_ids);
        if ($strip)
            $query = $query->select(['P.id', 'P.date_debut AS date'                 , 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id', 'trackdechets_id', 'C.id AS collecte_id', 'P.id AS passage_id']);
        else
            $query = $query->select(['P.id', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id', 'trackdechets_id', 'C.id AS collecte_id', 'P.id AS passage_id']);

            $query = $query->orderBy($orderCriteria, $orderWay);
        return $query->get();
    }
    static public function listWithWeightNDangerousWasteByPointcollecte(int $integrateur_id, string $start, string $end, string $orderCriteria = 'P.date_debut', string $orderWay = 'desc', bool $strip = false) {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->join('clients        AS Cl', 'Cl.id', '=', 'PC.client_id')
                   ->join('dechets        AS D' , 'D.id' , '=', 'C.dechet_id')
                   ->where(DB::raw('INSTR(D.rubrique, "*")'), '>', 0)
                   ->where('Cl.integrateur_id', $integrateur_id);
        $query = $query->whereBetween('P.date_debut', [$start, $end])
                       ->where('C.statut', '>=', 21);
        if ($strip)
            $query = $query->select(['P.id', 'P.date_debut AS date'                 , 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id']);
        else
            $query = $query->select(['P.id', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'C.poids', 'D.nom as dechets', 'C.dechet_id', 'P.pointcollecte_id']);

            $query = $query->orderBy($orderCriteria, $orderWay);
        return $query->get();
    }
    static public function listFutureByPointcollecte(array $pointcollecte_ids) {
        $query = DB::table('gcalendar     AS P')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->whereBetween('P.date', [DB::raw('NOW()'), DB::raw("CONCAT(DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 YEAR), '%Y'), '-12-31')")]);
        if (count($pointcollecte_ids) > 0) $query->whereIn('P.pointcollecte_id', $pointcollecte_ids);
        $query = $query->orderBy('P.date', 'asc')
                       ->select([DB::raw("Date(P.date) AS 'Date'"), 'PC.nom', 'PC.adresse']);
        return $query->get();
    }
    static public function listWeightedWithWasteByPointcollecte(array $pointcollecte_ids, string $start, string $end, array $dechet_ids = []) {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->join('dechets        AS D' , 'D.id' , '=', 'C.dechet_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$start, $end])
                   ->where('C.statut', '>=', 50);

        if (count($dechet_ids)    > 0) $query = $query->whereIn('D.id', $dechet_ids);

        $query = $query->select(['C.id', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'D.nom as dechets']);
        return $query->get();
    }
    static public function listWeightedWithWasteByPointcollecteNDocument(array $pointcollecte_ids, string $start, string $end, array $dechet_ids = [], array $typeDocuments = []) {
        $query = DB::table('collectes AS C')
                   ->join('passages          AS P'  , 'P.id'           , '=', 'C.passage_id')
                   ->join('pointcollectes    AS PC' , 'PC.id'          , '=', 'P.pointcollecte_id')
                   ->join('dechets           AS D'  , 'D.id'           , '=', 'C.dechet_id')
                   ->join('collecte_document AS CD' , 'CD.collecte_id' , '=', 'C.id')
                   ->join('documents         AS DO' , 'DO.id'          , '=', 'CD.document_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereIn('DO.type', $typeDocuments)
                   ->whereBetween('P.date_debut', [$start, $end])
                   ->where('C.statut', '>=', 50);

        if (count($dechet_ids)    > 0) $query = $query->whereIn('D.id', $dechet_ids);

        $query = $query->select(['C.id', DB::raw("DATE(P.date_debut) AS 'date'"), 'PC.nom', 'D.nom as dechets']);
        return $query->get();
    }
    static public function listAssociatedDocs(int $pointcollecte_id) {
        return DB::table('collecte_document')
                 ->join('documents', 'documents.id', '=', 'collecte_document.document_id')
                 ->select(['collecte_document.*', 'documents.nom'])
                 ->where('collecte_document.collecte_id', '=', $pointcollecte_id)
                 ->get();
    }
    static public function listWithWeightByPointcollecteNMonth(array $pointcollecte_ids, string $start, string $end, string $orderCriteria = 'P.date_debut') {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$start, $end])
                   ->where('C.statut', '>=', 50)
                   ->groupBy(['year', 'month', 'C.dechet_id'])
                   ->select([DB::raw("SUM(C.poids) AS 'poids'"), DB::raw('YEAR(P.date_debut) AS year'), DB::raw('MONTH(P.date_debut) as month'), 'C.dechet_id'])
                   ->orderBy('year', 'ASC')->orderBy('month', 'ASC');
        return $query->get();
    }
    static public function listDateByPointcollecte(array $pointcollecte_ids, string $start, string $end) {
        $query = DB::table('collectes AS C')
                   ->join('passages       AS P' , 'P.id' , '=', 'C.passage_id')
                   ->join('pointcollectes AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                   ->whereIn('P.pointcollecte_id', $pointcollecte_ids)
                   ->whereBetween('P.date_debut', [$start, $end])
                   ->groupBy([DB::raw('DATE(P.date_debut)')])
                   ->select(['P.date_debut'])
                   ->orderBy('P.date_debut', 'DESC');
        return $query->get();
    }
    static public function listByPointcollecteNPassage(array $pointcollecte_ids, string $start, string $end, string $orderCriteria = 'P.date_debut', string $orderWay = 'desc') {
        $query = DB::table('passages       AS P')
                  ->join('pointcollectes   AS PC', 'PC.id', '=', 'P.pointcollecte_id')
                  ->leftJoin('collectes    AS C' , 'P.id' , '=', 'C.passage_id')
                  ->leftJoin('dechets      AS D' , 'D.id' , '=', 'C.dechet_id');
        if (count($pointcollecte_ids) > 0) $query->whereIn('P.pointcollecte_id', $pointcollecte_ids);

        $query = $query->whereBetween('P.date_debut', [$start, $end])
                       ->groupBy('P.id')
                       ->select(['P.id', 'P.date_debut AS date', 'PC.nom', 'P.statut', DB::raw('SUM(poids) as poids'), DB::raw("GROUP_CONCAT(DISTINCT CONCAT(D.nom, '#', C.statut) SEPARATOR ', ') as dechets")])
                       ->orderBy($orderCriteria, $orderWay);
        return $query->get();
    }
    static public function listWithAllByPointcollecteAndDateRange(int $integrateur_id, string $start, string $end) {
        $query = <<<EOF
            SELECT p.event_id, GROUP_CONCAT(JSON_MERGE(
                                                        JSON_OBJECT(
                                                            'id', p.id, 'event_id', p.event_id, 'pointcollecte_id', p.pointcollecte_id, 'date_debut', p.date_debut
                                                            , 'statut', p.statut, 'motif_passage_vide', p.motif_passage_vide, 'dangerousWastes', p.dangerousWastes
                                                            , 'vehicule_id', p.vehicule_id, 'transporteur_id', p.transporteur_id
                                                        )
                                                        , CONCAT('{"collectes": ',  co.collectes, '}')
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
            WHERE p.date_debut BETWEEN date(?) AND date(?) AND p.event_id IS NOT NULL
            GROUP BY p.event_id, p.id
            ORDER BY date_debut
        EOF;
        return DB::select($query,[$start, $end]);
    }
    public static function updateModel(int $collecte_id, array &$fillable) {//il faudrait vérifier qui le demande et que le client lui soit bien associé
        $collecte = Collecte::find($collecte_id);
        if ($collecte == null) return false;
        return $collecte->updateModelViaModel($fillable);
    }
    public static function massStore(array &$collectes) {
        return DB::transaction(function () use (&$collectes) {//à garder synchro avec updateModelViaModel
            foreach($collectes AS &$value) {
                $value['statut'] = 50;
                unset($value['id']);
            }
            //DB::unprepared('LOCK TABLES collectes WRITE');
            $retour = DB::table('collectes')->orderBy('id', 'desc')->limit(1)->select('id')->first();
            if ($retour == null) $retour = 0;
            else $retour = $retour->id;

            Collecte::insert($collectes);
            $last_insert_collecte_id = DB::getPdo()->lastInsertId();
            $retour = Collecte::where('id', '>', $retour)->get();
            //DB::unprepared('COMMIT');
            //DB::unprepared('UNLOCK TABLES');
            foreach($retour AS &$value) {
                // Mise à jour du Registre de suivi de déchet
                $value->rsdsUpdateWT();
                // Génération des documents associés à la pesée
                $value->documentGenerationWT();
            }
            return $last_insert_collecte_id;
        });
    }
    public function deleteCollectes(array &$collecte_id, int $passageRestrict = -1) {
        return DB::transaction(function () use (&$collecte_id, &$passageRestrict) {
            $nb = 0;
            $collectes = Collecte::join('collecte_document', 'collecte_document.collecte_id', '=', 'collectes.id')
                                 ->whereIn('collectes.id', $collecte_id);
            if ($passageRestrict != -1) {
                $nb = Collecte::whereIn('id', $collecte_id)->where('collectes.passage_id', '=', $passageRestrict)->delete();
                if ($nb != 1)
                    throw new Exception('Tried to delete a collecte associated to a passage but, it is nowhere to be found; '.json_encode(['passage'=>$passageRestrict, 'collecte_id' => $collecte_id], true));
                $collectes = $collectes->where('collectes.passage_id', '=', $passageRestrict);
            }
            else
                Collecte::whereIn('id', $collecte_id)->delete();

            Rsd::whereIn('collecte_id', $collecte_id)->delete();

            $fs = new Filesystem;
            $collectes = $collectes->select(['collecte_document.document'])->get();
            foreach($collectes AS &$value) {
                if (!$fs->delete(Paths::clientDocuments($value->document)))
                    \Log::warning('Failed to delete a file! file='.Paths::clientDocuments($value->document).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            }
        });
    }
    function rsdsUpdateWT() {
        /*
                        IL faut tester pls scénariis :
                        - enlèvement directe à la filière de recyclage
                        - regroupement, puis enlèvement
        */
        $sql = <<<'END'
INSERT INTO rsds
(collecte_id, date_collecte, date_dechargement, dechet, code_dechet, unite, quantite
 , transporteur_entree_nom, transporteur_entree_adresse, transporteur_entree_immatriculation, transporteur_entree_recepisse                -- transporteur
 -- -----------------------------DEBUT--
 -- si pas directe en déchetterie
 -- sinon infos de la déchetterie
 -- ------------------------------------
 , destination_regroupement_nom, destination_regroupement_adresse, destination_regroupement_code_traitement                                -- destination regroupement
 -- -----------------------------FIN----
 -- -----------------------------DEBUT--
 -- si directe en déchetterie
 -- sinon simple recopie de l'existant
 -- ------------------------------------
 , destination_finale_nom, destination_finale_adresse, destination_finale_traitement                                                       -- destination finale
 , transporteur_sortie_nom, transporteur_sortie_adresse, transporteur_sortie_immatriculation, transporteur_sortie_recepisse                -- transporteur sortie il s'agit du même
                                                                                                                                              -- équipage qu'à l'aller si directe
 -- -----------------------------FIN----
 )
 /*
  Un véhicule effectue un passage, et collecte différents déchets
  SI ce véhicule est celui de l'intégrateur (ou I) ALORS :
      - l'I est marqué comme le transporteur
      - les informations de l'équipage sont sauvés
  SINON c'est le véhicule de quelqu'un d'autre ALORS les infos sont enregistrées en conséquence (et donc pas de vehicule_id)

  Un passage est composé de différentes collectes.
  SI les collectes sont apportées directement en exutoire ALORS exutoire_id est rempli et la plaque d'immatriculation utilisée est celle du passage
  SINON les collectes sont apportées chez l'I, et il y a un enlèvement

  REM : quand il n'y a pas de exutoire_id dans collectes alors la collecte n'a pas été apportée directement à la filière de recyclage
 */
 SELECT C.id, P.date_debut, P.date_debut, D.nom, D.rubrique, 'kg', C.poids
        , Et.raison_sociale, Et.adresse_administrative, IF (V.id IS NULL, P.immatriculation, V.immatriculation), T.recepisse                                           -- transporteur
        , IF(C.exutoire_id IS NULL, Ie.raison_sociale, Ex.raison_sociale), IF(C.exutoire_id IS NULL, Ie.adresse_administrative, Ex.adresse_administrative)             -- destination regroupement
                                                                         , IF(C.exutoire_id IS NULL, 'R13', D.code_traitement)
        , IF(C.exutoire_id IS NULL, '', Ex.raison_sociale), IF(C.exutoire_id IS NULL, '', Ex.adresse_administrative)                                                   -- destination finale
                                                          , 'Recyclage' -- IF(C.exutoire_id IS NULL, '', D.code_traitement)
                                                                                                                                                                          -- si il y a enlèvement alors
                                                                                                                                                                          -- les données correctes seront placées
                                                                                                                                                                          -- lors de l'enlèvement
        , IF(C.exutoire_id IS NULL, '', Et.raison_sociale), IF(C.exutoire_id IS NULL, '', Et.adresse_administrative)                                                   -- transporteur sortie
                                                          , IF(C.exutoire_id IS NULL, '', IF (V.id IS NULL, P.immatriculation, V.immatriculation))                        -- si il y a enlèvement alors
                                                          , IF(C.exutoire_id IS NULL, '', T.recepisse)                                                                    -- les données correctes seront placées
                                                                                                                                                                          -- lors de l'enlèvement
 FROM       collectes       C
 INNER JOIN passages        P   ON P.id           = C.passage_id
 INNER JOIN transporteurs   T   ON T.id           = P.transporteur_id
 LEFT  JOIN vehicules       V   ON V.id           = P.vehicule_id
 INNER JOIN entreprises     Et  ON Et.id          = T.entreprise_id
 INNER JOIN pointcollectes  PC  ON PC.id          = P.pointcollecte_id
 INNER JOIN clients         CL  ON CL.id          = PC.client_id
 INNER JOIN integrateurs    I   ON I.id           = CL.integrateur_id
 INNER JOIN entreprises     Ie  ON Ie.id          = I.entreprise_id
 INNER JOIN dechets         D   ON D.id           = C.dechet_id
 LEFT JOIN  exutoires       EX  ON EX.id          = C.exutoire_id
 LEFT JOIN  entreprises     Ex  ON Ex.id          = EX.entreprise_id
 LEFT JOIN  dechet_exutoire DE  ON DE.exutoire_id = EX.id AND DE.dechet_id = C.dechet_id
 LEFT JOIN  enlevements     EN  ON EN.id          = C.enlevement_id     -- Si enlèvement effectué et non pas dépôt directe
 LEFT JOIN  transporteurs   Te  ON Te.id          = EN.transporteur_id
 LEFT JOIN  entreprises     Tee ON Tee.id         = Te.entreprise_id
 WHERE C.id = ?
 -- tous les points gérés par la requête d'enlèvement (donc les champs en simple guillemets) ne doivent pas être modifiés
 ON DUPLICATE KEY UPDATE
  date_collecte = P.date_debut, date_dechargement = P.date_debut, dechet = D.nom, code_dechet = D.rubrique, unite = 'kg', quantite = C.poids
, transporteur_entree_nom = Et.raison_sociale, transporteur_entree_adresse = Et.adresse_administrative                                                                 -- transporteur
                                             , transporteur_entree_immatriculation = IF (V.id IS NULL, P.immatriculation, V.immatriculation), transporteur_entree_recepisse = T.recepisse
, destination_regroupement_nom = IF(C.exutoire_id IS NULL, Ie.raison_sociale, Ex.raison_sociale)                                                                       -- destination regroupement
                              , destination_regroupement_adresse         = IF(C.exutoire_id IS NULL, Ie.adresse_administrative, Ex.adresse_administrative)
                              , destination_regroupement_code_traitement = IF(C.exutoire_id IS NULL, 'R13', D.code_traitement)
, destination_finale_nom = IF(C.exutoire_id IS NULL, destination_finale_nom, Ex.raison_sociale)                                                                        -- destination finale
                              , destination_finale_adresse    = IF(C.exutoire_id IS NULL, destination_finale_adresse, Ex.adresse_administrative)
                              , destination_finale_traitement = 'Recyclage' -- IF(C.exutoire_id IS NULL, destination_finale_traitement, D.code_traitement)
, transporteur_sortie_nom = IF(C.exutoire_id IS NULL, transporteur_sortie_nom, Et.raison_sociale)                                                                      -- transporteur
                              , transporteur_sortie_adresse         = IF(C.exutoire_id IS NULL, transporteur_sortie_adresse, Et.adresse_administrative)
                              , transporteur_sortie_immatriculation = IF(C.exutoire_id IS NULL, transporteur_sortie_immatriculation, IF (V.id IS NULL, P.immatriculation, V.immatriculation))
                              , transporteur_sortie_recepisse       = IF(C.exutoire_id IS NULL, transporteur_sortie_recepisse, T.recepisse)
 ;
END;
        DB::statement($sql, [$this->id]);
    }
    function documentGenerationWT() {
        $collecte_id = $this->id;
        $docs = Document::where('dechet_id', '=', $this->dechet_id)
                        ->where('generation', '=', 3) //c.f. doc modèle ; cela indique génération au moment de la pesée
                        ->get();
        if ($docs->count() == 0) return true;

        $users = User::join('parametres'    , 'parametres.user_id'       , '=', 'users.id')
                     ->join('client_user'   , 'client_user.user_id'      , '=', 'users.id')
                     ->join('pointcollectes', 'pointcollectes.client_id' , '=', 'client_user.client_id')
                     ->join('passages'      , 'passages.pointcollecte_id', '=', 'pointcollectes.id')
                     ->join('collectes'     , 'collectes.passage_id'     , '=', 'passages.id')
                     ->where('parametres.notification_enlevement', '=', 1)
                     ->where('collectes.id', '=', $collecte_id)
                     ->select('users.*')
                     ->get();
        $infos = DB::table('collectes')
                   ->join('passages'      , 'passages.id'      , '=', 'collectes.passage_id')
                   ->join('dechets'       , 'dechets.id'       , '=', 'collectes.dechet_id')
                   ->join('pointcollectes', 'pointcollectes.id', '=', 'passages.pointcollecte_id')
                   ->join('clients'       , 'clients.id'       , '=', 'pointcollectes.client_id')
                   ->join('entreprises'   , 'entreprises.id'   , '=', 'clients.entreprise_id')
                   ->leftJoin('workers'   , 'workers.id'       , '=', 'collectes.worker_id')
                   ->leftJoin('users'     , 'users.id'         , '=', 'workers.user_id')
                   ->select([
                      'entreprises.id AS entreprise_id',
                      'clients.id     AS client_id',
                      'collectes.id   AS collecte_id',
                      'clients.integrateur_id',
                      'dechets.nom            AS collecte_dechet_nom',
                      'collectes.poids        AS collecte_poids',
                      'passages.date_debut    AS collecte_date',
                      DB::raw("CONCAT(users.prenom, ' ', users.nom) AS collecte_operateur"),
                      'pointcollectes.nom     AS client_raison_sociale',
                      'pointcollectes.adresse AS client_adresse',
                      'entreprises.SIRET      AS client_SIRET'
                  ])
                  ->where('collectes.id', '=', $collecte_id)
                  ->first();

        if ($infos == null) {
            $message = 'Nothing found for this collecte! collecte_id='.$collecte_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            throw(new \Exception($message));
        }
        if (isset($infos->prenom) && isset($infos->nom))
        $infos->operateur = $infos->prenom .' '.$infos->nom;

        unset($infos->prenom);
        unset($infos->nom);

        $passageDate          = Carbon::createFromFormat('Y-m-d H:i:s', $infos->collecte_date);
        $infos->collecte_date = $passageDate->format('d/m/Y');

        $result = Document::generateSingleFullAuto($infos, $docs, $users, 0);
        if ($result !== true)
            throw(new \Exception($result));
    }
    function updateModelViaModel(array &$fillable) {
        return DB::transaction(function () use (&$fillable) {//à garder synchro avec massStore
            /*$generated  = $this->poids > 0;
            $toGenerate = false;
            if (isset($fillable['poids']))
                $toGenerate = $this->poids != $fillable['poids'] || !$generated;

            if ($toGenerate) $fillable['statut'] = 50;*/
            $this->update($fillable);
            if ($this->save() == false) return false;

            // Mise à jour du Registre de suivi de déchet
            $this->rsdsUpdateWT();

            //if (!$toGenerate) return true;
            // Génération des documents associés à la pesée
            $this->documentGenerationWT();
            return true;
        });
    }
    public function regenerate(int $collecte_id, int $integrateur_id) {
        $infos = DB::table('collectes AS C')
                    ->join('passages       AS P'  ,'C.passage_id'      , '=', 'P.id')                //Passage
                    ->join('pointcollectes AS PC' ,'P.pointcollecte_id', '=', 'PC.id')               //Pointcollecte
                    ->join('clients        AS CL' ,'CL.id'             , '=', 'PC.client_id')        //Client
                    ->join('entreprises    AS CL_E' ,'CL_E.id'         , '=', 'CL.entreprise_id')
                    ->join('users          AS U'  ,'U.id'              , '=', 'CL.contact_juridique')
                    ->join('dechets        AS D'  ,'D.id'              , '=', 'C.dechet_id')         //Dechet
                    ->leftJoin('caps       AS Ca' ,'Ca.dechet_id'      , '=', 'D.id')
                    ->leftJoin('cap_client AS CC' , function ($join) {
                        $join->on('CC.cap_id'      , '=', 'Ca.id')
                            ->where('CC.client_id', '=', 'CL.id');
                    })
                    ->leftJoin('workers    AS W'  ,'W.id'              , '=', 'C.worker_id')         //Collecteur
                    ->leftJoin('users      AS U2' ,'U2.id'             , '=', 'W.user_id')
                    ->leftJoin('enlevements AS E' ,'E.id'              , '=', 'C.enlevement_id')
                    ->leftJoin('exutoires   AS Ex','Ex.id'             , '=', 'E.exutoire_id')       //Exutoire
                    ->leftJoin('entreprises AS En' ,'En.id'            , '=', 'Ex.entreprise_id')
                    ->join('transporteurs  AS T'  ,'T.id'              , '=', 'P.transporteur_id')   //Transporteur depuis le client
                    ->join('entreprises    AS En2','En2.id'            , '=', 'T.entreprise_id')
                    ->join('integrateurs   AS I'  ,'I.id'              , '=', 'CL.integrateur_id')   //Integrateur
                    ->join('entreprises    AS En3','En3.id'            , '=', 'I.entreprise_id')
                    ->join('users          AS U3' ,'U3.id'             , '=', 'I.signataire')
                    ->whereIn('C.id', [$collecte_id])
                    ->where('CL.integrateur_id', $integrateur_id)
                    ->select([
                            'C.dechet_id  AS dechet_id',
                            'CL_E.id      AS entreprise_id',
                            'CL.id        AS client_id',
                            'C.id         AS collecte_id',
                            'CL.email     AS client_site_courriel', //Il s'agit du courriel de l'entreprise et non du site
                            'CL.telephone AS client_site_telephone',//Il s'agit du telephone de l'entreprise et non du site
                            'PC.adresse   AS client_site_adresse',
                            'PC.nom       AS client_site_nom',
                            'CL_E.SIRET                                    AS client_SIRET',
                            'CL_E.adresse_administrative                   AS client_adresse',
                            DB::raw("DATE_FORMAT(CL.contrat, '%d/%m/%Y')   AS client_debut_contrat_date"),
                            'CL_E.raison_sociale                           AS client_raison_sociale',
                            'U.nom                                AS client_contact_juridique_nom',
                            'U.prenom                             AS client_contact_juridique_prenom',
                            'U.civilite                           AS client_contact_juridique_civilite',
                            DB::raw("CONCAT(U.prenom, ' ', U.nom) AS client_contact_juridique_nom_complet"),
                            'D.nom                                          AS collecte_dechet_nom',
                            'D.rubrique                                     AS collecte_dechet_code', //  (rubrique)
                            'D.code_traitement                              AS collecte_dechet_traitement',
                            DB::raw("IF(CC.numero IS NULL, '', CC.numero)   AS collecte_dechet_cap_numero"),
                            'C.numero_certificat                                    AS collecte_numero_certificat',
                            DB::raw("DATE_FORMAT(P.date_debut, '%d/%m/%Y')          AS collecte_date"),
                            'C.poids                                                AS collecte_poids',
                            DB::raw('C.poids/1000                                   AS collecte_poids_en_tonne'),
                            DB::raw('0                                              AS collecte_statut_non_faite'), //uniquement pour mémoire des
                            DB::raw('1                                              AS collecte_statut_faite'),     //données souvent récupérées ; ici inutile
                            DB::raw("CONCAT(U2.prenom, ' ', U2.nom) AS collecte_operateur"),
                            'En3.SIRET                  AS collecte_destination_SIRET',                                  //Destination (integrateur)
                            'En3.raison_sociale         AS collecte_destination_raison_sociale',
                            'En3.adresse_administrative AS collecte_destination_adresse',
                            'I.telephone                AS collecte_destination_telephone',
                            'I.email                    AS collecte_destination_courriel',
                            DB::raw("CONCAT(U3.prenom, ' ', U3.nom) AS collecte_destination_contact_nom_complet"),
                            'En.SIRET                           AS collecte_destination_ulterieure_SIRET',               //Exutoire
                            'En.raison_sociale                  AS collecte_destination_ulterieure_raison_sociale',
                            'En.adresse_administrative          AS collecte_destination_ulterieure_adresse',
                            'Ex.telephone                       AS collecte_destination_ulterieure_telephone',
                            'Ex.email                           AS collecte_destination_ulterieure_courriel',
                            'Ex.contact                         AS collecte_destination_ulterieure_contact_nom_complet',
                            'En2.SIRET                  AS collecte_transporteur_SIRET',                                 //Transporteur
                            'En2.raison_sociale         AS collecte_transporteur_raison_sociale',
                            'En2.adresse_administrative AS collecte_transporteur_adresse',
                            'T.departement              AS collecte_transporteur_adresse_departement',
                            'T.telephone                AS collecte_transporteur_telephone',
                            'T.email                    AS collecte_transporteur_courriel',
                            'T.contact                  AS collecte_transporteur_contact_nom_complet',
                            'T.recepisse                AS collecte_transporteur_recepisse_numero',
                            DB::raw("DATE_FORMAT(T.limite_recepisse, '%d/%m/%Y')   AS collecte_transporteur_recepisse_validite"),
                            DB::raw("CONCAT(U3.prenom, ' ', U3.nom) AS collecte_integrateur_signataire_nom_complet"),
                            DB::raw("DATE_FORMAT(NOW(), '%d/%m/%Y') AS document_generation_date"),
                        ])
                        ->get();
        $docs = DB::table('documents        AS D')
                  ->join('collecte_document AS CD', 'D.id', '=', 'CD.document_id')
                  ->where('CD.collecte_id', $collecte_id)
                  ->select('D.*')
                  ->get();
        if ($infos->count() == 0) {
            $message = 'Nothing found for those collectes! collecte_ids='.$collecte_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            throw(new \Exception($message));
        }
        if ($docs->count() == 0) {
            $message = 'No docs to generate for those collectes! collecte_ids='.$collecte_id.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            return;
        }
        $users = new Collection();
        $result = Document::generates($infos, $docs, $users, 0);
        return $result;
    }
}
