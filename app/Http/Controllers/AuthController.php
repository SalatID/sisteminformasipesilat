<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function login ()
    {
        return view('auth.login');
    }
    public function logout()
    {
        auth()->logout();
    
        request()->session()->invalidate();
    
        request()->session()->regenerateToken();
    
        return redirect('/');
    }
    public function procLogin()
    {
        $credentials = request()->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        if (auth()->attempt($credentials)) {
            request()->session()->regenerate();
            $user = User::find(auth()->user()->id);
            if ($user->email_verified_at == null){
                auth()->logout();
                return back()->withErrors([
                    'error'=>true,
                    'message' => 'Your email has not been activated, please check your email for activation',
                ]);
            }
            $user->last_login = date("Y-m-d H:i:s");
            $user->update();
            return redirect()->route('dashboard');
        }
 
        return back()->withErrors([
            'error'=>true,
            'message' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
