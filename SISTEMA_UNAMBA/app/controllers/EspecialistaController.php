<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/RoleMiddleware.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../models/Tutoria.php';
require_once __DIR__ . '/../models/Derivacion.php';

class EspecialistaController
{
    // ===============================
    // DASHBOARD ESPECIALISTA
    // ===============================
    
public function dashboard(): void
{
    // 1. Seguridad
    RoleMiddleware::requireRole(['especialista']);

    // 2. Obtener datos del usuario logueado
    $user = Auth::user();

    // 3. DEFINIR LA VARIABLE $id (Esto es lo que te faltaba)
    // Intentamos obtener el ID ya sea que se llame 'id_usuario' o solo 'id'
    $id = (int)($user['id_usuario'] ?? $user['id'] ?? 0);

    // Validación de seguridad por si el ID falló
    if ($id === 0) {
        die("Error: No se pudo identificar el ID del especialista. Revisa la sesión.");
    }

    // 4. Inicializar Modelos
    $tutoriaModel = new Tutoria();
    $derivacionModel = new Derivacion();

    // 5. Obtener área y estadísticas usando el $id ya definido
    $detalle = $tutoriaModel->obtenerDetalleEspecialista($id);
    if (!$detalle) {
        die('Especialista sin área asignada');
    }

    $area = $detalle['area'];

    // 6. Cargar datos para la vista
    $stats = $derivacionModel->obtenerEstadisticasEspecialista($id); // <-- Línea 37 corregida
    $derivaciones = $derivacionModel->derivacionesPendientesPorArea($area, $id);
    $agendaHoy = $derivacionModel->obtenerAgendaHoy($id);

    // 7. Enviar a la vista
    render('especialista/dashboard', [
        'stats'        => $stats,
        'derivaciones' => $derivaciones,
        'area'         => $area,
        'user'         => $user,
        'agendaHoy'    => $agendaHoy
    ]);
}


    // ===============================
    // GUARDAR ATENCIÓN
    // ===============================
    public function guardarAtencion(): void
    {
        RoleMiddleware::requireRole(['especialista']);

        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id_derivacion = (int)$_POST['id_derivacion'];
            $acciones = trim($_POST['acciones_realizadas']);

            if (empty($acciones)) {
                $_SESSION['flash_error'] =
                    "Debe describir las acciones.";
                header('Location: index.php?route=especialista/dashboard');
                exit;
            }

            $model = new Derivacion();

            $ok = $model->registrarAtencion(
                $id_derivacion,
               // (int)$user['id_usuario'],
                (int)$user['id'],
                $acciones
            );

            $_SESSION['flash_success'] = $ok
                ? "Caso atendido correctamente"
                : "Error al registrar atención";
        }

        header('Location: index.php?route=especialista/dashboard');
        exit;
    }
   public function programarCita()
{
    // 1. Validar que el usuario tenga el rol (Seguridad)
    RoleMiddleware::requireRole(['especialista']);

    // 2. Obtener el usuario actual (ESTO FALTABA)
    $user = Auth::user();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $derivacionModel = new Derivacion();

        // 3. Preparar los datos del formulario
        $datosCita = [
            'id_derivacion' => (int)$_POST['id_derivacion'],
            'fecha_cita'    => $_POST['fecha_cita'],
            'hora_cita'     => $_POST['hora_cita'],
            'modalidad'     => $_POST['modalidad']
        ];

        // 4. Llamar al modelo pasando el ID del especialista
        // Nota: Asegúrate si tu sesión usa 'id' o 'id_usuario'
        $idEspecialista = (int)($user['id_usuario'] ?? $user['id']);

        $ok = $derivacionModel->registrarCita($datosCita, $idEspecialista);

        if ($ok) {
            $_SESSION['flash_success'] = "Cita programada correctamente.";
        } else {
            $_SESSION['flash_error'] = "Error al programar la cita.";
        }
    }

    header("Location: index.php?route=especialista/dashboard");
    exit;
}
}