<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Ids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid username or password.'], 422);
        }
        $request->session()->regenerate();

        return response()->json(['user' => $request->user()]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'university' => ['nullable', 'string', Rule::exists('universities', 'name')],
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'id' => Ids::make('u'), 'username' => $data['username'], 'password' => Hash::make($data['password']),
                'role' => 'user', 'rating' => 1200, 'university' => $data['university'] ?? null, 'rank' => 'Newbie', 'created_at' => now(),
            ]);
            DB::table('activity_logs')->insert(['user_id' => $user->id, 'action' => 'auth.register', 'details' => 'Account created', 'created_at' => now()]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user], 201);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
