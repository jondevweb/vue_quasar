<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;

class IorTraits {
    trait Lookable {

        public function look(string $pattern, string $fieldName) {
            if (!in_array($fieldName, $this->fillable)) {
                \Log::warning('Asked to look for a field that is not allowed! field='.$fieldName.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
                return false;
            }
            $className = get_class($this);
            return call_user_func($className, 'where', $fieldName,'LIKE', '%'.$pattern.'%')->get();
            //return Pointcollecte::where($fieldName,'LIKE', '%'.$pattern.'%')->get();
        }
    }
}