<?php

namespace App\Forms;

class AccidenteTrabajoForm extends FormDefinition
{
    public function slug(): string
    {
        return 'accidente-trabajo';
    }

    public function label(): string
    {
        return 'Reportar accidente de trabajo';
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
            ['name' => 'hora', 'label' => 'Hora', 'type' => 'time', 'required' => true],
            ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => true, 'maxLength' => 255],
            ['name' => 'parteCuerpo', 'label' => 'Parte del cuerpo afectada', 'type' => 'text', 'required' => true, 'maxLength' => 255],
            ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => true, 'minLength' => 20],
            ['name' => 'testigos', 'label' => 'Testigos', 'type' => 'text', 'required' => false, 'maxLength' => 255],
        ];
    }
}
