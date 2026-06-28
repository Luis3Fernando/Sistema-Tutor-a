<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #002147; font-weight: bold;">
            <i class="fas fa-chart-pie mr-2"></i> Reporte Consolidado de Tutoría
        </h2>
        <div>
            <a href="index.php?route=tutor/exportar-excel" class="btn btn-success shadow-sm">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <a href="index.php?route=tutor/exportar-pdf" class="btn btn-danger shadow-sm">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
        </div>
    </div>

    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header text-white" style="background: #002147;">
            <h5 class="mb-0"><i class="fas fa-users mr-2"></i> Estado de Tutorados (Periodo <?= $periodo ?>)</h5>
        </div>
        <div class="card-body bg-light">
            <div class="table-responsive">
                <table class="table table-hover table-bordered bg-white">
                    <thead style="background: #f8f9fc; color: #002147;">
                        <tr class="text-center">
                            <th>Código</th>
                            <th>Estudiante</th>
                            <th>Ciclo</th>
                            <th>Situación</th>
                            <th>Asistencias</th>
                            <th>Nivel de Riesgo</th>
                            <th>Derivaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dataReporte as $row): 
                            // Lógica de semáforo de riesgo
                            $maxRiesgo = min($row['nivel_personal_social'] ?? 3, $row['nivel_salud_mental'] ?? 3, $row['nivel_academico'] ?? 3, $row['nivel_vocacional'] ?? 3);
                            $badgeClass = ($maxRiesgo == 1) ? 'badge-danger' : (($maxRiesgo == 2) ? 'badge-warning' : 'badge-success');
                            $badgeText = ($maxRiesgo == 1) ? 'Alto' : (($maxRiesgo == 2) ? 'Medio' : 'Estable');
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?= $row['codigo_unamba'] ?></td>
                            <td><?= $row['estudiante_nombre'] ?></td>
                            <td class="text-center"><?= $row['ciclo_actual'] ?>°</td>
                            <td class="text-center">
                                <span
                                    class="badge badge-pill text-dark <?= $row['situacion_academica'] == 'Regular' ? 'badge-info' : 'badge-warning' ?>"
                                    style="font-weight: 600;">
                                    <?= $row['situacion_academica'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="h6"><?= $row['total_asistencias'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badgeClass ?> p-2" style="min-width: 80px;">
                                    <?= $badgeText ?>
                                </span>
                            </td>
                            <td class="text-center font-weight-bold text-primary">
                                <?= $row['total_derivaciones'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table thead th {
    border-top: none;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.badge-danger {
    background-color: #e74a3b;
}

.badge-warning {
    background-color: #f6c23e;
    color: #333;
}

.badge-success {
    background-color: #1cc88a;
}

.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}
</style>

<?php require __DIR__ . '/../layouts/footer.php'; ?>