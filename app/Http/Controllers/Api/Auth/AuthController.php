<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\RegistrationPendingNotification;
use App\Services\DirectoryService;
use App\Services\SumateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly SumateService $sumate,
        private readonly DirectoryService $directory,
    ) {}

    /** 🌐 POST /api/auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos.'], 401);
        }

        if (! $user->active) {
            $message = $user->activated_at === null
                ? 'Tu cuenta está pendiente de activación. Te avisaremos cuando puedas ingresar.'
                : 'Tu cuenta está desactivada. Comunícate con Gestión Humana.';

            return response()->json(['message' => $message], 403);
        }

        $token = $user->createToken('intranet')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * 🌐 POST /api/auth/register — siempre crea roleType 'user', inactiva hasta que
     * un administrador la active (ver UserController@update). No entra a Súmate ni
     * al Directorio todavía: eso pasa al activarse.
     */
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
            'initials' => User::initialsFrom($request->name),
            'color' => User::colorFrom($request->email),
            'joined_at' => now()->toDateString(),
            'active' => false,
            'activated_at' => null,
        ]);

        if ($user->isProfileComplete()) {
            $user->forceFill(['profile_completed_at' => now()])->save();
        }

        $activators = User::withPermission('usuarios', 'editar')->where('active', true)->get();
        Notification::send($activators, new RegistrationPendingNotification($user));

        return response()->json([
            'pendingActivation' => true,
            'message' => 'Tu cuenta fue creada y está pendiente de activación por un administrador. Te avisaremos cuando puedas ingresar.',
        ], 202);
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

    /**
     * PATCH /api/auth/me — 👤 propio; email no editable.
     * Es también el endpoint del onboarding: al quedar el perfil completo se
     * sella `profile_completed_at` y se levanta el bloqueo (EnsureProfileCompleted).
     */
    public function updateMe(UpdateProfileRequest $request): UserProfileResource
    {
        $user = $request->user();
        $data = $request->profileData();

        $user->fill($data);

        if (array_key_exists('name', $data)) {
            $user->initials = User::initialsFrom($user->name);
        }

        if (! $user->color) {
            $user->color = User::colorFrom($user->email);
        }

        if ($user->isProfileComplete() && ! $user->profile_completed_at) {
            $user->profile_completed_at = now();
        }

        $user->save();

        $this->sumate->syncParticipantFor($user);
        $this->directory->syncFromUser($user);

        return new UserProfileResource($user);
    }

    /** 🌐 POST /api/auth/forgot-password — responde 200 siempre (no revela cuentas). */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json(['success' => true]);
    }

    /** 🌐 POST /api/auth/reset-password — revoca todas las sesiones activas del usuario. */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['success' => true]);
    }
}
