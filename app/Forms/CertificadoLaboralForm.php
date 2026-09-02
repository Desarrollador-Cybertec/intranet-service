<?php

namespace App\Forms;

class CertificadoLaboralForm extends FormDefinition
{
    public function slug(): string
    {
        return 'certificado-laboral';
    }

    public function label(): string
    {
        return 'Certificado laboral';
    }

    public function section(): string
    {
        return 'rh';
    }

    public function fields(): array
    {
        return [
            ['name' => 'dirigidoA', 'label' => 'Dirigido a', 'type' => 'text', 'required' => true, 'maxLength' => 255],
            ['name' => 'conSalario', 'label' => '¿Incluir salario?', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ]],
            ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true, 'minLength' => 10],
            ['name' => 'observaciones', 'label' => 'Observaciones', 'type' => 'textarea', 'required' => false],
        ];
    }
}
