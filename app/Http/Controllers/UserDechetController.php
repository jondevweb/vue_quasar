<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Dechet;
use App\Models\Document;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Helpers\Paths;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\Files;
use App\Helpers\HZip;

class UserDechetController extends Controller
{
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::whereIn('integrateur_id', $session['integrateurs'])->select(['*'])->get()], 200);
    }
}
