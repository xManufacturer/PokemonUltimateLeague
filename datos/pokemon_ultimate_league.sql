-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-07-2026 a las 16:14:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pokemon_ultimate_league`
--
CREATE DATABASE IF NOT EXISTS `pokemon_ultimate_league` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pokemon_ultimate_league`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`id`, `usuario`, `password`) VALUES
(1, 'admin', '$2y$10$632nvsdJAlshxptP7kSSC.39KgunpPJBJOTADUhjL4CStZiirRxGG');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `competiciones`
--

DROP TABLE IF EXISTS `competiciones`;
CREATE TABLE IF NOT EXISTS `competiciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('liga','copa','mundial','legendary') NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `ruta` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `competiciones`
--

INSERT INTO `competiciones` (`id`, `nombre`, `tipo`, `activa`, `ruta`) VALUES
(1, 'Liga Kanto', 'liga', 1, 'kanto.php'),
(2, 'Liga Johto', 'liga', 1, 'johto.php'),
(3, 'Liga Hoenn', 'liga', 0, 'hoenn.php'),
(4, 'Liga Sinnoh', 'liga', 0, 'sinnoh.php'),
(5, 'Liga Teselia', 'liga', 0, 'teselia.php'),
(6, 'Liga Kalos', 'liga', 0, 'kalos.php'),
(7, 'Liga Alola', 'liga', 0, 'alola.php'),
(8, 'Liga Galar', 'liga', 0, 'galar.php'),
(9, 'Liga Paldea', 'liga', 0, 'paldea.php'),
(10, 'Legendary League', 'legendary', 1, 'legendary.php'),
(11, 'Champions League', 'copa', 1, 'champions.php'),
(12, 'Mundial', 'mundial', 1, 'mundial.php');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `competiciones_temporadas`
--

DROP TABLE IF EXISTS `competiciones_temporadas`;
CREATE TABLE IF NOT EXISTS `competiciones_temporadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_id` int(11) NOT NULL,
  `temporada_id` int(11) NOT NULL,
  `jornadas` int(11) DEFAULT NULL,
  `grupos` int(11) DEFAULT NULL,
  `sets_fase` int(11) DEFAULT NULL,
  `sets_final` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_competicion_temporada` (`competicion_id`,`temporada_id`),
  UNIQUE KEY `unica_temporada` (`competicion_id`,`temporada_id`),
  KEY `temporada_id` (`temporada_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `competiciones_temporadas`
--

INSERT INTO `competiciones_temporadas` (`id`, `competicion_id`, `temporada_id`, `jornadas`, `grupos`, `sets_fase`, `sets_final`, `fecha_actualizacion`) VALUES
(1, 1, 1, 19, NULL, 1, NULL, '2026-07-15 16:02:02'),
(2, 10, 1, 1, NULL, 3, 3, '2026-07-15 16:02:02'),
(3, 11, 1, 3, 2, 2, 3, '2026-07-15 16:02:02'),
(4, 1, 2, 19, NULL, 1, NULL, '2026-07-15 16:02:02'),
(5, 2, 2, 19, NULL, 1, NULL, '2026-07-15 16:02:02'),
(6, 10, 2, 4, NULL, 2, NULL, '2026-07-15 16:02:02'),
(7, 11, 2, 3, 4, 2, 3, '2026-07-15 16:02:02'),
(8, 12, 2, 1, NULL, 1, NULL, '2026-07-15 16:02:02'),
(9, 1, 3, 19, NULL, 1, NULL, '2026-07-15 16:02:02'),
(10, 2, 3, 19, NULL, 1, NULL, '2026-07-15 16:02:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mundial_ediciones`
--

DROP TABLE IF EXISTS `mundial_ediciones`;
CREATE TABLE IF NOT EXISTS `mundial_ediciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_temporada_id` int(11) NOT NULL,
  `region_campeona_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competicion_temporada_id` (`competicion_temporada_id`),
  KEY `region_campeona_id` (`region_campeona_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mundial_ediciones`
--

INSERT INTO `mundial_ediciones` (`id`, `competicion_temporada_id`, `region_campeona_id`) VALUES
(1, 8, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mundial_participantes`
--

DROP TABLE IF EXISTS `mundial_participantes`;
CREATE TABLE IF NOT EXISTS `mundial_participantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mundial_id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  `pokemon_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mundial_id` (`mundial_id`),
  KEY `region_id` (`region_id`),
  KEY `pokemon_id` (`pokemon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mundial_participantes`
--

INSERT INTO `mundial_participantes` (`id`, `mundial_id`, `region_id`, `pokemon_id`) VALUES
(1, 1, 1, 121),
(2, 1, 1, 145),
(3, 1, 1, 143),
(4, 1, 1, 144),
(5, 1, 1, 134),
(6, 1, 1, 135),
(7, 1, 2, 243),
(8, 1, 2, 245),
(9, 1, 2, 212),
(10, 1, 2, 157),
(11, 1, 2, 160),
(12, 1, 2, 230);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mundial_regiones`
--

DROP TABLE IF EXISTS `mundial_regiones`;
CREATE TABLE IF NOT EXISTS `mundial_regiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `imagen` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mundial_regiones`
--

INSERT INTO `mundial_regiones` (`id`, `nombre`, `imagen`) VALUES
(1, 'Kanto', NULL),
(2, 'Johto', NULL),
(3, 'Hoenn', NULL),
(4, 'Sinnoh', NULL),
(5, 'Teselia', NULL),
(6, 'Kalos', NULL),
(7, 'Alola', NULL),
(8, 'Galar', NULL),
(9, 'Paldea', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mundial_resultados`
--

DROP TABLE IF EXISTS `mundial_resultados`;
CREATE TABLE IF NOT EXISTS `mundial_resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mundial_id` int(11) NOT NULL,
  `region_local_id` int(11) NOT NULL,
  `region_visitante_id` int(11) NOT NULL,
  `puntos_local` int(11) NOT NULL,
  `puntos_visitante` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mundial_id` (`mundial_id`),
  KEY `region_local_id` (`region_local_id`),
  KEY `region_visitante_id` (`region_visitante_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mundial_resultados`
--

INSERT INTO `mundial_resultados` (`id`, `mundial_id`, `region_local_id`, `region_visitante_id`, `puntos_local`, `puntos_visitante`) VALUES
(1, 1, 1, 2, 6, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

DROP TABLE IF EXISTS `participantes`;
CREATE TABLE IF NOT EXISTS `participantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_temporada_id` int(11) NOT NULL,
  `pokemon_id` int(11) NOT NULL,
  `grupo` char(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participantes` (`competicion_temporada_id`,`pokemon_id`),
  KEY `pokemon_id` (`pokemon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `participantes`
--

INSERT INTO `participantes` (`id`, `competicion_temporada_id`, `pokemon_id`, `grupo`) VALUES
(1, 1, 3, NULL),
(2, 1, 38, NULL),
(3, 1, 59, NULL),
(4, 1, 65, NULL),
(5, 1, 73, NULL),
(6, 1, 91, NULL),
(7, 1, 94, NULL),
(8, 1, 97, NULL),
(9, 1, 103, NULL),
(10, 1, 121, NULL),
(11, 1, 130, NULL),
(12, 1, 131, NULL),
(13, 1, 134, NULL),
(14, 1, 135, NULL),
(15, 1, 136, NULL),
(16, 1, 139, NULL),
(17, 1, 144, NULL),
(18, 1, 145, NULL),
(19, 1, 146, NULL),
(20, 1, 149, NULL),
(21, 2, 150, NULL),
(22, 2, 151, NULL),
(23, 3, 65, 'GA'),
(24, 3, 73, 'GB'),
(25, 3, 94, 'GA'),
(26, 3, 97, 'GB'),
(27, 3, 131, 'GB'),
(28, 3, 145, 'GA'),
(29, 4, 3, NULL),
(30, 4, 38, NULL),
(31, 4, 59, NULL),
(32, 4, 65, NULL),
(33, 4, 73, NULL),
(34, 4, 94, NULL),
(35, 4, 97, NULL),
(36, 4, 103, NULL),
(37, 4, 121, NULL),
(38, 4, 124, NULL),
(39, 4, 130, NULL),
(40, 4, 131, NULL),
(41, 4, 134, NULL),
(42, 4, 135, NULL),
(43, 4, 139, NULL),
(44, 4, 143, NULL),
(45, 4, 144, NULL),
(46, 4, 145, NULL),
(47, 4, 146, NULL),
(48, 4, 149, NULL),
(49, 5, 154, NULL),
(50, 5, 157, NULL),
(51, 5, 160, NULL),
(52, 5, 169, NULL),
(53, 5, 181, NULL),
(54, 5, 186, NULL),
(55, 5, 196, NULL),
(56, 5, 197, NULL),
(57, 5, 208, NULL),
(58, 5, 212, NULL),
(59, 5, 213, NULL),
(60, 5, 229, NULL),
(61, 5, 230, NULL),
(62, 5, 232, NULL),
(63, 5, 233, NULL),
(64, 5, 242, NULL),
(65, 5, 243, NULL),
(66, 5, 244, NULL),
(67, 5, 245, NULL),
(68, 5, 248, NULL),
(69, 6, 150, NULL),
(70, 6, 151, NULL),
(71, 6, 249, NULL),
(72, 6, 250, NULL),
(73, 6, 251, NULL),
(74, 7, 65, 'GD'),
(75, 7, 94, 'GA'),
(76, 7, 121, 'GC'),
(77, 7, 134, 'GC'),
(78, 7, 135, 'GB'),
(79, 7, 143, 'GC'),
(80, 7, 157, 'GD'),
(81, 7, 160, 'GD'),
(82, 7, 212, 'GB'),
(83, 7, 230, 'GA'),
(84, 7, 243, 'GB'),
(85, 7, 245, 'GA'),
(86, 8, 121, NULL),
(87, 8, 134, NULL),
(88, 8, 135, NULL),
(89, 8, 143, NULL),
(90, 8, 144, NULL),
(91, 8, 145, NULL),
(92, 8, 157, NULL),
(93, 8, 160, NULL),
(94, 8, 212, NULL),
(95, 8, 230, NULL),
(96, 8, 243, NULL),
(97, 8, 245, NULL),
(98, 9, 3, NULL),
(99, 9, 59, NULL),
(100, 9, 65, NULL),
(101, 9, 73, NULL),
(102, 9, 94, NULL),
(103, 9, 97, NULL),
(104, 9, 103, NULL),
(105, 9, 114, NULL),
(106, 9, 121, NULL),
(107, 9, 124, NULL),
(108, 9, 128, NULL),
(109, 9, 131, NULL),
(110, 9, 134, NULL),
(111, 9, 135, NULL),
(112, 9, 139, NULL),
(113, 9, 143, NULL),
(114, 9, 144, NULL),
(115, 9, 145, NULL),
(116, 9, 146, NULL),
(117, 9, 149, NULL),
(118, 10, 154, NULL),
(119, 10, 157, NULL),
(120, 10, 160, NULL),
(121, 10, 181, NULL),
(122, 10, 186, NULL),
(123, 10, 196, NULL),
(124, 10, 197, NULL),
(125, 10, 200, NULL),
(126, 10, 208, NULL),
(127, 10, 212, NULL),
(128, 10, 214, NULL),
(129, 10, 229, NULL),
(130, 10, 230, NULL),
(131, 10, 233, NULL),
(132, 10, 242, NULL),
(133, 10, 243, NULL),
(134, 10, 244, NULL),
(135, 10, 245, NULL),
(136, 10, 248, NULL),
(137, 10, 251, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

DROP TABLE IF EXISTS `partidos`;
CREATE TABLE IF NOT EXISTS `partidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_temporada_id` int(11) NOT NULL,
  `fase` enum('L','GA','GB','GC','GD','GE','GF','GG','GH','SF','F') NOT NULL,
  `jornada` int(11) DEFAULT NULL,
  `local_id` int(11) NOT NULL,
  `visitante_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `local_id` (`local_id`),
  KEY `visitante_id` (`visitante_id`),
  KEY `partidos_ibfk_1` (`competicion_temporada_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plazas_especiales`
--

DROP TABLE IF EXISTS `plazas_especiales`;
CREATE TABLE IF NOT EXISTS `plazas_especiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_temporada_id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `competicion` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `participante_id` (`participante_id`),
  KEY `plazas_especiales_ibfk_1` (`competicion_temporada_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plazas_especiales`
--

INSERT INTO `plazas_especiales` (`id`, `competicion_temporada_id`, `participante_id`, `competicion`) VALUES
(4, 4, 32, 'Champions'),
(5, 4, 34, 'Champions'),
(6, 10, 120, 'Champions');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pokemon`
--

DROP TABLE IF EXISTS `pokemon`;
CREATE TABLE IF NOT EXISTS `pokemon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `generacion` int(11) NOT NULL,
  `tipo_primario` varchar(50) NOT NULL,
  `tipo_secundario` varchar(50) DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=387 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pokemon`
--

INSERT INTO `pokemon` (`id`, `nombre`, `generacion`, `tipo_primario`, `tipo_secundario`, `imagen`) VALUES
(1, 'Bulbasaur', 1, 'Planta', 'Veneno', 'bulbasaur.png'),
(2, 'Ivysaur', 1, 'Planta', 'Veneno', 'ivysaur.png'),
(3, 'Venusaur', 1, 'Planta', 'Veneno', 'venusaur.png'),
(4, 'Charmander', 1, 'Fuego', NULL, 'charmander.png'),
(5, 'Charmeleon', 1, 'Fuego', NULL, 'charmeleon.png'),
(6, 'Charizard', 1, 'Fuego', 'Volador', 'charizard.png'),
(7, 'Squirtle', 1, 'Agua', NULL, 'squirtle.png'),
(8, 'Wartortle', 1, 'Agua', NULL, 'wartortle.png'),
(9, 'Blastoise', 1, 'Agua', NULL, 'blastoise.png'),
(10, 'Caterpie', 1, 'Bicho', NULL, 'caterpie.png'),
(11, 'Metapod', 1, 'Bicho', NULL, 'metapod.png'),
(12, 'Butterfree', 1, 'Bicho', 'Volador', 'butterfree.png'),
(13, 'Weedle', 1, 'Bicho', 'Veneno', 'weedle.png'),
(14, 'Kakuna', 1, 'Bicho', 'Veneno', 'kakuna.png'),
(15, 'Beedrill', 1, 'Bicho', 'Veneno', 'beedrill.png'),
(16, 'Pidgey', 1, 'Normal', 'Volador', 'pidgey.png'),
(17, 'Pidgeotto', 1, 'Normal', 'Volador', 'pidgeotto.png'),
(18, 'Pidgeot', 1, 'Normal', 'Volador', 'pidgeot.png'),
(19, 'Rattata', 1, 'Normal', NULL, 'rattata.png'),
(20, 'Raticate', 1, 'Normal', NULL, 'raticate.png'),
(21, 'Spearow', 1, 'Normal', 'Volador', 'spearow.png'),
(22, 'Fearow', 1, 'Normal', 'Volador', 'fearow.png'),
(23, 'Ekans', 1, 'Veneno', NULL, 'ekans.png'),
(24, 'Arbok', 1, 'Veneno', NULL, 'arbok.png'),
(25, 'Pikachu', 1, 'Eléctrico', NULL, 'pikachu.png'),
(26, 'Raichu', 1, 'Eléctrico', NULL, 'raichu.png'),
(27, 'Sandshrew', 1, 'Tierra', NULL, 'sandshrew.png'),
(28, 'Sandslash', 1, 'Tierra', NULL, 'sandslash.png'),
(29, 'Nidoran-F', 1, 'Veneno', NULL, 'nidoran-f.png'),
(30, 'Nidorina', 1, 'Veneno', NULL, 'nidorina.png'),
(31, 'Nidoqueen', 1, 'Veneno', 'Tierra', 'nidoqueen.png'),
(32, 'Nidoran-M', 1, 'Veneno', NULL, 'nidoran-m.png'),
(33, 'Nidorino', 1, 'Veneno', NULL, 'nidorino.png'),
(34, 'Nidoking', 1, 'Veneno', 'Tierra', 'nidoking.png'),
(35, 'Clefairy', 1, 'Hada', NULL, 'clefairy.png'),
(36, 'Clefable', 1, 'Hada', NULL, 'clefable.png'),
(37, 'Vulpix', 1, 'Fuego', NULL, 'vulpix.png'),
(38, 'Ninetales', 1, 'Fuego', NULL, 'ninetales.png'),
(39, 'Jigglypuff', 1, 'Normal', 'Hada', 'jigglypuff.png'),
(40, 'Wigglytuff', 1, 'Normal', 'Hada', 'wigglytuff.png'),
(41, 'Zubat', 1, 'Veneno', 'Volador', 'zubat.png'),
(42, 'Golbat', 1, 'Veneno', 'Volador', 'golbat.png'),
(43, 'Oddish', 1, 'Planta', 'Veneno', 'oddish.png'),
(44, 'Gloom', 1, 'Planta', 'Veneno', 'gloom.png'),
(45, 'Vileplume', 1, 'Planta', 'Veneno', 'vileplume.png'),
(46, 'Paras', 1, 'Bicho', 'Planta', 'paras.png'),
(47, 'Parasect', 1, 'Bicho', 'Planta', 'parasect.png'),
(48, 'Venonat', 1, 'Bicho', 'Veneno', 'venonat.png'),
(49, 'Venomoth', 1, 'Bicho', 'Veneno', 'venomoth.png'),
(50, 'Diglett', 1, 'Tierra', NULL, 'diglett.png'),
(51, 'Dugtrio', 1, 'Tierra', NULL, 'dugtrio.png'),
(52, 'Meowth', 1, 'Normal', NULL, 'meowth.png'),
(53, 'Persian', 1, 'Normal', NULL, 'persian.png'),
(54, 'Psyduck', 1, 'Agua', NULL, 'psyduck.png'),
(55, 'Golduck', 1, 'Agua', NULL, 'golduck.png'),
(56, 'Mankey', 1, 'Lucha', NULL, 'mankey.png'),
(57, 'Primeape', 1, 'Lucha', NULL, 'primeape.png'),
(58, 'Growlithe', 1, 'Fuego', NULL, 'growlithe.png'),
(59, 'Arcanine', 1, 'Fuego', NULL, 'arcanine.png'),
(60, 'Poliwag', 1, 'Agua', NULL, 'poliwag.png'),
(61, 'Poliwhirl', 1, 'Agua', NULL, 'poliwhirl.png'),
(62, 'Poliwrath', 1, 'Agua', 'Lucha', 'poliwrath.png'),
(63, 'Abra', 1, 'Psíquico', NULL, 'abra.png'),
(64, 'Kadabra', 1, 'Psíquico', NULL, 'kadabra.png'),
(65, 'Alakazam', 1, 'Psíquico', NULL, 'alakazam.png'),
(66, 'Machop', 1, 'Lucha', NULL, 'machop.png'),
(67, 'Machoke', 1, 'Lucha', NULL, 'machoke.png'),
(68, 'Machamp', 1, 'Lucha', NULL, 'machamp.png'),
(69, 'Bellsprout', 1, 'Planta', 'Veneno', 'bellsprout.png'),
(70, 'Weepinbell', 1, 'Planta', 'Veneno', 'weepinbell.png'),
(71, 'Victreebel', 1, 'Planta', 'Veneno', 'victreebel.png'),
(72, 'Tentacool', 1, 'Agua', 'Veneno', 'tentacool.png'),
(73, 'Tentacruel', 1, 'Agua', 'Veneno', 'tentacruel.png'),
(74, 'Geodude', 1, 'Roca', 'Tierra', 'geodude.png'),
(75, 'Graveler', 1, 'Roca', 'Tierra', 'graveler.png'),
(76, 'Golem', 1, 'Roca', 'Tierra', 'golem.png'),
(77, 'Ponyta', 1, 'Fuego', NULL, 'ponyta.png'),
(78, 'Rapidash', 1, 'Fuego', NULL, 'rapidash.png'),
(79, 'Slowpoke', 1, 'Agua', 'Psíquico', 'slowpoke.png'),
(80, 'Slowbro', 1, 'Agua', 'Psíquico', 'slowbro.png'),
(81, 'Magnemite', 1, 'Eléctrico', 'Acero', 'magnemite.png'),
(82, 'Magneton', 1, 'Eléctrico', 'Acero', 'magneton.png'),
(83, 'Farfetch\'d', 1, 'Normal', 'Volador', 'farfetchd.png'),
(84, 'Doduo', 1, 'Normal', 'Volador', 'doduo.png'),
(85, 'Dodrio', 1, 'Normal', 'Volador', 'dodrio.png'),
(86, 'Seel', 1, 'Agua', NULL, 'seel.png'),
(87, 'Dewgong', 1, 'Agua', 'Hielo', 'dewgong.png'),
(88, 'Grimer', 1, 'Veneno', NULL, 'grimer.png'),
(89, 'Muk', 1, 'Veneno', NULL, 'muk.png'),
(90, 'Shellder', 1, 'Agua', NULL, 'shellder.png'),
(91, 'Cloyster', 1, 'Agua', 'Hielo', 'cloyster.png'),
(92, 'Gastly', 1, 'Fantasma', 'Veneno', 'gastly.png'),
(93, 'Haunter', 1, 'Fantasma', 'Veneno', 'haunter.png'),
(94, 'Gengar', 1, 'Fantasma', 'Veneno', 'gengar.png'),
(95, 'Onix', 1, 'Roca', 'Tierra', 'onix.png'),
(96, 'Drowzee', 1, 'Psíquico', NULL, 'drowzee.png'),
(97, 'Hypno', 1, 'Psíquico', NULL, 'hypno.png'),
(98, 'Krabby', 1, 'Agua', NULL, 'krabby.png'),
(99, 'Kingler', 1, 'Agua', NULL, 'kingler.png'),
(100, 'Voltorb', 1, 'Eléctrico', NULL, 'voltorb.png'),
(101, 'Electrode', 1, 'Eléctrico', NULL, 'electrode.png'),
(102, 'Exeggcute', 1, 'Planta', 'Psíquico', 'exeggcute.png'),
(103, 'Exeggutor', 1, 'Planta', 'Psíquico', 'exeggutor.png'),
(104, 'Cubone', 1, 'Tierra', NULL, 'cubone.png'),
(105, 'Marowak', 1, 'Tierra', NULL, 'marowak.png'),
(106, 'Hitmonlee', 1, 'Lucha', NULL, 'hitmonlee.png'),
(107, 'Hitmonchan', 1, 'Lucha', NULL, 'hitmonchan.png'),
(108, 'Lickitung', 1, 'Normal', NULL, 'lickitung.png'),
(109, 'Koffing', 1, 'Veneno', NULL, 'koffing.png'),
(110, 'Weezing', 1, 'Veneno', NULL, 'weezing.png'),
(111, 'Rhyhorn', 1, 'Tierra', 'Roca', 'rhyhorn.png'),
(112, 'Rhydon', 1, 'Tierra', 'Roca', 'rhydon.png'),
(113, 'Chansey', 1, 'Normal', NULL, 'chansey.png'),
(114, 'Tangela', 1, 'Planta', NULL, 'tangela.png'),
(115, 'Kangaskhan', 1, 'Normal', NULL, 'kangaskhan.png'),
(116, 'Horsea', 1, 'Agua', NULL, 'horsea.png'),
(117, 'Seadra', 1, 'Agua', NULL, 'seadra.png'),
(118, 'Goldeen', 1, 'Agua', NULL, 'goldeen.png'),
(119, 'Seaking', 1, 'Agua', NULL, 'seaking.png'),
(120, 'Staryu', 1, 'Agua', NULL, 'staryu.png'),
(121, 'Starmie', 1, 'Agua', 'Psíquico', 'starmie.png'),
(122, 'Mr. Mime', 1, 'Psíquico', 'Hada', 'mr_mime.png'),
(123, 'Scyther', 1, 'Bicho', 'Volador', 'scyther.png'),
(124, 'Jynx', 1, 'Hielo', 'Psíquico', 'jynx.png'),
(125, 'Electabuzz', 1, 'Eléctrico', NULL, 'electabuzz.png'),
(126, 'Magmar', 1, 'Fuego', NULL, 'magmar.png'),
(127, 'Pinsir', 1, 'Bicho', NULL, 'pinsir.png'),
(128, 'Tauros', 1, 'Normal', NULL, 'tauros.png'),
(129, 'Magikarp', 1, 'Agua', NULL, 'magikarp.png'),
(130, 'Gyarados', 1, 'Agua', 'Volador', 'gyarados.png'),
(131, 'Lapras', 1, 'Agua', 'Hielo', 'lapras.png'),
(132, 'Ditto', 1, 'Normal', NULL, 'ditto.png'),
(133, 'Eevee', 1, 'Normal', NULL, 'eevee.png'),
(134, 'Vaporeon', 1, 'Agua', NULL, 'vaporeon.png'),
(135, 'Jolteon', 1, 'Eléctrico', NULL, 'jolteon.png'),
(136, 'Flareon', 1, 'Fuego', NULL, 'flareon.png'),
(137, 'Porygon', 1, 'Normal', NULL, 'porygon.png'),
(138, 'Omanyte', 1, 'Roca', 'Agua', 'omanyte.png'),
(139, 'Omastar', 1, 'Roca', 'Agua', 'omastar.png'),
(140, 'Kabuto', 1, 'Roca', 'Agua', 'kabuto.png'),
(141, 'Kabutops', 1, 'Roca', 'Agua', 'kabutops.png'),
(142, 'Aerodactyl', 1, 'Roca', 'Volador', 'aerodactyl.png'),
(143, 'Snorlax', 1, 'Normal', NULL, 'snorlax.png'),
(144, 'Articuno', 1, 'Hielo', 'Volador', 'articuno.png'),
(145, 'Zapdos', 1, 'Eléctrico', 'Volador', 'zapdos.png'),
(146, 'Moltres', 1, 'Fuego', 'Volador', 'moltres.png'),
(147, 'Dratini', 1, 'Dragón', NULL, 'dratini.png'),
(148, 'Dragonair', 1, 'Dragón', NULL, 'dragonair.png'),
(149, 'Dragonite', 1, 'Dragón', 'Volador', 'dragonite.png'),
(150, 'Mewtwo', 1, 'Psíquico', NULL, 'mewtwo.png'),
(151, 'Mew', 1, 'Psíquico', NULL, 'mew.png'),
(152, 'Chikorita', 2, 'Planta', NULL, 'chikorita.png'),
(153, 'Bayleef', 2, 'Planta', NULL, 'bayleef.png'),
(154, 'Meganium', 2, 'Planta', NULL, 'meganium.png'),
(155, 'Cyndaquil', 2, 'Fuego', NULL, 'cyndaquil.png'),
(156, 'Quilava', 2, 'Fuego', NULL, 'quilava.png'),
(157, 'Typhlosion', 2, 'Fuego', NULL, 'typhlosion.png'),
(158, 'Totodile', 2, 'Agua', NULL, 'totodile.png'),
(159, 'Croconaw', 2, 'Agua', NULL, 'croconaw.png'),
(160, 'Feraligatr', 2, 'Agua', NULL, 'feraligatr.png'),
(161, 'Sentret', 2, 'Normal', NULL, 'sentret.png'),
(162, 'Furret', 2, 'Normal', NULL, 'furret.png'),
(163, 'Hoothoot', 2, 'Normal', 'Volador', 'hoothoot.png'),
(164, 'Noctowl', 2, 'Normal', 'Volador', 'noctowl.png'),
(165, 'Ledyba', 2, 'Bicho', 'Volador', 'ledyba.png'),
(166, 'Ledian', 2, 'Bicho', 'Volador', 'ledian.png'),
(167, 'Spinarak', 2, 'Bicho', 'Veneno', 'spinarak.png'),
(168, 'Ariados', 2, 'Bicho', 'Veneno', 'ariados.png'),
(169, 'Crobat', 2, 'Veneno', 'Volador', 'crobat.png'),
(170, 'Chinchou', 2, 'Agua', 'Eléctrico', 'chinchou.png'),
(171, 'Lanturn', 2, 'Agua', 'Eléctrico', 'lanturn.png'),
(172, 'Pichu', 2, 'Eléctrico', NULL, 'pichu.png'),
(173, 'Cleffa', 2, 'Hada', NULL, 'cleffa.png'),
(174, 'Igglybuff', 2, 'Normal', 'Hada', 'igglybuff.png'),
(175, 'Togepi', 2, 'Hada', NULL, 'togepi.png'),
(176, 'Togetic', 2, 'Hada', 'Volador', 'togetic.png'),
(177, 'Natu', 2, 'Psíquico', 'Volador', 'natu.png'),
(178, 'Xatu', 2, 'Psíquico', 'Volador', 'xatu.png'),
(179, 'Mareep', 2, 'Eléctrico', NULL, 'mareep.png'),
(180, 'Flaaffy', 2, 'Eléctrico', NULL, 'flaaffy.png'),
(181, 'Ampharos', 2, 'Eléctrico', NULL, 'ampharos.png'),
(182, 'Bellossom', 2, 'Planta', NULL, 'bellossom.png'),
(183, 'Marill', 2, 'Agua', 'Hada', 'marill.png'),
(184, 'Azumarill', 2, 'Agua', 'Hada', 'azumarill.png'),
(185, 'Sudowoodo', 2, 'Roca', NULL, 'sudowoodo.png'),
(186, 'Politoed', 2, 'Agua', NULL, 'politoed.png'),
(187, 'Hoppip', 2, 'Planta', 'Volador', 'hoppip.png'),
(188, 'Skiploom', 2, 'Planta', 'Volador', 'skiploom.png'),
(189, 'Jumpluff', 2, 'Planta', 'Volador', 'jumpluff.png'),
(190, 'Aipom', 2, 'Normal', NULL, 'aipom.png'),
(191, 'Sunkern', 2, 'Planta', NULL, 'sunkern.png'),
(192, 'Sunflora', 2, 'Planta', NULL, 'sunflora.png'),
(193, 'Yanma', 2, 'Bicho', 'Volador', 'yanma.png'),
(194, 'Wooper', 2, 'Agua', 'Tierra', 'wooper.png'),
(195, 'Quagsire', 2, 'Agua', 'Tierra', 'quagsire.png'),
(196, 'Espeon', 2, 'Psíquico', NULL, 'espeon.png'),
(197, 'Umbreon', 2, 'Siniestro', NULL, 'umbreon.png'),
(198, 'Murkrow', 2, 'Siniestro', 'Volador', 'murkrow.png'),
(199, 'Slowking', 2, 'Agua', 'Psíquico', 'slowking.png'),
(200, 'Misdreavus', 2, 'Fantasma', NULL, 'misdreavus.png'),
(201, 'Unown', 2, 'Psíquico', NULL, 'unown.png'),
(202, 'Wobbuffet', 2, 'Psíquico', NULL, 'wobbuffet.png'),
(203, 'Girafarig', 2, 'Normal', 'Psíquico', 'girafarig.png'),
(204, 'Pineco', 2, 'Bicho', NULL, 'pineco.png'),
(205, 'Forretress', 2, 'Bicho', 'Acero', 'forretress.png'),
(206, 'Dunsparce', 2, 'Normal', NULL, 'dunsparce.png'),
(207, 'Gligar', 2, 'Tierra', 'Volador', 'gligar.png'),
(208, 'Steelix', 2, 'Acero', 'Tierra', 'steelix.png'),
(209, 'Snubbull', 2, 'Hada', NULL, 'snubbull.png'),
(210, 'Granbull', 2, 'Hada', NULL, 'granbull.png'),
(211, 'Qwilfish', 2, 'Agua', 'Veneno', 'qwilfish.png'),
(212, 'Scizor', 2, 'Bicho', 'Acero', 'scizor.png'),
(213, 'Shuckle', 2, 'Bicho', 'Roca', 'shuckle.png'),
(214, 'Heracross', 2, 'Bicho', 'Lucha', 'heracross.png'),
(215, 'Sneasel', 2, 'Siniestro', 'Hielo', 'sneasel.png'),
(216, 'Teddiursa', 2, 'Normal', NULL, 'teddiursa.png'),
(217, 'Ursaring', 2, 'Normal', NULL, 'ursaring.png'),
(218, 'Slugma', 2, 'Fuego', NULL, 'slugma.png'),
(219, 'Magcargo', 2, 'Fuego', 'Roca', 'magcargo.png'),
(220, 'Swinub', 2, 'Hielo', 'Tierra', 'swinub.png'),
(221, 'Piloswine', 2, 'Hielo', 'Tierra', 'piloswine.png'),
(222, 'Corsola', 2, 'Agua', 'Roca', 'corsola.png'),
(223, 'Remoraid', 2, 'Agua', NULL, 'remoraid.png'),
(224, 'Octillery', 2, 'Agua', NULL, 'octillery.png'),
(225, 'Delibird', 2, 'Hielo', 'Volador', 'delibird.png'),
(226, 'Mantine', 2, 'Agua', 'Volador', 'mantine.png'),
(227, 'Skarmory', 2, 'Acero', 'Volador', 'skarmory.png'),
(228, 'Houndour', 2, 'Siniestro', 'Fuego', 'houndour.png'),
(229, 'Houndoom', 2, 'Siniestro', 'Fuego', 'houndoom.png'),
(230, 'Kingdra', 2, 'Agua', 'Dragón', 'kingdra.png'),
(231, 'Phanpy', 2, 'Tierra', NULL, 'phanpy.png'),
(232, 'Donphan', 2, 'Tierra', NULL, 'donphan.png'),
(233, 'Porygon2', 2, 'Normal', NULL, 'porygon2.png'),
(234, 'Stantler', 2, 'Normal', NULL, 'stantler.png'),
(235, 'Smeargle', 2, 'Normal', NULL, 'smeargle.png'),
(236, 'Tyrogue', 2, 'Lucha', NULL, 'tyrogue.png'),
(237, 'Hitmontop', 2, 'Lucha', NULL, 'hitmontop.png'),
(238, 'Smoochum', 2, 'Hielo', 'Psíquico', 'smoochum.png'),
(239, 'Elekid', 2, 'Eléctrico', NULL, 'elekid.png'),
(240, 'Magby', 2, 'Fuego', NULL, 'magby.png'),
(241, 'Miltank', 2, 'Normal', NULL, 'miltank.png'),
(242, 'Blissey', 2, 'Normal', NULL, 'blissey.png'),
(243, 'Raikou', 2, 'Eléctrico', NULL, 'raikou.png'),
(244, 'Entei', 2, 'Fuego', NULL, 'entei.png'),
(245, 'Suicune', 2, 'Agua', NULL, 'suicune.png'),
(246, 'Larvitar', 2, 'Roca', 'Tierra', 'larvitar.png'),
(247, 'Pupitar', 2, 'Roca', 'Tierra', 'pupitar.png'),
(248, 'Tyranitar', 2, 'Roca', 'Siniestro', 'tyranitar.png'),
(249, 'Lugia', 2, 'Psíquico', 'Volador', 'lugia.png'),
(250, 'Ho-Oh', 2, 'Fuego', 'Volador', 'ho-oh.png'),
(251, 'Celebi', 2, 'Psíquico', 'Planta', 'celebi.png'),
(252, 'Treecko', 3, 'Planta', NULL, 'treecko.png'),
(253, 'Grovyle', 3, 'Planta', NULL, 'grovyle.png'),
(254, 'Sceptile', 3, 'Planta', NULL, 'sceptile.png'),
(255, 'Torchic', 3, 'Fuego', NULL, 'torchic.png'),
(256, 'Combusken', 3, 'Fuego', 'Lucha', 'combusken.png'),
(257, 'Blaziken', 3, 'Fuego', 'Lucha', 'blaziken.png'),
(258, 'Mudkip', 3, 'Agua', NULL, 'mudkip.png'),
(259, 'Marshtomp', 3, 'Agua', 'Tierra', 'marshtomp.png'),
(260, 'Swampert', 3, 'Agua', 'Tierra', 'swampert.png'),
(261, 'Poochyena', 3, 'Siniestro', NULL, 'poochyena.png'),
(262, 'Mightyena', 3, 'Siniestro', NULL, 'mightyena.png'),
(263, 'Zigzagoon', 3, 'Normal', NULL, 'zigzagoon.png'),
(264, 'Linoone', 3, 'Normal', NULL, 'linoone.png'),
(265, 'Wurmple', 3, 'Bicho', NULL, 'wurmple.png'),
(266, 'Silcoon', 3, 'Bicho', NULL, 'silcoon.png'),
(267, 'Beautifly', 3, 'Bicho', 'Volador', 'beautifly.png'),
(268, 'Cascoon', 3, 'Bicho', NULL, 'cascoon.png'),
(269, 'Dustox', 3, 'Bicho', 'Veneno', 'dustox.png'),
(270, 'Lotad', 3, 'Agua', 'Planta', 'lotad.png'),
(271, 'Lombre', 3, 'Agua', 'Planta', 'lombre.png'),
(272, 'Ludicolo', 3, 'Agua', 'Planta', 'ludicolo.png'),
(273, 'Seedot', 3, 'Planta', NULL, 'seedot.png'),
(274, 'Nuzleaf', 3, 'Planta', 'Siniestro', 'nuzleaf.png'),
(275, 'Shiftry', 3, 'Planta', 'Siniestro', 'shiftry.png'),
(276, 'Taillow', 3, 'Normal', 'Volador', 'taillow.png'),
(277, 'Swellow', 3, 'Normal', 'Volador', 'swellow.png'),
(278, 'Wingull', 3, 'Agua', 'Volador', 'wingull.png'),
(279, 'Pelipper', 3, 'Agua', 'Volador', 'pelipper.png'),
(280, 'Ralts', 3, 'Psíquico', 'Hada', 'ralts.png'),
(281, 'Kirlia', 3, 'Psíquico', 'Hada', 'kirlia.png'),
(282, 'Gardevoir', 3, 'Psíquico', 'Hada', 'gardevoir.png'),
(283, 'Surskit', 3, 'Bicho', 'Agua', 'surskit.png'),
(284, 'Masquerain', 3, 'Bicho', 'Volador', 'masquerain.png'),
(285, 'Shroomish', 3, 'Planta', NULL, 'shroomish.png'),
(286, 'Breloom', 3, 'Planta', 'Lucha', 'breloom.png'),
(287, 'Slakoth', 3, 'Normal', NULL, 'slakoth.png'),
(288, 'Vigoroth', 3, 'Normal', NULL, 'vigoroth.png'),
(289, 'Slaking', 3, 'Normal', NULL, 'slaking.png'),
(290, 'Nincada', 3, 'Bicho', 'Tierra', 'nincada.png'),
(291, 'Ninjask', 3, 'Bicho', 'Volador', 'ninjask.png'),
(292, 'Shedinja', 3, 'Bicho', 'Fantasma', 'shedinja.png'),
(293, 'Whismur', 3, 'Normal', NULL, 'whismur.png'),
(294, 'Loudred', 3, 'Normal', NULL, 'loudred.png'),
(295, 'Exploud', 3, 'Normal', NULL, 'exploud.png'),
(296, 'Makuhita', 3, 'Lucha', NULL, 'makuhita.png'),
(297, 'Hariyama', 3, 'Lucha', NULL, 'hariyama.png'),
(298, 'Azurill', 3, 'Normal', 'Hada', 'azurill.png'),
(299, 'Nosepass', 3, 'Roca', NULL, 'nosepass.png'),
(300, 'Skitty', 3, 'Normal', NULL, 'skitty.png'),
(301, 'Delcatty', 3, 'Normal', NULL, 'delcatty.png'),
(302, 'Sableye', 3, 'Siniestro', 'Fantasma', 'sableye.png'),
(303, 'Mawile', 3, 'Acero', 'Hada', 'mawile.png'),
(304, 'Aron', 3, 'Acero', 'Roca', 'aron.png'),
(305, 'Lairon', 3, 'Acero', 'Roca', 'lairon.png'),
(306, 'Aggron', 3, 'Acero', 'Roca', 'aggron.png'),
(307, 'Meditite', 3, 'Lucha', 'Psíquico', 'meditite.png'),
(308, 'Medicham', 3, 'Lucha', 'Psíquico', 'medicham.png'),
(309, 'Electrike', 3, 'Eléctrico', NULL, 'electrike.png'),
(310, 'Manectric', 3, 'Eléctrico', NULL, 'manectric.png'),
(311, 'Plusle', 3, 'Eléctrico', NULL, 'plusle.png'),
(312, 'Minun', 3, 'Eléctrico', NULL, 'minun.png'),
(313, 'Volbeat', 3, 'Bicho', NULL, 'volbeat.png'),
(314, 'Illumise', 3, 'Bicho', NULL, 'illumise.png'),
(315, 'Roselia', 3, 'Planta', 'Veneno', 'roselia.png'),
(316, 'Gulpin', 3, 'Veneno', NULL, 'gulpin.png'),
(317, 'Swalot', 3, 'Veneno', NULL, 'swalot.png'),
(318, 'Carvanha', 3, 'Agua', 'Siniestro', 'carvanha.png'),
(319, 'Sharpedo', 3, 'Agua', 'Siniestro', 'sharpedo.png'),
(320, 'Wailmer', 3, 'Agua', NULL, 'wailmer.png'),
(321, 'Wailord', 3, 'Agua', NULL, 'wailord.png'),
(322, 'Numel', 3, 'Fuego', 'Tierra', 'numel.png'),
(323, 'Camerupt', 3, 'Fuego', 'Tierra', 'camerupt.png'),
(324, 'Torkoal', 3, 'Fuego', NULL, 'torkoal.png'),
(325, 'Spoink', 3, 'Psíquico', NULL, 'spoink.png'),
(326, 'Grumpig', 3, 'Psíquico', NULL, 'grumpig.png'),
(327, 'Spinda', 3, 'Normal', NULL, 'spinda.png'),
(328, 'Trapinch', 3, 'Tierra', NULL, 'trapinch.png'),
(329, 'Vibrava', 3, 'Tierra', 'Dragón', 'vibrava.png'),
(330, 'Flygon', 3, 'Tierra', 'Dragón', 'flygon.png'),
(331, 'Cacnea', 3, 'Planta', NULL, 'cacnea.png'),
(332, 'Cacturne', 3, 'Planta', 'Siniestro', 'cacturne.png'),
(333, 'Swablu', 3, 'Normal', 'Volador', 'swablu.png'),
(334, 'Altaria', 3, 'Dragón', 'Volador', 'altaria.png'),
(335, 'Zangoose', 3, 'Normal', NULL, 'zangoose.png'),
(336, 'Seviper', 3, 'Veneno', NULL, 'seviper.png'),
(337, 'Lunatone', 3, 'Roca', 'Psíquico', 'lunatone.png'),
(338, 'Solrock', 3, 'Roca', 'Psíquico', 'solrock.png'),
(339, 'Barboach', 3, 'Agua', 'Tierra', 'barboach.png'),
(340, 'Whiscash', 3, 'Agua', 'Tierra', 'whiscash.png'),
(341, 'Corphish', 3, 'Agua', NULL, 'corphish.png'),
(342, 'Crawdaunt', 3, 'Agua', 'Siniestro', 'crawdaunt.png'),
(343, 'Baltoy', 3, 'Tierra', 'Psíquico', 'baltoy.png'),
(344, 'Claydol', 3, 'Tierra', 'Psíquico', 'claydol.png'),
(345, 'Lileep', 3, 'Roca', 'Planta', 'lileep.png'),
(346, 'Cradily', 3, 'Roca', 'Planta', 'cradily.png'),
(347, 'Anorith', 3, 'Roca', 'Bicho', 'anorith.png'),
(348, 'Armaldo', 3, 'Roca', 'Bicho', 'armaldo.png'),
(349, 'Feebas', 3, 'Agua', NULL, 'feebas.png'),
(350, 'Milotic', 3, 'Agua', NULL, 'milotic.png'),
(351, 'Castform', 3, 'Normal', NULL, 'castform.png'),
(352, 'Kecleon', 3, 'Normal', NULL, 'kecleon.png'),
(353, 'Shuppet', 3, 'Fantasma', NULL, 'shuppet.png'),
(354, 'Banette', 3, 'Fantasma', NULL, 'banette.png'),
(355, 'Duskull', 3, 'Fantasma', NULL, 'duskull.png'),
(356, 'Dusclops', 3, 'Fantasma', NULL, 'dusclops.png'),
(357, 'Tropius', 3, 'Planta', 'Volador', 'tropius.png'),
(358, 'Chimecho', 3, 'Psíquico', NULL, 'chimecho.png'),
(359, 'Absol', 3, 'Siniestro', NULL, 'absol.png'),
(360, 'Wynaut', 3, 'Psíquico', NULL, 'wynaut.png'),
(361, 'Snorunt', 3, 'Hielo', NULL, 'snorunt.png'),
(362, 'Glalie', 3, 'Hielo', NULL, 'glalie.png'),
(363, 'Spheal', 3, 'Hielo', 'Agua', 'spheal.png'),
(364, 'Sealeo', 3, 'Hielo', 'Agua', 'sealeo.png'),
(365, 'Walrein', 3, 'Hielo', 'Agua', 'walrein.png'),
(366, 'Clamperl', 3, 'Agua', NULL, 'clamperl.png'),
(367, 'Huntail', 3, 'Agua', NULL, 'huntail.png'),
(368, 'Gorebyss', 3, 'Agua', NULL, 'gorebyss.png'),
(369, 'Relicanth', 3, 'Agua', 'Roca', 'relicanth.png'),
(370, 'Luvdisc', 3, 'Agua', NULL, 'luvdisc.png'),
(371, 'Bagon', 3, 'Dragón', NULL, 'bagon.png'),
(372, 'Shelgon', 3, 'Dragón', NULL, 'shelgon.png'),
(373, 'Salamence', 3, 'Dragón', 'Volador', 'salamence.png'),
(374, 'Beldum', 3, 'Acero', 'Psíquico', 'beldum.png'),
(375, 'Metang', 3, 'Acero', 'Psíquico', 'metang.png'),
(376, 'Metagross', 3, 'Acero', 'Psíquico', 'metagross.png'),
(377, 'Regirock', 3, 'Roca', NULL, 'regirock.png'),
(378, 'Regice', 3, 'Hielo', NULL, 'regice.png'),
(379, 'Registeel', 3, 'Acero', NULL, 'registeel.png'),
(380, 'Latias', 3, 'Dragón', 'Psíquico', 'latias.png'),
(381, 'Latios', 3, 'Dragón', 'Psíquico', 'latios.png'),
(382, 'Kyogre', 3, 'Agua', NULL, 'kyogre.png'),
(383, 'Groudon', 3, 'Tierra', NULL, 'groudon.png'),
(384, 'Rayquaza', 3, 'Dragón', 'Volador', 'rayquaza.png'),
(385, 'Jirachi', 3, 'Acero', 'Psíquico', 'jirachi.png'),
(386, 'Deoxys', 3, 'Psíquico', NULL, 'deoxys.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sets`
--

DROP TABLE IF EXISTS `sets`;
CREATE TABLE IF NOT EXISTS `sets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partido_id` int(11) NOT NULL,
  `numero_set` int(11) NOT NULL,
  `vida_local` int(11) DEFAULT NULL,
  `vida_visitante` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_partido_set` (`partido_id`,`numero_set`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temporadas`
--

DROP TABLE IF EXISTS `temporadas`;
CREATE TABLE IF NOT EXISTS `temporadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `estado` enum('en_curso','finalizada') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `temporadas`
--

INSERT INTO `temporadas` (`id`, `numero`, `estado`) VALUES
(1, 1, 'finalizada'),
(2, 2, 'finalizada'),
(3, 3, 'en_curso');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_clasificacion`
--

DROP TABLE IF EXISTS `zonas_clasificacion`;
CREATE TABLE IF NOT EXISTS `zonas_clasificacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competicion_temporada_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `posicion_inicio` int(11) NOT NULL,
  `posicion_fin` int(11) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zonas_clasificacion_ibfk_1` (`competicion_temporada_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `zonas_clasificacion`
--

INSERT INTO `zonas_clasificacion` (`id`, `competicion_temporada_id`, `nombre`, `posicion_inicio`, `posicion_fin`, `color`) VALUES
(1, 1, 'Campeón', 1, 1, 'amarillo'),
(2, 1, 'Champions', 1, 6, 'azul'),
(3, 1, 'Promoción descenso', 17, 19, 'naranja'),
(4, 1, 'Descenso', 20, 20, 'rojo'),
(5, 4, 'Campeón', 1, 1, 'amarillo'),
(6, 4, 'Champions', 1, 4, 'azul'),
(7, 4, 'Mundial', 1, 6, 'verde'),
(8, 4, 'Promoción descenso', 17, 19, 'naranja'),
(9, 4, 'Descenso', 20, 20, 'rojo'),
(10, 5, 'Campeón', 1, 1, 'amarillo'),
(11, 5, 'Champions', 1, 6, 'azul'),
(12, 5, 'Mundial', 1, 6, 'verde'),
(13, 5, 'Promoción descenso', 16, 19, 'naranja'),
(14, 5, 'Descenso', 20, 20, 'rojo'),
(15, 9, 'Campeón', 1, 1, 'amarillo'),
(16, 9, 'Champions', 1, 6, 'azul'),
(17, 9, 'Mundial', 1, 6, 'verde'),
(18, 9, 'Promoción descenso', 17, 19, 'naranja'),
(19, 9, 'Descenso', 20, 20, 'rojo'),
(20, 10, 'Campeón', 1, 1, 'amarillo'),
(21, 10, 'Champions', 1, 4, 'azul'),
(22, 10, 'Mundial', 1, 6, 'verde'),
(23, 10, 'Promoción descenso', 17, 19, 'naranja'),
(24, 10, 'Descenso', 20, 20, 'rojo'),
(25, 2, 'Campeón', 1, 1, 'amarillo'),
(26, 6, 'Campeón', 1, 1, 'amarillo'),
(27, 3, 'Campeón', 1, 1, 'amarillo'),
(28, 7, 'Campeón', 1, 1, 'amarillo');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `competiciones_temporadas`
--
ALTER TABLE `competiciones_temporadas`
  ADD CONSTRAINT `competiciones_temporadas_ibfk_1` FOREIGN KEY (`competicion_id`) REFERENCES `competiciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `competiciones_temporadas_ibfk_2` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mundial_ediciones`
--
ALTER TABLE `mundial_ediciones`
  ADD CONSTRAINT `mundial_ediciones_ibfk_1` FOREIGN KEY (`competicion_temporada_id`) REFERENCES `competiciones_temporadas` (`id`),
  ADD CONSTRAINT `mundial_ediciones_ibfk_2` FOREIGN KEY (`region_campeona_id`) REFERENCES `mundial_regiones` (`id`);

--
-- Filtros para la tabla `mundial_participantes`
--
ALTER TABLE `mundial_participantes`
  ADD CONSTRAINT `mundial_participantes_ibfk_1` FOREIGN KEY (`mundial_id`) REFERENCES `mundial_ediciones` (`id`),
  ADD CONSTRAINT `mundial_participantes_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `mundial_regiones` (`id`),
  ADD CONSTRAINT `mundial_participantes_ibfk_3` FOREIGN KEY (`pokemon_id`) REFERENCES `pokemon` (`id`);

--
-- Filtros para la tabla `mundial_resultados`
--
ALTER TABLE `mundial_resultados`
  ADD CONSTRAINT `mundial_resultados_ibfk_1` FOREIGN KEY (`mundial_id`) REFERENCES `mundial_ediciones` (`id`),
  ADD CONSTRAINT `mundial_resultados_ibfk_2` FOREIGN KEY (`region_local_id`) REFERENCES `mundial_regiones` (`id`),
  ADD CONSTRAINT `mundial_resultados_ibfk_3` FOREIGN KEY (`region_visitante_id`) REFERENCES `mundial_regiones` (`id`);

--
-- Filtros para la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD CONSTRAINT `participantes_ibfk_1` FOREIGN KEY (`competicion_temporada_id`) REFERENCES `competiciones_temporadas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participantes_ibfk_2` FOREIGN KEY (`pokemon_id`) REFERENCES `pokemon` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`competicion_temporada_id`) REFERENCES `competiciones_temporadas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`local_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`visitante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plazas_especiales`
--
ALTER TABLE `plazas_especiales`
  ADD CONSTRAINT `plazas_especiales_ibfk_1` FOREIGN KEY (`competicion_temporada_id`) REFERENCES `competiciones_temporadas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plazas_especiales_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sets`
--
ALTER TABLE `sets`
  ADD CONSTRAINT `sets_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `zonas_clasificacion`
--
ALTER TABLE `zonas_clasificacion`
  ADD CONSTRAINT `zonas_clasificacion_ibfk_1` FOREIGN KEY (`competicion_temporada_id`) REFERENCES `competiciones_temporadas` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
