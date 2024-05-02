<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;

class Pdf {
    private $path   = null;
    private $fields = null;
    /**
     *
     * @return an instance of PDF or false if an error occured
     */
    static public function fromUpload(Request &$request, string $disk, string $path, string $attribut = 'file') {
        $fdfHandle = null;
        $pdf       = new Pdf;
        $fs        = new Filesystem;
        $pdf->path = Upload::saveUpload($request,  $disk, $path, 'application/pdf', $attribut);
        if ($pdf->path === false) return false;
        $pdf->path = realpath($pdf->path);

        try {
            $fdfHandle = tmpfile();
            $fdf = stream_get_meta_data($fdfHandle)['uri'];

            $result = Pdf::generate_fdf($pdf->path, $fdf);
            if ($result === false) {
                \Log::warning('Convertion to FDF failed; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }

            $pdf->fields  = Pdf::parse_fdf($fdf);
            if ($pdf->fields === false) {
                \Log::warning('Failed to extract fields; strange error while trying to open the file; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
        } finally {
            if (!empty($fdfHandle))  fclose($fdfHandle);
        }
        return $pdf;
    }
    static public function fromPath(string $path) {
        $fdfHandle = null;
        $pdf = new Pdf;
        $fs  = new Filesystem;
        if (mime_content_type($path) != 'application/pdf')                    {
            \Log::warning('No >application/pdf< file provided! Detected type='.mime_content_type($path).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            $fs = new Filesystem;
            $fs->delete($path);
            return false;
        }
        $pdf->path = realpath($path);
        return $pdf;
    }
    static public function concat(array $inputs, string $outputfile) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open('pdftk "'.implode('" "', $inputs).'" cat output '.$outputfile, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            $errorMsg = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $result_code = proc_close($process);
            if ($result_code != 0) {
                \Log::warning('Failed to concat the PDFs; stderr= >'.$errorMsg.'<', \App\Helpers\Context::getContext());
                return false;
            }
            return true;
        }
        return false;
    }
    /**
     * @return chemin « canon »
     */
    public function path() {
        return $this->path;
    }
    public function fields(bool $headersOnly = false) {
        if ($this->fields == null) {
            try {
                $fdfHandle = tmpfile();
                $fdf = stream_get_meta_data($fdfHandle)['uri'];

                $result = Pdf::generate_fdf($this->path, $fdf);
                if ($result === false) {
                    \Log::warning('Convertion to FDF failed; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                    return false;
                }
                $this->fields  = Pdf::parse_fdf($fdf);
                if ($this->fields === false) {
                    \Log::warning('Failed to extract fields; strange error while trying to open the file; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                    return false;
                }
            } finally {
                if (!empty($fdfHandle))  fclose($fdfHandle);
            }
        }
        if ($headersOnly)
            return array_keys($this->fields);
        return $this->fields;
    }
    public function convert(string $outputfile, int $quality = 2) {
        $result_code = 0;
        $output      = [];
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        if ($quality <= 2)        $convertQuality = '100x100';
        else if ($quality <= 4)   $convertQuality = '150x150';
        else if ($quality <= 6)   $convertQuality = '300x300';
        else if ($quality <= 8)   $convertQuality = '600x600';
        else $convertQuality = '1200x1200';

        $process = proc_open('/usr/bin/convert  -density '.$convertQuality.' -alpha flatten '.$this->path.' '.$outputfile, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            $errorMsg = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $result_code = proc_close($process);
            if ($result_code != 0) {
                \Log::warning('Failed to convert the PDF; result_code='.$result_code.', stderr= >'.$errorMsg.'< stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            return true;
        }
        return false;
    }
    public function rotate(string $outputfile, string $params = '1-enddown') {
        $result_code = 0;
        $output      = [];
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open('pdftk '.$this->path.' rotate '.$params.' output '.$outputfile, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            $errorMsg = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $result_code = proc_close($process);
            if ($result_code != 0) {
                \Log::warning('Failed to rotate the PDF; stderr= >'.$errorMsg.'< stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            return true;
        }
        return false;
    }
    public function fill($values, $outputfile) {
        $result_code = 0;
        $output      = [];
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open('pdftk  '.$this->path.' fill_form - output '.$outputfile .' flatten', $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], Pdf::fill_fdf($values));
            fclose($pipes[0]);
            fclose($pipes[1]);
            $errorMsg = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $result_code = proc_close($process);
            if ($result_code != 0) {
                \Log::warning('Failed to generate the PDF; stderr= >'.$errorMsg.'< stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            return true;
        }
        return false;
    }
    static function stamp($pdfFilename, $stampFile, $outputFile) {//génère un fdf d'après un PDF
        $result_code = 0;
        $output      = [];
        exec('pdftk  '.$pdfFilename.' multistamp '.$stampFile.' output '.$outputFile, $output, $result_code);
        if ($result_code != 0)
            return $output;
        return true;
    }
    static function generate_fdf($pdfFilename, $tmpfile) {//génère un fdf d'après un PDF
        $result_code = 0;
        $output      = [];
        exec('pdftk  '.$pdfFilename.' generate_fdf  output '.$tmpfile, $output, $result_code);
        if ($result_code != 0)
            return $output;
        return true;
    }
    static function fill_fdf($values/*, $outputfile*/) {//génère un FDF d'après un tableau de valeur
        $buffer = "%FDF-1.2\n%\xe2\xe3\xcf\xd3\n1 0 obj \n<<\n/FDF\n<<\n/Fields [\n";
        foreach($values as $key => &$value) {
            $buffer .= "<<\n/T (".str_replace(["\n", "\n", "\n", '\\', '(', ')'], ['\r\n', '\r\n', '\r\n', '\\\\', '\\(', '\\)'], iconv('UTF8', 'ISO-8859-15//TRANSLIT',$key)).")\n"
                          ."/V (".str_replace(["\n", "\n", "\n", '\\', '(', ')'], ['\r\n', '\r\n', '\r\n', '\\\\', '\\(', '\\)'], iconv('UTF8', 'ISO-8859-15//TRANSLIT',$value)).")\n"
                      .">>";
        }
        $buffer .= "]\n>>\n>>\nendobj \ntrailer\n\n<<\n/Root 1 0 R\n>>\n%%EOF\n";
        return $buffer;//file_put_contents($outputfile, $buffer);
    }
    /**
     *
     * @param filename soit une ressource, soit le chemin du fichier
     */
    static function parse_fdf($filename) {
        $NOISE       = 0;
        $FIELD_NAME  = 1;
        $retour = [];
        $state = $NOISE;
        $key   = '';
        $handle = false;
        //$handle = fopen($filename, "r");
        if (is_string($filename)) $handle = fopen($filename, "r");
        else $handle = $filename;

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
}
