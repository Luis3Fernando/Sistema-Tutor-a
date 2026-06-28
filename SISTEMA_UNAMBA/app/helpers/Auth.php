<?php
declare(strict_types=1);

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        self::startSession();
        $_SESSION['user'] = [
            'id'       => $user['id'] ?? null,
            'nombre'   => $user['nombre'] ?? '',
            'email'    => $user['email'] ?? '',
            'rol'      => $user['rol'] ?? 'alumno',
            'codigo'   => $user['codigo'] ?? '',
            'celular'=>$user['celular'] ?? '',
            
            'nombre_escuela' => $user['nombre_escuela'] ?? 'No asignada',     
        ];
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        self::startSession();

    if (isset($_SESSION['user']['id'])) {

        $db = db();

        $sql = "UPDATE usuarios
                SET ultimo_acceso = NOW()
                WHERE id_usuario = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$_SESSION['user']['id']]);
    }

        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['rol'] ?? null;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }
    }
   
}
