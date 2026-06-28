<?php
require_once __DIR__ . '/../../app/helpers/Auth.php';
$role = Auth::role();
$user = Auth::user();
$routeActual = $_GET['route'] ?? '';

$isActive = static function (array $routes, string $actual): string {
    return in_array($actual, $routes, true) ? 'is-active' : '';
};

$nombreCompleto = trim((string)(($user['nombre'] ?? '') ?: (($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''))));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Usuario';
}
$dashboardPorRol = 'index.php?route=login';
if ($role === 'administrador') {
    $dashboardPorRol = 'index.php?route=admin/dashboard';
} elseif ($role === 'tutor') {
    $dashboardPorRol = 'index.php?route=tutor/dashboard';
} elseif ($role === 'estudiante') {
    $dashboardPorRol = 'index.php?route=estudiante/dashboard';
} elseif ($role === 'especialista') {
    $dashboardPorRol = 'index.php?route=especialista/dashboard';
}
?>
<aside class="sidebar app-sidebar">
    <div class="sidebar-brand">

        <div>
            <h3>MENU</h3>

        </div>
    </div>

    <ul class="sidebar-menu">


        <?php if ($role === 'tutor'): ?>
        <li>
            <a class="<?= $isActive(['tutor/dashboard'], $routeActual) ?>" href="index.php?route=tutor/dashboard">📊
                <span>Dashboard</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['perfil/mis-datos'], $routeActual) ?>" href="index.php?route=perfil/mis-datos">👤
                <span>Mis datos</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['tutor/mis-estudiantes', 'tutor/historial-estudiante'], $routeActual) ?>"
                href="index.php?route=tutor/mis-estudiantes">👨‍🎓 <span>Mis estudiantes</span></a>
        </li>

         <li>
            <a class="<?= $isActive(['tutor/plan-trabajo'], $routeActual) ?>"
                href="index.php?route=tutor/plan-trabajo">📝 <span>Plan de Trabajo de Actividades Tutoriales</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['tutor/registrar-sesion'], $routeActual) ?>"
                href="index.php?route=tutor/registrar-sesion">📝 <span>Registrar sesión</span></a>
        </li>
       

        <li>
            <a class="<?= $isActive(['tutor/ver-expediente'], $routeActual) ?>"
                href="index.php?route=tutor/ver-expediente">
                📑 <span>Expediente del Tutorado
            </a>
        </li>
        <li>
          
            <a class="<?= $isActive(['tutor/reporte-general'], $routeActual) ?>"
                href="index.php?route=tutor/reporte-general">📝 <span>Reporte general</span></a>
        </li>
        <?php endif; ?>

        <?php if ($role === 'estudiante'): ?>
        <li>
            <a class="<?= $isActive(['estudiante/dashboard'], $routeActual) ?>"
                href="index.php?route=estudiante/dashboard">🏠 <span>Inicio</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['perfil/mis-datos'], $routeActual) ?>" href="index.php?route=perfil/mis-datos">👤
                <span>Mis datos</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['estudiante/mi-expediente'], $routeActual) ?>"
                href="index.php?route=estudiante/mi-expediente">📚 <span>Mis tutorías</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['estudiante/miSesion'], $routeActual) ?>"
                href="index.php?route=estudiante/miSesion">📋 <span>Mis Sesiones de Tutoría</span></a>
        </li>
       
        <?php endif; ?>

        <?php if ($role === 'administrador'): ?>
        <li class="sidebar-group-title">Administración</li>
        <li>
            <a class="<?= $isActive(['admin/dashboard'], $routeActual) ?>" href="index.php?route=admin/dashboard">📈
                <span>Dashboard</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['perfil/mis-datos'], $routeActual) ?>" href="index.php?route=perfil/mis-datos">👤
                <span>Mis datos</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['admin/usuarios'], $routeActual) ?>" href="index.php?route=admin/usuarios">👥
                <span>Usuarios</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['admin/asignaciones'], $routeActual) ?>"
                href="index.php?route=admin/asignaciones">🏫 <span>Escuelas y asignaciones</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['admin/sesiones'], $routeActual) ?>" href="index.php?route=admin/sesiones">🗓️
                <span>Sesiones de tutoría</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['admin/reportes'], $routeActual) ?>" href="index.php?route=admin/reportes">📑
                <span>Reportes consolidados</span></a>
        </li>
        <li>
            <a class="<?= $isActive(['admin/monitoreo'], $routeActual) ?>" href="index.php?route=admin/monitoreo">🧾 <span>Monitoreo
                    del sistema</span></a>
        </li>
        <?php endif; ?>
        
       <?php if ($role === 'especialista'): ?>
    <li class="sidebar-group-title">PANEL ESPECIALISTA</li>
    <li>
        <a class="<?= $isActive(['especialista/dashboard'], $routeActual) ?>" href="index.php?route=especialista/dashboard">
            <i class="bi bi-clipboard-pulse"></i> <!-- Un icono más profesional -->
            <span>Bandeja de Casos</span>
        </a>
    </li>
<?php endif; ?>

    </ul>
</aside>