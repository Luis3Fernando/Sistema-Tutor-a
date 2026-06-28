<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/helpers/Auth.php';

Auth::requireAuth();
if (Auth::role() !== 'administrador') {
    header('Location: index.php?route=login');
    exit;
}

$escuelas = $escuelas ?? [];
$tutores = $tutores ?? [];
$estudiantes = $estudiantes ?? [];
$asignacionesActuales = $asignacionesActuales ?? [];
$periodos = $periodos ?? [];
$errores = $errores ?? [];
$ok = $ok ?? '';
$filtroEscuela = (int)($filtroEscuela ?? 0);
$filtroPeriodo = (string)($filtroPeriodo ?? '');

?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="content admin-dashboard">
    <section class="admin-hero d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="mb-1">Escuelas y asignaciones</h2>
            <p class="mb-0 text-muted">Registro de escuelas y asignación de estudiantes a tutores por periodo académico.
            </p>
        </div>
    </section>

    <?php if ($ok !== ''): ?>
    <div class="alert alert-success mt-3"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger mt-3">
        <ul class="mb-0">
            <?php foreach ($errores as $err): ?>
            <li><?= htmlspecialchars((string)$err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building"></i> Escuelas registradas
                    </h5>
                    <button class="btn-azul" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus"></i> Agregar Escuela
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center" id="escuelasTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Escuela</th>
                                    <th>Facultad</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($escuelas as $escuela): ?>
                                <tr>
                                    <td><?= (int)$escuela['id_escuela'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($escuela['nombre_escuela']) ?></td>
                                    <td><?= htmlspecialchars($escuela['facultad']) ?></td>
                                    <td>
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="fas fa-check-circle"></i> Activa
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($escuela['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="<?= $escuela['id_escuela'] ?>"
                                                data-nombre="<?= htmlspecialchars($escuela['nombre_escuela']) ?>"
                                                data-facultad="<?= htmlspecialchars($escuela['facultad']) ?>">
                                                <i class="fas fa-edit">editar</i>
                                            </button>
                                            <form method="post" onsubmit="return confirm('¿Eliminar esta escuela?')">
                                                <input type="hidden" name="accion" value="eliminar_escuela">
                                                <input type="hidden" name="id_escuela" value="<?= (int)$escuela['id_escuela'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash">eliminar</i>
                                                </button>
                                            </form>
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
    </div>
    
    <!-- Modales de Escuela (Sin cambios en lógica) -->
    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content shadow border-0">
               <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva Escuela</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_escuela">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Escuela</label>
                        <input type="text" name="nombre_escuela" class="form-control" placeholder="Ej: Ingeniería de Sistemas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Facultad</label>
                        <input type="text" name="facultad" class="form-control" placeholder="Ej: Ingeniería" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="1" selected>Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-azul"><i class="fas fa-save"></i> Guardar</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content shadow border-0">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Escuela</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_escuela">
                    <input type="hidden" name="id_escuela" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Escuela</label>
                        <input type="text" name="nombre_escuela" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Facultad</label>
                        <input type="text" name="facultad" id="edit_facultad" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-azul"><i class="fas fa-save"></i> Actualizar</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sección Asignación Manual -->
    <section class="card mt-4 shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="mb-0">Asignación manual (estudiantes a tutor)</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?route=admin/asignaciones">
                <input type="hidden" name="accion" value="asignar_manual">
                <input type="hidden" name="filtro_escuela" value="<?= $filtroEscuela ?>">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tutor destino</label>
                        <select name="id_tutor" class="form-select" required>
                            <option value="">Seleccionar tutor</option>
                            <?php foreach ($tutores as $t): ?>
                            <option value="<?= (int)$t['id_usuario'] ?>">
                                <?= htmlspecialchars((string)($t['apellidos'] . ' ' . $t['nombres'])) ?>
                                (<?= htmlspecialchars((string)($t['nombre_escuela'] ?? 'Sin escuela')) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Periodo académico</label>
                        <input type="text" name="periodo_academico" class="form-control" value="<?= htmlspecialchars($filtroPeriodo) ?>" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered" id="tablaManualEstudiantes">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" onclick="document.querySelectorAll('.chk-est').forEach(c => c.checked = this.checked);">
                                </th>
                                <th>Estudiante</th>
                                <th>Código</th>
                                <th>Ciclo</th>
                                <th>Escuela</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $e): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="chk-est" name="estudiantes_ids[]" value="<?= (int)$e['id_usuario'] ?>">
                                </td>
                                <td><?= htmlspecialchars((string)($e['apellidos'] . ' ' . $e['nombres'])) ?></td>
                                <td><?= htmlspecialchars((string)($e['codigo_unamba'] ?? '')) ?></td>
                                <td><?= (int)($e['ciclo_actual'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($e['nombre_escuela'] ?? '')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn-azul mt-3">Asignar seleccionados</button>
            </form>
        </div>
    </section>

    <!-- Sección Asignación Automática -->
    <section class="card mt-4 shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="mb-0">Asignación automática por cupos</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?route=admin/asignaciones">
                <input type="hidden" name="accion" value="asignar_automatico">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Escuela</label>
                        <select name="filtro_escuela" class="form-select" required>
                            <option value="">Seleccionar</option>
                            <?php foreach ($escuelas as $esc): ?>
                            <option value="<?= (int)$esc['id_escuela'] ?>" <?= $filtroEscuela === (int)$esc['id_escuela'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$esc['nombre_escuela']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Periodo académico</label>
                        <input type="text" name="periodo_academico" class="form-control" value="<?= htmlspecialchars($filtroPeriodo) ?>" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="tablaAutoTutores">
                        <thead class="table-light">
                            <tr>
                                <th>Tutor</th>
                                <th>Escuela</th>
                                <th>Asignados actuales</th>
                                <th style="width: 160px;">Cupo nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tutores as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($t['apellidos'] . ' ' . $t['nombres'])) ?></td>
                                <td><?= htmlspecialchars((string)($t['nombre_escuela'] ?? '')) ?></td>
                                <td class="text-center"><?= (int)($t['total_asignados'] ?? 0) ?></td>
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm" name="limites_tutor[<?= (int)$t['id_usuario'] ?>]" value="0">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn-azul mt-3">Ejecutar asignación automática</button>
            </form>
        </div>
    </section>

    <!-- Historial de Asignaciones -->
    <section class="card mt-4 shadow-sm border-0 mb-5">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Asignaciones actuales</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered w-100" id="tablaAsignaciones">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Periodo</th>
                            <th>Estudiante</th>
                            <th>Tutor</th>
                            <th>Escuela</th>
                            <th>Ciclo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignacionesActuales as $a): ?>
                        <tr>
                            <td><?= (int)$a['id_asignacion'] ?></td>
                            <td><?= htmlspecialchars((string)$a['periodo_academico']) ?></td>
                            <td><?= htmlspecialchars((string)$a['estudiante']) ?></td>
                            <td><?= htmlspecialchars((string)$a['tutor']) ?></td>
                            <td><?= htmlspecialchars((string)($a['nombre_escuela'] ?? '')) ?></td>
                            <td class="text-center"><?= (int)($a['ciclo_actual'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Scripts de DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Configuración de idioma común
    const i18n_es = {
        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
        "emptyTable": "No hay datos disponibles en esta tabla",
        "paginate": {
            "previous": "Atrás",
            "next": "Adelante"
        }
    };

    // 1. Tabla de Escuelas (CRUD)
    $('#escuelasTable').DataTable({
        language: i18n_es,
        pageLength: 10,
        order: [[0, 'desc']],
        dom: '<"d-flex justify-content-between mb-2"f>rt<"d-flex justify-content-between mt-2"ip>'
    });

    // 2. Tabla Manual de Estudiantes
    $('#tablaManualEstudiantes').DataTable({
        language: i18n_es,
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        columnDefs: [{ orderable: false, targets: 0 }], // Desactiva orden en checkbox
        dom: '<"d-flex justify-content-between mb-2"f>rt<"d-flex justify-content-between mt-2"ip>'
    });

    // 3. Tabla Automática de Tutores
    $('#tablaAutoTutores').DataTable({
        language: i18n_es,
        pageLength: 5,
        dom: 'rt<"d-flex justify-content-between mt-2"ip>' // Sin buscador para esta tabla pequeña
    });

    // 4. Tabla de Asignaciones Actuales
    $('#tablaAsignaciones').DataTable({
        language: i18n_es,
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        dom: '<"d-flex justify-content-between mb-2"f>rt<"d-flex justify-content-between mt-2"ip>',
        pagingType: "simple_numbers"
    });
});

// Lógica para cargar datos en el modal de edición
document.getElementById('modalEditar')?.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget;
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('edit_nombre').value = button.getAttribute('data-nombre');
    document.getElementById('edit_facultad').value = button.getAttribute('data-facultad');
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>