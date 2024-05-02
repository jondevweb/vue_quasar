<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Dechet;
use App\Helpers\Paths;
use App\Helpers\Files;
use Illuminate\Filesystem\Filesystem;

class DechetController extends Controller
{

    public function create(Request &$request) {
        $session  = $request->session()->get('triethic');
        $user_id        = $session['user']['id'];
        $integrateur_id = $session['integrateurs'][0];
        $dest           = \Storage::disk('dechet')->path($integrateur_id);
        $dechet  = new Dechet;
        $request->validate($dechet->getFillableValidators());
        $fillable = $request->only($dechet->getFillable());
        $fillable['integrateur_id'] = $session['integrateurs'][0];

        unset($fillable['photo']);
        unset($fillable['file']);

        $fs = new Filesystem;
        if (!file_exists($dest))
          if (! $fs->makeDirectory($dest, intval('0755', 8), true, true)) {
            \Log::warning('Impossible to create the required directory; dest='.json_encode($dest).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 500);
          }


        if ($request->has('file') && !\is_null($request->file('file'))) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $path     = $request->file('file')->store($user_id, 'users_tmp');
            $filename = substr($path    , strpos($path, '/')+1);
            $basename = substr($filename, 0                   , strpos($filename, '.'));
            $source   = \Storage::disk('users_tmp')->path($path);
            $type  = finfo_file($finfo, $source);

            if (strpos($type, 'image/') != 0 )
                return response()->json(['status' => false, 'message' => 'wrong file format for the image', 'result' => ''], 400);

            if ($type == "image/svg+xml") {
                rename($source, $dest.'/'.$basename.'.svg');
                $fillable['photo'] = $integrateur_id.'/'.$basename.'.svg';
            } else {
                $img = \Image::make(\Storage::disk('users_tmp')->path($path));
                $img = $img->save($dest.'/'.$basename.'.jpg', 80);
                $fs->delete(\Storage::disk('users_tmp')->path($path));
                $fillable['photo'] = $integrateur_id.'/'.$basename.'.jpg';
            }
        }

        $dechet  = Dechet::create($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' => ['id' => $dechet->id]], 200);
    }
    public function view(Request &$request, int $dechet_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::where('integrateur_id', $session['integrateurs'][0])
                                                                                      ->where('id', $dechet_id)
                                                                                      ->first()], 200);
    }
    public function sortPosterDownload(Request &$request, int $dechet_id) {
        return $this->posterDownload($request, $dechet_id, 'affiche_tri');
    }
    public function commPosterDownload(Request &$request, int $dechet_id) {
        return $this->posterDownload($request, $dechet_id, 'affiche_communication');
    }
    public function valorisationPosterDownload(Request &$request, int $dechet_id) {
        return $this->posterDownload($request, $dechet_id, 'affiche_valorisation');
    }
    private function posterDownload(Request &$request, int $dechet_id, string $field) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];

        $result = DB::table('dechets')
                    ->where('integrateur_id', $integrateur_id)
                    ->where('id', $dechet_id)
                    ->get();

        if ($result->count() == 0) {
            \Log::warning('Someone attempted to retrieve data that is not his; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $path = \Storage::disk('dechet')->path($result[0]->$field);
        return response()->download($path, $field.'.pdf');
    }
    /**
     *
     *
     * to keep synchronized with UserDocumentController::downloadImage
     */
    public function update(Request &$request, int $dechet_id) {
        $session  = $request->session()->get('triethic');
        $user_id        = $session['user']['id'];
        $integrateur_id = $session['integrateurs'][0];
        $dechet = new Dechet;
        $request->validate($dechet->getFillableValidators(false));
        $fillable = $request->only($dechet->getFillable());
        $fillable['integrateur_id'] = $session['integrateurs'][0];

        unset($fillable['file']);
        unset($fillable['photo']);
        $fs = new Filesystem;
        $dest = \Storage::disk('dechet')->path($integrateur_id);
        $fs->makeDirectory($dest, intval('0755', 8), true, true);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach([['field'=>'affiche_tri', 'file' => '-affiche-tri'],
                 ['field'=>'affiche_communication', 'file' => '-affiche-bac'],
                 ['field'=>'affiche_valorisation', 'file' => '-affiche-valorisation']
                ] AS $value) {
            unset($fillable[$value['field']]);
            $field = $value['field'].'-pdf';
            if ($request->has($field) && !\is_null($request->file($field))) {
                $path     = $request->file($field)->store($user_id, 'users_tmp');
                $basename = $dechet_id.$value['file'];
                $source   = \Storage::disk('users_tmp')->path($path);
                $type  = finfo_file($finfo, $source);
                if ($type != 'application/pdf') {
                    $message = 'wrong file format for a PDF';
                    \Log::warning($message.'; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                    return response()->json(['status' => false, 'message' => $message , 'result' => ''], 400);
                }
                if (!Files::convert($source, $dest.'/'.$basename.'.webp', '-quality 100')) {
                    $message = 'could not convert the pdf to webp';
                    \Log::warning($message.'; session='.json_encode($session).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                    return response()->json(['status' => false, 'message' => $message, 'result' => ''], 400);
                }
                rename($source, $dest.'/'.$basename.'.pdf');
                $fillable[$value['field']] = $integrateur_id.'/'.$basename.'.pdf';
            }
        }
        foreach(['equivalence_photo'] AS $value) {
            unset($fillable[$value]);
            $field = $value.'-webp';
            if ($request->has($field) && !\is_null($request->file($field))) {
                $path     = $request->file($field)->store($user_id, 'users_tmp');
                $filename = substr($path    , strpos($path, '/')+1);
                $basename = substr($filename, 0                   , strpos($filename, '.'));
                $source   = \Storage::disk('users_tmp')->path($path);
                $type  = finfo_file($finfo, $source);
                if ($type != 'image/webp')
                    return response()->json(['status' => false, 'message' => 'wrong file format for the image (expected webp)', 'result' => ''], 400);

                rename($source, $dest.'/'.$basename.'.webp');
                $fillable[$value] = $integrateur_id.'/'.$basename.'.webp';
            }
        }
        if ($request->has('file') && !\is_null($request->file('file'))) {
            $path     = $request->file('file')->store($user_id, 'users_tmp');
            $filename = substr($path    , strpos($path, '/')+1);
            $basename = substr($filename, 0                   , strpos($filename, '.'));
            $source   = \Storage::disk('users_tmp')->path($path);
            $type  = finfo_file($finfo, $source);

            if (strpos($type, 'image/') != 0 )
                return response()->json(['status' => false, 'message' => 'wrong file format for the image', 'result' => ''], 400);

            if ($type == "image/svg+xml") {
                rename($source, $dest.'/'.$basename.'.svg');
                $fillable['photo'] = $integrateur_id.'/'.$basename.'.svg';
            } else {
                $img = \Image::make(\Storage::disk('users_tmp')->path($path));
                $img = $img->save($dest.'/'.$basename.'.jpg', 80);
                $fs->delete(\Storage::disk('users_tmp')->path($path));
                $fillable['photo'] = $integrateur_id.'/'.$basename.'.jpg';
            }
        }

        Dechet::where('integrateur_id', $session['integrateurs'][0])->where('id', $dechet_id)->update($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' => ['photo' => isset($fillable['photo']) ? $fillable['photo'] : '']], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        if ($request->has('InStock') && $request->get('InStock')) {
            $sql = <<<END
            SELECT d.*, (T.dechet_id IS NOT NULL) AS en_stock
            FROM dechets d
            LEFT JOIN (
              SELECT DISTINCT dechet_id
              FROM collectes C
              WHERE C.statut BETWEEN 50 AND 89
            ) T ON T.dechet_id = d.id
            WHERE d.integrateur_id = ?
            ORDER BY d.nom
            END;
            return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$session['integrateurs'][0]])], 200);
        }
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::where('integrateur_id', $session['integrateurs'][0])
                                                                                      ->orderBy('nom')
                                                                                      ->get()], 200);
    }
    public function exutoireList(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Dechet::join('dechet_exutoire', 'dechets.id', '=', 'dechet_exutoire.dechet_id')
                                                                                      ->where('dechets.integrateur_id', $session['integrateurs'][0])
                                                                                      ->where('dechet_exutoire.active', 1)
                                                                                      ->select(['dechet_exutoire.dechet_id', 'dechet_exutoire.exutoire_id'])
                                                                                      ->get()], 200);
    }
}
