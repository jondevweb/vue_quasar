<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use App\Models\Historique;
use App\Models\Historiquetype;
use App\Models\Passage;
use App\Helpers\Paths;
use App\Helpers\Files;
use App\Jobs\GenerateOneBilanJob;
use App\Mail\BilansGenerated;

class Bilan extends Model
{
    use HasFactory;
    protected $fillable = [
        'document',
        'annee',
        'numero',
        'pointcollecte_id',
    ];
    static public $CHROME        = '/opt/google/chrome/chrome ';
    static public $CHROME_PARAMS = ' --headless --print-to-pdf-no-header ';
    static public $PROGRESS_FILE = '/tmp/bilan-generation-progress';
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['annee' => 'required|integer', 'document' => 'required|max:255'
                 , 'numero' => 'required|max:255', 'pointcollecte_id' => 'required|integer'];

        if (!$withRequired)
            $retour = ['annee'  => 'integer', 'document' => 'max:255'
                     , 'numero' => 'max:255', 'pointcollecte_id' => 'integer'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public static function generate(int $integrateur_id, int $pointcollecte_id, string $templatefile, string $outputfile, int $year = null) {
        if ($year == '0')
            $year = date('Y') - 1;
        $collectesSQL = <<<"EOF"
            SELECT MONTH(P.date_debut) AS month_number, COUNT(C.id) AS nb_collecte, D.nom, D.photo, SUM(C.poids) AS total, AVG(C.poids) AS moyenne, D.ordre_affichage, D.couleur
            FROM collectes C
            INNER JOIN passages       P   ON C.passage_id       = P.id
            INNER JOIN pointcollectes Pc  ON P.pointcollecte_id = Pc.id
            INNER JOIN dechets        D   ON C.dechet_id        = D.id
            LEFT JOIN dechet_exutoire DE  ON C.exutoire_id      = DE.exutoire_id  AND DE.dechet_id = D.id
            LEFT JOIN enlevements     E   ON C.enlevement_id    = E.id
            LEFT JOIN dechet_exutoire DE2 ON E.exutoire_id      = DE2.exutoire_id AND DE2.dechet_id = D.id
            WHERE YEAR(P.date_debut) = $year
                AND Pc.id = $pointcollecte_id
            GROUP BY C.dechet_id, MONTH(P.date_debut);
        EOF;
        $dechetSQL = <<<"EOF"
            SELECT D.nom, COUNT(C.id) AS nb_collecte, SUM(C.poids) AS total,  AVG(C.poids) AS moyenne,  STDDEV(C.poids) AS ecart_type, D.couleur
                , SUM(IF(DE.coeff_env_arbre   IS NULL, DE2.coeff_env_arbre  , DE.coeff_env_arbre)   *C.poids) AS equ_bois
                , SUM(IF(DE.coeff_env_eau     IS NULL, DE2.coeff_env_eau    , DE.coeff_env_eau)     *C.poids) AS equ_eau
                , SUM(IF(DE.coeff_env_energie IS NULL, DE2.coeff_env_energie, DE.coeff_env_energie) *C.poids) AS equ_energie
                , SUM(IF(DE.coeff_env_co2     IS NULL, DE2.coeff_env_co2    , DE.coeff_env_co2)     *C.poids) AS equ_co2
                ,     IF(DE.taux_recyclage    IS NULL, DE2.taux_recyclage   , DE.taux_recyclage)              AS taux_recyclage
                , D.equivalence_coefficient, D.equivalence_nom, D.equivalence_photo
            FROM collectes C
            INNER JOIN passages       P   ON C.passage_id       = P.id
            INNER JOIN pointcollectes Pc  ON P.pointcollecte_id = Pc.id
            INNER JOIN dechets        D   ON C.dechet_id        = D.id
            LEFT JOIN dechet_exutoire DE  ON C.exutoire_id      = DE.exutoire_id  AND DE.dechet_id = D.id
            LEFT JOIN enlevements     E   ON C.enlevement_id    = E.id
            LEFT JOIN dechet_exutoire DE2 ON E.exutoire_id      = DE2.exutoire_id AND DE2.dechet_id = D.id
            WHERE YEAR(P.date_debut) = $year
                AND Pc.id = $pointcollecte_id
            GROUP BY C.dechet_id
            ORDER BY total DESC;
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
            WHERE YEAR(P.date_debut) = $year
                AND Pc.id = $pointcollecte_id;
        EOF;
        $identiteSQL = <<<"EOF"
            SELECT Pc.nom, Pc.adresse, $year AS annee, E2.raison_sociale AS integrateur_nom, E2.adresse_administrative AS integrateur_adresse
            FROM pointcollectes Pc
            INNER JOIN clients      C  ON Pc.client_id     = C.id
            INNER JOIN entreprises  E  ON C.entreprise_id  = E.id
            INNER JOIN integrateurs I  ON C.integrateur_id = I.id
            INNER JOIN entreprises  E2 ON I.entreprise_id  = E2.id
            WHERE Pc.id = $pointcollecte_id;
        EOF;
        $file = new Filesystem;
        if (!$file->exists($templatefile)) {
            \Log::warning('generate bilan; Given file doesn\'t exist; file=>'.$templatefile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $templatefile = realpath($templatefile);
        $templateDir  = $file->dirname($templatefile);
        if (!realpath($file->dirname($outputfile))) {
            \Log::warning('generate bilan; Given parent output directory doesn\'t exist; file=>'.$outputfile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $result = [
            'identite'  => DB::select($identiteSQL),
            'dechet'    => DB::select($dechetSQL),
            'collectes' => DB::select($collectesSQL),
            'environnement' => DB::select($environnementSQL),
        ];
        file_put_contents($templateDir.'/data.js', 'var data='.json_encode($result));//
        $command = Bilan::$CHROME.Bilan::$CHROME_PARAMS.' --disable-gpu --disable-software-rasterizer --print-to-pdf='.$outputfile.' file://'.$templatefile /*.' 2>&1 > /dev/stdout'*/;
        $result = exec($command, $output, $retval);
        if ($result === false) {
            \Log::warning('generate bilan; Failed to generate the bilan!; file=>'.\json_encode(['retval'=> $retval, 'command' => $command, 'output' => $output]), \App\Helpers\Context::getContext());
            return false;
        }
        return true;
    }
    public static function generateMultiple(int $integrateur_id, array $pointcollecte_ids, string $templatefile, string $outputdir, int $year = null, $progressFile = false) {
        if ($year == '0')
            $year = date('Y') - 1;
        $file = new Filesystem;
        if (!$file->exists($outputdir)) {
            \Log::warning('generate bilan; Given output dir doesn\'t exist; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file->isDirectory($outputdir)) {
            \Log::warning('generate bilan; Given output file is not a directory; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file-> isWritable($outputdir)) {
            \Log::warning('generate bilan; Given output dir is not writable; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file->exists($templatefile)) {
            \Log::warning('generate bilan; Given file doesn\'t exist; file=>'.$templatefile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $i = 0;
        $failed = 0;
        $desc = $progressFile;
        if ($desc == null) {
            $desc = fopen(Bilan::$PROGRESS_FILE, 'w');
            if ($desc === false) {
                \Log::warning('generate bilan; impossible to open for write the given file; file=>'.Bilan::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
                return false;
            }
            if (flock($desc, LOCK_EX) === false) {
                \Log::warning('generate bilan; impossible to flock the progress file; given file =>'.$progressFile.'<', \App\Helpers\Context::getContext());
                return false;
            }
        }
        $message = $i.'/'.count($pointcollecte_ids).'/'.$failed;
        if ($desc !== false)
            if (fwrite($desc, $message) != strlen($message)) {
                \Log::warning('generate bilan; error while writing progression', \App\Helpers\Context::getContext());
                return false;
            }
        foreach($pointcollecte_ids AS $pointcollecte_id) {
            $i++;
            $finalOutputdir = $outputdir.'/'.$pointcollecte_id;
            if (!$file->exists($finalOutputdir))
                if (!$file->makeDirectory($finalOutputdir)) {
                    \Log::warning("generate bilan; Couldn't create the following directory: >".$finalOutputdir, \App\Helpers\Context::getContext());
                    continue;
                }
            if (! Bilan::generate($integrateur_id, $pointcollecte_id, $templatefile, $finalOutputdir.'/bilan-'.$year.'-'.$integrateur_id.'.pdf', $year))
                $failed++;
            $message = $i.'/'.count($pointcollecte_ids).'/'.$failed;
            if ($desc !== false)
                if (fwrite($desc, $message) != strlen($message)) {
                    \Log::warning('generate bilan; error while writing progression', \App\Helpers\Context::getContext());
                }
        }
        return ['done' => $i, 'total' => count($pointcollecte_ids), 'failed' => $failed];
    }
    public static function generateAll(int $integrateur_id, bool $publish = false, int $year = null) {
        $desc = fopen(Bilan::$PROGRESS_FILE, 'w');
        if ($desc === false) {
            \Log::warning('generate bilan; impossible to open for write the given file; file=>'.Bilan::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (flock($desc, LOCK_EX) === false) {
            \Log::warning('generate bilan; impossible to flock the progress file; given file =>'.Bilan::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if ($year == '0')
            $year = date('Y') - 1;
        $pointcollecte_ids = Passage::join('pointcollectes AS Pc', 'Pc.id', 'passages.pointcollecte_id')
                                    ->join('clients        AS C' , 'C.id' , 'Pc.client_id')
                                    ->where(DB::raw('YEAR(date_debut)'), $year)
                                    ->where('C.integrateur_id', $integrateur_id)
                                    ->select([DB::raw('distinct pointcollecte_id')])
                                    ->get()->reduce(function ($acc, $value) {
            array_push($acc, $value->pointcollecte_id);
            return $acc;
        }, []);

        $result = Bilan::generateMultiple($integrateur_id, $pointcollecte_ids, Paths::bilanTemplate($integrateur_id), Paths::clientDocuments(), $year, $desc);
        if ($result === false) return false;
        if ($result['failed'] != 0)   return $result;
        if (!$publish)         return $result;
        $toInsert = [];
        // DOIT être synchro avec ior:bilan:generate:multiple : $finalOutputdir.'/bilan-'.$year.'-'.$integrateur_id.'.pdf'
        $filename = '/bilan-'.$year.'-'.$integrateur_id.'.pdf';
        $currentTimestamp = DB::raw('CURRENT_TIMESTAMP');
        foreach($pointcollecte_ids AS $value) {
            array_push($toInsert, ['document'=> $value.$filename, 'annee' => $year, 'pointcollecte_id' => $value, 'updated_at' => $currentTimestamp]);
        }
        DB::transaction(function () use (&$toInsert) {
            while(count($toInsert) > 0) { //le pilote laravel ne semble pas prendre en charge la limitation de taille de requête
                $tmp = array_splice($toInsert, 0, 100);
                Bilan::upsert($tmp, ['updated_at']);
            }
        });
        return $result;
    }
    public static function generateAllBatch(int $integrateur_id, string $email, int $year = null) {
        if ($year == '0')
            $year = date('Y') - 1;
        $jobs = [];

        $pointcollecte_ids = Passage::join('pointcollectes AS Pc', 'Pc.id', 'passages.pointcollecte_id')
                                    ->join('clients        AS C' , 'C.id' , 'Pc.client_id')
                                    ->where(DB::raw('YEAR(date_debut)'), $year)
                                    ->where('C.integrateur_id', $integrateur_id)
                                    ->select([DB::raw('distinct pointcollecte_id')])
                                    ->get()->reduce(function ($acc, $value) use (&$jobs, &$year, &$integrateur_id) {
            array_push($acc, $value->pointcollecte_id);
            array_push($jobs, new GenerateOneBilanJob($integrateur_id, $value->pointcollecte_id, $year));
            return $acc;
        }, []);

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use($pointcollecte_ids, $year, $integrateur_id) {
            $toInsert = [];
            // DOIT être synchro avec ior:bilan:generate:multiple : $finalOutputdir.'/bilan-'.$year.'-'.$integrateur_id.'.pdf'
            $filename = '/bilan-'.$year.'-'.$integrateur_id.'.pdf';
            $currentTimestamp = DB::raw('CURRENT_TIMESTAMP');
            foreach($pointcollecte_ids AS $value) {
                array_push($toInsert, ['document'=> $value.$filename, 'annee' => $year, 'pointcollecte_id' => $value, 'updated_at' => $currentTimestamp]);
            }
            DB::transaction(function () use (&$toInsert) {
                while(count($toInsert) > 0) { //le pilote laravel ne semble pas prendre en charge la limitation de taille de requête
                    $tmp = array_splice($toInsert, 0, 100);
                    Bilan::upsert($tmp, ['updated_at']);
                }
            });
        })->catch(function (Batch $batch, \Throwable $e) use($email, $year) {
            \Log::warning('Bilan::generateAllBatch failed; info=>'.json_encode(['name' => $batch->name, 'totalJobs' => $batch->totalJobs, 'pendingJobs' => $batch->pendingJobs, 'failedJobs' => $batch->failedJobs, 'createdAt' => $batch->createdAt, 'cancelledAt' => $batch->cancelledAt, 'finishedAt' => $batch->finishedAt, 'throw' => $e]).'<', \App\Helpers\Context::getContext());
        })->finally(function (Batch $batch) use ($email, $year) {
            if ($email == "") return;
            $stats = 'Démarré à '.$batch->createdAt.' et terminé à '.$batch->finishedAt.' pour '.$batch->totalJobs.' bilans au total et '.$batch->failedJobs.' échoué(s)';
            Mail::to($email)->send(new BilansGenerated($year, $batch->failedJobs == 0, $stats));
        })->name('BILANS-'.$integrateur_id.'-'.$year)->dispatch();

        return true;
    }
}
