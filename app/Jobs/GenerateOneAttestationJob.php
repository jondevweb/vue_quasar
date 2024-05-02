<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use App\Models\Attestation;
use App\Helpers\Paths;


class GenerateOneAttestationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $integrateur_id;
    private int $pointcollecte_id;
    private int $year;

    public function __construct(int $integrateur_id, int $pointcollecte_id, int $year)
    {
        $this->integrateur_id   = $integrateur_id;
        $this->pointcollecte_id = $pointcollecte_id;
        $this->year             = $year;
    }

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }
        $result = Attestation::generateMultiple($this->integrateur_id, [$this->pointcollecte_id], Paths::attestationTemplate($this->integrateur_id), Paths::clientDocuments(), $this->year);
        if ($result['failed'] != 0)
            throw new \Exception ('The job failed');
    }
}
