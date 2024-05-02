<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use App\Models\User;

class WebClientController extends Controller
{
    public function root(Request &$request) {
        if (!Auth::check())
            return view('layouts.clients.account-login', ['path' => '']);

        if (!$request->session()->get('triethic')['roles']->containsStrict('client')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return view('layouts.clients.account-login', ['path' => '', 'loggedButNoPermission' => 'true']);
        }
        return view('layouts.clients.welcome', ['path' => '']);
    }
    public function path(Request &$request, string $path) {
        if (!Auth::check())
            return view('layouts.clients.account-login', ['path' => $path]);
        return view('layouts.clients.welcome', ['path' => $path]);
    }
    public function recup(Request &$request, string $token) {
        return view('layouts.clients.account-reset-password', ['token' => $token]);
    }
    public function migration(Request &$request) {
        $request->validate(['email' => 'required|email', 'migration_token' => 'required']);
        $account = new AccountController;
        if ($account->logMigrated($request, $request->get('email'), $request->get('migration_token')) == false)
            return response()->json(['status' => false, 'message' => '', 'result' => ''], 401);

        return redirect(url('/clients'));
    }
}
