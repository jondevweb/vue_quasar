<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
/*
# id, nom, public
'100', 'autre', '1'
'200', 'présence', '1'
'201', 'connexion integrateur', '1'
'202', 'connexion apporteur', '1'
'203', 'connexion groupe apporteur', '1'
'204', 'connexion client', '1'
'205', 'connexion integrateur comme client', '1'
'206', 'rafraîchissement connexion', '1'
'210', 'déconnexion', '1'
'211', 'déconnexion integrateur', '1'
'212', 'déconnexion apporteur', '1'
'213', 'déconnexion groupe apporteur', '1'
'214', 'déconnexion client', '1'
'220', 'demande reset de mdp', '1'
'221', 'suite à reset mdp changé', '1'
'230', 'migration de compte', '1'
'300', 'collecte', '1'
'301', 'client', '1'
'302', 'apporteur', '1'
'303', 'groupe apporteur', '1'
'304', 'intégrateur', '1'
'400', 'bordereau', '1'
'401', 'téléchargement', '1'
'500', 'spécifique intégrateur', '1'
'501', 'téléversement logo', '1'
'502', 'téléversement pied de page', '1'
'503', 'téléversement cachet', '1'
'504', 'téléversement signature', '1'
'505', 'téléversement dechet', '1'
'600', 'demande de collecte', '1'
'650', 'génération bilan réussie', '1'
'651', 'génération bilan échouée', '1'
'652', 'génération bilan démarrée', '1'
'653', 'génération bilan terminée', '1'
*/
abstract class HistoriqueTypes {
    private int $specificValue;
    private int $infValue;
    private int $supValue;
    protected function __construct(int $infValue, int $supValue, int $specificValue = PHP_INT_MIN) {
        $this->specificValue = $specificValue;
        $this->infValue      = $infValue;
        $this->supValue      = $supValue;
    }
    public function getInf() : int {if ($this->specificValue != PHP_INT_MIN) return $this->specificValue; else return $this->infValue;}
    public function getSup() : int {if ($this->specificValue != PHP_INT_MIN) return $this->specificValue; else return $this->supValue;}
}

class HistoriqueBilan extends HistoriqueTypes {
    public function __construct() {
        parent::__construct(650, 659);
    }
}
class Historiquetype extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom',
        'public',
    ];
    public function getFillableValidators(bool $withRequired = true, $except = null) {
        $retour = ['public' => 'boolean', 'nom' => 'required|max:255'];

        if (!$withRequired)
            $retour = ['public' => 'boolean', 'nom' => 'max:255'];

        if ($except == null) $except = [];
        if (! is_array($except))
            $except = explode(',', $except);
        foreach($except AS &$value)
            unset($retour[$value]);

        return $retour;
    }
    public function store(array &$fillable) {
        return DB::transaction(function ()   use (&$fillable) {
            $historiquetype = Historiquetype::create($fillable);
            return $historiquetype->id;
        });
    }
    public const BILAN = 1;
    static public function getType(int $type = null) {
        switch($type) {
            case Historiquetype::BILAN: return new HistoriqueBilan();break;
            default: return null;
        }
    }
}
