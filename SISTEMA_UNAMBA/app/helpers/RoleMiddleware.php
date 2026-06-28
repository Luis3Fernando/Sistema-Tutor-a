<?php
declare(strict_types=1);

require_once __DIR__ . '/Auth.php';

class RoleMiddleware
{
    public static function requireRole(array $roles): void
    {
        Auth::requireAuth();

        $role = Auth::role();
        if ($role === null || !in_array($role, $roles, true)) {
            http_response_code(403);
            echo 'Acceso denegado: no tienes permisos para acceder a este recurso.';
            exit;
        }
    }
}
