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
            ['section' => 'rh', 'slug' => 'nomina', 'label' => 'Nómina y liquidaciones', 'icon' => '💰', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Consulta de desprendibles de pago, liquidaciones y deducciones.', 'visible' => false],
            ['section' => 'rh', 'slug' => 'desempeno', 'label' => 'Evaluación de desempeño', 'icon' => '📊', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Seguimiento a objetivos, evaluaciones periódicas y planes de mejora individual.', 'visible' => false],
            ['section' => 'rh', 'slug' => 'bienestar', 'label' => 'Bienestar laboral', 'icon' => '🌟', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Programas de bienestar, subsidios, auxilios y beneficios para colaboradores.', 'visible' => false],
            ['section' => 'rh', 'slug' => 'disciplinaria', 'label' => 'Gestión disciplinaria', 'icon' => '⚖️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Procedimientos, descargos y seguimiento de procesos disciplinarios.', 'visible' => false],

            // RH › nuevos (constructor de módulos)
            ['section' => 'rh', 'slug' => 'rit', 'label' => 'Reglamento Interno de Trabajo', 'icon' => '📘', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Consulta el Reglamento Interno de Trabajo (RIT) vigente.', 'type' => 'documento', 'href' => 'https://insumma.co/rit.pdf'],
            ['section' => 'rh', 'slug' => 'certificado-laboral', 'label' => 'Certificado laboral', 'icon' => '📝', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Solicita tu certificado laboral, con o sin salario, para trámites externos.', 'type' => 'formulario', 'config' => ['formSlug' => 'certificado-laboral']],
            ['section' => 'rh', 'slug' => 'certificado-ingresos-retenciones', 'label' => 'Certificado de ingresos y retenciones', 'icon' => '🧾', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Solicita tu certificado de ingresos y retenciones del año gravable.', 'type' => 'formulario', 'config' => ['formSlug' => 'certificado-ingresos-retenciones']],

            // SST (sstMock.ts)
            ['section' => 'sst', 'slug' => 'incidentes', 'label' => 'Reporte de incidentes', 'icon' => '⚠️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Registra incidentes, accidentes y casi-accidentes de forma inmediata.', 'visible' => false],
            ['section' => 'sst', 'slug' => 'inspecciones', 'label' => 'Inspecciones de seguridad', 'icon' => '🔍', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Programa y registra inspecciones periódicas por área y puesto de trabajo.'],
            ['section' => 'sst', 'slug' => 'epp', 'label' => 'EPP y dotación', 'icon' => '🦺', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Solicita elementos de protección personal y consulta el inventario disponible.', 'visible' => false],
            ['section' => 'sst', 'slug' => 'formaciones', 'label' => 'Capacitaciones SST', 'icon' => '📚', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Accede al cronograma de formaciones obligatorias en seguridad y salud.', 'visible' => false],
            ['section' => 'sst', 'slug' => 'indicadores', 'label' => 'Indicadores HSE', 'icon' => '📊', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Consulta los indicadores de accidentalidad, ausentismo y gestión del riesgo.'],
            ['section' => 'sst', 'slug' => 'normatividad', 'label' => 'Normatividad vigente', 'icon' => '📋', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Resolución 0312, Decreto 1072 y demás normas aplicables al SG-SST.', 'visible' => false],

            // SST › nuevos (constructor de módulos)
            ['section' => 'sst', 'slug' => 'calendario-sst', 'label' => 'Calendario SST', 'icon' => '🗓️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Cronograma de inspecciones, simulacros y formaciones obligatorias.', 'type' => 'calendario', 'config' => ['calendarUrl' => 'https://minextcloud.com/index.php/apps/calendar/embed/sst']],
            ['section' => 'sst', 'slug' => 'indicadores-sst', 'label' => 'Indicadores SST', 'icon' => '📈', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Frecuencia y severidad de accidentalidad, ausentismo y cierre de hallazgos.', 'type' => 'indicadores', 'config' => ['tiles' => [
                ['label' => 'Índice de frecuencia', 'value' => '2.1', 'color' => '#C62828'],
                ['label' => 'Índice de severidad', 'value' => '18', 'color' => '#F57C00'],
                ['label' => 'Hallazgos cerrados', 'value' => '87%', 'color' => '#2E7D32'],
            ]]],
            ['section' => 'sst', 'slug' => 'condiciones-inseguras', 'label' => 'Reportar condición insegura', 'icon' => '🚧', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Reporta una condición insegura o acto inseguro para que SST lo atienda.', 'type' => 'formulario', 'config' => ['formSlug' => 'condiciones-inseguras']],
            ['section' => 'sst', 'slug' => 'accidente-trabajo', 'label' => 'Reportar accidente de trabajo', 'icon' => '🚑', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Reporta un accidente de trabajo de forma inmediata a SST.', 'type' => 'formulario', 'config' => ['formSlug' => 'accidente-trabajo']],
            ['section' => 'sst', 'slug' => 'reinducciones-sst', 'label' => 'Reinducciones SST', 'icon' => '🎓', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Material de la reinducción anual en seguridad y salud en el trabajo.', 'type' => 'documento', 'href' => 'https://insumma.co/reinduccion-sst.pdf'],
            ['section' => 'sst', 'slug' => 'inspecciones-auditorias', 'label' => 'Inspecciones y auditorías', 'icon' => '🗂️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Formatos y consolidado de inspecciones planeadas y auditorías del SG-SST.', 'type' => 'documento', 'href' => 'https://insumma.co/inspecciones-auditorias-sst.pdf'],

            // SIG (sigMock.ts)
            ['section' => 'sig', 'slug' => 'calidad', 'label' => 'Calidad (ISO 9001)', 'icon' => '🏅', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Gestión de procesos, no conformidades, acciones correctivas y auditorías internas.'],
            ['section' => 'sig', 'slug' => 'ambiental', 'label' => 'Ambiental (ISO 14001)', 'icon' => '🌿', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Control de aspectos ambientales, residuos, consumo de recursos y plan de gestión.'],
            ['section' => 'sig', 'slug' => 'sst', 'label' => 'SST (ISO 45001)', 'icon' => '🦺', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Identificación de peligros, evaluación de riesgos y controles operacionales.'],
            ['section' => 'sig', 'slug' => 'inocuidad', 'label' => 'Inocuidad (ISO 22000)', 'icon' => '🧪', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Buenas prácticas de manufactura, HACCP y control de puntos críticos.'],
            ['section' => 'sig', 'slug' => 'documentacion', 'label' => 'Documentación y registros', 'icon' => '📂', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Control de documentos externos e internos, versiones vigentes y distribución.'],
            ['section' => 'sig', 'slug' => 'auditorias', 'label' => 'Auditorías internas', 'icon' => '🔎', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Programación de auditorías, listas de verificación e informes de resultados.'],

            // SIG › heredado de SICREO 2.0 (eliminado en F2; contenido migrado aquí)
            ['section' => 'sig', 'slug' => 'solpec', 'label' => 'SOLPEC', 'icon' => '🐖', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Proyectos integrales de porcicultura. Sistema externo.', 'href' => 'https://solpec.net/'],
            ['section' => 'sig', 'slug' => 'codorcol', 'label' => 'CODORCOL', 'icon' => '🥚', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Proveedor mayorista de huevos de codorniz. Sistema externo.', 'href' => 'https://op1.codorcol.cyberteconline.com/'],
            ['section' => 'sig', 'slug' => 'mpd', 'label' => 'MPD — Procesos de Dirección', 'icon' => '🧭', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Gestión Estratégica (GE), Sistemas Integrados (SI) y Gestión del Saber (GS). Repositorio en Nextcloud.', 'href' => 'https://nextcloud.net.co/index.php/s/4YkpXq3ESFPM78f'],
            ['section' => 'sig', 'slug' => 'mpo', 'label' => 'MPO — Procesos Operacionales', 'icon' => '⚙️', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Gestión Comercial (GC), Cadena de Suministro (GL) y Gestión de Operaciones (GO). Repositorio en Nextcloud.', 'href' => 'https://nextcloud.net.co/index.php/s/4YkpXq3ESFPM78f'],
            ['section' => 'sig', 'slug' => 'mpa', 'label' => 'MPA — Procesos de Apoyo', 'icon' => '🤝', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Financiera y Contable (GF), Talento Humano (GH), Infraestructura (GI) e Información (TI). Repositorio en Nextcloud.', 'href' => 'https://nextcloud.net.co/index.php/s/4YkpXq3ESFPM78f'],

            // SIG › nuevos (constructor de módulos)
            ['section' => 'sig', 'slug' => 'indicadores-calidad', 'label' => 'Indicadores de calidad', 'icon' => '📈', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Cumplimiento de objetivos de calidad y satisfacción del cliente.', 'type' => 'indicadores', 'config' => ['tiles' => [
                ['label' => 'Cumplimiento objetivos', 'value' => '93%', 'color' => '#1565C0'],
                ['label' => 'Satisfacción cliente', 'value' => '4.6/5', 'color' => '#2E7D32'],
                ['label' => 'No conformidades abiertas', 'value' => '3', 'color' => '#F57C00'],
            ]]],
            ['section' => 'sig', 'slug' => 'cronograma-auditorias', 'label' => 'Cronograma de auditorías', 'icon' => '🗓️', 'color' => '#00695C', 'bg' => '#E0F2F1', 'desc' => 'Fechas programadas de auditorías internas y externas del SIG.', 'type' => 'calendario', 'config' => ['calendarUrl' => 'https://minextcloud.com/index.php/apps/calendar/embed/sig']],
            ['section' => 'sig', 'slug' => 'resultado-auditorias', 'label' => 'Resultados de auditorías', 'icon' => '📑', 'color' => '#6A1B9A', 'bg' => '#F3E5F5', 'desc' => 'Informes de las últimas auditorías internas y externas del SIG.', 'type' => 'documento', 'href' => 'https://insumma.co/resultados-auditorias.pdf'],
            ['section' => 'sig', 'slug' => 'proyectos-estrategicos', 'label' => 'Proyectos estratégicos', 'icon' => '🚀', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Seguimiento a los proyectos estratégicos vigentes de la organización.', 'type' => 'documento', 'href' => 'https://insumma.co/proyectos-estrategicos.pdf'],
            ['section' => 'sig', 'slug' => 'yo-aporto', 'label' => 'Yo Aporto', 'icon' => '💡', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Propón ideas de mejora para tu proceso o para toda la organización.', 'href' => 'https://insumma.co/yo-aporto'],
            ['section' => 'sig', 'slug' => 'acciones-mejora', 'label' => 'Acciones de mejora', 'icon' => '🔧', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Seguimiento a los planes de acción correctiva y de mejora abiertos.', 'href' => 'https://insumma.co/acciones-mejora'],
            ['section' => 'sig', 'slug' => 'infraestructura', 'label' => 'Temas de infraestructura', 'icon' => '🏗️', 'color' => '#C62828', 'bg' => '#FFEBEE', 'desc' => 'Reporta o consulta solicitudes relacionadas con infraestructura y planta física.', 'href' => 'https://insumma.co/infraestructura'],

            // SINTYC
            ['section' => 'sintyc', 'slug' => 'sintyc-app', 'label' => 'S!NTyC', 'icon' => '✅', 'color' => '#1B5E20', 'bg' => '#E8F5E9', 'desc' => 'Sistema Integrado de Tareas y Compromisos. Gestiona tus tareas asignadas.', 'href' => 'https://app.insumma.cyberteconline.com/login'],

            // Inicio › accesos rápidos (editables desde Configuraciones)
            ['section' => 'inicio', 'slug' => 'enterate', 'label' => 'Entérate', 'icon' => '📰', 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'desc' => 'Noticias y comunicados corporativos.', 'config' => ['section' => 'enterate']],
            ['section' => 'inicio', 'slug' => 'sumate', 'label' => 'Súmate', 'icon' => '🎯', 'color' => '#F57C00', 'bg' => '#FFF3E0', 'desc' => 'Programa de participación y reconocimiento.', 'config' => ['section' => 'sumate']],
            ['section' => 'inicio', 'slug' => 'directorio', 'label' => 'Directorio', 'icon' => '👥', 'color' => '#1565C0', 'bg' => '#E3F2FD', 'desc' => 'Directorio institucional de colaboradores.', 'config' => ['section' => 'directorio']],
            ['section' => 'inicio', 'slug' => 'calendario', 'label' => 'Calendario', 'icon' => '📅', 'color' => '#283593', 'bg' => '#E8EAF6', 'desc' => 'Calendario corporativo de eventos.', 'config' => ['section' => 'calendario']],
            ['section' => 'inicio', 'slug' => 'salas', 'label' => 'Salas de Juntas', 'icon' => '🏢', 'color' => '#004D40', 'bg' => '#E0F2F1', 'desc' => 'Reserva de salas de reunión.', 'config' => ['section' => 'salas']],
            ['section' => 'inicio', 'slug' => 'rh', 'label' => 'RH', 'icon' => '👔', 'color' => '#4A148C', 'bg' => '#F3E5F5', 'desc' => 'Gestión de Recursos Humanos.', 'config' => ['section' => 'rh']],
            ['section' => 'inicio', 'slug' => 'sst', 'label' => 'SST', 'icon' => '🦺', 'color' => '#B71C1C', 'bg' => '#FFEBEE', 'desc' => 'Seguridad y Salud en el Trabajo.', 'config' => ['section' => 'sst']],
            ['section' => 'inicio', 'slug' => 'sig', 'label' => 'SIG', 'icon' => '⚙️', 'color' => '#0D47A1', 'bg' => '#E3F2FD', 'desc' => 'Sistema Integrado de Gestión.', 'config' => ['section' => 'sig']],
            ['section' => 'inicio', 'slug' => 'sintyc', 'label' => 'S!NTyC', 'icon' => '✅', 'color' => '#1B5E20', 'bg' => '#E8F5E9', 'desc' => 'Sistema Integrado de Tareas y Compromisos.', 'config' => ['section' => 'sintyc']],
        ];

        foreach ($modules as $i => $m) {
            Module::updateOrCreate(
                ['section' => $m['section'], 'slug' => $m['slug']],
                $m + ['position' => $i, 'type' => $m['type'] ?? 'enlace'],
            );
        }
    }
}
