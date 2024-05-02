<?php

namespace App\View\Components\common;

use Illuminate\View\Component;

class AccountLoginLayout extends Component
{

    public $target;
    public $path;
    public $loggedButNoPermission;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($target, $path, $loggedButNopermission = false)
    {
        $this->target = $target;
        $this->path   = $path;
        $this->loggedButNoPermission   = $loggedButNoPermission;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.common.account-login-layout');
    }
}
