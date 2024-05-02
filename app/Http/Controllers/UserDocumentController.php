<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Helpers\Paths;

class UserDocumentController extends Controller
{
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                                                                                        ->whereIn('dechets.integrateur_id', $session['integrateurs'])
                                                                                        ->select(['documents.id', 'documents.nom'])
                                                                                        ->get()], 200);
    }
    private function downloadPrint(Request &$request, int $dechet_id, string $type) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $result = DB::table('dechets')
                    ->where('id', '=', $dechet_id)
                    ->select('nom')
                    ->get();

        if (count($result) == 0) {
            \Log::warning('Someone attempted to retrieve a non existant waste; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $nom_fichier = $result[0]->nom.'-'.$type.'.pdf';
        $filepath = '';
        switch($type) {
            case 'affiche-bac':          $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-bac.pdf';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-bac.pdf', $nom_fichier);
            case 'affiche-tri':          $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-tri.pdf';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-tri.pdf', $nom_fichier);
            case 'affiche-valorisation': $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-valorisation.pdf';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-valorisation.pdf', $nom_fichier);
        }
        if (! $filepath) {
            \Log::warning('Wrong type of print asked; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->file($filepath, ['filename'=>$nom_fichier]);
    }
    /**
     *
     *
     * to keep synchronized with DechetController::update
     */
    private function downloadImage(Request &$request, int $dechet_id, string $type) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $result = DB::table('dechets')
                    ->where('id', '=', $dechet_id)
                    ->select('nom')
                    ->get();

        if (count($result) == 0) {
            \Log::warning('Someone attempted to retrieve a non existant waste; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $nom_fichier = $result[0]->nom.'-'.$type.'.webp';
        $filepath = '';
        switch($type) {
            case 'affiche-bac':          $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-bac.webp';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-bac.pdf', $nom_fichier);
            case 'affiche-tri':          $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-tri.webp';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-tri.pdf', $nom_fichier);
            case 'affiche-valorisation': $filepath = Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-valorisation.webp';break;//return response()->download(Paths::integrateurDechets($integrateur_id).'/'.$dechet_id.'-affiche-valorisation.pdf', $nom_fichier);
        }
        if (! $filepath) {
            \Log::warning('Wrong type of print asked; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        return response()->file($filepath, ['filename'=>$nom_fichier]);
    }
    public function downloadCommPrint(Request &$request, $dechet_id) {
        return $this->downloadPrint($request, $dechet_id, 'affiche-bac');
    }
    public function downloadCommImage(Request &$request, $dechet_id) {
        return $this->downloadImage($request, $dechet_id, 'affiche-bac');
    }
    public function downloadSortPrint(Request &$request, $dechet_id) {
        return $this->downloadPrint($request, $dechet_id, 'affiche-tri');
    }
    public function downloadSortImage(Request &$request, $dechet_id) {
        return $this->downloadImage($request, $dechet_id, 'affiche-tri');
    }
    public function downloadValuePrint(Request &$request, $dechet_id) {
        return $this->downloadPrint($request, $dechet_id, 'affiche-valorisation');
    }
    public function downloadValueImage(Request &$request, $dechet_id) {
        return $this->downloadImage($request, $dechet_id, 'affiche-valorisation');
    }
}
