<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;

class Context {
    public static function getContext() {
        return [
            'user' => \App\Helpers\User::currentLoggedToString(),
            'debug_backtrace' => debug_backtrace()
        ];
    }
}
