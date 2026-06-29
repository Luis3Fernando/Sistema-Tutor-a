<?php
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

$hoy = date('Y-m-d');
$stats = [
    'realizadas'  => 0,
    'programadas' => 0,
    'pendientes'  => 0,
];

// Procesar actividades/sesiones
if (!empty($actividades)) {
    foreach ($actividades as $a) {
        $estado = strtolower($a['estado'] ?? '');
        $fecha  = date('Y-m-d', strtotime($a['fecha'] ?? ''));

        if ($estado === 'realizado') {
            $stats['realizadas']++;
        } elseif ($estado === 'programada') {
            ($fecha >= $hoy) ? $stats['programadas']++ : $stats['pendientes']++;
        }
    }
}

// --- 2. DEFINICIÓN DE KPIS (Esto soluciona tu error) ---
$totalDerivaciones = isset($derivaciones) ? count($derivaciones) : 0;

$kpis = [
    ['label' => 'Sesiones Realizadas',   'val' => $stats['realizadas'],  'icon' => 'bi-check-all',      'color' => 'success'],
    ['label' => 'Sesiones Programadas',  'val' => $stats['programadas'], 'icon' => 'bi-calendar-event', 'color' => 'primary'],
    ['label' => 'Sesiones Vencidas',     'val' => $stats['pendientes'],  'icon' => 'bi-calendar-x',     'color' => 'danger'],
    ['label' => 'Derivaciones', 'val' => $totalDerivaciones,    'icon' => 'bi-arrow-repeat',   'color' => 'warning'],
];

// --- 3. LÓGICA DE RIESGOS ---
function parseRisk($nivelRaw) {
    $n = strtolower((string)$nivelRaw);
    if (str_contains($n, 'alto') || str_contains($n, '1')) 
        return ['label' => 'Alto Riesgo', 'color' => 'danger', 'emoji' => '🔴', 'puntos' => 3];
    if (str_contains($n, 'medio') || str_contains($n, '2')) 
        return ['label' => 'Riesgo Medio', 'color' => 'warning', 'emoji' => '🟡', 'puntos' => 2];
    if (str_contains($n, 'adecuado') || str_contains($n, '3') || str_contains($n, 'bajo')) 
        return ['label' => 'Adecuado', 'color' => 'success', 'emoji' => '🟢', 'puntos' => 1];
    
    return ['label' => 'No evaluado', 'color' => 'secondary', 'emoji' => '⚪', 'puntos' => 0];
}

$areas = [
    'Personal Social' => ['data' => parseRisk($riesgos['nivel_personal_social'] ?? ''), 'icon' => 'bi-people-fill'],
    'Salud Mental'    => ['data' => parseRisk($riesgos['nivel_salud_mental'] ?? ''),    'icon' => 'bi-heart-pulse-fill'],
    'Académico'       => ['data' => parseRisk($riesgos['nivel_academico'] ?? ''),       'icon' => 'bi-book-half'],
    'Vocacional'      => ['data' => parseRisk($riesgos['nivel_vocacional'] ?? ''),      'icon' => 'bi-compass-fill']
];
?>

<?php
// ... (Tus definiciones de $stats y la función parseRisk deben ir antes) ...

$alertas = []; // Inicializamos el array vacío

// --- ALERTA 1: RIESGO ACADÉMICO ALTO ---
// Usamos la misma función parseRisk para ser coherentes con las etiquetas de colores
$res_academico = parseRisk($riesgos['nivel_academico'] ?? '');
if ($res_academico['label'] === 'Alto Riesgo') {
    $alertas[] = "Alerta Académica: Se ha detectado un nivel de ALTO RIESGO. Por favor, coordina una asesoría con tu tutor.";
}

// --- ALERTA 2: RIESGO DE SALUD MENTAL ALTO ---
$res_salud = parseRisk($riesgos['nivel_salud_mental'] ?? '');
if ($res_salud['label'] === 'Alto Riesgo') {
    $alertas[] = "Atención: Tu nivel de riesgo en Salud Mental es ALTO. Se recomienda visitar al especialista.";
}
 
 // Alerta Académica (Solo para este ID de estudiante)
 $res_vocacional = parseRisk($riesgos['nivel_vocacional'] ?? '');
    if ($res_vocacional['label'] === 'Alto Riesgo') {
        $alertas[] = "Alerta vocacional: Tu nivel vocacional requiere atención inmediata.";
    }

    // Alerta Académica (Solo para este ID de estudiante)
    $res_personal = parseRisk($riesgos['nivel_personal_social'] ?? '');
    if ($res_personal['label'] === 'Alto Riesgo'){
        $alertas[] = "Alerta personal social: Tu nivel social requiere atención inmediata.";
    }
// --- ALERTA 3: CITAS PARA HOY ---
if (!empty($citasEspecialista)) {
    $hoy = date('Y-m-d');
    foreach ($citasEspecialista as $cita) {
        $fechaCita = date('Y-m-d', strtotime($cita['fecha_cita']));
        // Si la fecha de la cita es hoy y está programada
        if ($fechaCita === $hoy && strtolower($cita['estado']) === 'programada') {
            $alertas[] = "Recordatorio: Hoy tienes una cita programada con el especialista.";
        }
    }
}

// --- ALERTA 4: SESIONES VENCIDAS ---
if (isset($stats['pendientes']) && $stats['pendientes'] > 0) {
    $alertas[] = "Aviso: Tienes " . $stats['pendientes'] . " sesión(es) de tutoría vencidas o sin registrar.";
}
?>


<div class="container-fluid main-content">

    <!-- ENCABEZADO -->
    <header class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold h3 mb-1">Dashboard Estudiantil</h1>
            
        </div>
        <div class="col-md-4 text-md-end">
            <div class="badge bg-white shadow-sm text-dark p-2 px-3 border">
                <i class="bi bi-clock me-1 text-primary"></i> <?= date('d M, Y') ?>
            </div>
        </div>
    </header>

    <!-- KPIs PRINCIPALES -->
    <div class="row g-3 mb-4">
        <?php foreach ($kpis as $kpi): ?>
        <div class="col-6 col-md-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-<?= $kpi['color'] ?>-subtle text-<?= $kpi['color'] ?> me-3">
                            <i class="bi <?= $kpi['icon'] ?>"></i>
                        </div>
                        <span class="text-muted small fw-medium"><?= $kpi['label'] ?></span>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $kpi['val'] ?></h3>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- SECCIÓN IZQUIERDA: RIESGOS Y ALERTAS -->
        <div class="col-12 col-lg-8">

            <!-- Nivel de Riesgo -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-shield-exclamation text-danger me-2"></i>Niveles de Riesgo
                        Sugeridos</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <!-- Gráfico Radar -->
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div style="max-width: 280px; margin: auto;">
                                        <canvas id="riskRadarChart"></canvas>
                                    </div>
                                </div>

                                <!-- Lista de Detalles -->
                                <div class="col-md-6">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($areas as $titulo => $info): ?>
                                        <div class="list-group-item bg-transparent border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold text-dark small">
                                                    <i
                                                        class="bi <?= $info['icon'] ?> me-2 text-primary"></i><?= $titulo ?>
                                                </span>
                                                <span
                                                    class="badge rounded-pill bg-<?= $info['data']['color'] ?>-subtle text-<?= $info['data']['color'] ?> border border-<?= $info['data']['color'] ?> px-3">
                                                    <?= $info['data']['emoji'] ?> <?= $info['data']['label'] ?>
                                                </span>
                                            </div>
                                            <!-- Mini barra de progreso decorativa -->
                                            <div class="progress" style="height: 4px; background-color: #eee;">
                                                <div class="progress-bar bg-<?= $info['data']['color'] ?>"
                                                    style="width: <?= ($info['data']['puntos'] / 3) * 100 ?>%">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas del Sistema -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-bell text-warning me-2"></i>Alertas</h5>
                </div>
                <div class="card-body px-4">
                    <?php if (!empty($alertas)): ?>
                    <?php foreach ($alertas as $a): ?>
                    <div
                        class="alert alert-warning border-0 bg-warning-subtle d-flex align-items-start rounded-3 mb-3 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-3 fs-5 text-warning"></i>
                        <div>
                            <span class="small fw-bold d-block text-dark">Acción Requerida</span>
                            <span class="small text-dark"><?= htmlspecialchars($a) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="bg-success-subtle d-inline-block p-3 rounded-circle mb-3">
                            <i class="bi bi-check-circle text-success fs-3"></i>
                        </div>
                        <p class="text-muted fw-medium mb-0">¡Todo en orden! No tienes alertas pendientes esta semana.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DERECHA: PRÓXIMA CITA Y ESPECIALISTAS -->
        <div class="col-12 col-lg-4">

            <!-- Próxima Cita -->
            <div class="card next-event-card shadow-lg mb-4">
                <div class="card-body p-4">
                    <?php if ($proximaCita): ?>
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="bg-white bg-opacity-25 p-2 rounded-3">
                            <i class="bi <?= $proximaCita['icono'] ?? 'bi-calendar-check' ?> fs-4"></i>
                        </div>
                        <span class="badge bg-light text-primary fw-bold">PRÓXIMA SESIÓN</span>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($proximaCita['tipo']) ?></h4>
                    <p class="small text-white-50 mb-4"><?= htmlspecialchars($proximaCita['objetivo']) ?></p>

                    <div class="d-flex gap-3 border-top border-white border-opacity-10 pt-3">
                        <div>
                            <small class="d-block text-white-50 small">Fecha</small>
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($proximaCita['fecha'])) ?></span>
                        </div>
                        <div class="vr opacity-25"></div>
                        <div>
                            <small class="d-block text-white-50 small">Hora</small>
                            <span class="fw-bold"><?= date('h:i A', strtotime($proximaCita['hora'])) ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-calendar-check fs-1 opacity-25"></i>
                        <p class="mt-2 mb-0">Sin actividades programadas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Citas Especialistas -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card unamba-card mb-4">
                    
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-box">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <h6 class="unamba-title fw-bold mb-0"> Cita de psicopedagogía</h6>
                        </div>

                             <?php 
                            $citas_prog = array_filter(
                                $citasEspecialista ?? [],
                                fn($c) => strtolower($c['estado']) === 'programada' 
                            );

                            if (!empty($citas_prog)): 
                                foreach ($citas_prog as $cita):
                            ?>
                        <div class="cita-item mb-3">
                            <div class="cita-motivo text-truncate">
                                <?= htmlspecialchars($cita['motivo'] ?? 'Cita programada') ?>
                            </div>

                            <div class="cita-meta mb-2">
                                <span><i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y', strtotime($cita['fecha_cita'])) ?></span>
                                <span><i class="bi bi-clock me-1"></i>
                                    <?= date('h:i A', strtotime($cita['hora_cita'])) ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge-modalidad">
                                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($cita['modalidad']) ?>
                                </span>
                                
                            </div>
                        </div>
                        <?php 
                                endforeach;
                            else:
                            ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-calendar-x opacity-25" style="font-size: 3rem; color: #003366;"></i>
                            </div>
                            <p class="text-muted small">No hay citas médicas o psicológicas pendientes.</p>
                        </div>
                        <?php endif; ?>
                    
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('riskRadarChart');
    if (!ctx) return;

    // Pasamos los datos de PHP a JS de forma segura (Formato JSON)
    const puntosRiesgo = [
        <?= (int)$areas['Personal Social']['data']['puntos'] ?>,
        <?= (int)$areas['Salud Mental']['data']['puntos'] ?>,
        <?= (int)$areas['Académico']['data']['puntos'] ?>,
        <?= (int)$areas['Vocacional']['data']['puntos'] ?>
    ];

    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Personal', 'Salud', 'Académico', 'Vocacional'],
            datasets: [{
                label: 'Nivel de Riesgo',
                data: puntosRiesgo,
                backgroundColor: 'rgba(0, 51, 102, 0.2)',
                borderColor: '#003366',
                borderWidth: 3,
                pointBackgroundColor: '#FFC107',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 3,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        display: false
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    pointLabels: {
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
<style>
/* Estilos específicos para seguir la línea de la imagen */
.unamba-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    background: #fff;
}

.unamba-title {
    color: #003366;
    /* Azul Marino UNAMBA */
    font-size: 1.1rem;
    letter-spacing: -0.02em;
}

.cita-item {
    background-color: #f8f9fa;
    border: none;
    border-left: 5px solid #0d6efd;
    /* El acento azul de la imagen */
    border-radius: 12px;
    transition: all 0.3s ease;
    padding: 15px;
}

.cita-item:hover {
    background-color: #f1f4f9;
    transform: translateX(5px);
}

.cita-motivo {
    color: #1a202c;
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 4px;
}

.cita-meta {
    color: #718096;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge-modalidad {
    background-color: #e0efff;
    color: #0d6efd;
    font-weight: 600;
    font-size: 0.7rem;
    padding: 4px 10px;
    border-radius: 8px;
    text-transform: uppercase;
}

.icon-box {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin-right: 12px;
}
</style>
<?php require __DIR__ . '/../layouts/footer.php'; ?>