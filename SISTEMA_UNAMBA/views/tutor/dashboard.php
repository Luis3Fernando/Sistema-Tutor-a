<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>

<!-- Google Fonts e Iconos -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
:root {
    --unamba-blue: #003366;
    --unamba-gold: #C5A059;
    --danger-soft: #fff5f5;
    --warning-soft: #fffdf5;
    --success-soft: #f5fff8;
}

body {
    background-color: #f4f7f9;
    font-family: 'Inter', sans-serif;
}

.page-title { font-weight: 700; color: var(--unamba-blue); }

/* Estilo de Tarjetas de Indicadores (KPIs) */
.kpi-card {
    border: none;
    border-radius: 15px;
    transition: transform 0.2s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}
.kpi-card:hover { transform: translateY(-5px); }
.kpi-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.5rem;
}

/* Gráfico y Agenda */
.dashboard-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    background: #fff;
    height: 100%;
}
.dashboard-card .card-header {
    background: #fff;
    border-bottom: 1px solid #edf2f7;
    padding: 1.25rem;
    font-weight: 700;
    color: var(--unamba-blue);
}

/* Lista de Riesgo */
.risk-item {
    border-radius: 10px;
    transition: background 0.2s;
}
.risk-item:hover { background-color: var(--danger-soft); }

.area-tag {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 4px;
    background: #eee;
    color: #555;
    font-weight: 600;
}

/* Agenda minimalista */
.agenda-date {
    min-width: 50px;
    text-align: center;
    border-right: 2px solid #eee;
}
</style>

<style>
    /* Contenedor limitado para que no se estire al infinito */
    .agenda-compact-wrapper {
        max-width: 500px; 
    }

    .card-next-compact {
        background: linear-gradient(135deg, #003366 0%, #00509d 100%);
        border-radius: 16px;
        border: none;
        color: white;
        transition: transform 0.3s ease;
        box-shadow: 0 8px 20px rgba(0, 51, 102, 0.15);
    }

    .card-next-compact:hover {
        transform: translateY(-5px);
    }

    .badge-next-mini {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(4px);
        font-size: 0.65rem;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .icon-circle-mini {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-cronograma-sm {
        font-size: 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        border: 1px solid #003366;
        color: #003366;
        text-decoration: none;
        padding: 5px 12px;
    }
</style>

<div class="container-fluid px-4 py-4">
    
    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="page-title mb-0">Panel de Control Tutorial</h3>
            <p class="text-muted small mb-0">Bienvenido, Docente Tutor.</p>
        </div>
        <div class="text-end">
            <div class="small opacity-75">Semestre</div>
            <span class="badge bg-white text-primary border shadow-sm p-2">
                <?= htmlspecialchars($tutor['periodo_academico'] ?? 'No Asignado') ?>
            </span>
        </div>
    </div>

    <!-- FILA DE INDICADORES (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card h-100 shadow-sm border-start border-primary border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Tutorados</div>
                        <div class="h3 mb-0 fw-bold"><?= $total_estudiantes ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card h-100 shadow-sm border-start border-success border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-success-subtle text-success me-3">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Act. Pendientes</div>
                        <div class="h3 mb-0 fw-bold"><?= $activ_pendientes ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card h-100 shadow-sm border-start border-warning border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-warning-subtle text-warning me-3">
                        <i class="bi bi-send-exclamation"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Derivaciones</div>
                        <div class="h3 mb-0 fw-bold"><?= $deriv_pendientes ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card h-100 shadow-sm border-start border-danger border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-danger-subtle text-danger me-3">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">En Riesgo</div>
                        <div class="h3 mb-0 fw-bold text-danger"><?= $estudiante_riesgo ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ANÁLISIS DE DIAGNÓSTICO -->
        <div class="col-xl-8">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bar-chart-fill me-2"></i>Análisis de Diagnóstico Grupal</span>
                   
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="diagnosticoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATENCIÓN PRIORITARIA (ESTUDIANTES EN RIESGO) -->
        <div class="col-xl-4">
            <div class="card dashboard-card border-top border-danger border-3">
                <div class="card-header bg-white">
                    <span class="text-danger"><i class="bi bi-lightning-charge-fill me-2"></i>Atención Prioritaria</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                        <?php if($estudiantes_riesgo): ?>
                            <?php foreach($estudiantes_riesgo as $e): ?>
                                <div class="list-group-item risk-item p-3 border-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger-subtle text-danger rounded-circle p-2 me-3" style="width: 40px; height: 40px; display:flex; align-items:center; justify-content:center;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small"><?= $e['apellidos'].", ".$e['nombres']; ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Ciclo: <?= $e['ciclo_actual']; ?>°</div>
                                            </div>
                                        </div>
                                        <?php if($e['derivacion_pendiente'] > 0): ?>
                                            <span class="badge bg-light text-success border small"><i class="bi bi-check2"></i> Derivado</span>
                                        <?php else: ?>
                                            <a href="index.php?route=tutor/ver-expediente&id=<?= $e['id_usuario'] ?>&derivar=1" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.7rem;">Derivar</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <?php 
                                            if($e['nivel_salud_mental'] == 1) echo '<span class="area-tag">Salud Mental</span> ';
                                            if($e['nivel_personal_social'] == 1) echo '<span class="area-tag">Personal/Social</span> ';
                                            if($e['nivel_academico'] == 1) echo '<span class="area-tag">Académica</span> ';
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check display-4"></i>
                                <p class="mt-2">No hay estudiantes en riesgo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 agenda-compact-wrapper">
    <!-- Header pequeño -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark m-0">
            <i class="bi bi-calendar-event text-primary me-2"></i>Agenda Tutorial
        </h6>
        <a href="index.php?route=tutor/plan-trabajo" class="btn-cronograma-sm">
            Ver cronograma completo
        </a>
    </div>

    <?php if (!empty($agenda)): 
        $proxima = $agenda[0]; 
    ?>
        <!-- Tarjeta Reducida -->
        <div class="card card-next-compact shadow-sm">
            <div class="card-body p-3">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge badge-next-mini px-2 py-1">Próxima Actividad</span>
                    <i class="bi bi-pin-angle-fill opacity-50"></i>
                </div>

                <div class="mb-3">
                    <h5 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($proxima['actividad_tipo']) ?></h5>
                    <p class="small opacity-75 mb-0 text-truncate" style="max-width: 100%;">
                        <?= htmlspecialchars($proxima['objetivo_especifico']) ?>
                    </p>
                </div>

                <!-- Info de tiempo en una sola fila -->
                <div class="bg-white bg-opacity-10 rounded-3 p-2">
                    <div class="row g-0 align-items-center text-center">
                        <div class="col-6 border-end border-white border-opacity-10">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-calendar3 me-2 small opacity-75"></i>
                                <span class="small fw-bold"><?= date('d/m/Y', strtotime($proxima['fecha'])) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-clock me-2 small opacity-75"></i>
                                <span class="small fw-bold"><?= date('h:i A', strtotime($proxima['hora'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Estado vacío minimalista -->
        <div class="p-3 text-center border rounded-3 bg-light">
            <p class="small text-muted mb-0">No hay citas pendientes</p>
        </div>
    <?php endif; ?>
</div>

    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('diagnosticoChart').getContext('2d');
    const dataServer = <?= json_encode($dataGrafico) ?>;

     const diagnosticoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Personal/Social', 'Salud Mental', 'Académico', 'Vocacional'],
            datasets: [{
                    label: '🔴 Alto Riesgo',
                    data: dataServer.alto,
                    backgroundColor: 'rgba(231, 74, 59, 0.8)', // Rojo
                    borderColor: '#e74a3b',
                    borderWidth: 1
                },
                {
                    label: '🟡 Riesgo Medio',
                    data: dataServer.medio,
                    backgroundColor: 'rgba(246, 194, 62, 0.8)', // Amarillo
                    borderColor: '#f6c23e',
                    borderWidth: 1
                },
                {
                    label: '🟢 Adecuado',
                    data: dataServer.adecuado,
                    backgroundColor: 'rgba(28, 200, 138, 0.8)', // Verde
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    stacked: true, // Apilamos para ver el total de alumnos
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    suggestedMax: Math.max(
                        ...dataServer.alto,
                        ...dataServer.medio,
                        ...dataServer.adecuado
                    ) + 2,
                    title: {
                        display: true,
                        text: 'Cantidad de Estudiantes'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>