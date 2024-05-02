<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;

class Files {
    const UNOCONV = '/usr/bin/unoconv';
    static public function uniqFilename($extension, $path = '', bool $isDir = false) {
        $name = (new \DateTime())->format('Uu').uniqid();
        if (empty($path)) return $name.'.'.$extension;
        $handle = false;
        while($handle === false) {
            $name .= random_int(100000000,  PHP_INT_MAX);
            if ($isDir) $handle = @mkdir($path.'/'.$name.'.'.$extension);
            else $handle = fopen($path.'/'.$name.'.'.$extension, 'x');
        }
        if (!$isDir) fclose($handle);
        return $name.'.'.$extension;
    }
    static public function csv2Xlsx(string $inputFile, string $outputFile) {
        $output=null;
        $retval=null;
        $command = 'HOME='.config('app.sysroot').' '.self::UNOCONV .' -f xlsx -o '.$outputFile.' '.$inputFile;
        $result = exec($command, $output, $retval);
        if ($result === false) {
            \Log::warning('Failed to convert a csv! retval='.$retval.', command=>'.$command.'<; output=>'.$output.'< ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        return true;
    }
    static public function rows2Xlsx(array &$rowsList, bool $toIso8859 = true, string $xlsx = '') {
        $csv  = '/tmp/'.Files::uniqFilename('csv', '/tmp');
        if (empty($outputFile))
            $xlsx = '/tmp/'.Files::uniqFilename('xlsx', '/tmp');

        $FH = fopen($csv, 'w');
        if ($toIso8859)
            foreach ($rowsList as $row) {
                foreach($row as &$value)
                    $value = iconv('UTF-8', 'ISO-8859-15//TRANSLIT', $value);
                fputcsv($FH, $row);
            }
        else
            foreach ($rowsList as $row)
                fputcsv($FH, $row);

        fclose($FH);
        if (!Files::csv2Xlsx($csv, $xlsx))
            return false;

        \File::delete($csv);
        return $xlsx;
    }
    static function copyFile(string $inputfile, string $outputfile) {
        $fs  = new Filesystem;
        return $fs->copy($inputfile, $outputfile); //return true or false
    }
    static function delete($file) { //either string or array
        $fs  = new Filesystem;
        return $fs->delete($file); //return true or false
    }
    static function convert(string $inputfile, string $outputfile, string $params = '') {
        $result_code = 0;
        $output      = [];
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );
        $command = '/usr/bin/convert '.$inputfile.' '.$params.' '.$outputfile;
        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            $errorMsg = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $result_code = proc_close($process);
            if ($result_code != 0) {
                \Log::warning('Failed to convert; result_code='.$result_code.', command=>'.$command.'< stderr= >'.$errorMsg.'< stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            return true;
        }
        return false;
    }
    static function convertJStoDataURLToJpeg(string &$data, string $dst, $finfo_desc = null, string $sha256 = null) {
        $result = false;
        if ($sha256)
            if (hash('sha256', $data) != $sha256) {
                \Log::warning('Invalid data signature ! stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
        $imgData = str_replace(' ','+', $data);
        $imgData = substr($imgData,strpos($imgData,",")+1);
        $imgData = base64_decode($imgData);
        // Path where the image is going to be saved
        $file = false;
        // Write $imgData into the image file
        if (($file = fopen($dst, 'w')) === false) {
            \Log::warning('Impossible to open the file >'.$dst.'< ! stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return $result;
        }
        if (fwrite($file, $imgData)=== false) {
            \Log::warning('Could not write down the file >'.$dst.'<! stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return $result;
        }
        fclose($file);
        if ($finfo_desc == null) {
            $finfo_desc = finfo_open(FILEINFO_MIME_TYPE);
            if (finfo_file($finfo_desc, $dst) != 'image/jpeg')
                \Log::warning('Invalid file type received; expected >image/jpeg<, received >'.finfo_file($finfo_desc, $dst).'<! stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            else
                $result = true;
            finfo_close($finfo_desc);
        }else{
            if (finfo_file($finfo_desc, $dst) != 'image/jpeg')
                \Log::warning('Invalid file type received; expected >image/jpeg<, received >'.finfo_file($finfo_desc, $dst).'<! stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            else
                $result = true;
        }
        return $result;
    }
}
