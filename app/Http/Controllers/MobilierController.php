<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Models\Mobilier;
use Illuminate\Filesystem\Filesystem;

class MobilierController extends Controller
{
    public function view(Request &$request, int $mobilier_id) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Mobilier::where('integrateur_id', $session['integrateurs'][0])
                                                                                        ->where('id', $mobilier_id)
                                                                                        ->first()], 200);
    }
    public function clientInventory(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => json_decode(file_get_contents(\Storage::disk('users_tmp')->path('inventaire_mobilier.json')))], 200);
    }
    public function createEntryClientInventory(Request &$request) {
        $session = $request->session()->get('triethic');
        $validators = ['pointcollecte_id' => 'required|integer'];
        $request->validate($validators);
        $pointcollecte_id = $request->get('pointcollecte_id');
        $data = json_decode(file_get_contents(\Storage::disk('users_tmp')->path('inventaire_mobilier.json')));

        $idx = count($data);
        $entry = ["id" => $idx, "pointcollecte_id" => $pointcollecte_id, "mobilier_id" => 1, "localisation" => "", "appartient_client" => true, "quantite" => 1];
        array_push($data, $entry);

        if (false === file_put_contents(\Storage::disk('users_tmp')->path('inventaire_mobilier.json'), json_encode($data)))
            return response()->json(['status' => true, 'message' => 'humpf', 'result' => ''], 400);
        return response()->json(['status' => true, 'message' => '', 'result' => $entry], 200);
    }
    public function updateClientInventory(Request &$request) {
        $session = $request->session()->get('triethic');
        $validators = ['id' => 'required|integer', 'field' => 'in:pointcollecte_id,mobilier_id,localisation,appartient_client,quantite', 'value' => 'required'];
        $request->validate($validators);
        $id    = $request->get('id');
        $field = $request->get('field');
        $data = json_decode(file_get_contents(\Storage::disk('users_tmp')->path('inventaire_mobilier.json')));
        if (!isset($data[$id]))
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 400);

        $data[$id]->$field = $request->get('value');
        if (false === file_put_contents(\Storage::disk('users_tmp')->path('inventaire_mobilier.json'), json_encode($data)))
            return response()->json(['status' => true, 'message' => 'humpf', 'result' => ''], 400);
        return response()->json(['status' => true, 'message' => '', 'result' => $data[$id]], 200);
    }
    public function update(Request &$request, int $mobilier_id) {
        $session  = $request->session()->get('triethic');
        $user_id        = $session['user']['id'];
        $integrateur_id = $session['integrateurs'][0];
        $mobilier = new Mobilier;
        $request->validate($mobilier->getFillableValidators(false));
        $fillable = $request->only($mobilier->getFillable());
        $fillable['integrateur_id'] = $session['integrateurs'][0];
        unset($fillable['file']);
        unset($fillable['photo']);

        if ($request->has('file') && !\is_null($request->file('file'))) {
            $path     = $request->file('file')->store($user_id, 'users_tmp');
            $filename = substr($path    , strpos($path, '/')+1);
            $basename = substr($filename, 0                   , strpos($filename, '.'));

            $img      = \Image::make(\Storage::disk('users_tmp')->path($path));

            $fs = new Filesystem;
            $dest = \Storage::disk('mobilier')->path($integrateur_id);
            $fs->makeDirectory($dest, intval('0755', 8), true, true);

            $img      = $img->save($dest.'/'.$basename.'.jpg', 80);
            $fs->delete(\Storage::disk('users_tmp')->path($path));

            $fillable['photo'] = $integrateur_id.'/'.$basename.'.jpg';
        }

        Mobilier::where('integrateur_id', $session['integrateurs'][0])->where('id', $mobilier_id)->update($fillable);
        return response()->json(['status' => true, 'message' => '', 'result' => ['photo' => isset($fillable['photo']) ? $fillable['photo'] : '']], 200);
    }
    public function delete(Request &$request, int $mobilier_id) {
        $session  = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        DB::unprepared('SET autocommit=0');
        DB::unprepared('LOCK TABLES mobiliers WRITE, mobilier_pointcollecte WRITE');
        $result = DB::select(DB::raw('SELECT COUNT(mobilier_pointcollecte.id) as total
                                      FROM mobilier_pointcollecte
                                      INNER JOIN mobiliers ON mobiliers.id = mobilier_pointcollecte.mobilier_id
                                      WHERE mobilier_id=? AND integrateur_id=?')
                          , [$mobilier_id, $integrateur_id])[0]->total;

        if ($result > 0)
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 200);
        $mobilier = Mobilier::find($mobilier_id);

        if ($mobilier->photo != '') {
            $fs = new Filesystem;
            $fs->delete(\Storage::disk('mobilier')->path('./').$mobilier->photo);
        }
        $mobilier->delete();

        DB::unprepared('COMMIT');
        DB::unprepared('UNLOCK TABLES');
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function create(Request &$request) {
        $session  = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $user_id        = $session['user']['id'];
        $path     = '';
        $mobilier = new Mobilier;
        $request->validate($mobilier->getFillableValidators());

        $fillable = $request->only($mobilier->getFillable());
        $fillable['integrateur_id'] = $session['integrateurs'][0];
        unset($fillable['file']);
        unset($fillable['photo']);

        if ($request->has('file') && !\is_null($request->file('file'))) {
            $path     = $request->file('file')->store($user_id, 'users_tmp');
            $filename = substr($path    , strpos($path, '/')+1);
            $basename = substr($filename, 0                   , strpos($filename, '.'));

            $img      = \Image::make(\Storage::disk('users_tmp')->path($path));

            $fs = new Filesystem;
            $dest = \Storage::disk('mobilier')->path($integrateur_id);
            $fs->makeDirectory($dest, intval('0755', 8), true, true);

            $img      = $img->save($dest.'/'.$basename.'.jpg', 80);
            $fs->delete(\Storage::disk('users_tmp')->path($path));

            $fillable['photo'] = $integrateur_id.'/'.$basename.'.jpg';
        }

        $mobilier = Mobilier::create($fillable);

        return response()->json(['status' => true, 'message' => '', 'result' => $mobilier], 200);
    }
    public function list(Request &$request) {
        $session = $request->session()->get('triethic');
        return response()->json(['status' => true, 'message' => '', 'result' => Mobilier::where('integrateur_id', $session['integrateurs'][0])
                                                                                                      ->orderBy('nom')
                                                                                                      ->get()], 200);
    }
    public function clientsList(Request &$request, int $mobilier_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $sql = <<<EOF
SELECT P.id, E.raison_sociale, P.nom, SUM(IF(MP.appartient_client = 0, MP.quantite, 0)) as total_integrateur, SUM(IF(MP.appartient_client = 1, MP.quantite, 0)) as total_point, SUM(MP.quantite) as total
FROM mobilier_pointcollecte MP
INNER JOIN pointcollectes P ON P.id = MP.pointcollecte_id AND MP.mobilier_id = ?
INNER JOIN clients        C ON C.id = P.client_id         AND C.integrateur_id = ?
INNER JOIN entreprises    E ON E.id = C.entreprise_id
GROUP BY E.id, C.id, P.id
;
EOF;
        return response()->json(['status' => true, 'message' => '', 'result' => ['mobiliers' => DB::select(DB::raw($sql), [$mobilier_id, $integrateur_id])]], 200);
    }
    public function clientList(Request &$request, int $mobilier_id, int $client_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $sql = <<<EOF
SELECT P.id, E.raison_sociale, P.nom, SUM(IF(MP.appartient_client = 0, MP.quantite, 0)) as total_integrateur, SUM(IF(MP.appartient_client = 1, MP.quantite, 0)) as total_point, SUM(MP.quantite) as total
FROM mobilier_pointcollecte MP
INNER JOIN pointcollectes P ON P.id = MP.pointcollecte_id AND MP.mobilier_id   = ? AND P.client_id = ?
INNER JOIN clients        C ON C.id = P.client_id         AND C.integrateur_id = ?
INNER JOIN entreprises    E ON E.id = C.entreprise_id
GROUP BY E.id, C.id, P.id
;
EOF;
        return response()->json(['status' => true, 'message' => '', 'result' => ['mobiliers' => DB::select(DB::raw($sql), [$mobilier_id, $client_id, $integrateur_id])]], 200);
    }
}
