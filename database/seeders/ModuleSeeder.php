<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // RH (rhMock.ts)
            ['section' => 'rh', 'slug' => 'documentos', 'label' => 'Mis documentos', 'icon' => '📄', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Contrato, paz y salvo, certificaciones laborales y otros documentos personales.'],
            ['section' => 'rh', 'slug' => 'permisos', 'label' => 'Solicitud de permisos', 'icon' => '📅', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Radicación de permisos, vacaciones, licencias y ausencias justificadas.'],
            ['section' => 'rh', 'slug' => 'nomina', 'label' => 'Nómina y liquidaciones', 'icon' => '💰', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Consulta de desprendibles de pago, liquidaciones y deducciones.'],
            ['section' => 'rh', 'slug' => 'desempeno', 'label' => 'Evaluación de desempeño', 'icon' => '📊', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Seguimiento a objetivos, evaluaciones periódicas y planes de mejora individual.'],
            ['section' => 'rh', 'slug' => 'bienestar', 'label' => 'Bienestar laboral', 'icon' => '🌟', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Programas de bienestar, subsidios, auxilios y beneficios para colaboradores.'],
            ['section' => 'rh', 'slug' => 'disciplinaria', 'label' => 'Gestión disciplinaria', 'icon' => '⚖️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Procedimientos, descargos y seguimiento de procesos disciplinarios.'],

            // SST (sstMock.ts)
            ['section' => 'sst', 'slug' => 'incidentes', 'label' => 'Reporte de incidentes', 'icon' => '⚠️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Registra incidentes, accidentes y casi-accidentes de forma inmediata.'],
            ['section' => 'sst', 'slug' => 'inspecciones', 'label' => 'Inspecciones de seguridad', 'icon' => '🔍', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Programa y registra inspecciones periódicas por área y puesto de trabajo.'],
            ['section' => 'sst', 'slug' => 'epp', 'label' => 'EPP y dotación', 'icon' => '🦺', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Solicita elementos de protección personal y consulta el inventario disponible.'],
            ['section' => 'sst', 'slug' => 'formaciones', 'label' => 'Capacitaciones SST', 'icon' => '📚', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Accede al cronograma de formaciones obligatorias en seguridad y salud.'],
            ['section' => 'sst', 'slug' => 'indicadores', 'label' => 'Indicadores HSE', 'icon' => '📊', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Consulta los indicadores de accidentalidad, ausentismo y gestión del riesgo.'],
            ['section' => 'sst', 'slug' => 'normatividad', 'label' => 'Normatividad vigente', 'icon' => '📋', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Resolución 0312, Decreto 1072 y demás normas aplicables al SG-SST.'],

            // SIG (sigMock.ts)
            ['section' => 'sig', 'slug' => 'calidad', 'label' => 'Calidad (ISO 9001)', 'icon' => '🏅', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Gestión de procesos, no conformidades, acciones correctivas y auditorías internas.'],
            ['section' => 'sig', 'slug' => 'ambiental', 'label' => 'Ambiental (ISO 14001)', 'icon' => '🌿', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Control de aspectos ambientales, residuos, consumo de recursos y plan de gestión.'],
            ['section' => 'sig', 'slug' => 'sst', 'label' => 'SST (ISO 45001)', 'icon' => '🦺', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Identificación de peligros, evaluación de riesgos y controles operacionales.'],
            ['section' => 'sig', 'slug' => 'inocuidad', 'label' => 'Inocuidad (ISO 22000)', 'icon' => '🧪', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Buenas prácticas de manufactura, HACCP y control de puntos críticos.'],
            ['section' => 'sig', 'slug' => 'documentacion', 'label' => 'Documentación y registros', 'icon' => '📂', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Control de documentos externos e internos, versiones vigentes y distribución.'],
            ['section' => 'sig', 'slug' => 'auditorias', 'label' => 'Auditorías internas', 'icon' => '🔎', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Programación de auditorías, listas de verificación e informes de resultados.'],
        ];

        foreach ($modules as $i => $m) {
            Module::updateOrCreate(
                ['section' => $m['section'], 'slug' => $m['slug']],
                $m + ['position' => $i],
            );
        }
    }
}
