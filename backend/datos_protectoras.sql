-- ====================================================================
-- Datos: Usuarios para las 6 protectoras existentes (password: Admin123!)
-- Ejecutar DESPUES de migracion_roles.sql
-- ====================================================================

-- 1. Usuarios para cada protectora
INSERT IGNORE INTO `usuarios` (`idUsuario`, `nombre`, `username`, `email`, `password_hash`, `rol`, `idProtectora`, `activo`, `fecha_registro`) VALUES
(10, 'Centro Proteccion Animal Gijon', 'cpagijon', 'cpagijon@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 1, 1, NOW()),
(11, 'Fundacion Protectora Asturias', 'fpa', 'fpa@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 2, 1, NOW()),
(12, 'Asociacion Felina La Esperanza', 'laesperanza', 'laesperanza@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 3, 1, NOW()),
(13, 'Mas Que Chuchos', 'masquechuchos', 'masquechuchos@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 4, 1, NOW()),
(14, 'Nortemascotas', 'nortemascotas', 'nortemascotas@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 5, 1, NOW()),
(15, 'APA Luz Felina', 'luzfelina', 'luzfelina@yopmail.es', '$2y$10$NLZ/nofF1Ubb.fwbaQlocu.LtowE5aqeFZkSR.EXUfve5UszbMSlW', 'protectora', 6, 1, NOW());

-- 2. Vincular usuarios a protectoras
UPDATE `usuarios` SET `idProtectora` = 1 WHERE `idUsuario` = 10 AND `idProtectora` IS NULL;
UPDATE `usuarios` SET `idProtectora` = 2 WHERE `idUsuario` = 11 AND `idProtectora` IS NULL;
UPDATE `usuarios` SET `idProtectora` = 3 WHERE `idUsuario` = 12 AND `idProtectora` IS NULL;
UPDATE `usuarios` SET `idProtectora` = 4 WHERE `idUsuario` = 13 AND `idProtectora` IS NULL;
UPDATE `usuarios` SET `idProtectora` = 5 WHERE `idUsuario` = 14 AND `idProtectora` IS NULL;
UPDATE `usuarios` SET `idProtectora` = 6 WHERE `idUsuario` = 15 AND `idProtectora` IS NULL;

-- 3. Vincular protectoras a sus usuarios
UPDATE `protectoras` SET `idUsuario` = 10 WHERE `idProtectora` = 1 AND `idUsuario` IS NULL;
UPDATE `protectoras` SET `idUsuario` = 11 WHERE `idProtectora` = 2 AND `idUsuario` IS NULL;
UPDATE `protectoras` SET `idUsuario` = 12 WHERE `idProtectora` = 3 AND `idUsuario` IS NULL;
UPDATE `protectoras` SET `idUsuario` = 13 WHERE `idProtectora` = 4 AND `idUsuario` IS NULL;
UPDATE `protectoras` SET `idUsuario` = 14 WHERE `idProtectora` = 5 AND `idUsuario` IS NULL;
UPDATE `protectoras` SET `idUsuario` = 15 WHERE `idProtectora` = 6 AND `idUsuario` IS NULL;
