<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FacebookService;
use App\Models\User;

class FacebookController extends Controller
{
    protected $facebookService;

    public function __construct(FacebookService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    public function redirectToProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleProviderCallback()
    {
        try {
            $user = Socialite::driver('facebook')->user();

            $existingUser = User::where('facebook_id', $user->id)->first();

            if ($existingUser) {
                Auth::login($existingUser);
            } else {
                $newUser = new User;
                $newUser->name = $user->name;
                $newUser->email = $user->email;
                $newUser->facebook_id = $user->id;
                $newUser->access_token = $user->token;
                $newUser->save();

                Auth::login($newUser);
            }

            return redirect()->route('home');
        } catch (Exception $e) {
            return redirect('auth/facebook');
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $response = $this->facebookService->sendMessage(
                $request->user()->access_token,
                $request->input('recipient_id'),
                $request->input('message')
            );
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
