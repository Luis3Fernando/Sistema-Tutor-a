<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<style>
    :root { --unamba-blue: #003366; --unamba-gold: #C5A059; }
    body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
    .header-custom { background: var(--unamba-blue); color: white; border-radius: 12px 12px 0 0; padding: 20px; }
    .card-academic { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #fff; }
    .badge-presente { background-color: #d1e7dd; color: #0f5132; }
    .badge-ausente { background-color: #f8d7da; color: #842029; }
    .btn-download { color: var(--unamba-blue); border: 1px solid var(--unamba-blue); }
    .btn-download:hover { background: var(--unamba-blue); color: white; }

    /* Botón Volver Estilo Senior */
.btn-back-senior {
    display: inline-flex;
    align-items: center;
    background-color: var(--unamba-blue); /* Tu azul oscuro */
    color: #ffffff !important;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    border: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-decoration: none;
}

/* Icono dentro del botón */
.btn-back-senior i {
    font-size: 1.4rem;
    margin-right: 8px;
    transition: transform 0.3s ease;
}

/* Efectos Hover */
.btn-back-senior:hover {
    background-color: #001e3d; /* Un azul un poco más oscuro al presionar */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px); /* Pequeño salto hacia arriba */
}

/* Animación de la flecha al pasar el mouse */
.btn-back-senior:hover i {
    transform: translateX(-5px);
}

/* Efecto Click */
.btn-back-senior:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

<div class="container-fluid px-4 py-4">
    <div class="mb-4">
    <a href="index.php?route=tutor/plan-trabajo" class="btn btn-back-senior">
        <i class="bi bi-arrow-left-short"></i>
        <span>Volver al Plan de Trabajo</span>
    </a>
</div>

    <div class="card card-academic">
        <div class="header-custom">
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Asistencia y Archivos de Estudiantes</h4>
            <small class="opacity-75">Listado de alumnos asignados a la actividad tutorial ejecutada</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaDetalle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Código</th>
                            <th class="text-center">Asistencia</th>
                            <th class="text-center">Archivo del Estudiante</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($asistentes) && is_array($asistentes)): ?>
                            <?php foreach($asistentes as $ast): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($ast['apellidos'] . ', ' . $ast['nombres']) ?></div>
                                </td>
                                <td><code><?= htmlspecialchars($ast['codigo_unamba']) ?></code></td>
                                <td class="text-center">
                                    <?php if($ast['asistencia'] == 1): ?>
                                        <span class="badge badge-presente"><i class="bi bi-check-circle"></i> Presente</span>
                                    <?php else: ?>
                                        <span class="badge badge-ausente"><i class="bi bi-x-circle"></i> Ausente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">

    <?php if(!empty($ast['archivo_estudiante'])): ?>
        <a href="<?= $ast['archivo_estudiante'] ?>" target="_blank" class="btn btn-sm btn-download rounded-pill">
            <i class="bi bi-eye"></i> Ver
        </a>
    <?php else: ?>
        <span class="text-muted small">No disponible</span>
    <?php endif; ?>
</td>

                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No se encontraron registros de estudiantes asignados a esta sesión.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#tablaDetalle')) {
        $('#tablaDetalle').DataTable().destroy();
    }
    $('#tablaDetalle').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
        dom: 'frtip'
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>