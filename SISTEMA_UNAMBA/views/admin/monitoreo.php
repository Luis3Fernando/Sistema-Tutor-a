<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/helpers/Auth.php';

Auth::requireAuth();
if (Auth::role() !== 'administrador') {
    header('Location: index.php?route=login');
    exit;
}

/**
 * Helpers de lógica de vista (Business Logic)
 */
function tiempoRelativo($fecha) {
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;
    if ($diferencia < 60) return "Justo ahora";
    if ($diferencia < 3600) return round($diferencia / 60) . " min";
    if ($diferencia < 86400) return round($diferencia / 3600) . " horas";
    return date('d/m', $timestamp);
}

function calcularProgreso($inicio, $fin) {
    $start = strtotime($inicio);
    $end = strtotime($fin);
    $now = time();
    if ($now >= $end) return 100;
    if ($now <= $start) return 0;
    $total = $end - $start;
    $passed = $now - $start;
    return (int)round(($passed / $total) * 100);
}
?>

<!-- Estilos Senior con Paleta Institucional Completa -->
<style>
:root {
    --unamba-blue: #003a6d;
    /* Azul Universitario (Autoridad) */
    --unamba-green: #006837;
    /* Verde UNAMBA (Éxito/Acción) */
    --unamba-gold: #f9b233;
    /* Oro (Acentos) */
    --bs-primary: #003a6d;
}

body {
    background-color: #f4f7f9;
    font-family: 'Inter', sans-serif;
    color: #334155;
}

/* Utilidades de Color */
.bg-unamba-blue {
    background-color: var(--unamba-blue) !important;
    color: white;
}

.bg-unamba-green {
    background-color: var(--unamba-green) !important;
    color: white;
}

.text-unamba-blue {
    color: var(--unamba-blue) !important;
}

.border-unamba-blue {
    border-color: var(--unamba-blue) !important;
}

/* Refinamiento de Cards */
.card-monitoring {
    border: none;
    border-radius: 1.25rem;
    box-shadow: 0 0.4rem 0.8rem rgba(0, 58, 109, 0.05);
}

.table-thead-blue {
    background-color: #f1f5f9;
    border-bottom: 2px solid var(--unamba-blue);
}

/* Timeline Institucional */
.timeline-widget {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.8rem;
    border-left: 2px solid #e2e8f0;
    padding-left: 1.5rem;
}

.timeline-item:last-child {
    border-left-color: transparent;
}

.timeline-dot {
    position: absolute;
    left: -0.55rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--unamba-blue);
}

/* Avatar Soft Style */
.avatar-unamba {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--unamba-blue), #0056a3);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    box-shadow: 0 4px 6px rgba(0, 58, 109, 0.2);
}

/* Pulse Green */
.pulse-status {
    width: 10px;
    height: 10px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
    animation: pulse-blue 2s infinite;
}

@keyframes pulse-blue {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }

    70% {
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

/* Timeline Crítico (Rojo) */
.timeline-item-critical {
    position: relative;
    padding-bottom: 1.5rem;
    border-left: 2px solid #fee2e2;
    /* Rojo muy claro */
    padding-left: 1.5rem;
}

.timeline-item-critical:last-child {
    border-left-color: transparent;
}

.timeline-dot-critical {
    position: absolute;
    left: -0.55rem;
    top: 0;
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 50%;
    background: #ef4444;
    /* Rojo Danger */
    border: 3px solid #fee2e2;
    box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
    animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }

    70% {
        box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col ps-md-4 pt-4 pb-5">

            <!-- Header Superior -->
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h1 class="h3 fw-bold text-unamba-blue mb-0">Centro de Monitoreo</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item small">Administración</li>
                            <li class="breadcrumb-item small active text-unamba-blue fw-semibold" aria-current="page">
                                Control en tiempo real</li>
                        </ol>
                    </nav>
                </div>

                <div class="d-flex align-items-center mt-3 mt-md-0">
                    <div class="text-end me-4">
                        <div class="fs-4 fw-black text-unamba-blue" id="live-clock"
                            style="font-variant-numeric: tabular-nums;"><?= date('H:i:s') ?></div>
                        <div class="text-muted small fw-bold text-uppercase ls-wide" style="font-size: 0.65rem;">
                            <?= date('d F, Y') ?></div>
                    </div>
                    <div class="vr text-secondary opacity-25 me-4" style="height: 45px;"></div>
                    <div class="px-3 py-2 bg-light border rounded-pill d-flex align-items-center">
                        <span class="pulse-status"></span>
                        <span class="small fw-bold text-unamba-green">SISTEMA EN LÍNEA</span>
                    </div>
                </div>
            </div>

            <!-- KPIs Rápidos -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-monitoring p-3 border-start border-unamba-blue border-4">
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Sesiones del Día
                        </div>
                        <div class="d-flex align-items-center">
                            <h2 class="fw-black mb-0 me-2"><?= count($activeSessions) ?></h2>
                            <i class="fas fa-video text-light-emphasis ms-auto fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-monitoring p-3 border-start border-unamba-green border-4">
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Tutores Online
                        </div>
                        <div class="d-flex align-items-center">
                            <h2 class="fw-black mb-0 text-unamba-green"><?= count($onlineUsers) ?></h2>
                            <i class="fas fa-user-check text-light-emphasis ms-auto fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-monitoring p-3 border-start border-danger border-4">
                        <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Alertas Críticas
                            Derivaciones
                        </div>
                        <div class="d-flex align-items-center">
                            <h2 class="fw-black mb-0 text-danger"><?= count($pendingDerivations) ?></h2>
                            <i class="fas fa-bell text-light-emphasis ms-auto fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-monitoring p-3 bg-unamba-blue text-white shadow-lg">
                        <div class="text-white-50 fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Semestre
                            Actual</div>
                        <div class="d-flex align-items-center">

                            <h2 class="fw-black mb-0">
                                <?= htmlspecialchars($periodos[0] ?? 'Sin período') ?></h2>

                            <i class="fas fa-university text-white-50 ms-auto fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Columna: Monitor de Sesiones -->
                <div class="col-12 col-lg-8">
                    <div class="card card-monitoring">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold text-unamba-blue mb-0">Control de Actividades: Plan vs. Ejecución</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-thead-blue">
                                    <tr class="small fw-bold text-muted">
                                        <th class="ps-4">TUTOR / ESCUELA</th>
                                        <th>ACTIVIDAD PROGRAMADA</th>
                                        <th>HORA PLAN</th>
                                        <th>ESTADO REAL</th>
                                        <th class="text-end pe-4">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeSessions as $a): 
                                    $estado = $a['estado_monitor'];
                                    
                                    // Configuración visual por estado
                                    $badgeConfig = [
                                        'REALIZADA'   => ['class' => 'bg-success', 'icon' => 'fa-check-circle', 'text' => 'COMPLETADA'],
                                        'EN EJECUCION' => ['class' => 'bg-unamba-green', 'icon' => 'fa-spinner fa-spin', 'text' => 'EN VIVO'],
                                        'RETRASADA'   => ['class' => 'bg-danger', 'icon' => 'fa-exclamation-triangle', 'text' => 'RETRASO'],
                                        'PROGRAMADA'  => ['class' => 'bg-unamba-blue', 'icon' => 'fa-calendar', 'text' => 'PRÓXIMA'],
                                        'PENDIENTE'   => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock', 'text' => 'PENDIENTE']
                                    ];
                                    
                                    $cfg = $badgeConfig[$estado] ?? $badgeConfig['PENDIENTE'];
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-unamba me-3"><?= substr($a['tutor_nombre'], 0, 1) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0 small"><?= $a['tutor_nombre'] ?>
                                                        <?= $a['tutor_apellido'] ?></div>
                                                    <div class="text-unamba-blue fw-bold uppercase"
                                                        style="font-size: 0.6rem;"><?= $a['nombre_escuela'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-secondary small"><?= $a['actividad_tipo'] ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                <?= $a['objetivo_especifico'] ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                                <?= date('Y-m-d H:i:s', strtotime($a['hora_plan'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge <?= $cfg['class'] ?> d-inline-flex align-items-center px-3 py-2">
                                                <?php if($estado == 'EN EJECUCION'): ?>
                                                <span class="pulse-status me-2"></span>
                                                <?php else: ?>
                                                <i class="fas <?= $cfg['icon'] ?> me-2"></i>
                                                <?php endif; ?>
                                                <?= $cfg['text'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if($a['id_sesion']): ?>
                                            <a href="index.php?route=ver-sesion&id=<?= $a['id_sesion'] ?>"
                                                class="btn btn-sm btn-outline-primary rounded-pill">
                                                Ver Evidencia
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-light rounded-pill text-muted" disabled>
                                                Sin registro
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Alertas Críticas de Derivación (Estilo Actividades) -->
                    <div class="card card-monitoring border-0 shadow-sm mt-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="fw-bold text-danger mb-0">
                                        <i class="fas fa-exclamation-circle me-2"></i>Atenciones Críticas Pendientes
                                    </h5>
                                    <p class="text-muted small mb-0">Derivaciones que requieren intervención inmediata
                                    </p>
                                </div>
                                <span class="badge bg-danger rounded-pill px-3">
                                    <?= count($pendingDerivations) ?> alertas
                                </span>
                            </div>

                            <div class="timeline-widget mt-2">
                                <?php if (!empty($pendingDerivations)): ?>
                                <?php foreach ($pendingDerivations as $d): ?>
                                <div class="timeline-item-critical">
                                    <div class="timeline-dot-critical"></div>

                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?= htmlspecialchars($d['nombres']) ?>
                                                <?= htmlspecialchars($d['apellidos']) ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <span class="badge bg-light text-dark border me-1">Cód:
                                                    <?= htmlspecialchars($d['codigo_unamba']) ?></span>
                                                <span class="text-danger fw-semibold">Esperando atención
                                                    especializada</span>
                                            </div>

                                            <!-- Botón de acción rápida -->
                                            <div class="mt-2">
                                                <a href="index.php?route=admin/sesiones&id=<?= $d['id_derivacion'] ?>"
                                                    class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold"
                                                    style="font-size: 0.7rem;">
                                                    Gestionar Caso <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <span class="text-danger fw-bold d-block" style="font-size: 0.65rem;">
                                                <i class="far fa-clock"></i>
                                                <?= tiempoRelativo($d['fecha_derivacion']) ?>
                                            </span>
                                            <span class="text-muted" style="font-size: 0.6rem;">Hace instantes</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="avatar-unamba bg-light text-success mx-auto mb-3"
                                        style="width: 60px; height: 60px;">
                                        <i class="fas fa-check-circle fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">¡Todo bajo control!</h6>
                                    <p class="text-muted small">No hay derivaciones críticas pendientes de atención.</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (count($pendingDerivations) > 0): ?>
                            <div class="d-grid mt-3">
                                
                                <a href="index.php?route=admin/sesiones&estado=pendiente" 
   class="btn btn-link btn-sm text-danger fw-bold">
   Ver todas las alertas <i class="fas fa-chevron-right ms-1"></i>
</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna: Derecha -->
                <div class="col-12 col-lg-4">

                    <!-- Actividad Reciente (Azul Universitario) -->
                    <div class="card card-monitoring mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-unamba-blue mb-0">Derivaciones Recientes</h5>
                                <span class="badge bg-unamba-blue small px-2 py-1">Historial</span>
                            </div>
                            <div class="timeline-widget">
                                <?php foreach ($recentActivity as $a): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="d-flex justify-content-between">
                                        <p class="small text-dark mb-0 pe-2">

                                            <span class="fw-bold text-unamba-blue">
                                                <?= htmlspecialchars($a['responsable']) ?>
                                            </span>

                                            <span class="text-secondary">
                                                <?= htmlspecialchars($a['accion']) ?>
                                            </span>

                                            <span class="fw-semibold text-primary">
                                                para <?= htmlspecialchars($a['estudiante']) ?>
                                            </span>

                                        </p>
                                        <span class="text-muted fw-bold"
                                            style="font-size: 0.6rem; white-space: nowrap;">
                                            <i class="far fa-clock"></i> <?= tiempoRelativo($a['fecha']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-grid mt-2">
                                <a href="index.php?route=admin/sesiones" 
   class="btn btn-link btn-sm text-unamba-blue text-decoration-none fw-bold">
   Ver todo el registro <i class="fas fa-chevron-right ms-1"></i>
</a>
                               
                            </div>
                        </div>
                    </div>

                    <!-- Personal Online -->
                    <div class="card card-monitoring shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-unamba-blue mb-4">Usuarios Conectados</h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($onlineUsers as $u): ?>
                                <div
                                    class="list-group-item px-0 border-0 mb-3 d-flex justify-content-between align-items-center bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nombres']) ?>&background=003a6d&color=fff&bold=true"
                                                class="rounded-circle shadow-sm" width="38" height="38">
                                            <span
                                                class="position-absolute bottom-0 end-0 p-1 bg-unamba-green border border-2 border-white rounded-circle"></span>
                                        </div>
                                        <div class="ms-3">
                                            <div class="fw-bold text-dark small mb-0"><?= $u['nombres'] ?></div>
                                            <div class="text-muted fw-bold text-uppercase"
                                                style="font-size: 0.55rem; letter-spacing: 0.5px;"><?= $u['rol'] ?>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="https://wa.me/<?= $u['celular'] ?>" target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-outline-success btn-sm rounded-circle border-0">
                                        <i class="fab fa-whatsapp fs-5">Whatsapp</i>
                                    </a>

                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Reloj Institucional con Formato Senior
function updateClock() {
    const now = new Date();
    const options = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    const timeString = now.toLocaleTimeString('es-PE', options);
    const clockElement = document.getElementById('live-clock');
    if (clockElement) clockElement.textContent = timeString;
}
setInterval(updateClock, 1000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>