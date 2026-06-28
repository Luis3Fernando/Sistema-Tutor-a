<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Derivacion {

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    

    // ✅ Registrar atención del especialista
    public function registrarAtencion(
        int $id_derivacion,
        int $id_especialista,
        string $acciones
    ): bool {

        
           $sql = "UPDATE derivaciones SET
                acciones_realizadas = :acciones,
                id_especialista = :id_esp,
                estado_atencion = 'Cerrado', -- <--- CAMBIA 'Atendido' por 'Cerrado'
                updated_at = NOW()
            WHERE id_derivacion = :id";
        

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'acciones' => $acciones,
            'id_esp'   => $id_especialista,
            'id' => $id_derivacion
        ]);
    }

 

public function registrarCita($data, int $idEspecialista)
{
    $this->pdo->beginTransaction();

    try {

        // 1️⃣ Registrar cita
        $sql = "INSERT INTO citas_especialista 
                (id_derivacion, fecha_cita, hora_cita, modalidad, estado)
                VALUES (?, ?, ?, ?, 'Programada')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['id_derivacion'],
            $data['fecha_cita'],
            $data['hora_cita'],
            $data['modalidad']
        ]);

        // 2️⃣ Asignar especialista al caso
        $update = $this->pdo->prepare("
            UPDATE derivaciones
            SET id_especialista = ?
            WHERE id_derivacion = ?
        ");

        $update->execute([
            $idEspecialista,
            $data['id_derivacion']
        ]);

        $this->pdo->commit();
        return true;

    } catch(Exception $e){
        $this->pdo->rollBack();
        return false;
    }
}

public function listarDerivacionesPorArea(string $area)
{
    $sql = "SELECT d.*, 
                   u_t.nombres as tutor_nombre,
                   u_t.apellidos as tutor_apellido,
                   u_e.nombres as est_nombre,
                   u_e.apellidos as est_apellido,
                   est.codigo_unamba,
                   esc.nombre_escuela,
                   est.ciclo_actual
            FROM derivaciones d
            JOIN usuarios u_t ON d.id_tutor = u_t.id_usuario
            JOIN usuarios u_e ON d.id_estudiante = u_e.id_usuario
            JOIN estudiantes_detalle est ON u_e.id_usuario = est.id_usuario
            JOIN escuelas esc ON est.id_escuela = esc.id_escuela
            WHERE d.area_destino = :area
            ORDER BY d.fecha_derivacion DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['area' => $area]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function derivacionesPendientesPorArea(string $area, int $idEspecialista): array
{
    $sql = "
        SELECT 
            d.id_derivacion,
            d.fecha_derivacion,
            d.motivo_informe,
            d.resumen_caso,
            d.estado_atencion,
            u_t.nombres AS tutor_nombre,
            u_t.apellidos AS tutor_apellido,
            u_e.nombres AS est_nombre,
            u_e.apellidos AS est_apellido,
            est.codigo_unamba,
            est.ciclo_actual,
            esc.nombre_escuela
        FROM derivaciones d
        JOIN usuarios u_t ON d.id_tutor = u_t.id_usuario
        JOIN usuarios u_e ON d.id_estudiante = u_e.id_usuario
        LEFT JOIN estudiantes_detalle est ON est.id_usuario = u_e.id_usuario
        LEFT JOIN escuelas esc ON est.id_escuela = esc.id_escuela
        WHERE d.area_destino = :area
        AND (d.id_especialista IS NULL OR d.id_especialista = :id_esp)
        AND d.estado_atencion != 'Cerrado'
        ORDER BY d.fecha_derivacion DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'area' => $area,
        'id_esp' => $idEspecialista
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerAgendaHoy(int $idEspecialista): array 
{
    $sql = "SELECT 
                c.fecha_cita,
                c.hora_cita,
                c.modalidad,
                CONCAT(u.apellidos, ' ', u.nombres) AS estudiante,
                esc.nombre_escuela
            FROM citas_especialista c
            INNER JOIN derivaciones d ON c.id_derivacion = d.id_derivacion
            INNER JOIN usuarios u ON d.id_estudiante = u.id_usuario
            LEFT JOIN estudiantes_detalle est ON u.id_usuario = est.id_usuario
            LEFT JOIN escuelas esc ON est.id_escuela = esc.id_escuela
            WHERE d.id_especialista = :id_esp 
              AND c.fecha_cita >= CURDATE() -- Trae lo de hoy y lo que viene
              AND c.estado = 'Programada'
            ORDER BY c.fecha_cita ASC, c.hora_cita ASC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_esp' => $idEspecialista]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerEstadisticasEspecialista(int $idEspecialista): array
{
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_atencion = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado_atencion = 'Cerrado' THEN 1 ELSE 0 END) as finalizados
            FROM derivaciones 
            WHERE id_especialista = :id";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
        'id' => $idEspecialista
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total'=>0,
        'pendientes'=>0,
        'finalizados'=>0
    ];
}
public function listarPendientesPorEstudiante(int $idEstudiante): array
{
    $sql = "SELECT d.*,
                   u_t.nombres AS tutor_nombre,
                   u_t.apellidos AS tutor_apellido
            FROM derivaciones d
            JOIN usuarios u_t ON d.id_tutor = u_t.id_usuario
            WHERE d.id_estudiante = :id
              AND d.estado_atencion != 'Cerrado'
            ORDER BY d.fecha_derivacion DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $idEstudiante]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Dentro de la clase Derivacion en app/models/Derivacion.php

 public function listarConCitasPorEstudiante(int $idUsuario): array
    {
        $sql = "SELECT d.*
                FROM derivaciones d
                INNER JOIN estudiantes_detalle ed 
                    ON ed.id_estudiante = d.id_estudiante
                WHERE ed.id_usuario = :id
                ORDER BY d.fecha_derivacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idUsuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}