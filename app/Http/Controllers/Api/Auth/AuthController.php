<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** 🌐 POST /api/auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos.'], 401);
        }

        $token = $user->createToken('intranet')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /** 🌐 POST /api/auth/register — siempre crea roleType 'user'. */
    public function register(RegisterRequest $request): JsonResponse
    {
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'Ya existe una cuenta con ese correo.'], 409);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // cast 'hashed'
            'area' => $request->area,
            'role' => 'Colaborador',
            'role_type' => 'user', // forzado: registro público siempre es user
            'initials' => $this->initialsFrom($request->name),
            'joined_at' => now()->toDateString(),
        ]);

        $token = $user->createToken('intranet')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    /** POST /api/auth/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true]);
    }

    /** GET /api/auth/me */
    public function me(Request $request): UserProfileResource
    {
        return new UserProfileResource($request->user());
    }

    /** PATCH /api/auth/me — 👤 propio; email no editable. */
    public function updateMe(UpdateProfileRequest $request): UserProfileResource
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return new UserProfileResource($user);
    }

    /** 🌐 POST /api/auth/forgot-password — responde 200 siempre (no revela cuentas). */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Aquí se dispararía el envío del correo de recuperación.
        return response()->json(['success' => true]);
    }

    private function initialsFrom(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)
            ->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '??';
    }
}
