<?php 

require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../layouts/sidebar.php'; 
?>

<!-- ESTILOS PROFESIONALES SENIOR -->
<style>
:root {
    --unamba-blue: #003366;
    --unamba-gold: #050446;
    --bg-light: #f8f9fa;
}

.main-content {
    background-color: #f4f7f6;
    min-height: 100vh;
    padding: 20px;
    width: 100%;
    /* Asegura que ocupe el ancho total */
    max-width: 100%;
    /* Elimina cualquier límite de ancho */
}

.card-senior {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
    background: #ffffff;
}

.nav-pills .nav-link {
    color: #555;
    font-weight: 600;
    border-radius: 8px;
    padding: 10px 20px;
}

.nav-pills .nav-link.active {
    background-color: var(--unamba-blue);
    color: white;
}



.diagnostic-reference {
    background-color: #fff9e6;
    border-left: 4px solid var(--unamba-gold);
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

.form-section-title {
    background: #e9ecef;
    border-left: 5px solid var(--unamba-blue);
    padding: 8px 15px;
    font-weight: 700;
    color: var(--unamba-blue);
    text-transform: uppercase;
    font-size: 0.75rem;
    margin-top: 20px;
    margin-bottom: 15px;
}

.label-senior {
    font-weight: 700;
    color: #343a40;
    font-size: 0.85rem;
    margin-bottom: 5px;
    display: block;
}

.timeline-card {
    border-left: 4px solid var(--unamba-blue);
    transition: transform 0.2s;
}

.timeline-card:hover {
    transform: translateX(5px);
}

.badge-status {
    padding: 5px 12px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.7rem;
}
</style>

<style>
.compare-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.student-answer-preview {
    background: #fff9e6;
    border-radius: 8px;
    padding: 12px;
    font-size: 0.85rem;
    border: 1px dashed var(--unamba-gold);
}

.tutor-note-area {
    border-left: 3px solid var(--unamba-blue);
}

.badge {
    font-size: 13px;
    border-radius: 20px;
    letter-spacing: .5px;
}

.btn-warning {
    background-color: var(--unamba-blue) !important;
    border-color: var(--unamba-blue) !important;
}

.bg-unamba {
    background-color: #003366 !important;
}

.nav-tabs-senior .nav-link {
    border: 1px solid #dee2e6;
    margin: 0 5px;
}
</style>

<div class="main-content printable-area">
    <!-- Bloque de Mensajes de Éxito/Error -->
    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert"
        style="border-left: 5px solid #198754 !important;">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>
                <strong>¡Excelente!</strong> <?= $_SESSION['flash_success']; ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert"
        style="border-left: 5px solid #dc3545 !important;">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Error:</strong> <?= $_SESSION['flash_error']; ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- ENCABEZADO: PERFIL DEL ESTUDIANTE -->
    <div class="card card-senior border-0">
        <div class="card-body d-flex align-items-center py-3">
            <div class="flex-shrink-0">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm"
                    style="width: 65px; height: 65px; font-size: 1.5rem; font-weight: bold;">
                    <?= substr($estudiante['nombres'], 0, 1) . substr($estudiante['apellidos'], 0, 1) ?>
                </div>
            </div>
            <div class="ms-4">
                <h4 class="mb-1 fw-bold text-dark">
                    <?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></h4>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="bi bi-card-text me-1"></i> <strong>Código:</strong>
                        <?= $estudiante['codigo_unamba'] ?></span>
                    <span><i class="bi bi-mortarboard me-1"></i> <strong>Escuela:</strong>
                        <?= $estudiante['nombre_escuela'] ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i> <strong>Ciclo:</strong>
                        <?= $estudiante['ciclo_actual'] ?>°</span>
                </div>
            </div>
            <div class="ms-auto">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>Imprimir Ficha
                </button>
            </div>
        </div>
    </div>

    <!-- TABS DE NAVEGACIÓN -->


    <div class="card-header bg-transparent border-0 pt-4 px-4">

        <ul class="nav nav-pills nav-fill nav-tabs-senior" id="pills-tab" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-seguimiento" type="button"
                    role="tab">

                    <i class="bi bi-journal-check me-2"></i>
                    Seguimiento Individual

                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-derivacion" type="button"
                    role="tab">

                    <i class="bi bi-box-arrow-up-right me-2"></i>
                    Derivación

                </button>
            </li>

        </ul>

    </div>



    <div class="tab-content" id="pills-tabContent">

        <!-- PESTAÑA 1: SEGUIMIENTO INDIVIDUAL -->
        <div class="tab-pane fade show active" id="tab-seguimiento" role="tabpanel">

            <form method="POST" action="index.php?route=tutor/guardarSeguimiento">
                <input type="hidden" name="id_estudiante" value="<?= $estudiante['id_usuario'] ?>">

                <div class="modal-body bg-light" style="max-height: 80vh; overflow-y: auto;">


                    <!-- SECCIÓN 1: COMPARATIVA PERSONAL/SOCIAL -->
                    <div class="form-section-title">1. Área Personal y Social</div>

                    <div class="row g-4 mb-4">

                        <!-- COLUMNA IZQUIERDA -->
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted uppercase">
                                Diagnóstico del Alumno:
                            </label>

                            <div class="student-answer-preview shadow-sm">
                                <p><strong>¿Cómo te sientes en tu entorno universitario?</strong>
                                    <?= htmlspecialchars($diagnostico['p_entorno_uni'] ?? 'Sin respuesta') ?></p>

                                <p><strong>¿Sientes apoyo de tus compañeros y profesores?</strong>
                                    <?= htmlspecialchars($diagnostico['p_apoyo_social'] ?? 'Sin respuesta') ?></p>

                                <p><strong>¿Cómo manejas el estrés?</strong>
                                    <?= htmlspecialchars($diagnostico['p_manejo_estres'] ?? 'Sin respuesta') ?></p>

                                <p><strong>¿Has tenido problemas de integración o aislamiento?</strong>
                                    <?= htmlspecialchars($diagnostico['p_integracion'] ?? 'Sin respuesta') ?></p>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA -->
                        <div class="col-md-7 tutor-note-area">

                            <label class="question-label">
                                Seguimiento y Análisis del Tutor:
                            </label>

                            <textarea name="seguimiento_personal_social" class="form-control mb-3" rows="4" required
                                placeholder="¿Qué cambios has notado? ¿Cómo ha evolucionado su integración?"> <?= htmlspecialchars($seguimiento['seguimiento_personal_social'] ?? '') ?> </textarea>

                            <!-- NIVEL -->
                            <label class="fw-bold">Nivel Personal y Social</label>
                            <select name="nivel_personal_social" class="form-control" required>
                                <option value="">Seleccione.....</option>
                                <option value="3">🟢 Adecuado</option>
                                <option value="2">🟡 Riesgo Medio</option>
                                <option value="1">🔴 Riesgo Alto</option>
                            </select>

                        </div>
                    </div>

                    <!-- SECCIÓN 2: ÁREA SALUD (Ejemplo de consistencia) -->
                    <div class="form-section-title">2.Área Salud Corporal y Mental </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted uppercase">Diagnóstico del Alumno:</label>
                            <div class="student-answer-preview">
                                <p><strong>¿Cómo describirías tu alimentación y hábitos de sueño?</strong>
                                    <?= htmlspecialchars($diagnostico['s_alimentacion_sueno'] ?? 'N/A') ?></p>
                                <p><strong>¿Realizas actividad física regularmente?</strong>
                                    <?= htmlspecialchars($diagnostico['s_ejercicio'] ?? 'N/A') ?></p>
                                <p><strong>¿Tienes problemas de concentración o fatiga?</strong>
                                    <?= htmlspecialchars($diagnostico['s_concentracion'] ?? 'N/A') ?></p>
                                <p><strong>¿Has sentido ansiedad, estrés o desmotivación
                                        recientemente?</strong>
                                    <?= htmlspecialchars($diagnostico['s_ansiedad_estres'] ?? 'N/A') ?></p>
                                <p><strong>¿Cómo manejas estos estados emocionales?</strong>
                                    <?= htmlspecialchars($diagnostico['s_manejo_emocional'] ?? 'N/A') ?></p>
                                <p><strong>¿Has consumido alcohol u otras sustancias que puedan afectar
                                        tu bienestar?</strong>
                                    <?= htmlspecialchars($diagnostico['s_consumo_sustancias'] ?? 'N/A') ?></p>
                                <p><strong>¿Conoces los riesgos del consumo excesivo de estas
                                        sustancias?</strong>
                                    <?= htmlspecialchars($diagnostico['s_riesgos_sustancias'] ?? 'N/A') ?></p>

                            </div>
                        </div>

                        <div class="col-md-7 tutor-note-area">
                            <label class="question-label">Análisis de Progreso de SALUD CORPORAL Y MENTAL:</label>
                            <textarea name="seguimiento_salud_mental" class="form-control"
                                rows="6"> <?= htmlspecialchars($seguimiento['seguimiento_salud_mental'] ?? '') ?></textarea>
                            <!-- NIVEL -->
                            <label class="fw-bold">Nivel Salud Corporal y Mental </label>
                            <select name="nivel_salud_mental" class="form-control" required>
                                <option value="">Seleccione......</option>
                                <option value="3">🟢 Adecuado</option>
                                <option value="2">🟡 Riesgo Medio</option>
                                <option value="1">🔴 Riesgo Alto</option>
                            </select>
                        </div>

                    </div>
                    <!-- SECCIÓN 2: ÁREA ACADÉMICA (Ejemplo de consistencia) -->
                    <div class="form-section-title">3. Área Académica</div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted uppercase">Diagnóstico del Alumno:</label>
                            <div class="student-answer-preview">
                                <p><strong>¿Cómo evalúas tu rendimiento académico?</strong>
                                    <?= htmlspecialchars($diagnostico['a_rendimiento'] ?? 'N/A') ?></p>
                                <p><strong>¿Tienes dificultades en alguna asignatura en
                                        particular?</strong>
                                    <?= htmlspecialchars($diagnostico['a_dificultad_curso'] ?? 'N/A') ?></p>
                                <p><strong>¿Utilizas técnicas de estudio?</strong>
                                    <?= htmlspecialchars($diagnostico['a_tecnicas_estudio'] ?? 'N/A') ?></p>
                                <p><strong>¿Asistes a clases con regularidad?</strong>
                                    <?= htmlspecialchars($diagnostico['a_asistencia'] ?? 'N/A') ?></p>
                                <p><strong>¿Tienes problemas para organizar tu tiempo entre clases,
                                        estudio y descanso?</strong>
                                    <?= htmlspecialchars($diagnostico['a_organizacion_tiempo'] ?? 'N/A') ?></p>
                                <p><strong>¿Buscas apoyo académico cuando lo necesitas?</strong>
                                    <?= htmlspecialchars($diagnostico['a_apoyo_academico'] ?? 'N/A') ?></p>

                            </div>
                        </div>

                        <div class="col-md-7 tutor-note-area">
                            <label class="question-label">Análisis de Progreso Académico:</label>
                            <textarea name="seguimiento_academico" class="form-control"
                                rows="6"><?= htmlspecialchars($seguimiento['seguimiento_academico'] ?? '') ?></textarea>

                            <!-- NIVEL -->
                            <label class="fw-bold">Nivel Académica</label>
                            <select name="nivel_academico" class="form-control" required>
                                <option value="">Seleccione.....</option>
                                <option value="3">🟢 Adecuado</option>
                                <option value="2">🟡 Riesgo Medio</option>
                                <option value="1">🔴 Riesgo Alto</option>
                            </select>
                        </div>
                    </div>
                    <!-- SECCIÓN 2: ÁREA ACADÉMICA (Ejemplo de consistencia) -->
                    <div class="form-section-title">4. ÁREA VOCACIONAL</div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted uppercase">Diagnóstico del Alumno:</label>
                            <div class="student-answer-preview">
                                <p><strong>¿Sientes que la carrera que estudias es la adecuada para
                                        ti?</strong>
                                    <?= htmlspecialchars($diagnostico['v_carrera_adecuada'] ?? 'N/A') ?></p>
                                <p><strong>¿Cuáles son tus metas profesionales a corto y largo
                                        plazo?</strong>
                                    <?= htmlspecialchars($diagnostico['v_metas'] ?? 'N/A') ?></p>
                                <p><strong>¿Has participado en actividades o eventos que refuercen
                                        tuvocación (charlas, talleres, pasantías)?</strong>
                                    <?= htmlspecialchars($diagnostico['v_actividades_refuerzo'] ?? 'N/A') ?></p>
                                <p><strong>¿Qué dificultades has encontrado en tu camino
                                        profesional?</strong>
                                    <?= htmlspecialchars($diagnostico['v_dificultades'] ?? 'N/A') ?></p>
                            </div>
                        </div>

                        <div class="col-md-7 ">
                            <label class="question-label">Análisis de Progreso Vocacional:</label>
                            <textarea name="seguimiento_vocacional" class="form-control"
                                rows="6"><?= htmlspecialchars($seguimiento['seguimiento_vocacional'] ?? '') ?></textarea>
                            <!-- NIVEL -->
                            <label class="fw-bold">Nivel Vocacional</label>
                            <select name="nivel_vocacional" class="form-control" required>
                                <option value="">Seleccione......</option>
                                <option value="3">🟢 Adecuado</option>
                                <option value="2">🟡 Riesgo Medio</option>
                                <option value="1">🔴 Riesgo Alto</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="label-senior">Fecha de Seguimiento</label><input type="date"
                                name="fecha_seguimiento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4"><label class="label-senior">Próxima Cita</label><input type="date"
                                name="proxima_cita" class="form-control"></div>
                    </div>

                    <div class="row g-3 border-top pt-4">
                        <!-- Acciones y Acuerdos -->
                        <div class="col-md-6">
                            <label class="label-senior text-primary">Acciones y Acuerdos Tomados</label>
                            <textarea name="acciones_acuerdos" class="form-control" rows="3"
                                placeholder="Compromisos del estudiante..."><?= htmlspecialchars($seguimiento['acciones_acuerdos'] ?? '') ?></textarea>
                        </div>

                        <!-- Recomendaciones -->
                        <div class="col-md-6">
                            <label class="label-senior text-success">Recomendaciones del Tutor</label>
                            <textarea name="recomendaciones" class="form-control"
                                rows="3"><?= htmlspecialchars($seguimiento['recomendaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- Observaciones Generales -->
                        <div class="col-6">
                            <label class="label-senior">Observaciones Generales</label>
                            <textarea name="observaciones_generales" class="form-control"
                                rows="4"><?= htmlspecialchars($seguimiento['observaciones_generales'] ?? '') ?></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white d-flex justify-content-end align-items-center flex-nowrap">

                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary btn-sm">
                        Finalizar y Firmar Seguimiento
                    </button>

                </div>
            </form>
        </div>

        <!-- PESTAÑA 2: DERIVACIÓN -->
        <div class="tab-pane fade" id="tab-derivacion" role="tabpanel">
            <div class="d-flex align-items-center mb-3">

                <h5 class="fw-bold text-dark mb-0">
                    Fichas de Referencia Tutorial
                </h5>

                <div class="ms-auto">
                    <button class="btn btn-warning btn-sm fw-bold text-white shadow-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#modalNuevaDerivacion">
                        <i class="bi bi-send me-1"></i> Nueva Referencia
                    </button>
                </div>
            </div>
            <div class="card card-senior overflow-hidden">
                <div class="table-responsive">
                    <table id="tablaDerivaciones" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Fecha</th>
                                <th>Área Destino</th>
                                <th>Motivo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($derivaciones)): ?>
                            <?php foreach ($derivaciones as $d): ?>
                            <tr>
                                <td class="ps-4 fw-bold small">
                                    <?= date('d/m/Y', strtotime($d['fecha_derivacion'])) ?>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-info text-dark"><?= htmlspecialchars($d['area_destino'] ?? 'N/A') ?></span>
                                </td>
                                <td class="small">
                                    <?= htmlspecialchars($d['motivo_informe'] ?? '') ?>
                                </td>
                                <td class="text-center">
                                    <?php
                        $estado = $d['estado_atencion'] ?? 'Pendiente';
                        if ($estado === 'Cerrado') {
                            echo '<span class="badge bg-secondary">Cerrado</span>';
                        } elseif ($estado === 'Atendido') {
                            echo '<span class="badge bg-success">Atendido</span>';
                        } else {
                            echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                        }
                        ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                        <?php if ($estado === 'Pendiente'): ?>
                                        <button type="button" class="btn btn-sm btn-white border btn-editar-derivacion"
                                            data-id="<?= $d['id_derivacion'] ?>"
                                            data-motivo="<?= htmlspecialchars($d['motivo_informe'] ?? '') ?>"
                                            data-resumen="<?= htmlspecialchars($d['resumen_caso'] ?? '') ?>"
                                            data-especialista="<?= $d['id_especialista'] ?? '' ?>"
                                            data-bs-toggle="tooltip" title="Editar">
                                            <i class="bi bi-pencil-square text-warning">✏️</i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-white border btn-eliminar-derivacion"
                                            data-id="<?= $d['id_derivacion'] ?>" data-bs-toggle="tooltip"
                                            title="Eliminar">
                                            <i class="bi bi-trash3 text-danger">🗑️</i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-light border" disabled title="Registro bloqueado">
                                            <i class="bi bi-lock-fill text-muted"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>

<!-- ==========================================
     MODAL: FICHA DE REFERENCIA (F-TUT-05)
     ========================================== -->
<div class="modal fade" id="modalNuevaDerivacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="index.php?route=tutor/guardarDerivacion" method="POST" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="id_estudiante">
            <div class="modal-header bg-unamba text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-send me-2"></i>Ficha de Referencia Tutorial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id_estudiante" value="<?= $estudiante['id_usuario'] ?>">

                <div class="row g-3">
                    <div class="col-md-12">

                        <label>Área de Destino:</label>
                        <select name="id_especialista" class="form-control" required>
                            <option value="">-- Seleccione Área --</option>
                            <?php foreach ($especialistas as $esp): ?>
                            <option value="<?= $esp['id_especialista'] ?>">
                                <?= htmlspecialchars($esp['area']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="label-senior">Motivo de la Referencia *</label>
                        <input type="text" name="motivo_informe" class="form-control"
                            placeholder="Ej: Problemas de ansiedad detectados en sesión" required>
                    </div>
                    <div class="col-md-12">
                        <label class="label-senior">Resumen del Caso</label>
                        <textarea name="resumen_caso" class="form-control" rows="3"
                            placeholder="Proporcione detalles al especialista..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning text-white fw-bold">Enviar Referencia</button>
            </div>
        </form>
    </div>
</div>
<?php if(isset($_GET['derivar'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {

    let modalElement = document.getElementById('modalNuevaDerivacion');

    if (modalElement) {
        let modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

});
</script>
<?php endif; ?>
<!-- ... (El código de las pestañas y la tabla se mantiene igual, asegúrate de que los botones tengan las clases correctas) ... -->

<script>
// 1. GESTIÓN DE ALERTAS (Se cierra automáticamente después de 4 seg)
document.addEventListener("DOMContentLoaded", function() {
    const alerts = document.querySelectorAll('.alert-success, .alert-danger');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 4000);
    });
});
</script>

<!-- LIBRERÍAS (Asegúrate de cargarlas en orden) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Destruir si ya existe
    if ($.fn.DataTable.isDataTable('#tablaDerivaciones')) {
        $('#tablaDerivaciones').DataTable().destroy();
    }

    // Inicializar
    var table = $('#tablaDerivaciones').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "responsive": true,
        "order": [
            [0, "desc"]
        ],
        "pageLength": 5,
        "columnDefs": [{
                "targets": 4,
                "orderable": false
            } // La columna 5 (índice 4) es Acciones
        ]
    });

    // IMPORTANTE: Esto corrige errores visuales al cambiar de pestañas
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        if ($(e.target).attr('data-bs-target') === '#tab-derivacion') {
            table.columns.adjust().responsive.recalc();
        }
    });
});


// 3. LÓGICA PARA EDITAR
$(document).ready(function() {
    // --- LÓGICA PARA EDITAR ---
    $(document).on('click', '.btn-editar-derivacion', function(e) {
        e.preventDefault();
        const btn = $(this);
        const modalEl = document.getElementById('modalNuevaDerivacion');
        const form = modalEl.querySelector('form');

        // Cambiar a ruta de edición
        modalEl.querySelector('.modal-title').innerHTML =
            '<i class="bi bi-pencil-square me-2"></i>Editar Referencia Tutorial';
        form.action = 'index.php?route=tutor/editarDerivacion';

        // Llenar datos
        $(form).find('[name="motivo_informe"]').val(btn.attr('data-motivo'));
        $(form).find('[name="resumen_caso"]').val(btn.attr('data-resumen'));
        $(form).find('[name="id_especialista"]').val(btn.attr('data-especialista'));

        // Asegurar ID de derivación
        let inputIdDer = form.querySelector('[name="id_derivacion"]');
        if (!inputIdDer) {
            $(form).append('<input type="hidden" name="id_derivacion">');
            inputIdDer = form.querySelector('[name="id_derivacion"]');
        }
        $(inputIdDer).val(btn.attr('data-id'));

        var myModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        myModal.show();
    });

    // --- LÓGICA PARA ELIMINAR ---
    $(document).on('click', '.btn-eliminar-derivacion', function(e) {
        e.preventDefault();
        // Usamos closest para asegurar que capturamos el ID aunque se haga clic en el icono
        const idDerivacion = $(this).closest('button').attr('data-id');
        const idEstudiante = "<?= $estudiante['id_usuario'] ?>";

        if (!idDerivacion) {
            Swal.fire("Error", "No se encontró el ID del registro", "error");
            return;
        }

        Swal.fire({
            title: '¿Eliminar esta derivación?',
            text: "Se borrarán todos los datos de esta fila permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar fila',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirección con ambos IDs necesarios
                window.location.href =
                    `index.php?route=tutor/eliminarDerivacion&id=${idDerivacion}&id_estudiante=${idEstudiante}`;
            }
        });
    });

    // Limpiar modal al crear nueva referencia
    $('[data-bs-target="#modalNuevaDerivacion"]').on('click', function() {
        const modalEl = document.getElementById('modalNuevaDerivacion');
        const form = modalEl.querySelector('form');
        form.action = 'index.php?route=tutor/guardarDerivacion';
        form.reset();
        $(form).find('[name="id_derivacion"]').remove();
        modalEl.querySelector('.modal-title').innerHTML =
            '<i class="bi bi-send me-2"></i>Ficha de Referencia Tutorial';
    });
});

// 5. TOOLTIPS
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipTriggerList.map(function(el) {
return new bootstrap.Tooltip(el)
});

</script>

<style>
@media print {

    .sidebar,
    .main-sidebar,
    .menu,
    nav,
    header,
    .nav-pills,
    .navbar,
    .btn,
    .btn-close {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    body {
        background: white !important;
    }

    .modal-body {
        max-height: none !important;
        overflow: visible !important;
    }

    .card,
    .row,
    .form-section-title {
        page-break-inside: avoid;
    }
}
</style>

<?php require __DIR__ . '/../layouts/footer.php'; ?>