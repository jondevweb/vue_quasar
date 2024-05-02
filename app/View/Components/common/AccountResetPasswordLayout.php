<?php

namespace App\View\Components\common;

use Illuminate\View\Component;

class AccountResetPasswordLayout extends Component
{

    public $target;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.common.account-reset-password-layout');
    }
}
