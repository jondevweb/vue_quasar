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
use App\Jobs\GenerateOneAttestationJob;
use App\Mail\AttestationsGenerated;

class Attestation extends Model
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
    static public $PROGRESS_FILE = '/tmp/attestation-generation-progress';
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
        $dechetSQL = <<<"EOF"
            SELECT Pc.client_id,  D.attestation_type, SUM(C.poids) AS poids, P.pointcollecte_id, D.nom, C.dechet_id
            FROM dechets D
            INNER JOIN collectes      C  ON C.dechet_id         = D.id
            INNER JOIN passages       P  ON C.passage_id        = P.id
            INNER JOIN pointcollectes Pc ON P.pointcollecte_id  = Pc.id
            WHERE D.attestation_type <> 0 AND YEAR(P.date_debut) = $year AND Pc.id = $pointcollecte_id
            GROUP BY D.attestation_type
        EOF;
        $identiteSQL = <<<"EOF"
            SELECT Cl.id AS client_id, Cl.contrat
            , Pc.nom, Pc.adresse AS adresse
            , I_E.raison_sociale AS integrateur, I_E.adresse_administrative AS integrateur_adresse, T.recepisse AS integrateur_recepisse, I_E.SIRET AS integrateur_SIRET
            , I_U.email AS integrateur_email, IF(I_U.telephone IS NULL, I_U.portable, I_U.telephone) AS integrateur_telephone
            , CONCAT(IF(I_U.civilite = 0, 'Mme ','M. '), I_U.prenom, ' ', I_U.nom) AS integrateur_personne_contact
            , CONCAT(IF(U.civilite = 0, 'Mme ','M. '), U.prenom, ' ', U.nom) AS personne_contact, U.email, IF(U.telephone IS NULL, U.portable, U.telephone) as telephone, E.SIRET, $year AS annee, T.departement AS integrateur_departement
            , T.type_activite_prefecture AS integrateur_type_activite_prefecture, DATE_FORMAT(T.limite_recepisse, '%d/%m/%Y') as integrateur_limite_validite_recepisse
            , DATE_FORMAT(Cl.contrat, '%d/%m/%y') AS contrat
            , E.id AS id_entreprise, I.id AS id_integrateur
            , CONCAT('$year', LPAD(Pc.id, 6, "0"), '-AV') AS numero_attestation
            FROM pointcollectes      Pc
            INNER JOIN clients       Cl  ON Pc.client_id         = Cl.id AND Cl.integrateur_id = 1
            INNER JOIN entreprises   E   ON Cl.entreprise_id     = E.id
            INNER JOIN integrateurs  I   ON Cl.integrateur_id    = I.id
            INNER JOIN transporteurs T   ON T.entreprise_id      = I.entreprise_id
            INNER JOIN entreprises   I_E ON I.entreprise_id      = I_E.id
            INNER JOIN users         U   ON Cl.contact_juridique = U.id
            INNER JOIN users         I_U ON I.signataire         = I_U.id
            WHERE Pc.id = $pointcollecte_id;
        EOF;
        $file = new Filesystem;
        if (!$file->exists($templatefile)) {
            \Log::warning('generate attestation; Given file doesn\'t exist; file=>'.$templatefile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $templatefile = realpath($templatefile);
        $templateDir  = $file->dirname($templatefile);
        if (!realpath($file->dirname($outputfile))) {
            \Log::warning('generate attestation; Given parent output directory doesn\'t exist; file=>'.$outputfile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $result = [
            'commun'         => DB::select($identiteSQL),
            'id_type_dechet' => DB::select($dechetSQL),
        ];
        if (count($result['commun']) != 1) {
            \Log::warning('generate attestation; when retrieving pointcollecte info too much ou no info found; info=>'.json_encode(['result[commun]' => $result['commun'], 'pointcollecte_id' => $pointcollecte_id]).'<', \App\Helpers\Context::getContext());
            return false;
        }
        $result['commun'] = $result['commun'][0];
        file_put_contents($templateDir.'/data.js', 'var data='.json_encode($result));//
        $command = Attestation::$CHROME.Attestation::$CHROME_PARAMS.' --disable-gpu --disable-software-rasterizer --print-to-pdf='.$outputfile.' file://'.$templatefile /*.' 2>&1 > /dev/stdout'*/;
        $result = exec($command, $output, $retval);
        if ($result === false) {
            \Log::warning('generate attestation; Failed to generate the attestation!; file=>'.\json_encode(['retval'=> $retval, 'command' => $command, 'output' => $output]), \App\Helpers\Context::getContext());
            return false;
        }
        return true;
    }
    public static function generateMultiple(int $integrateur_id, array $pointcollecte_ids, string $templatefile, string $outputdir, int $year = null, $progressFile = false) {
        if ($year == '0')
            $year = date('Y') - 1;
        $file = new Filesystem;
        if (!$file->exists($outputdir)) {
            \Log::warning('generate attestation; Given output dir doesn\'t exist; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file->isDirectory($outputdir)) {
            \Log::warning('generate attestation; Given output file is not a directory; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file-> isWritable($outputdir)) {
            \Log::warning('generate attestation; Given output dir is not writable; file=>'.$outputdir.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (!$file->exists($templatefile)) {
            \Log::warning('generate attestation; Given file doesn\'t exist; file=>'.$templatefile.'<', \App\Helpers\Context::getContext());
            return false;
        }
        $i = 0;
        $failed = 0;
        $desc = $progressFile;
        if ($desc == null) {
            $desc = fopen(Attestation::$PROGRESS_FILE, 'w');
            if ($desc === false) {
                \Log::warning('generate attestation; impossible to open for write the given file; file=>'.Attestation::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
                return false;
            }
            if (flock($desc, LOCK_EX) === false) {
                \Log::warning('generate attestation; impossible to flock the progress file; given file =>'.$progressFile.'<', \App\Helpers\Context::getContext());
                return false;
            }
        }
        $message = $i.'/'.count($pointcollecte_ids).'/'.$failed;
        if ($desc !== false)
            if (fwrite($desc, $message) != strlen($message)) {
                \Log::warning('generate attestation; error while writing progression', \App\Helpers\Context::getContext());
                return false;
            }
        foreach($pointcollecte_ids AS $pointcollecte_id) {
            $i++;
            $finalOutputdir = $outputdir.'/'.$pointcollecte_id;
            if (!$file->exists($finalOutputdir))
                if (!$file->makeDirectory($finalOutputdir)) {
                    \Log::warning("generate attestation; Couldn't create the following directory: >".$finalOutputdir, \App\Helpers\Context::getContext());
                    continue;
                }
            if (! Attestation::generate($integrateur_id, $pointcollecte_id, $templatefile, $finalOutputdir.'/attestation-'.$year.'-'.$integrateur_id.'.pdf', $year))
                $failed++;
            $message = $i.'/'.count($pointcollecte_ids).'/'.$failed;
            if ($desc !== false)
                if (fwrite($desc, $message) != strlen($message)) {
                    \Log::warning('generate attestation; error while writing progression', \App\Helpers\Context::getContext());
                }
        }
        return ['done' => $i, 'total' => count($pointcollecte_ids), 'failed' => $failed];
    }
    public static function fullyGenerateOne(int $integrateur_id, int $pointcollecte_id, int $year) {
        $publish = true;
        $desc = fopen(Attestation::$PROGRESS_FILE, 'w');
        if ($desc === false) {
            \Log::warning('generate attestation; impossible to open for write the given file; file=>'.Attestation::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (flock($desc, LOCK_EX) === false) {
            \Log::warning('generate attestation; impossible to flock the progress file; given file =>'.Attestation::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if ($year == '0')
            $year = date('Y') - 1;
        $pointcollecte_ids = [$pointcollecte_id];

        $result = Attestation::generateMultiple($integrateur_id, $pointcollecte_ids, Paths::attestationTemplate($integrateur_id), Paths::clientDocuments(), $year, $desc);
        if ($result === false) return false;
        if ($result['failed'] != 0)   return $result;
        if (!$publish)         return $result;
        $toInsert = [];
        // DOIT être synchro avec ior:attestation:generate:multiple : $finalOutputdir.'/attestation-'.$year.'-'.$integrateur_id.'.pdf'
        $filename = '/attestation-'.$year.'-'.$integrateur_id.'.pdf';
        $currentTimestamp = DB::raw('CURRENT_TIMESTAMP');
        foreach($pointcollecte_ids AS $value) {
            array_push($toInsert, ['document'=> $value.$filename, 'annee' => $year, 'pointcollecte_id' => $value, 'updated_at' => $currentTimestamp, 'numero' => $year.str_pad($value, 6, '0', STR_PAD_LEFT).'-AV']);
        }
        DB::transaction(function () use (&$toInsert) {
            while(count($toInsert) > 0) { //le pilote laravel ne semble pas prendre en charge la limitation de taille de requête
                $tmp = array_splice($toInsert, 0, 100);
                Attestation::upsert($tmp, ['updated_at', 'document', 'numero']);
            }
        });
        return $result;
    }
    public static function generateAll(int $integrateur_id, bool $publish = false, int $year = null) {
        $desc = fopen(Attestation::$PROGRESS_FILE, 'w');
        if ($desc === false) {
            \Log::warning('generate attestation; impossible to open for write the given file; file=>'.Attestation::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if (flock($desc, LOCK_EX) === false) {
            \Log::warning('generate attestation; impossible to flock the progress file; given file =>'.Attestation::$PROGRESS_FILE.'<', \App\Helpers\Context::getContext());
            return false;
        }
        if ($year == '0')
            $year = date('Y') - 1;
        $pointcollecte_ids = Passage::join('pointcollectes AS Pc', 'Pc.id', 'passages.pointcollecte_id')
                                    ->join('clients        AS C' , 'C.id' , 'Pc.client_id')
                                    ->join('collectes      AS Co', 'passages.id', 'Co.passage_id')
                                    ->join('dechets        AS D' , 'D.id' , 'Co.dechet_id')
                                    ->where('D.attestation_type', '<>', 0)
                                    ->where(DB::raw('YEAR(date_debut)'), $year)
                                    ->where('C.integrateur_id', $integrateur_id)
                                    ->select([DB::raw('distinct pointcollecte_id')])
                                    ->get()->reduce(function ($acc, $value) {
            array_push($acc, $value->pointcollecte_id);
            return $acc;
        }, []);

        $result = Attestation::generateMultiple($integrateur_id, $pointcollecte_ids, Paths::attestationTemplate($integrateur_id), Paths::clientDocuments(), $year, $desc);
        if ($result === false) return false;
        if ($result['failed'] != 0)   return $result;
        if (!$publish)         return $result;
        $toInsert = [];
        // DOIT être synchro avec ior:attestation:generate:multiple : $finalOutputdir.'/attestation-'.$year.'-'.$integrateur_id.'.pdf'
        $filename = '/attestation-'.$year.'-'.$integrateur_id.'.pdf';
        $currentTimestamp = DB::raw('CURRENT_TIMESTAMP');
        foreach($pointcollecte_ids AS $value) {
            array_push($toInsert, ['document'=> $value.$filename, 'annee' => $year, 'pointcollecte_id' => $value, 'updated_at' => $currentTimestamp, 'numero' => $year.str_pad($value, 6, '0', STR_PAD_LEFT).'-AV']);
        }
        DB::transaction(function () use (&$toInsert) {
            while(count($toInsert) > 0) { //le pilote laravel ne semble pas prendre en charge la limitation de taille de requête
                $tmp = array_splice($toInsert, 0, 100);
                Attestation::upsert($tmp, ['updated_at', 'document', 'numero']);
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
                                    ->join('collectes      AS Co', 'passages.id', 'Co.passage_id')
                                    ->join('dechets        AS D' , 'D.id' , 'Co.dechet_id')
                                    ->where('D.attestation_type', '<>', 0)
                                    ->where(DB::raw('YEAR(date_debut)'), $year)
                                    ->where('C.integrateur_id', $integrateur_id)
                                    ->select([DB::raw('distinct pointcollecte_id')])
                                    ->get()->reduce(function ($acc, $value) use (&$jobs, &$year, &$integrateur_id) {
            array_push($acc, $value->pointcollecte_id);
            array_push($jobs, new GenerateOneAttestationJob($integrateur_id, $value->pointcollecte_id, $year));
            return $acc;
        }, []);

        $batch = Bus::batch($jobs)->then(function (Batch $batch) use($pointcollecte_ids, $year, $integrateur_id) {
            $toInsert = [];
            // DOIT être synchro avec ior:attestation:generate:multiple : $finalOutputdir.'/attestation-'.$year.'-'.$integrateur_id.'.pdf'
            $filename = '/attestation-'.$year.'-'.$integrateur_id.'.pdf';
            $currentTimestamp = DB::raw('CURRENT_TIMESTAMP');
            foreach($pointcollecte_ids AS $value) {
                array_push($toInsert, ['document'=> $value.$filename, 'annee' => $year, 'pointcollecte_id' => $value, 'updated_at' => $currentTimestamp, 'numero' => $year.str_pad($value, 6, '0', STR_PAD_LEFT).'-AV']);
            }
            DB::transaction(function () use (&$toInsert) {
                while(count($toInsert) > 0) { //le pilote laravel ne semble pas prendre en charge la limitation de taille de requête
                    $tmp = array_splice($toInsert, 0, 100);
                    Attestation::upsert($tmp, ['updated_at', 'document', 'numero']);
                }
            });
        })->catch(function (Batch $batch, \Throwable $e) use($email, $year) {
            \Log::warning('Attestation::generateAllBatch failed; info=>'.json_encode(['name' => $batch->name, 'totalJobs' => $batch->totalJobs, 'pendingJobs' => $batch->pendingJobs, 'failedJobs' => $batch->failedJobs, 'createdAt' => $batch->createdAt, 'cancelledAt' => $batch->cancelledAt, 'finishedAt' => $batch->finishedAt, 'throw' => $e]).'<', \App\Helpers\Context::getContext());
        })->finally(function (Batch $batch) use ($email, $year) {
            if ($email == "") return;
            $stats = 'Démarré à '.$batch->createdAt.' et terminé à '.$batch->finishedAt.' pour '.$batch->totalJobs.' attestations au total et '.$batch->failedJobs.' échoué(s)';
            Mail::to($email)->send(new AttestationsGenerated($year, $batch->failedJobs == 0, $stats));
        })->name('ATTESTATIONS-'.$integrateur_id.'-'.$year)->dispatch();

        return true;
    }
}
