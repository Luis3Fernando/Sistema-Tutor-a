<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class AdminAsignacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function listarEscuelas(): array
    {
        $sql = "SELECT id_escuela, nombre_escuela, facultad
                FROM escuelas
                ORDER BY nombre_escuela ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll() ?: [];
    }

    public function crearEscuela(string $nombreEscuela, string $facultad): array
    {
        $nombreEscuela = trim($nombreEscuela);
        $facultad = trim($facultad);

        if ($nombreEscuela === '' || $facultad === '') {
            return ['ok' => false, 'msg' => 'Debe completar nombre de escuela y facultad.'];
        }

        $stmtExiste = $this->db->prepare(
            "SELECT COUNT(*) FROM escuelas WHERE nombre_escuela = :nombre_escuela"
        );
        $stmtExiste->execute(['nombre_escuela' => $nombreEscuela]);

        if ((int)$stmtExiste->fetchColumn() > 0) {
            return ['ok' => false, 'msg' => 'La escuela ya está registrada.'];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO escuelas (nombre_escuela, facultad) VALUES (:nombre_escuela, :facultad)"
            );
            $stmt->execute([
                'nombre_escuela' => $nombreEscuela,
                'facultad' => $facultad,
            ]);

            return ['ok' => true, 'msg' => 'Escuela registrada correctamente.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => 'Error al registrar escuela: ' . $e->getMessage()];
        }
    }

    public function actualizarEscuela(int $idEscuela, string $nombreEscuela, string $facultad): array
    {
        $nombreEscuela = trim($nombreEscuela);
        $facultad = trim($facultad);

        if ($idEscuela <= 0 || $nombreEscuela === '' || $facultad === '') {
            return ['ok' => false, 'msg' => 'Datos inválidos para actualizar escuela.'];
        }

        $stmtExiste = $this->db->prepare(
            "SELECT COUNT(*) FROM escuelas WHERE nombre_escuela = :nombre_escuela AND id_escuela <> :id_escuela"
        );
        $stmtExiste->execute([
            'nombre_escuela' => $nombreEscuela,
            'id_escuela' => $idEscuela,
        ]);

        if ((int)$stmtExiste->fetchColumn() > 0) {
            return ['ok' => false, 'msg' => 'Ya existe otra escuela con ese nombre.'];
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE escuelas
                 SET nombre_escuela = :nombre_escuela, facultad = :facultad
                 WHERE id_escuela = :id_escuela"
            );
            $stmt->execute([
                'nombre_escuela' => $nombreEscuela,
                'facultad' => $facultad,
                'id_escuela' => $idEscuela,
            ]);

            if ($stmt->rowCount() <= 0) {
                return ['ok' => false, 'msg' => 'No se encontró la escuela o no hubo cambios.'];
            }

            return ['ok' => true, 'msg' => 'Escuela actualizada correctamente.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => 'Error al actualizar escuela: ' . $e->getMessage()];
        }
    }

    public function eliminarEscuela(int $idEscuela): array
{
    if ($idEscuela <= 0) {
        return ['ok' => false, 'msg' => 'Escuela no válida.'];
    }

    // Verificar tutores
    $stmtUsoTutor = $this->db->prepare("
        SELECT COUNT(*) 
        FROM tutor_detalles 
        WHERE id_escuela = :id_escuela
    ");

    $stmtUsoTutor->execute([
        'id_escuela' => $idEscuela
    ]);

    // Verificar estudiantes
    $stmtUsoEst = $this->db->prepare("
        SELECT COUNT(*) 
        FROM estudiantes_detalle 
        WHERE id_escuela = :id_escuela
    ");

    $stmtUsoEst->execute([
        'id_escuela' => $idEscuela
    ]);

    // Verificar especialistas
    $stmtUsoEsp = $this->db->prepare("
        SELECT COUNT(*) 
        FROM detalles_especialista 
        WHERE id_escuela = :id_escuela
    ");

    $stmtUsoEsp->execute([
        'id_escuela' => $idEscuela
    ]);

    $totalTutor = (int)$stmtUsoTutor->fetchColumn();
    $totalEst = (int)$stmtUsoEst->fetchColumn();
    $totalEsp = (int)$stmtUsoEsp->fetchColumn();

    if ($totalTutor > 0 || $totalEst > 0 || $totalEsp > 0) {

        return [
            'ok' => false,
            'msg' => 'No se puede eliminar la escuela porque tiene registros asociados.'
        ];
    }

    try {

        $stmt = $this->db->prepare("
            DELETE FROM escuelas 
            WHERE id_escuela = :id_escuela
        ");

        $stmt->execute([
            'id_escuela' => $idEscuela
        ]);

        if ($stmt->rowCount() <= 0) {

            return [
                'ok' => false,
                'msg' => 'No se encontró la escuela.'
            ];
        }

        return [
            'ok' => true,
            'msg' => 'Escuela eliminada correctamente.'
        ];

    } catch (Throwable $e) {

        return [
            'ok' => false,
            'msg' => 'Error al eliminar escuela: ' . $e->getMessage()
        ];
    }
}

    public function listar(): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.dni,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.celular,
                    u.estado,
                    e.nombre_escuela
                FROM usuarios u
                LEFT JOIN tutor_detalles td ON td.id_usuario = u.id_usuario
                LEFT JOIN escuelas e ON e.id_escuela = td.id_escuela
                WHERE u.rol = 'tutor'
                ORDER BY u.apellidos ASC, u.nombres ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll() ?: [];
    }

    public function listarEstudiantes(): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.dni,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.estado,
                    ed.codigo_unamba,
                    ed.ciclo_actual,
                    ed.situacion_academica,
                    e.nombre_escuela
                FROM usuarios u
                LEFT JOIN estudiantes_detalle ed ON ed.id_usuario = u.id_usuario
                LEFT JOIN escuelas e ON e.id_escuela = ed.id_escuela
                WHERE u.rol = 'estudiante'
                ORDER BY u.apellidos ASC, u.nombres ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll() ?: [];
    }

public function listarEspecialistas(): array
{
    $sql = "SELECT
                u.idusuario,
                u.dni,
                u.nombres,
                u.apellidos,
                u.correo,
                u.estado,
                de.area,
                de.cargo,
                e.nombreescuela
            FROM usuarios u
            INNER JOIN detallesespecialista de ON de.idusuario = u.idusuario
            LEFT JOIN escuelas e ON e.idescuela = de.idescuela
            WHERE u.rol = 'especialista'
            ORDER BY u.apellidos ASC, u.nombres ASC";

    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function listarPeriodosAsignaciones(): array
    {
        $sql = "SELECT DISTINCT periodo_academico
                FROM asignaciones
                WHERE periodo_academico IS NOT NULL AND periodo_academico <> ''
                ORDER BY periodo_academico DESC";
        $stmt = $this->db->query($sql);

        $rows = $stmt->fetchAll() ?: [];

        return array_values(array_filter(array_map(static fn(array $r): string => (string)($r['periodo_academico'] ?? ''), $rows)));
    }

    public function listarTutoresPorEscuela(?int $idEscuela = null): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    u.estado,
                    td.id_escuela,
                    e.nombre_escuela,
                    (
                        SELECT COUNT(*)
                        FROM asignaciones a
                        WHERE a.id_tutor = u.id_usuario
                    ) AS total_asignados
                FROM usuarios u
                INNER JOIN tutor_detalles td ON td.id_tutor = u.id_usuario
                LEFT JOIN escuelas e ON e.id_escuela = td.id_escuela
                WHERE u.rol = 'tutor'";

        $params = [];
        if ($idEscuela !== null && $idEscuela > 0) {
            $sql .= " AND td.id_escuela = :id_escuela";
            $params['id_escuela'] = $idEscuela;
        }

        $sql .= " ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function listarEstudiantesPorEscuelaYPeriodo(?int $idEscuela = null, ?string $periodo = null): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    u.estado,
                    ed.codigo_unamba,
                    ed.ciclo_actual,
                    ed.id_escuela,
                    e.nombre_escuela
                FROM usuarios u
                INNER JOIN estudiantes_detalle ed ON ed.id_estudiante = u.id_usuario
                LEFT JOIN escuelas e ON e.id_escuela = ed.id_escuela
                WHERE u.rol = 'estudiante' AND u.estado = 1";

        $params = [];
        if ($idEscuela !== null && $idEscuela > 0) {
            $sql .= " AND ed.id_escuela = :id_escuela";
            $params['id_escuela'] = $idEscuela;
        }

        if ($periodo !== null && $periodo !== '') {
            $sql .= " AND NOT EXISTS (
                        SELECT 1 FROM asignaciones a
                        WHERE a.id_estudiante = u.id_usuario
                          AND a.periodo_academico = :periodo
                    )";
            $params['periodo'] = $periodo;
        }

        $sql .= " ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function listarAsignacionesActuales(?int $idEscuela = null, ?string $periodo = null): array
    {
        $sql = "SELECT
                    a.id_asignacion,
                    a.periodo_academico,
                    est.id_usuario AS id_estudiante,
                    CONCAT(est.apellidos, ' ', est.nombres) AS estudiante,
                    tut.id_usuario AS id_tutor,
                    CONCAT(tut.apellidos, ' ', tut.nombres) AS tutor,
                    e.nombre_escuela,
                    ed.ciclo_actual
                FROM asignaciones a
                INNER JOIN usuarios est ON est.id_usuario = a.id_estudiante
                INNER JOIN usuarios tut ON tut.id_usuario = a.id_tutor
                LEFT JOIN estudiantes_detalle ed ON ed.id_estudiante = est.id_usuario
                LEFT JOIN escuelas e ON e.id_escuela = ed.id_escuela
                WHERE 1=1";
        $params = [];

        if ($idEscuela !== null && $idEscuela > 0) {
            $sql .= " AND ed.id_escuela = :id_escuela";
            $params['id_escuela'] = $idEscuela;
        }

        if ($periodo !== null && $periodo !== '') {
            $sql .= " AND a.periodo_academico = :periodo";
            $params['periodo'] = $periodo;
        }

        $sql .= " ORDER BY a.id_asignacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function asignarManual(array $estudiantesIds, int $idTutor, string $periodo): array
    {
        if ($idTutor <= 0 || $periodo === '' || empty($estudiantesIds)) {
            return ['ok' => false, 'msg' => 'Datos incompletos para asignación manual.'];
        }

        $insertados = 0;
        $omitidos = 0;

        try {
            $this->db->beginTransaction();

            foreach ($estudiantesIds as $idEstudiante) {
                $idEstudiante = (int)$idEstudiante;
                if ($idEstudiante <= 0) {
                    continue;
                }

                if ($this->existeAsignacionPeriodo($idEstudiante, $periodo)) {
                    $omitidos++;
                    continue;
                }

                $sql = "INSERT INTO asignaciones
                        (id_tutor, id_estudiante, periodo_academico, fecha_asignacion)
                        VALUES
                        (:id_tutor, :id_estudiante, :periodo, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'id_tutor' => $idTutor,
                    'id_estudiante' => $idEstudiante,
                    'periodo' => $periodo,
                ]);
                $insertados++;
            }

            $this->db->commit();

            return [
                'ok' => true,
                'msg' => "Asignación manual completada. Nuevas: {$insertados}. Omitidas: {$omitidos}.",
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['ok' => false, 'msg' => 'Error en asignación manual: ' . $e->getMessage()];
        }
    }

    public function asignarAutomatico(int $idEscuela, string $periodo, array $limitesPorTutor): array
    {
        if ($idEscuela <= 0 || $periodo === '' || empty($limitesPorTutor)) {
            return ['ok' => false, 'msg' => 'Debe seleccionar escuela, periodo y al menos un tutor con límite.'];
        }

        $estudiantes = $this->listarEstudiantesPorEscuelaYPeriodo($idEscuela, $periodo);
        if (empty($estudiantes)) {
            return ['ok' => true, 'msg' => 'No hay estudiantes pendientes para asignar en el filtro actual.'];
        }

        $colaTutores = [];
        foreach ($limitesPorTutor as $idTutor => $limite) {
            $idTutorInt = (int)$idTutor;
            $limiteInt = max(0, (int)$limite);
            if ($idTutorInt > 0 && $limiteInt > 0) {
                $colaTutores[] = ['id_tutor' => $idTutorInt, 'capacidad' => $limiteInt];
            }
        }

        if (empty($colaTutores)) {
            return ['ok' => false, 'msg' => 'Los límites por tutor no son válidos.'];
        }

        $asignados = 0;
        $sinCupo = 0;
        $idxTutor = 0;

        try {
            $this->db->beginTransaction();

            foreach ($estudiantes as $est) {
                while ($idxTutor < count($colaTutores) && $colaTutores[$idxTutor]['capacidad'] <= 0) {
                    $idxTutor++;
                }

                if ($idxTutor >= count($colaTutores)) {
                    $sinCupo++;
                    continue;
                }

                $idEstudiante = (int)($est['id_usuario'] ?? 0);
                if ($idEstudiante <= 0 || $this->existeAsignacionPeriodo($idEstudiante, $periodo)) {
                    continue;
                }

                $idTutor = (int)$colaTutores[$idxTutor]['id_tutor'];

                $sql = "INSERT INTO asignaciones
                        (id_tutor, id_estudiante, periodo_academico, fecha_asignacion)
                        VALUES
                        (:id_tutor, :id_estudiante, :periodo, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'id_tutor' => $idTutor,
                    'id_estudiante' => $idEstudiante,
                    'periodo' => $periodo,
                ]);

                $colaTutores[$idxTutor]['capacidad']--;
                if ($colaTutores[$idxTutor]['capacidad'] <= 0) {
                    $idxTutor++;
                }
                $asignados++;
            }

            $this->db->commit();

            return [
                'ok' => true,
                'msg' => "Asignación automática finalizada. Asignados: {$asignados}. Sin cupo: {$sinCupo}.",
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['ok' => false, 'msg' => 'Error en asignación automática: ' . $e->getMessage()];
        }
    }

    private function existeAsignacionPeriodo(int $idEstudiante, string $periodo): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM asignaciones WHERE id_estudiante = :id_estudiante AND periodo_academico = :periodo"
        );
        $stmt->execute([
            'id_estudiante' => $idEstudiante,
            'periodo' => $periodo,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function importarMatriculadosCSV(array $file, string $periodo): array
    {
        if ($periodo === '') {
            return ['ok' => false, 'msg' => 'Debe indicar periodo académico.', 'stats' => []];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Debe seleccionar un archivo CSV válido.', 'stats' => []];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        $name = strtolower((string)($file['name'] ?? ''));
        if (!str_ends_with($name, '.csv')) {
            return ['ok' => false, 'msg' => 'Por ahora solo se permite importación CSV.', 'stats' => []];
        }

        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            return ['ok' => false, 'msg' => 'No se pudo abrir el archivo.', 'stats' => []];
        }

        $stats = ['nuevos' => 0, 'actualizados' => 0, 'errores' => 0];

        try {
            $this->db->beginTransaction();

            $header = fgetcsv($handle, 0, ',');
            if ($header === false) {
                fclose($handle);
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'Archivo CSV vacío.', 'stats' => $stats];
            }

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                try {
                    $dni = trim((string)($row[0] ?? ''));
                    $nombres = trim((string)($row[1] ?? ''));
                    $apellidos = trim((string)($row[2] ?? ''));
                    $correo = trim((string)($row[3] ?? ''));
                    $codigoUnamba = trim((string)($row[4] ?? ''));
                    $escuelaNombre = trim((string)($row[5] ?? ''));
                    $ciclo = (int)($row[6] ?? 1);
                    $situacion = trim((string)($row[7] ?? 'Regular'));

                    if ($dni === '' || strlen($dni) !== 8 || $nombres === '' || $apellidos === '' || $correo === '' || $codigoUnamba === '' || $escuelaNombre === '') {
                        $stats['errores']++;
                        continue;
                    }

                    $idEscuela = $this->buscarIdEscuelaPorNombre($escuelaNombre);
                    if ($idEscuela <= 0) {
                        $stats['errores']++;
                        continue;
                    }

                    $idUsuario = $this->buscarUsuarioPorDni($dni);
                    if ($idUsuario > 0) {
                        if ($this->existeCorreo($correo, $idUsuario)) {
                            $stats['errores']++;
                            continue;
                        }

                        if ($this->existeCodigoUnamba($codigoUnamba, $idUsuario)) {
                            $stats['errores']++;
                            continue;
                        }

                        $sqlUpUser = "UPDATE usuarios
                                      SET nombres = :nombres, apellidos = :apellidos, correo = :correo, rol = 'estudiante', estado = 1
                                      WHERE id_usuario = :id_usuario";
                        $stmtUpUser = $this->db->prepare($sqlUpUser);
                        $stmtUpUser->execute([
                            'nombres' => $nombres,
                            'apellidos' => $apellidos,
                            'correo' => $correo,
                            'id_usuario' => $idUsuario,
                        ]);

                        $this->upsertDetalleEstudiante($idUsuario, $idEscuela, $codigoUnamba, max(1, $ciclo), $situacion);
                        $stats['actualizados']++;
                    } else {
                        if ($this->existeCorreo($correo)) {
                            $stats['errores']++;
                            continue;
                        }

                        if ($this->existeCodigoUnamba($codigoUnamba)) {
                            $stats['errores']++;
                            continue;
                        }
                        $uuid = $this->uuidv4();
                        $password = password_hash($dni, PASSWORD_DEFAULT);

                        $sqlInUser = "INSERT INTO usuarios
                                      (uuid_usuario, dni, nombres, apellidos, correo, password, rol, estado)
                                      VALUES
                                      (:uuid, :dni, :nombres, :apellidos, :correo, :password, 'estudiante', 1)";
                        $stmtInUser = $this->db->prepare($sqlInUser);
                        $stmtInUser->execute([
                            'uuid' => $uuid,
                            'dni' => $dni,
                            'nombres' => $nombres,
                            'apellidos' => $apellidos,
                            'correo' => $correo,
                            'password' => $password,
                        ]);

                        $newId = (int)$this->db->lastInsertId();
                        $this->upsertDetalleEstudiante($newId, $idEscuela, $codigoUnamba, max(1, $ciclo), $situacion);
                        $stats['nuevos']++;
                    }
                } catch (Throwable $e) {
                    $stats['errores']++;
                }
            }

            fclose($handle);
            $this->db->commit();

            return ['ok' => true, 'msg' => 'Importación completada.', 'stats' => $stats];
        } catch (Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['ok' => false, 'msg' => 'Error en importación: ' . $e->getMessage(), 'stats' => $stats];
        }
    }

    private function buscarUsuarioPorDni(string $dni): int
    {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE dni = :dni LIMIT 1");
        $stmt->execute(['dni' => $dni]);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : 0;
    }

    private function buscarIdEscuelaPorNombre(string $nombreEscuela): int
    {
        $stmt = $this->db->prepare("SELECT id_escuela FROM escuelas WHERE nombre_escuela = :nombre LIMIT 1");
        $stmt->execute(['nombre' => $nombreEscuela]);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : 0;
    }

    private function upsertDetalleEstudiante(int $idEstudiante, int $idEscuela, string $codigoUnamba, int $ciclo, string $situacion): void
    {
        $situacionNorm = in_array($situacion, ['Regular', 'Repitente', 'Riesgo'], true) ? $situacion : 'Regular';

        $stmtExiste = $this->db->prepare("SELECT id_estudiante FROM estudiantes_detalle WHERE id_estudiante = :id LIMIT 1");
        $stmtExiste->execute(['id' => $idEstudiante]);
        $existe = (bool)$stmtExiste->fetchColumn();

        if ($existe) {
            $sql = "UPDATE estudiantes_detalle
                    SET id_escuela = :id_escuela,
                        codigo_unamba = :codigo_unamba,
                        ciclo_actual = :ciclo_actual,
                        situacion_academica = :situacion
                    WHERE id_estudiante = :id_estudiante";
        } else {
            $sql = "INSERT INTO estudiantes_detalle
                    (id_estudiante, id_escuela, codigo_unamba, ciclo_actual, situacion_academica)
                    VALUES
                    (:id_estudiante, :id_escuela, :codigo_unamba, :ciclo_actual, :situacion)";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_estudiante' => $idEstudiante,
            'id_escuela' => $idEscuela,
            'codigo_unamba' => $codigoUnamba,
            'ciclo_actual' => max(1, $ciclo),
            'situacion' => $situacionNorm,
        ]);
    }

    private function existeDni(string $dni, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE dni = :dni";
        $params = ['dni' => $dni];

        if ($excludeId !== null) {
            $sql .= " AND id_usuario <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function existeCorreo(string $correo, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE correo = :correo";
        $params = ['correo' => $correo];

        if ($excludeId !== null) {
            $sql .= " AND id_usuario <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function existeCelular(string $celular, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE celular = :celular";
        $params = ['celular' => $celular];

        if ($excludeId !== null) {
            $sql .= " AND id_usuario <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function existeCodigoUnamba(string $codigoUnamba, ?int $excludeIdEstudiante = null): bool
    {
        $sql = "SELECT COUNT(*) 
                FROM estudiantes_detalle ed
                INNER JOIN usuarios u ON u.id_usuario = ed.id_estudiante
                WHERE ed.codigo_unamba = :codigo_unamba";
        $params = ['codigo_unamba' => $codigoUnamba];

        if ($excludeIdEstudiante !== null) {
            $sql .= " AND ed.id_estudiante <> :exclude_id";
            $params['exclude_id'] = $excludeIdEstudiante;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
