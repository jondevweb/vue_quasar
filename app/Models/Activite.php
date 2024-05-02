<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use App\Helpers\Files;

class Activite extends Model
{
    use HasFactory;
    const CHROME         = '/opt/google/chrome/chrome';
    const CHROME_PARAMS  = '--headless --print-to-pdf-no-header';
    const DEFAULT_TPL    = '/var/www/html/triethic.icionrecycle.fr/root/storage/app/public/activite_model/rapport.html';
    const RSC_DIR        = '/var/www/html/triethic.icionrecycle.fr/root/storage/app/public/activite_model/ressources';

    static public function generateOneRapport(int $integrateur_id, $pointcollecte_ids, $dechet_ids, Carbon $startDate = null, Carbon $endDate = null, string $templatefile = null, string $outputfile = null) {
        $CHROME         = self::CHROME.' ';
        $CHROME_PARAMS  = ' '.self::CHROME_PARAMS.' ';

        if ($templatefile == null)
            $templatefile = self::DEFAULT_TPL;
        if ($endDate      == null)
            $endDate      = date('Y-m-d') . ' 23:59:59';
        else
            $endDate   = $endDate->toDateTimeString();
        if ($startDate    == null)
            $startDate    = date('Y').'-01-01 23:59:59';
        else
            $startDate = $startDate->toDateTimeString();

        $pointcollecte_ids = ' AND Pc.id IN ('.implode(', ', $pointcollecte_ids).') ';

        if ($dechet_ids == null)
            $dechet_ids = '';
        else
            $dechet_ids = ' AND D.id IN ('.implode(', ', $dechet_ids).') ';


        $file = new Filesystem;
        $outputDir = $file->dirname(self::DEFAULT_TPL);

        $collectesSQL = <<<"EOF"
            SELECT C.dechet_id, DATE_FORMAT(P.date_debut, "%Y%m") AS month_number, COUNT(C.id) AS nb_collecte, D.nom, D.photo, SUM(C.poids) AS total, AVG(C.poids) AS moyenne, D.ordre_affichage, D.couleur
            FROM collectes C
            INNER JOIN passages       P   ON C.passage_id       = P.id
            INNER JOIN pointcollectes Pc  ON P.pointcollecte_id = Pc.id
            INNER JOIN clients        Cl  ON Pc.client_id       = Cl.id AND Cl.integrateur_id = $integrateur_id
            INNER JOIN dechets        D   ON C.dechet_id        = D.id
            LEFT JOIN dechet_exutoire DE  ON C.exutoire_id      = DE.exutoire_id  AND DE.dechet_id = D.id
            LEFT JOIN enlevements     E   ON C.enlevement_id    = E.id
            LEFT JOIN dechet_exutoire DE2 ON E.exutoire_id      = DE2.exutoire_id AND DE2.dechet_id = D.id
            WHERE P.date_debut BETWEEN '$startDate' AND '$endDate'
                  $pointcollecte_ids
                  $dechet_ids
            GROUP BY C.dechet_id, MONTH(P.date_debut);
        EOF;
        $dechetSQL = <<<"EOF"
            SELECT D.id AS dechet_id, D.photo, D.nom, COUNT(C.id) AS nb_collecte, SUM(C.poids) AS total,  AVG(C.poids) AS moyenne,  STDDEV(C.poids) AS ecart_type, D.couleur
                , SUM(IF(DE.coeff_env_arbre   IS NULL, DE2.coeff_env_arbre  , DE.coeff_env_arbre)   *C.poids) AS equ_bois
                , SUM(IF(DE.coeff_env_eau     IS NULL, DE2.coeff_env_eau    , DE.coeff_env_eau)     *C.poids) AS equ_eau
                , SUM(IF(DE.coeff_env_energie IS NULL, DE2.coeff_env_energie, DE.coeff_env_energie) *C.poids) AS equ_energie
                , SUM(IF(DE.coeff_env_co2     IS NULL, DE2.coeff_env_co2    , DE.coeff_env_co2)     *C.poids) AS equ_co2
                , SUM(IF(DE.taux_recyclage    IS NULL, DE2.taux_recyclage   , DE.taux_recyclage)    *C.poids)/SUM(C.poids) AS taux_recyclage
                , D.equivalence_coefficient, D.equivalence_nom, D.equivalence_photo
            FROM collectes C
            INNER JOIN passages       P   ON C.passage_id       = P.id
            INNER JOIN pointcollectes Pc  ON P.pointcollecte_id = Pc.id
            INNER JOIN dechets        D   ON C.dechet_id        = D.id
            LEFT JOIN dechet_exutoire DE  ON C.exutoire_id      = DE.exutoire_id  AND DE.dechet_id = D.id
            LEFT JOIN enlevements     E   ON C.enlevement_id    = E.id
            LEFT JOIN dechet_exutoire DE2 ON E.exutoire_id      = DE2.exutoire_id AND DE2.dechet_id = D.id
            WHERE P.date_debut BETWEEN '$startDate' AND '$endDate'
                  $pointcollecte_ids
                  $dechet_ids
            GROUP BY C.dechet_id
            ORDER BY total DESC
        EOF;
        $environnementSQL = <<<"EOF"
            SELECT COUNT(C.id) AS nb_collecte, SUM(C.poids) AS total,  AVG(C.poids) AS moyenne,  STDDEV(C.poids) AS ecart_type
            , SUM(IF(DE.coeff_env_arbre   IS NULL, DE2.coeff_env_arbre  , DE.coeff_env_arbre)   *C.poids) AS equ_bois
            , SUM(IF(DE.coeff_env_eau     IS NULL, DE2.coeff_env_eau    , DE.coeff_env_eau)     *C.poids) AS equ_eau
            , SUM(IF(DE.coeff_env_energie IS NULL, DE2.coeff_env_energie, DE.coeff_env_energie) *C.poids) AS equ_energie
            , SUM(IF(DE.coeff_env_co2     IS NULL, DE2.coeff_env_co2    , DE.coeff_env_co2)     *C.poids) AS equ_co2
            , SUM(IF(DE.taux_recyclage    IS NULL, DE2.taux_recyclage   , DE.taux_recyclage)    *C.poids)/SUM(C.poids) AS taux_recyclage
            FROM collectes C
            INNER JOIN passages       P   ON C.passage_id       = P.id
            INNER JOIN pointcollectes Pc  ON P.pointcollecte_id = Pc.id
            INNER JOIN dechets        D   ON C.dechet_id        = D.id
            LEFT JOIN dechet_exutoire DE  ON C.exutoire_id      = DE.exutoire_id  AND DE.dechet_id = D.id
            LEFT JOIN enlevements     E   ON C.enlevement_id    = E.id
            LEFT JOIN dechet_exutoire DE2 ON E.exutoire_id      = DE2.exutoire_id AND DE2.dechet_id = D.id
            WHERE P.date_debut BETWEEN '$startDate' AND '$endDate'
                $pointcollecte_ids
                $dechet_ids
        EOF;
        $pointcollecte_ids = ' WHERE '.str_replace(' AND', '', $pointcollecte_ids);
        $identiteSQL = <<<"EOF"
            SELECT Pc.nom, Pc.adresse, E2.raison_sociale AS integrateur_nom, E2.adresse_administrative AS integrateur_adresse
            FROM pointcollectes Pc
            INNER JOIN clients      C  ON Pc.client_id     = C.id
            INNER JOIN entreprises  E  ON C.entreprise_id  = E.id
            INNER JOIN integrateurs I  ON C.integrateur_id = I.id AND I.id = $integrateur_id
            INNER JOIN entreprises  E2 ON I.entreprise_id  = E2.id
            $pointcollecte_ids
        EOF;
        $pointcollecte_ids = str_replace('WHERE  Pc.id', 'AND P.pointcollecte_id', $pointcollecte_ids);
        $stats = <<<"EOF"
            SELECT COUNT(jour) AS nb_passage, AVG(nb_dechet) AS moy_type_dechet, AVG(poids_total) AS moy_poids, SUM(poids_total) AS  poids_total
            FROM (
                SELECT MONTH(P.date_debut)*100+DAY(P.date_debut) AS jour
                    , COUNT(C.id) as nb_dechet, SUM(C.poids) as poids_total
                    , GROUP_CONCAT(C.dechet_id)
                FROM collectes C
                INNER JOIN dechets        D   ON C.dechet_id        = D.id
                INNER JOIN passages       P   ON C.passage_id       = P.id
                WHERE C.statut >= 50 AND P.date_debut BETWEEN '$startDate' AND '$endDate'
                    $pointcollecte_ids
                    $dechet_ids
                GROUP BY jour
            ) ParPassage
        EOF;
        $result = [
            'identite'  => DB::select($identiteSQL),
            'dechet'    => DB::select($dechetSQL),
            'collectes' => DB::select($collectesSQL),
            'stats'     => DB::select($stats)[0],
            'environnement' => DB::select($environnementSQL),
            'integrateur_id' => $integrateur_id,
            'interval' => [
                'start' => Carbon::createFromFormat('Y-m-d H:i:s', $startDate)->format('d/m/Y'),
                'end'   => Carbon::createFromFormat('Y-m-d H:i:s', $endDate)->format('d/m/Y')
            ],
        ];
        if (count($result['identite']) == 0) {
            \Log::warning('The pointcollecte is not associated to the integrator! integrateur_id='.$integrateur_id.', pointcollecte_ids='.json_encode($pointcollecte_ids).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        $dir        = '/tmp/'.Files::uniqFilename('pdfs', '/tmp', true);
        $outputfile = '/tmp/'.Files::uniqFilename('pdf', '/tmp');
        file_put_contents($dir.'/data.js', 'var data='.json_encode($result));
        $file->link(self::DEFAULT_TPL, $dir.'/rapport.html');
        $file->link(self::RSC_DIR, $dir.'/ressources');

        $command = $CHROME.$CHROME_PARAMS.' --disable-gpu --disable-software-rasterizer --print-to-pdf='.$outputfile.' file://'.$dir.'/rapport.html'.' 2>&1 > /dev/stdout';
        $result = exec($command, $output, $retval);
        if ($result === false) {
            \Log::warning('Failed to generate the bilan! retval='.$retval.', command=>'.$command.'<; output=>'.$output.'< ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        $file->delete($dir.'/ressources');
        $file->delete($dir.'/rapport.html');
        $file->delete($dir.'/data.js');
        rmdir($dir);

        return $outputfile;
    }
}
