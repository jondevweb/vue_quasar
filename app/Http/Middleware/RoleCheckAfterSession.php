<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleCheckAfterSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $invalid = function(Request &$request, string $complement) {
            \Log::warning('RoleCheckAfterSession failed! complement=>'.$complement.'<, session='.\json_encode($request->session()->all()), \App\Helpers\Context::getContext());
            return response()->json(['status' => false, 'message' => 'Access forbidden', 'result' => []], 401);
        };

        if (!$request->session()->has('triethic')) return $invalid($request, 'contains triethic data');
        if (!isset($request->session()->get('triethic')['roles'])) return $invalid($request, 'contains roles data');
        if (!$request->session()->get('triethic')['roles']->containsStrict($role)) return $invalid($request, 'contains role='.$role);//redirect('home');

        return $next($request);
    }
}
