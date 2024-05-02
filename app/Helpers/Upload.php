<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use Illuminate\Filesystem\Filesystem;

class Upload {
    static public function saveUpload(Request &$request, string $disk, string $path, string $expectedMimeType = 'application/pdf', string $attribut = 'file') {
        if (!$request->has($attribut) || \is_null($request->file($attribut))) {
            \Log::warning('No >'.$attribut.'< provided! ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        $path = \Storage::disk($disk)->path('/').$request->file('file')->store($path, $disk);
        if (mime_content_type($path) != $expectedMimeType)                    {
            \Log::warning('No >'.$expectedMimeType.'< file provided! Detected type='.mime_content_type($path).' ; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            $fs = new Filesystem;
            $fs->delete($path);
            return false;
        }
        return $path;
    }
}
