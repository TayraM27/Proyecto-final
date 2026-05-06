/* Sistema de actualizaciones para animales apadrinados */

CREATE TABLE IF NOT EXISTS actualizaciones (
    idActualizacion INT AUTO_INCREMENT PRIMARY KEY,
    idMascota INT NOT NULL,
    idProtectora INT NOT NULL,
    mensaje TEXT NOT NULL,
    fotos JSON DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    eliminado TINYINT DEFAULT 0,
    INDEX idx_animal_id (idMascota),
    INDEX idx_protectora_id (idProtectora),
    FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota) ON DELETE CASCADE,
    FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS actualizacion_padrinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idActualizacion INT NOT NULL,
    idPadrino INT NOT NULL,
    leido TINYINT DEFAULT 0,
    fecha_leido DATETIME DEFAULT NULL,
    INDEX idx_padrino_id (idPadrino),
    INDEX idx_actualizacion_id (idActualizacion),
    FOREIGN KEY (idActualizacion) REFERENCES actualizaciones(idActualizacion) ON DELETE CASCADE,
    FOREIGN KEY (idPadrino) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS respuestas_actualizacion (
    idRespuesta INT AUTO_INCREMENT PRIMARY KEY,
    idActualizacion INT NOT NULL,
    idRemitente INT NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    eliminado TINYINT DEFAULT 0,
    INDEX idx_actualizacion_id (idActualizacion),
    FOREIGN KEY (idActualizacion) REFERENCES actualizaciones(idActualizacion) ON DELETE CASCADE,
    FOREIGN KEY (idRemitente) REFERENCES usuarios(idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
