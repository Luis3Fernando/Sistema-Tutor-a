<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/helpers/Auth.php';

Auth::requireAuth();  // Requiere login

// Verifica rol específico
if (Auth::role() !== 'administrador') {
    header('Location: index.php?route=login');
    exit;
}
?>

<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>


<div class="container-fluid px-4 py-4">
    
    <!-- Header de Bienvenida -->
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h1 class="h3 fw-black text-dark text-uppercase fst-italic mb-1">Visualización de desempeño académico</h1>
            
        </div>
        
    </header>

    <!-- Grid de KPIs -->
    <div class="row g-3 mb-4">
        <!-- KPI 1 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stats h-100 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="small fw-bold text-muted text-uppercase tracking-wider mb-1">Estudiantes</p>
                        <h3 class="fw-black text-dark mb-0"><?= number_format($kpis['total_estudiantes']) ?></h3>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="fas fa-graduation-cap fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stats h-100 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="small fw-bold text-muted text-uppercase tracking-wider mb-1">Total de tutores</p>
                        <h3 class="fw-black text-dark mb-0"><?= $kpis['total_tutores'] ?></h3>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3">
                        <i class="fas fa-user-graduate fs-4"></i>
                    </div>
                </div>
              
            </div>
        </div>
        
        <!-- KPI 2 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stats h-100 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="small fw-bold text-muted text-uppercase tracking-wider mb-1">Sesiones Ejecutadas</p>
                        <h3 class="fw-black text-dark mb-0"><?= $kpis['sesiones_ejecutadas'] ?></h3>
                    </div>
                    <div class="p-3 bg-unamba-light rounded-3">
                        <i class="fas fa-calendar-check fs-4 text-unamba"></i>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- KPI 3 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-stats h-100 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="small fw-bold text-muted text-uppercase tracking-wider mb-1">Derivaciones</p>
                        <h3 class="fw-black text-dark mb-0"><?= $kpis['derivaciones_pendientes'] ?></h3>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3">
                        <i class="fas fa-exchange-alt fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-danger small fw-bold"><?= $kpis['derivaciones_pendientes'] ?> Pendientes</span> <span class="small text-muted">de atención</span>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Gráficos -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm p-4 h-100">
                <h3 class="h6 fw-bold text-dark mb-4 d-flex align-items-center">
                    <i class="fas fa-university me-2 text-unamba"></i> Cumplimiento por Escuela Profesional
                </h3>
                <div style="height: 300px;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm p-4 h-100">
                <h3 class="h6 fw-bold text-dark mb-4 d-flex align-items-center">
                    <i class="fas fa-brain me-2 text-danger"></i> Distribución de Diagnósticos
                </h3>
                <div style="height: 300px;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    
</div>
<!-- Enlaces a Bootstrap 5 y FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Configuración de Gráfico de Barras
    new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?php echo $labels_grafico; ?>, 
        datasets: [
            {
                label: 'Ejecución Actividades',
                data: <?php echo $valores_actividades; ?>,
                backgroundColor: '#220650', // Morado oscuro
                borderRadius: 5
            },
            {
                label: 'Cobertura Alumnos ',
                data: <?php echo $valores_cobertura; ?>,
                backgroundColor: '#ffc107', // Amarillo/Dorado
                borderRadius: 5
            },
            {
                label: 'Casos Críticos ',
                data: <?php echo $valores_casos; ?>,
                backgroundColor: '#dc3545', // Rojo
                borderRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                display: true, 
                position: 'bottom' // Mostramos la leyenda para saber qué es cada barra
            } 
        },
        scales: { 
            y: { 
                beginAtZero: true, 
                max: 100,
                title: { display: true, text: 'Porcentaje de Logro' },
                ticks: { callback: function(value) { return value + "%"; } }
            } 
        }
    }
});
const datosRecibidos = <?php echo $valores_diagnostico; ?>;
console.log("Datos para el gráfico:", datosRecibidos);
    // Configuración de Gráfico de Pie
 new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Personal Social', 'Salud Mental', 'Vocacional', 'Académico'],
        datasets: [{
            // PHP imprime: [10, 5, 8, 12]
            data: <?php echo $valores_diagnostico; ?>, 
            backgroundColor: ['#dc2626', '#f59e0b', '#3b82f6', '#198754'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed;
                        // Suma los valores del array para sacar el total
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = total > 0 ? ((value * 100) / total).toFixed(1) + '%' : '0%';
                        return `${label}: ${value} casos (${percentage})`;
                    }
                }
            }
        }
    }
});
</script>

   
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
