<?php

namespace App\Forms;

class CertificadoIngresosRetencionesForm extends FormDefinition
{
    public function slug(): string
    {
        return 'certificado-ingresos-retenciones';
    }

    public function label(): string
    {
        return 'Certificado de ingresos y retenciones';
    }

    public function section(): string
    {
        return 'rh';
    }

    public function fields(): array
    {
        $anioActual = (int) now()->format('Y');
        $anios = [];
        for ($i = 0; $i < 5; $i++) {
            $anio = (string) ($anioActual - $i);
            $anios[] = ['value' => $anio, 'label' => $anio];
        }

        return [
            ['name' => 'anioGravable', 'label' => 'Año gravable', 'type' => 'select', 'required' => true, 'options' => $anios],
            ['name' => 'correoEnvio', 'label' => 'Correo de envío', 'type' => 'email', 'required' => true, 'maxLength' => 255],
            ['name' => 'observaciones', 'label' => 'Observaciones', 'type' => 'textarea', 'required' => false],
        ];
    }
}
