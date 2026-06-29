<?php 
require_once __DIR__.'/../layouts/header.php';
require_once __DIR__.'/../layouts/sidebar.php';

/* ================= CALCULOS ================= */

$total = count($derivaciones ?? []);

$pendientes = count(array_filter(
    $derivaciones ?? [],
    fn($d)=>$d['estado_atencion']==='Pendiente'
));

$finalizados = count(array_filter(
    $derivaciones ?? [],
    fn($d)=>$d['estado_atencion']==='Cerrado'
));



/* ===== PRIORIDAD AUTOMATICA ===== */
function prioridadCaso($fecha){
    $dias = (new DateTime($fecha))->diff(new DateTime())->days;

    if($dias >= 7) return ['clase'=>'danger','texto'=>'Crítico'];
    if($dias >= 3) return ['clase'=>'warning','texto'=>'Urgente'];
    return ['clase'=>'success','texto'=>'Normal'];
}

/* ===== AGENDA SIMULADA (Luego viene DB) ===== */
$agendaHoy = $agendaHoy ?? [];

?>

<div class="main-content">
    <div class="container-fluid p-4">

        <!-- ================= HEADER ================= -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold">Panel Psicopedagógico</h2>
                <p class="text-muted">
                    Tienes <strong><?= $pendientes ?></strong> casos pendientes hoy.
                </p>
            </div>

            <div class="col-md-4 text-end">

                <span class="badge bg-success">
                    Especialista Activo
                </span>

            </div>
        </div>


        <!-- ================= ALERTA ================= -->
        <?php if($pendientes >=5): ?>
        <div class="alert alert-danger shadow-sm">
            ⚠️ Alto número de casos pendientes. Priorizar atención inmediata.
        </div>
        <?php endif; ?>



        <!-- ================= KPIs ================= -->
        <div class="row mb-4">

            <!-- TOTAL -->
            <div class="col-md-4">
                <div class="card stat-card card-blue shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-primary text-white me-3">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div>
                            <h3><?= $stats['total'] ?? 0 ?></h3>
                            <small class="text-muted">Total Asignados</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PENDIENTES -->
            <div class="col-md-4">
                <div class="card stat-card card-orange shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-warning text-dark me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h3><?= $stats['pendientes'] ?? 0 ?></h3>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FINALIZADOS -->
            <div class="col-md-4">
                <div class="card stat-card card-green shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-success text-white me-3">
                            <i class="bi bi-check-all"></i>
                        </div>
                        <div>
                            <h3><?= $stats['finalizados'] ?? 0 ?></h3>
                            <small class="text-muted">Finalizados (Cerrados)</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- ================= AGENDA MEJORADA ================= -->
        <div class="card shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-calendar3 me-2"></i> Próximas Citas en Agenda
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($agendaHoy)): ?>
                <div class="text-center py-4">
                    <p class="text-muted italic">No hay citas próximas programadas.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr class="text-muted" style="font-size: 0.85rem;">
                                <th>FECHA / HORA</th>
                                <th>ESTUDIANTE</th>
                                <th>MODALIDAD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendaHoy as $cita): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark">
                                        <?= date('d/m/Y', strtotime($cita['fecha_cita'])) ?>
                                    </span><br>
                                    <small class="text-primary"><?= substr($cita['hora_cita'], 0, 5) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($cita['estudiante']) ?></div>
                                    <div style="font-size: 0.75rem;" class="text-muted">
                                        <?= htmlspecialchars($cita['nombre_escuela']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($cita['modalidad'] === 'Virtual'): ?>
                                    <span class="badge bg-info text-dark">Virtual</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Presencial</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>



        <!-- ================= GRAFICO ================= -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-center">
                <div style="width: 260px; height: 260px;">
                    <canvas id="graficoCasos"></canvas>
                </div>
            </div>
        </div>



        <!-- ================= TABLA ================= -->
        <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between">
                <h5>Casos Recibidos</h5>
                <input id="buscar" class="form-control w-25" placeholder="Buscar estudiante">
            </div>

            <div class="table-responsive">

                <table class="table table-hover" id="tablaCasos">

                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Escuela</th>
                            <th>Motivo</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if(empty($derivaciones)): ?>
                        <tr>
                            <td colspan="6" class="text-center p-4">Sin casos.</td>
                        </tr>
                        <?php else: foreach($derivaciones as $d): 

$prioridad = prioridadCaso($d['fecha_derivacion']);
?>

                        <tr>

                            <td>
                                <strong><?= $d['est_apellido'].', '.$d['est_nombre'] ?></strong><br>
                                <small><?= $d['codigo_unamba'] ?></small>
                            </td>

                            <td><?= $d['nombre_escuela'] ?></td>

                            <td class="text-truncate" style="max-width:200px;">
                                <?= htmlspecialchars($d['motivo_informe']) ?>
                            </td>

                            <td>
                                <span class="badge bg-<?= $prioridad['clase'] ?>">
                                    <?= $prioridad['texto'] ?>
                                </span>
                            </td>

                            <td>
                                <?php
$estadoClase = match($d['estado_atencion']){
    'Pendiente' => 'bg-warning text-dark',
    'Atendido'  => 'bg-info text-white', // Color azul claro para casos en proceso
    'Cerrado'   => 'bg-success',
    default     => 'bg-secondary'
};
?>

                                <span class="badge <?= $estadoClase ?>">
                                    <?= $d['estado_atencion'] ?>
                                </span>
                            </td>

                            <td class="text-end">

                                <button class="btn btn-primary btn-sm abrirCaso" data-id="<?= $d['id_derivacion'] ?>"
                                    data-resumen="<?= htmlspecialchars($d['resumen_caso']) ?>" data-bs-toggle="modal"
                                    data-bs-target="#modalAtencion">

                                    Abrir

                                </button>
                                <button class="btn btn-warning btn-sm abrirCita" data-id="<?= $d['id_derivacion'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalCita">

                                    Citar
                                </button>

                            </td>

                        </tr>

                        <?php endforeach; endif; ?>

                    </tbody>

                </table>

            </div>
        </div>


    </div>
</div>



<!-- ================= MODAL ================= -->
<div class="modal fade" id="modalAtencion">
    <div class="modal-dialog modal-lg">

        <form action="index.php?route=especialista/guardarAtencion" method="POST" class="modal-content">

            <input type="hidden" name="id_derivacion" id="modal_id">

            <div class="modal-header">
                <h5>Ficha Psicopedagógica</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <label class="fw-bold">Resumen Tutor</label>
                <div id="modal_resumen" class="border p-3 mb-3 bg-light"></div>

                <label>Acciones realizadas</label>
                <textarea name="acciones_realizadas" class="form-control" rows="6" required></textarea>

            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Finalizar Atención</button>
            </div>

        </form>

    </div>
</div>

<div class="modal fade" id="modalCita">
    <div class="modal-dialog">

        <form action="index.php?route=especialista/programarCita" method="POST" class="modal-content">

            <input type="hidden" name="id_derivacion" id="cita_id">

            <div class="modal-header">
                <h5>Programar Cita con Estudiante</h5>ç
                 <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <label>Fecha</label>
                <input type="date" name="fecha_cita" class="form-control" required>

                <label class="mt-2">Hora</label>
                <input type="time" name="hora_cita" class="form-control" required>

                <label class="mt-2">Modalidad</label>
                <select name="modalidad" class="form-control">
                    <option>Presencial</option>
                    <option>Virtual</option>
                </select>

            </div>

            <div class="modal-footer">
                <button class="btn btn-warning">Guardar Cita</button>
            </div>

        </form>

    </div>
</div>




<!-- ================= JS ================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* MODAL */
document.querySelectorAll('.abrirCaso').forEach(btn => {
    btn.onclick = function() {
        modal_id.value = this.dataset.id;
        modal_resumen.textContent = this.dataset.resumen;
    }
});

document.querySelectorAll('.abrirCita').forEach(btn => {
    btn.onclick = function() {
        cita_id.value = this.dataset.id;
    }
});

/* BUSCADOR */
buscar.addEventListener("keyup", function() {
    let f = this.value.toLowerCase();
    document.querySelectorAll("#tablaCasos tbody tr").forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(f) ? "" : "none";
    });
});


/* GRAFICO */
new Chart(document.getElementById('graficoCasos'), {
    type: 'doughnut',
    data: {
        labels: ['Pendientes', 'Finalizados'],
        datasets: [{
            // Usamos el array $stats igual que en las cards superiores
            data: [
                <?= $stats['pendientes'] ?? 0 ?>, 
                <?= $stats['finalizados'] ?? 0 ?>
            ],
            backgroundColor: ['#ffc107', '#28a745'], // Amarillo y Verde
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php require __DIR__.'/../layouts/footer.php'; ?>