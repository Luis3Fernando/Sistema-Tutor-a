<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/RoleMiddleware.php';
//require_once __DIR__ . '/../models/Estadistica.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/AdminAsignacion.php';
require_once __DIR__ . '/../models/Monitoreo.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;


class ReporteController
{
   public function dashboard(): void
{
    RoleMiddleware::requireRole(['administrador']);
    $MonitoreoModel = new Monitoreo();

     $kpis = $MonitoreoModel->getKPIs();
     $tutores = $MonitoreoModel->getRankingTutores();
    $cumplimiento = $MonitoreoModel->getCumplimientoGeneralPorEscuela();

    // Obtenemos el array de conteos [PS, SE, VC, RG]
    $diagnosticosData = $MonitoreoModel->getDistribucionDiagnosticos();
     $valores_diagnostico = json_encode($diagnosticosData);
     
      
    $nombresEscuelas = [];
    $dataActividades = []; // Para el 40%
    $dataCobertura = [];   // Para el 30%
    $dataCasos = [];       // Para el 30%

    if (!empty($cumplimiento)) {
        foreach ($cumplimiento as $item) {
            $nombresEscuelas[] = $item['escuela'];
            
            // Extraemos los porcentajes individuales que calculó el modelo
            $dataActividades[] = floatval(str_replace('%', '', $item['detalle']['ejecucion_actividades']));
            $dataCobertura[]   = floatval(str_replace('%', '', $item['detalle']['cobertura_estudiantes']));
            $dataCasos[]       = floatval(str_replace('%', '', $item['detalle']['casos_cerrados']));
        }
    }

    // Enviamos los 3 arreglos a la vista
    $labels_grafico = json_encode($nombresEscuelas);
    $valores_actividades = json_encode($dataActividades);
    $valores_cobertura = json_encode($dataCobertura);
    $valores_casos = json_encode($dataCasos);

    require __DIR__ . '/../../views/admin/dashboard.php';
}

    public function usuarios(): void
    {
        RoleMiddleware::requireRole(['administrador']);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $usuarioModel = new Usuario();
        $errores = [];
        $ok = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = (string)($_POST['accion'] ?? '');

            if ($accion === 'crear_usuario') {
                $rolNuevo = (string)($_POST['rol'] ?? '');

                $result = $usuarioModel->crearUsuarioAdmin([
                    'rol' => $rolNuevo,
                    'dni' => (string)($_POST['dni'] ?? ''),
                    'nombres' => (string)($_POST['nombres'] ?? ''),
                    'apellidos' => (string)($_POST['apellidos'] ?? ''),
                    'sexo' => (string)($_POST['sexo'] ?? ''),
                    'correo' => (string)($_POST['correo'] ?? ''),
                    'celular' => (string)($_POST['celular'] ?? ''),
                    'password' => (string)($_POST['password'] ?? ''),
                ]);

                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo crear el usuario.');
                } else {
                    $idNuevoUsuario = (int)($result['id_usuario'] ?? 0);

                    if ($idNuevoUsuario <= 0) {
                        $errores[] = 'No se pudo obtener el ID del nuevo usuario.';
                    } else {
                        if ($rolNuevo === 'estudiante') {
                            $idEscuela = (int)($_POST['id_escuela'] ?? 0);
                            $codigoUnamba = trim((string)($_POST['codigo_unamba'] ?? ''));
                            $cicloActual = (int)($_POST['ciclo_actual'] ?? 0);
                            $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));
                            $semestreIngresado = trim((string)($_POST['semestre_ingreso'] ?? ''));
                            $situacionAcademica = trim((string)($_POST['situacion_academica'] ?? 'ACTIVO'));

                            if ($idEscuela <= 0 || $codigoUnamba === '' || $cicloActual <= 0) {
                                $errores[] = 'Completa los datos académicos del estudiante (escuela, código y ciclo).';
                            } else {
                                $okDetalle = $usuarioModel->guardarDetalleEstudiante(
                                    $idNuevoUsuario,
                                    $idEscuela,
                                    $codigoUnamba,
                                    $cicloActual,
                                    $fechaNacimiento,
                                    $semestreIngresado,
                                    $situacionAcademica
                                );

                                if (!$okDetalle) {
                                    $errores[] = 'Se creó el usuario, pero no se pudo guardar el detalle del estudiante.';
                                }
                            }
                        }

                        if ($rolNuevo === 'tutor') {
                            $idEscuelaTut = (int)($_POST['id_escuela'] ?? 0);
                            $grado = trim((string)($_POST['grado_academico'] ?? ''));
                            $especialidad = trim((string)($_POST['especialidad'] ?? ''));
                            $categoria = trim((string)($_POST['categoria'] ?? ''));

                            if ($idEscuelaTut > 0 && $grado !== '') {
                                $usuarioModel->guardarDetalleTutor(
                                    $idNuevoUsuario,
                                    $idEscuelaTut,
                                    $grado,
                                    $especialidad,
                                    $categoria
                                );
                            }
                        }

                        if ($rolNuevo === 'especialista') {
                            $idEscuelaEsp = (int)($_POST['id_escuela'] ?? 0);
                            $area = trim((string)($_POST['area'] ?? ''));
                            $cargo = trim((string)($_POST['cargo'] ?? ''));

                            if ($idEscuelaEsp > 0 && $area !== '') {
                                $okDetalleEsp = $usuarioModel->guardarDetalleEspecialista(
                                    $idNuevoUsuario,
                                    $idEscuelaEsp,
                                    $area,
                                    $cargo
                                );

                                if (!$okDetalleEsp) {
                                    $errores[] = 'Se creó el usuario, pero no se pudo guardar el detalle del especialista.';
                                }
                            } else {
                                $errores[] = 'Completa los datos del especialista (escuela y área).';
                            }
                        }
                        if ($rolNuevo === 'administrador') {

                        $cargoAdmin = trim((string)($_POST['cargo'] ?? ''));
                        $dependenciaAdmin = trim((string)($_POST['dependencia'] ?? ''));

                        if ($cargoAdmin === '' || $dependenciaAdmin === '') {

                            $errores[] = 'Completa los datos del administrador (cargo y dependencia).';

                        } else {

                            $okDetalleAdmin = $usuarioModel->guardarDetallesAdmin(
                                $idNuevoUsuario,
                                $cargoAdmin,
                                $dependenciaAdmin
                            );

                            if (!$okDetalleAdmin) {
                                $errores[] = 'Se creó el usuario, pero no se pudo guardar el detalle del administrador.';
                            }
                        }
                    }

                    }

                    if (empty($errores)) {
                        $ok = (string)($result['msg'] ?? 'Usuario creado correctamente.');
                    }
                }
            } elseif ($accion === 'importar_usuarios') {
                $file = $_FILES['archivo_usuarios'] ?? [];
                
                $result = $usuarioModel->importarTodoDesdeExcel($file);

                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo importar el archivo.');
                } else {
                    $stats = $result['stats'] ?? ['nuevos' => 0, 'errores' => 0];
                    $ok = (string)($result['msg'] ?? 'Importación completada.')
                        . ' Nuevos: ' . (int)($stats['nuevos'] ?? 0)
                        . ' | Errores: ' . (int)($stats['errores'] ?? 0);
                }
            } else {
                $idUsuario = (int)($_POST['id_usuario'] ?? 0);

                if ($idUsuario <= 0) {
                    $errores[] = 'Usuario no válido.';
                } else {
                    if ($accion === 'actualizar') {
                        $dni = trim((string)($_POST['dni'] ?? ''));
                        $nombres = trim((string)($_POST['nombres'] ?? ''));
                        $apellidos = trim((string)($_POST['apellidos'] ?? ''));
                        $correo = trim((string)($_POST['correo'] ?? ''));
                        $celular = trim((string)($_POST['celular'] ?? ''));

                        if ($dni === '' || strlen($dni) < 8) {
                            $errores[] = 'El DNI debe tener al menos 8 caracteres.';
                        }
                        if ($nombres === '' || $apellidos === '') {
                            $errores[] = 'Nombres y apellidos son obligatorios.';
                        }
                        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                            $errores[] = 'El correo no es válido.';
                        }

                        if (empty($errores)) {
                            $okDb = $usuarioModel->actualizarUsuarioAdmin($idUsuario, [
                                'dni' => $dni,
                                'nombres' => $nombres,
                                'apellidos' => $apellidos,
                                'correo' => $correo,
                                'celular' => $celular,
                            ]);
                            if ($okDb) {
                                $ok = 'Usuario actualizado correctamente.';
                            } else {
                                $errores[] = 'No se pudo actualizar el usuario.';
                            }
                        }
                    } elseif ($accion === 'eliminar') {
                        $okDb = $usuarioModel->desactivarUsuario($idUsuario);
                        if ($okDb) {
                            $ok = 'Usuario desactivado correctamente.';
                        } else {
                            $errores[] = 'No se pudo desactivar el usuario.';
                        }
                    } elseif ($accion === 'activar') { // <--- AGREGAR ESTO
    $okDb = $usuarioModel->activarUsuario($idUsuario);
    if ($okDb) {
        $ok = 'Usuario activado correctamente.';
    } else {
        $errores[] = 'No se pudo activar el usuario.';
    }
}
                }
            }
        }

        $q = trim((string)($_GET['q'] ?? ''));
        $estadoRaw = trim((string)($_GET['estado'] ?? ''));
        $estado = ($estadoRaw === '0' || $estadoRaw === '1') ? (int)$estadoRaw : null;

        $pageEst = max(1, (int)($_GET['page_est'] ?? 1));
        $pageTut = max(1, (int)($_GET['page_tut'] ?? 1));
        $pageEsp = max(1, (int)($_GET['page_esp'] ?? 1));
        $pageAdm = max(1, (int)($_GET['page_admi'] ?? 1));

        $perPageEst = 25;
        $perPageTut = 10;
        $perPageEsp = 10;
        $perPageAdm = 10;

        $totalEstudiantes = $usuarioModel->contarPorRol('estudiante', $q, $estado);
        $totalTutores = $usuarioModel->contarPorRol('tutor', $q, $estado);
        $totalEspecialistas = $usuarioModel->contarPorRol('especialista', $q, $estado);
        $totalAdmin = $usuarioModel->contarPorRol('administrador', $q, $estado);

        $totalPagesEst = max(1, (int)ceil($totalEstudiantes / $perPageEst));
        $totalPagesTut = max(1, (int)ceil($totalTutores / $perPageTut));
         $totalPagesEsp = max(1, (int)ceil($totalEspecialistas / $perPageEsp));
         $totalPagesAdm = max(1, (int)ceil($totalAdmin / $perPageAdm));

        $pageEst = min($pageEst, $totalPagesEst);
        $pageTut = min($pageTut, $totalPagesTut);
        $pageEsp = min($pageEsp, $totalPagesEsp);
         $pageAdm = min($pageAdm, $totalPagesAdm);

        $offsetEst = ($pageEst - 1) * $perPageEst;
        $offsetTut = ($pageTut - 1) * $perPageTut;
        $offsetEsp = ($pageEsp - 1) * $perPageEsp;
        $offsetAdm = ($pageAdm - 1) * $perPageAdm;

        $estudiantes = $usuarioModel->listarPorRolPaginado('estudiante', $perPageEst, $offsetEst, $q, $estado);
        $tutores = $usuarioModel->listarPorRolPaginado('tutor', $perPageTut, $offsetTut, $q, $estado);
        $especialistas = $usuarioModel->listarPorRolPaginado('especialista', $perPageEsp, $offsetEsp, $q, $estado);
        $administrador = $usuarioModel->listarPorRolPaginado('administrador',$perPageAdm,$offsetAdm,$q,$estado);

        $adminAsignacionModel = new AdminAsignacion();
        $escuelas = $adminAsignacionModel->listarEscuelas();

        require __DIR__ . '/../../views/admin/usuarios.php';
    }

    public function escuelasAsignaciones(): void
    {
        RoleMiddleware::requireRole(['administrador']);

        $adminAsignacionModel = new AdminAsignacion();
        $errores = [];
        $ok = '';

        $importStats = [
            'nuevos' => 0,
            'actualizados' => 0,
            'errores' => 0,
        ];

        $filtroEscuela = (int)($_REQUEST['filtro_escuela'] ?? 0);
        $filtroPeriodo = trim((string)($_REQUEST['filtro_periodo'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = (string)($_POST['accion'] ?? '');

            if ($accion === 'crear_escuela') {
                $nombreEscuela = trim((string)($_POST['nombre_escuela'] ?? ''));
                $facultad = trim((string)($_POST['facultad'] ?? ''));

                $result = $adminAsignacionModel->crearEscuela($nombreEscuela, $facultad);
                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo registrar la escuela.');
                } else {
                    $ok = (string)($result['msg'] ?? 'Escuela registrada correctamente.');
                }
            }

            if ($accion === 'editar_escuela') {
                $idEscuela = (int)($_POST['id_escuela'] ?? 0);
                $nombreEscuela = trim((string)($_POST['nombre_escuela'] ?? ''));
                $facultad = trim((string)($_POST['facultad'] ?? ''));

                $result = $adminAsignacionModel->actualizarEscuela($idEscuela, $nombreEscuela, $facultad);
                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo actualizar la escuela.');
                } else {
                    $ok = (string)($result['msg'] ?? 'Escuela actualizada correctamente.');
                }
            }

            if ($accion === 'eliminar_escuela') {
                $idEscuela = (int)($_POST['id_escuela'] ?? 0);

                $result = $adminAsignacionModel->eliminarEscuela($idEscuela);
                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo eliminar la escuela.');
                } else {
                    $ok = (string)($result['msg'] ?? 'Escuela eliminada correctamente.');
                }
            }

            if ($accion === 'asignar_manual') {
                $idTutor = (int)($_POST['id_tutor'] ?? 0);
                $idsEstudiantes = $_POST['estudiantes_ids'] ?? [];
                $periodoAsignacion = trim((string)($_POST['periodo_academico'] ?? $filtroPeriodo));

                $result = $adminAsignacionModel->asignarManual((array)$idsEstudiantes, $idTutor, $periodoAsignacion);
                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo completar la asignación manual.');
                } else {
                    $ok = (string)($result['msg'] ?? 'Asignación manual completada.');
                }
            }

            if ($accion === 'asignar_automatico') {
                $idEscuela = (int)($_POST['filtro_escuela'] ?? $filtroEscuela);
                $periodoAsignacion = trim((string)($_POST['periodo_academico'] ?? $filtroPeriodo));
                $limites = (array)($_POST['limites_tutor'] ?? []);

                $result = $adminAsignacionModel->asignarAutomatico($idEscuela, $periodoAsignacion, $limites);
                if (!($result['ok'] ?? false)) {
                    $errores[] = (string)($result['msg'] ?? 'No se pudo completar la asignación automática.');
                } else {
                    $ok = (string)($result['msg'] ?? 'Asignación automática completada.');
                }
            }
        }

        $escuelas = $adminAsignacionModel->listarEscuelas();
        $periodos = $adminAsignacionModel->listarPeriodosAsignaciones();

        if ($filtroPeriodo === '' && !empty($periodos)) {
            $filtroPeriodo = (string)$periodos[0];
        }

        $tutores = $adminAsignacionModel->listarTutoresPorEscuela($filtroEscuela > 0 ? $filtroEscuela : null);
        $estudiantes = $adminAsignacionModel->listarEstudiantesPorEscuelaYPeriodo(
            $filtroEscuela > 0 ? $filtroEscuela : null,
            $filtroPeriodo !== '' ? $filtroPeriodo : null
        );
        $asignacionesActuales = $adminAsignacionModel->listarAsignacionesActuales(
            $filtroEscuela > 0 ? $filtroEscuela : null,
            $filtroPeriodo !== '' ? $filtroPeriodo : null
        );

        require __DIR__ . '/../../views/admin/escuelas_asignaciones.php';
    }

    public function apiResumen(): void
    {
        RoleMiddleware::requireRole(['administrador']);

        header('Content-Type: application/json; charset=utf-8');

        $periodo = trim((string)($_GET['periodo'] ?? ''));
        $estadisticaModel = new Estadistica();

        echo json_encode([
            'ok' => true,
            'resumen' => $estadisticaModel->resumenGeneral($periodo !== '' ? $periodo : null),
            'riesgo_por_escuela' => $estadisticaModel->estudiantesRiesgoPorEscuela($periodo !== '' ? $periodo : null),
            'sesiones_por_mes' => $estadisticaModel->sesionesPorMes($periodo !== '' ? $periodo : null),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
    public function listarEstudiantesPorEscuelaYPeriodo($idEscuela = null, $periodo = null)
    {
        $sql = "SELECT 
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    ed.codigo_unamba,
                    ed.ciclo_actual,
                    e.nombre_escuela
                FROM usuarios u
                INNER JOIN estudiante_detalles ed 
                    ON u.id_usuario = ed.id_estudiante
                INNER JOIN escuelas e 
                    ON ed.id_escuela = e.id_escuela
                WHERE u.rol = 'estudiante'";

        $params = [];

        // FILTRO POR ESCUELA
        if (!empty($idEscuela)) {
            $sql .= " AND ed.id_escuela = ?";
            $params[] = $idEscuela;
        }

        // EVITAR ESTUDIANTES YA ASIGNADOS EN ESE PERIODO
        if (!empty($periodo)) {
            $sql .= " AND u.id_usuario NOT IN (
                SELECT id_estudiante FROM asignaciones WHERE periodo_academico = ?
            )";
            $params[] = $periodo;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        
    //controllers de monitoreo
      public function monitoreo() {
         RoleMiddleware::requireRole(['administrador']);

        $MonitoreoModel = new Monitoreo();
        
        $periodos = $MonitoreoModel->listarPeriodosAcademicos();

        $onlineUsers =  $MonitoreoModel->getTutoresOnline();
        $activeSessions =  $MonitoreoModel->getSesionesHoy();
        $pendingDerivations =  $MonitoreoModel->getPendingDerivations();
        $recentActivity =  $MonitoreoModel->getRecentActivity();

        // Cargar la vista y pasar los datos
        require_once __DIR__ . '/../../views/admin/monitoreo.php';
    }

    // sesiones 
     public function sesiones(){

       RoleMiddleware::requireRole(['administrador']);

        $MonitoreoModel = new Monitoreo();
        $id_escuela = $_GET['escuela'] ?? null;
        $evidencia = $_GET['evidencia'] ?? null;
        $fecha = $_GET['fecha'] ?? null;

        
        $sesiones =  $MonitoreoModel ->listarSesionesDetalladas($id_escuela, $evidencia, $fecha);

         $derivaciones =  $MonitoreoModel ->getDerivacionesAudit();
        $escuelas =  $MonitoreoModel ->getEscuelas();
       // $escuelas=  $MonitoreoModel -> listarEscuelas();
        // Luego envías $sesiones a la vista
        require_once __DIR__ . '/../../views/admin/sesiones.php';
        

     }

// reportes

      // Método que carga la vista que me mostraste
    public function reportesSAT(): void {

    RoleMiddleware::requireRole(['administrador']);
        $model = new Monitoreo();
        
        // Carga de datos para la vista
        $data = [
            'kpis' => $model->getSummaryKPIs(),
            'graficoBarras' => $model->getRiesgosPorEscuela(),
            'graficoPie' => $model->getEstadisticasDerivaciones(),
            'graficoRadar' => $model->getImpactoGlobal(),
            'tablaDetalle' => $model->getDetalleSeguimiento(['id_escuela' => $_GET['id_escuela'] ?? null]),
            'escuelas' => $model->getEscuelas(),
            'filtro_actual' => $_GET['id_escuela'] ?? null
        ];

        extract($data);
        require_once __DIR__ . '/../../views/admin/reportes_consolidados.php';
    }

    // --- AQUÍ ASIGNAS LOS MÉTODOS DE EXPORTACIÓN ---

    public function exportar() {
        RoleMiddleware::requireRole(['administrador']);
        $model = new Monitoreo();
        
        $tipo = $_GET['tipo'] ?? 'excel';
        $filtros = ['id_escuela' => $_GET['id_escuela'] ?? null];

        // 1. Obtener los KPIs reales para el informe de tesis
        $kpis = $model->getSummaryKPIs();
        $impacto = $model->getImpactoGlobal();
        
        // 2. Obtener la lista detallada
        $datos = $model->getDetalleSeguimiento($filtros);

        if ($tipo === 'excel') {
            $this->exportarMatriz();
        } elseif ($tipo === 'pdf') {
            $this->generarPdf($datos, $kpis, $impacto);
        }
          
    }


private function generarPdf($datos, $kpis, $impacto) {

    
    $dompdf = new \Dompdf\Dompdf();

    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .titulo { text-align: center; font-size: 16px; font-weight: bold; color: #002B5B; }
        .resumen-box { width: 100%; margin-bottom: 20px; border: 1px solid #002B5B; }
        .resumen-header { background: #002B5B; color: white; padding: 5px; font-weight: bold; }
        .resumen-body td { padding: 8px; border: 1px solid #eee; text-align: center; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-datos th { background: #f2f2f2; border: 1px solid #ccc; padding: 5px; }
        .tabla-datos td { border: 1px solid #ccc; padding: 5px; }
    </style>

    <div class="titulo">REPORTE INSTITUCIONAL DE GESTIÓN TUTORIAL UNAMBA</div>
    <p style="text-align:right;">Fecha: '.date('d/m/Y').'</p>

    <div class="resumen-header">1. INDICADORES DE COBERTURA E IMPACTO (DATOS DE TESIS)</div>
    <table class="resumen-body" style="width:100%;">
        <tr>
            <td><strong>Cobertura Real:</strong><br>'.$kpis['cobertura_porcentaje'].'% (Estudiantes Diagnosticados)</td>
           
            <td><strong>Académico:</strong><br>'.round((float)$impacto['academico'], 2).' / 3</td>
<td><strong>Salud Mental:</strong><br>'.round((float)$impacto['salud'], 2).' / 3</td>
<td><strong>Personal:</strong><br>'.round((float)$impacto['personal'], 2).' / 3</td>
<td><strong>Vocacional:</strong><br>'.round((float)$impacto['vocacional'], 2).' / 3</td>
        </tr>
    </table>

    <div class="resumen-header" style="margin-top:10px;">2. LISTADO DETALLADO DE SEGUIMIENTO</div>
    <table class="tabla-datos">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Código</th>
                <th>Escuela</th>
                <th>Nivel Académico</th>
                <th>Salud Mental</th>
                 <th>Salud Personal Social </th>
                  <th>Salud Vocacional</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($datos as $row) {
        $html .= '
        <tr>
            <td>'.$row['apellidos'].', '.$row['nombres'].'</td>
            <td>'.$row['codigo_unamba'].'</td>
            <td>'.ucwords($row['nombre_escuela']).'</td>
            <td align="center">'.$this->obtenerTextoRiesgo($row['nivel_academico']).'</td>
            <td align="center">'.$this->obtenerTextoRiesgo($row['nivel_salud_mental']).'</td>
             <td align="center">'.$this->obtenerTextoRiesgo($row['nivel_personal_social']).'</td>
            <td align="center">'.$this->obtenerTextoRiesgo($row['nivel_vocacional']).'</td>
            
        </tr>';
    }

    $html .= '</tbody></table>';

    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("Resultado_Tesis_UNAMBA.pdf");

     exit; 
}

 public function exportarMatriz(): void {

    RoleMiddleware::requireRole(['administrador']);

    $model = new Monitoreo();

    // Obtener datos desde el modelo
    $datos = $model->obtenerMatrizSPSS();

    if (empty($datos)) {
        echo "No hay datos en el sistema para exportar.";
        return;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeceras
    $columnas = array_keys($datos[0]);
    $sheet->fromArray($columnas, NULL, 'A1');

    // Datos
    $fila = 2;

    foreach ($datos as $row) {

    $sheet->fromArray(
        array_values($row),
        NULL,
        'A' . $fila
    );

    $fila++;
}

    // Descargar Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="MATRIZ_DATOS_SPSS_UNAMBA.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    $writer->save('php://output');

    exit;
}


    private function obtenerTextoRiesgo($nivel) {
        if($nivel == 1) return "ALTO";
        if($nivel == 2) return "MEDIO";
        return "ADECUADO";
    }
}