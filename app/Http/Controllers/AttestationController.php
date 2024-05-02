<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Attestation;
use App\Helpers\Paths;

class AttestationController extends Controller
{
    public function generateAttestation(Request &$request, int $pointcollecte_id, int $annee) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $result = Attestation::fullyGenerateOne($integrateur_id, $pointcollecte_id, $annee);

        return response()->json(['status' => true, 'message' => '', 'result' => $result], 200);
    }
    public function generateAttestations(Request &$request, int $annee) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $allowedFields = ['email' => 'email:rfc'];
        $request->validate($allowedFields);

        Attestation::generateAllBatch($integrateur_id, $request->get('email'), $annee);
        return response()->json(['status' => true, 'message' => '', 'result' => ''], 200);
    }
    public function status(Request &$request, int $annee) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        $name = 'ATTESTATIONS-'.$integrateur_id.'-'.$annee;
        if ($annee == 0)
            $name = 'ATTESTATIONS-'.$integrateur_id.'-';
        $attestationStatus = <<<"EOF"
            SELECT total_jobs, pending_jobs, cancelled_at, failed_jobs, created_at, finished_at
            FROM job_batches jb
            WHERE INSTR(name, '$name') >= 0
            ORDER BY created_at DESC
            LIMIT 1
        EOF;
        $result = DB::select($attestationStatus);
        if (count($result) == 0 || DB::select($attestationStatus)[0]->finished_at != null)
            return response()->json(['status' => true, 'message' => '', 'result' => []], 200);
        else {
            $result = DB::select($attestationStatus)[0];
            return response()->json(['status' => true, 'message' => '', 'result' => ['total_jobs'   => $result->total_jobs,
                                                                                     'pending_jobs' => $result->pending_jobs,
                                                                                     'cancelled_at' => $result->cancelled_at,
                                                                                     'failed_jobs'  => $result->failed_jobs,
                                                                                     'created_at'   => $result->created_at,
                                                                                     'finished_at'  => $result->finished_at]
            ], 200);
        }
    }
    public function download(Request &$request, int $pointcollecte_id, int $annee) {
        $session = $request->session()->get('triethic');
        $integrateur_id = $session['integrateurs'][0];
        if (!in_array($pointcollecte_id, $session['pointcollectes'])) {
            \Log::warning('Tried to access to an account that is not his; pointcollecte_id'.$pointcollecte_id.'; session:'.\json_encode($session['clients']).'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }
        $result = DB::table('attestations')
                    ->where('pointcollecte_id', $pointcollecte_id)
                    ->where('annee', $annee)
                    ->get();

        if ($result->count() == 0) {
            \Log::warning('Someone tried to access to a non-existante document; data='.json_encode([$pointcollecte_id, $annee]), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);
        }

        return response()->download(Paths::clientDocuments($result[0]->document), $result[0]->annee.'-attestation.pdf');
    }
}
