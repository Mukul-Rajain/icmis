<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:mongodb.users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:filer,registry,judge',

            // Filer-specific
            'filer_profile.type'               => 'required_if:role,filer|in:lawyer,litigant',
            'filer_profile.bar_council_number' => 'nullable|string',
            'filer_profile.years_of_practice'  => 'nullable|integer|min:0',
            'filer_profile.phone'              => 'required_if:role,filer|string',

            // Judge / Registry
            'judge_profile.court_id'           => 'required_if:role,judge|string',
            'judge_profile.designation'        => 'required_if:role,judge|string',
            'judge_profile.bench_number'       => 'required_if:role,judge|string',
            'registry_profile.court_id'        => 'required_if:role,registry|string',
            'registry_profile.employee_id'     => 'required_if:role,registry|string',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => $data['role'],
            'is_active' => true,
            'filer_profile'    => $data['filer_profile']    ?? null,
            'judge_profile'    => $data['judge_profile']    ?? null,
            'registry_profile' => $data['registry_profile'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $this->formatUser($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->formatUser($request->user())]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'    => (string) $user->_id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'profile' => match ($user->role) {
                'filer'    => $user->filer_profile,
                'judge'    => $user->judge_profile,
                'registry' => $user->registry_profile,
                default    => null,
            },
        ];
    }
}
