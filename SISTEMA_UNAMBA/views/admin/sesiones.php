<?php 
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../layouts/sidebar.php'; 

// Helpers locales
function getEstadoBadge($estado) {
    return $estado === 'Pendiente' 
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2"><i class="fas fa-clock me-1"></i> PENDIENTE</span>'
        : '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="fas fa-check-double me-1"></i> CERRADO</span>';
}
?>

<!-- DataTables CSS e Inter Font ya deben estar en tu Header Global, si no, déjalos -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="content"> <!-- Usamos 'content' que ya tiene el fix de margen en style.css -->
    <div class="container-fluid py-2">

        <!-- Header Principal -->
        <div class="card bg-unamba-premium p-4 mb-4 border-0 shadow-lg">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h2 class="fw-bold mb-1">Centro de Control y Fiscalización</h2>
                    <p class="opacity-75 mb-0">Gestión Integral de Sesiones y Derivaciones</p>
                </div>
                <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-block text-center me-4">
                        <h3 class="fw-bold mb-0"><?= count($sesiones) ?></h3>
                        <small class="opacity-75">Sesiones</small>
                    </div>
                    <div class="d-inline-block text-center text-warning">
                        <h3 class="fw-bold mb-0"><?= count(array_filter($derivaciones, fn($d) => $d['estado_atencion'] === 'Pendiente')) ?></h3>
                        <small class="opacity-75">Alertas Críticas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación por Pestañas -->
        <ul class="nav nav-pills nav-pills-premium mb-4 gap-2" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-sesiones-tab" data-bs-toggle="pill" data-bs-target="#pills-sesiones" type="button">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Auditoría de Sesiones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-derivaciones-tab" data-bs-toggle="pill" data-bs-target="#pills-derivaciones" type="button">
                    <i class="fas fa-hospital-user me-2"></i>Gestión de Derivaciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <!-- PESTAÑA 1: AUDITORÍA DE SESIONES -->
            <div class="tab-pane fade show active" id="pills-sesiones" role="tabpanel">
                <div class="card card-senior p-4 shadow-sm">
                    <div class="table-responsive">
                        <table id="tablaSesiones" class="table table-hover align-middle">
                            <thead class="table-thead-senior">
                                <tr>
                                    <th>Tutor / Escuela</th>
                                    <th>Fecha</th>
                                    <th>Objetivo</th>
                                    <th class="text-center">Asistencia</th>
                                    <th>Evidencia</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach ($sesiones as $s): 
                                    $hasEvidence = !empty($s['archivo_evidencia']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3"><?= substr($s['tutor_nombre'], 0, 1) ?></div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= $s['tutor_nombre'] ?></div>
                                                <div class="text-primary text-xs-caps"><?= $s['nombre_escuela'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold"><?= date('d/m/Y', strtotime($s['fecha_ejecucion'])) ?></td>
                                    <td class="text-muted" style="max-width: 200px;"><?= $s['objetivo_sesion'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $s['total_asistentes'] ?> Alumnos</span>
                                    </td>
                                    <td>
                                        <?= $hasEvidence 
                                            ? '<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Recibido</span>' 
                                            : '<span class="text-danger fw-bold pulse-red"><i class="fas fa-exclamation-triangle"></i> Pendiente</span>' ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($hasEvidence): ?>
                                            <a href="<?= $s['archivo_evidencia'] ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 2: GESTIÓN DE DERIVACIONES -->
            <div class="tab-pane fade" id="pills-derivaciones" role="tabpanel">
                <div class="card card-senior p-4 shadow-sm">
                    <div class="table-responsive">
                        <table id="tablaDerivaciones" class="table table-hover align-middle">
                            <thead class="table-thead-senior">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Área Destino</th>
                                    <th>Motivo</th>
                                    <th>Fecha Envío</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach ($derivaciones as $d): 
                                    $isPending = ($d['estado_atencion'] === 'Pendiente');
                                ?>
                                <tr class="<?= $isPending ? 'border-left-danger' : '' ?>">
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($d['est_nombres'] . ' ' . $d['est_apellidos']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($d['codigo_unamba']) ?></div>
                                    </td>
                                    <td>
                                        
                                    <span class="badge bg-unamba-blue text-xs-caps text-dark px-2 py-1"><?= htmlspecialchars($d['area_destino']) ?></span>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($d['motivo_informe']) ?></div>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt me-1"></i> <?= date('d/m/y H:i', strtotime($d['fecha_derivacion'])) ?>
                                    </td>
                                    <td><?= getEstadoBadge($d['estado_atencion']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- Fin tab-content -->
    </div> <!-- Fin container-fluid -->
</div> <!-- Fin content -->

<!-- Scripts de Inicialización -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    const configDT = {
        "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "pageLength": 10,
        "order": [[1, 'desc']], // Ordenar por fecha
        "responsive": true
    };

    $('#tablaSesiones').DataTable(configDT);
    $('#tablaDerivaciones').DataTable(configDT);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>