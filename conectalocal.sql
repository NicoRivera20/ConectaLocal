-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 06-10-2026 a las 03:22:29
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
-- Base de datos: `conectalocal`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oficios`
--

CREATE TABLE `oficios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `oficios`
--

INSERT INTO `oficios` (`id`, `nombre`) VALUES
(5, 'Carpintería'),
(6, 'Cerrajería'),
(2, 'Electricidad'),
(1, 'Gasfitería'),
(4, 'Jardinería'),
(8, 'Mecánico'),
(7, 'Mueblista'),
(3, 'Pintura');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id` int(10) UNSIGNED NOT NULL,
  `trabajador_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `estrellas` tinyint(4) NOT NULL,
  `comentario` varchar(300) NOT NULL DEFAULT '',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resenas`
--

INSERT INTO `resenas` (`id`, `trabajador_id`, `usuario_id`, `estrellas`, `comentario`, `creado_en`) VALUES
(1, 1, 2, 5, 'Excelente trabajo, muy puntual y ordenado. Lo recomiendo totalmente.', '2026-10-02 02:53:52'),
(2, 2, 2, 4, 'Buen trabajo, aunque se demoró un poco más de lo acordado.', '2026-10-02 02:53:52'),
(3, 7, 2, 5, 'Muy buen trabajador, cumplió con lo que dijo, aunque se demoro un poco mas de lo que estaba establecido pero muy buen trabajador.', '2026-10-02 03:06:40'),
(4, 1, 9, 1, 'no me gusto su trabajo realizado, se demoro en llegar y no realizo lo que le pidio de buena manera...', '2026-10-06 00:04:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sectores`
--

CREATE TABLE `sectores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sectores`
--

INSERT INTO `sectores` (`id`, `nombre`) VALUES
(8, 'Amanecer'),
(7, 'Centro'),
(5, 'Fundo El Carmen'),
(2, 'Labranza'),
(4, 'Padre las Casas'),
(3, 'Pedro de Valdivia'),
(6, 'Pueblo Nuevo'),
(9, 'Santa Rosa'),
(1, 'Temuco');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajadores`
--

CREATE TABLE `trabajadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `whatsapp` char(11) NOT NULL,
  `oficio_id` int(10) UNSIGNED NOT NULL,
  `sector_id` int(10) UNSIGNED NOT NULL,
  `tarifa` varchar(80) NOT NULL,
  `descripcion` varchar(200) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trabajadores`
--

INSERT INTO `trabajadores` (`id`, `nombre`, `whatsapp`, `oficio_id`, `sector_id`, `tarifa`, `descripcion`, `foto`, `disponible`, `destacado`, `creado_en`) VALUES
(1, 'Marcelo Vidal', '56900000001', 1, 1, 'Desde $15.000 la visita', 'Filtraciones, destape de cañerías e instalación de grifería.', NULL, 1, 1, '2026-10-01 23:10:54'),
(2, 'Carla Núñez', '56900000002', 2, 1, 'Desde $18.000 la visita', 'Tableros, enchufes, luminarias y corte de luz.', NULL, 1, 0, '2026-10-01 23:10:54'),
(3, 'Iván Soto', '56900000003', 3, 2, '$8.000 por hora', 'Pintura interior y exterior, reparación de muros.', NULL, 0, 0, '2026-10-01 23:10:54'),
(4, 'Patricia Reyes', '56900000004', 4, 4, '$7.000 por hora', 'Poda, mantención de jardines y riego.', NULL, 1, 0, '2026-10-01 23:10:54'),
(5, 'Hugo Contreras', '56900000005', 5, 3, 'Presupuesto sin costo', 'Muebles a medida, puertas y reparaciones de madera.', NULL, 1, 0, '2026-10-01 23:10:54'),
(6, 'Daniela Fuentes', '56900000006', 6, 1, 'Desde $20.000', 'Apertura de puertas, cambio de chapas y llaves.', NULL, 0, 0, '2026-10-01 23:10:54'),
(7, 'Juan Pedro', '56969192903', 2, 4, 'Desde $15.000 la visita', 'Arreglos de casas, electrodomésticos entre otras cosas mas.', NULL, 1, 0, '2026-10-02 02:01:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL DEFAULT '',
  `correo` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('trabajador','cliente','admin') NOT NULL DEFAULT 'trabajador',
  `trabajador_id` int(10) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password_hash`, `rol`, `trabajador_id`, `creado_en`) VALUES
(1, 'Administrador', 'admin@conectalocal.cl', '$2y$10$8FNFtbOOtexa6VkOVXc75OH2YxSt40WgZv/K.R2Mtm6m/OOC6BosO', 'admin', NULL, '2026-10-02 00:56:34'),
(2, 'Nicolas Rivera', 'riveranicolas2018@gmail.com', '$2y$10$hBdv6CCbyTwzPeWoCXM9tOYS1ZQIA5YTAxRwZWRMznzGZyZG7Yu22', 'cliente', NULL, '2026-10-02 01:06:17'),
(3, 'juan', 'juan@gmail.cl', '$2y$10$lOlrtUOnXg3VoQaQoB8s4Op5ClPp3UgXim32Cn5g1FG4YBMM/7Np.', 'trabajador', 7, '2026-10-02 01:59:04'),
(9, 'Federico', 'federico@gmail.com', '$2y$10$mSqjnxw0Yioh7Z2eW4asHehv50acriUcHYu7MI1KU8POipjXeflf6', 'cliente', NULL, '2026-10-06 00:02:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `oficios`
--
ALTER TABLE `oficios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_resena` (`trabajador_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sectores`
--
ALTER TABLE `sectores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `trabajadores`
--
ALTER TABLE `trabajadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oficio_id` (`oficio_id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `trabajador_id` (`trabajador_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `oficios`
--
ALTER TABLE `oficios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sectores`
--
ALTER TABLE `sectores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `trabajadores`
--
ALTER TABLE `trabajadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `trabajadores`
--
ALTER TABLE `trabajadores`
  ADD CONSTRAINT `trabajadores_ibfk_1` FOREIGN KEY (`oficio_id`) REFERENCES `oficios` (`id`),
  ADD CONSTRAINT `trabajadores_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectores` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
