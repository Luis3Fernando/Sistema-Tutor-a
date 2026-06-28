<?php 
declare(strict_types=1);
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../layouts/sidebar.php'; 

// Helpers UI
function getInitials(string $name): string {
    $words = explode(' ', $name);
    return strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
}

function getRiskBadge(int $level): string {
    return match($level) {
        1 => '<span class="badge-soft-danger"><i class="fas fa-arrow-up"></i> Alto</span>',
        2 => '<span class="badge-soft-warning"><i class="fas fa-minus"></i> Medio</span>',
        3 => '<span class="badge-soft-success"><i class="fas fa-check"></i> Adecuado</span>',
        default => '<span class="badge bg-light text-muted text-xs">N/A</span>'
    };
}
?>

<!-- Librerías -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="content"> <!-- Clase controlada por el CSS global -->
    <div class="container-fluid">
        
        <!-- HEADER -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-7">
                <h2 class="fw-bold text-navy mb-1">Reportes Consolidados</h2>
                
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <div class="btn-group shadow-sm bg-white p-1" style="border-radius: 10px;">
                    <button onclick="exportar('pdf')" class="btn btn-outline-danger border-0 px-3">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </button>
                    <button onclick="exportar('excel')" class="btn btn-outline-success border-0 px-3">
                        <i class="fas fa-file-excel me-2"></i>Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row mb-4 g-3">
            <!-- KPI Cobertura -->
            <div class="col-6 col-xl-3">
                <div class="card card-kpi shadow-sm" style="border-left: 4px solid #4e73df;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-xs fw-bold text-primary text-uppercase mb-1">Cobertura</p>
                                <h4 class="mb-0 fw-bold"><?= $kpis['cobertura_porcentaje'] ?>%</h4>
                            </div>
                            <div class="icon-shape bg-light text-primary"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- KPI Riesgo Alto -->
            <div class="col-6 col-xl-3">
                <div class="card card-kpi shadow-sm" style="border-left: 4px solid #e74a3b;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-xs fw-bold text-danger text-uppercase mb-1">Riesgo Alto</p>
                                <h4 class="mb-0 fw-bold"><?= $kpis['alto_riesgo'] ?></h4>
                            </div>
                            <div class="icon-shape bg-light text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- KPI Pendientes -->
            <div class="col-6 col-xl-3">
                <div class="card card-kpi shadow-sm" style="border-left: 4px solid #f6c23e;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-xs fw-bold text-warning text-uppercase mb-1">Pendientes</p>
                                <h4 class="mb-0 fw-bold"><?= $kpis['derivaciones_pendientes'] ?></h4>
                            </div>
                            <div class="icon-shape bg-light text-warning"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- KPI Sesiones -->
            <div class="col-6 col-xl-3">
                <div class="card card-kpi shadow-sm" style="border-left: 4px solid #1cc88a;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-xs fw-bold text-success text-uppercase mb-1">Sesiones</p>
                                <h4 class="mb-0 fw-bold"><?= $kpis['sesiones_totales'] ?></h4>
                            </div>
                            <div class="icon-shape bg-light text-success"><i class="fas fa-check-double"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRÁFICOS -->
        <div class="row mb-4 g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-bullseye me-2"></i>Eficacia</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartRadarImpacto"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-chart-bar me-2"></i>Riesgos por Escuela</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartRiesgoEscuela"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 reportes-consolidados-header border-0">
                <h6 class="m-0 fw-bold text-navy"> Detalle Estudiantil</h6>
                <div class="reportes-consolidados-filters">
                    <select class="form-select form-select-sm border-0 bg-light" id="filterEscuela" onchange="filtrarDatos()">
                        <option value="">Todas las Escuelas Profesionales</option>
                        <?php foreach($escuelas as $esc): ?>
                            <option value="<?= $esc['id_escuela'] ?>" <?= ($filtro_actual == $esc['id_escuela']) ? 'selected' : '' ?>>
                                <?= ucwords($esc['nombre_escuela']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tablaDetalleAdmin" class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-navy text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Escuela</th>
                            <th class="text-center">Académico</th>
                            <th class="text-center">Salud Mental</th>
                            <th class="text-center">P. Social</th>
                            <th class="text-center">Vocacional</th>
                            
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        <?php foreach($tablaDetalle as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <?= getInitials($row['nombres'] . ' ' . $row['apellidos']) ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold d-block text-dark"><?= $row['apellidos'] ?>, <?= $row['nombres'] ?></span>
                                        <small class="text-muted"><?= $row['codigo_unamba'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= ucwords($row['nombre_escuela']) ?></td>
                            <td class="text-center"><?= getRiskBadge((int)$row['nivel_academico']) ?></td>
                            <td class="text-center"><?= getRiskBadge((int)$row['nivel_salud_mental']) ?></td>
                            <td class="text-center"><?= getRiskBadge((int)$row['nivel_personal_social']) ?></td>
                            <td class="text-center"><?= getRiskBadge((int)$row['nivel_vocacional']) ?></td>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
// --- CONFIGURACIÓN RESPONSIVA DE CHART.JS ---
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false, // Permite que el contenedor de CSS controle el alto
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } }
    }
};

                        
// 1. FUNCIÓN DE EXPORTACIÓN (LA QUE TE FALTABA)
function exportar(tipo) {
    const filterEl = document.getElementById('filterEscuela');
    const idEscuela = filterEl ? filterEl.value : '';
    
    // Construir la URL exactamente como la espera tu controlador
    const url = `index.php?route=admin/reportes/exportar&tipo=${tipo}&id_escuela=${idEscuela}`;
    
    console.log("Iniciando descarga:", url);
    window.location.href = url;
}
$('#tablaDetalleAdmin').DataTable({
        "pageLength": 10,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "dom": '<"d-flex justify-content-between mb-3"f>rt<"d-flex justify-content-between mt-3"ip>',
        "order": [[0, "asc"]]
    });
// Radar
new Chart(document.getElementById('chartRadarImpacto'), {
    type: 'radar',
    data: {
        labels: ['Académico', 'Salud', 'Social', 'Vocacional'],
        datasets: [{
            label: 'Nivel Promedio',
            data: [<?= $graficoRadar['academico'] ?>, <?= $graficoRadar['salud'] ?>, <?= $graficoRadar['personal'] ?>, <?= $graficoRadar['vocacional'] ?>],
            backgroundColor: 'rgba(11, 60, 116, 0.2)',
            borderColor: '#0b3c74',
            pointBackgroundColor: '#0b3c74'
        }]
    },
    options: {
        ...commonOptions,
        scales: { r: { beginAtZero: true, max: 3, ticks: { stepSize: 1 } } }
    }
});

// Barras
new Chart(document.getElementById('chartRiesgoEscuela'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($graficoBarras, 'nombre_escuela')) ?>,
        datasets: [
            { 
                label: 'Riesgo Alto', 
                data: <?= json_encode(array_column($graficoBarras, 'riesgo_alto')) ?>, 
                backgroundColor: '#e74a3b',
                maxBarThickness: 30, // Reducimos el grosor porque ahora hay 3 barras por escuela
            },
            { 
                label: 'Riesgo Medio', 
                data: <?= json_encode(array_column($graficoBarras, 'riesgo_medio')) ?>, 
                backgroundColor: '#f6c23e',
                maxBarThickness: 30,
            },
            { 
                label: 'Adecuado', 
                data: <?= json_encode(array_column($graficoBarras, 'adecuado')) ?>, 
                backgroundColor: '#1cc88a',
                maxBarThickness: 30,
            }
        ]
    },
    options: {
        ...commonOptions,
        scales: { 
            y: { 
                stacked: false, // CAMBIAR A FALSE
                beginAtZero: true,
                title: { display: true, text: 'Cantidad de Indicadores', font: { size: 10 } }
            }, 
            x: { 
                stacked: false, // CAMBIAR A FALSE
                grid: { display: false }
            } 
        },
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

function filtrarDatos() {
    const idEscuela = document.getElementById('filterEscuela').value;
    window.location.href = `index.php?route=admin/reportes&id_escuela=${idEscuela}`;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>