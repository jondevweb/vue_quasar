<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as LDB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
class DB {
    static public function prefixesKeys(string $prefix, array $array) {
        $retour = [];
        foreach($array AS $key => &$value)
            $retour[$prefix.$key] = $value;
        return $retour;
    }
    static public function getColumnsForSelect(Model &$model, string $prefix = '') {
        if ($prefix == '')
            $prefix = $model->getTable();

        return array_reduce(array_keys($model->getFillableValidators()), function(&$acc, &$value) use(&$prefix){
            array_push($acc, $prefix.'.'.$value.' AS '.$prefix.'|'.$value);
            return $acc;
        }, [$prefix.'.id AS '.$prefix.'|id']);
    }
    static function getColumnsOfSelect(Model &$model, string $prefix = '') {
        if ($prefix == '')
            $prefix = $model->getTable();

        return array_reduce(array_keys($model->getFillableValidators()), function(&$acc, &$value) use(&$prefix){
            $acc[$value] = $prefix.'|'.$value;
            return $acc;
        }, ['id' => $prefix.'|id']);
    }
    /**
     *
     * @param $mapping [ 0 => 'class principale', 'nom attribut' => ['instance' => 'sous element', 'prefix' => '']]
     *
     * ['pointcollecte' => ['instance' => new Pointcollecte]]
     * ou
     * ['pointcollecte' => ['instance' => new Pointcollecte, 'prefix' => 'WHATEVER']]
     */
    static public function convertToObject(Collection &$collection, Model &$mainModel, array $mapping, string $mainPrefix = '') {
        if ($mainPrefix == '')
            $mainPrefix = $mainModel->getTable();

        $mainReflexion = new \ReflectionClass($mainModel);//)->newInstance();
        $mainColumns   = DB::getColumnsOfSelect($mainModel, $mainPrefix);
        foreach($mapping AS $key => &$value) {
            if (!isset($value['prefix'])) $value['prefix'] = $value['instance']->getTable();
            $value['reflexion'] = new \ReflectionClass($value['instance']);
            $value['columns']   = DB::getColumnsOfSelect($value['instance'], $value['prefix']);
            $value['name']      = $key;
        }
        //$columns = DB::getColumnsOfSelect($model, $prefix);
        $finalCollection = $collection->reduce(function(&$acc, &$value) use(&$mapping, &$mainReflexion, $mainColumns){ // pas optimal
            $tmp = [];
            foreach($mainColumns AS $key => &$column) {
                $tmp[$key] = $value->$column;
            }
            $mainObject = $mainReflexion->newInstance();
            $mainObject->forceFill($tmp) ;
            foreach($mapping AS &$valueMapping) {
                $tmp = [];
                foreach($valueMapping['columns'] AS $key => &$column) {
                    $tmp[$key] = $value->$column;
                }
                $mainObject->{$valueMapping['name']} = $valueMapping['reflexion']->newInstance();
                $mainObject->{$valueMapping['name']}->forceFill($tmp) ;
            }
            array_push($acc, $mainObject);
            return $acc;
        }, []);
        return $finalCollection;
    }
}
