<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Helpers\Upload;
use Illuminate\Filesystem\Filesystem;

trait IorModelTraits {

    public function look(string $pattern, string $fieldName = null) {
        $className = get_class($this);
        if (empty($fieldName) && property_exists($className, 'defaultLook'))
            $fieldName = $this->defaultLook;
        elseif(empty($fieldName))
            $fieldName = 'nom';

        if (!in_array($fieldName, $this->fillable) || empty($fieldName)) {
            \Log::warning('Asked to look for a field that is not allowed! field='.$fieldName.'; stack: '.(new \Exception)->getTraceAsString(), \App\Helpers\Context::getContext());
            return false;
        }
        return call_user_func([$className, 'where'], $fieldName,'LIKE', '%'.$pattern.'%')->get();
    }
}
