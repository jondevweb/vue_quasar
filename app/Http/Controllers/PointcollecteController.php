<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Pointcollecte;
use App\Models\MobilierPointcollecte;
use App\Models\Mobilier;

class PointcollecteController extends Controller
{
    public function list(Request &$request) {
        $allowedFields = ['annee' => 'int'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $annee = date("Y");
        if ($request->has('annee'))
            $annee = intval($request->get('annee'));

        $sql = <<<EOM
          SELECT p.id, p.nom, p.adresse, p.client_id, c.code_trackdechet <> '' AS code_trackdechet, CONCAT('[', GROUP_CONCAT(IF(c2.dechet_id IS NULL, '', c2.dechet_id)), ']') AS caps_ok, cc.annee
          FROM pointcollectes p
          INNER JOIN clients    c  ON c.id         = p.client_id
          LEFT JOIN cap_client  cc ON cc.client_id = p.client_id AND cc.annee = ? AND cc.statut >= 10
          LEFT JOIN caps        c2 ON c2.id        = cc.cap_id
          WHERE c.integrateur_id = ? /*AND (annee = ? OR annee IS NULL)*/
          GROUP BY p.id
        EOM;

        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$annee, $integrateur_id])], 200);
    }
    public function bilans(Request &$request) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];

        $sql = <<<EOM
        SELECT P.id, P.nom, P.adresse, GROUP_CONCAT(B.annee) AS annees
        FROM pointcollectes P
        INNER JOIN bilans   B ON B.pointcollecte_id = P.id
        INNER JOIN clients  C ON P.client_id        = C.id AND C.integrateur_id = ?
        GROUP BY P.id
        ORDER BY P.nom
        EOM;

        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$integrateur_id])], 200);
    }
    public function pointcollecteBilans(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        $sql = <<<EOM
        SELECT B.*, IF(A.annee IS NOT NULL, 1, 0) as collectes_presentes
        FROM bilans  B
        LEFT JOIN (
            SELECT DISTINCT YEAR(P.date_debut) AS annee
            FROM passages P
            WHERE P.pointcollecte_id = ?
        ) A ON A.annee = B.annee
        WHERE B.pointcollecte_id = ?
        EOM;

        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$pointcollecte_id, $pointcollecte_id])], 200);
    }
    public function attestations(Request &$request) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];

        $sql = <<<EOM
        SELECT P.id, P.nom, P.adresse, GROUP_CONCAT(B.annee) AS annees
        FROM pointcollectes P
        INNER JOIN attestations  B ON B.pointcollecte_id = P.id
        INNER JOIN clients       C ON P.client_id        = C.id AND C.integrateur_id = ?
        GROUP BY P.id
        ORDER BY P.nom
        EOM;

        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$integrateur_id])], 200);
    }
    public function pointcollecteAttestations(Request &$request, int $pointcollecte_id) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        $sql = <<<EOM
        SELECT B.*, IF(A.annee IS NOT NULL, 1, 0) as collectes_presentes
        FROM attestations  B
        LEFT JOIN (
            SELECT DISTINCT YEAR(P.date_debut) AS annee
            FROM passages P
            WHERE P.pointcollecte_id = ?
        ) A ON A.annee = B.annee
        WHERE B.pointcollecte_id = ?
        EOM;

        return response()->json(['status' => true, 'message' => '', 'result' => DB::select($sql,[$pointcollecte_id, $pointcollecte_id])], 200);
    }
    public function listCap(Request &$request, int $pointcollecte_id) {
        $allowedFields = ['annee' => 'int'];
        $request->validate($allowedFields);
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $annee = date("Y");
        if ($request->has('annee'))
            $annee = intval($request->get('annee'));

        $result = Pointcollecte::join('cap_client AS CC', 'CC.client_id', 'pointcollectes.client_id')
                               ->join('caps       AS C' , 'C.id'        , 'CC.cap_id')
                               ->join('dechets    AS D' , 'D.id'        , 'C.dechet_id')
                               ->where('D.integrateur_id' , $integrateur_id)
                               ->where('CC.annee'         , $annee)
                               ->where('pointcollectes.id', $pointcollecte_id)
                               ->select(['C.dechet_id', 'CC.annee', 'pointcollectes.id AS pointcollecte_id', 'CC.client_id',  'CC.cap_id'])
                               ->get();
        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
}
