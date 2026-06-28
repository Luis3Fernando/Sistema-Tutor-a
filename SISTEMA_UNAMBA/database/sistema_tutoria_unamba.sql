-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-06-2026 a las 17:24:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_tutoria_unamba`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin_detalles`
--

CREATE TABLE `admin_detalles` (
  `id_admin` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `dependencia` varchar(100) DEFAULT 'Dirección de Bienestar Universitario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `periodo_academico` varchar(10) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas_especialista`
--

CREATE TABLE `citas_especialista` (
  `id_cita` int(11) NOT NULL,
  `id_derivacion` int(11) NOT NULL,
  `fecha_cita` date DEFAULT NULL,
  `hora_cita` time DEFAULT NULL,
  `modalidad` varchar(50) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Programada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `derivaciones`
--

CREATE TABLE `derivaciones` (
  `id_derivacion` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_sesion` int(11) DEFAULT NULL,
  `id_especialista` int(11) DEFAULT NULL,
  `fecha_derivacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `area_destino` varchar(100) DEFAULT NULL,
  `motivo_informe` text DEFAULT NULL,
  `resumen_caso` text DEFAULT NULL,
  `acciones_realizadas` text DEFAULT NULL,
  `estado_atencion` enum('Pendiente','En Proceso','Cerrado','Rechazado') DEFAULT 'Pendiente',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_especialista`
--

CREATE TABLE `detalles_especialista` (
  `id_especialista` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_escuela` int(11) DEFAULT NULL,
  `area` varchar(50) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diagnostico_inicial`
--

CREATE TABLE `diagnostico_inicial` (
  `id_diagnostico` int(11) NOT NULL,
  `id_sesion` int(11) DEFAULT NULL,
  `id_estudiante` int(11) NOT NULL,
  `p_entorno_uni` text DEFAULT NULL,
  `p_apoyo_social` text DEFAULT NULL,
  `p_manejo_estres` text DEFAULT NULL,
  `p_integracion` text DEFAULT NULL,
  `s_alimentacion_sueno` text DEFAULT NULL,
  `s_ejercicio` text DEFAULT NULL,
  `s_concentracion` text DEFAULT NULL,
  `s_ansiedad_estres` text DEFAULT NULL,
  `s_manejo_emocional` text DEFAULT NULL,
  `s_consumo_sustancias` text DEFAULT NULL,
  `s_riesgos_sustancias` text DEFAULT NULL,
  `a_rendimiento` text DEFAULT NULL,
  `a_dificultad_curso` text DEFAULT NULL,
  `a_tecnicas_estudio` text DEFAULT NULL,
  `a_asistencia` text DEFAULT NULL,
  `a_organizacion_tiempo` text DEFAULT NULL,
  `a_apoyo_academico` text DEFAULT NULL,
  `v_carrera_adecuada` text DEFAULT NULL,
  `v_metas` text DEFAULT NULL,
  `v_actividades_refuerzo` text DEFAULT NULL,
  `v_dificultades` text DEFAULT NULL,
  `fecha_actividad` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escuelas`
--

CREATE TABLE `escuelas` (
  `id_escuela` int(11) NOT NULL,
  `nombre_escuela` varchar(100) NOT NULL,
  `facultad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes_detalle`
--

CREATE TABLE `estudiantes_detalle` (
  `id_estudiante` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_escuela` int(11) NOT NULL,
  `codigo_unamba` varchar(15) NOT NULL,
  `ciclo_actual` int(11) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `semestre_ingreso` varchar(10) DEFAULT NULL,
  `situacion_academica` enum('Regular','Repitente','Riesgo') DEFAULT 'Regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_actividades_cronograma`
--

CREATE TABLE `plan_actividades_cronograma` (
  `id_actividad` int(11) NOT NULL,
  `id_plan` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `actividad_tipo` enum('Control grupal','Diagnostico individual','Seguimiento individual','Referencia tutoría') NOT NULL,
  `instrumento` varchar(255) DEFAULT NULL,
  `objetivo_especifico` text DEFAULT NULL,
  `estado` enum('Programada','Realizado','Pendiente') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_trabajo_tutorial`
--

CREATE TABLE `plan_trabajo_tutorial` (
  `id_plan` int(11) NOT NULL,
  `id_tutor` int(11) NOT NULL,
  `periodo_academico` varchar(10) NOT NULL,
  `objetivo_general` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_individual`
--

CREATE TABLE `seguimiento_individual` (
  `id_seguimiento` int(11) NOT NULL,
  `id_sesion` int(11) DEFAULT NULL,
  `id_estudiante` int(11) DEFAULT NULL,
  `id_tutor` int(11) DEFAULT NULL,
  `mejoras_respecto_inicio` text DEFAULT NULL,
  `seguimiento_personal_social` text DEFAULT NULL,
  `seguimiento_salud_mental` text DEFAULT NULL,
  `seguimiento_academico` text DEFAULT NULL,
  `seguimiento_vocacional` text DEFAULT NULL,
  `acciones_acuerdos` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `observaciones_generales` text DEFAULT NULL,
  `proxima_cita` date DEFAULT NULL,
  `nivel_personal_social` tinyint(1) DEFAULT NULL,
  `nivel_salud_mental` tinyint(1) DEFAULT NULL,
  `nivel_academico` tinyint(1) DEFAULT NULL,
  `nivel_vocacional` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_tutoria`
--

CREATE TABLE `sesiones_tutoria` (
  `id_sesion` int(11) NOT NULL,
  `id_asignacion` int(11) DEFAULT NULL,
  `id_tutor` int(11) DEFAULT NULL,
  `id_actividad` int(11) DEFAULT NULL,
  `objetivo_sesion` text DEFAULT NULL,
  `fecha_ejecucion` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `archivo_evidencia` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_asistencia`
--

CREATE TABLE `sesion_asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `id_sesion` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `asistencia` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivo_estudiante` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutor_detalles`
--

CREATE TABLE `tutor_detalles` (
  `id_tutor` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_escuela` int(11) NOT NULL,
  `grado_academico` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'Nombrado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `uuid_usuario` char(36) NOT NULL,
  `dni` varchar(8) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `sexo` enum('Masculino','Femenino','Otro') DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','tutor','estudiante','especialista') NOT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `uuid_usuario`, `dni`, `nombres`, `apellidos`, `sexo`, `correo`, `password`, `rol`, `celular`, `estado`, `ultimo_acceso`, `created_at`) VALUES
(1, '', '71742908', 'herminea', 'garay roman', NULL, '201226@unamba.edu.pe', 'admin12345', 'administrador', '987654321', 1, '2026-06-28 10:23:48', '2026-06-28 15:21:28');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin_detalles`
--
ALTER TABLE `admin_detalles`
  ADD PRIMARY KEY (`id_admin`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD UNIQUE KEY `idx_asignacion_unica` (`id_tutor`,`id_estudiante`,`periodo_academico`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `citas_especialista`
--
ALTER TABLE `citas_especialista`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `id_derivacion` (`id_derivacion`);

--
-- Indices de la tabla `derivaciones`
--
ALTER TABLE `derivaciones`
  ADD PRIMARY KEY (`id_derivacion`),
  ADD KEY `id_tutor` (`id_tutor`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_sesion` (`id_sesion`);

--
-- Indices de la tabla `detalles_especialista`
--
ALTER TABLE `detalles_especialista`
  ADD PRIMARY KEY (`id_especialista`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_escuela` (`id_escuela`);

--
-- Indices de la tabla `diagnostico_inicial`
--
ALTER TABLE `diagnostico_inicial`
  ADD PRIMARY KEY (`id_diagnostico`),
  ADD UNIQUE KEY `id_sesion` (`id_sesion`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  ADD PRIMARY KEY (`id_escuela`);

--
-- Indices de la tabla `estudiantes_detalle`
--
ALTER TABLE `estudiantes_detalle`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD KEY `id_escuela` (`id_escuela`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `plan_actividades_cronograma`
--
ALTER TABLE `plan_actividades_cronograma`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_plan` (`id_plan`);

--
-- Indices de la tabla `plan_trabajo_tutorial`
--
ALTER TABLE `plan_trabajo_tutorial`
  ADD PRIMARY KEY (`id_plan`),
  ADD KEY `id_tutor` (`id_tutor`);

--
-- Indices de la tabla `seguimiento_individual`
--
ALTER TABLE `seguimiento_individual`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD UNIQUE KEY `id_sesion` (`id_sesion`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_tutor` (`id_tutor`);

--
-- Indices de la tabla `sesiones_tutoria`
--
ALTER TABLE `sesiones_tutoria`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `id_asignacion` (`id_asignacion`),
  ADD KEY `id_tutor` (`id_tutor`),
  ADD KEY `id_actividad` (`id_actividad`);

--
-- Indices de la tabla `sesion_asistencia`
--
ALTER TABLE `sesion_asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_sesion` (`id_sesion`);

--
-- Indices de la tabla `tutor_detalles`
--
ALTER TABLE `tutor_detalles`
  ADD PRIMARY KEY (`id_tutor`),
  ADD KEY `id_escuela` (`id_escuela`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `citas_especialista`
--
ALTER TABLE `citas_especialista`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `derivaciones`
--
ALTER TABLE `derivaciones`
  MODIFY `id_derivacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `diagnostico_inicial`
--
ALTER TABLE `diagnostico_inicial`
  MODIFY `id_diagnostico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  MODIFY `id_escuela` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plan_actividades_cronograma`
--
ALTER TABLE `plan_actividades_cronograma`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plan_trabajo_tutorial`
--
ALTER TABLE `plan_trabajo_tutorial`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimiento_individual`
--
ALTER TABLE `seguimiento_individual`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_tutoria`
--
ALTER TABLE `sesiones_tutoria`
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesion_asistencia`
--
ALTER TABLE `sesion_asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `admin_detalles`
--
ALTER TABLE `admin_detalles`
  ADD CONSTRAINT `admin_detalles_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `tutor_detalles` (`id_tutor`),
  ADD CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes_detalle` (`id_estudiante`);

--
-- Filtros para la tabla `citas_especialista`
--
ALTER TABLE `citas_especialista`
  ADD CONSTRAINT `citas_especialista_ibfk_1` FOREIGN KEY (`id_derivacion`) REFERENCES `derivaciones` (`id_derivacion`);

--
-- Filtros para la tabla `derivaciones`
--
ALTER TABLE `derivaciones`
  ADD CONSTRAINT `derivaciones_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `tutor_detalles` (`id_tutor`),
  ADD CONSTRAINT `derivaciones_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes_detalle` (`id_estudiante`),
  ADD CONSTRAINT `derivaciones_ibfk_3` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones_tutoria` (`id_sesion`);

--
-- Filtros para la tabla `detalles_especialista`
--
ALTER TABLE `detalles_especialista`
  ADD CONSTRAINT `detalles_especialista_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `detalles_especialista_ibfk_2` FOREIGN KEY (`id_escuela`) REFERENCES `escuelas` (`id_escuela`);

--
-- Filtros para la tabla `diagnostico_inicial`
--
ALTER TABLE `diagnostico_inicial`
  ADD CONSTRAINT `diagnostico_inicial_ibfk_1` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones_tutoria` (`id_sesion`) ON DELETE CASCADE,
  ADD CONSTRAINT `diagnostico_inicial_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes_detalle` (`id_estudiante`);

--
-- Filtros para la tabla `estudiantes_detalle`
--
ALTER TABLE `estudiantes_detalle`
  ADD CONSTRAINT `estudiantes_detalle_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `estudiantes_detalle_ibfk_2` FOREIGN KEY (`id_escuela`) REFERENCES `escuelas` (`id_escuela`);

--
-- Filtros para la tabla `plan_actividades_cronograma`
--
ALTER TABLE `plan_actividades_cronograma`
  ADD CONSTRAINT `plan_actividades_cronograma_ibfk_1` FOREIGN KEY (`id_plan`) REFERENCES `plan_trabajo_tutorial` (`id_plan`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plan_trabajo_tutorial`
--
ALTER TABLE `plan_trabajo_tutorial`
  ADD CONSTRAINT `plan_trabajo_tutorial_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `tutor_detalles` (`id_tutor`);

--
-- Filtros para la tabla `seguimiento_individual`
--
ALTER TABLE `seguimiento_individual`
  ADD CONSTRAINT `seguimiento_individual_ibfk_1` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones_tutoria` (`id_sesion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones_tutoria`
--
ALTER TABLE `sesiones_tutoria`
  ADD CONSTRAINT `sesiones_tutoria_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones` (`id_asignacion`),
  ADD CONSTRAINT `sesiones_tutoria_ibfk_2` FOREIGN KEY (`id_tutor`) REFERENCES `tutor_detalles` (`id_tutor`);

--
-- Filtros para la tabla `sesion_asistencia`
--
ALTER TABLE `sesion_asistencia`
  ADD CONSTRAINT `sesion_asistencia_ibfk_1` FOREIGN KEY (`id_sesion`) REFERENCES `sesiones_tutoria` (`id_sesion`) ON DELETE CASCADE,
  ADD CONSTRAINT `sesion_asistencia_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes_detalle` (`id_estudiante`);

--
-- Filtros para la tabla `tutor_detalles`
--
ALTER TABLE `tutor_detalles`
  ADD CONSTRAINT `tutor_detalles_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutor_detalles_ibfk_2` FOREIGN KEY (`id_escuela`) REFERENCES `escuelas` (`id_escuela`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
