<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use App\Helpers\Files;
use Illuminate\Filesystem\Filesystem;


class Paths {
    /*static function clientPesee(string $subDir = '', bool $create = true) {
        $path = \Storage::disk('client_pesee')->path($subDir);
        if (!file_exists($path)) {
            $file = new Filesystem;
            $file->makeDirectory($path, 0755, true);
        }
        return $path;
    }*/
    static function bilanTemplate(int $integrateur_id) {
        return \Storage::disk('public')->path('bilan_model/'.$integrateur_id.'/bilan.html');
    }
    static function attestationTemplate(int $integrateur_id) {
        return \Storage::disk('public')->path('attestation_model/'.$integrateur_id.'/attestation_valorisation_dechet.html');
    }
    static function dacTemplate(int $integrateur_id) {
        return \Storage::disk('public')->path('integrateur_document/'.$integrateur_id.'/dac.pdf');
    }
    static function clientEnlevement(string $subDir = '', bool $create = false) {
        $path = \Storage::disk('client_enlevement')->path($subDir);
        if (!file_exists($path) && $create) {
            $file = new Filesystem;
            $file->makeDirectory($path, 0755, true);
        }
        return $path;
    }
    static function clientDocuments(string $subDir = '', bool $create = false) {
        $path = \Storage::disk('client_document')->path($subDir);
        if (!file_exists($path) && $create) {
            $file = new Filesystem;
            if (!$file->makeDirectory($path, 0755, true)) return false;
        }
        return $path;
    }
    static function integrateurDechets(int $integrateur_id) {
        return \Storage::disk('dechet')->path($integrateur_id);
    }
    static function integrateurDocuments(string $subDir = '', bool $create = false) {
        $path = \Storage::disk('integrateur_document')->path($subDir);
        if (!file_exists($path) && $create) {
            $file = new Filesystem;
            if (!$file->makeDirectory($path, 0755, true)) return false;
        }
        return $path;
    }
    static function newIntegrateurDocument(string $subDir = '', string $extension = 'pdf', bool $createDir = true) {
        $path = Paths::integrateurDocuments($subDir, $createDir);
        if ($path === false) return false;
        $filepath = Files::uniqFilename($extension, $path);
        return $path.'/'.$filepath;
    }
    static function newClientDocument(string $subDir = '', string $extension = 'pdf', bool $createDir = true) {
        $path = Paths::clientDocuments($subDir, $createDir);
        if ($path === false) return false;
        $filepath = Files::uniqFilename($extension, $path);
        return $path.'/'.$filepath;
    }
    static function newTemporaryDocument(string $userId = '', string $extension = 'pdf', bool $createDir = true) {
        $path = \Storage::disk('users_tmp')->path($userId);
        if (!file_exists($path) && $createDir) {
            $file = new Filesystem;
            if (!$file->makeDirectory($path, 0755, true)) return false;
        }
        $filepath = Files::uniqFilename($extension, $path);
        return $path.'/'.$filepath;
    }
}
