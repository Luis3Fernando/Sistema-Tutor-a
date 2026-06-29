<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php date_default_timezone_set('America/Lima'); ?>

<!-- Google Fonts para un look más moderno -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


<div class="tutor-plan-main">
    <div class="container-fluid px-4 py-4">

        <!-- TÍTULO DE SECCIÓN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="page-title"><i class="bi bi-file-earmark-text me-2"></i>Plan de Trabajo Tutorial</h3>
            </div>
            <div>
                <span class="badge bg-white text-dark shadow-sm p-2 border">
                    <i class="bi bi-calendar-event me-1 text-primary"></i>
                    Hoy: <?= date('d/m/Y') ?>
                </span>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                <div><strong>¡Éxito!</strong> <?= $_SESSION['flash_msg']; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg']); endif; ?>

        <!-- CABECERA: DATOS DEL TUTOR -->
        <div class="card card-academic">
            <div class="card-header header-accent d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fs-6 fw-bold">INFORMACIÓN ACADÉMICA DEL TUTOR</h5>
                <span class="badge bg-primary"><?= $plan['periodo_academico'] ?></span>
            </div>
            <div class="card-body bg-light-subtle">
                <div class="row g-4 text-center text-md-start">
                    <div class="col-md-4 border-end-md">
                        <span class="info-label">Docente Tutor</span>
                        <span class="info-value fs-5"><?= $plan['nombres'] . ' ' . $plan['apellidos'] ?></span>
                    </div>
                    <div class="col-md-4 border-end-md">
                        <span class="info-label">Escuela Profesional</span>
                        <span class="info-value"><?= $plan['nombre_escuela'] ?></span>
                    </div>
                    <div class="col-md-2 border-end-md text-center">
                        <span class="info-label">Estudiantes</span>
                        <span class="info-value text-primary">
                            <i class="bi bi-people-fill me-1"></i><?= $plan['nro_estudiantes_asignados'] ?>
                        </span>
                    </div>
                    <div class="col-md-2 text-center">
                        <span class="info-label">Estado Plan</span>
                        <span class="badge <?= $plan['id_plan'] == 0 ? 'bg-danger' : 'bg-success' ?> rounded-pill">
                            <?= $plan['id_plan'] == 0 ? 'Pendiente' : 'Activo' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($plan['id_plan'] == 0): ?>
        <!-- ESTADO: INICIALIZAR PLAN -->
        <div class="card card-academic ">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="bi bi-pencil-square text-warning display-3"></i>
                </div>
                <h4 class="fw-bold">Definir Objetivo Estratégico</h4>
                <p class="text-muted mx-auto" style="max-width: 600px;">
                    Para comenzar con el cronograma de tutoría, por favor defina el objetivo general que guiará sus
                    actividades durante este semestre académico.
                </p>
                <form action="index.php?route=tutor/inicializar-plan" method="POST" class="col-md-7 mx-auto mt-4">
                    <input type="hidden" name="periodo_academico" value="<?= $plan['periodo_academico'] ?>">
                    <div class="form-floating mb-3">
                        <textarea name="objetivo_general" class="form-control" id="objInput" style="height: 120px"
                            placeholder="Objetivo..." required></textarea>

                    </div>
                    <button type="submit" class="btn btn-unamba btn-lg w-100">
                        <i class="bi bi-send-fill me-2"></i>Guardar e Iniciar Cronograma
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ESTADO: PLAN EXISTENTE -->

        <!-- Bloque de Objetivo -->
        <div class="card card-academic mb-4">
            <div class="card-body p-0">
                <div class="objective-box">
                    <span class="info-label d-block mb-2 text-primary">Objetivo General del Periodo:</span>
                    "<?= htmlspecialchars($plan['objetivo_general']) ?>"
                </div>
            </div>
        </div>

        <!-- Tabla de Cronograma -->
        <div class="card card-academic">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2 text-primary"></i>Cronograma de Actividades</h5>
                <div class="ms-auto">
                    <button class="btn btn-unamba btn-sm" data-bs-toggle="modal" data-bs-target="#modalActividad">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Actividad
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaCronograma" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Programación</th>
                                <th>Tipo de Actividad</th>
                                <th>Instrumento</th>
                                <th>Objetivo Específico</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $act): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-center" style="min-width: 60px;">
                                            <div class="small fw-bold text-uppercase text-muted"
                                                style="font-size: 0.6rem;">
                                                <?= date('M', strtotime($act['fecha'])) ?>
                                            </div>
                                            <div class="fs-5 fw-bold"><?= date('d', strtotime($act['fecha'])) ?></div>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= date('H:i', strtotime($act['hora'])) ?></div>
                                            <div class="text-muted small">Hora programada</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary px-3 py-2">
                                        <i class="bi bi-tag-fill me-1"></i><?= $act['actividad_tipo'] ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small"><?= htmlspecialchars($act['instrumento']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;"
                                        title="<?= $act['objetivo_especifico'] ?>">
                                        <?= htmlspecialchars($act['objetivo_especifico']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($act['estado'] == 'Realizado'): ?>
                                    <span class="badge badge-soft-success fw-normal"><i class="bi bi-check2-all"></i>
                                        Ejecutado</span>
                                    <?php else: ?>
                                    <span class="badge badge-soft-warning fw-normal"><i
                                            class="bi bi-hourglass-split"></i> Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($act['estado'] != 'Realizado'): ?>
                                        

                                    <a href="index.php?route=tutor/registrar-sesion&id_actividad=<?= $act['id_actividad'] ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Ejecutar <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                    <?php else: ?>
                              <a href="index.php?route=tutor/verDetalleSesion&id_actividad=<?= $act['id_actividad'] ?>"
       class="btn btn-sm btn-info rounded-pill text-white px-3">
        <i class="bi bi-eye-fill"></i> Ver detalle
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
        <?php endif; ?>
    </div>

    <!-- ... (Mantén tu modal  -->
    <div class="modal fade" id="modalActividad" tabindex="-1">
        <div class="modal-dialog">
            <form id="formActividad" action="index.php?route=tutor/guardar-actividad" method="POST"
                class="modal-content">

                <input type="hidden" name="id_plan" value="<?= $plan['id_plan'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title">Programar Nueva Actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">


                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Fecha</label>
                            <input type="date" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Hora</label>
                            <input type="time" name="hora" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Tipo de Actividad</label>
                            <select name="actividad_tipo" class="form-select" required>
                                <option value="">Seleccionar actividad...</option>
                                <option value="Control grupal">Control grupal</option>
                                <option value="Diagnostico individual">Diagnostico individual</option>
                                <option value="Seguimiento individual">Seguimiento individual</option>
                                <option value="Referencia tutoría">Referencia tutoría</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Instrumento a utilizar</label>
                            <input type="text" name="instrumento" class="form-control"
                                placeholder="Ej: Ficha de cotejo, Test, Formulario" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Objetivo Específico</label>
                            <textarea name="objetivo_especifico" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-unamba btn-sm">Guardar Programación</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaCronograma').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 5,
        responsive: true,
        order: [
            [0, 'desc']
        ]
    });
});


</script>



<?php require __DIR__ . '/../layouts/footer.php'; ?>