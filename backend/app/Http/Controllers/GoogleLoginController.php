<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * @return RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::query()->where('email', $googleUser->email)->first();

            if($user){
                Auth::login($user);
                return redirect()->to(config('app.frontend_url'));
            } else {
                return redirect()->to(config('app.frontend_url') . 'login')->withErrors('Sorry, you are not registered in our system.');
            }
        } catch (\Exception $e) {
            return redirect()->to(config('app.frontend_url') . 'login')->withErrors('Something went wrong or you have denied the request');
        }
    }
}
