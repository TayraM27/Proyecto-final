-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-04-2026 a las 11:26:23
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
-- Base de datos: `petfamily`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apadrinamientos`
--

CREATE TABLE `apadrinamientos` (
  `idApadrinamiento` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idMascota` int(10) UNSIGNED NOT NULL,
  `cuota` decimal(6,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activo','cancelado','pausado') NOT NULL DEFAULT 'activo',
  `nombre_pagador` varchar(120) DEFAULT NULL,
  `email_pagador` varchar(120) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `idComentario` int(10) UNSIGNED NOT NULL,
  `idPublicacion` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED DEFAULT NULL,
  `idProtectora` int(10) UNSIGNED DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `contenido` text NOT NULL,
  `num_likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`idComentario`, `idPublicacion`, `idUsuario`, `contenido`, `num_likes`, `activo`, `fecha`) VALUES
(1, 1, 2, 'Qué lindos gatos!!', 0, 1, '2026-04-26 21:25:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donaciones`
--

CREATE TABLE `donaciones` (
  `idDonacion` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED DEFAULT NULL,
  `idProtectora` int(10) UNSIGNED DEFAULT NULL,
  `nombre_donante` varchar(120) DEFAULT NULL,
  `email_donante` varchar(120) DEFAULT NULL,
  `importe` decimal(8,2) NOT NULL,
  `mensaje` varchar(300) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `estado` enum('pendiente','completada','fallida') NOT NULL DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entidades_colaboradoras`
--

CREATE TABLE `entidades_colaboradoras` (
  `idEntidad` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('tienda','veterinaria','clinica','ong','institucion','media','Otro') NOT NULL,
  `web` varchar(255) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `localidad` varchar(80) DEFAULT NULL,
  `descuento` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `entidades_colaboradoras`
--

INSERT INTO `entidades_colaboradoras` (`idEntidad`, `nombre`, `descripcion`, `tipo`, `web`, `email`, `telefono`, `direccion`, `localidad`, `descuento`, `logo`, `activa`, `fecha_creacion`) VALUES
(18, 'Kiwoko - Asturias', 'Punto de recogida de pienso para protectoras colaboradoras. Descuento del 10 % con código PetFamily.', 'tienda', 'https://www.kiwoko.com', NULL, NULL, NULL, NULL, '10% descuento', NULL, 1, '2026-04-28 00:25:59'),
(19, 'Peluquería Huella Nature — Lugones', 'Primera sesión de peluquería gratuita para mascotas adoptadas a través de PetFamily.', 'tienda', 'https://huellanature.vercel.app/', NULL, NULL, NULL, NULL, '1 sesión gratis', NULL, 1, '2026-04-28 00:25:59'),
(20, 'Clínica Sabuvet — Avilés', 'Primera consulta gratuita para nuevos adoptantes. Vacunación anual a precio reducido para animales adoptados.', 'clinica', 'https://www.sabuvet.com/', NULL, NULL, NULL, NULL, 'Consulta gratis', NULL, 1, '2026-04-28 00:25:59'),
(21, 'Tienda Animal - Asturias', 'Kit de bienvenida gratuito al adotar: collar, placa identificativa y pienso de muestra.', 'tienda', 'https://www.tiendanimal.es/', NULL, NULL, NULL, NULL, 'Kit bienvenida', NULL, 1, '2026-04-28 00:25:59'),
(22, 'Ayuntamiento de Gijón', 'Gestión del Centro de Protección Animal municipal y financiación de campañas de esterilización.', 'institucion', 'https://www.gijon.es', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-28 00:25:59'),
(23, 'SEPROBA', 'Sociedad Española de Protección Animal. Marco legal y guías de bienestar para las protectoras asociadas.', 'ong', 'https://seproba.chil.me/', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-28 00:25:59'),
(24, 'Universidad de Oviedo', 'Alumnos de Veterinaria colaboran con revisiones gratuitas en jornadas de adopción.', 'institucion', 'https://www.uniovi.es', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-28 00:25:59'),
(25, 'La Nueva España', 'Espacio mensual en el diario para dar visibilidad a los animales más urgentes de Asturias.', 'media', 'https://www.lne.es', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-28 00:25:59'),
(26, 'prueba', 'esto es una prueba', 'media', 'https://www.ticketmaster.es/', NULL, NULL, NULL, NULL, 'Test', NULL, 0, '2026-04-28 08:58:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `idEvento` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_evento` date NOT NULL,
  `hora` varchar(20) DEFAULT NULL,
  `lugar` varchar(200) DEFAULT NULL,
  `localidad` varchar(80) DEFAULT NULL,
  `url_info` varchar(255) DEFAULT NULL,
  `precio` varchar(50) DEFAULT 'Gratis',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`idEvento`, `titulo`, `descripcion`, `fecha_evento`, `hora`, `lugar`, `localidad`, `url_info`, `precio`, `activa`, `fecha_creacion`) VALUES
(7, 'Jornada de Adopción — Gijón', 'Ven a conocer a los animales de las protectoras gijonesas. Traída masiva de perros y gatos en busca de hogar.', '2025-06-14', '11:00 – 14:00', 'Plaza Mayor, Gijón', 'Gijón', NULL, 'Gratis', 1, '2026-04-28 00:25:59'),
(8, 'Feria Animal — Oviedo', 'Jornada anual con adopciones, talleres de educación canina y punto de microchipado gratuito.', '2025-06-28', '10:00 – 18:00', 'Parque de Invierno, Oviedo', 'Oviedo', NULL, 'Gratis', 1, '2026-04-28 00:25:59'),
(9, 'Día del Animal — Avilés', 'Concentración de protectoras asturianas. Charlas sobre tenencia responsable y punto de donación de pienso.', '2025-07-05', '10:30 – 13:30', 'Parque del Muelle, Avilés', 'Avilés', NULL, 'Gratis', 1, '2026-04-28 00:25:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idMascota` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`idUsuario`, `idMascota`, `fecha`) VALUES
(1, 1, '2026-04-25 21:50:28'),
(1, 6, '2026-04-26 17:02:01'),
(1, 7, '2026-04-27 10:31:30'),
(2, 5, '2026-04-26 16:57:11'),
(2, 6, '2026-04-26 16:55:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_comentarios`
--

CREATE TABLE `likes_comentarios` (
  `idLike` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED DEFAULT NULL,
  `idProtectora` int(10) UNSIGNED DEFAULT NULL,
  `idComentario` int(10) UNSIGNED NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  CONSTRAINT chk_like_author CHECK ((idUsuario IS NOT NULL AND idProtectora IS NULL) OR (idUsuario IS NULL AND idProtectora IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_publicaciones`
--

CREATE TABLE `likes_publicaciones` (
  `idLike` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idPublicacion` int(10) UNSIGNED NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `likes_publicaciones`
--

INSERT INTO `likes_publicaciones` (`idLike`, `idUsuario`, `idPublicacion`, `fecha`) VALUES
(1, 2, 2, '2026-04-26 23:24:43'),
(2, 2, 1, '2026-04-26 23:24:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `idMascota` int(10) UNSIGNED NOT NULL,
  `idProtectora` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `especie` enum('perro','gato') NOT NULL,
  `raza` varchar(100) DEFAULT NULL,
  `sexo` enum('macho','hembra') NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `tamanyo` enum('pequeño','mediano','grande') NOT NULL,
  `color` varchar(80) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado_salud` varchar(120) DEFAULT NULL,
  `urgencia` enum('normal','urgente','nuevo') NOT NULL DEFAULT 'normal',
  `estado_adopcion` enum('disponible','en_proceso','adoptado','no_disponible') NOT NULL DEFAULT 'disponible',
  `disponible_apadrinamiento` tinyint(1) NOT NULL DEFAULT 1,
  `disponible_acogida` tinyint(1) NOT NULL DEFAULT 1,
  `compatible_ninos` tinyint(1) NOT NULL DEFAULT 0,
  `compatible_perros` tinyint(1) NOT NULL DEFAULT 0,
  `compatible_gatos` tinyint(1) NOT NULL DEFAULT 0,
  `apto_piso` tinyint(1) NOT NULL DEFAULT 0,
  `vacunado` tinyint(1) NOT NULL DEFAULT 0,
  `esterilizado` tinyint(1) NOT NULL DEFAULT 0,
  `microchip` tinyint(1) NOT NULL DEFAULT 0,
  `desparasitado` tinyint(1) NOT NULL DEFAULT 0,
  `badge_extra` varchar(120) DEFAULT NULL,
  `edad_texto` varchar(40) DEFAULT NULL,
  `num_vistas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_entrada` date DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `prioritaria` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_prioritaria` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`idMascota`, `idProtectora`, `nombre`, `especie`, `raza`, `sexo`, `fecha_nacimiento`, `tamanyo`, `color`, `descripcion`, `estado_salud`, `urgencia`, `estado_adopcion`, `disponible_apadrinamiento`, `disponible_acogida`, `compatible_ninos`, `compatible_perros`, `compatible_gatos`, `apto_piso`, `vacunado`, `esterilizado`, `microchip`, `desparasitado`, `badge_extra`, `edad_texto`, `num_vistas`, `fecha_entrada`, `fecha_publicacion`, `activa`) VALUES
(1, 1, 'Leia', 'perro', 'Pit bull', 'hembra', NULL, 'mediano', 'Marrón', 'Pit bull de 14 años con artrosis. Muy sociable con personas y perros. Necesita licencia PPP.', 'Artrosis', 'normal', 'disponible', 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 'Licencia PPP', '14 años', 17, '2010-01-01', '2026-04-24 12:12:05', 1),
(2, 2, 'Haru', 'perro', 'Mestiza', 'hembra', NULL, 'grande', 'Marrón', 'Perra de 7 años con mucha energía. Lleva 1 año esperando familia.', 'Bueno', 'urgente', 'disponible', 1, 1, 1, 0, 0, 0, 1, 1, 1, 1, NULL, '7 años', 41, '2024-01-01', '2026-04-24 12:12:05', 1),
(3, 3, 'Roy', 'gato', 'Europeo', 'macho', NULL, 'pequeño', 'Blanco y negro', 'Muy cariñoso y mimoso. Se lleva fenomenal con niños y otros gatos.', 'Bueno', 'nuevo', 'disponible', 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, NULL, '2 años', 17, '2023-07-26', '2026-04-24 12:12:05', 1),
(4, 4, 'Bambi', 'gato', 'Común europeo', 'macho', '2022-05-10', 'pequeño', 'bicolor:blanco,negro', 'Gato tranquilo que busca un hogar donde sentirse seguro y querido.', 'Bueno', 'normal', 'disponible', 1, 1, 0, 0, 0, 1, 1, 1, 1, 1, '', '', 15, '2025-06-11', '2026-04-24 12:12:05', 1),
(5, 5, 'Dexter', 'perro', 'Cruce Pastor Alemán', 'macho', NULL, 'mediano', 'bicolor:marron,negro', 'Lleva 9 años esperando. Fue maltratado y abandonado.', 'Bueno', 'urgente', 'disponible', 0, 1, 1, 0, 1, 1, 1, 1, 1, 1, '', '', 17, '2015-05-05', '2026-04-24 12:12:05', 1),
(6, 2, 'Bosque', 'gato', 'Común europeo', 'hembra', '2025-04-10', 'pequeño', 'atigrado:blanco,gris,negro', 'Gatita de 8 meses positiva a leucemia felina. Necesita hogar sin otros animales.', 'Leucemia felina+', 'normal', 'no_disponible', 1, 0, 0, 0, 0, 1, 1, 1, 1, 1, '', '', 14, '2024-12-01', '2026-04-24 12:12:05', 1),
(7, 6, 'Chucky', 'gato', '', 'macho', '2024-07-11', 'mediano', 'bicolor:negro,blanco', 'Gatito juguetón y sociable, se lleva bien con personas, niños, gatos y perros.', 'Bueno', 'nuevo', 'disponible', 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, '', '', 5, '2024-06-27', '2026-04-27 10:27:33', 1),
(8, 6, 'dssfds', 'perro', 'dddddfs', 'macho', '2026-04-01', 'pequeño', '', '', 'Bueno', 'urgente', 'disponible', 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 'Urgente', '', 2, '2026-04-24', '2026-04-28 09:01:35', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas_fotos`
--

CREATE TABLE `mascotas_fotos` (
  `idFoto` int(10) UNSIGNED NOT NULL,
  `idMascota` int(10) UNSIGNED NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mascotas_fotos`
--

INSERT INTO `mascotas_fotos` (`idFoto`, `idMascota`, `ruta`, `es_principal`, `orden`) VALUES
(1, 1, 'img/mascotas/leia-centro-proteccion-animales-gijon.jpg', 1, 0),
(2, 1, 'img/mascotas/leai2-centro-proteccion-animales-gijon.jpg', 0, 1),
(3, 2, 'img/mascotas/haru-protectora-de-asturias-dog.jpg', 1, 0),
(4, 2, 'img/mascotas/haru2-protectora-de-asturias.jpg', 0, 1),
(5, 2, 'img/mascotas/haru3-protectora-de-asturias.jpg', 0, 2),
(6, 3, 'img/mascotas/roys-asociacion-felina-la-esperanza-cat.jpg', 1, 0),
(7, 3, 'img/mascotas/roy2-asociacion-felina-la-esperanza.jpg', 0, 1),
(8, 3, 'img/mascotas/roy3-asociacion-felina-la-esperanza.jpg', 0, 2),
(9, 4, 'img/mascotas/bambi-masquechuchos-cat.jpg', 1, 0),
(10, 5, 'img/mascotas/dexter-nortemascotas-dog.jpg', 1, 0),
(11, 5, 'img/mascotas/dexter2-nortemascotas-1461588001-ouIWI.jpg', 0, 1),
(12, 6, 'img/mascotas/bosque-protectora-de-asturias-cat.jpg', 1, 0),
(13, 6, 'img/mascotas/bosque2-protectora-de-asturias.jpg', 0, 1),
(14, 6, 'img/mascotas/bosque3-protectora-de-asturias.jpg', 0, 2),
(15, 7, 'img/mascotas/mascota_7_69ef3a157f116.jpeg', 1, 0),
(16, 7, 'img/mascotas/mascota_7_69ef3a1580d79.jpeg', 0, 1),
(17, 7, 'img/mascotas/mascota_7_69ef3a15821da.jpeg', 0, 2),
(18, 7, 'img/mascotas/mascota_7_69ef3a15834b8.jpg', 0, 3),
(20, 8, 'img/mascotas/mascota_8_69f0776f773cb.jpeg', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `idMensaje` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `asunto` varchar(200) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('colaboracion','protectora','otro') NOT NULL DEFAULT 'otro',
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `idNotificacion` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `protectoras`
--

CREATE TABLE `protectoras` (
  `idProtectora` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `descripcion_dona` varchar(300) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `localidad` varchar(80) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `web` varchar(200) DEFAULT NULL,
  `red_social_url` varchar(255) DEFAULT NULL,
  `especie_atencion` enum('perros','gatos','ambos') NOT NULL DEFAULT 'ambos',
  `badges` varchar(500) DEFAULT NULL,
  `url_formulario_acogida` varchar(500) DEFAULT NULL,
  `tipo_pagina` enum('web','red_social','portal','otra','sin_pagina') NOT NULL DEFAULT 'sin_pagina',
  `iban` varchar(34) DEFAULT NULL,
  `bizum` varchar(9) DEFAULT NULL,
  `teaming` varchar(200) DEFAULT NULL,
  `foto_logo` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `verificada` tinyint(1) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `protectoras`
--

INSERT INTO `protectoras` (`idProtectora`, `nombre`, `descripcion`, `descripcion_dona`, `direccion`, `localidad`, `telefono`, `email`, `web`, `red_social_url`, `especie_atencion`, `badges`, `url_formulario_acogida`, `tipo_pagina`, `iban`, `bizum`, `teaming`, `foto_logo`, `latitud`, `longitud`, `verificada`, `activa`, `fecha_registro`) VALUES
(1, 'Centro de Protección Animal de Gijón', 'Instalación municipal gestionada por la Fundación Protectora de Asturias. Recogen animales perdidos o abandonados en Gijón, les dan atención veterinaria completa y los preparan para la adopción.', 'Centro municipal de Gijón gestionado por el Ayuntamiento. Atiende urgencias, animales perdidos y abandonados, con especial sensibilidad hacia mayores y razas PPP.', 'Camino de Liervado S/n', 'Gijón', '615 411 417', 'cproteccionanimalgijon@gmail.com', 'https://www.gijon.es/es/directorio/deposito-municipal-de-animales', 'https://www.facebook.com/cpagijon', 'ambos', 'gestion_municipal,animales_mayores,licencia_ppp', NULL, '', 'ES15 0081 5665 2400 0109 0516 ', NULL, NULL, 'img/protectora/centro-proteccion-animales-gijon.jpg', 43.5155000, -5.6940000, 1, 1, '2026-04-24 12:12:05'),
(2, 'Fundación Protectora de Asturias', 'Fundada en 2012 como respuesta al abandono masivo. Trabajan con casas de acogida como base principal y tienen refugio propio en Siero.', 'Trabajan exclusivamente con casas de acogida en toda Asturias desde 2012. Sus animales se entregan vacunados, esterilizados y con microchip, con contrato y seguimiento posterior.', 'Siero, Asturias', 'Siero', NULL, 'info@protectoradeasturias.org', 'http://www.protectoradeasturias.org/index.php/colabora/donaciones', 'https://www.facebook.com/laprotectoradeasturias', 'perros', 'casas_acogida,fundacion_oficial,teaming', 'https://docs.google.com/forms/d/e/1FAIpQLSdbnY3B2RRvkBGQljSjyU2VUniOlWKyISzbv_vhxXJH6ESAyw/viewform?c=0&w=1', 'web', 'ES39 0182 2800 1902 0163 9405 ', NULL, 'https://www.teaming.net/fundacionprotectoradeanimalesasturias', 'img/protectora/protectora-de-asturias.jpg', 43.3614000, -5.8496000, 1, 1, '2026-04-24 12:12:05'),
(3, 'Asociación Felina La Esperanza', 'Asociación especializada exclusivamente en gatos, con especial atención a los casos más difíciles: felinos con enfermedades crónicas como la leucemia felina, gatos de edad avanzada o con necesidades especiales.', 'Especialistas en gatos con enfermedades crónicas como leucemia felina o FIV. Sostenida íntegramente por voluntarios, acompañan los casos más difíciles hasta encontrar familias comprometidas.', 'Asturias', 'Asturias', '', 'asociacionfelinalaesperanza@gmail.com', 'https://asociacionfelinalaesperanza.com/', 'https://www.facebook.com/AsociacionFelinaEsperanza/', 'gatos', 'teaming,voluntarios,principalmente_gatos', NULL, 'web', 'ES90 0081 5726760001131419', NULL, 'https://www.teaming.net/asociacionfelinalaesperanza', 'img/protectora/asociacion-felina-la-esperanza.jpg', 43.5547000, -5.9249000, 1, 1, '2026-04-24 12:12:05'),
(4, 'MÁS QUE CHUCHOS', 'Nacida en 1993, es una de las protectoras con más trayectoria de Asturias. Han conseguido cientos de adopciones responsables.', 'Una de las protectoras más longevas de Asturias, desde 1993. Su red de casas de acogida y su labor de concienciación han logrado cientos de adopciones responsables en más de tres décadas.', 'Oviedo – Llanera', 'Oviedo', NULL, 'info@masquechuchos.org', 'http://masquechuchos.org', 'https://www.facebook.com/MASQUECHUCHOS', 'ambos', 'casas_acogida,teaming,voluntarios', NULL, 'web', 'ES65 2103 7001 6000 3022 1601', NULL, 'https://www.teaming.net/masquechuchos', 'img/protectora/masquechuchos.jpg', 43.3572000, -5.8602000, 1, 1, '2026-04-24 12:12:05'),
(5, 'Nortemascotas', 'Asociación sin ánimo de lucro que lucha por los derechos de los animales desde Gijón. Trabajan con voluntarios y casas de acogida particulares, sin instalaciones propias.', 'Asociación gijonesa 100% voluntaria, sin instalaciones propias. Su modelo de acogida familiar permite un seguimiento muy cercano de cada animal hasta encontrar su hogar definitivo.', 'Gijón, Pumarín', 'Gijón', '665 971 933', 'Mariasol.ferreyra2014@gmail.com', 'https://www.albergaria.es/protectoras/nortemascotas/', NULL, 'gatos', 'voluntarios,sin_instalaciones,trato_familiar', NULL, 'otra', NULL, NULL, '', 'img/protectora/nortemascota-dex.jpg', 43.5322000, -5.6611000, 1, 1, '2026-04-24 12:12:05'),
(6, 'A.P.A Luz Felina', 'La Asociación Protectora de Animales Luz Felina comenzó su andadura a finales del 2014. Lo que comenzó como unas particulares tratando de sacar gatos sociables de las calles, se ha convertido en una asociación que mantiene los ideales de los comienzos y ha podido ampliar su radio de acción y llegar a más animales de los que hubieran podido imaginar.', 'Asociación enfocada en darle un hogar a gatos que se encuentran en la calle.', '', 'Asturias', '', 'apaluzfelina@gmail.com', 'https://www.albergaria.es/protectoras/luz-felina/', NULL, 'perros', 'teaming,trato_familiar,principalmente_perros', NULL, 'otra', 'ES09 0133 5857 9242 0000 2029', NULL, 'https://www.teaming.net/rayitodeluz', 'img/protectora/apa1.jpg', NULL, NULL, 0, 1, '2026-04-24 18:04:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

CREATE TABLE `publicaciones` (
  `idPublicacion` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `tipo_media` varchar(20) NOT NULL DEFAULT 'none',
  `categoria` varchar(80) DEFAULT NULL,
  `num_likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `num_vistas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fijada` tinyint(1) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `publicaciones`
--

INSERT INTO `publicaciones` (`idPublicacion`, `idUsuario`, `titulo`, `contenido`, `imagen`, `video`, `tipo_media`, `categoria`, `num_likes`, `num_vistas`, `fijada`, `activa`, `fecha`) VALUES
(1, 1, '¡Bienvenid@s al foro de PetFamily!', 'Bienvenidos al foro oficial de PetFamily\r\nEste espacio nace con el objetivo de reunir a personas comprometidas con el bienestar animal y ofrecer un lugar seguro donde compartir información, resolver dudas y apoyar a quienes conviven con animales o desean hacerlo\r\n\r\nEn este foro encontrarás secciones dedicadas a información general, salud, adopción, acogida y cuidados, todas orientadas a facilitar una convivencia responsable y a promover la protección animal\r\nNuestro propósito es crear una comunidad útil, respetuosa y activa, donde cada experiencia pueda ayudar a otros y donde las protectoras y familias encuentren un punto de encuentro claro y accesible\r\n\r\nGracias por formar parte de PetFamily y por contribuir a que más animales tengan una vida mejor', 'img/foro/foro_69ee7e9e05f824.21747752.jpg', NULL, 'imagen', 'informacion', 1, 3, 0, 0, '2026-04-26 21:07:42'),
(2, 1, 'Revisiones veterinarias periódicas', 'Las revisiones veterinarias regulares son esenciales para garantizar el bienestar de cualquier animal\r\nPermiten detectar problemas de salud en fases tempranas, actualizar el calendario de vacunación y resolver dudas sobre alimentación, comportamiento o cuidados específicos\r\nLa frecuencia recomendada puede variar según la edad, la especie y las necesidades individuales, pero en general se aconseja realizar al menos una revisión anual\r\nUn seguimiento adecuado contribuye a una vida más larga y saludable y ayuda a prevenir situaciones que podrían complicarse con el tiempo. \r\nQue nuestros peluditos sean saludables y juguetones por un largo tiempo🥰', NULL, 'img/foro/videos/foro_69ee81abb6dd10.28648444.mp4', 'video', 'salud', 1, 2, 0, 1, '2026-04-26 21:20:43'),
(3, 1, '¡Bienveni@s al foro de PetFamily!', 'Bienvenid@s al foro oficial de PetFamily🐾.\r\nEste espacio nace con el objetivo de reunir a personas comprometidas con el bienestar animal y ofrecer un lugar seguro donde compartir información, resolver dudas y apoyar a quienes conviven con animales o desean hacerlo.\r\n\r\nEn este foro encontrarás secciones dedicadas a información general, salud, adopción, acogida y cuidados, todas orientadas a facilitar una convivencia responsable y a promover la protección animal.\r\nNuestro propósito es crear una comunidad útil, respetuosa y activa, donde cada experiencia pueda ayudar a otros y donde las protectoras y familias encuentren un punto de encuentro claro y accesible.', 'img/foro/foro_69ee8465a83f69.16869490.jpg', NULL, 'imagen', 'informacion', 0, 0, 0, 1, '2026-04-26 21:32:21'),
(4, 1, 'fdfsdf', 'fdfsdf', NULL, NULL, 'none', 'adopcion', 0, 0, 0, 0, '2026-04-28 09:16:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_mapa`
--

CREATE TABLE `puntos_mapa` (
  `idPunto` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `categoria` enum('protectora','veterinaria','tienda','donacion','refugio') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `localidad` varchar(80) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `web` varchar(200) DEFAULT NULL,
  `latitud` decimal(10,7) NOT NULL,
  `longitud` decimal(10,7) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `idProtectora` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos`
--

CREATE TABLE `seguimientos` (
  `idSeguimiento` int(10) UNSIGNED NOT NULL,
  `idApadrinamiento` int(10) UNSIGNED NOT NULL,
  `contenido` text DEFAULT NULL,
  `tipo_archivo` enum('foto','video','texto') NOT NULL DEFAULT 'texto',
  `ruta_archivo` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_adopcion`
--

CREATE TABLE `solicitudes_adopcion` (
  `idSolicitud` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED DEFAULT NULL,
  `idMascota` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion_completa` varchar(255) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `tipo_vivienda` enum('piso','casa','finca') DEFAULT NULL,
  `vivienda_en_propiedad` enum('si','no') DEFAULT NULL,
  `permiso_propietario` varchar(255) DEFAULT NULL,
  `personas_en_hogar` int(10) UNSIGNED DEFAULT NULL,
  `ninos_en_hogar` enum('si','no') DEFAULT NULL,
  `otros_animales` enum('si','no') DEFAULT NULL,
  `descripcion_otros_animales` text DEFAULT NULL,
  `experiencia_animales` text DEFAULT NULL,
  `tiempo_fuera_casa` varchar(50) DEFAULT NULL,
  `motivo_adopcion` text DEFAULT NULL,
  `compromiso_visitas` tinyint(1) DEFAULT 0,
  `aceptar_politica_privacidad` tinyint(1) NOT NULL DEFAULT 0,
  `mensaje` text DEFAULT NULL,
  `estado` enum('pendiente','en_revision','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_gestion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `test_opciones`
--

CREATE TABLE `test_opciones` (
  `idOpcion` int(10) UNSIGNED NOT NULL,
  `idPregunta` int(10) UNSIGNED NOT NULL,
  `texto` varchar(300) NOT NULL,
  `valor` varchar(80) DEFAULT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `test_preguntas`
--

CREATE TABLE `test_preguntas` (
  `idPregunta` int(10) UNSIGNED NOT NULL,
  `tipo_test` enum('compatibilidad','conocimiento') NOT NULL,
  `texto` varchar(400) NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `test_resultados`
--

CREATE TABLE `test_resultados` (
  `idResultado` int(10) UNSIGNED NOT NULL,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `tipo_test` enum('compatibilidad','conocimiento') NOT NULL,
  `respuestas_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`respuestas_json`)),
  `puntuacion` tinyint(3) UNSIGNED DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellidos` varchar(120) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `localidad` varchar(80) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `rol` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `nombre`, `apellidos`, `username`, `email`, `password_hash`, `localidad`, `telefono`, `foto_perfil`, `google_id`, `rol`, `activo`, `fecha_registro`, `ultimo_login`) VALUES
(1, 'Admin', 'PetFamily', 'admin', 'admin-petfamily@yopmail.es', '$2y$10$5.W.nYdW/bkqSi12UQh2B.H6du9JXMwOg57nlxP5XHpY6IGv1zQfO', NULL, NULL, NULL, NULL, 'admin', 1, '2026-04-24 12:12:05', '2026-04-28 08:57:23'),
(2, 'Tayra Maldonado', NULL, 'tayramaldonado', 'lpsdiscs@gmail.com', '$2y$10$SdkEM8YohG7jBLCOhc.w4uJbnLBGul/B83O/87YX5bi0zmtV9Bzdq', NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJP4a1ONUzmPdGX6dMfQgABGab2RH7DXBHt0-ulFJjO2tGEjC0T=s96-c', '108227877952948717077', 'usuario', 1, '2026-04-24 17:47:30', '2026-04-26 21:24:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `apadrinamientos`
--
ALTER TABLE `apadrinamientos`
  ADD PRIMARY KEY (`idApadrinamiento`),
  ADD KEY `fk_apad_usuario` (`idUsuario`),
  ADD KEY `fk_apad_mascota` (`idMascota`),
  ADD KEY `idx_apad_estado` (`estado`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`idComentario`),
  ADD KEY `fk_com_usuario` (`idUsuario`),
  ADD KEY `fk_com_protectora` (`idProtectora`),
  ADD KEY `idx_com_publicacion` (`idPublicacion`),
  ADD KEY `fk_com_parent` (`parent_id`);

--
-- Indices de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD PRIMARY KEY (`idDonacion`),
  ADD KEY `fk_don_usuario` (`idUsuario`),
  ADD KEY `fk_don_protectora` (`idProtectora`),
  ADD KEY `idx_don_estado` (`estado`);

--
-- Indices de la tabla `entidades_colaboradoras`
--
ALTER TABLE `entidades_colaboradoras`
  ADD PRIMARY KEY (`idEntidad`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`idEvento`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`idUsuario`,`idMascota`),
  ADD KEY `fk_fav_mascota` (`idMascota`);

--
-- Indices de la tabla `likes_comentarios`
--
ALTER TABLE `likes_comentarios`
  ADD PRIMARY KEY (`idLike`),
  ADD UNIQUE KEY `uq_like_user_com` (`idUsuario`,`idComentario`),
  ADD UNIQUE KEY `uq_like_prot_com` (`idProtectora`,`idComentario`),
  ADD KEY `fk_like_comentario` (`idComentario`);

--
-- Indices de la tabla `likes_publicaciones`
--
ALTER TABLE `likes_publicaciones`
  ADD PRIMARY KEY (`idLike`),
  ADD UNIQUE KEY `uq_like` (`idUsuario`,`idPublicacion`),
  ADD KEY `idPublicacion` (`idPublicacion`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`idMascota`),
  ADD KEY `idx_mascotas_especie` (`especie`),
  ADD KEY `idx_mascotas_urgencia` (`urgencia`),
  ADD KEY `idx_mascotas_estado` (`estado_adopcion`),
  ADD KEY `idx_mascotas_protectora` (`idProtectora`),
  ADD KEY `idx_mascotas_apadrinamiento` (`disponible_apadrinamiento`);

--
-- Indices de la tabla `mascotas_fotos`
--
ALTER TABLE `mascotas_fotos`
  ADD PRIMARY KEY (`idFoto`),
  ADD KEY `fk_foto_mascota` (`idMascota`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`idMensaje`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`idNotificacion`),
  ADD KEY `idUsuario` (`idUsuario`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idUsuario` (`idUsuario`);

--
-- Indices de la tabla `protectoras`
--
ALTER TABLE `protectoras`
  ADD PRIMARY KEY (`idProtectora`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD PRIMARY KEY (`idPublicacion`),
  ADD KEY `idx_pub_usuario` (`idUsuario`);

--
-- Indices de la tabla `puntos_mapa`
--
ALTER TABLE `puntos_mapa`
  ADD PRIMARY KEY (`idPunto`),
  ADD KEY `fk_punto_protectora` (`idProtectora`);

--
-- Indices de la tabla `seguimientos`
--
ALTER TABLE `seguimientos`
  ADD PRIMARY KEY (`idSeguimiento`),
  ADD KEY `fk_seg_apadrinamiento` (`idApadrinamiento`);

--
-- Indices de la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  ADD PRIMARY KEY (`idSolicitud`),
  ADD KEY `fk_sol_usuario` (`idUsuario`),
  ADD KEY `fk_sol_mascota` (`idMascota`);

--
-- Indices de la tabla `test_opciones`
--
ALTER TABLE `test_opciones`
  ADD PRIMARY KEY (`idOpcion`),
  ADD KEY `fk_opcion_pregunta` (`idPregunta`);

--
-- Indices de la tabla `test_preguntas`
--
ALTER TABLE `test_preguntas`
  ADD PRIMARY KEY (`idPregunta`);

--
-- Indices de la tabla `test_resultados`
--
ALTER TABLE `test_resultados`
  ADD PRIMARY KEY (`idResultado`),
  ADD KEY `fk_res_usuario` (`idUsuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `apadrinamientos`
--
ALTER TABLE `apadrinamientos`
  MODIFY `idApadrinamiento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `idComentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  MODIFY `idDonacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entidades_colaboradoras`
--
ALTER TABLE `entidades_colaboradoras`
  MODIFY `idEntidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `idEvento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `likes_comentarios`
--
ALTER TABLE `likes_comentarios`
  MODIFY `idLike` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `likes_publicaciones`
--
ALTER TABLE `likes_publicaciones`
  MODIFY `idLike` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `idMascota` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `mascotas_fotos`
--
ALTER TABLE `mascotas_fotos`
  MODIFY `idFoto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `idMensaje` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `idNotificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `protectoras`
--
ALTER TABLE `protectoras`
  MODIFY `idProtectora` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `idPublicacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `puntos_mapa`
--
ALTER TABLE `puntos_mapa`
  MODIFY `idPunto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimientos`
--
ALTER TABLE `seguimientos`
  MODIFY `idSeguimiento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  MODIFY `idSolicitud` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `test_opciones`
--
ALTER TABLE `test_opciones`
  MODIFY `idOpcion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `test_preguntas`
--
ALTER TABLE `test_preguntas`
  MODIFY `idPregunta` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `test_resultados`
--
ALTER TABLE `test_resultados`
  MODIFY `idResultado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `apadrinamientos`
--
ALTER TABLE `apadrinamientos`
  ADD CONSTRAINT `fk_apad_mascota` FOREIGN KEY (`idMascota`) REFERENCES `mascotas` (`idMascota`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_apad_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_com_publicacion` FOREIGN KEY (`idPublicacion`) REFERENCES `publicaciones` (`idPublicacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_com_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_com_protectora` FOREIGN KEY (`idProtectora`) REFERENCES `protectoras` (`idProtectora`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `comentarios` (`idComentario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD CONSTRAINT `fk_don_protectora` FOREIGN KEY (`idProtectora`) REFERENCES `protectoras` (`idProtectora`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_don_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_fav_mascota` FOREIGN KEY (`idMascota`) REFERENCES `mascotas` (`idMascota`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `likes_comentarios`
--
ALTER TABLE `likes_comentarios`
  ADD CONSTRAINT `fk_like_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_like_protectora` FOREIGN KEY (`idProtectora`) REFERENCES `protectoras` (`idProtectora`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_like_comentario` FOREIGN KEY (`idComentario`) REFERENCES `comentarios` (`idComentario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes_publicaciones`
--
ALTER TABLE `likes_publicaciones`
  ADD CONSTRAINT `likes_publicaciones_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_publicaciones_ibfk_2` FOREIGN KEY (`idPublicacion`) REFERENCES `publicaciones` (`idPublicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `fk_mascota_protectora` FOREIGN KEY (`idProtectora`) REFERENCES `protectoras` (`idProtectora`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mascotas_fotos`
--
ALTER TABLE `mascotas_fotos`
  ADD CONSTRAINT `fk_foto_mascota` FOREIGN KEY (`idMascota`) REFERENCES `mascotas` (`idMascota`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `fk_pub_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `puntos_mapa`
--
ALTER TABLE `puntos_mapa`
  ADD CONSTRAINT `fk_punto_protectora` FOREIGN KEY (`idProtectora`) REFERENCES `protectoras` (`idProtectora`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimientos`
--
ALTER TABLE `seguimientos`
  ADD CONSTRAINT `fk_seg_apadrinamiento` FOREIGN KEY (`idApadrinamiento`) REFERENCES `apadrinamientos` (`idApadrinamiento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  ADD CONSTRAINT `fk_sol_mascota` FOREIGN KEY (`idMascota`) REFERENCES `mascotas` (`idMascota`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sol_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `test_opciones`
--
ALTER TABLE `test_opciones`
  ADD CONSTRAINT `fk_opcion_pregunta` FOREIGN KEY (`idPregunta`) REFERENCES `test_preguntas` (`idPregunta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `test_resultados`
--
ALTER TABLE `test_resultados`
  ADD CONSTRAINT `fk_res_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
