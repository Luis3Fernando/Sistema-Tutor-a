<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<style>
:root {

    --unamba-blue: #003366;
    --accent-gold: #a18262;
}

.student-welcome {
    background: linear-gradient(135deg, var(--unamba-blue) 0%, #052344 100%);
    color: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
}

.nav-pills .nav-link.active {
    background-color: var(--unamba-blue);
    box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2);
}

.nav-pills .nav-link {
    color: var(--unamba-blue);
    font-weight: 600;
    border: 1px solid #dee2e6;
    margin: 0 5px;
}

.section-card {
    border: none;
    border-radius: 12px;
    transition: transform 0.2s;
}

.question-box {
    background: #fdfdfd;
    border: 1px solid #f1f1f1;
    border-radius: 10px;
    padding: 15px;
    height: 100%;
}

.timeline-item {
    border-left: 3px solid var(--accent-gold);
    padding-left: 20px;
    position: relative;
    margin-bottom: 25px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    width: 15px;
    height: 15px;
    background: var(--accent-gold);
    border-radius: 50%;
    left: -9px;
    top: 0;
}

.btn-unamba {
    background-color: var(--unamba-blue);
    color: white;
    border: none;
    transition: all .25s ease;
}

.btn-unamba:hover {
    background-color: #0a3d91;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(0, 51, 102, .25);
}
</style>



<div class="container-fluid py-4">


    <!-- Encabezado Personalizado -->
    <div class="student-welcome shadow-sm">
        <div class="row align-items-center">

            <div class="col-md-4 border-end">
                <span class="label-header"><i class="bi bi-person-fill"></i> Estudiante</span>
                <span
                    class="data-header"><?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?></span>
            </div>

            <!-- Código del Estudiante -->
            <div class="col-md-2 border-end text-center">
                <span class="label-header">Código</span>
                <span class="data-header"><?= htmlspecialchars($estudiante['codigo_unamba']) ?></span>
            </div>

            <!-- Escuela Académico Profesional -->
            <div class="col-md-3 border-end">
                <span class="label-header">Escuela Académico Profesional</span>
                <span class="data-header"><?= htmlspecialchars($estudiante['nombre_escuela']) ?></span>
            </div>

            <!-- Semestre / Ciclo -->
            <div class="col-md-1 border-end text-center">
                <span class="label-header">Semestre</span>
                <span class="data-header"><?= htmlspecialchars($estudiante['ciclo_actual']) ?>°</span>
            </div>

            <!-- Nombre del Tutor -->
            <div class="col-md-2 text-end">
                <span class="label-header"><i class="bi bi-person-badge"></i> Nombre del Tutor</span>
                <span
                    class="data-header text-primary"><?= htmlspecialchars($estudiante['nombre_tutor'] ?? 'No asignado') ?></span>
            </div>

        </div>
    </div>
    <!-- BLOQUE DE MENSAJES FLASH -->
    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= $_SESSION['flash_success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= $_SESSION['flash_error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>


    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <ul class="nav nav-pills nav-fill" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-diag">
                        <i class="bi bi-person-bounding-box me-2"></i>Mi Diagnóstico Inicial
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-seguimiento">
                        <i class="bi bi-graph-up-arrow me-2"></i>Mis Avances
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-derivacion">
                        <i class="bi bi-hospital me-2"></i>Mis Derivaciones
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4 tab-content">

            <!-- TABS 1: DIAGNÓSTICO (Auto-reflexión) -->
            <div class="tab-pane fade show active" id="pills-diag">

                <form action="index.php?route=estudiante/actualizarDiagnostico" method="POST">

                    <!-- BLOQUE 1: DATOS DE LA ACTIVIDAD (Manual) -->
                    <div class="row mb-4 bg-light p-3 rounded border">
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase">Fecha de la Actividad</label>
                            <input type="date" name="fecha_actividad" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase">Hora de Inicio</label>
                            <input type="time" name="hora_inicio" class="form-control" value="<?= date('H:i') ?>"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase">Hora de Término</label>
                            <input type="time" name="hora_fin" class="form-control" required>
                        </div>
                    </div>

                    <!-- BLOQUE 2: ÁREA PERSONAL Y SOCIAL -->
                    <div class="form-section-title"><i class="bi bi-person-heart me-2"></i> 👥 ÁREA PERSONAL Y SOCIAL
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="question-label">¿Cómo te sientes en tu entorno universitario?</label>
                            <textarea name="p_entorno_uni" class="form-control" rows="2"
                                required><?= $diagnostico['p_entorno_uni'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Sientes apoyo de tus compañeros y profesores?</label>
                            <textarea name="p_apoyo_social" class="form-control" rows="2"
                                required><?= $diagnostico['p_apoyo_social'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Cómo manejas el estrés?</label>
                            <textarea name="p_manejo_estres" class="form-control" rows="2"
                                required><?= $diagnostico['p_manejo_estres'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Has tenido problemas de integración o aislamiento?</label>
                            <textarea name="p_integracion" class="form-control" rows="2"
                                required><?= $diagnostico['p_integracion'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <!-- BLOQUE 3: ÁREA SALUD CORPORAL Y MENTAL -->
                    <div class="form-section-title"><i class="bi bi-heart-pulse me-2"></i>❤️‍🩹 ÁREA SALUD CORPORAL Y
                        MENTAL</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="question-label">¿Cómo describirías tu alimentación y hábitos de sueño?</label>
                            <textarea name="s_alimentacion_sueno" class="form-control" rows="2"
                                required><?= $diagnostico['s_alimentacion_sueno'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="question-label">¿Realizas actividad física regularmente?</label>
                            <textarea name="s_ejercicio" class="form-control" rows="2"
                                required><?= $diagnostico['s_ejercicio'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="question-label">¿Tienes problemas de concentración o fatiga?</label>
                            <textarea name="s_concentracion" class="form-control" rows="2"
                                required><?= $diagnostico['s_concentracion'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Has sentido ansiedad, estrés o desmotivación
                                recientemente?</label>
                            <textarea name="s_ansiedad_estres" class="form-control" rows="2"
                                required><?= $diagnostico['s_ansiedad_estres'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Cómo manejas estos estados emocionales?</label>
                            <textarea name="s_manejo_emocional" class="form-control" rows="2"
                                required><?= $diagnostico['s_manejo_emocional'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Has consumido alcohol u otras sustancias?</label>
                            <textarea name="s_consumo_sustancias" class="form-control" rows="2"
                                required><?= $diagnostico['s_consumo_sustancias'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Conoces los riesgos del consumo excesivo?</label>
                            <textarea name="s_riesgos_sustancias" class="form-control" rows="2"
                                required><?= $diagnostico['s_riesgos_sustancias'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <!-- BLOQUE 4: ÁREA ACADÉMICA -->
                    <div class="form-section-title"><i class="bi bi-book me-2"></i>🎓 ÁREA  ACADÉMICA</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="question-label">¿Cómo evalúas tu rendimiento académico?</label>
                            <textarea name="a_rendimiento" class="form-control" rows="2"
                                required><?= $diagnostico['a_rendimiento'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Tienes dificultades en alguna asignatura?</label>
                            <textarea name="a_dificultad_curso" class="form-control" rows="2"
                                required><?= $diagnostico['a_dificultad_curso'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Utilizas técnicas de estudio?</label>
                            <textarea name="a_tecnicas_estudio" class="form-control" rows="2"
                                required><?= $diagnostico['a_tecnicas_estudio'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label"> ¿Asistes a clases con regularidad?</label>
                            <textarea name="a_asistencia" class="form-control" rows="2"
                                required><?= $diagnostico['a_asistencia'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Tienes problemas para organizar tu tiempo?</label>
                            <textarea name="a_organización_tiempo" class="form-control" rows="2"
                                required><?= $diagnostico['a_organizacion_tiempo'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Buscas apoyo académico cuando lo necesitas?</label>
                            <textarea name="a_apoyo_academico" class="form-control" rows="2"
                                required><?= $diagnostico['a_apoyo_academico'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <!-- BLOQUE 5: ÁREA VOCACIONAL -->
                    <div class="form-section-title"><i class="bi bi-mortarboard me-2"></i> 🧭 ÁREA VOCACIONAL</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="question-label">¿Sientes que la carrera es la adecuada para ti?</label>
                            <textarea name="v_carrera_adecuada" class="form-control" rows="2"
                                required><?= $diagnostico['v_carrera_adecuada'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Cuáles son tus metas profesionales?</label>
                            <textarea name="v_metas" class="form-control" rows="2"
                                required><?= $diagnostico['v_metas'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Has participado en actividades que refuercen tu
                                vocación?</label>
                            <textarea name="v_actividades_refuerzo" class="form-control" rows="2"
                                required><?= $diagnostico['v_actividades_refuerzo'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="question-label">¿Qué dificultades has encontrado?</label>
                            <textarea name="v_dificultades" class="form-control" rows="2"
                                required><?= $diagnostico['v_dificultades'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-3">
                        <button type="submit" class="btn btn-unamba px-4 py-2 rounded-pill shadow-sm fw-semibold">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Guardar y Enviar Diagnóstico
                        </button>
                    </div>
                </form>
            </div>

            <!-- TABS 2: SEGUIMIENTO (Línea de tiempo de logros) -->
            <div class="tab-pane fade" id="pills-seguimiento">
                <h5 class="fw-bold mb-4">Mi Evolución Académica, Personal , Salud mental y Vocacional</h5>

                <?php if (empty($seguimientos)): ?>
                <div class="text-center py-5">
                    <img src="assets/img/empty_states.svg" style="width: 150px;" class="mb-3">
                    <p class="text-muted">Aún no tienes sesiones de seguimiento registradas por tu tutor.</p>
                </div>
                <?php else: ?>
                <div class="timeline-container px-3">
                    <?php foreach ($seguimientos as $s): ?>
                    <div class="mt-3">
                        

                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <strong>Personal Social</strong>
                                    <p class="small mb-0">
                                        <?= nl2br(htmlspecialchars($s['seg_personal'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <strong>Salud Mental</strong>
                                    <p class="small mb-0">
                                        <?= nl2br(htmlspecialchars($s['seg_salud'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <strong>Académico</strong>
                                    <p class="small mb-0">
                                        <?= nl2br(htmlspecialchars($s['seg_academico'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <strong>Vocacional</strong>
                                    <p class="small mb-0">
                                        <?= nl2br(htmlspecialchars($s['seg_vocacional'])) ?>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- TABS 3: DERIVACIONES (Transparencia en el servicio) -->
            <div class="tab-pane fade" id="pills-derivacion">

                <h5 class="fw-bold mb-4">Servicios de Apoyo Especializado de psicopedagogía</h5>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">

                        <table id="tablaDerivaciones" class="table table-hover table-bordered align-middle">

                            <thead class="table-primary">
                                <tr>
                                    <th>Especialidad</th>
                                    <th>Motivo de derivación</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($derivaciones as $d): ?>
                                <tr>

                                    <td class="fw-bold text-primary">
                                        <?= $d['area_destino'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d['motivo_informe']) ?>
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill 
                                    bg-<?= $d['estado_atencion'] == 'Cerrado'
                                        ? 'success'
                                        : 'warning text-dark' ?>">
                                            <?= $d['estado_atencion'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y', strtotime($d['fecha_derivacion'])) ?>
                                    </td>

                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>

                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {

    $('#tablaDerivaciones').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ derivaciones",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Adelante",
                previous: "Atras"
            },
            zeroRecords: "No se encontraron resultados"
        }
    });

});
</script>


<?php require __DIR__ . '/../layouts/footer.php'; ?>