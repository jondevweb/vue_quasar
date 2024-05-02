<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MeetmeController extends Controller
{
    public function launch(Request &$request, string $room) {
        return view('meetme.launch');
    }
}
