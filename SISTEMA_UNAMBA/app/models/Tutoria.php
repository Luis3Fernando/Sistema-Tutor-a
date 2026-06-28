<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Tutoria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function listarPorTutor(int $tutorId): array
    {
                $sql = "SELECT 
            st.id_sesion,
            st.fecha_ejecucion,
            st.tipo_tutoria,
            st.modalidad,
            COALESCE(st.recomendaciones, '') AS observaciones_seguimiento,
            st.nivel_riesgo_detectado,
            COALESCE(st.observaciones, '') AS tema_tratado,
            st.estado_sesion,
            u.id_usuario AS id_estudiante,
            CONCAT(u.nombres, ' ', u.apellidos) AS estudiante_nombre,
            u.dni AS estudiante_dni
        FROM sesiones_tutoria st
        INNER JOIN asignaciones a ON a.id_asignacion = st.id_asignacion
        INNER JOIN usuarios u ON u.id_usuario = a.id_estudiante
        WHERE a.id_tutor = :tutor_id
        ORDER BY st.fecha_ejecucion DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['tutor_id' => $tutorId]);

                return $stmt->fetchAll();
    }

public function listarPorEstudiante(int $estudianteId): array
{
    try {
        $sql = "SELECT 
                    st.id_sesion,
                    st.fecha_ejecucion,
                    st.hora_ejecucion,
                    st.tipo_tutoria,
                    st.modalidad,
                    st.medio_ejecucion, 
                    st.archivo_evidencia, 
                    st.estado_sesion,
                    st.recomendaciones,
                    st.duracion,
                    st.proxima_cita, 
                    COALESCE(cm.nombre_motivo, 'Sin tema registrado') AS motivo_nombre, 
                    ds.indicador_alerta,
                    ds.observacion_evolutiva,
                    CASE 
                        WHEN st.tipo_tutoria = 'Integral' AND ds.estado_estudiante IS NOT NULL 
                            THEN ds.estado_estudiante
                        WHEN st.tipo_tutoria = 'Socioemocional' AND ase.requiere_derivacion = 1 
                            THEN 'Crítico'
                        WHEN st.tipo_tutoria = 'Academica' AND oa.satisfaccion_estudiante <= 2 AND oa.satisfaccion_estudiante > 0 
                            THEN 'En Observación'
                        WHEN LOWER(st.nivel_riesgo_detectado) = 'alto' THEN 'Crítico'
                        WHEN LOWER(st.nivel_riesgo_detectado) = 'medio' THEN 'En Observación'
                        ELSE 'Estable'
                    END AS nivel_riesgo_detectado,
                    oa.observaciones_academicas,
                    ase.descripcion_estado_emocional,
                    ase.requiere_derivacion,
                    (SELECT GROUP_CONCAT(CONCAT('- ', descripcion_compromiso) SEPARATOR '\n') 
                     FROM compromisos WHERE id_sesion = st.id_sesion) AS lista_compromisos,
                    CONCAT(t.nombres, ' ', t.apellidos) AS tutor_nombre
                FROM sesiones_tutoria st
                INNER JOIN asignaciones a ON a.id_asignacion = st.id_asignacion
                LEFT JOIN cat_motivos cm ON st.id_motivo = cm.id_motivo
                LEFT JOIN deteccion_seguimiento ds ON st.id_sesion = ds.id_sesion
                LEFT JOIN orientacion_academica oa ON st.id_sesion = oa.id_sesion
                LEFT JOIN apoyo_socioemocional ase ON st.id_sesion = ase.id_sesion
                INNER JOIN usuarios t ON t.id_usuario = st.id_tutor
                WHERE a.id_estudiante = :estudiante_id
                ORDER BY st.fecha_ejecucion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estudiante_id', $estudianteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("ERROR listarPorEstudiante: " . $e->getMessage());
        return [];
    }
}

    public function listarAsignacionesTutor(int $tutorId): array
    {
        $sql = "SELECT 
                    a.id_asignacion,
                    a.periodo_academico,
                    u.id_usuario AS id_estudiante,
                    CONCAT(u.nombres, ' ', u.apellidos) AS estudiante_nombre,
                    u.dni AS estudiante_dni
                FROM asignaciones a
                INNER JOIN usuarios u ON u.id_usuario = a.id_estudiante
                WHERE a.id_tutor = :tutor_id
                ORDER BY a.periodo_academico DESC, estudiante_nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tutor_id' => $tutorId]);

        return $stmt->fetchAll();
    }

    public function listarMisEstudiantes(int $tutorId, array $filters = []): array
    {
        $sql = "
            SELECT 
                a.id_asignacion,
                u.id_usuario,
                u.nombres,
                u.apellidos,
                u.dni,
                u.correo,
                ed.codigo_unamba,
                ed.ciclo_actual,
                ed.situacion_academica
            FROM asignaciones a
            INNER JOIN estudiantes_detalle ed
                ON ed.id_estudiante = a.id_estudiante
            INNER JOIN usuarios u
                ON u.id_usuario = ed.id_usuario
            WHERE a.id_tutor = :tutor
        ";

        $params = ['tutor' => $tutorId];

        if (!empty($filters['codigo'])) {
            $sql .= " AND ed.codigo_unamba = :codigo";
            $params['codigo'] = $filters['codigo'];
        }
        if (!empty($filters['ciclo'])) {
            $sql .= " AND ed.ciclo_actual = :ciclo";
            $params['ciclo'] = $filters['ciclo'];
        }
        if (!empty($filters['situacion'])) {
            $sql .= " AND ed.situacion_academica = :situacion";
            $params['situacion'] = $filters['situacion'];
        }

        $sql .= " ORDER BY u.nombres, u.apellidos";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function historialPorEstudianteTutor(int $tutorId, int $estudianteId): array
    {
        $sql = "SELECT
                    st.id_sesion,
                    st.fecha_ejecucion,
                    st.tipo_tutoria,
                    st.modalidad,
                    COALESCE(st.recomendaciones, '') AS observaciones_seguimiento,
                    COALESCE(st.observaciones, '') AS compromisos_estudiante,
                    st.nivel_riesgo_detectado,
                    COALESCE(st.observaciones, '') AS tema_tratado,
                    st.proxima_cita,
                    st.estado_sesion
                FROM sesiones_tutoria st
                INNER JOIN asignaciones a ON a.id_asignacion = st.id_asignacion
                WHERE a.id_tutor = :tutor_id
                  AND a.id_estudiante = :estudiante_id
                ORDER BY st.fecha_ejecucion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tutor_id' => $tutorId,
            'estudiante_id' => $estudianteId,
        ]);

        return $stmt->fetchAll();
    }

    public function resumenPersonalPorTipo(int $tutorId, ?string $periodo = null, ?string $tipo = null, ?string $escuela = null): array
    {
        $sql = "SELECT st.tipo_tutoria, COUNT(*) AS total
                FROM sesiones_tutoria st
                INNER JOIN asignaciones a ON a.id_asignacion = st.id_asignacion
                INNER JOIN usuarios u ON u.id_usuario = a.id_estudiante
                WHERE a.id_tutor = :tutor_id";
        $params = ['tutor_id' => $tutorId];

        if (!empty($periodo)) {
            $sql .= " AND a.periodo_academico = :periodo";
            $params['periodo'] = $periodo;
        }
        if (!empty($tipo)) {
            $sql .= " AND st.tipo_tutoria = :tipo";
            $params['tipo'] = $tipo;
        }
        if (!empty($escuela)) {
            $sql .= " AND u.escuela_profesional = :escuela";
            $params['escuela'] = $escuela;
        }

        $sql .= " GROUP BY st.tipo_tutoria ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function obtenerProximaCita(int $idEstudiante)
{
    $stmt = $this->db->prepare("
        SELECT t.fecha_ejecucion, t.hora_ejecucion 
        FROM sesiones_tutoria t
        INNER JOIN asignaciones a ON t.id_asignacion = a.id_asignacion
        WHERE a.id_estudiante = ? 
        AND t.estado_sesion = 'Programada'
        AND t.fecha_ejecucion >= CURDATE()
        ORDER BY t.fecha_ejecucion ASC 
        LIMIT 1
    ");
    $stmt->execute([$idEstudiante]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function listarMotivos(): array
{
    $sql = "SELECT id_motivo, nombre_motivo
            FROM cat_motivos
            ORDER BY nombre_motivo ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener la cabecera del plan por ID de Tutor

    public function getPlanByTutor($id_tutor) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nombres, u.apellidos, e.nombre_escuela 
            FROM plan_trabajo_tutorial p
            JOIN tutor_detalles td ON p.id_tutor = td.id_tutor
            JOIN usuarios u ON td.id_tutor = u.id_usuario
            JOIN escuelas e ON td.id_escuela = e.id_escuela
            WHERE p.id_tutor = ? LIMIT 1
        ");
        $stmt->execute([$id_tutor]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener las actividades del cronograma
    public function getActividades($id_plan) {
        $stmt = $this->db->prepare("
            SELECT * FROM plan_actividades_cronograma 
            WHERE id_plan = ? 
            ORDER BY fecha ASC, hora ASC
        ");
        $stmt->execute([$id_plan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insertar nueva actividad
    public function guardarActividad($data) {
        $sql = "INSERT INTO plan_actividades_cronograma 
                (id_plan, fecha, hora, actividad_tipo, instrumento, objetivo_especifico, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['id_plan'],
            $data['fecha'],
            $data['hora'],
            $data['actividad_tipo'],
            $data['instrumento'],
            $data['objetivo_especifico'],
            $data['estado'],
        ]);
    }


// Mapeo preciso para el ENUM de la base de datos
private function mapearTipoTutoria(string $tipo): string {
    return [
        'Diagnostico' => 'Integral',
        'Seguimiento' => 'Socioemocional',
        'Referencia'  => 'Socioemocional',
        'Academica'   => 'Academica'
    ][$tipo] ?? 'Academica';
}

private function insertarDiagnostico($id_sesion, $data) {
    $sql = "INSERT INTO diagnostico_inicial (id_sesion, id_estudiante, p_entorno_uni, p_apoyo_social, s_alimentacion_sueno, s_ansiedad_estres, a_rendimiento, v_carrera_adecuada) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $this->db->prepare($sql)->execute([
        $id_sesion, $data['id_estudiante_principal'], $data['p_entorno_uni'], $data['p_apoyo_social'], 
        $data['s_alimentacion_sueno'], $data['s_ansiedad_estres'], $data['a_rendimiento'], $data['v_carrera_adecuada']
    ]);
}

public function registrarDerivacionDirecta(array $data)
{
    try {
        $sql = "INSERT INTO derivaciones (
                    id_tutor, 
                    id_estudiante, 
                    id_sesion, 
                    id_especialista,
                    area_destino, 
                    motivo_informe, 
                    resumen_caso, 
                    fecha_derivacion,
                    estado_atencion
                ) VALUES (
                    :id_tutor, 
                    :id_estudiante, 
                    NULL, 
                    :id_especialista,
                    :area, 
                    :motivo, 
                    :resumen, 
                    NOW(),
                    'Pendiente'
                )";

        $stmt = $this->db->prepare($sql);
        
        $idEspecialista = ($data['id_especialista'] > 0) ? (int)$data['id_especialista'] : null;

        return $stmt->execute([
            'id_tutor'      => (int)$data['id_tutor'],
            'id_estudiante' => (int)$data['id_estudiante'],
            'id_especialista' => $idEspecialista,
            'area'          => $data['area_destino'],
            'motivo'        => $data['motivo_informe'],
            'resumen'       => $data['resumen_caso']
        ]);
    } catch (PDOException $e) {
        error_log("Error en registrarDerivacionDirecta: " . $e->getMessage());
        return false;
    }
}
public function actualizarDerivacion(int $id_derivacion, array $data): bool
{
    try {
        // 1. Obtener el nombre del área según el especialista seleccionado
        $area = '';
        if (!empty($data['id_especialista'])) {
            $sqlEsp = "SELECT area FROM detalles_especialista WHERE id_especialista = :id LIMIT 1";
            $stmtEsp = $this->db->prepare($sqlEsp);
            $stmtEsp->execute([':id' => (int)$data['id_especialista']]);
            $esp = $stmtEsp->fetch(PDO::FETCH_ASSOC);
            $area = $esp['area'] ?? '';
        }

        // 2. Actualizar solo los campos permitidos, manteniendo el resto igual
        $sql = "UPDATE derivaciones 
                SET id_especialista = :id_esp,
                    area_destino = :area,
                    motivo_informe = :motivo,
                    resumen_caso = :resumen,
                    updated_at = NOW()
                WHERE id_derivacion = :id_der";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_esp' => !empty($data['id_especialista']) ? (int)$data['id_especialista'] : null,
            ':area'   => $area,
            ':motivo' => trim($data['motivo_informe'] ?? ''),
            ':resumen'=> trim($data['resumen_caso'] ?? ''),
            ':id_der' => $id_derivacion
        ]);
    } catch (PDOException $e) {
        error_log("Error en actualizarDerivacion: " . $e->getMessage());
        return false;
    }
}

public function eliminarDerivacion(int $id_derivacion): bool
{
    try {
        $sql = "DELETE FROM derivaciones WHERE id_derivacion = :id";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([':id' => $id_derivacion]);

        // Opcional: verificar que se eliminó al menos una fila
        return $ok && $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error en eliminarDerivacion: " . $e->getMessage());
        return false;
    }
}

public function obtenerEspecialistasActivos(): array
{
    try {
        // Traemos el ID, el área y el cargo para armar el select
        $sql = "SELECT id_especialista, area FROM detalles_especialista WHERE id_especialista IS NOT NULL";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener especialistas: " . $e->getMessage());
        return [];
    }
}
 
public function obtenerDatosTutorCompleto(int $tutorId): ?array
{
    $sql = "SELECT 
                u.id_usuario,
                u.nombres,
                u.apellidos,
                td.grado_academico,
                e.nombre_escuela
            FROM usuarios u
            JOIN tutor_detalles td ON td.id_tutor = u.id_usuario
            JOIN escuelas e ON e.id_escuela = td.id_escuela
            WHERE u.id_usuario = :id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $tutorId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


public function obtenerSesionPorId(int $idSesion): ?array
{
    $sql = "SELECT * FROM sesiones_tutoria WHERE id_sesion = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idSesion]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Obtiene el diagnóstico inicial más reciente de un estudiante
 */
public function obtenerDiagnosticoActual(int $idEstudiante): ?array
{
    $sql = "SELECT * FROM diagnostico_inicial 
            WHERE id_estudiante = :id 
            ORDER BY id_diagnostico DESC LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idEstudiante]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Lista todos los seguimientos realizados al estudiante
 */
 public function listarSeguimientosPorEstudiante(int $idEstudiante): array
{
    $sql = "SELECT 
                mejoras_respecto_inicio,
                seguimiento_personal_social as seg_personal,
                seguimiento_salud_mental as seg_salud,
                seguimiento_academico as seg_academico,
                seguimiento_vocacional as seg_vocacional,
                acciones_acuerdos,
                proxima_cita,
                nivel_personal_social,
                nivel_salud_mental,
                nivel_academico,
                nivel_vocacional
            FROM seguimiento_individual
            WHERE id_estudiante = :id
            ORDER BY id_seguimiento DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerNivelesRiesgo(int $idEstudiante): ?array
{
    $sql = "SELECT 
                nivel_personal_social,
                nivel_salud_mental,
                nivel_academico,
                nivel_vocacional
            FROM seguimiento_individual
            WHERE id_estudiante = :id
            ORDER BY id_seguimiento DESC
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idEstudiante]);

    //return $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ?: null;
}

/**
 * Lista todas las derivaciones/referencias del estudiante
 */
public function listarDerivacionesPorEstudiante(int $idEstudiante): array
{
    $sql = "SELECT 
                 id_derivacion,
                id_tutor,
                id_estudiante,
                id_especialista,
                fecha_derivacion,
                area_destino,
                motivo_informe,
                resumen_caso,
                estado_atencion,
                updated_at
            FROM derivaciones
            WHERE id_estudiante = :id
            ORDER BY fecha_derivacion DESC";
            $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $idEstudiante]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function guardarOActualizarDiagnostico(int $estudianteId, array $datos): bool
{
    try {
        // 1. Verificar si ya existe
        $sqlCheck = "SELECT id_diagnostico FROM diagnostico_inicial WHERE id_estudiante = :id LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $estudianteId]);
        $existe = $stmtCheck->fetch();

        // 2. Preparar el mapeo de datos para evitar errores de PDO
        // Esto asegura que solo pasamos al SQL lo que el SQL espera
        $params = [
            'id_estudiante'          => $estudianteId,
            'p_entorno_uni'          => $datos['p_entorno_uni'] ?? '',
            'p_apoyo_social'         => $datos['p_apoyo_social'] ?? '',
            'p_manejo_estres'        => $datos['p_manejo_estres'] ?? '',
            'p_integracion'          => $datos['p_integracion'] ?? '',
            's_alimentacion_sueno'   => $datos['s_alimentacion_sueno'] ?? '',
            's_ejercicio'            => $datos['s_ejercicio'] ?? '',
            's_concentracion'        => $datos['s_concentracion'] ?? '',
            's_ansiedad_estres'      => $datos['s_ansiedad_estres'] ?? '',
            's_manejo_emocional'     => $datos['s_manejo_emocional'] ?? '',
            's_consumo_sustancias'   => $datos['s_consumo_sustancias'] ?? '',
            's_riesgos_sustancias'   => $datos['s_riesgos_sustancias'] ?? '',
            'a_rendimiento'          => $datos['a_rendimiento'] ?? '',
            'a_dificultad_curso'     => $datos['a_dificultad_curso'] ?? '',
            'a_tecnicas_estudio'     => $datos['a_tecnicas_estudio'] ?? '',
            'a_asistencia'           => $datos['a_asistencia'] ?? '',
            'a_organizacion_tiempo'  => $datos['a_organizacion_tiempo'] ?? '',
            'a_apoyo_academico'      => $datos['a_apoyo_academico'] ?? '',
            'v_carrera_adecuada'     => $datos['v_carrera_adecuada'] ?? '',
            'v_metas'                => $datos['v_metas'] ?? '',
            'v_actividades_refuerzo' => $datos['v_actividades_refuerzo'] ?? '',
            'v_dificultades'         => $datos['v_dificultades'] ?? '',
            'fecha_actividad'        => $datos['fecha_actividad'] ?? date('Y-m-d'),
            'hora_inicio'            => $datos['hora_inicio'] ?? null,
            'hora_fin'               => $datos['hora_fin'] ?? null
        ];

        if ($existe) {
            // ACTUALIZAR
            $sql = "UPDATE diagnostico_inicial SET 
                        p_entorno_uni = :p_entorno_uni, p_apoyo_social = :p_apoyo_social, 
                        p_manejo_estres = :p_manejo_estres, p_integracion = :p_integracion,
                        s_alimentacion_sueno = :s_alimentacion_sueno, s_ejercicio = :s_ejercicio, 
                        s_concentracion = :s_concentracion, s_ansiedad_estres = :s_ansiedad_estres,
                        s_manejo_emocional = :s_manejo_emocional, s_consumo_sustancias = :s_consumo_sustancias, 
                        s_riesgos_sustancias = :s_riesgos_sustancias, a_rendimiento = :a_rendimiento,
                        a_dificultad_curso = :a_dificultad_curso, a_tecnicas_estudio = :a_tecnicas_estudio, 
                        a_asistencia = :a_asistencia, a_organizacion_tiempo = :a_organizacion_tiempo,
                        a_apoyo_academico = :a_apoyo_academico, v_carrera_adecuada = :v_carrera_adecuada, 
                        v_metas = :v_metas, v_actividades_refuerzo = :v_actividades_refuerzo, 
                        v_dificultades = :v_dificultades,
                        fecha_actividad = :fecha_actividad, hora_inicio = :hora_inicio, hora_fin = :hora_fin
                    WHERE id_estudiante = :id_estudiante";
        } else {
            // INSERTAR
            // Buscamos id_sesion solo si es obligatorio en tu BD
            $sesionSql = "SELECT s.id_sesion FROM sesiones_tutoria s 
                          JOIN asignaciones a ON s.id_asignacion = a.id_asignacion 
                          WHERE a.id_estudiante = :id LIMIT 1";
            $stSesion = $this->db->prepare($sesionSql);
            $stSesion->execute(['id' => $estudianteId]);
            $sesionId = $stSesion->fetchColumn();

            // Si id_sesion es NOT NULL en la BD, necesitamos un valor:
            $params['id_sesion'] = $sesionId ?: null; 

            $sql = "INSERT INTO diagnostico_inicial 
                    (id_estudiante, id_sesion, p_entorno_uni, p_apoyo_social, p_manejo_estres, p_integracion, 
                    s_alimentacion_sueno, s_ejercicio, s_concentracion, s_ansiedad_estres, s_manejo_emocional, 
                    s_consumo_sustancias, s_riesgos_sustancias, a_rendimiento, a_dificultad_curso, 
                    a_tecnicas_estudio, a_asistencia, a_organizacion_tiempo, a_apoyo_academico, 
                    v_carrera_adecuada, v_metas, v_actividades_refuerzo, v_dificultades,
                    fecha_actividad, hora_inicio, hora_fin) 
                    VALUES 
                    (:id_estudiante, :id_sesion, :p_entorno_uni, :p_apoyo_social, :p_manejo_estres, :p_integracion, 
                    :s_alimentacion_sueno, :s_ejercicio, :s_concentracion, :s_ansiedad_estres, :s_manejo_emocional, 
                    :s_consumo_sustancias, :s_riesgos_sustancias, :a_rendimiento, :a_dificultad_curso, 
                    :a_tecnicas_estudio, :a_asistencia, :a_organizacion_tiempo, :a_apoyo_academico, 
                    :v_carrera_adecuada, :v_metas, :v_actividades_refuerzo, :v_dificultades,
                    :fecha_actividad, :hora_inicio, :hora_fin)";
        }

        return $this->db->prepare($sql)->execute($params);

    } catch (PDOException $e) {
        error_log("Error en Diagnóstico: " . $e->getMessage());
        return false;
    }
}

 public function verExpedienteCompleto(int $id_estudiante): array
    {
        try {
            // 1. Datos Personales y Académicos
            $sqlEstudiante = "SELECT u.id_usuario, u.nombres, u.apellidos, u.dni, u.sexo, u.correo, u.celular,
                                     ed.codigo_unamba, ed.ciclo_actual, ed.situacion_academica,
                                     esc.nombre_escuela, esc.facultad
                              FROM usuarios u
                              INNER JOIN estudiantes_detalle ed ON u.id_usuario = ed.id_estudiante
                              INNER JOIN escuelas esc ON ed.id_escuela = esc.id_escuela
                              WHERE u.id_usuario = :id LIMIT 1";
            
            $stmt = $this->db->prepare($sqlEstudiante);
            $stmt->execute(['id' => $id_estudiante]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) return [];

            // 2. Diagnóstico Inicial (Respuestas del Alumno)
            $sqlDiag = "SELECT * FROM diagnostico_inicial WHERE id_estudiante = :id LIMIT 1";
            $stmt = $this->db->prepare($sqlDiag);
            $stmt->execute(['id' => $id_estudiante]);
            $diagnostico = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Historial de Seguimientos (Uniendo con Sesiones para obtener fecha)
            $sqlSeg = "SELECT si.*, st.fecha_ejecucion, st.modalidad, st.tema_sesion
                       FROM seguimiento_individual si
                       INNER JOIN sesiones_tutoria st ON si.id_sesion = st.id_sesion
                       INNER JOIN asignaciones a ON st.id_asignacion = a.id_asignacion
                       WHERE a.id_estudiante = :id
                       ORDER BY st.fecha_ejecucion DESC";
            
            $stmt = $this->db->prepare($sqlSeg);
            $stmt->execute(['id' => $id_estudiante]);
            $seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Historial de Derivaciones
            $sqlDer = "SELECT * FROM derivaciones 
                       WHERE id_estudiante = :id 
                       ORDER BY fecha_derivacion DESC";
            $stmt = $this->db->prepare($sqlDer);
            $stmt->execute(['id' => $id_estudiante]);
            $derivaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'estudiante'   => $estudiante,
                'diagnostico'  => $diagnostico ?: [],
                'seguimientos' => $seguimientos ?: [],
                'derivaciones' => $derivaciones ?: []
            ];

        } catch (PDOException $e) {
            error_log("Error en Tutoria::verExpedienteCompleto -> " . $e->getMessage());
            return [];
        }
}

 


private function obtenerIdEstudiante(int $idUsuario): ?int
{
    $sql = "SELECT id_estudiante 
            FROM estudiantes_detalle
            WHERE id_usuario = ?";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idUsuario]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row['id_estudiante'] ?? null;
}

public function obtenerDetalleEstudiante(int $idUsuario)
{
    $sql = "SELECT 
                u.id_usuario,
                u.nombres,
                u.apellidos,
                u.correo,
                e.codigo_unamba,
                e.ciclo_actual,
                esc.nombre_escuela,
                --a.periodo_academico,
                
                -- 👇 tutor (última asignación)
                CONCAT(t.nombres, ' ', t.apellidos) AS nombre_tutor

            FROM usuarios u
            LEFT JOIN estudiantes_detalle e 
                ON u.id_usuario = e.id_usuario

            LEFT JOIN escuelas esc 
                ON e.id_escuela = esc.id_escuela

            LEFT JOIN asignaciones a 
                ON a.id_estudiante = u.id_usuario

            LEFT JOIN usuarios t 
                ON t.id_usuario = a.id_tutor

            WHERE u.id_usuario = :id
            ORDER BY a.id_asignacion DESC
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idUsuario]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Insertar en sesiones_tutoria
public function crearSesion(array $data): int
{
    $sql = "INSERT INTO sesiones_tutoria 
            (id_asignacion, id_tutor, tipo_tutoria, tema_sesion, fecha_ejecucion, estado_sesion) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        $data['id_asignacion'],
        $data['id_tutor'],
        $data['tipo_tutoria'],
        $data['tema_sesion'],
        $data['fecha_ejecucion'],
        $data['estado_sesion']
    ]);

    return (int)$this->db->lastInsertId();
}

// Insertar en seguimiento_individual
public function registrarSeguimientoIndividual(array $data)
{
    $sql = "INSERT INTO seguimiento_individual (
                id_sesion, mejoras_respecto_inicio, seguimiento_personal_social, 
                seguimiento_salud_mental, seguimiento_academico, seguimiento_vocacional, 
                acciones_acuerdos, recomendaciones, observaciones_generales, proxima_cita ,nivel_personal_social,
                nivel_salud_mental,
                nivel_academico,
                nivel_vocacional
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        $data['id_sesion'],
        $data['mejoras_respecto_inicio'],
        $data['seguimiento_personal_social'],
        $data['seguimiento_salud_mental'],
        $data['seguimiento_academico'],
        $data['seguimiento_vocacional'],
        $data['acciones_acuerdos'],
        $data['recomendaciones'],
        $data['observaciones_generales'],
        $data['proxima_cita'],
       $data['nivel_personal_social'],
       $data['nivel_salud_mental'],
        $data['nivel_academico'],
       $data['nivel_vocacional']
    ]);
}


public function obtenerIdAsignacion(int $idEstudiante, int $idTutor): ?int
{
    $sql = "SELECT id_asignacion FROM asignaciones 
            WHERE id_estudiante = :est AND id_tutor = :tut LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['est' => $idEstudiante, 'tut' => $idTutor]);
    $res = $stmt->fetch();
    return $res ? (int)$res['id_asignacion'] : null;
}


// Dentro de la clase Tutoria en Tutoria.php

/**
 * Obtiene los datos de una sesión específica para edición
 */
public function obtenerEstudiantesConAsistencia(int $tutorId, int $idSesion, string $periodo): array
{
    $sql = "SELECT 
                u.id_usuario, 
                u.apellidos, 
                u.nombres, 
                ed.codigo_unamba, 
                ed.ciclo_actual as semestre,
                IF(sa.id_estudiante IS NOT NULL, 1, 0) as asistio
            FROM asignaciones a
            JOIN estudiantes_detalle ed ON a.id_estudiante = ed.id_estudiante
            JOIN usuarios u ON ed.id_usuario = u.id_usuario
            LEFT JOIN sesion_asistencia sa ON (sa.id_estudiante = u.id_usuario AND sa.id_sesion = :id_sesion)
            WHERE a.id_tutor = :id_tutor 
              AND a.periodo_academico = :periodo
            ORDER BY u.apellidos ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id_sesion' => $idSesion,
        'id_tutor'  => $tutorId,
        'periodo'   => $periodo
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Proceso transaccional para guardar datos de sesión y sincronizar asistencia
 */
public function guardarOActualizarSesionGrupal(array $datos, array $asistencias, int $id_tutor): bool {
    try {
        $this->db->beginTransaction();

        $id_sesion = (int)$datos['id_sesion'];

        if ($id_sesion === 0) {
            // --- ES UNA SESIÓN NUEVA: INSERT ---
            $sql = "INSERT INTO sesiones_tutoria 
                    (id_tutor, id_actividad, objetivo_sesion, fecha_ejecucion, hora_inicio, hora_fin, archivo_evidencia) 
                    VALUES (:tutor, :id_actividad, :obj, :fecha, :h_ini, :h_fin, :archivo)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tutor'   => $id_tutor,
                'id_actividad'=>$datos['id_actividad'],
                'obj'     => $datos['objetivo_sesion'],
                'fecha'   => $datos['fecha_ejecucion'],
                'h_ini'   => $datos['hora_inicio'],
                'h_fin'   => $datos['hora_fin'],
                'archivo' => $datos['archivo_evidencia']
            ]);
            $id_sesion = (int)$this->db->lastInsertId();
        } else {
            // --- ES UNA SESIÓN EXISTENTE: UPDATE ---
            $sql = "UPDATE sesiones_tutoria SET 
                        id_actividad = :id_actividad,
                         objetivo_sesion = :obj, 
                        fecha_ejecucion = :fecha, hora_inicio = :h_ini, hora_fin = :h_fin" . 
                        ($datos['archivo_evidencia'] ? ", archivo_evidencia = :archivo" : "") . 
                    " WHERE id_sesion = :id";
            
            $params = [
                'id_actividad'=>$datos['id_actividad'],
                'obj'   => $datos['objetivo_sesion'],
                'fecha' => $datos['fecha_ejecucion'],
                'h_ini' => $datos['hora_inicio'],
                'h_fin' => $datos['hora_fin'],
                'id'    => $id_sesion
            ];
            if ($datos['archivo_evidencia']) $params['archivo'] = $datos['archivo_evidencia'];
            $this->db->prepare($sql)->execute($params);
        }

        // --- REGISTRAR ASISTENCIA (Para ambos casos: Limpiar y Re-insertar) ---
        $this->db->prepare("DELETE FROM sesion_asistencia WHERE id_sesion = ?")->execute([$id_sesion]);

        if (!empty($asistencias)) {
            $stmtAsis = $this->db->prepare("INSERT INTO sesion_asistencia (id_sesion, id_estudiante, asistencia) VALUES (?, ?, 1)");
            foreach ($asistencias as $id_estudiante => $valor) {
                $stmtAsis->execute([$id_sesion, $id_estudiante]);
            }
        }

        $this->db->commit();
        return true;
            }catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log("Error Senior: " . $e->getMessage());
                return false;
            }
}

public function obtenerActividadesProgramadas(int $id_tutor, string $periodo)
{
    
    $sql = "SELECT pac.*
            FROM plan_actividades_cronograma pac
            INNER JOIN plan_trabajo_tutorial ptt ON ptt.id_plan = pac.id_plan
            WHERE ptt.id_tutor = ?
            AND ptt.periodo_academico = ?
            AND pac.estado = 'Programada'  
            ORDER BY pac.fecha ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id_tutor, $periodo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerActividadPorId(int $idActividad)
{
    $sql = "SELECT *
            FROM plan_actividades_cronograma
            WHERE id_actividad = ?";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idActividad]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function marcarActividadComoRealizada($id_actividad) {
    $sql = "UPDATE plan_actividades_cronograma SET estado = 'Realizado' WHERE id_actividad = ?";
    return $this->db->prepare($sql)->execute([$id_actividad]);
}
public function guardarOActualizarSeguimiento(array $data)
{
    try {
        // 1. Verificar si ya existe un registro para este estudiante
        $sqlCheck = "SELECT id_seguimiento FROM seguimiento_individual WHERE id_estudiante = :id_est LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id_est' => $data['id_estudiante']]);
        $existe = $stmtCheck->fetch();

        if ($existe) {
            // 2. Si existe, hacemos UPDATE
            $sql = "UPDATE seguimiento_individual SET 
                        seguimiento_personal_social = :p_social, 
                        seguimiento_salud_mental = :s_mental, 
                        seguimiento_academico = :a_academico, 
                        seguimiento_vocacional = :v_vocacional, 
                        acciones_acuerdos = :acciones, 
                        recomendaciones = :recomen, 
                        observaciones_generales = :obs, 
                        proxima_cita = :cita,
                        id_tutor = :id_tutor ,
                        nivel_personal_social = :n_p,
                        nivel_salud_mental = :n_s,
                        nivel_academico = :n_a,
                        nivel_vocacional = :n_v
                    WHERE id_estudiante = :id_estudiante";
        } else {
            // 3. Si no existe, hacemos INSERT
            $sql = "INSERT INTO seguimiento_individual (
                        id_estudiante, id_tutor, seguimiento_personal_social, 
                        seguimiento_salud_mental, seguimiento_academico, 
                        seguimiento_vocacional, acciones_acuerdos, 
                        recomendaciones, observaciones_generales, proxima_cita, nivel_personal_social,
                        nivel_salud_mental,
                        nivel_academico,
                        nivel_vocacional
                    ) VALUES (
                        :id_estudiante, :id_tutor, :p_social, :s_mental, :a_academico, 
                        :v_vocacional, :acciones, :recomen, :obs, :cita, :n_p, :n_s, :n_a, :n_v
                    )";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_estudiante' => $data['id_estudiante'],
            'id_tutor'      => $data['id_tutor'],
            'p_social'      => $data['seguimiento_personal_social'],
            's_mental'      => $data['seguimiento_salud_mental'],
            'a_academico'   => $data['seguimiento_academico'],
            'v_vocacional'  => $data['seguimiento_vocacional'],
            'acciones'      => $data['acciones_acuerdos'],
            'recomen'       => $data['recomendaciones'],
            'obs'           => $data['observaciones_generales'],
            'cita'          => !empty($data['proxima_cita']) ? $data['proxima_cita'] : null,
            'n_p'          => $data['nivel_personal_social'],
            'n_s'          => $data['nivel_salud_mental'],
            'n_a'         =>$data['nivel_academico'],
            'n_v'         =>$data['nivel_vocacional']
        ]);

    } catch (PDOException $e) {
        error_log("Error en guardarOActualizarSeguimiento: " . $e->getMessage());
        return false;
    }
}

public function obtenerSeguimientoPorEstudiante(int $idEstudiante)
{
    try {
        // Buscamos el registro de seguimiento para este alumno específico
        $sql = "SELECT * FROM seguimiento_individual WHERE id_estudiante = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idEstudiante]);
        
        // Retornamos el resultado (será un array con los datos o false si no hay nada)
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerSeguimientoPorEstudiante: " . $e->getMessage());
        return null;
    }
}


/**
 * Listar derivaciones dirigidas a un área específica
 */
public function listarDerivacionesPorArea(string $area)
{
    $sql = "SELECT d.*, 
                   IFNULL(d.estado_atencion,'Pendiente') AS estado_atencion,
                   u_t.nombres as tutor_nombre, u_t.apellidos as tutor_apellido,
                   u_e.nombres as est_nombre, u_e.apellidos as est_apellido, est.codigo_unamba,
                   esc.nombre_escuela, est.ciclo_actual
            FROM derivaciones d
            JOIN usuarios u_t ON d.id_tutor = u_t.id_usuario
            JOIN usuarios u_e ON d.id_estudiante = u_e.id_usuario
            JOIN estudiantes_detalle est ON u_e.id_usuario = est.id_usuario
            JOIN escuelas esc ON est.id_escuela = esc.id_escuela
            WHERE d.area_destino = :area
            ORDER BY d.fecha_derivacion DESC";

    $stmt = $this->db->prepare($sql);
    
    $stmt->execute(['area' => $area]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Especialista responde la derivación (F-TUT-06)
 */
public function registrarAtencionEspecialista(int $idDerivacion, int $idEspecialista, string $acciones)
{
    $sql = "UPDATE derivaciones SET 
                id_especialista = :id_esp,
                acciones_realizadas = :acciones,
                estado_atencion = 'Cerrado',
                updated_at = NOW()
            WHERE id_derivacion = :id_der";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        'id_esp'   => $idEspecialista,
        'acciones' => $acciones,
        'id_der'   => $idDerivacion
    ]);
}


public function obtenerDetalleEspecialista(int $idUsuario): ?array
{
    $sql = "SELECT area
            FROM detalles_especialista
            WHERE id_usuario = :id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idUsuario]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function obtenerResumenDashboard(int $idUsuario): array
{
    $sql = "
        SELECT td.id_tutor
        FROM tutor_detalles td
        WHERE td.id_usuario = ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idUsuario]);
    $tutor = $stmt->fetch();

    $idTutor = $tutor['id_tutor'] ?? 0;

    // Total estudiantes
    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM asignaciones 
        WHERE id_tutor=?
    ");
    $stmt->execute([$idTutor]);
    $total_estudiantes = $stmt->fetchColumn();

    // Actividades
    $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM plan_actividades_cronograma ac
        JOIN plan_trabajo_tutorial ptt ON ac.id_plan=ptt.id_plan
        WHERE ptt.id_tutor=? AND ac.fecha>=CURDATE()
    ");
    $stmt->execute([$idTutor]);
    $activ_pendientes = $stmt->fetchColumn();

    // Derivaciones
    $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM derivaciones
        WHERE id_tutor=? AND estado_atencion='Pendiente'
    ");
    $stmt->execute([$idTutor]);
    $deriv_pendientes = $stmt->fetchColumn();

    // Agenda
    $stmt = $this->db->prepare("
        SELECT ac.fecha,ac.hora,
               ac.actividad_tipo,
               ac.objetivo_especifico
        FROM plan_actividades_cronograma ac
        JOIN plan_trabajo_tutorial ptt ON ac.id_plan=ptt.id_plan
        WHERE ptt.id_tutor=? AND ac.fecha>=CURDATE()
        ORDER BY ac.fecha,ac.hora
        LIMIT 5
    ");

    $stmt->execute([$idTutor]);
    $agenda = $stmt->fetchAll();

   

   /*   // Riesgo
    $stmt = $this->db->prepare("
        SELECT u.apellidos,u.nombres,
               ed.ciclo_actual
        FROM estudiantes_detalle ed
        JOIN usuarios u ON ed.id_usuario=u.id_usuario
        JOIN asignaciones a ON ed.id_estudiante=a.id_estudiante
        WHERE a.id_tutor=? AND ed.situacion_academica='Riesgo'
    ");
    $stmt->execute([$idTutor]);
    $estudiantes_riesgo = $stmt->fetchAll();
   */
    $stmt = $this->db->prepare("
    SELECT COUNT(DISTINCT id_estudiante) AS total
    FROM seguimiento_individual
    WHERE id_tutor = ?
    AND (
        nivel_personal_social = 1 OR
        nivel_salud_mental = 1 OR
        nivel_academico = 1 OR
        nivel_vocacional = 1
    )
    ");

            $stmt->execute([$idTutor]);
            $total_riesgo = $stmt->fetchColumn();

            $stmt = $this->db->prepare("
                SELECT DISTINCT 
                    u.id_usuario, u.apellidos, u.nombres, ed.ciclo_actual,
                    s.nivel_personal_social, s.nivel_salud_mental,
                    s.nivel_academico, s.nivel_vocacional,
                    -- Agregamos un conteo de derivaciones pendientes
                    (SELECT COUNT(*) FROM derivaciones d 
                    WHERE d.id_estudiante = u.id_usuario 
                    AND d.estado_atencion = 'Pendiente') as derivacion_pendiente
                FROM seguimiento_individual s
                JOIN estudiantes_detalle ed ON s.id_estudiante = ed.id_estudiante
                JOIN usuarios u ON ed.id_usuario = u.id_usuario
                WHERE s.id_tutor = ?
                AND (
                    s.nivel_personal_social = 1 OR
                    s.nivel_salud_mental = 1 OR
                    s.nivel_academico = 1 OR
                    s.nivel_vocacional = 1
                )
            ");

            $stmt->execute([$idTutor]);
            $lista_riesgo = $stmt->fetchAll();

                return [
                    'total_estudiantes'=>$total_estudiantes,
                    'activ_pendientes'=>$activ_pendientes,
                    'deriv_pendientes'=>$deriv_pendientes,
                    'agenda'=>$agenda,
                // 'estudiantes_riesgo'=>$estudiantes_riesgo
                    'estudiante_riesgo'=>$total_riesgo,
                    'estudiantes_riesgo'=>$lista_riesgo
                ];
}
public function obtenerConteoNiveles(int $idUsuario): array
{
    // obtener tutor
    $stmt = $this->db->prepare("
        SELECT id_tutor
        FROM tutor_detalles
        WHERE id_usuario=?
    ");
    $stmt->execute([$idUsuario]);
    $tutor = $stmt->fetch();

    $idTutor = $tutor['id_tutor'] ?? 0;

    // conteo por niveles
    $stmt = $this->db->prepare("
        SELECT

        /* PERSONAL SOCIAL */
        SUM(nivel_personal_social = 1) AS ps_alto,
        SUM(nivel_personal_social = 2) AS ps_medio,
        SUM(nivel_personal_social = 3) AS ps_adecuado,

        /* SALUD MENTAL */
        SUM(nivel_salud_mental = 1) AS sm_alto,
        SUM(nivel_salud_mental = 2) AS sm_medio,
        SUM(nivel_salud_mental = 3) AS sm_adecuado,

        /* ACADEMICO */
        SUM(nivel_academico = 1) AS ac_alto,
        SUM(nivel_academico = 2) AS ac_medio,
        SUM(nivel_academico = 3) AS ac_adecuado,

        /* VOCACIONAL */
        SUM(nivel_vocacional = 1) AS vo_alto,
        SUM(nivel_vocacional = 2) AS vo_medio,
        SUM(nivel_vocacional = 3) AS vo_adecuado

        FROM seguimiento_individual
        WHERE id_tutor=?
    ");

    $stmt->execute([$idTutor]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function listarSesionesPorEstudiante(int $idEstudiante): array 
{
    $sql = "SELECT 
                s.id_sesion,
                s.objetivo_sesion,
                s.fecha_ejecucion,
                s.hora_inicio,
                s.hora_fin,
                s.archivo_evidencia,
                sa.asistencia,
                sa.archivo_estudiante
            FROM sesiones_tutoria s
            INNER JOIN sesion_asistencia sa 
                ON sa.id_sesion = s.id_sesion
            WHERE sa.id_estudiante = :id
            ORDER BY s.fecha_ejecucion DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function listarActividadesProgramadas($idEstudiante)
{
    $sql = "
        SELECT pac.*
        FROM asignaciones a
        INNER JOIN plan_trabajo_tutorial ptt 
            ON ptt.id_tutor = a.id_tutor
        INNER JOIN plan_actividades_cronograma pac 
            ON pac.id_plan = ptt.id_plan
        WHERE a.id_estudiante = ?
        ORDER BY pac.fecha ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerCitasDelEstudiante(int $idUsuario): array
{
    $sql = "SELECT 
                c.id_cita,
                c.fecha_cita,
                c.hora_cita,
                c.modalidad,
                c.estado,

                d.motivo_informe,
                d.fecha_derivacion

            FROM citas_especialista c

            INNER JOIN derivaciones d
                ON d.id_derivacion = c.id_derivacion

            INNER JOIN estudiantes_detalle ed
                ON ed.id_estudiante = d.id_estudiante

            WHERE ed.id_usuario = :id

            ORDER BY c.fecha_cita ASC, c.hora_cita ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $idUsuario]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerCitasProgramadas(int $idEstudiante)
{
    $sql = "
        SELECT 
            c.id_cita,
            c.fecha_cita,
            c.hora_cita,
            c.modalidad,
            c.estado,
            d.area_destino,
            d.motivo_informe
        FROM citas_especialista c
        INNER JOIN derivaciones d 
            ON d.id_derivacion = c.id_derivacion
        WHERE d.id_estudiante = ?
        AND c.estado = 'Programada'
        ORDER BY c.fecha_cita ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function obtenerEstudiantePorUsuario(int $idUsuario)
{
    $sql = "SELECT id_estudiante
            FROM estudiantes_detalle
            WHERE id_usuario = :id_usuario
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id_usuario' => $idUsuario
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function obtenerCitasEspecialista(int $idEstudiante)
{
    $sql = "SELECT c.*, d.motivo_informe AS motivo
            FROM citas_especialista c
            INNER JOIN derivaciones d 
                ON d.id_derivacion = c.id_derivacion
            WHERE d.id_estudiante = ?
            AND c.estado = 'Programada'
            ORDER BY c.fecha_cita ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerEstudiantePorId(int $idEstudiante): ?array
{
    try {
        $sql = "SELECT e.*, a.periodo_academico 
                FROM estudiantes_detalle e
                LEFT JOIN asignaciones a ON e.id_usuario = a.id_estudiante
                WHERE e.id_usuario = :id 
                LIMIT 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idEstudiante]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        return null;
    }
}
public function obtenerPeriodoTutor(int $idTutor): ?array
{
    try {
        $sql = "SELECT e.*, a.periodo_academico 
                FROM tutor_detalles e
                LEFT JOIN asignaciones a ON e.id_usuario = a.id_tutor
                WHERE e.id_usuario = :id 
                LIMIT 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idTutor]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        return null;
    }
}


public function obtenerAsistentesPorSesion(int $idSesion)
{
    // Unimos estudiantes_detalle con usuarios para traer nombres y apellidos
    $sql = "SELECT sa.asistencia, sa.archivo_estudiante, e.codigo_unamba, 
                   u.nombres, u.apellidos
            FROM sesion_asistencia sa
            INNER JOIN estudiantes_detalle e ON sa.id_estudiante = e.id_estudiante
            INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
            WHERE sa.id_sesion = :id_sesion";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id_sesion' => $idSesion
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function guardarEvidenciaEstudiante(int $idSesion, int $idUsuario, string $ruta): bool
{
    try {
        // Buscamos el id_estudiante asociado a ese id_usuario antes de actualizar
        $sql = "UPDATE sesion_asistencia sa
                INNER JOIN estudiantes_detalle ed ON sa.id_estudiante = ed.id_estudiante
                SET sa.archivo_estudiante = :ruta, sa.updated_at = NOW() 
                WHERE sa.id_sesion = :id_s AND ed.id_usuario = :id_u";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ruta' => $ruta,
            'id_s' => $idSesion,
            'id_u' => $idUsuario
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

public function obtenerDataReporteGeneral(int $idTutor, string $periodo): array
{
    try {
        $sql = "SELECT 
                    u.id_usuario, 
                    ed.codigo_unamba, 
                    CONCAT(u.apellidos, ' ', u.nombres) as estudiante_nombre,
                    ed.ciclo_actual,
                    ed.situacion_academica,
                    -- Conteo de asistencias (Subconsulta con parámetro único)
                    (SELECT COUNT(*) FROM sesion_asistencia sa 
                     INNER JOIN sesiones_tutoria st ON sa.id_sesion = st.id_sesion 
                     WHERE sa.id_estudiante = ed.id_estudiante 
                     AND st.id_tutor = :id_tutor_asis) as total_asistencias,
                    -- Niveles de riesgo
                    si.nivel_personal_social, 
                    si.nivel_salud_mental, 
                    si.nivel_academico, 
                    si.nivel_vocacional,
                    -- Conteo de derivaciones (Subconsulta con parámetro único)
                    (SELECT COUNT(*) FROM derivaciones d 
                     WHERE d.id_estudiante = u.id_usuario 
                     AND d.id_tutor = :id_tutor_der) as total_derivaciones
                FROM asignaciones a
                INNER JOIN usuarios u ON a.id_estudiante = u.id_usuario
                INNER JOIN estudiantes_detalle ed ON u.id_usuario = ed.id_usuario
                LEFT JOIN seguimiento_individual si ON ed.id_estudiante = si.id_estudiante
                WHERE a.id_tutor = :id_tutor_main 
                AND a.periodo_academico = :periodo_main
                ORDER BY u.apellidos ASC";

        $stmt = $this->db->prepare($sql);
        
        // Ejecutamos pasando cada valor para su respectivo marcador único
        $stmt->execute([
            'id_tutor_asis' => $idTutor,
            'id_tutor_der'  => $idTutor,
            'id_tutor_main' => $idTutor,
            'periodo_main'  => $periodo
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerDataReporteGeneral: " . $e->getMessage());
        return [];
    }
}

}

