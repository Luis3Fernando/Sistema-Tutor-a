<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AdminAsignacion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$adminModel = new AdminAsignacion();
$escuelas = $adminModel->listarEscuelas();

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function findFullById(int $id): ?array
    {
        $sql = "SELECT
                    id_usuario,
                    uuid_usuario,
                    dni,
                    nombres,
                    apellidos,
                    sexo,
                    correo,
                    password,
                    rol,
                    celular,
                    estado,
                    ultimo_acceso,
                    created_at
                FROM usuarios
                WHERE id_usuario = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    
    public function actualizarPerfilBasico(int $id, array $data): bool
    {
        $sql = "UPDATE usuarios
                SET dni = :dni,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    correo = :correo,
                    celular = :celular
                WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'dni' => $data['dni'],
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'correo' => $data['correo'],
            'celular' => $data['celular'] !== '' ? $data['celular'] : null,
            'id' => $id,
        ]);
    }

    public function actualizarPassword(int $id, string $passwordPlano): bool
    {
        $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET password = :password WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'password' => $hash,
            'id' => $id,
        ]);
    }

    public function listarPorRoles(array $roles = ['estudiante', 'tutor','especialista']): array
    {
        if (empty($roles)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($roles) as $i => $rol) {
            $key = 'rol_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $rol;
        }

        $sql = "SELECT
                    id_usuario,
                    uuid_usuario,
                    dni,
                    nombres,
                    apellidos,
                    sexo,
                    correo,
                    rol,
                    celular,
                    estado,
                    ultimo_acceso,
                    created_at
                FROM usuarios
                WHERE rol IN (" . implode(',', $placeholders) . ")
                ORDER BY rol ASC, apellidos ASC, nombres ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listarPorRolPaginado(string $rol, int $limit, int $offset, string $q = '', ?int $estado = null): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "SELECT
                    id_usuario,
                    uuid_usuario,
                    dni,
                    nombres,
                    apellidos,
                    correo,
                    rol,
                    celular,
                    estado,
                    ultimo_acceso,
                    created_at
                FROM usuarios
                WHERE rol = :rol";
        $params = ['rol' => $rol];

        if ($q !== '') {
            $sql .= " AND (
                dni LIKE :q
                OR nombres LIKE :q
                OR apellidos LIKE :q
                OR correo LIKE :q
            )";
            $params['q'] = '%' . $q . '%';
        }

        if ($estado !== null) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY apellidos ASC, nombres ASC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarPorRol(string $rol, string $q = '', ?int $estado = null): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = :rol";
        $params = ['rol' => $rol];

        if ($q !== '') {
            $sql .= " AND (
                dni LIKE :q
                OR nombres LIKE :q
                OR apellidos LIKE :q
                OR correo LIKE :q
            )";
            $params['q'] = '%' . $q . '%';
        }

        if ($estado !== null) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $estado;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function actualizarUsuarioAdmin(int $id, array $data): array
    {
        $dni = trim((string)($data['dni'] ?? ''));
        $correo = trim((string)($data['correo'] ?? ''));
        $celular = trim((string)($data['celular'] ?? ''));

        if ($this->existeDni($dni, $id)) {
            return ['ok' => false, 'msg' => 'El DNI ya está registrado.'];
        }

        if ($this->existeCorreo($correo, $id)) {
            return ['ok' => false, 'msg' => 'El correo ya está registrado.'];
        }

        if ($celular !== '' && $this->existeCelular($celular, $id)) {
            return ['ok' => false, 'msg' => 'El celular ya está registrado.'];
        }

        $sql = "UPDATE usuarios
                SET dni = :dni,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    correo = :correo,
                    celular = :celular
                WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            'dni' => $dni,
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'correo' => $correo,
            'celular' => $celular !== '' ? $celular : null,
            'id' => $id,
        ]);

        return $ok
            ? ['ok' => true, 'msg' => 'Usuario actualizado correctamente.']
            : ['ok' => false, 'msg' => 'No se pudo actualizar el usuario.'];
    }

    public function desactivarUsuario(int $id): bool
    {
        $sql = "UPDATE usuarios SET estado = 0 WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }
     

    public function activarUsuario(int $id): bool
{
    $sql = "UPDATE usuarios SET estado = 1 WHERE id_usuario = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute(['id' => $id]);
}

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT 
                    id_usuario AS id,
                    CONCAT(nombres, ' ', apellidos) AS nombre,
                    correo AS email,
                    password,
                    rol,
                    dni AS codigo,
                    celular
                FROM usuarios 
                WHERE correo = :email AND estado = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT 
                    id_usuario AS id,
                    CONCAT(nombres, ' ', apellidos) AS nombre,
                    correo AS email,
                    rol,
                    dni AS codigo,
                    celular 

                FROM usuarios 
                WHERE id_usuario = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function crearUsuarioAdmin(array $data): array
    {

        $rol = trim((string)($data['rol'] ?? ''));
        $dni = trim((string)($data['dni'] ?? ''));
        $nombres = trim((string)($data['nombres'] ?? ''));
        $apellidos = trim((string)($data['apellidos'] ?? ''));
        $sexo = trim((string)($data['sexo'] ?? ''));
        $correo = trim((string)($data['correo'] ?? ''));
        $celular = trim((string)($data['celular'] ?? ''));
        $passwordPlano = trim((string)($data['password'] ?? ''));

        if (!in_array($rol, ['estudiante', 'tutor','especialista','administrador'], true)) {
            return ['ok' => false, 'msg' => 'Rol inválido.'];
        }
        if ($dni === '' || strlen($dni) < 8) {
            return ['ok' => false, 'msg' => 'El DNI debe tener al menos 8 caracteres.'];
        }
        if ($nombres === '' || $apellidos === '') {
            return ['ok' => false, 'msg' => 'Nombres y apellidos son obligatorios.'];
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Correo inválido.'];
        }

        if ($rol === 'estudiante') {
            if (!preg_match('/^[0-9]+@unamba\.edu\.pe$/i', $correo)) {
                return ['ok' => false, 'msg' => 'Para estudiantes, el correo debe tener formato codigo@unamba.edu.pe.'];
            }
        } elseif (in_array($rol, ['tutor', 'especialista','administrador'])) {
            if (!preg_match('/^[^@\s]+@(gmail\.com|unamba\.edu\.pe)$/i', $correo)) {
                return ['ok' => false, 'msg' => 'Para tutores, el correo debe terminar en @gmail.com o @unamba.edu.pe.'];
            }
        } 

        if ($this->existeCorreo($correo)) {
            return ['ok' => false, 'msg' => 'El correo ya está registrado.'];
        }

        if ($this->existeDni($dni)) {
            return ['ok' => false, 'msg' => 'El DNI ya está registrado.'];
        }

        if ($celular !== '' && $this->existeCelular($celular)) {
            return ['ok' => false, 'msg' => 'El celular ya está registrado.'];
        }

        if ($passwordPlano === '') {
            $passwordPlano = $dni;
        }

        $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);

        $uuid = $this->uuidv4();

        $sql = "INSERT INTO usuarios (uuid_usuario, dni, nombres, apellidos,sexo, correo, password, rol, celular, estado)
                VALUES (:uuid_usuario, :dni, :nombres, :apellidos,:sexo, :correo, :password, :rol, :celular, 1)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'uuid_usuario' => $uuid,
            'dni' => $dni,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'sexo'=>$sexo,
            'correo' => $correo,
            'password' => $hash,
            'rol' => $rol,
            'celular' => $celular !== '' ? $celular : null,
        ]);

        if (!$ok) {
            return ['ok' => false, 'msg' => 'No se pudo crear el usuario.'];
        }

        $idUsuario = (int)$this->db->lastInsertId();

        return ['ok' => true, 'msg' => 'Usuario creado correctamente.', 'id_usuario' => $idUsuario];
    }

    public function importarUsuariosDesdeCSV(array $file): array
    {
        $name = strtolower((string)($file['name'] ?? ''));
        $tmp = (string)($file['tmp_name'] ?? '');
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        $stats = ['nuevos' => 0, 'errores' => 0];

        if ($error !== UPLOAD_ERR_OK || $tmp === '') {
            return ['ok' => false, 'msg' => 'Archivo inválido.', 'stats' => $stats];
        }

        if (str_ends_with($name, '.xlsx') || str_ends_with($name, '.xls')) {
            return ['ok' => false, 'msg' => 'Formato Excel aún no soportado. Use CSV.', 'stats' => $stats];
        }

        if (!str_ends_with($name, '.csv')) {
            return ['ok' => false, 'msg' => 'Solo se permite CSV por ahora.', 'stats' => $stats];
        }

        $handle = fopen($tmp, 'r');
        if ($handle === false) {
            return ['ok' => false, 'msg' => 'No se pudo leer el archivo.', 'stats' => $stats];
        }

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            fclose($handle);
            return ['ok' => false, 'msg' => 'CSV vacío.', 'stats' => $stats];
        }

        $map = array_map(static fn($v) => strtolower(trim((string)$v)), $header);
        $idx = [
            'rol' => array_search('rol', $map, true),
            'dni' => array_search('dni', $map, true),
            'nombres' => array_search('nombres', $map, true),
            'apellidos' => array_search('apellidos', $map, true),
            'correo' => array_search('correo', $map, true),
            'celular' => array_search('celular', $map, true),
            'password' => array_search('password', $map, true),
        ];

        if ($idx['rol'] === false || $idx['dni'] === false || $idx['nombres'] === false || $idx['apellidos'] === false || $idx['correo'] === false) {
            fclose($handle);
            return ['ok' => false, 'msg' => 'Cabeceras requeridas: rol,dni,nombres,apellidos,correo (celular,password opcionales).', 'stats' => $stats];
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $data = [
                'rol' => (string)($row[(int)$idx['rol']] ?? ''),
                'dni' => (string)($row[(int)$idx['dni']] ?? ''),
                'nombres' => (string)($row[(int)$idx['nombres']] ?? ''),
                'apellidos' => (string)($row[(int)$idx['apellidos']] ?? ''),
                'correo' => (string)($row[(int)$idx['correo']] ?? ''),
                'celular' => $idx['celular'] !== false ? (string)($row[(int)$idx['celular']] ?? '') : '',
                'password' => $idx['password'] !== false ? (string)($row[(int)$idx['password']] ?? '') : '',
            ];

            $res = $this->crearUsuarioAdmin($data);
            if ($res['ok'] ?? false) {
                $stats['nuevos']++;
            } else {
                $stats['errores']++;
            }
        }

        fclose($handle);

        return ['ok' => true, 'msg' => 'Importación finalizada.', 'stats' => $stats];
    }

   public function importarTodoDesdeExcel(array $file): array
{
    $stats = ['escuelas' => 0, 'usuarios' => 0, 'errores' => 0, 'asignaciones' => 0];
    $logs = []; // Aquí guardaremos qué pasó en cada fila
    $tmp = (string)($file['tmp_name'] ?? '');

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);

        // --- 1. PROCESAR ESCUELAS ---
        $sheetEsc = $spreadsheet->getSheetByName('escuelas');
        if ($sheetEsc) {
            $rows = $sheetEsc->toArray();
            unset($rows[0]);
            foreach ($rows as $row) {
                if (empty($row[1])) continue;
                $nombreEsc = trim((string)$row[1]);
                $facultad = trim((string)($row[2] ?? ''));

                $check = $this->db->prepare("SELECT id_escuela FROM escuelas WHERE LOWER(nombre_escuela) = LOWER(?)");
                $check->execute([$nombreEsc]);
                if (!$check->fetch()) {
                    $this->db->prepare("INSERT INTO escuelas (nombre_escuela, facultad) VALUES (?, ?)")
                             ->execute([$nombreEsc, $facultad]);
                    $stats['escuelas']++;
                }
            }
        }

        // --- 2. PROCESAR USUARIOS (Estudiante, Tutor, Especialista) ---
        $roles = ['estudiante', 'tutor', 'especialista'];
        foreach ($roles as $rol) {
            $sheet = $spreadsheet->getSheetByName($rol);
            if (!$sheet) continue;

            $rows = $sheet->toArray();
            unset($rows[0]);

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // DNI vacío
                
                $dni = trim((string)$row[0]);
                $nombreEscuela = trim((string)($row[7] ?? ''));
                $idEscuela = $this->obtenerIdEscuelaPorNombre($nombreEscuela);

                if (!$idEscuela) {
                    $stats['errores']++;
                    $logs[] = "ERROR [$rol DNI $dni]: La escuela '$nombreEscuela' no existe.";
                    continue;
                }
               
                 // PASSWORD
    $passwordPlano = trim((string)($row[6] ?? ''));

    if ($passwordPlano === '') {
        $passwordPlano = $dni;
    }

                // Intentar crear el usuario
                $res = $this->crearUsuarioAdmin([
                    'rol'       => $rol,
                    'dni'       => $dni,
                    'nombres'   => trim((string)($row[1] ?? '')),
                    'apellidos' => trim((string)($row[2] ?? '')),
                    'sexo'      => trim((string)($row[3] ?? '')),
                    'correo'    => trim((string)($row[4] ?? '')),
                    'celular'   => trim((string)($row[5] ?? '')),
                    'password'  => $passwordPlano,
                ]);

                

                if ($res['ok']) {
                    $idUser = (int)$res['id_usuario'];
                    // Guardar Detalles según Rol (Ajuste de índices según tus capturas)
                    if ($rol === 'estudiante') {
                        $this->guardarDetalleEstudiante($idUser, $idEscuela, 
                            trim((string)$row[8]),  // codigo_unamba (Col I)
                            (int)($row[9] ?? 1),    // ciclo_actual (Col J)
                           $this->formatearFechaExcel($row[10] ?? ''), // fecha_nacimiento (Col K)
                            trim((string)$row[11]), // semestre_ingreso (Col L)
                            trim((string)($row[12] ?: 'Regular')) // situacion (Col M)
                        );
                    } elseif ($rol === 'tutor') {
                        $this->guardarDetalleTutor($idUser, $idEscuela, 
                            trim((string)$row[8]),  // grado (Col I)
                            trim((string)$row[9]),  // especialidad (Col J)
                            trim((string)($row[10] ?: 'Nombrado')) // categoria (Col K)
                        );
                    } elseif ($rol === 'especialista') {
                        $this->guardarDetalleEspecialista($idUser, $idEscuela, 
                            trim((string)$row[8]),  // area (Col I)
                            trim((string)$row[9])   // cargo (Col J)
                        );
                    }
                    $stats['usuarios']++;
                } else {
                    $stats['errores']++;
                    $logs[] = "ERROR [$rol DNI $dni]: " . ($res['msg'] ?? 'DNI o Correo ya existen.');
                }
            }
        }

            // --- 3. PROCESAR ASIGNACIONES ---
$sheetAsig = $spreadsheet->getSheetByName('asignaciones');

if ($sheetAsig) {
    $rows = $sheetAsig->toArray();
    unset($rows[0]);

    foreach ($rows as $row) {
        $dniTutor = preg_replace('/[^0-9]/', '', (string)($row[0] ?? ''));
        $dniEstudiante = preg_replace('/[^0-9]/', '', (string)($row[1] ?? ''));
        $periodo = trim((string)($row[2] ?? ''));

        if ($dniTutor === '' || $dniEstudiante === '') continue;

        $idT = $this->obtenerIdUsuarioPorDni($dniTutor);
        $idE = $this->obtenerIdUsuarioPorDni($dniEstudiante);

        if ($idT && $idE) {
            // VERIFICACIÓN SENIOR: ¿Realmente este ID está registrado como TUTOR?
            $checkTutor = $this->db->prepare("SELECT 1 FROM tutor_detalles WHERE id_tutor = ?");
            $checkTutor->execute([$idT]);
            $esTutorReal = $checkTutor->fetch();

            // VERIFICACIÓN SENIOR: ¿Realmente este ID está registrado como ESTUDIANTE?
            $checkEst = $this->db->prepare("SELECT 1 FROM estudiantes_detalle WHERE id_estudiante = ?");
            $checkEst->execute([$idE]);
            $esEstudianteReal = $checkEst->fetch();

            if ($esTutorReal && $esEstudianteReal) {
                // Solo si ambos existen en sus tablas de detalles, insertamos
                try {
                    $sql = "INSERT INTO asignaciones (id_tutor, id_estudiante, periodo_academico, fecha_asignacion)
                            VALUES (?, ?, ?, NOW())";
                    $this->db->prepare($sql)->execute([$idT, $idE, $periodo]);
                    $stats['asignaciones']++;
                } catch (\Exception $e) {
                    $stats['errores']++;
                    $logs[] = "Fallo SQL en asignación Tutor $dniTutor - Estudiante $dniEstudiante: " . $e->getMessage();
                }
            } else {
                $stats['errores']++;
                if (!$esTutorReal) {
                    $logs[] = "ERROR: El usuario con DNI $dniTutor existe pero NO está registrado como TUTOR (Falta en tutor_detalles).";
                }
                if (!$esEstudianteReal) {
                    $logs[] = "ERROR: El usuario con DNI $dniEstudiante existe pero NO está registrado como ESTUDIANTE (Falta en estudiantes_detalle).";
                }
            }
        } else {
            $stats['errores']++;
            if (!$idT) $logs[] = "DNI Tutor no encontrado en el sistema: $dniTutor";
            if (!$idE) $logs[] = "DNI Estudiante no encontrado en el sistema: $dniEstudiante";
        }
    }
}

        $mensaje = "Importación finalizada. Nuevos: {$stats['usuarios']} | Errores: {$stats['errores']}";
        if (!empty($logs)) {
            $mensaje .= " | DETALLE: " . implode(" / ", $logs);
        }

        return ['ok' => true, 'msg' => $mensaje, 'stats' => $stats];

    } catch (\Exception $e) {
        return ['ok' => false, 'msg' => 'Error Crítico: ' . $e->getMessage(), 'stats' => $stats];
    }
}
// Función auxiliar para no repetir código de cabeceras
private function obtenerFilasSinCabecera($sheet): array {
    $rows = $sheet->toArray();
    unset($rows[0]);
    return $rows;
}

// Mejora esta función para que no falle por mayúsculas/minúsculas
public function obtenerIdEscuelaPorNombre(string $nombre): ?int
{
    $sql = "SELECT id_escuela FROM escuelas WHERE LOWER(nombre_escuela) = LOWER(:nombre) LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':nombre' => trim($nombre)]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

public function obtenerIdUsuarioPorDni(string $dni): ?int
{
    $sql = "SELECT id_usuario
            FROM usuarios
            WHERE dni = ? 
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$dni]);

    $id = $stmt->fetchColumn();

    return $id ? (int)$id : null;
}
private function formatearFechaExcel($fecha): string
{
    if (empty($fecha)) {
        return '';
    }

    /*
    =========================================================
    SI EXCEL ENVÍA FECHA NUMÉRICA
    =========================================================
    */

    if (is_numeric($fecha)) {

        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha)
            ->format('Y-m-d');
    }

    /*
    =========================================================
    SI VIENE COMO TEXTO: 26/12/2000
    =========================================================
    */

    $fecha = trim((string)$fecha);

    $formatos = [
        'd/m/Y',
        'd-m-Y',
        'Y-m-d'
    ];

    foreach ($formatos as $formato) {

        $date = \DateTime::createFromFormat($formato, $fecha);

        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }

    return '';
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

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


 public function guardarDetalleEstudiante(
    int $id_usuario,
    int $id_escuela,
    string $codigo,
    int $ciclo,
    string $nacimiento,
    string $ingreso,

    string $situacionAcademica = 'ACTIVO'
    ): bool {
    $sql = "INSERT INTO estudiantes_detalle (id_estudiante, id_usuario, id_escuela, codigo_unamba, ciclo_actual, fecha_nacimiento,semestre_ingreso, situacion_academica)
            VALUES (:id_estudiante, :id_usuario, :id_escuela, :codigo_unamba, :ciclo_actual, :fecha_nacimiento,:semestre_ingreso, :situacion_academica)
            ON DUPLICATE KEY UPDATE
                id_usuario = VALUES(id_usuario),
                id_escuela = VALUES(id_escuela),
                codigo_unamba = VALUES(codigo_unamba),
                ciclo_actual = VALUES(ciclo_actual),
                fecha_nacimiento = VALUES(fecha_nacimiento),
                semestre_ingreso = VALUES(semestre_ingreso),
                situacion_academica = VALUES(situacion_academica)";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':id_estudiante' => $id_usuario,
        ':id_usuario' => $id_usuario,
        ':id_escuela' => $id_escuela,
        ':codigo_unamba' => $codigo,
        ':ciclo_actual' => max(1, $ciclo),
        ':fecha_nacimiento'=>$nacimiento,
        ':semestre_ingreso'=>$ingreso,
        ':situacion_academica' => $situacionAcademica !== '' ? strtoupper($situacionAcademica) : 'ACTIVO'
    ]);
}

    public function guardarDetalleTutor(int $id_usuario, int $id_escuela, string $grado, string $especialidad, string $categoria): bool {
    $sql = "INSERT INTO tutor_detalles (id_tutor, id_usuario, id_escuela, grado_academico, especialidad, categoria)
            VALUES (:id_tutor, :id_usuario, :id_escuela, :grado, :especialidad, :categoria)
            ON DUPLICATE KEY UPDATE
                id_usuario = VALUES(id_usuario),
                id_escuela = VALUES(id_escuela),
                grado_academico = VALUES(grado_academico),
                especialidad = VALUES(especialidad),
                categoria = VALUES(categoria)";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':id_tutor' => $id_usuario,
        ':id_usuario' => $id_usuario,
        ':id_escuela' => $id_escuela,
        ':grado' => $grado,
        ':especialidad' => $especialidad,
        ':categoria' => $categoria
    ]);
}

public function guardarDetalleEspecialista(
    int $id_usuario,
    int $id_escuela,
    string $area,
    string $cargo
): bool {

    try {

        $sql = "INSERT INTO detalles_especialista
                (
                    id_especialista,
                    id_usuario,
                    id_escuela,
                    area,
                    cargo
                )
                VALUES
                (
                    :id_especialista,
                    :id_usuario,
                    :id_escuela,
                    :area,
                    :cargo
                )";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':id_especialista' => $id_usuario,
            ':id_usuario' => $id_usuario,
            ':id_escuela' => $id_escuela,
            ':area' => $area,
            ':cargo' => $cargo
        ]);

        if (!$ok) {
            return false;
        }

        return true;

    } catch (PDOException $e) {
        return false;
    }
}

public function guardarDetallesAdmin(int $id_usuario,string $cargo, string $dependencia): bool {
try {

        $sql = "INSERT INTO admin_detalles
                (
                    id_admin,
                    id_usuario,
                    cargo,
                    dependencia	
                )
                VALUES
                (
                    :id_admin,
                    :id_usuario,
                    :cargo,
                    :dependencia
                )";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':id_admin' => $id_usuario,
            ':id_usuario' => $id_usuario,
            ':cargo' => $cargo,
            ':dependencia' =>$dependencia
        ]);

        if (!$ok) {
            return false;
        }

        return true;

    } catch (PDOException $e) {
        return false;
    }

}

public function actualizarUltimoAcceso(int $id_usuario): bool
{
    $sql = "UPDATE usuarios
            SET ultimo_acceso = NOW()
            WHERE id_usuario = ?";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([$id_usuario]);
}
}
