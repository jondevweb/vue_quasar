<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Dechet;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Helpers\Pdf;
use App\Helpers\Paths;
use App\Helpers\Files;
use \App\Helpers\HZip;

class Document extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom',
        'document',
        'generation',
        'dechet_id',
        'type',
    ];
    public function dechet()
    {
        return $this->belongsTo(Dechet::class);
    }
    public static function dechetModelsLocation(string $subDir = '') {
        return  \Storage::disk('dechetmodel')->path($subDir);
    }
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['nom' => 'required|max:255', 'document' => 'max:255'
                 , 'generation' => 'min:0|max:3', 'dechet_id' => 'numeric'];

        if (!$withRequired)
            $retour = ['nom' => 'max:255', 'document' => 'max:255'
                     , 'generation' => 'min:0|max:3', 'dechet_id' => 'numeric'];
        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(&$fillable, &$dechet_id) {
        return DB::transaction(function ()   use (&$fillable, &$dechet_id) {
            $doc = Document::create(array_merge($fillable, ['dechet_id' => $dechet_id]));
            return $doc->id;
        });
    }
    public function path() {
        return Document::dechetModelsLocation($this->document);
    }
    /**
     * @param docs documents à utiliser comme modèle ; doit être de la forme :
     *             [ id: integrateur_id, document: quelque chose comme integrateur_id/mondoc.pdf ]
     * @param infos une Collection d'infos pour le PDF (pour extraction des nom de champs :  pdftk bordereau_passage.pdf dump_data_fields | grep FieldName) :
     *             - collecte_id et ou  passage_id
     *             - entreprise_id
     * @param typeForNotification pour le moment seulement 0; c.f. commentaire en BD
     * @param outputfilepath      chemin complet (chemin et nom de fichier) menant au fichier
     *
     * @remarks il faut que entreprise_id figure dans les informations car le doc généré est placé dans clientDocuments
     */
    static public function generate(object &$info, int $document_id, Collection &$users, int $typeForNotification, string $pdfTpl, string $outputfilepath = '') {
        $generationInfo = @json_decode(json_encode($info), true);//(array)$info;
        $filepath = '';
        $filename = '';
        $fullfilename = $outputfilepath;
        $pdf = Pdf::fromPath($pdfTpl);

        if ($outputfilepath == '') {
            $filepath = Paths::clientDocuments($generationInfo['entreprise_id']);
            if (! file_exists($filepath))
                if (! mkdir($filepath, 0755, true)) {
                    $message = 'Failed to create: '.$filepath;
                    \Log::warning($message, \App\Helpers\Context::getContext());
                    throw(new \Exception($message));
                }

            $filename = Files::uniqFilename('pdf', $filepath);
            $fullfilename = $filepath.'/'.$filename;
        }else{
            $pathInfo = pathinfo($outputfilepath);
            if (! file_exists($pathInfo['dirname']))
                if (! mkdir($pathInfo['dirname'], 0755, true)) {
                    $message = 'Failed to create: '.$filepath;
                    \Log::warning($message, \App\Helpers\Context::getContext());
                    throw(new \Exception($message));
                }

        }
        if ($pdf->fill($generationInfo, $fullfilename) === false) {
            $message = 'Impossible to fill the PDF! path='.$fullfilename.'; stack: '.(new \Exception)->getTraceAsString();
            \Log::warning($message, \App\Helpers\Context::getContext());
            return $message;
        }
        //Il faudrait normalement vérifier si il existe déjà une entrée et supprimer l'éventuel fichier devenu inutile
        if($outputfilepath == '')
            DB::table('collecte_document')->upsert([ ['collecte_id' => isset($generationInfo['collecte_id']) ? $generationInfo['collecte_id'] : DB::RAW('NULL')
                                                    , 'passage_id'  => isset($generationInfo['passage_id'])  ? $generationInfo['passage_id']  : DB::RAW('NULL')
                                                    , 'document_id' => $document_id, 'document' => $generationInfo["entreprise_id"].'/'.$filename
                                                    , 'date' => DB::raw('CURRENT_TIMESTAMP'), 'updated_at' => DB::raw('CURRENT_TIMESTAMP')] ]
                                                , ['collecte_id', 'document_id']
                                                , ['document', 'date', 'updated_at']);


        foreach ($users as &$user) {
            DB::table('mailnotifications')->upsert([ ['user_id' => $user->id, 'type' => $typeForNotification, 'updated_at' => DB::raw('CURRENT_TIMESTAMP')] ]
                                                 , ['user_id', 'type']
                                                 , ['updated_at']);
        }
        return true;
    }
    /**
     *
     * @remark idem que generate mais accepte un tableau d'objets et fait tout en automatique (utile pour les documents des déchets)
     */
    static public function generates(Collection &$infos, Collection &$docs, Collection &$users, int $typeForNotification) {
        $emptyUsers = new Collection();
        foreach ($infos as &$info) {
            foreach ($docs as &$doc) {
                if (!Document::generate($info, $doc->id, $emptyUsers, $typeForNotification, Document::dechetModelsLocation().$doc->document))
                    return false;
            }
        }


        foreach ($users as &$user) {
            DB::table('mailnotifications')->upsert([ ['user_id' => $user->id, 'type' => $typeForNotification, 'updated_at' => DB::raw('CURRENT_TIMESTAMP')] ]
                                                 , ['user_id', 'type']
                                                 , ['updated_at']);
        }
        return true;
    }
    /**
     *
     * @remark idem que generates mais accepte un simple objet
     */
    static public function generateSingleFullAuto(object &$info, Collection &$docs, Collection &$users, int $typeForNotification) {
        $wrap = new Collection();
        $wrap->push($info);
        return Document::generates($wrap, $docs, $users, $typeForNotification);
    }
    /**
     *
     * @param name nom de l'archive (sans le .zip) ; sera utilisé pour nommer le sous répertoire dans l'archive
     * @return retourne un chemin au fichier ; le nom est aléatoire et ne correspond pas au nom fourni au paramètre
     */
    static public function zip(array $files, string $name) {
        $outputDir = '/tmp/'.Files::uniqFilename('', '/tmp', true);
        $fs = new Filesystem;
        if (!$fs->makeDirectory($outputDir.'/'.$name)) {
            \Log::warning('Failed to create a directory; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
        }
        foreach($files AS &$file) {
            if (!$fs->copy($file['file'], $outputDir.'/'.$name.'/'.$file['name'])) {
                \Log::warning('Failed to copy file for creating archives; copy from=>'.$src.'<, to=>'.$outputDir.'/'.$dst.'<; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
        }
        HZip::zipDir($outputDir.'/'.$name, $outputDir.'/'.$name.'.zip');
        $finalFile = '/tmp/'.Files::uniqFilename('', '/tmp');
        $fs->move($outputDir.'/'.$name.'.zip', $finalFile);
        $fs->deleteDirectory($outputDir);
        return $finalFile;
    }
/*    public function getFields($user_id = null) {
        $fs             = new Filesystem;
        $fdf = '';
        if ($user_id == null) $fdf = tempnam(sys_get_temp_dir(), 'FDF');
        else {
            $fdf = \Storage::disk('users_tmp')->path($user_id);
            $fdf = \tempnam($fdf, 'FDF');
        }
        try{
            $pdfFilename = \Storage::disk('dechetmodel')->path($this->document);
            $result = Document::generate_fdf($pdfFilename, $fdf);
            if ($result !== true)  {
                \Log::warning('Convertion to FDF failed; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return [];
            }
            $result = Document::parse_fdf($fdf);
            if ($result === false)  {
                \Log::warning('FDF parsing failed; stack: '.$fdf.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return [];
            }
            return $result;
        }finally{
            if(!empty($fdf)) $fs->delete($fdf);
        }
    }


    public static function parse_fdf($filename) {
        $NOISE       = 0;
        $FIELD_NAME  = 1;
        $retour = [];
        $state = $NOISE;
        $key   = '';

        $handle = fopen($filename, "r");

        if (!$handle)
            return false;

        while (($buffer = fgets($handle, 4096)) !== false) {
            switch ($state) {
                case $NOISE:
                    if (preg_match("/^\/T \((.*)\)$/", $buffer, $matches)) {
                        $key = iconv('ISO-8859-15', 'UTF8//TRANSLIT', $matches[1]);
                        $state = $FIELD_NAME;
                    }
                    break;
                case $FIELD_NAME:
                    if (preg_match("/^\/V \((.*)\)$/", $buffer, $matches)) {
                        $retour[$key] = str_replace(['\r\n', '\n', '\r', '\\\\'], ["\n", "\n", "\n", '\\'], iconv('ISO-8859-15', 'UTF8//TRANSLIT', $matches[1]));
                        $state = $NOISE;
                    }
                    break;
            }
        }
        if (!feof($handle))  return false;
        fclose($handle);
        return $retour;
    }
    public static function generate_fdf($pdfFilename, $tmpfile) {
        $result_code = 0;
        $output      = [];
        exec('pdftk  '.$pdfFilename.' generate_fdf  output '.$tmpfile, $output, $result_code);
        if ($result_code != 0)
            return $output;
        return true;
    }
    public static function fill_fdf($values, $outputfile) {
        $buffer = "%FDF-1.2\n%\xe2\xe3\xcf\xd3\n1 0 obj \n<<\n/FDF\n<<\n/Fields [\n";
        foreach($values as $key => &$value) {
            $buffer .= "<<\n/T (".str_replace(["\n", "\n", "\n", '\\', '(', ')'], ['\r\n', '\r\n', '\r\n', '\\\\', '\\(', '\\)'], iconv('UTF8', 'ISO-8859-15//TRANSLIT',$key)).")\n"
                          ."/V (".str_replace(["\n", "\n", "\n", '\\', '(', ')'], ['\r\n', '\r\n', '\r\n', '\\\\', '\\(', '\\)'], iconv('UTF8', 'ISO-8859-15//TRANSLIT',$value)).")\n"
                      .">>";
        }
        $buffer .= "]\n>>\n>>\nendobj \ntrailer\n\n<<\n/Root 1 0 R\n>>\n%%EOF\n";
        return file_put_contents($outputfile, $buffer);
    }
    public static function fill_pdf($pdfFilename, $fdfFilename, $outputfile) {
        $result_code = 0;
        $output      = [];
        exec('pdftk  '.$pdfFilename.' fill_form '.$fdfFilename.' output '.$outputfile .' flatten', $output, $result_code);
        if ($result_code != 0) {
            print_f($output);
            return false;
        }
        return $output;
    }*/
}
