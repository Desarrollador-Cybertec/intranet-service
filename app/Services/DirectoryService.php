<?php

namespace App\Services;

use App\Models\DirectoryPerson;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Mantiene `directory_people` sincronizada con `users` sin pisar lo que un admin
 * curó a mano. Se invoca desde UserController@store/@update (al activar),
 * AuthController@updateMe e ImportUsersCommand — nunca desde register(): una
 * cuenta pendiente de activación no debe aparecer en el directorio.
 */
class DirectoryService
{
    /**
     * Identidad (`name`, `email`, `photo`, `initials`, `color`) se sobrescribe
     * siempre porque el usuario la autogestiona. `area`, `role`, `phone`,
     * `extension` solo se rellenan si están vacíos: lo que un admin curó a mano
     * (p. ej. el cargo de Asistente Gerencia) no se pierde cuando el usuario
     * actualiza su propio perfil. `active` y `position` nunca se tocan aquí.
     */
    public function syncFromUser(User $user): DirectoryPerson
    {
        $person = DirectoryPerson::where('user_id', $user->id)->first()
            ?? DirectoryPerson::whereRaw('LOWER(email) = ?', [Str::lower($user->email)])->first();

        if (! $person) {
            $person = new DirectoryPerson(['active' => true, 'position' => 0]);
        }

        $person->user_id = $user->id;
        $person->name = $user->name;
        $person->email = Str::lower($user->email);
        $person->photo = $user->photo;
        $person->initials = $user->initials ?: User::initialsFrom($user->name);
        $person->color = $user->color ?: User::colorFrom($user->email);

        if (blank($person->area)) {
            $person->area = $user->area;
        }
        if (blank($person->role)) {
            $person->role = $user->role;
        }
        if (blank($person->phone)) {
            $person->phone = $user->phone;
        }
        if (blank($person->extension)) {
            $person->extension = $user->extension;
        }

        $person->save();

        return $person;
    }
}
