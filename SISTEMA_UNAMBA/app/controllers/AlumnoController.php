<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/RoleMiddleware.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../models/Tutoria.php';

require_once __DIR__ . '/../models/Derivacion.php';

class AlumnoController
{
 public function dashboard(): void
{
    RoleMiddleware::requireRole(['estudiante', 'administrador']);
    $user = Auth::user();
    $idUsuario = (int)$user['id'];

    $tutoriaModel = new Tutoria();
    $derivacionModel = new Derivacion();

    
    $estudiante = $tutoriaModel->obtenerEstudiantePorUsuario($idUsuario);

$idEstudiante = (int)($estudiante['id_estudiante'] ?? 0);

if ($idEstudiante === 0) {
    die('Estudiante no encontrado');
}

//$riesgos = $tutoriaModel->obtenerNivelesRiesgo($idUsuario);
$riesgos = $tutoriaModel->obtenerNivelesRiesgo($idEstudiante);

$citasEspecialista = $tutoriaModel->obtenerCitasProgramadas($idEstudiante);
$citasEspecialista = $tutoriaModel->obtenerCitasEspecialista($idEstudiante);


    
// ahora sí traer citas
 

    // 1. Corregir el nombre del usuario (buscamos en DB si no está en sesión)
    $datosEstudiante = $tutoriaModel->obtenerDetalleEstudiante($idUsuario);
    $nombresUsuario = $datosEstudiante['nombres'] ?? 'Estudiante';

    // 2. Obtener Sesiones de Ejecución (Historial)
    $sesiones = $tutoriaModel->listarSesionesPorEstudiante($idUsuario) ?? [];

    // 3. Obtener Actividades Programadas (Planificación)
    $actividades = $tutoriaModel->listarActividadesProgramadas($idUsuario) ?? [];

    // 4. Obtener Derivaciones
    $derivaciones = $derivacionModel->listarConCitasPorEstudiante($idUsuario) ?? [];

    // 5. Cálculos para los KPIs del Dashboard
    $stats = [
        'total_realizadas' => count(array_filter($sesiones, fn($s) => !empty($s['archivo_evidencia']))),
        'total_pendientes' => count($actividades),
        'derivaciones_activas' => count(array_filter($derivaciones, fn($d) => $d['estado_atencion'] !== 'Cerrado')),
    ];

    // 6. Lógica de Próxima Cita (la más cercana a hoy)
    $proximaCita = null;
    if (!empty($actividades)) {
        $hoy = date('Y-m-d');
        foreach ($actividades as $act) {
            if ($act['fecha'] >= $hoy) {
                $proximaCita = [
                    'tipo' => $act['actividad_tipo'],
                    'fecha' => $act['fecha'],
                    'hora' => $act['hora'],
                    'objetivo' => $act['objetivo_especifico'],
                    'icono' => 'bi-calendar-event'
                ];
                break;
            }
        }
    }

    // 7. Enviar datos a la vista con nombres consistentes
    extract([
        'user_name' => $nombresUsuario,
        'sesiones' => $sesiones,
        'actividades' => $actividades,
        'derivaciones' => $derivaciones,
        'stats' => $stats,
        'proximaCita' => $proximaCita,

        'riesgos' => $riesgos,
        'citasEspecialista' => $citasEspecialista
    ]);

    require __DIR__ . '/../../views/estudiante/dashboard.php';
}

/**
 * Función auxiliar privada para extraer la lógica compleja de la próxima cita
 */
private function obtenerProximaCita(array $sesiones, $solicitudModel, int $idEstudiante): ?array
{
    $hoy = strtotime(date('Y-m-d'));

    // Buscar primero en sesiones programadas
    foreach ($sesiones as $s) {
        if (in_array(strtolower($s['estado_sesion']), ['pendiente', 'programada', 'aceptada'])) {
            if (strtotime($s['fecha_ejecucion']) >= $hoy) {
                return $s;
            }
        }
    }

    // Fallback a solicitudes aceptadas si no hay sesión formal creada
    $solicitudesAceptadas = $solicitudModel->listarPorEstudianteEstado($idEstudiante, 'Aceptado') ?? [];
    foreach ($solicitudesAceptadas as $sol) {
        if (!empty($sol['fecha_ejecucion']) && strtotime($sol['fecha_ejecucion']) >= $hoy) {
            return [
                'fecha_ejecucion' => $sol['fecha_ejecucion'],
                'hora_ejecucion'  => $sol['hora_ejecucion'] ?? $sol['hora'],
                'tipo_tutoria'    => $sol['tipo_tutoria'],
                'estado_sesion'   => 'Solicitud Aceptada'
            ];
        }
    }

    return null;
}
   
 public function miExpediente(): void
{
    RoleMiddleware::requireRole(['estudiante']);
    $user = Auth::user();
    $estudianteId = (int)$user['id'];

    $tutoriaModel = new Tutoria();
    
    // Obtenemos su información específica
   
    $estudiante = $tutoriaModel->obtenerDetalleEstudiante($estudianteId);
    $diagnostico = $tutoriaModel->obtenerDiagnosticoActual($estudianteId);
    $seguimientos = $tutoriaModel->listarSeguimientosPorEstudiante($estudianteId);
    $derivaciones = $tutoriaModel->listarDerivacionesPorEstudiante($estudianteId);

    // Esta función es la única que debe quedar
    render('estudiante/mi_expediente', [
        
        'estudiante' => $estudiante,
        'diagnostico' => $diagnostico,
        'seguimientos' => $seguimientos,
        'derivaciones' => $derivaciones,
        'isEstudiante' => true
    ]);

    // ELIMINAMOS ESTA LÍNEA:
    // require __DIR__ . '/../../views/estudiante/mi_expediente.php'; 
}


public function actualizarDiagnostico(): void
{
    RoleMiddleware::requireRole(['estudiante']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('estudiante/mi-expediente');
    }

    $user = Auth::user();
    $tutoriaModel = new Tutoria();

    // Mapeo directo de POST a BD
    $campos = [
        'fecha_actividad', 'hora_inicio', 'hora_fin',
        'p_entorno_uni', 'p_apoyo_social', 'p_manejo_estres', 'p_integracion',
        's_alimentacion_sueno', 's_ejercicio', 's_concentracion', 's_ansiedad_estres', 's_manejo_emocional', 's_consumo_sustancias', 's_riesgos_sustancias',
        'a_rendimiento', 'a_dificultad_curso', 'a_tecnicas_estudio', 'a_asistencia', 'a_organizacion_tiempo', 'a_apoyo_academico',
        'v_carrera_adecuada', 'v_metas', 'v_actividades_refuerzo', 'v_dificultades'
    ];

    $datos = [];
    foreach ($campos as $campo) {
        $datos[$campo] = $_POST[$campo] ?? null;
    }

    if ($tutoriaModel->guardarOActualizarDiagnostico((int)$user['id'], $datos)) {
        $_SESSION['flash_success'] = "¡Diagnóstico guardado correctamente!";
    } else {
        $_SESSION['flash_error'] = "Error al guardar el diagnóstico en la base de datos.";
    }

    header('Location: index.php?route=estudiante/mi-expediente');
    exit;
}
    public function solicitar(): void
    {
        RoleMiddleware::requireRole(['estudiante', 'administrador']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido';
            return;
        }

        $user = Auth::user();
        $id_estudiante = (int)($user['id'] ?? 0);

        $fecha = trim($_POST['fecha'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        $prioridad = trim($_POST['prioridad'] ?? '');

        $errores = [];
        if (empty($fecha) || !strtotime($fecha)) {
            $errores[] = 'Fecha inválida';
        }
        if (empty($motivo)) {
            $errores[] = 'Motivo requerido';
        }

        // Validación dinámica de tipo_tutoria desde BD
        $solicitudModel = new SolicitudesTutoria();
        $tiposDisponibles = $solicitudModel->getTipos();
        $tiposValidos = array_column($tiposDisponibles, 'tipo_tutoria');
        
        if (!in_array($tipo, $tiposValidos)) {
            $errores[] = 'Tipo de tutoría no válido';
        }

        if (empty($errores)) {
            $exito = $solicitudModel->crearSolicitud([
                'id_estudiante' => $id_estudiante,
                'fecha' => $fecha,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'prioridad' => $prioridad
            ]);
            
            if ($exito) {
                $_SESSION['flash_solicitud_ok'] = 'Solicitud enviada correctamente. El tutor revisará pronto.';
            } else {
                $_SESSION['flash_errores'] = ['Error al guardar la solicitud. Intenta nuevamente.'];
            }
        } else {
            $_SESSION['flash_errores'] = $errores;
        }
        
        header('Location: index.php?route=estudiante/mis-tutorias');
        exit;
        
    }

    public function reportes(): void
{
    // 1. Seguridad
    RoleMiddleware::requireRole(['estudiante', 'administrador']);
    $user = Auth::user();
    $idEstudiante = (int)$user['id'];

    // 2. Modelos
    $tutoriaModel = new Tutoria();
    $evaluacionModel = new Evaluacion();

    // 3. Obtención de Datos (Lista completa de todas sus sesiones)
    $sesiones = $tutoriaModel->listarPorEstudiante($idEstudiante);
    
    // 4. Promedio de Satisfacción
    $promedioData = $evaluacionModel->promedioPorEstudiante($idEstudiante);
    $promedio = !empty($promedioData['promedio']) ? round((float)$promedioData['promedio'], 1) : 0.0;

    // 5. Estado de Riesgo Actual (El de la última sesión realizada)
    $ultimoRiesgo = 'Estable';
    foreach ($sesiones as $s) {
        if (in_array($s['estado_sesion'], ['Realizada', 'Evaluada'])) {
            $ultimoRiesgo = $s['riesgo_texto'] ?? $s['nivel_riesgo_detectado'] ?? 'Estable';
            break; 
        }
    }

    $estadoBadge = match(strtolower($ultimoRiesgo)) {
        'alto', 'crítico', 'critico' => 'bg-danger',
        'medio', 'en observación'    => 'bg-warning text-dark',
        default                      => 'bg-success'
    };

    $estadoActual = ['text' => $ultimoRiesgo];

    // 6. Preparar Datos para el Gráfico del Reporte
    $chartLabels = [];
    $chartData = [];
    // Ordenamos cronológicamente para el gráfico (Pasado -> Presente)
    $historico = array_reverse($sesiones); 
    
    foreach ($historico as $s) {
        $fechaRaw = $s['fecha_ejecucion'] ?? '';
        $ts = strtotime($fechaRaw);
        
        if ($ts && $fechaRaw !== '0000-00-00') {
            $chartLabels[] = date('d/m', $ts);
        } else {
            $chartLabels[] = 'Pend.';
        }

        $chartData[] = match(strtolower($s['riesgo_texto'] ?? $s['nivel_riesgo_detectado'] ?? 'bajo')) {
            'alto', 'crítico', 'critico' => 3,
            'medio', 'en observación'    => 2,
            default                      => 1
        };
    }

    // 7. Pasar todo a la vista (Aquí definimos $sesionesFiltradas)
    extract([
        'sesionesFiltradas' => $sesiones,
        'promedio' => $promedio,
        'estadoBadge' => $estadoBadge,
        'estadoActual' => $estadoActual,
        'chartLabels' => $chartLabels,
        'chartData' => $chartData
    ]);

    require __DIR__ . '/../../views/estudiante/reportes.php';
}

public function progreso(): void
{
    RoleMiddleware::requireRole(['estudiante', 'administrador']);
    
    $user = Auth::user();
    $tutoriaModel = new Tutoria();
    $evaluacionModel = new Evaluacion();

    // 1. Obtener todas las sesiones
    $todasSesiones = $tutoriaModel->listarPorEstudiante((int)$user['id']);
    
    // 2. Preparar datos para el gráfico (últimas 10 sesiones)
    $sesionesGrafico = array_reverse(array_slice($todasSesiones, 0, 100));
    
    
    $labels = []; $dataSatisfaccion = []; $dataRiesgo = []; $temas = [];
    $totalEval = 0; $conEval = 0;

    foreach ($sesionesGrafico as $index => $s) {
        $avg = $evaluacionModel->getAvgBySession((int)$s['id_sesion']);
        
        $fecha = isset($s['fecha_ejecucion']) ? date('d/m', strtotime($s['fecha_ejecucion'])) : 'S/D';
        $labels[] = "Sesión " . ($index + 1) . " ($fecha)";
        $dataSatisfaccion[] = $avg !== false ? round($avg, 2) : 0;
        $dataRiesgo[] = Evaluacion::mapearRiesgoANumero($s['nivel_riesgo_detectado']);
        $temas[] = $s['motivo_nombre'] ?? 'Sin tema';

        if ($avg !== false) {
            $totalEval += $avg;
            $conEval++;
        }
    }

    // 3. Lógica de Alerta (Riesgo crítico en alguna de las últimas 3 sesiones)
    $mostrarAlerta = false;
    foreach (array_slice($todasSesiones, 0, 3) as $s) {
        if (str_contains(strtolower($s['nivel_riesgo_detectado'] ?? ''), 'crit')) {
            $mostrarAlerta = true;
            break;
        }
    }

    // 4. Datos para la Vista
    $estadoActual = $todasSesiones[0]['nivel_riesgo_detectado'] ?? 'Estable';
    
    extract([
        'totalTutorias' => count($todasSesiones),
        'avgEval'       => $conEval > 0 ? ($totalEval / $conEval) : 0,
        'estadoActual'  => $estadoActual,
        'estadoBadge'   => $this->obtenerClaseBadge($estadoActual),
        'labelsJson'    => json_encode($labels),
        'satisfaccionJson' => json_encode($dataSatisfaccion),
        'riesgoJson'    => json_encode($dataRiesgo),
        'temasJson'     => json_encode($temas),
        'mostrarAlerta' => $mostrarAlerta
    ]);

    require __DIR__ . '/../../views/estudiante/progreso.php';
}

private function obtenerClaseBadge(string $texto): string {
    $t = strtolower($texto);
    if (str_contains($t, 'crit')) return 'bg-danger';
    if (str_contains($t, 'observ') || str_contains($t, 'med')) return 'bg-warning text-dark';
    return 'bg-success';
}

 // AGREGA ESTE MÉTODO O REVISA SI ESTÁ BIEN ESCRITO
    public function miSesion() {
         RoleMiddleware::requireRole(['estudiante', 'administrador']);
    
            $user = Auth::user();
            $tutoriaModel = new Tutoria();
             $idEstudiante = $user['id']; 

              $estudiante = $tutoriaModel->obtenerEstudiantePorId($idEstudiante);


            $sesiones = $tutoriaModel->listarSesionesPorEstudiante($idEstudiante);

               $actividades = $tutoriaModel->listarActividadesProgramadas($idEstudiante);


        require __DIR__ . '/../../views/estudiante/mi_sesion.php';
    }

    public function guardarEvidencia() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id_sesion = (int)$_POST['id_sesion'];
        $user = Auth::user();
        $id_usuario = (int)$user['id']; 

        if (isset($_FILES['archivo_estudiante']) && $_FILES['archivo_estudiante']['error'] == 0) {
            
            // 1. Definir rutas
            $folder_path = 'uploads/evidencias_estudiantes/';
            $upload_dir = __DIR__ . '/../../public/' . $folder_path;
            
            if (!is_dir($upload_dir)) { 
                mkdir($upload_dir, 0777, true); 
            }

            $nombre_archivo = "est_" . $id_usuario . "_ses_" . $id_sesion . "_" . time() . ".pdf";
            $ruta_servidor = $upload_dir . $nombre_archivo; // Para mover el archivo
            $ruta_db = $folder_path . $nombre_archivo;     // PARA GUARDAR EN BD (RUTA RELATIVA)

            if (move_uploaded_file($_FILES['archivo_estudiante']['tmp_name'], $ruta_servidor)) {
                $tutoriaModel = new Tutoria();
                
                // IMPORTANTE: Necesitas el id_estudiante, no el id_usuario.
                // Si no tienes un método para esto, asumo que el modelo debe buscarlo o ya lo tienes.
                // Aquí usamos una consulta rápida o ajusta tu modelo para que reciba id_usuario
                $tutoriaModel->guardarEvidenciaEstudiante($id_sesion, $id_usuario, $ruta_db);
                
                $_SESSION['flash_msg'] = "Evidencia enviada con éxito.";
            } else {
                $_SESSION['error_msg'] = "Error al guardar el archivo.";
            }
        }
        header("Location: index.php?route=estudiante/miSesion"); 
        exit();
    }
}
}
