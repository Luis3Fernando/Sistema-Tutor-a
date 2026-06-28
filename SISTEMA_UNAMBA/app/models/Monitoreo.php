<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';


class Monitoreo {
     private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * 1. TUTORES ONLINE
     * Detecta tutores cuya actividad en el sistema es reciente.
     * Consideramos "Online" a quien haya cargado una página en los últimos 5 minutos.
     */
    public function getTutoresOnline() {
        $sql = "SELECT 
                    u.id_usuario, 
                    u.nombres, 
                    u.apellidos, 
                    u.rol,
                    e.nombre_escuela, 
                    u.ultimo_acceso,
                    u.celular
                FROM usuarios u
                JOIN tutor_detalles td ON u.id_usuario = td.id_usuario
                JOIN escuelas e ON td.id_escuela = e.id_escuela
                WHERE u.rol = 'tutor' 
                AND u.ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND u.estado = 1
                ORDER BY u.ultimo_acceso DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 2. SESIONES ACTIVAS "JUSTO AHORA"
     * Cruza la programación horaria con la hora del servidor.
     * Útil para que el Admin pueda realizar "visitas inopinadas" virtuales o presenciales.
     */
  /*  public function getSesionesActivasAhora() {
        $sql = "SELECT 
                    s.id_sesion,
                    u.nombres AS tutor_nombre,
                    u.apellidos AS tutor_apellido,
                    e.nombre_escuela,
                    s.objetivo_sesion,
                    s.hora_inicio,
                    s.hora_fin,
                    (SELECT COUNT(*) FROM sesion_asistencia sa WHERE sa.id_sesion = s.id_sesion) as alumnos_esperados
                FROM sesiones_tutoria s
                JOIN tutor_detalles td ON s.id_tutor = td.id_tutor
                JOIN usuarios u ON td.id_usuario = u.id_usuario
                JOIN escuelas e ON td.id_escuela = e.id_escuela
                WHERE s.fecha_ejecucion = CURDATE()
                AND CURTIME() BETWEEN s.hora_inicio AND s.hora_fin";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
*/
public function getSesionesHoy() {
    // Usamos DISTINCT para asegurar filas únicas y un LEFT JOIN preciso
    $sql = "SELECT DISTINCT
                p.id_actividad,
                u.nombres AS tutor_nombre,
                u.apellidos AS tutor_apellido,
                e.nombre_escuela,
                p.actividad_tipo,
                p.hora AS hora_plan,
                p.objetivo_especifico,
                s.id_sesion,
                s.hora_inicio,
                s.hora_fin,
                -- Lógica de Estado Senior
                CASE 
                    WHEN s.id_sesion IS NOT NULL AND (s.archivo_evidencia IS NOT NULL OR s.hora_fin IS NOT NULL) THEN 'REALIZADA'
                    WHEN s.id_sesion IS NOT NULL AND s.hora_fin IS NULL THEN 'EN EJECUCION'
                    WHEN CURTIME() > ADDTIME(p.hora, '00:15:00') AND s.id_sesion IS NULL THEN 'RETRASADA'
                    WHEN CURTIME() < p.hora THEN 'PROGRAMADA'
                    ELSE 'PENDIENTE'
                END as estado_monitor
            FROM plan_actividades_cronograma p
            INNER JOIN plan_trabajo_tutorial pt ON p.id_plan = pt.id_plan
            INNER JOIN tutor_detalles td ON pt.id_tutor = td.id_tutor
            INNER JOIN usuarios u ON td.id_usuario = u.id_usuario
            INNER JOIN escuelas e ON td.id_escuela = e.id_escuela
            -- LA CLAVE: Unir solo por id_actividad, NO por id_tutor
            LEFT JOIN sesiones_tutoria s ON s.id_actividad = p.id_actividad
            WHERE p.fecha = CURDATE()
            ORDER BY p.hora ASC, p.id_actividad ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

 // Obtener todas las derivaciones con datos de estudiantes y tutores
    public function getDerivacionesAudit() {
        $sql = "SELECT 
                    d.*,
                    u_est.nombres AS est_nombres,
                    u_est.apellidos AS est_apellidos,
                    ed.codigo_unamba,
                    u_tut.nombres AS tutor_nombre,
                    u_tut.apellidos AS tutor_apellido
                FROM derivaciones d
                JOIN estudiantes_detalle ed ON d.id_estudiante = ed.id_estudiante
                JOIN usuarios u_est ON ed.id_usuario = u_est.id_usuario
                JOIN usuarios u_tut ON d.id_tutor = u_tut.id_usuario
                ORDER BY 
                    CASE WHEN d.estado_atencion = 'Pendiente' THEN 1 ELSE 2 END, 
                    d.fecha_derivacion DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEscuelas() {
        return $this->db->query("SELECT id_escuela, nombre_escuela FROM escuelas ORDER BY nombre_escuela ASC")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 3. MAPA DE CALOR DE AUSENTISMO (POR ESCUELA)
     * Calcula el porcentaje de inasistencia por cada Escuela Profesional.
     * Identifica dónde los estudiantes están faltando más a sus tutorías hoy/última semana.
     */
    public function getMapaCalorAsistencia($periodo) {
        $sql = "SELECT 
                    e.nombre_escuela,
                    COUNT(sa.id_asistencia) as total_registros,
                    SUM(CASE WHEN sa.asistencia = 0 THEN 1 ELSE 0 END) as total_faltas,
                    ROUND((SUM(CASE WHEN sa.asistencia = 0 THEN 1 ELSE 0 END) / COUNT(sa.id_asistencia)) * 100, 2) as porcentaje_ausentismo
                FROM sesion_asistencia sa
                JOIN sesiones_tutoria st ON sa.id_sesion = st.id_sesion
                JOIN estudiantes_detalle ed ON sa.id_estudiante = ed.id_estudiante
                JOIN escuelas e ON ed.id_escuela = e.id_escuela
                -- Filtramos por el periodo actual si es necesario
                GROUP BY e.id_escuela
                ORDER BY porcentaje_ausentismo DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 4. ESTADO DE DERIVACIONES URGENTES
     * Extra: Alerta al administrador sobre casos críticos que no han sido atendidos.
     */
    public function getAlertasCriticas() {
        $sql = "SELECT 
                    d.id_derivacion,
                    u_est.nombres AS estudiante,
                    d.area_destino,
                    d.fecha_derivacion,
                    DATEDIFF(NOW(), d.fecha_derivacion) as dias_espera
                FROM derivaciones d
                JOIN estudiantes_detalle ed ON d.id_estudiante = ed.id_estudiante
                JOIN usuarios u_est ON ed.id_usuario = u_est.id_usuario
                WHERE d.estado_atencion = 'Pendiente'
                AND DATEDIFF(NOW(), d.fecha_derivacion) > 3
                ORDER BY dias_espera DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Derivaciones pendientes (Alertas)
    public function getPendingDerivations() {
        $sql = "SELECT d.*, u.nombres, u.apellidos, ed.codigo_unamba 
                FROM derivaciones d
                JOIN estudiantes_detalle ed ON d.id_estudiante = ed.id_estudiante
                JOIN usuarios u ON ed.id_usuario = u.id_usuario
                WHERE d.estado_atencion = 'Pendiente' 
                ORDER BY d.fecha_derivacion DESC LIMIT 5";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actividad reciente (Mezcla de eventos)
/*    public function getRecentActivity() {
       
        $sql = "(SELECT 'sesion' as tipo, s.created_at as fecha, u.nombres, u.apellidos, 'finalizó una sesión' as accion 
                 FROM sesiones_tutoria s JOIN usuarios u ON s.id_tutor = u.id_usuario)
                UNION
                (SELECT 'derivacion' as tipo, d.fecha_derivacion as fecha, u.nombres, u.apellidos, 'creó una derivación' as accion 
                 FROM derivaciones d JOIN usuarios u ON d.id_tutor = u.id_usuario)
                ORDER BY fecha DESC LIMIT 10";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
*/
// derivaciones recientes
    public function getRecentActivity()
{
    $sql = "

    (
        SELECT 
            'derivacion' AS tipo,
            d.fecha_derivacion AS fecha,

            CONCAT(t.nombres, ' ', t.apellidos) AS responsable,
            CONCAT(est.nombres, ' ', est.apellidos) AS estudiante,

            CONCAT(
                'creó una derivación al área de ',
                d.area_destino
            ) AS accion

        FROM derivaciones d

        INNER JOIN usuarios t
            ON d.id_tutor = t.id_usuario

        INNER JOIN usuarios est
            ON d.id_estudiante = est.id_usuario
    )

    UNION

    (
        SELECT 
            'especialista' AS tipo,
            d.updated_at AS fecha,

            CONCAT(esp.nombres, ' ', esp.apellidos) AS responsable,
            CONCAT(est.nombres, ' ', est.apellidos) AS estudiante,

            CONCAT(
                'registró atención: ',
                LEFT(d.acciones_realizadas, 80)
            ) AS accion

        FROM derivaciones d

        INNER JOIN usuarios esp
            ON d.id_especialista = esp.id_usuario

        INNER JOIN usuarios est
            ON d.id_estudiante = est.id_usuario

        WHERE d.acciones_realizadas IS NOT NULL
        AND d.acciones_realizadas != ''
        AND d.updated_at IS NOT NULL
    )

    ORDER BY fecha DESC
    LIMIT 5
    ";

    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


 /**
     * Lista sesiones con detalles de tutor, escuela y estadísticas de asistencia
     * optimizado para el módulo de auditoría.
     */
    public function listarSesionesDetalladas($id_escuela = null, $estado_evidencia = null, $fecha = null) {
        try {
            $where = [];
            $params = [];

            // Construcción dinámica de la consulta según filtros
            if (!empty($id_escuela)) {
                $where[] = "esc.id_escuela = :id_escuela";
                $params[':id_escuela'] = $id_escuela;
            }

            if ($estado_evidencia === 'con') {
                $where[] = "s.archivo_evidencia IS NOT NULL AND s.archivo_evidencia != ''";
            } elseif ($estado_evidencia === 'sin') {
                $where[] = "(s.archivo_evidencia IS NULL OR s.archivo_evidencia = '')";
            }

            if (!empty($fecha)) {
                $where[] = "s.fecha_ejecucion = :fecha";
                $params[':fecha'] = $fecha;
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "SELECT 
                        s.id_sesion,
                        u.nombres AS tutor_nombre, 
                        u.apellidos AS tutor_apellido,
                        esc.nombre_escuela,
                        s.fecha_ejecucion,
                        s.hora_inicio, 
                        s.hora_fin,
                        s.objetivo_sesion,
                        s.archivo_evidencia,
                        -- Subconsultas optimizadas para conteo de asistencia
                        (SELECT COUNT(*) 
                         FROM sesion_asistencia sa 
                         WHERE sa.id_sesion = s.id_sesion AND sa.asistencia = 1) as total_asistentes,
                        (SELECT COUNT(*) 
                         FROM sesion_asistencia sa 
                         WHERE sa.id_sesion = s.id_sesion) as total_inscritos
                    FROM sesiones_tutoria s
                    INNER JOIN tutor_detalles td ON s.id_tutor = td.id_tutor
                    INNER JOIN usuarios u ON td.id_usuario = u.id_usuario
                    INNER JOIN escuelas esc ON td.id_escuela = esc.id_escuela
                    $whereSql
                    ORDER BY s.fecha_ejecucion DESC, s.hora_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Log de error profesional
            error_log("Error en Estadistica::listarSesionesDetalladas -> " . $e->getMessage());
            return [];
        }
    }


    // reportes

     public function getResumenAlertas(): array {
        return [
            'rojas' => (int) $this->db->query("
                SELECT COUNT(*) FROM estudiantes_detalle 
                WHERE situacion_academica IN ('Repitente', 'Riesgo')
            ")->fetchColumn(),

            'amarillas' => (int) $this->db->query("
                SELECT COUNT(*) FROM derivaciones 
                WHERE estado_atencion = 'Pendiente'
            ")->fetchColumn(),

            'verdes' => (int) $this->db->query("
                SELECT COUNT(*) FROM derivaciones 
                WHERE estado_atencion = 'En Proceso'
            ")->fetchColumn()
        ];
    }

    public function getListaRiesgoSAT(): array {
        $sql = "SELECT 
                    u.nombres, 
                    u.apellidos, 
                    ed.codigo_unamba, 
                    ed.situacion_academica,
                    t_u.nombres as tutor_nom, 
                    t_u.apellidos as tutor_ape,
                    esc.nombre_escuela,
                    -- Calculamos inasistencias totales en el ciclo actual
                    (SELECT COUNT(*) FROM sesion_asistencia sa 
                     JOIN sesiones_tutoria st ON sa.id_sesion = st.id_sesion
                     WHERE sa.id_estudiante = ed.id_estudiante AND sa.asistencia = 0) as total_faltas
                FROM estudiantes_detalle ed
                JOIN usuarios u ON ed.id_usuario = u.id_usuario
                JOIN escuelas esc ON ed.id_escuela = esc.id_escuela
                LEFT JOIN asignaciones a ON ed.id_estudiante = a.id_estudiante
                LEFT JOIN tutor_detalles td ON a.id_tutor = td.id_tutor
                LEFT JOIN usuarios t_u ON td.id_usuario = t_u.id_usuario
                WHERE ed.situacion_academica != 'Regular'
                ORDER BY total_faltas DESC, ed.situacion_academica DESC
                LIMIT 20";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticasCasos(): array {
        $sql = "SELECT area_destino, COUNT(*) as total 
                FROM derivaciones 
                GROUP BY area_destino";
        $res = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $data = [];
        foreach ($res as $row) {
            $labels[] = ucfirst($row['area_destino']);
            $data[] = (int)$row['total'];
        }

        // Si no hay datos, enviamos un ejemplo para que el gráfico no se rompa
        if (empty($data)) {
            return ['labels' => ['Sin Datos'], 'data' => [1]];
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function getDesempeñoTutores(): array {
        $sql = "SELECT 
                    u.nombres, 
                    u.apellidos,
                    COUNT(p.id_actividad) as programadas,
                    SUM(CASE WHEN p.estado = 'Realizado' THEN 1 ELSE 0 END) as realizadas
                FROM usuarios u
                JOIN tutor_detalles td ON u.id_usuario = td.id_usuario
                JOIN plan_trabajo_tutorial ptt ON td.id_tutor = ptt.id_tutor
                JOIN plan_actividades_cronograma p ON ptt.id_plan = p.id_plan
                WHERE u.rol = 'tutor'
                GROUP BY u.id_usuario";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInasistenciasCriticas(): array {
        $sql = "SELECT 
                    u.nombres, 
                    u.apellidos, 
                    COUNT(sa.id_asistencia) as faltas
                FROM sesion_asistencia sa
                JOIN usuarios u ON sa.id_estudiante = u.id_usuario
                WHERE sa.asistencia = 0
                GROUP BY sa.id_estudiante
                HAVING faltas >= 2
                ORDER BY faltas DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

         // 1. KPIs Estratégicos
    public function getSummaryKPIs() {
        return [
            'alto_riesgo' => $this->db->query("
    SELECT 
        SUM(
            (CASE WHEN nivel_academico = 1 THEN 1 ELSE 0 END) +
            (CASE WHEN nivel_salud_mental = 1 THEN 1 ELSE 0 END) +
            (CASE WHEN nivel_personal_social = 1 THEN 1 ELSE 0 END) +
            (CASE WHEN nivel_vocacional = 1 THEN 1 ELSE 0 END)
        ) AS total
    FROM seguimiento_individual
")->fetchColumn(),
            'sesiones_totales' => $this->db->query("SELECT COUNT(*) FROM sesiones_tutoria")->fetchColumn(),
            'derivaciones_pendientes' => $this->db->query("SELECT COUNT(*) FROM derivaciones WHERE estado_atencion = 'Pendiente'")->fetchColumn(),
            'tutores_activos' => $this->db->query("SELECT COUNT(*) FROM tutor_detalles")->fetchColumn(),
            'cobertura_porcentaje' => $this->calculateCobertura()
        ];
    }

    private function calculateCobertura() {
        $total = $this->db->query("SELECT COUNT(*) FROM estudiantes_detalle")->fetchColumn();
        $diag = $this->db->query("SELECT COUNT(DISTINCT id_estudiante) FROM diagnostico_inicial")->fetchColumn();
        return ($total > 0) ? round(($diag / $total) * 100, 1) : 0;
    }

    // 2. Impacto de Mejora (Promedios de niveles) - VITAL PARA LA TESIS
    public function getImpactoGlobal() {
        $sql = "SELECT 
                AVG(nivel_academico) as academico, 
                AVG(nivel_salud_mental) as salud, 
                AVG(nivel_personal_social) as personal, 
                AVG(nivel_vocacional) as vocacional 
                FROM seguimiento_individual";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getRiesgosPorEscuela() {
    $sql = "SELECT 
                e.nombre_escuela,
                -- Sumamos las incidencias de nivel 1 (Riesgo Alto) en las 4 áreas
                (SUM(CASE WHEN si.nivel_academico = 1 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_salud_mental = 1 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_personal_social = 1 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_vocacional = 1 THEN 1 ELSE 0 END)) as riesgo_alto,

                -- Sumamos las incidencias de nivel 2 (Riesgo Medio) en las 4 áreas
                (SUM(CASE WHEN si.nivel_academico = 2 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_salud_mental = 2 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_personal_social = 2 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_vocacional = 2 THEN 1 ELSE 0 END)) as riesgo_medio,

                -- Sumamos las incidencias de nivel 3 (Adecuado) en las 4 áreas
                (SUM(CASE WHEN si.nivel_academico = 3 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_salud_mental = 3 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_personal_social = 3 THEN 1 ELSE 0 END) +
                 SUM(CASE WHEN si.nivel_vocacional = 3 THEN 1 ELSE 0 END)) as adecuado

            FROM seguimiento_individual si
            JOIN estudiantes_detalle ed ON si.id_estudiante = ed.id_estudiante
            JOIN escuelas e ON ed.id_escuela = e.id_escuela
            GROUP BY e.id_escuela";

    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}  

    public function getEstadisticasDerivaciones() {
        $sql = "SELECT area_destino as label, COUNT(*) as value 
                FROM derivaciones GROUP BY area_destino";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetalleSeguimiento($filtros = []) {
        $where = "";
        if (!empty($filtros['id_escuela'])) {
            $where = " WHERE ed.id_escuela = " . intval($filtros['id_escuela']);
        }
        $sql = "SELECT u.id_usuario, u.nombres, u.apellidos, e.nombre_escuela, ed.codigo_unamba,
                si.nivel_academico, si.nivel_salud_mental , si.nivel_personal_social, si.nivel_vocacional
                FROM seguimiento_individual si
                JOIN estudiantes_detalle ed ON si.id_estudiante = ed.id_estudiante
                JOIN usuarios u ON ed.id_usuario = u.id_usuario
                JOIN escuelas e ON ed.id_escuela = e.id_escuela $where";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
public function obtenerMatrizSPSS() {

    $sql = "SELECT 
                u.id_usuario as ID_CASO,
                ed.codigo_unamba as CODIGO,
                u.nombres as NOMBRE,
                u.apellidos as APELLIDO,
                CASE 
                    WHEN u.sexo = 'Masculino' THEN 'M'
                    WHEN u.sexo = 'Femenino' THEN 'F' 
                    ELSE 'otros' 
                END as SEXO,
                e.nombre_escuela as ESCUELA,
                ed.ciclo_actual as CICLO,

                CASE 
                    WHEN ed.situacion_academica = 'Regular' THEN 'REGULAR'
                    WHEN ed.situacion_academica = 'Repitente' THEN 'REPITENTE'
                    WHEN ed.situacion_academica = 'Riesgo' THEN 'RIESGO'
                    ELSE 0
                END as SITUACION_MATRICULA,

                si.nivel_academico as NIVEL_ACA,
                si.nivel_salud_mental as NIVEL_SALUD,
                si.nivel_personal_social as NIVEL_SOCIAL,
                si.nivel_vocacional as NIVEL_VOC,

                (SELECT COUNT(*) 
                 FROM sesion_asistencia sa 
                 WHERE sa.id_estudiante = ed.id_estudiante 
                 AND sa.asistencia = 1) as TOTAL_ASISTENCIAS,

                (SELECT COUNT(*) 
                 FROM derivaciones d 
                 WHERE d.id_estudiante = ed.id_estudiante) as TOTAL_DERIVACIONES,

                (SELECT COUNT(*) 
                 FROM citas_especialista ce 
                 JOIN derivaciones d 
                 ON ce.id_derivacion = d.id_derivacion 
                 WHERE d.id_estudiante = ed.id_estudiante) as TOTAL_CITAS_PSIC

            FROM estudiantes_detalle ed

            JOIN usuarios u 
            ON ed.id_usuario = u.id_usuario 
            JOIN escuelas e
            ON e.id_escuela = ed.id_escuela

            LEFT JOIN seguimiento_individual si 
            ON si.id_seguimiento = (
                SELECT MAX(id_seguimiento) 
                FROM seguimiento_individual 
                WHERE id_estudiante = ed.id_estudiante
            )";

    $stmt = $this->db->query($sql);

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
    
    

    // dashboardh 
    public function getKPIs() {
        return [
            'total_estudiantes' => $this->db->query("SELECT COUNT(*) FROM estudiantes_detalle")->fetchColumn(),
            'sesiones_ejecutadas' => $this->db->query("SELECT COUNT(*) FROM sesiones_tutoria")->fetchColumn(),
            'derivaciones_pendientes' => $this->db->query("SELECT COUNT(*) FROM derivaciones WHERE estado_atencion = 'Pendiente'")->fetchColumn(),
           'total_tutores' => $this->db->query("SELECT COUNT(*) FROM tutor_detalles")->fetchColumn(),
        ];
    }

    public function getRankingTutores() {
        $sql = "SELECT u.nombres, u.apellidos, e.nombre_escuela, 
                (SELECT COUNT(*) FROM asignaciones a WHERE a.id_tutor = t.id_tutor) as asignados,
                (SELECT COUNT(*) FROM sesiones_tutoria s WHERE s.id_tutor = t.id_tutor) as sesiones
                FROM tutor_detalles t
                JOIN usuarios u ON t.id_tutor = u.id_usuario
                JOIN escuelas e ON t.id_escuela = e.id_escuela";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPeriodosAcademicos(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT periodo_academico
             FROM asignaciones
             WHERE periodo_academico IS NOT NULL AND periodo_academico <> ''
             ORDER BY periodo_academico DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
     public function getCumplimientoGeneralPorEscuela() {
        // 1. Obtener todas las escuelas para iterar
        $sqlEscuelas = "SELECT id_escuela, nombre_escuela FROM escuelas";
        $escuelas = $this->db->query($sqlEscuelas)->fetchAll(PDO::FETCH_ASSOC);

        $reporteFinal = [];

        foreach ($escuelas as $escuela) {
            $id_escuela = $escuela['id_escuela'];

            // --- INDICADOR 1: EJECUCIÓN DE ACTIVIDADES (40%) ---
            $sqlAct = "SELECT 
                        COUNT(pac.id_actividad) as total,
                        SUM(CASE WHEN pac.estado = 'Realizado' THEN 1 ELSE 0 END) as realizadas
                       FROM tutor_detalles td
                       JOIN plan_trabajo_tutorial ptt ON td.id_tutor = ptt.id_tutor
                       JOIN plan_actividades_cronograma pac ON ptt.id_plan = pac.id_plan
                       WHERE td.id_escuela = ?";
            $stmtAct = $this->db->prepare($sqlAct);
            $stmtAct->execute([$id_escuela]);
            $resAct = $stmtAct->fetch(PDO::FETCH_ASSOC);
            
            $percAct = ($resAct['total'] > 0) ? ($resAct['realizadas'] / $resAct['total']) : 0;
            $puntosAct = $percAct * 40; // Vale el 40%


            // --- INDICADOR 2: COBERTURA DE ALUMNOS (30%) ---
            // Alumnos asignados vs Alumnos que tienen al menos 1 asistencia marcada
            $sqlCob = "SELECT 
                        (SELECT COUNT(DISTINCT id_estudiante) FROM asignaciones a 
                         JOIN tutor_detalles td ON a.id_tutor = td.id_tutor 
                         WHERE td.id_escuela = ?) as asignados,
                        (SELECT COUNT(DISTINCT sa.id_estudiante) FROM sesion_asistencia sa
                         JOIN estudiantes_detalle ed ON sa.id_estudiante = ed.id_estudiante
                         WHERE ed.id_escuela = ? AND sa.asistencia = 1) as atendidos";
            $stmtCob = $this->db->prepare($sqlCob);
            $stmtCob->execute([$id_escuela, $id_escuela]);
            $resCob = $stmtCob->fetch(PDO::FETCH_ASSOC);

            $percCob = ($resCob['asignados'] > 0) ? ($resCob['atendidos'] / $resCob['asignados']) : 0;
            $puntosCob = $percCob * 30; // Vale el 30%


            // --- INDICADOR 3: GESTIÓN DE CASOS CRÍTICOS (30%) ---
            // Total derivaciones de la escuela vs Cerradas
            $sqlDer = "SELECT 
                        COUNT(d.id_derivacion) as total,
                        SUM(CASE WHEN d.estado_atencion = 'Cerrado' THEN 1 ELSE 0 END) as cerrados
                       FROM derivaciones d
                       JOIN estudiantes_detalle ed ON d.id_estudiante = ed.id_estudiante
                       WHERE ed.id_escuela = ?";
            $stmtDer = $this->db->prepare($sqlDer);
            $stmtDer->execute([$id_escuela]);
            $resDer = $stmtDer->fetch(PDO::FETCH_ASSOC);

            $percDer = ($resDer['total'] > 0) ? ($resDer['cerrados'] / $resDer['total']) : 0;
            $puntosDer = $percDer * 30; // Vale el 30%

            // --- CÁLCULO FINAL ---
            $puntajeTotal = $puntosAct + $puntosCob + $puntosDer;

            $reporteFinal[] = [
                'escuela' => $escuela['nombre_escuela'],
                'detalle' => [
                    'ejecucion_actividades' => round($percAct * 100, 1) . '%',
                    'cobertura_estudiantes' => round($percCob * 100, 1) . '%',
                    'casos_cerrados' => round($percDer * 100, 1) . '%'
                ],
                'puntos_obtenidos' => [
                    'actividades' => round($puntosAct, 2),
                    'cobertura' => round($puntosCob, 2),
                    'derivaciones' => round($puntosDer, 2)
                ],
                'cumplimiento_total' => round($puntajeTotal, 2) . '%'
            ];
        }

        return $reporteFinal;
    }

  public function getDistribucionDiagnosticos() {
    $sql = "SELECT 
        SUM(CASE WHEN nivel_personal_social IN (1,2) THEN 1 ELSE 0 END) as personal_social,
        SUM(CASE WHEN nivel_salud_mental IN (1,2) THEN 1 ELSE 0 END) as salud_mental,
        SUM(CASE WHEN nivel_vocacional IN (1,2) THEN 1 ELSE 0 END) as vocacional,
        SUM(CASE WHEN nivel_academico IN (1,2) THEN 1 ELSE 0 END) as academico
    FROM seguimiento_individual";

    $res = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

    // Devolvemos ENTEROS en el orden que los espera la gráfica
    return [
        (int)$res['personal_social'],
        (int)$res['salud_mental'],
        (int)$res['vocacional'],
        (int)$res['academico']
    ];
}
}