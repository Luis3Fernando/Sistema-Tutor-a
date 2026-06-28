<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/RoleMiddleware.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../models/Usuario.php';


class PerfilController
{
    public function misDatos(): void
    {
        RoleMiddleware::requireRole(['estudiante', 'tutor', 'administrador','especialista']);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $authUser = Auth::user();
        $usuarioModel = new Usuario();

        
        $userDb = $usuarioModel->findFullById((int)($authUser['id'] ?? 0));
        
        $user = is_array($userDb) ? $userDb : $authUser;
        $rol = (string)($user['rol'] ?? $authUser['rol'] ?? '');

        $errores = [];
        $ok = false;


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dni = trim((string)($_POST['dni'] ?? ''));
            $nombres = trim((string)($_POST['nombres'] ?? ''));
            $apellidos = trim((string)($_POST['apellidos'] ?? ''));
            $correo = trim((string)($_POST['correo'] ?? ''));
            $celular = trim((string)($_POST['celular'] ?? ''));
            $passwordNueva = (string)($_POST['password_nueva'] ?? '');
            $passwordConfirmar = (string)($_POST['password_confirmar'] ?? '');

            if ($dni === '' || strlen($dni) < 8) {
                $errores[] = 'El DNI es obligatorio y debe tener al menos 8 caracteres.';
            }
            if ($nombres === '') {
                $errores[] = 'Los nombres son obligatorios.';
            }
            if ($apellidos === '') {
                $errores[] = 'Los apellidos son obligatorios.';
            }
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El correo no tiene un formato válido.';
            }
            if ($passwordNueva !== '' && strlen($passwordNueva) < 6) {
                $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            }
            if ($passwordNueva !== '' && $passwordNueva !== $passwordConfirmar) {
                $errores[] = 'La confirmación de contraseña no coincide.';
            }

            if (empty($errores)) {
                if ($rol === 'estudiante') {

                     
                    $codigo = trim($_POST['codigo_unamba'] ?? '');
                    $ciclo = (int)($_POST['ciclo_actual'] ?? 0);
                    $escuela = (int)($_POST['id_escuela'] ?? 0); //  nombre correcto

                    if ($codigo === '' || $ciclo <= 0) {
                        $errores[] = 'Completa los datos académicos del estudiante.';
                    } else {
                        $usuarioModel->guardarDetalleEstudiante((int)$user['id_usuario'], $escuela, $codigo, $ciclo);
                    }
                }

                    if ($rol === 'tutor') {
                        $escuela = (int)($_POST['id_escuela'] ?? 0); // mismo cambio
                        $grado = trim($_POST['grado_academico'] ?? '');
                        $especialidad = trim($_POST['especialidad'] ?? '');

                        if ($grado === '') {
                            $errores[] = 'Completa los datos del tutor.';
                        } else {
                            $usuarioModel->guardarDetalleTutor((int)$user['id_usuario'], $escuela, $grado, $especialidad, $_POST['categoria'] ?? '');
                        }
                    } 

                    //  NUEVA LÓGICA PARA EL ESPECIALISTA
                   if ($rol === 'especialista') {

                            $escuela = (int)($_POST['id_escuela'] ?? 0);
                            $area = trim($_POST['area'] ?? ''); // Ej: Psicopedagogía, Salud
                            $cargo = trim($_POST['cargo'] ?? ''); // Ej: Psicólogo, Médico
                            
                            if ($area === '') {
                                $errores[] = 'El área de especialidad es obligatoria.';
                            } else {
                                
                                $usuarioModel->guardarDetalleEspecialista((int)$user['id_usuario'], $escuela, $area, $cargo);
                            }
                        }

                $actualizado = $usuarioModel->actualizarPerfilBasico((int)$user['id_usuario'], [
                    'dni' => $dni,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'correo' => $correo,
                    'celular' => $celular,
                ]);

                if ($actualizado && $passwordNueva !== '') {
                    $actualizado = $usuarioModel->actualizarPassword((int)$user['id_usuario'], $passwordNueva);
                }

                if ($actualizado) {
                    $refrescado = $usuarioModel->findById((int)$user['id_usuario']);
                    if (!empty($refrescado)) {
                        Auth::login($refrescado);
                    }
                    $_SESSION['flash_mis_datos_ok'] = 'Datos actualizados correctamente.';
                    header('Location: index.php?route=perfil/mis-datos');
                    exit;
                }

                $errores[] = 'No se pudo actualizar la información en este momento.';
            }

        }

        if (!empty($_SESSION['flash_mis_datos_ok'])) {
            $ok = true;
            unset($_SESSION['flash_mis_datos_ok']);
        }

        require __DIR__ . '/../../views/perfil/mis_datos.php';
    }
}