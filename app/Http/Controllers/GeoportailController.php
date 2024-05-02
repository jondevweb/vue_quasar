<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeoportailController extends Controller
{
    public function simpleMap(Request &$request, float $latitude, float $longitude) {
        return view('geoportail.simpleMap', ['latitude' => $latitude, 'longitude' => $longitude]);
    }
}
