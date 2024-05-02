<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Pdf;
use Illuminate\Filesystem\Filesystem;

class DocumentController extends Controller
{
    public function create(Request &$request, int $dechet_id) {
        $path      = '';
        $fdf       = '';
        $fdfFields = [];
        $session        = $request->session()->get('triethic');
        $user_id        = $session['user']['id'];
        $integrateur_id = $session['integrateurs'][0];
        $document       = new Document;
        $fs             = new Filesystem;

        $request->validate($document->getFillableValidators());
        $fillable = $request->only($document->getFillable());
        $dest = \Storage::disk('dechetmodel')->path($integrateur_id);

        unset($fillable['document']);
        unset($fillable['file']);
        try {

            $pdf = Pdf::fromUpload($request, 'dechetmodel', $integrateur_id);
            if ($pdf === false ) return response()->json(['status' => false, 'message' => '', 'result' => ''], 200);
            $fillable['document'] = \str_replace(\Storage::disk('dechetmodel')->path(''), '', $pdf->path());

            return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $document->store($fillable, $dechet_id), 'fields' => array_keys($pdf->fields())]], 200);
        } finally {
            if (!empty($path)) $fs->delete($path);
            if (!empty($fdf))  $fs->delete($fdf);
        }
    }
    public function view(Request &$request, int $document_id) {
        $session = $request->session()->get('triethic');
        $document = Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                            ->where('dechets.integrateur_id', $session['integrateurs'][0])
                            ->where('documents.id', $document_id)
                            ->first();
        return response()->json(['status' => true, 'message' => '', 'result' => ['document' => $document, 'fields' => Pdf::fromPath($document->path())->fields(true)]], 200);
    }
    public function delete(Request &$request, int $document_id) {
        $fs       = new Filesystem;
        $session  = $request->session()->get('triethic');
        $document = Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                            ->where('dechets.integrateur_id', $session['integrateurs'][0])
                            ->where('documents.id', $document_id)
                            ->select('documents.*')
                            ->first();

        $fs->delete(\Storage::disk('dechetmodel')->path($document->document));
        $document->delete();
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function update(Request &$request, int $document_id) {
        $doc = new Document;
        $request->validate($doc->getFillableValidators(false));
        $fillable = $request->only($doc->getFillable());

        Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                ->where('dechets.integrateur_id', $session['integrateurs'][0])
                ->where('documents.id', $document_id)
                ->update($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                                                                                        ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                        ->select('documents.*')
                                                                                        ->get()], 200);
    }
    public function dechetList(Request &$request, int $dechet_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Document::join('dechets', 'dechets.id', '=', 'documents.dechet_id')
                                                                                        ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                        ->where('dechets.id', $dechet_id)
                                                                                        ->select('documents.*')
                                                                                        ->get()], 200);
    }
}
