<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo del feed (newsMock.ts, reconocimientosMock.ts, eventosMock.ts).
 * Se respetan los IDs originales del mock para que el front funcione sin cambios.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $a) {
            Article::updateOrCreate(['id' => $a['id']], $a);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'id' => 1, 'type' => 'noticias',
                'tag' => 'Corporativo', 'tag_bg' => '#E8F5E9', 'tag_color' => '#2E7D32',
                'title' => 'Nueva política de bienestar laboral 2026',
                'excerpt' => 'Conoce los cambios en los beneficios para todos los colaboradores. Se implementan jornadas flexibles, apoyo en salud mental y convenio deportivo.',
                'date' => 'Hace 2 horas', 'author' => 'Recursos Humanos',
                'imgs' => ['bien1', 'bien2', 'bien3'],
                'body' => <<<'HTML'
      <p><strong>Insumma Business Group</strong> anuncia una renovación integral de su política de bienestar laboral, efectiva a partir del <strong>1 de julio de 2026</strong>. Esta iniciativa nace del compromiso de la organización con la calidad de vida de sus 180 colaboradores.</p>

      <h4 style="color:#2E7D32;margin:16px 0 8px">🕐 Jornadas flexibles</h4>
      <p>Posibilidad de iniciar la jornada laboral entre las 7:00 a.m. y las 9:00 a.m., según acuerdo previo con el jefe inmediato. La jornada sigue siendo de 8 horas pero el colaborador elige su hora de entrada dentro de esa ventana.</p>

      <h4 style="color:#2E7D32;margin:16px 0 8px">🧠 Apoyo en salud mental</h4>
      <p>Tres sesiones gratuitas por año con psicólogo externo, completamente confidenciales. El proceso de agendamiento se hace directamente con el profesional sin pasar por RH, garantizando privacidad total.</p>

      <h4 style="color:#2E7D32;margin:16px 0 8px">🏃 Programa deportivo</h4>
      <p>Convenio con <strong>Bodytech Girón</strong> para el colaborador y un familiar a cargo, con descuento del <strong>40 %</strong> en la membresía mensual. Aplica para sede Girón y Cabecera.</p>

      <h4 style="color:#2E7D32;margin:16px 0 8px">🍽️ Alimentación saludable</h4>
      <p>Desde julio se dispondrá de frutas y snacks saludables en cada área, renovados dos veces por semana. Iniciativa piloto en planta Girón durante el tercer trimestre.</p>

      <p style="margin-top:20px;padding:12px 16px;background:#E8F5E9;border-radius:8px;font-size:13px">
        📞 Para mayor información comunícate con el área de Gestión Humana a la <strong>extensión 102</strong> o escribe a <strong>bienestar@insumma.co</strong>
      </p>
HTML,
            ],
            [
                'id' => 2, 'type' => 'noticias',
                'tag' => 'Resultados', 'tag_bg' => '#FFF3E0', 'tag_color' => '#E65100',
                'title' => 'Q1 2026 — Superamos la meta comercial en todas las unidades',
                'excerpt' => 'Salud Animal creció 18 % y Nutrición Animal 12 %. El mejor primer trimestre en la historia del grupo. Gracias a todo el equipo.',
                'date' => 'Ayer', 'author' => 'Gerencia General',
                'imgs' => ['res1', 'res2', 'res3'],
                'body' => <<<'HTML'
      <p>Con gran orgullo la Gerencia General comparte que el primer trimestre de 2026 cerró con <strong>resultados históricos</strong> para Insumma Business Group S.A. Este logro es el reflejo directo del trabajo comprometido de cada persona en el equipo.</p>

      <h4 style="color:#E65100;margin:16px 0 8px">📈 Unidad Salud Animal</h4>
      <p>Crecimiento del <strong>18 %</strong> frente al Q1 2025, superando la meta en 6 puntos porcentuales. La línea de antiparasitarios y vitaminas registró el mayor incremento histórico en ventas de temporada.</p>

      <h4 style="color:#E65100;margin:16px 0 8px">📈 Unidad Nutrición Animal</h4>
      <p>Crecimiento del <strong>12 %</strong>, con un incremento notable en el segmento de aves de postura. Los nuevos clientes incorporados en febrero ya representan el 8 % del volumen total.</p>

      <h4 style="color:#E65100;margin:16px 0 8px">📦 Logística e indicadores de servicio</h4>
      <p>Cumplimiento del <strong>97 %</strong> en los tiempos de entrega pactados — el mejor indicador de los últimos tres años. La optimización de rutas implementada en enero redujo el costo por kilómetro en un 9 %.</p>

      <h4 style="color:#E65100;margin:16px 0 8px">💰 Perspectiva Q2 2026</h4>
      <p>La meta del segundo trimestre se proyecta con un crecimiento adicional del 8 %, apoyado en el lanzamiento de la línea <em>Proteínas Max</em> y la expansión a nuevos municipios del departamento de Santander.</p>

      <p style="margin-top:20px;padding:12px 16px;background:#FFF3E0;border-radius:8px;font-size:13px">
        🎉 <strong>¡Gracias por hacer posible este logro! El éxito de Insumma es el éxito de cada uno de ustedes.</strong>
      </p>
HTML,
            ],
            [
                'id' => 3, 'type' => 'noticias',
                'tag' => 'Formación', 'tag_bg' => '#E3F2FD', 'tag_color' => '#1565C0',
                'title' => 'Inscripciones abiertas — Programa de Capacitación Q2 2026',
                'excerpt' => 'Bioseguridad, nutrición animal y habilidades blandas. Cupos limitados. Modalidad presencial y virtual. Inscripciones hasta el 10 de julio.',
                'date' => 'Hace 2 días', 'author' => 'Gestión Humana',
                'imgs' => ['cap1', 'cap2', 'cap3'],
                'body' => <<<'HTML'
      <p>El área de <strong>Gestión Humana</strong> pone a disposición el catálogo de formación del segundo trimestre de 2026. Las inscripciones estarán abiertas del <strong>25 de junio al 10 de julio</strong>.</p>

      <h4 style="color:#1565C0;margin:16px 0 8px">🔬 Bioseguridad avanzada</h4>
      <p><strong>16 horas</strong> · Presencial en sede Girón · Cupos: 15 personas<br>
      Dirigido a personal de planta, bodegas y campo. Cubre protocolos Decreto 1072, manejo de sustancias peligrosas y respuesta a emergencias.</p>

      <h4 style="color:#1565C0;margin:16px 0 8px">🐄 Nutrición animal aplicada</h4>
      <p><strong>12 horas</strong> · Virtual en vivo · Cupos: 30 personas<br>
      Para el equipo comercial y técnico. Énfasis en formulación de raciones para aves de postura y porcinos de alta producción.</p>

      <h4 style="color:#1565C0;margin:16px 0 8px">🤝 Comunicación asertiva</h4>
      <p><strong>8 horas</strong> · Virtual asincrónica · Sin límite de cupos<br>
      Habilidades de comunicación, manejo de conflictos y trabajo en equipo. Acceso libre durante todo julio.</p>

      <h4 style="color:#1565C0;margin:16px 0 8px">💼 Excel para gestión comercial</h4>
      <p><strong>10 horas</strong> · Virtual en vivo · Cupos: 20 personas<br>
      Tablas dinámicas, dashboards y automatización básica aplicadas al seguimiento de ventas y cartera.</p>

      <p style="margin-top:20px;padding:12px 16px;background:#E3F2FD;border-radius:8px;font-size:13px">
        📝 Inscríbete en el portal de formación o comunícate con Gestión Humana a la <strong>ext. 104</strong>. Los cupos se asignan por orden de solicitud.
      </p>
HTML,
            ],
            [
                'id' => 4, 'type' => 'noticias',
                'tag' => 'Sostenibilidad', 'tag_bg' => '#E0F2F1', 'tag_color' => '#00695C',
                'title' => 'Segunda fase del programa de reciclaje e impacto ambiental',
                'excerpt' => 'Iniciamos la fase II del programa de gestión ambiental en todas las instalaciones. Puntos ecológicos, ahorro de agua y jornada de reforestación el 22 de agosto.',
                'date' => 'Hace 3 días', 'author' => 'HSEQ',
                'imgs' => ['rec1', 'rec2', 'rec3'],
                'body' => <<<'HTML'
      <p>El área de <strong>HSEQ</strong> anuncia el inicio de la segunda fase del <em>Programa de Gestión Ambiental IBG</em>, que busca reducir la huella de carbono de la organización en un 20 % para finales de 2026.</p>

      <h4 style="color:#00695C;margin:16px 0 8px">♻️ Puntos ecológicos</h4>
      <p>Instalación de puntos de separación de residuos en todas las áreas (plástico, papel, vidrio, orgánico). El proceso de recolección diferenciada empieza el <strong>1 de julio</strong> con capacitación a cada área.</p>

      <h4 style="color:#00695C;margin:16px 0 8px">💧 Ahorro de agua</h4>
      <p>Meta de reducción del <strong>15 %</strong> en consumo mensual respecto al promedio de los últimos 6 meses. Se instalarán sensores de flujo en baños y áreas de lavado de planta.</p>

      <h4 style="color:#00695C;margin:16px 0 8px">⚡ Eficiencia energética</h4>
      <p>Cambio progresivo a iluminación LED en bodega principal y pasillos administrativos. Se estima un ahorro del 35 % en la factura eléctrica de esas zonas.</p>

      <h4 style="color:#00695C;margin:16px 0 8px">🌳 Jornada de reforestación</h4>
      <p>El <strong>22 de agosto</strong> realizaremos la siembra de 200 árboles nativos en las zonas verdes del campo Girón, con participación voluntaria de colaboradores y sus familias.</p>

      <p style="margin-top:20px;padding:12px 16px;background:#E0F2F1;border-radius:8px;font-size:13px">
        🌱 ¿Quieres participar en la jornada de reforestación? Inscríbete con HSEQ antes del 15 de agosto. ¡Los cupos son limitados!
      </p>
HTML,
            ],
            [
                'id' => 10, 'type' => 'comunicados',
                'tag' => 'Seguridad', 'tag_bg' => '#FFEBEE', 'tag_color' => '#C62828',
                'title' => 'Actualización del protocolo de seguridad industrial — Uso de EPP',
                'excerpt' => 'A partir del 1 de julio es obligatorio el uso de casco, chaleco y guantes en toda el área de producción. Sin excepciones para ningún cargo.',
                'date' => 'Hace 4 horas', 'author' => 'HSEQ · Jefe de Seguridad',
                'imgs' => ['seg1', 'seg2', 'seg3'],
                'body' => <<<'HTML'
      <p style="padding:10px 14px;background:#FFEBEE;border-left:4px solid #C62828;border-radius:4px;font-weight:600;color:#C62828">
        COMUNICADO OFICIAL — HSEQ · PSI-2026-02
      </p>

      <p>A partir del <strong>1 de julio de 2026</strong> entran en vigencia las modificaciones al Protocolo de Seguridad Industrial N.° PSI-2026-02. Este comunicado es de carácter obligatorio y aplica para la totalidad del personal, contratistas y visitas.</p>

      <h4 style="color:#C62828;margin:16px 0 8px">🦺 Área de producción y bodegas</h4>
      <p>Equipo mínimo obligatorio en todo momento:</p>
      <ul style="margin:8px 0 8px 20px;line-height:1.9">
        <li>Casco tipo I (suministrado por almacén)</li>
        <li>Chaleco reflectivo categoría II</li>
        <li>Guantes de nitrilo para manejo de productos</li>
        <li>Botas con punta de acero (no se permite calzado abierto)</li>
      </ul>

      <h4 style="color:#C62828;margin:16px 0 8px">🥽 Área de laboratorio</h4>
      <p>Adicionalmente a lo anterior:</p>
      <ul style="margin:8px 0 8px 20px;line-height:1.9">
        <li>Monogafas de seguridad anti-salpicadura</li>
        <li>Bata anti-fluidos de manga larga</li>
        <li>Mascarilla N95 durante manipulación de sustancias</li>
      </ul>

      <h4 style="color:#C62828;margin:16px 0 8px">🚧 Visitas y contratistas</h4>
      <p>Deben presentar su propio EPP al ingresar a zonas restringidas o solicitarlo en recepción con mínimo 30 minutos de antelación. Sin EPP no se permite el acceso.</p>

      <p style="margin-top:20px;padding:12px 16px;background:#FFEBEE;border-radius:8px;font-size:13px">
        ⚠️ El incumplimiento será causal de llamado de atención formal. Solicita tu dotación en <strong>almacén ext. 118</strong> o escribe a hseq@insumma.co
      </p>
HTML,
            ],
            [
                'id' => 11, 'type' => 'comunicados',
                'tag' => 'Institucional', 'tag_bg' => '#E8EAF6', 'tag_color' => '#283593',
                'title' => 'Convocatoria — Reunión de Junta Directiva · Julio 2026',
                'excerpt' => 'Se convoca a todos los miembros de la junta directiva y gerentes de unidad a la sesión ordinaria del mes de julio. Confirmación requerida.',
                'date' => 'Hace 1 día', 'author' => 'Secretaría General',
                'imgs' => ['reu1', 'reu2', 'reu3'],
                'body' => <<<'HTML'
      <p style="padding:10px 14px;background:#E8EAF6;border-left:4px solid #283593;border-radius:4px;font-weight:600;color:#283593">
        CONVOCATORIA FORMAL — Secretaría General
      </p>

      <p>La Secretaría General convoca a la sesión ordinaria de Junta Directiva correspondiente al mes de julio de 2026, de conformidad con el artículo 18 de los Estatutos Sociales de Insumma Business Group S.A.</p>

      <h4 style="color:#283593;margin:16px 0 8px">📋 Datos de la sesión</h4>
      <ul style="margin:8px 0 8px 20px;line-height:2.0">
        <li>📅 <strong>Fecha:</strong> Viernes 11 de julio de 2026</li>
        <li>🕘 <strong>Hora:</strong> 9:00 a.m. — 12:00 m. (3 horas)</li>
        <li>📍 <strong>Lugar:</strong> Sala de juntas principal — Sede Girón, piso 2</li>
        <li>🔗 <strong>Enlace virtual:</strong> Disponible en la invitación de calendario</li>
      </ul>

      <h4 style="color:#283593;margin:16px 0 8px">📌 Orden del día (provisional)</h4>
      <ol style="margin:8px 0 8px 20px;line-height:2.0">
        <li>Verificación de quórum y apertura</li>
        <li>Aprobación del acta anterior</li>
        <li>Informe de resultados Q1 2026</li>
        <li>Revisión de metas Q2 y ajuste de presupuesto</li>
        <li>Lanzamiento línea Proteínas Max — aprobación de inversión</li>
        <li>Propuestas y varios</li>
      </ol>

      <p style="margin-top:20px;padding:12px 16px;background:#E8EAF6;border-radius:8px;font-size:13px">
        ✅ Confirmar asistencia <strong>antes del 9 de julio</strong> al correo <strong>junta@insumma.co</strong>. La no confirmación se tomará como inasistencia justificada.
      </p>
HTML,
            ],
            [
                'id' => 12, 'type' => 'comunicados',
                'tag' => 'Comercial', 'tag_bg' => '#F3E5F5', 'tag_color' => '#6A1B9A',
                'title' => 'Lanzamiento oficial — Línea Proteínas Max 2026',
                'excerpt' => 'Presentamos nuestra nueva línea premium de nutrición animal de alta performance. Disponible desde agosto 2026 para toda la región Santander.',
                'date' => 'Hace 2 días', 'author' => 'Dirección Comercial',
                'imgs' => ['prod1', 'prod2', 'prod3'],
                'body' => <<<'HTML'
      <p>La Dirección Comercial anuncia con orgullo el lanzamiento de <strong>Proteínas Max</strong>, la nueva línea premium de nutrición animal de Insumma Business Group, resultado de 18 meses de investigación y desarrollo con el equipo técnico.</p>

      <h4 style="color:#6A1B9A;margin:16px 0 8px">🥩 Características técnicas</h4>
      <ul style="margin:8px 0 8px 20px;line-height:1.9">
        <li>Concentrado proteico de alta digestibilidad (<strong>≥ 68 % PB</strong>)</li>
        <li>Formulado para aves, porcinos y bovinos de alta producción</li>
        <li>Aminograma balanceado con metionina y lisina suplementadas</li>
        <li>Sin antibióticos promotores de crecimiento (APCs)</li>
      </ul>

      <h4 style="color:#6A1B9A;margin:16px 0 8px">📦 Presentaciones disponibles</h4>
      <ul style="margin:8px 0 8px 20px;line-height:1.9">
        <li>Bulto 40 kg — Distribución minorista</li>
        <li>Bulto 50 kg — Canal mayorista</li>
        <li>Big bag 1.000 kg — Granjas industriales (pedido mínimo 5 ton)</li>
      </ul>

      <h4 style="color:#6A1B9A;margin:16px 0 8px">📅 Capacitación al equipo comercial</h4>
      <ul style="margin:8px 0 8px 20px;line-height:1.9">
        <li>🌐 <strong>Julio 18</strong> — Virtual (Zoom) · 2:00 p.m. a 4:00 p.m.</li>
        <li>🏢 <strong>Julio 25</strong> — Presencial, sede Girón · 8:00 a.m. a 12:00 m.</li>
      </ul>

      <p style="margin-top:20px;padding:12px 16px;background:#F3E5F5;border-radius:8px;font-size:13px">
        📋 El portafolio completo y la lista de precios estarán disponibles en SICREO 2.0 a partir del <strong>1 de agosto</strong>. Consultas a la Dirección Comercial ext. 110.
      </p>
HTML,
            ],
            [
                'id' => 30, 'type' => 'reconocimientos',
                'tag' => '⭐ MVP del Mes', 'tag_bg' => '#FFF3E0', 'tag_color' => '#E65100',
                'title' => 'Laura Peña — Bioseguridad · Junio 2026',
                'excerpt' => 'Por liderar la implementación del nuevo protocolo de bioseguridad en planta, reduciendo incidentes en un 40 % en solo tres meses.',
                'date' => 'Jun 2026', 'author' => 'Nominado por Sandra Ruiz',
                'imgs' => ['mvp1', 'mvp2', 'mvp3'],
                'body' => <<<'HTML'
<div style="display:flex;align-items:center;gap:14px;background:#FFF3E0;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
               <div style="width:54px;height:54px;border-radius:50%;background:#E65100;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;flex-shrink:0;">LP</div>
               <div>
                   <div style="font-size:17px;font-weight:800;color:#1C2B2D;">Laura Peña</div>
                   <div style="font-size:12px;color:#E65100;font-weight:600;">Coordinadora de Bioseguridad · Planta Girón</div>
                   <div style="font-size:11.5px;color:#78909C;margin-top:2px;">Nominada por Sandra Ruiz (HSEQ) · Junio 2026</div>
               </div>
           </div>
           <p><strong>¿Por qué este reconocimiento?</strong></p>
           <p>🛡️ <strong>40 % de reducción</strong> en incidentes de bioseguridad reportados.<br>
           📋 Capacitación del 100 % del personal de planta en el nuevo protocolo.<br>
           🤝 Coordinación con proveedores para adecuación de EPP sin costos adicionales.<br>
           ✅ Aprobación sin observaciones en la auditoría externa de julio.</p>
HTML,
            ],
            [
                'id' => 31, 'type' => 'reconocimientos',
                'tag' => '🤝 Trabajo en Equipo', 'tag_bg' => '#E3F2FD', 'tag_color' => '#1565C0',
                'title' => 'Equipo Comercial Zona Norte · Junio 2026',
                'excerpt' => 'Superaron la meta de ventas Q1 en un 18 % gracias a la sinergia y trabajo conjunto de todo el equipo en campo.',
                'date' => 'Jun 2026', 'author' => 'Gerencia General',
                'imgs' => ['equipo1', 'equipo2', 'equipo3'],
                'body' => <<<'HTML'
<p>📈 <strong>+18 % sobre la meta de ventas Q1</strong> — El mejor trimestre en la historia de la zona.<br>
           🗺️ Apertura de <strong>23 clientes nuevos</strong> en municipios rurales de Boyacá y Santander.<br>
           🐄 Crecimiento del 22 % en el segmento bovino.<br>
           ⏱️ Reducción del ciclo de venta promedio de 12 a 8 días.</p>
HTML,
            ],
            [
                'id' => 32, 'type' => 'reconocimientos',
                'tag' => '💡 Innovación', 'tag_bg' => '#E8F5E9', 'tag_color' => '#2E7D32',
                'title' => 'Roberto Pardo — Metalmecánica · Mayo 2026',
                'excerpt' => 'Diseñó una solución que optimizó el proceso de producción de equipos, ahorrando 15 horas semanales de operación.',
                'date' => 'May 2026', 'author' => 'Dir. de Producción',
                'imgs' => ['innov1', 'innov2', 'innov3'],
                'body' => <<<'HTML'
<p>⚙️ <strong>Jig de posicionamiento</strong> diseñado con materiales disponibles en planta, sin inversión externa.<br>
           ⏱️ <strong>15 horas semanales ahorradas</strong> — equivalentes a casi 2 turnos completos de producción.<br>
           📉 Reducción del 28 % en el desperdicio de material en el área de corte.<br>
           🔩 La solución fue adoptada también por las plantas de Bucaramanga y Cali.</p>
HTML,
            ],
            [
                'id' => 20, 'type' => 'eventos',
                'tag' => 'Formación', 'tag_bg' => '#E3F2FD', 'tag_color' => '#1565C0',
                'title' => 'Capacitación: Bioseguridad Q2 2026',
                'excerpt' => 'Actualización de protocolos para todo el personal de campo y técnicos de planta. Modalidad virtual y presencial. Cupos limitados.',
                'date' => '4 jul, 2026 · 8:00 am', 'author' => 'Gestión Humana',
                'imgs' => ['bio1', 'bio2', 'bio3'],
                'body' => <<<'HTML'
<div style="background:#E3F2FD;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
               <span>📅 <strong>Fecha:</strong> 4 de julio de 2026</span>
               <span>🕗 <strong>Hora:</strong> 8:00 a.m. – 12:00 m.</span>
               <span>📍 <strong>Lugar:</strong> Virtual + Sede Girón</span>
               <span>👥 <strong>Cupos:</strong> 40 por sesión</span>
           </div>
           <p><strong>Contenido:</strong></p>
           <p>🔬 <strong>Módulo 1:</strong> Normativa vigente — Resolución 0312 y cambios 2026.<br>
           🦺 <strong>Módulo 2:</strong> Uso correcto del EPP por área de trabajo.<br>
           🧪 <strong>Módulo 3:</strong> Protocolos de manipulación de productos veterinarios.<br>
           📋 <strong>Módulo 4:</strong> Registro e informes de incidentes.</p>
           <p>La asistencia es <strong>obligatoria</strong> para personal de producción y campo.</p>
HTML,
            ],
            [
                'id' => 21, 'type' => 'eventos',
                'tag' => 'Social', 'tag_bg' => '#FFF3E0', 'tag_color' => '#E65100',
                'title' => 'Celebración aniversario 25 años — Insumma BG',
                'excerpt' => '25 años de crecimiento conjunto. Gran celebración con colaboradores, familias e invitados especiales. Sede principal Girón.',
                'date' => '12 jul, 2026 · 4:00 pm', 'author' => 'Gerencia General',
                'imgs' => ['aniv1', 'aniv2', 'aniv3'],
                'body' => <<<'HTML'
<div style="background:#FFF3E0;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
               <span>📅 <strong>Fecha:</strong> 12 de julio de 2026</span>
               <span>🕓 <strong>Hora:</strong> 4:00 p.m. en adelante</span>
               <span>📍 <strong>Lugar:</strong> Sede principal, Girón</span>
               <span>👨‍👩‍👧 <strong>Invitados:</strong> Colaboradores y familia</span>
           </div>
           <p><strong>Programa del evento:</strong></p>
           <p>🎤 Palabras de la Gerencia General y reconocimientos especiales.<br>
           🏆 Entrega de premios — Colaboradores destacados 2026.<br>
           🎵 Show musical en vivo — Banda local de Girón.<br>
           🍽️ Cena buffet para todos los asistentes y sus familias.<br>
           📸 Fotografías profesionales y recuerdo del evento.</p>
           <p>Cada colaborador puede llevar hasta <strong>2 acompañantes</strong>. Confirmar asistencia antes del 5 de julio.</p>
HTML,
            ],
            [
                'id' => 22, 'type' => 'eventos',
                'tag' => 'Institucional', 'tag_bg' => '#E8F5E9', 'tag_color' => '#2E7D32',
                'title' => 'Feria Agropecuaria Bucaramanga 2026',
                'excerpt' => 'Participación institucional de Insumma BG. Se buscan representantes técnicos por área para atender el stand en Bucaramanga.',
                'date' => '20–23 ago, 2026 · Bucaramanga', 'author' => 'Dirección Comercial',
                'imgs' => ['feria1', 'feria2', 'feria3'],
                'body' => <<<'HTML'
<div style="background:#E8F5E9;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
               <span>📅 <strong>Fechas:</strong> 20 al 23 de agosto 2026</span>
               <span>📍 <strong>Lugar:</strong> Corferias Bucaramanga</span>
               <span>🏢 <strong>Stand:</strong> Pabellón 3, módulo B-12</span>
               <span>🎯 <strong>Perfiles:</strong> Técnicos, asesores y ventas</span>
           </div>
           <p>Se requieren <strong>6 representantes</strong> en turnos de 4 horas. Insumma cubre transporte, alimentación y hospedaje. Los participantes recibirán bonificación por asistencia.</p>
HTML,
            ],
        ];
    }
}
