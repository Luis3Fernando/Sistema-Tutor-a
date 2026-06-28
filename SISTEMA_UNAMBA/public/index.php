<?php


declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/controllers/TutorController.php';
require_once __DIR__ . '/../app/controllers/AlumnoController.php';
require_once __DIR__ . '/../app/controllers/ReporteController.php';
require_once __DIR__ . '/../app/controllers/PerfilController.php';
require_once __DIR__ . '/../app/controllers/EspecialistaController.php'; 
require_once __DIR__ . '/../vendor/autoload.php';

Auth::startSession();

$route = $_GET['route'] ?? 'login';
 // ESTA LÍNEA ES CLAVE: Ajusta la hora a Perú
    date_default_timezone_set('America/Lima'); 

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $view . '.php';
}

switch ($route) {
    case 'login':
        render('auth/login');
        break;

    case 'auth/process':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuarioModel = new Usuario();
        $user = $usuarioModel->findByEmail($email);

        if (!$user) {
            render('auth/login', ['error' => 'Usuario no encontrado.']);
            break;
        }

        $isValid = false;

        if (isset($user['password'])) {
            $hash = (string)$user['password'];
            $isValid = password_verify($password, $hash) || $password === $hash;
        }

        if (!$isValid) {
            render('auth/login', ['error' => 'Credenciales inválidas.']);
            break;
        }

        Auth::login($user);

        $role = Auth::role();
        if ($role === 'administrador') {
            header('Location: index.php?route=admin/dashboard');
        } elseif ($role === 'tutor') {
            header('Location: index.php?route=tutor/dashboard');
        } elseif ($role === 'estudiante') {
            header('Location: index.php?route=estudiante/dashboard');
        } elseif ($role === 'especialista') {
            header('Location: index.php?route=especialista/dashboard');
        } else {
            render('auth/login', ['error' => 'Rol de usuario no reconocido.']);
        }
        exit;

    case 'logout':
        Auth::logout();
        header('Location: index.php?route=login');
        exit;

    case 'tutor/dashboard':
        (new TutorController())->dashboard();
        break;

    case 'tutor/plan-trabajo':
       (new TutorController())->planTrabajo();
      break;
    
    case 'tutor/guardar-actividad':
        (new TutorController())->guardarActividad();
        break; 

   // case 'tutor/guardar':
       // (new TutorController())->guardarSesion();
        //break;

    case 'tutor/mis-estudiantes':
        (new TutorController())->misEstudiantes();
        break;

    case 'tutor/historial-estudiante':
        (new TutorController())->historialEstudiante();
        break;

   

    case 'estudiante/dashboard':
        (new AlumnoController())->dashboard();
        break;

    case 'estudiante/miSesion':
        (new AlumnoController())->miSesion();
        break;
        

    case 'estudiante/mi-expediente':
        (new AlumnoController())->miExpediente();
        break;

    case 'estudiante/solicitar':
        (new AlumnoController())->solicitar();
        break;

    case 'api/alumno/tutorias':
        (new AlumnoController())->misTutorias();
        break;

        case 'estudiante/actualizarDiagnostico':
        (new AlumnoController())->actualizarDiagnostico();
        break;

    case 'admin/dashboard':
        (new ReporteController())->dashboard();
        break;

    case 'admin/reportes':
        (new ReporteController())->reportesSAT();
        break;

    case 'admin/reportes/exportar':
        (new ReporteController())->exportar();
        break;
    
    case 'admin/usuarios':
        (new ReporteController())->usuarios();
        break;

    case 'admin/asignaciones':
        (new ReporteController())->escuelasAsignaciones();
        break;

    case 'admin/sesiones':
        (new ReporteController())->sesiones();
        break;

    case 'admin/monitoreo':
        (new ReporteController())->monitoreo();
        break;

    case 'api/reportes/resumen':
        (new ReporteController())->apiResumen();
        break;

    case 'perfil/mis-datos':
        (new PerfilController())->misDatos();
        break;

    case 'estudiante/progreso':
        (new AlumnoController())->progreso();
        break;
    
    case 'estudiante/guardarEvidencia':
        (new AlumnoController())->guardarEvidencia();
        break;

    case 'estudiante/reportes':
        require __DIR__ . '/../views/estudiante/reportes.php';
        break;
    
    case 'tutor/aceptar-solicitud':
       (new TutorController())->aceptarSolicitud();
       break;

    case 'tutor/bandeja-solicitudes':
        (new TutorController())->bandejaSolicitudes();
        break;

    case 'estudiante/reportes':
     (new AlumnoController())->reportes();
    break;

    case 'tutor/ver-expediente': 
        (new TutorController())->verExpediente();
        break;
     //case 'tutor/ver-Expediente':
       // (new TutorController())->verExpediente();
        //break;

      case 'tutor/registrar-sesion': // <--- Asegúrate que tenga el guion y el nombre exacto
        (new TutorController())->registroSesion();
        break;

    case 'tutor/exportar-excel': 
        (new TutorController())->exportarExcel();
        break;

    case 'tutor/exportar-pdf': 
        (new TutorController())->exportarPdf();
        break;
     case 'tutor/reporte-general': 
        (new TutorController())->reporteGeneral();
        break;

     case 'tutor/guardarAsistencia':
        (new TutorController())->guardarAsistencia();
        break;

    case 'tutor/historial-estudiante':
        (new TutorController())->historialEstudiante();
        break;

    case 'tutor/guardarSeguimiento':
        (new TutorController())->guardarSeguimiento();
        break;
      case 'tutor/inicializar-plan':
        (new TutorController())->inicializarPlan();
        break;
    
case 'tutor/guardarDerivacion':
    (new TutorController())->guardarDerivacion();
    break;
case 'tutor/editarDerivacion':
    (new TutorController())->editarDerivacion();
    break;
case 'tutor/eliminarDerivacion':
    (new TutorController())->eliminarDerivacion();
    break;
// Añade esto en tu switch de rutas:
case 'tutor/verDetalleSesion':
    (new TutorController())->verDetalleSesion();
    break;

// Dentro del switch de rutas
case 'especialista/dashboard':
    (new EspecialistaController())->dashboard();
    break;

case 'especialista/guardarAtencion':
    (new EspecialistaController())->guardarAtencion();
    break;
 case 'especialista/programarCita':
        (new EspecialistaController())->programarCita();
        break;

    default:
        http_response_code(404);
        echo 'Ruta no encontrada.';
        break;
}
