<?php
require_once __DIR__ . '/../config/database.php';  // Tu función db()
require_once __DIR__ . '/../models/Usuario.php';   // Tu clase Usuario

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();  // Usa tu clase existente
    }

    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validaciones básicas
            if (empty($email) || empty($password)) {
                $error = "Email y contraseña son requeridos";
                header('Location: index.php?route=auth/login&error=' . urlencode($error));
                exit;
            }

            // Buscar usuario CON TU MODELO
            $user = $this->usuarioModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {

             // actualizar último acceso
                $this->usuarioModel->actualizarUltimoAcceso(
                    (int)$user['id_usuario']
                );
                // Iniciar sesión - usa 'rol' que viene de tu modelo
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['rol'];  //  'rol' no 'role'
                $_SESSION['user_nombre'] = $user['nombre'] ?? '';
                

                // Redirigir según rol (usa tu campo 'rol')
                switch ($user['rol']) {
                    case 'admin':
                        header('Location: index.php?route=admin/dashboard');
                        break;
                    case 'tutor':
                        header('Location: index.php?route=tutor/dashboard');
                        break;
                    case 'especialista':
                        header('Location: index.php?route=especialista/dashboard');
                        break;
                    default:
                        header('Location: index.php?route=estudiante/dashboard');
                        break;
                }
                exit;
            } else {
                $error = "Credenciales inválidas";
                header('Location: index.php?route=auth/login&error=' . urlencode($error));
                exit;
            }
        }
    }

    public function login() {
        session_start();
        // Si ya está logueado, redirigir
        if (isset($_SESSION['user_id'])) {
            $rol = $_SESSION['user_rol'] ?? 'estudiante';
            header("Location: index.php?route={$rol}/dashboard");
            exit;
        }
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function logout() {
        session_start();
        session_destroy();
        header('Location: index.php?route=auth/login');
        exit;
    }
}