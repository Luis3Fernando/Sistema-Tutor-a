<?php 
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../layouts/sidebar.php'; 
?>

<!-- DataTables e Iconos (Si no están en el header global) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="content"> <!-- Clase controlada por style.css global -->
    <div class="container-fluid py-2">
        
        <!-- CABECERA -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold text-navy mb-0">
                    <i class="bi bi-people-fill me-2 text-gold"></i>Panel de Tutorados
                </h3>
                <p class="text-muted small mb-0">Gestión y seguimiento académico de estudiantes asignados</p>
            </div>
        </div>

        <!-- SECCIÓN DE FILTROS -->
        <div class="filter-section shadow-sm">
            <form method="GET" action="index.php">
                <input type="hidden" name="route" value="tutor/mis-estudiantes">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-xs-caps text-muted">Código de Estudiante</label>
                        <input type="text" name="codigo" value="<?= htmlspecialchars((string)($_GET['codigo'] ?? '')) ?>"
                            class="form-control form-control-sm border-secondary-subtle" placeholder="Ej: 201312">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-xs-caps text-muted">Ciclo</label>
                        <select name="ciclo" class="form-select form-select-sm border-secondary-subtle">
                            <option value="">Todos</option>
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= (($_GET['ciclo'] ?? '') == $i) ? 'selected' : '' ?>>Ciclo <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs-caps text-muted">Situación Académica</label>
                        <select name="situacion" class="form-select form-select-sm border-secondary-subtle">
                            <option value="">Todas las situaciones</option>
                            <option value="Regular" <?= (($_GET['situacion'] ?? '') === 'Regular') ? 'selected' : '' ?>>Regular</option>
                            <option value="Observado" <?= (($_GET['situacion'] ?? '') === 'Observado') ? 'selected' : '' ?>>Observado</option>
                            <option value="Riesgo" <?= (($_GET['situacion'] ?? '') === 'Riesgo') ? 'selected' : '' ?>>Riesgo</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-primary btn-sm px-4" type="submit">
                            <i class="bi bi-filter me-1"></i> Aplicar
                        </button>
                        <a class="btn btn-outline-secondary btn-sm" href="index.php?route=tutor/mis-estudiantes">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- TABLA DE ESTUDIANTES -->
        <div class="card card-academic shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaEstudiantes" class="table table-hover align-middle mb-0 w-100">
                        <thead class="bg-light">
                            <tr class="text-xs-caps text-muted">
                                <th class="ps-4">Código</th>
                                <th>Estudiante</th>
                                <th>Contacto / DNI</th>
                                <th class="text-center">Ciclo</th>
                                <th class="text-center">Situación</th>
                                <th class="text-end pe-4">Gestión</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <?php if (!empty($estudiantes)): ?>
                            <?php foreach ($estudiantes as $e): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="student-code"><?= htmlspecialchars((string)($e['codigo_unamba'] ?? '')) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars((string)($e['apellidos'] ?? '')) ?></div>
                                    <div class="text-muted"><?= htmlspecialchars((string)($e['nombres'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-card-text me-1 text-muted"></i> <?= htmlspecialchars((string)($e['dni'] ?? '')) ?></div>
                                    <div class="small text-primary"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars((string)($e['correo'] ?? '')) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3"><?= htmlspecialchars((string)($e['ciclo_actual'] ?? '')) ?>°</span>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $situacion = $e['situacion_academica'] ?? 'Regular';
                                        $clase_badge = 'badge-academic-regular';
                                        if($situacion == 'Observado') $clase_badge = 'badge-academic-observado';
                                        if($situacion == 'Riesgo') $clase_badge = 'badge-academic-riesgo';
                                    ?>
                                    <span class="<?= $clase_badge ?>">
                                        <i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> <?= $situacion ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                        href="index.php?route=tutor/ver-expediente&id=<?= (int)$e['id_usuario'] ?>">
                                        <i class="bi bi-folder2-open me-1"></i> Expediente
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x display-4 d-block mb-2"></i>
                                    No se encontraron estudiantes con los filtros aplicados.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaEstudiantes').DataTable({
        "pageLength": 10,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "dom": '<"d-none"f>rt<"d-flex justify-content-between align-items-center p-3"ip>',
        "order": [[1, "asc"]],
        "responsive": true
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>