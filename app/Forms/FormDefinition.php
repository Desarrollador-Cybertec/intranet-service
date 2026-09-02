<?php

namespace App\Forms;

use Illuminate\Validation\Rule;

/**
 * Definición de un formulario dinámico: campos + reglas + sección dueña + adjuntos.
 * Vive en código (no en `modules.config`) porque son artefactos fijos de RRHH/SST —
 * una edición accidental no debe poder romper un formulario obligatorio. Lo único
 * editable sin deploy (destinatarios, intro) vive en `modules.config` del módulo
 * `formulario` que apunta a este slug.
 */
abstract class FormDefinition
{
    abstract public function slug(): string;

    abstract public function label(): string;

    /** Vista dueña del formulario (rh|sst): gobierna quién ve/gestiona las solicitudes. */
    abstract public function section(): string;

    /**
     * @return list<array{name:string,label:string,type:string,required?:bool,options?:list<array{value:string,label:string}>,minLength?:int,maxLength?:int}>
     */
    abstract public function fields(): array;

    public function allowsAttachments(): bool
    {
        return false;
    }

    public function maxAttachments(): int
    {
        return 4;
    }

    /** Acción de Súmate que se puede reclamar al enviar este formulario (Parte C). */
    public function sumateAccion(): ?string
    {
        return null;
    }

    /**
     * Reglas de validación de `payload` derivadas de fields(). No cubre adjuntos:
     * esas reglas son las mismas para cualquier formulario y viven en el FormRequest.
     *
     * @return array<string, array<int, mixed>>
     */
    public function fieldRules(): array
    {
        $rules = [];
        foreach ($this->fields() as $field) {
            $fieldRules = [($field['required'] ?? true) ? 'required' : 'nullable'];
            $fieldRules[] = match ($field['type']) {
                'date' => 'date_format:Y-m-d',
                'time' => 'date_format:H:i',
                'email' => 'email',
                'select' => Rule::in(array_column($field['options'] ?? [], 'value')),
                default => 'string',
            };
            if (isset($field['minLength'])) {
                $fieldRules[] = 'min:'.$field['minLength'];
            }
            if (isset($field['maxLength'])) {
                $fieldRules[] = 'max:'.$field['maxLength'];
            }
            $rules[$field['name']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array{slug:string,label:string,section:string,fields:array,allowsAttachments:bool,maxAttachments:int}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug(),
            'label' => $this->label(),
            'section' => $this->section(),
            'fields' => $this->fields(),
            'allowsAttachments' => $this->allowsAttachments(),
            'maxAttachments' => $this->maxAttachments(),
        ];
    }
}
