<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">



<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">

            <!-- ENCABEZADO INSTITUCIONAL -->
            <div class="row align-items-center mb-4">

                <div class="col-8 text-center">
                    <h5 class="fw-bold mb-1">UNIVERSIDAD NACIONAL MICAELA BASTIDAS DE APURÍMAC</h5>

                    <h4 class="institutional-title mt-2">REGISTRO DE SESION Y CONTROL DE ASISTENCIA GRUPAL</h4>
                </div>

            </div>

            <form action="index.php?route=tutor/guardarAsistencia" method="POST" id="formAsistencia"
                enctype="multipart/form-data">
                <input type="hidden" name="id_sesion" value="<?= $id_sesion ?>">

                <!-- DATOS INFORMATIVOS -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-label">Docente Tutor</div>
                        <div class="info-value">
                            <?= htmlspecialchars(($tutor['grado_academico'] ?? '') . ' ' . ($tutor['apellidos'] ?? '') . ' ' . ($tutor['nombres'] ?? '')) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Escuela Profesional</div>
                        <div class="info-value">
                            <?= htmlspecialchars($tutor['nombre_escuela'] ?? '') ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="info-label">Actividad Programada</label>

                        <input type="text" class="form-control form-underline fw-bold text-primary"
                            value="<?= htmlspecialchars($actividadSeleccionada['actividad_tipo'] ?? '') ?>" readonly>

                        <!-- ID REAL QUE SE GUARDA -->
                        <input type="hidden" name="id_actividad"
                            value="<?= $actividadSeleccionada['id_actividad'] ?? '' ?>">


                    </div>

                    <div class="col-md-6">
                        <label class="info-label">Tema / Actividad de la Sesión</label>
                        <input type="text" name="objetivo_sesion" required
                            class="form-control form-underline fw-bold text-primary"
                            placeholder="Ej: Orientación sobre métodos de estudio" /* CAMBIO AQUÍ: Usamos la variable
                            preparada por el controlador */ value="<?= htmlspecialchars($objetivoSesion) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="info-label">Fecha de Ejecución</label>
                        <input type="date" name="fecha" required class="form-control form-underline" /* CAMBIO AQUÍ:
                            Usamos la variable del controlador */ value="<?= $fechaSesion ?: date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="info-label">Hora Inicio</label>
                        <input type="time" name="hora" required class="form-control form-underline" /* CAMBIO AQUÍ:
                            Usamos la variable del controlador */ value="<?= $horaInicioSesion ?: date('H:i') ?>">
                    </div>
                    <!-- NUEVO: HORA FIN -->
                    <div class="col-md-3 mt-3">
                        <label class="info-label">Hora Fin</label>
                        <input type="time" name="hora_fin" required class="form-control form-underline"
                            value="<?= $sesion['hora_fin'] ?? '' ?>">
                    </div>
                    <!-- NUEVO: ARCHIVO EVIDENCIA -->
                    <div class="col-md-6 mt-3">
                        <label class="info-label">Evidencia (PDF/JPG)</label>
                        <input type="file" name="archivo_evidencia" class="form-control form-control-sm"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <?php if(!empty($sesion['archivo_evidencia'])): ?>
                        <small class="text-success">Ya existe un archivo cargado.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABLA DE ESTUDIANTES -->
                <div class="card border-0 bg-light p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-people-fill me-2"></i>ESTUDIANTES ASIGNADOS
                        </h6>
                        <div class="no-print">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">
                                Marcar Todos
                            </button>
                        </div>
                    </div>

                    <table id="tablaAsistencia" class="table table-hover table-bordered bg-white shadow-sm w-100">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">N°</th>
                                <th width="15%">Código</th>
                                <th width="50%">Apellidos y Nombres</th>
                                <th class="text-center" width="15%">Semestre</th>
                                <th class="text-center no-print" width="15%">Asistencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_estudiantes as $index => $e): ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td class="fw-bold"><?= $e['codigo_unamba'] ?></td>
                                <td class="text-uppercase">
                                    <?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombres']) ?></td>
                                <td class="text-center"><?= $e['semestre'] ?>° Ciclo</td>
                                <td class="text-center no-print">
                                    <div class="form-check form-switch d-inline-block custom-switch">
                                        <input class="form-check-input check-asistencia" type="checkbox"
                                            name="asistencia[<?= $e['id_usuario'] ?>]" value="1"
                                            <?= $e['asistio'] ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BLOQUE ADICIONAL: SOLICITUD DE TAREA AL ESTUDIANTE -->
<div class="card border-primary mb-4 shadow-sm">
    <div class="card-header bg-light">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="pedir_evidencia" id="pedir_evidencia" value="1">
            <label class="form-check-label fw-bold text-primary" for="pedir_evidencia">
                <i class="bi bi-exclamation-circle-fill me-1"></i> ¿Solicitar evidencia/tarea al estudiante?
            </label>
        </div>
    </div>
    <div id="seccion_indicaciones" style="display: none;" class="card-body">
        <label class="info-label">Indicaciones para el estudiante</label>
        <textarea name="indicaciones_estudiante" class="form-control form-underline" rows="2" 
                  placeholder="Ej: Subir el certificado del taller o su horario de estudio actualizado..."></textarea>
    </div>
</div>



                <!-- ACCIONES -->
                <div class="d-flex justify-content-between align-items-center mt-4 no-print">

                    <div class="gap-2 d-flex">

                        <button type="submit" class="btn btn-primary px-4 shadow"
                            style="background-color: var(--unamba-blue);">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>


    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Mostrar/Ocultar indicaciones dinámicamente
    document.getElementById('pedir_evidencia').addEventListener('change', function() {
        document.getElementById('seccion_indicaciones').style.display = this.checked ? 'block' : 'none';
    });
</script>

<script>
$(document).ready(function() {


    // Inicializar DataTables
    var table = $('#tablaAsistencia').DataTable({
        "pageLength": 25,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "columnDefs": [{
            "orderable": false,
            "targets": [0, 4]
        }],
        "responsive": true
    });

    // Función para marcar/desmarcar todos
    var allChecked = false;
    $('#selectAll').click(function() {
        allChecked = !allChecked;
        $('.check-asistencia').prop('checked', allChecked);
        $(this).text(allChecked ? 'Desmarcar Todos' : 'Marcar Todos');
    });

    // Antes de enviar el formulario, DataTables a veces oculta inputs de otras páginas
    // Este código asegura que se envíen todos los checkboxes marcados
    $('#formAsistencia').on('submit', function(e) {
        var form = this;
        table.$('input[type="checkbox"]').each(function() {
            if (!$.contains(document, this)) {
                if (this.checked) {
                    $(form).append(
                        $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', this.name)
                        .val(this.value)
                    );
                }
            }
        });
        let seleccionados = $('.check-asistencia:checked').length;

        if (seleccionados === 0) {
            e.preventDefault();

            alert("⚠ Debe registrar asistencia de al menos un estudiante.");

            return false;
        }
    });
});


</script>


<?php require __DIR__ . '/../layouts/footer.php'; ?>