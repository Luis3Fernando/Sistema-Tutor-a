<?php 
require __DIR__ . '/../layouts/header.php'; 
require __DIR__ . '/../layouts/sidebar.php'; 

// Cálculos rápidos para indicadores
$total = count($sesiones);
$asistencias = count(array_filter($sesiones, fn($s) => $s['asistencia'] == 1));
$faltas = $total - $asistencias;
?>

<!-- DataTables & Google Fonts -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">



<div class="container-fluid px-4 py-4">

    <!-- BANNER DE IDENTIFICACIÓN -->
    <div class="info-banner d-flex justify-content-between align-items-center shadow-sm">
        <div>
            <h4 class="mb-1 fw-bold">Seguimiento de Tutoría Universitaria</h4>
            <p class="mb-0 opacity-75">Gestión académica y registro de participación del estudiante</p>
        </div>
        <div class="text-end">
            <div class="small opacity-75">Ciclo Académico</div>
            <span><?= htmlspecialchars($estudiante['periodo_academico'] ?? 'No Asignado') ?></span>
        </div>
    </div>

    <!-- KPI ROW (Resumen rápido) -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="kpi-card shadow-sm">
                <div class="kpi-icon bg-light-primary text-primary" style="background: #e7f1ff;"><i
                        class="bi bi-calendar-event"></i></div>
                <div>
                    <div class="text-muted small">Total Sesiones</div>
                    <div class="fw-bold fs-4"><?= $total ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card shadow-sm">
                <div class="kpi-icon text-success" style="background: #e6fcf5;"><i class="bi bi-check-all"></i></div>
                <div>
                    <div class="text-muted small">Asistencias</div>
                    <div class="fw-bold fs-4"><?= $asistencias ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card shadow-sm">
                <div class="kpi-icon text-danger" style="background: #fff5f5;"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="text-muted small">Inasistencias</div>
                    <div class="fw-bold fs-4"><?= $faltas ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- NAVEGACIÓN POR PESTAÑAS -->
    <ul class="nav nav-custom" id="tutoriaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="proximas-tab" data-bs-toggle="tab" data-bs-target="#proximas"
                type="button" role="tab">
                <i class="bi bi-clock-history me-2"></i>Sesiones Programadas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button"
                role="tab">
                <i class="bi bi-journal-text me-2"></i>Historial de Ejecución
            </button>
        </li>


    </ul>

    <!-- CONTENIDO DE LAS PESTAÑAS -->
    <div class="tab-content" id="myTabContent">

        <!-- PESTAÑA 1: PRÓXIMAS ACTIVIDADES -->
        <div class="tab-pane fade show active" id="proximas" role="tabpanel">
            <div class="table-container shadow-sm p-3 bg-white">

                <div class="table-responsive">
                    <!-- SE AGREGÓ ID tablaPlanificacion -->
                    <table id="tablaPlanificacion" class="table custom-table table-hover mb-0 w-100">
                        <thead>
                            <tr>
                                <th class="ps-4">Fecha y Hora</th>
                                <th>Tipo de Actividad</th>
                                <th>Objetivo Específico</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if(!empty($actividades)): ?>

                            <?php 
                                $hoy = date('Y-m-d');

                                foreach($actividades as $a): 

                                    $fecha = date('Y-m-d', strtotime($a['fecha']));
                                    $estado = $a['estado'];
                                ?>
                            <tr class="align-middle">

                                <!-- Fecha -->
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">
                                        <?= date('d/m/Y', strtotime($a['fecha'])) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= date('H:i A', strtotime($a['hora'])) ?>
                                    </div>
                                </td>

                                <!-- Tipo -->
                                <td>
                                    <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2">
                                        <?= htmlspecialchars($a['actividad_tipo']) ?>
                                    </span>
                                </td>

                                <!-- Objetivo -->
                                <td class="text-muted small">
                                    <?= htmlspecialchars($a['objetivo_especifico']) ?>
                                </td>

                                <!-- Estado Inteligente -->
                                <td class="text-center">

                                    <?php if($estado === 'Realizado'): ?>

                                    <span class="badge-status bg-success text-white">
                                        Realizada
                                    </span>

                                    <?php elseif($estado === 'Programada' && $fecha >= $hoy): ?>

                                    <span class="badge-status bg-warning text-dark">
                                        Programada
                                    </span>

                                    <?php elseif($estado === 'Programada' && $fecha < $hoy): ?>

                                    <span class="badge-status bg-danger text-white">
                                        Pendiente
                                    </span>

                                    <?php else: ?>

                                    <span class="badge-status bg-secondary text-white">
                                        <?= htmlspecialchars($estado) ?>
                                    </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: HISTORIAL DETALLADO -->
        <div class="tab-pane fade" id="historial" role="tabpanel">
            <div class="table-container shadow-sm p-4 bg-white">
                <table id="tablaSesiones" class="table custom-table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Objetivo de la Sesión</th>
                            <th>Asistencia</th>
                            <th class="text-center">Documento</th>
                            <th class="text-center">Evidencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sesiones as $s): ?>
                        <tr class="align-middle">
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($s['fecha_ejecucion'])) ?></td>
                            <td class="small text-muted">
                                <?= date('H:i', strtotime($s['hora_inicio'])) ?> -
                                <?= date('H:i', strtotime($s['hora_fin'])) ?>
                            </td>
                            <td>
                                <div class="text-wrap small" style="max-width: 350px;">
                                    <?= htmlspecialchars($s['objetivo_sesion']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if($s['asistencia'] == 1): ?>
                                <span class="badge-status bg-success text-white"><i class="bi bi-check2"></i>
                                    Presente</span>
                                <?php else: ?>
                                <span class="badge-status bg-danger text-white"><i class="bi bi-x"></i> Ausente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if(!empty($s['archivo_evidencia'])): ?>
                                <a href="<?= $s['archivo_evidencia'] ?>" target="_blank" class="btn btn-pdf shadow-sm">
                                    <i class="bi bi-file-pdf me-1"></i> Ver Guía
                                </a>
                                <?php else: ?>
                                <span class="text-muted small">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column gap-2 align-items-center">

                                    <!-- 2. Evidencia del Estudiante (Su tarea) -->
                                    <?php if(!empty($s['archivo_estudiante'])): ?>
                                    <a href="<?= $s['archivo_estudiante'] ?>" target="_blank"
                                        class="btn btn-sm btn-outline-success w-100 shadow-sm">
                                        <i class="bi bi-check-circle-fill me-1"></i> Mi Entrega
                                    </a>
                                    <?php else: ?>
                                     <button type="button" 
            class="btn btn-sm btn-primary w-100 shadow-sm" 
            onclick="abrirModalSubida(<?= $s['id_sesion'] ?>, '<?= date('d/m/Y', strtotime($s['fecha_ejecucion'])) ?>')">
        <i class="bi bi-cloud-upload me-1"></i> Subir Mi Evidencia
    </button>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para que el estudiante suba su archivo -->
<div class="modal fade" id="modalSubirEvidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: var(--primary-navy);">
                <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Cargar Evidencia del Estudiante <span id="modal_fecha_texto"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?route=estudiante/guardarEvidencia" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_sesion" id="modal_id_sesion">
                <input type="file" name="archivo_estudiante" accept=".pdf,.jpg,.png" required>
                <button type="submit">Enviar Evidencia</button>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>



<script>
$(document).ready(function() {
    // Configuración común de idioma
    var languageConfig = {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    };

    // Inicializar DataTable: Planificación (Pestaña 1)
    $('#tablaPlanificacion').DataTable({
        language: languageConfig,
        pageLength: 5,
        order: [
            [0, 'asc']
        ], // Ordenar por fecha ascendente
        dom: '<"d-flex justify-content-between align-items-center mb-3"f>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
    });

    // Inicializar DataTable: Historial (Pestaña 2)
    $('#tablaSesiones').DataTable({
        language: languageConfig,
        pageLength: 5,
        order: [
            [0, 'desc']
        ],
        dom: '<"d-flex justify-content-between align-items-center mb-3"f>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
    });



    // Reajustar columnas al cambiar de pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(event) {
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
    });
});

function abrirModalSubida(id, fecha) {
    $('#modal_id_sesion').val(id);
    $('#modal_fecha_texto').text(fecha);
    $('#modalSubirEvidencia').modal('show');
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>