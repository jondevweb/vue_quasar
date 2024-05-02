<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;

class User {
    public static function currentLoggedToString() {
        $user = Auth::user();
        if ($user)
            return ['user_id'=> $user->id, 'mail' =>$user->email];
        return [];
    }
}
