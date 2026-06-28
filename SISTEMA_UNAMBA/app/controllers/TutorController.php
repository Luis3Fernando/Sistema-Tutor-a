<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/RoleMiddleware.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../models/Tutoria.php';


class TutorController
{
    /**
     * AJAX Endpoints para Registro Sesión
     */
    public function buscarEstudiantesAjax(): void
    {
        header('Content-Type: application/json');
        RoleMiddleware::requireRole(['tutor', 'administrador']);
        
        $user = Auth::user();
        $tutorId = (int)$user['id'];
        $q = trim($_GET['q'] ?? '');
        
        if (strlen($q) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Query demasiado corta']);
            return;
        }
        
        $tutoriaModel = new Tutoria();
        $estudiantes = $tutoriaModel->listarMisEstudiantes($tutorId, []);
        
        $matches = array_filter($estudiantes, fn($e) => 
            stripos($e['nombres'].' '.$e['apellidos'], $q) !== false ||
            stripos($e['codigo_unamba'] ?? '', $q) !== false ||
            stripos($e['dni'] ?? '', $q) !== false
        );
        
        echo json_encode(array_values(array_slice($matches, 0, 10)));
    }
    
   public function listarMotivosAjax(): void
{
    // Limpiar cualquier salida previa para evitar JSON inválido
    if (ob_get_length()) ob_clean(); 
    
    header('Content-Type: application/json');
    RoleMiddleware::requireRole(['tutor', 'administrador']);
    
    try {
        $db = db();
        // Corregido: Seleccionamos nombre_motivo y eliminamos "activo" que no existe en tu tabla
        $stmt = $db->query("
            SELECT id_motivo, nombre_motivo, description 
            FROM cat_motivos 
            ORDER BY nombre_motivo ASC
        ");
        $motivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($motivos);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error en la base de datos',
            'detalle' => $e->getMessage() // Útil para debugear, quitar en producción
        ]);
    }
    exit;
}

    public function dashboard(): void
    {
        RoleMiddleware::requireRole(['tutor', 'administrador']);

        $user = Auth::user();
        $tutorId = (int)$user['id'];
      

        $tutoriaModel = new Tutoria();
       // $resumen = $tutoriaModel->resumenDashboardTutor($tutorId);
        
         $dashboard = $tutoriaModel->obtenerResumenDashboard($tutorId);
          $tutor=$tutoriaModel->obtenerPeriodoTutor($tutorId);

         extract($dashboard);
       //  $diagnostico = $tutoriaModel->obtenerDiagnosticoGrupal($tutorId);
      //    $sesionesPendientes = $tutoriaModel->sesionesPendientesTutor($tutorId);
     
     $diagnostico = $tutoriaModel->obtenerConteoNiveles($tutorId);

    // Formateamos los datos para Chart.js (Senior Tip: Estructura limpia)
    $dataGrafico = [
        'alto'     => [
            $diagnostico['ps_alto'] ?? 0, 
            $diagnostico['sm_alto'] ?? 0, 
           $diagnostico['ac_alto'] ?? 0, 
            $diagnostico['vo_alto'] ?? 0
        ],
        'medio'    => [
            $diagnostico['ps_medio'] ?? 0, 
            $diagnostico['sm_medio'] ?? 0, 
           $diagnostico['ac_medio'] ?? 0, 
           $diagnostico['vo_medio'] ?? 0
        ],
        'adecuado' => [
            $diagnostico['ps_adecuado'] ?? 0, 
           $diagnostico['sm_adecuado'] ?? 0, 
           $diagnostico['ac_adecuado'] ?? 0, 
           $diagnostico['vo_adecuado'] ?? 0
        ]
    ];
    
        require __DIR__ . '/../../views/tutor/dashboard.php';
        exit(); 
    }
  
   
    
    

    public function misEstudiantes(): void
    {
        RoleMiddleware::requireRole(['tutor', 'administrador']);

        $user = Auth::user();
        $tutorId = (int)$user['id'];

        $filters = [
            'codigo' => trim((string)($_GET['codigo'] ?? '')),
            'ciclo' => trim((string)($_GET['ciclo'] ?? '')),
            'situacion' => trim((string)($_GET['situacion'] ?? '')),
        ];

        $tutoriaModel = new Tutoria();
        $estudiantes = $tutoriaModel->listarMisEstudiantes($tutorId, $filters);

        require __DIR__ . '/../../views/tutor/mis_estudiantes.php';
        exit(); 
    }

    public function historialEstudiante(): void
    {
        RoleMiddleware::requireRole(['tutor', 'administrador']);

        $user = Auth::user();
        $tutorId = (int)$user['id'];
        $estudianteId = (int)($_GET['estudiante_id'] ?? 0);

        if ($estudianteId <= 0) {
            header('Location: index.php?route=tutor/mis-estudiantes');
            exit;
        }

        $tutoriaModel = new Tutoria();
        $historial = $tutoriaModel->historialPorEstudianteTutor($tutorId, $estudianteId);

        require __DIR__ . '/../../views/tutor/historial_estudiante.php';
         exit(); 
    }

   private function obtenerPeriodoActual(int $id_tutor): string
{
    $db = db();

    $sql = "SELECT periodo_academico
            FROM asignaciones
            WHERE id_tutor = ?
            ORDER BY fecha_asignacion DESC
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_tutor]);

    $periodo = $stmt->fetchColumn();

    return $periodo ?: '';
}
   
    public function aceptarSolicitud(): void
     {
        // 1. Seguridad
        RoleMiddleware::requireRole(['tutor', 'administrador']);

        $user = Auth::user();
        $tutorId = (int)$user['id'];
        
        // Obtenemos el ID de la solicitud
        $id_solicitud = (int)($_GET['id_solicitud'] ?? ($_POST['id_solicitud'] ?? 0));

        if ($id_solicitud <= 0) {
            $_SESSION['flash_error'] = 'ID de solicitud inválido.';
            header('Location: index.php?route=tutor/dashboard');
            exit;
        }

        $solicitudesModel = new SolicitudesTutoria();

        // --- LÓGICA PARA PROCESAR EL ENVÍO (POST) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha_ejecucion = trim($_POST['fecha_ejecucion'] ?? '');
            $hora_ejecucion = trim($_POST['hora_ejecucion'] ?? '');
            $tipo_tutoria = trim($_POST['tipo_tutoria'] ?? '');

        if (empty($fecha_ejecucion) || empty($tipo_tutoria)) {
            $_SESSION['flash_error'] = 'La fecha y el tipo de tutoría son obligatorios.';
            header("Location: index.php?route=tutor/aceptar-solicitud&id_solicitud=$id_solicitud");
            exit;
        }

        $solicitud = $solicitudesModel->validarAsignacionTutor($id_solicitud, $tutorId);

        if (!$solicitud) {
            $_SESSION['flash_error'] = 'Error de validación: La solicitud no existe o no le pertenece.';
            header('Location: index.php?route=tutor/dashboard');
            exit;
        }

        $id_asignacion = (int)$solicitud['id_asignacion'];

        // Actualizar solicitud
        $actualizado = $solicitudesModel->actualizarEstado($id_solicitud, 'Aceptado', $fecha_ejecucion, $hora_ejecucion);

        if ($actualizado) {
            $tutoriaModel = new Tutoria();

            

            // 1. Array de datos principales
            $dataPrincipal = [
                'uuid_sesion'            => bin2hex(random_bytes(16)),
                'id_asignacion'          => $id_asignacion,
                'id_tutor'               => $tutorId,
                'id_motivo'              => 1, // ID por defecto
                'tipo_tutoria'           => $tipo_tutoria,
                'modalidad'              => 'Individual', 
                'medio_ejecucion'        => 'Presencial',
                'fecha_ejecucion'        => $fecha_ejecucion,
                'hora_ejecucion'         => $hora_ejecucion ?: null,
                'observaciones'          => 'Programada desde solicitud',
                'recomendaciones'        => '',
                'nivel_riesgo_detectado' => 'Bajo',
                'archivo_evidencia'      => null,
                'duracion'               => null,
                'proxima_cita'           => null,
                'estado_sesion'          => 'Programada'
            ];

            // 2. Array de intervención (necesario para evitar errores de índice en el modelo)
            $intervencion = [
                'claridad_orientacion'         => 0,
                'pertinencia_orientacion'      => 0,
                'satisfaccion_estudiante'      => 0,
                'observaciones_academicas'     => '',
                'descripcion_estado_emocional' => '',
                'requiere_derivacion'          => false,
                'percepcion_estudiante_satisfaccion'      => 0,
                'indicador_alerta'             => 'Ninguno',
                'estado_estudiante'            => 'Estable',
                'observacion_evolutiva'        => ''
            ];

            // 3. Array de compromisos (vacío)
            $compromisos = [];

            // LLAMADA FINAL AL MODELO: Pasamos los 3 arrays definidos arriba
            if ($tutoriaModel->registrarSesion($dataPrincipal, $intervencion, $compromisos)) {
                $_SESSION['flash_msg'] = '¡Éxito! La sesión quedó programada.';
            } else {
                $_SESSION['flash_error'] = 'Solicitud aceptada, pero hubo un error al crear la sesión.';
            }
        }
        
        header('Location: index.php?route=tutor/dashboard');
        exit;
    

        // --- LÓGICA PARA MOSTRAR LA VISTA (GET) ---
        $solicitud = $solicitudesModel->obtenerDetalleParaAceptar($id_solicitud, $tutorId);

        if (!$solicitud) {
            $_SESSION['flash_error'] = 'Solicitud no encontrada.';
            header('Location: index.php?route=tutor/dashboard');
            exit;
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
       }
        require_once __DIR__ . '/../../views/tutor/aceptar_solicitud.php';

     
     }

    /**
     * Función auxiliar para gestionar la subida de archivos
     */
    private function handleFileUpload() {
        // Verificar si se subió un archivo y si no tiene errores
        if (isset($_FILES['archivo_evidencia']) && $_FILES['archivo_evidencia']['error'] === UPLOAD_ERR_OK) {
            
            $fileTmpPath = $_FILES['archivo_evidencia']['tmp_name'];
            $fileName = $_FILES['archivo_evidencia']['name'];
            $fileSize = $_FILES['archivo_evidencia']['size'];
            $fileType = $_FILES['archivo_evidencia']['type'];
            
            // Limpiar el nombre del archivo para evitar problemas de seguridad
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Puedes restringir extensiones aquí
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'pdf', 'doc', 'docx');
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Crear un nombre único para el archivo para que no se sobrescriba
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                
                // Define la ruta donde se guardará (asegúrate de que la carpeta exista y tenga permisos)
                $uploadFileDir = 'uploads/evidencias/';
                
                // Crear el directorio si no existe
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }

                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    return $dest_path; // Retorna la ruta para guardarla en la BD
                }
            }
        }
        
        return null; // Si no hay archivo o hubo error, devuelve null
    }
public function planTrabajo()
{
    RoleMiddleware::requireRole(['tutor', 'administrador']);

    $user = Auth::user();
    $id_tutor = (int)$user['id'];

    $periodo_actual = $this->obtenerPeriodoActual($id_tutor);

if (!$periodo_actual) {
    die("El tutor no tiene asignaciones registradas.");
}
   

    $db = db(); 

    // 1. Intentamos obtener el plan si ya existe
    $sqlPlan = "SELECT 
                    p.*, 
                    u.nombres, 
                    u.apellidos, 
                    e.nombre_escuela,
                    (SELECT COUNT(*) FROM asignaciones WHERE id_tutor = p.id_tutor AND periodo_academico = p.periodo_academico) as nro_real
                FROM plan_trabajo_tutorial p
                INNER JOIN tutor_detalles td ON p.id_tutor = td.id_tutor
                INNER JOIN usuarios u ON td.id_tutor = u.id_usuario
                INNER JOIN escuelas e ON td.id_escuela = e.id_escuela
                WHERE p.id_tutor = ? AND p.periodo_academico = ? LIMIT 1";

    $stmt = $db->prepare($sqlPlan);
    $stmt->execute([$id_tutor, $periodo_actual]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Si NO existe el plan, buscamos los datos del tutor para llenar la vista automáticamente
    if (!$plan) {
        $sqlTutor = "SELECT u.nombres, u.apellidos, e.nombre_escuela 
                     FROM usuarios u
                     INNER JOIN tutor_detalles td ON u.id_usuario = td.id_tutor
                     INNER JOIN escuelas e ON td.id_escuela = e.id_escuela
                     WHERE u.id_usuario = ?";
        
        $stmtTutor = $db->prepare($sqlTutor);
        $stmtTutor->execute([$id_tutor]);
        $datosTutor = $stmtTutor->fetch(PDO::FETCH_ASSOC);

        // Contamos cuántos alumnos tiene asignados este semestre
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM asignaciones WHERE id_tutor = ? AND periodo_academico = ?");
        $stmtCount->execute([$id_tutor, $periodo_actual]);
        $conteo_estudiantes = (int)$stmtCount->fetchColumn();

        // Llenamos el array $plan con los datos de la DB, NO de la sesión
        $plan = [
            'id_plan' => 0, 
            'nombres' => $datosTutor['nombres'] ?? 'Nombre no encontrado',
            'apellidos' => $datosTutor['apellidos'] ?? '',
            'nombre_escuela' => $datosTutor['nombre_escuela'] ?? 'Escuela no asignada',
            'periodo_academico' => $periodo_actual,
            'nro_estudiantes_asignados' => $conteo_estudiantes, 
            'objetivo_general' => ''
        ];
        $actividades = [];
    } else {
        // Si el plan existe, usamos el conteo real de la subconsulta
        $plan['nro_estudiantes_asignados'] = $plan['nro_real'];

        $stmtAct = $db->prepare("
            SELECT * FROM plan_actividades_cronograma 
            WHERE id_plan = ?
            ORDER BY fecha ASC, hora ASC
        ");
        $stmtAct->execute([$plan['id_plan']]);
        $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
    }

    require __DIR__ . '/../../views/tutor/plan_trabajo.php';
}

public function inicializarPlan()
{
    RoleMiddleware::requireRole(['tutor']);
    $user = Auth::user();
    $id_tutor = (int)$user['id'];
    
    $objetivo = $_POST['objetivo_general'] ?? '';
    $periodo = $this->obtenerPeriodoActual($id_tutor);

if (!$periodo) {
    die("No existe periodo académico asignado.");
}

    $db = db();
    $sql = "INSERT INTO plan_trabajo_tutorial (id_tutor, periodo_academico, objetivo_general) 
            VALUES (?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_tutor, $periodo, $objetivo]);

    header("Location: index.php?route=tutor/plan-trabajo");
    exit;
}

public function guardarActividad()
{
    RoleMiddleware::requireRole(['tutor']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php");
        exit;
    }

    $db = db();

    $id_plan = isset($_POST['id_plan']) ? (int)$_POST['id_plan'] : 0;
    $fecha = $_POST['fecha'] ?? null;
    $hora = $_POST['hora'] ?? null;
    $actividad_tipo = $_POST['actividad_tipo'] ?? null;
    $instrumento = $_POST['instrumento'] ?? null;
    $objetivo_especifico = $_POST['objetivo_especifico'] ?? null;

    if ($id_plan <= 0) {
        die("No existe Plan de Trabajo.");
    }

    try {

        $sql = "INSERT INTO plan_actividades_cronograma
                (id_plan, fecha, hora, actividad_tipo, instrumento, objetivo_especifico)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $id_plan,
            $fecha,
            $hora,
            $actividad_tipo,
            htmlspecialchars($instrumento),
            htmlspecialchars($objetivo_especifico)
        ]);
       $_SESSION['flash_msg'] = " Actividad programada correctamente.";
        

    } catch (PDOException $e) {
          $_SESSION['flash_error'] = " Error al guardar la programación.";
      //  echo "<pre>";
       // print_r($e->getMessage());
       // exit;
    }
    header("Location: index.php?route=tutor/plan-trabajo&msj=guardado");
        exit;
}


public function verExpediente()
{
    // 1. Validar Rol
    RoleMiddleware::requireRole(['tutor', 'admin']);

    // 2. Capturar ID de la URL (?route=tutor/ver-expediente&id=7)
    $idEstudiante = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($idEstudiante <= 0) {
         header('Location: index.php?route=tutor/mis-estudiantes');
    exit;
    }

    $tutoriaModel = new Tutoria();

    
    // 3. Obtener datos básicos del estudiante
    $estudiante = $tutoriaModel->obtenerDetalleEstudiante($idEstudiante);
    $especialistas = $tutoriaModel->obtenerEspecialistasActivos();
    

    // Si el estudiante no existe, aquí es donde salta tu error
    if (!$estudiante) {
        die("El registro del estudiante no existe o no tienes permisos.");
    }
   
    
    // 4. Obtener el diagnóstico que el alumno llenó (F-TUT-02)
    // Sin esto, tu vista mostrará siempre "Sin respuesta"
    $diagnostico = $tutoriaModel->obtenerDiagnosticoActual($idEstudiante);

    
   
    // 5. Obtener historial de seguimientos y derivaciones
    
    $derivaciones = $tutoriaModel->listarDerivacionesPorEstudiante($idEstudiante);
        $seguimiento = $tutoriaModel->obtenerSeguimientoPorEstudiante($idEstudiante);


    // 6. Cargar la vista con TODAS las variables necesarias
    render('tutor/ver_expediente', [
        'estudiante'   => $estudiante,
        'diagnostico'  => $diagnostico,
        'seguimiento' => $seguimiento,
        'derivaciones' => $derivaciones,
        'especialistas'=> $especialistas 
    ]);
   
}
    
    private function obtenerSesionActual($id_estudiante) {
        // Lógica para encontrar la sesión que el tutor está ejecutando hoy
        // Retornar ID de la tabla sesiones_tutoria
        return 1; // Placeholder
    }

    private function obtenerEstudiantesIds($ids_asig) 
{
        $db = db();
        $placeholders = implode(',', array_fill(0, count($ids_asig), '?'));
        $stmt = $db->prepare("SELECT id_estudiante FROM asignaciones WHERE id_asignacion IN ($placeholders)");
        $stmt->execute($ids_asig);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
}



public function registroSesion(): void {

    RoleMiddleware::requireRole(['tutor', 'administrador']);

    $id_sesion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $id_actividad_seleccionada =
        filter_input(INPUT_GET, 'id_actividad', FILTER_VALIDATE_INT) ?: 0;

    $user = Auth::user();
    $tutorId = (int)$user['id'];

    $tutoriaModel = new Tutoria();

    $tutor = $tutoriaModel->obtenerDatosTutorCompleto($tutorId);

    $sesion = ($id_sesion > 0)
        ? $tutoriaModel->obtenerSesionParaEditar($id_sesion)
        : [];

    if ($id_sesion > 0 && !$sesion) {
        die("La sesión con ID $id_sesion no existe.");
    }

     $periodo = $this->obtenerPeriodoActual($tutorId);

    $actividades = $tutoriaModel->obtenerActividadesProgramadas($tutorId, $periodo);
    $lista_estudiantes = $tutoriaModel->obtenerEstudiantesConAsistencia(
        $tutorId,
        $id_sesion,
        $periodo
    );

    /* ===============================
       ✅ INICIALIZAR VARIABLES
    =============================== */

    $actividadSeleccionada = [];
    $objetivoSesion = '';
    $fechaSesion = '';
    $horaInicioSesion = '';

    /* ===============================
       ✅ VIENE DEL BOTÓN EJECUTAR
    =============================== */

    if ($id_actividad_seleccionada > 0) {
    $actividadSeleccionada = $tutoriaModel->obtenerActividadPorId($id_actividad_seleccionada);
    if ($actividadSeleccionada) {
        // Concatenamos aquí para que la vista sea limpia
        $objetivoSesion =  $actividadSeleccionada['objetivo_especifico'];
        $fechaSesion    = $actividadSeleccionada['fecha'];
        $horaInicioSesion = $actividadSeleccionada['hora'];
    }
}

    /* ===============================
       ✅ SI ESTAMOS EDITANDO ACTA
    =============================== */

    if (!empty($sesion)) {
        $objetivoSesion   = $sesion['objetivo_sesion'];
        $fechaSesion      = $sesion['fecha_ejecucion'];
        $horaInicioSesion = $sesion['hora_inicio'];
    }

    require __DIR__ . '/../../views/tutor/registrar_sesion.php';
}


public function guardarAsistencia(): void {
    RoleMiddleware::requireRole(['tutor']);
    
    $tutorId = (int)Auth::user()['id'];
    $id_sesion = (int)($_POST['id_sesion'] ?? 0);
    $id_actividad = (int)($_POST['id_actividad'] ?? 0); // Capturar ID actividad

    if ($id_actividad === 0) {
        die("Error: ID de actividad no proporcionado.");
    }

    // Instanciamos el modelo al principio
    $model = new Tutoria();

    // ✅ SOLUCIÓN AL ERROR: Llamamos al modelo para actualizar el estado
    $model->marcarActividadComoRealizada($id_actividad);

    // Lógica de archivo
    $nombre_archivo = $this->handleFileUpload(); 

    $datosSesion = [
        'id_sesion'         => $id_sesion,
        'id_actividad'      => $id_actividad,
        'objetivo_sesion'   => $_POST['objetivo_sesion'],
        'fecha_ejecucion'   => $_POST['fecha'],
        'hora_inicio'       => $_POST['hora'],
        'hora_fin'          => $_POST['hora_fin'],
        'archivo_evidencia' => $nombre_archivo
    ];

    $asistencias = $_POST['asistencia'] ?? [];

    if (empty($asistencias)) {
        $_SESSION['flash_error'] = "Debe seleccionar al menos un estudiante.";
        header("Location: " . $_SERVER['HTTP_REFERER']); // Regresar a la página anterior
        exit;
    }

    // Guardar sesión y asistencias
    if ($model->guardarOActualizarSesionGrupal($datosSesion, $asistencias, $tutorId)) {
        $_SESSION['flash_msg'] = "Datos guardados y actividad marcada como realizada.";
        header("Location: index.php?route=tutor/dashboard");
    } else {
        die("Error al procesar la solicitud en el servidor.");
    }
}

public function guardarSeguimiento(): void
{
    RoleMiddleware::requireRole(['tutor']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?route=tutor/mis-estudiantes");
        exit;
    }

    $user = Auth::user();
    $id_estudiante = (int)($_POST['id_estudiante'] ?? 0);

    $datos = [
        'id_tutor'                    => (int)$user['id'],
        'id_estudiante'               => $id_estudiante,
        'fecha_seguimiento'           => $_POST['fecha_seguimiento'] ?? date('Y-m-d'),
        'seguimiento_personal_social' => $_POST['seguimiento_personal_social'] ?? '',
        'seguimiento_salud_mental'    => $_POST['seguimiento_salud_mental'] ?? '',
        'seguimiento_academico'       => $_POST['seguimiento_academico'] ?? '',
        'seguimiento_vocacional'      => $_POST['seguimiento_vocacional'] ?? '',
        'acciones_acuerdos'           => $_POST['acciones_acuerdos'] ?? '',
        'recomendaciones'             => $_POST['recomendaciones'] ?? '',
        'observaciones_generales'     => $_POST['observaciones_generales'] ?? '',
        'proxima_cita'                => $_POST['proxima_cita'] ?? null,
         'nivel_personal_social'           => $_POST['nivel_personal_social'] ?? '',
        'nivel_salud_mental'             => $_POST['nivel_salud_mental'] ?? '',
        'nivel_academico'     => $_POST['nivel_academico'] ?? '',
        'nivel_vocacional'                => $_POST['nivel_vocacional'] ?? null

    ];

    $tutoriaModel = new Tutoria();
    
    // Ahora esta línea ya no dará error porque el método existe en el modelo

if ($tutoriaModel->guardarOActualizarSeguimiento($datos)) {
    $_SESSION['flash_success'] = "¡Seguimiento guardado/actualizado correctamente!";
} else {
    $_SESSION['flash_error'] = "Error al procesar el seguimiento.";
}

    header("Location: index.php?route=tutor/ver-expediente&id=" . $id_estudiante);
    exit;
}

///
public function guardarDerivacion(): void
{
    RoleMiddleware::requireRole(['tutor']);
    $tutoriaModel = new Tutoria();

    $id_estudiante = (int)($_POST['id_estudiante'] ?? 0);
    $id_especialista = (int)($_POST['id_especialista'] ?? 0);

    // Si por alguna razón el ID del estudiante no llega, detenemos y avisamos
    if ($id_estudiante <= 0) {
        $_SESSION['flash_error'] = "Error: No se detectó el ID del estudiante.";
        header("Location: index.php?route=tutor/mis-estudiantes");
        exit;
    }
   
    // Buscamos el nombre del área
    $area_nombre = '';
    $especialistas = $tutoriaModel->obtenerEspecialistasActivos();
    foreach ($especialistas as $esp) {
        if ($esp['id_especialista'] == $id_especialista) {
            $area_nombre = $esp['area'];
            break;
        }
    }
 
  

    $datos = [
        'id_tutor'         => (int)Auth::user()['id'],
        'id_estudiante'    => $id_estudiante,
        'id_especialista'  => $id_especialista,
        'area_destino'     => $area_nombre,
        'motivo_informe'   => $_POST['motivo_informe'] ?? '',
        'resumen_caso'     => $_POST['resumen_caso'] ?? ''
    ];

    if ($tutoriaModel->registrarDerivacionDirecta($datos)) {
        $_SESSION['flash_success'] = "¡La derivación se ha registrado correctamente!";
    } else {
        // Si falla, mostramos un error más descriptivo
        $_SESSION['flash_error'] = "Error al registrar: Verifique que el estudiante y el especialista sean válidos.";
    }

    header("Location: index.php?route=tutor/ver-expediente&id=" . $id_estudiante);
    exit;
}

// Cambia el nombre de editarDerivacion a actualizarDerivacion para que coincida con el JS
public function editarDerivacion(): void
{
    RoleMiddleware::requireRole(['tutor']);
    $tutoriaModel = new Tutoria();

    
    // Recuperamos los datos del formulario POST
    $id_derivacion = isset($_POST['id_derivacion']) ? (int)$_POST['id_derivacion'] : 0;
    $id_estudiante = isset($_POST['id_estudiante']) ? (int)$_POST['id_estudiante'] : 0;

    // Actualizamos
    $ok = $tutoriaModel->actualizarDerivacion($id_derivacion, $_POST);

    if ($ok) {
        $_SESSION['flash_success'] = 'Registro actualizado correctamente.';
    } else {
        $_SESSION['flash_error'] = 'Error al intentar actualizar los datos.';
    }

    // Redirección obligatoria al expediente del estudiante
    header("Location: index.php?route=tutor/ver-expediente&id=" . $id_estudiante);
    exit;
}

public function eliminarDerivacion(): void
{
    RoleMiddleware::requireRole(['tutor']);
    $tutoriaModel = new Tutoria();

    // El JS envía estos datos por la URL (GET)
    $id_derivacion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $id_estudiante = isset($_GET['id_estudiante']) ? (int)$_GET['id_estudiante'] : 0;

    if ($id_derivacion > 0) {
        $ok = $tutoriaModel->eliminarDerivacion($id_derivacion);
        if ($ok) {
            $_SESSION['flash_success'] = 'La derivación ha sido eliminada.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo eliminar el registro.';
        }
    }

    header("Location: index.php?route=tutor/ver-expediente&id=" . $id_estudiante);
    exit;
}

public function verDetalleSesion() {
    RoleMiddleware::requireRole(['tutor']);
    
    $id_actividad = isset($_GET['id_actividad']) ? (int)$_GET['id_actividad'] : null;

    if (!$id_actividad) {
        header("Location: index.php?route=tutor/plan-trabajo");
        exit;
    }

    $sesionModel = new Tutoria();

    // 1. PRIMERO: Buscamos la sesión que tiene vinculada esa id_actividad
    // Necesitas este método en tu modelo o haz la consulta aquí
    $db = db();
    $stmt = $db->prepare("SELECT id_sesion FROM sesiones_tutoria WHERE id_actividad = ? LIMIT 1");
    $stmt->execute([$id_actividad]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sesion) {
        // Si no hay sesión, significa que la asistencia no se registró correctamente por el error del id_actividad NULL
        $asistentes = [];
    } else {
        // 2. AHORA SÍ: Usamos el id_sesion REAL para traer a los asistentes
        $asistentes = $sesionModel->obtenerAsistentesPorSesion((int)$sesion['id_sesion']);
    }

    require_once __DIR__ . '/../../views/tutor/sesion_detalles.php';
}

public function reporteGeneral()
{
    RoleMiddleware::requireRole(['tutor']);
    $user = Auth::user();
    $idTutor = (int)$user['id'];
    $periodo = $this->obtenerPeriodoActual($idTutor);

    $tutoriaModel = new Tutoria();
    $dataReporte = $tutoriaModel->obtenerDataReporteGeneral($idTutor, $periodo);

    require __DIR__ . '/../../views/tutor/reporte_general.php';
}

public function exportarExcel()
{
    $user = Auth::user();
    $idTutor = (int)$user['id'];
    $periodo = $this->obtenerPeriodoActual($idTutor);
    $data = (new Tutoria())->obtenerDataReporteGeneral($idTutor, $periodo);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Estilo de Cabecera (Azul UNAMBA)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '002147']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ];

    $sheet->setCellValue('A1', 'CÓDIGO')->setCellValue('B1', 'ESTUDIANTE')->setCellValue('C1', 'CICLO')
          ->setCellValue('D1', 'SITUACIÓN')->setCellValue('E1', 'ASISTENCIAS')->setCellValue('F1', 'NIVEL RIESGO')->setCellValue('G1', 'DERIVACIONES');
    
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
    
   

    $rowNum = 2;
    foreach ($data as $r) {

        // Lógica de Riesgo
        $maxRiesgo = min($r['nivel_personal_social'] ?? 3, $r['nivel_salud_mental'] ?? 3, $r['nivel_academico'] ?? 3, $r['nivel_vocacional'] ?? 3);
        $riesgoText = ($maxRiesgo == 1) ? 'ALTO' : (($maxRiesgo == 2) ? 'MEDIO' : 'ESTABLE');

        $sheet->setCellValue('A' . $rowNum, $r['codigo_unamba']);
        $sheet->setCellValue('B' . $rowNum, $r['estudiante_nombre']);
        $sheet->setCellValue('C' . $rowNum, $r['ciclo_actual']);
        $sheet->setCellValue('D' . $rowNum, $r['situacion_academica']);
        $sheet->setCellValue('E' . $rowNum, $r['total_asistencias']);
        $sheet->setCellValue('F' . $rowNum, $riesgoText);
        $sheet->setCellValue('G' . $rowNum, $r['total_derivaciones']);
        $rowNum++;
    }

    foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Tutoria_'.$periodo.'.xlsx"');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

public function exportarPdf()
{
    $user = Auth::user();
    $idTutor = (int)$user['id'];
    $periodo = $this->obtenerPeriodoActual($idTutor);
    $data = (new Tutoria())->obtenerDataReporteGeneral($idTutor, $periodo);

    // ✅ SOLUCIÓN SENIOR: Consultamos los datos reales del tutor a la BD
     $tutoriaModel = new Tutoria();
    $datosTutor = $tutoriaModel->obtenerDatosTutorCompleto($idTutor);
    
    // Armamos el nombre de forma segura (Defensive Programming)
    $nombreTutor = 'Tutor No Asignado';
    if ($datosTutor) {
        $grado = !empty($datosTutor['grado_academico']) ? $datosTutor['grado_academico'] . ' ' : '';
        $nombreTutor = trim($grado . ($datosTutor['nombres'] ?? '') . ' ' . ($datosTutor['apellidos'] ?? ''));
    }


    $dompdf = new \Dompdf\Dompdf();
    ob_start();
    ?>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { background: #002147; color: white; text-align: center; padding: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #002147; color: white; padding: 8px; border: 1px solid #002147; }
        td { border: 1px solid #ddd; padding: 6px; text-align: center; }
    </style>
    <div class="header">
        <h2>SISTEMA DE TUTORÍA - UNAMBA</h2>
        <!-- Corregida la doble 'D' de PERIODO -->
        <p><strong>REPORTE CONSOLIDADO PERIODO:</strong> <?php echo htmlspecialchars($periodo); ?></p>
        <p><strong>TUTOR:</strong> <?php echo htmlspecialchars(mb_strtoupper($nombreTutor, 'UTF-8')); ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>CÓDIGO</th>
                <th>ESTUDIANTE</th>
                <th>CICLO</th>
                <th>SITUACIÓN</th>
                <th>ASIST.</th>
                <th>RIESGO</th>
                <th>DERIV.</th>
            </tr>
        </thead>
        <tbody>
           
                <?php foreach($data as $r): 
                    $maxRiesgo = min($r['nivel_personal_social'] ?? 3, $r['nivel_salud_mental'] ?? 3, $r['nivel_academico'] ?? 3, $r['nivel_vocacional'] ?? 3);
                    $claseRiesgo = ($maxRiesgo == 1) ? 'riesgo-alto' : (($maxRiesgo == 2) ? 'riesgo-medio' : 'riesgo-estable');
                    $textoRiesgo = ($maxRiesgo == 1) ? 'ALTO' : (($maxRiesgo == 2) ? 'MEDIO' : 'ESTABLE');
                ?>
            <tr>
                <td><?php echo $r['codigo_unamba']; ?></td>
                <td style="text-align: left;"><?php echo $r['estudiante_nombre']; ?></td>
                <td><?php echo $r['ciclo_actual']; ?></td>
                <td><?php echo $r['situacion_academica']; ?></td>
                <td><?php echo $r['total_asistencias']; ?></td>
                 <td><?php echo $textoRiesgo; ?></td>
                    <td> <?php echo $r['total_derivaciones']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $dompdf->loadHtml(ob_get_clean());
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Reporte_Tutoria.pdf", ["Attachment" => true]);
    exit;
}

}



