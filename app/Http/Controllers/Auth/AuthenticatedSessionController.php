<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // $request->authenticate();

        $login_api = Http::post("https://loantracker.oicapp.com/api/v1/login", [
            'company_id' => $request->input('email'),
            'password' => $request->input("password")
        ]);


        $user = $login_api->json();

        if (!isset($user['token']) || (isset($user['message']))) {

            throw ValidationException::withMessages([
                'email' => $user['message']
            ]);
        }


        $request->session()->put('token', $user['token']);
        
        return redirect()->route('dashboard');

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $token = session('token');
        Http::withToken($token)->post("https://loantracker.oicapp.com/api/v1/logout");
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
