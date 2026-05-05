-- Migración: Flujo de solicitud de cuenta protectora
-- y permisos: Admin solo lectura/moderación, Protectora CRUD completo

ALTER TABLE `mascotas`
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `activa`;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `idLog` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id` INT(10) UNSIGNED NOT NULL,
    `actor_role` ENUM('admin','protectora','usuario') NOT NULL DEFAULT 'admin',
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` INT(10) UNSIGNED NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `detalles` TEXT DEFAULT NULL,
    `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idLog`),
    KEY `idx_actor` (`actor_id`),
    KEY `idx_target` (`target_type`, `target_id`),
    KEY `idx_fecha` (`fecha_registro`),
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `usuarios`(`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `solicitudes_protectora` (
    `idSolicitud` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idUsuario` INT(10) UNSIGNED NOT NULL,
    `nombre_protectora` VARCHAR(120) NOT NULL,
    `cif_nif` VARCHAR(20) NOT NULL,
    `direccion` VARCHAR(200) NOT NULL,
    `telefono` VARCHAR(20) DEFAULT NULL,
    `web_redes` VARCHAR(255) DEFAULT NULL,
    `documentacion` VARCHAR(255) DEFAULT NULL,
    `estado` ENUM('pendiente','aprobada','rechazada','info_adicional') NOT NULL DEFAULT 'pendiente',
    `respuesta_admin` TEXT DEFAULT NULL,
    `id_admin_responde` INT(10) UNSIGNED DEFAULT NULL,
    `fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_respuesta` DATETIME DEFAULT NULL,
    PRIMARY KEY (`idSolicitud`),
    KEY `idx_sprot_usuario` (`idUsuario`),
    KEY `idx_sprot_admin` (`id_admin_responde`),
    KEY `idx_sprot_estado` (`estado`),
    CONSTRAINT `fk_sprot_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`idUsuario`) ON DELETE CASCADE,
    CONSTRAINT `fk_sprot_admin` FOREIGN KEY (`id_admin_responde`) REFERENCES `usuarios`(`idUsuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `solicitudes_acogida` (
    `idSolicitud` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idUsuario` INT(10) UNSIGNED DEFAULT NULL,
    `idMascota` INT(10) UNSIGNED NOT NULL,
    `nombre` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `telefono` VARCHAR(20) DEFAULT NULL,
    `vivienda` ENUM('piso','casa_con_jardin','casa_sin_jardin','finca') NOT NULL,
    `experiencia` TEXT DEFAULT NULL,
    `tiempo` ENUM('menos_de_2h','2_a_4h','mas_de_4h','disponibilidad_completa') DEFAULT NULL,
    `mensaje` TEXT DEFAULT NULL,
    `estado` ENUM('pendiente','en_revision','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    `fecha_envio` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_gestion` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`idSolicitud`),
    KEY `idx_sacog_usuario` (`idUsuario`),
    KEY `idx_sacog_mascota` (`idMascota`),
    KEY `idx_sacog_estado` (`estado`),
    CONSTRAINT `fk_sacog_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`idUsuario`) ON DELETE SET NULL,
    CONSTRAINT `fk_sacog_mascota` FOREIGN KEY (`idMascota`) REFERENCES `mascotas`(`idMascota`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `apadrinamientos`
    ADD COLUMN IF NOT EXISTS `telefono` VARCHAR(20) DEFAULT NULL AFTER `email_pagador`,
    ADD COLUMN IF NOT EXISTS `mensaje` TEXT DEFAULT NULL AFTER `telefono`;

ALTER TABLE `protectoras`
    ADD COLUMN `idUsuario` INT(10) UNSIGNED DEFAULT NULL AFTER `activa`,
    ADD CONSTRAINT `fk_protectora_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios`(`idUsuario`) ON DELETE SET NULL;

ALTER TABLE `notificaciones`
    ADD COLUMN `ruta_destino` VARCHAR(255) DEFAULT NULL AFTER `mensaje`;

ALTER TABLE `solicitudes_protectora`
    ADD COLUMN `respuesta_usuario` TEXT DEFAULT NULL AFTER `respuesta_admin`,
    ADD COLUMN `fecha_respuesta_usuario` DATETIME DEFAULT NULL AFTER `respuesta_usuario`;

-- Usuarios para cada protectora (clave por defecto: misma que admin)
INSERT IGNORE INTO `usuarios` (`nombre`, `username`, `email`, `password_hash`, `rol`, `idProtectora`, `activo`, `fecha_registro`) VALUES
('Centro Protección Animal Gijón', 'centrogijon', 'centrogijon@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 1, 1, NOW()),
('Fundación Protectora Asturias', 'protectoraasturias', 'protectoraasturias@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 2, 1, NOW()),
('Asociación Felina La Esperanza', 'laesperanza', 'laesperanza@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 3, 1, NOW()),
('Más Que Chuchos', 'masquechuchos', 'masquechuchos@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 4, 1, NOW()),
('Nortemascotas', 'nortemascotas', 'nortemascotas@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 5, 1, NOW()),
('A.P.A Luz Felina', 'luzfelina', 'luzfelina@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 6, 1, NOW());
