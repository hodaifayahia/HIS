<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class LoginController extends AuthenticatedSessionController
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'mode' => 'sometimes|in:system,portal'
        ]);

        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Store the login mode in session
            $request->session()->put('login_mode', $request->input('mode', 'system'));
            
            // Return JSON response for SPA
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'mode' => $request->input('mode', 'system'),
                'redirect' => $request->input('mode') === 'portal' ? '/portal' : '/home',
                'user' => Auth::user()
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * Handle login (alias for store method)
     */
    public function login(Request $request)
    {
        return $this->store($request);
    }

    public function logout(Request $request)
    {
        // Log the user out
        Auth::guard('web')->logout();

        // Optionally, invalidate the session and regenerate the token (for security purposes)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the login page or any other page you prefer
        return redirect('/login');
    }
}
