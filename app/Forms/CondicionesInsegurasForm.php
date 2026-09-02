<?php

namespace App\Forms;

class CondicionesInsegurasForm extends FormDefinition
{
    public function slug(): string
    {
        return 'condiciones-inseguras';
    }

    public function label(): string
    {
        return 'Reportar condición insegura';
    }

    public function section(): string
    {
        return 'sst';
    }

    public function allowsAttachments(): bool
    {
        return true;
    }

    public function fields(): array
    {
        return [
            ['name' => 'fecha', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
            ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => true, 'maxLength' => 255],
            ['name' => 'area', 'label' => 'Área', 'type' => 'select', 'required' => true, 'options' => self::areaOptions()],
            ['name' => 'riesgo', 'label' => 'Tipo de riesgo', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'locativo', 'label' => 'Locativo'],
                ['value' => 'mecanico', 'label' => 'Mecánico'],
                ['value' => 'electrico', 'label' => 'Eléctrico'],
                ['value' => 'quimico', 'label' => 'Químico'],
                ['value' => 'biologico', 'label' => 'Biológico'],
                ['value' => 'ergonomico', 'label' => 'Ergonómico'],
                ['value' => 'otro', 'label' => 'Otro'],
            ]],
            ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => true, 'minLength' => 20],
        ];
    }

    /** @return list<array{value:string,label:string}> */
    public static function areaOptions(): array
    {
        // Espejo de AREAS en src/schemas/registerSchema.ts (frontend).
        return array_map(fn (string $a) => ['value' => $a, 'label' => $a], [
            'Comercial', 'Bioseguridad', 'Nutrición Animal', 'Metalmecánica', 'Salud Animal',
            'Producción', 'Administración', 'TI', 'Gestión Humana', 'Cybertec',
        ]);
    }
}
