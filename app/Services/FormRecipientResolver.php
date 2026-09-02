<?php

namespace App\Services;

use App\Models\Module;

/**
 * Resuelve destinatarios de un formulario dinámico, en orden: el módulo que lo expone
 * (config.recipients, editable sin deploy) → config/insumma.php por slug → 'default'.
 * Extraído a su propia clase para poder probarlo sin pasar por HTTP/colas/DB::afterCommit.
 */
class FormRecipientResolver
{
    /** @return list<string> */
    public function resolve(string $formSlug): array
    {
        $module = Module::where('type', 'formulario')->get()
            ->first(fn (Module $m) => ($m->config['formSlug'] ?? null) === $formSlug);

        $configured = $module?->config['recipients'] ?? null;
        if (! empty($configured)) {
            return array_values((array) $configured);
        }

        $bySlug = config("insumma.forms.destinatarios.{$formSlug}");
        if (! empty($bySlug)) {
            return array_values((array) $bySlug);
        }

        return array_values((array) config('insumma.forms.destinatarios.default', []));
    }
}
