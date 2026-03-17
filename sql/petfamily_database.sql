-- ============================================================
--  PetFamily – Base de datos completa
--  Motor: MySQL 8.0+
--  Charset: utf8mb4 (soporta emojis y acentos)
--  Generado según modelo E/R del proyecto PetFamily (2025/26)
-- ============================================================

CREATE DATABASE IF NOT EXISTS petfamily
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE petfamily;

-- ────────────────────────────────────────────────────────────
-- 1. PROTECTORAS
--    Organizaciones que gestionan animales y verifican adopciones
-- ────────────────────────────────────────────────────────────
CREATE TABLE protectoras (
    idProtectora   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120)  NOT NULL,
    descripcion    TEXT,
    direccion      VARCHAR(200),
    localidad      VARCHAR(80),
    telefono       VARCHAR(20),
    email          VARCHAR(120)  UNIQUE,
    web            VARCHAR(200),
    foto_logo      VARCHAR(255),                           -- ruta relativa img/
    latitud        DECIMAL(10,7),                         -- para el mapa
    longitud       DECIMAL(10,7),
    verificada     TINYINT(1)    NOT NULL DEFAULT 0,
    activa         TINYINT(1)    NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 2. USUARIOS
--    Visitantes registrados; el rol distingue usuario / admin
-- ────────────────────────────────────────────────────────────
CREATE TABLE usuarios (
    idUsuario      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(80)   NOT NULL,
    apellidos      VARCHAR(120),
    email          VARCHAR(120)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255)  NOT NULL,              -- password_hash() PHP
    telefono       VARCHAR(20),
    foto_perfil    VARCHAR(255),
    rol            ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    activo         TINYINT(1)    NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login   TIMESTAMP     NULL
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 3. MASCOTAS
--    Fichas de perros y gatos en adopción
-- ────────────────────────────────────────────────────────────
CREATE TABLE mascotas (
    idMascota         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idProtectora      INT UNSIGNED NOT NULL,
    nombre            VARCHAR(80)  NOT NULL,
    especie           ENUM('perro','gato') NOT NULL,
    raza              VARCHAR(100),
    sexo              ENUM('macho','hembra') NOT NULL,
    fecha_nacimiento  DATE,
    tamanyo           ENUM('pequeño','mediano','grande') NOT NULL,
    color             VARCHAR(80),
    descripcion       TEXT,
    estado_salud      VARCHAR(120),           -- 'Bueno', 'En tratamiento', etc.
    -- Estado de adopción
    urgencia          ENUM('normal','urgente','nuevo') NOT NULL DEFAULT 'normal',
    estado_adopcion   ENUM('disponible','en_proceso','adoptado') NOT NULL DEFAULT 'disponible',
    disponible_apadrinamiento TINYINT(1) NOT NULL DEFAULT 1,
    -- Características de compatibilidad (para test)
    compatible_ninos  TINYINT(1) NOT NULL DEFAULT 0,
    compatible_perros TINYINT(1) NOT NULL DEFAULT 0,
    compatible_gatos  TINYINT(1) NOT NULL DEFAULT 0,
    apto_piso         TINYINT(1) NOT NULL DEFAULT 0,
    -- Atributos de salud (badges del HTML)
    vacunado          TINYINT(1) NOT NULL DEFAULT 0,
    esterilizado      TINYINT(1) NOT NULL DEFAULT 0,
    microchip         TINYINT(1) NOT NULL DEFAULT 0,
    desparasitado     TINYINT(1) NOT NULL DEFAULT 0,
    -- Estadísticas
    num_vistas        INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_entrada     DATE,                              -- cuándo llegó a la protectora
    fecha_publicacion TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activa            TINYINT(1)  NOT NULL DEFAULT 1,

    CONSTRAINT fk_mascota_protectora
        FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 4. FOTOS DE MASCOTAS
--    Galería de imágenes de cada animal (miniatura y principal)
-- ────────────────────────────────────────────────────────────
CREATE TABLE mascotas_fotos (
    idFoto       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idMascota    INT UNSIGNED NOT NULL,
    ruta         VARCHAR(255) NOT NULL,
    es_principal TINYINT(1)   NOT NULL DEFAULT 0,
    orden        TINYINT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT fk_foto_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 5. FAVORITOS
--    Mascotas guardadas por un usuario registrado
-- ────────────────────────────────────────────────────────────
CREATE TABLE favoritos (
    idUsuario  INT UNSIGNED NOT NULL,
    idMascota  INT UNSIGNED NOT NULL,
    fecha      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (idUsuario, idMascota),

    CONSTRAINT fk_fav_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_fav_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 6. SOLICITUDES DE ADOPCIÓN
--    Registro del proceso cuando un usuario quiere adoptar
-- ────────────────────────────────────────────────────────────
CREATE TABLE solicitudes_adopcion (
    idSolicitud   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario     INT UNSIGNED NOT NULL,
    idMascota     INT UNSIGNED NOT NULL,
    -- Datos del formulario de adopción
    nombre        VARCHAR(120) NOT NULL,
    email         VARCHAR(120) NOT NULL,
    telefono      VARCHAR(20),
    mensaje       TEXT,
    estado        ENUM('pendiente','en_revision','aprobada','rechazada')
                  NOT NULL DEFAULT 'pendiente',
    fecha_envio   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_gestion TIMESTAMP NULL,

    CONSTRAINT fk_sol_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sol_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 7. APADRINAMIENTOS
--    Relación N:M entre Usuario y Mascota con atributos propios
-- ────────────────────────────────────────────────────────────
CREATE TABLE apadrinamientos (
    idApadrinamiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario        INT UNSIGNED NOT NULL,
    idMascota        INT UNSIGNED NOT NULL,
    cuota            DECIMAL(6,2) NOT NULL,             -- 3, 5, 8 o 10 €/mes
    fecha_inicio     DATE         NOT NULL,
    fecha_fin        DATE         NULL,                 -- NULL = activo
    estado           ENUM('activo','cancelado','pausado') NOT NULL DEFAULT 'activo',
    -- Datos del pagador (puede ser anónimo o distinto al usuario)
    nombre_pagador   VARCHAR(120),
    email_pagador    VARCHAR(120),
    referencia_pago  VARCHAR(100),                      -- ID de pasarela externa

    CONSTRAINT fk_apad_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_apad_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 8. SEGUIMIENTOS DE APADRINAMIENTO
--    Fotos, vídeos y actualizaciones que la protectora sube
-- ────────────────────────────────────────────────────────────
CREATE TABLE seguimientos (
    idSeguimiento    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idApadrinamiento INT UNSIGNED NOT NULL,
    contenido        TEXT,
    tipo_archivo     ENUM('foto','video','texto') NOT NULL DEFAULT 'texto',
    ruta_archivo     VARCHAR(255),
    fecha            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_seg_apadrinamiento
        FOREIGN KEY (idApadrinamiento) REFERENCES apadrinamientos(idApadrinamiento)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 9. DONACIONES
--    Donaciones puntuales (no periódicas)
-- ────────────────────────────────────────────────────────────
CREATE TABLE donaciones (
    idDonacion      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario       INT UNSIGNED NULL,                  -- NULL si es anónima
    idProtectora    INT UNSIGNED NULL,                  -- NULL = fondo general
    nombre_donante  VARCHAR(120),
    email_donante   VARCHAR(120),
    importe         DECIMAL(8,2) NOT NULL,
    mensaje         VARCHAR(300),
    referencia_pago VARCHAR(100),
    estado          ENUM('pendiente','completada','fallida') NOT NULL DEFAULT 'pendiente',
    fecha           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_don_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_don_protectora
        FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 10. FORO – PUBLICACIONES
-- ────────────────────────────────────────────────────────────
CREATE TABLE publicaciones (
    idPublicacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario     INT UNSIGNED NOT NULL,
    titulo        VARCHAR(200) NOT NULL,
    contenido     TEXT         NOT NULL,
    imagen        VARCHAR(255),
    categoria     VARCHAR(80),
    num_likes     INT UNSIGNED NOT NULL DEFAULT 0,
    num_vistas    INT UNSIGNED NOT NULL DEFAULT 0,
    fijada        TINYINT(1)   NOT NULL DEFAULT 0,      -- publicación anclada por admin
    activa        TINYINT(1)   NOT NULL DEFAULT 1,
    fecha         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pub_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 11. FORO – COMENTARIOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE comentarios (
    idComentario  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idPublicacion INT UNSIGNED NOT NULL,
    idUsuario     INT UNSIGNED NOT NULL,
    contenido     TEXT         NOT NULL,
    num_likes     INT UNSIGNED NOT NULL DEFAULT 0,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    fecha         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_com_publicacion
        FOREIGN KEY (idPublicacion) REFERENCES publicaciones(idPublicacion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_com_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 12. PUNTOS DEL MAPA INTERACTIVO
--    Protectoras, veterinarias, tiendas, puntos de donación
-- ────────────────────────────────────────────────────────────
CREATE TABLE puntos_mapa (
    idPunto      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    categoria    ENUM('protectora','veterinaria','tienda','donacion','refugio')
                 NOT NULL,
    descripcion  TEXT,
    direccion    VARCHAR(200),
    localidad    VARCHAR(80),
    telefono     VARCHAR(20),
    email        VARCHAR(120),
    web          VARCHAR(200),
    latitud      DECIMAL(10,7) NOT NULL,
    longitud     DECIMAL(10,7) NOT NULL,
    activo       TINYINT(1)    NOT NULL DEFAULT 1,
    -- Relación opcional con protectora registrada
    idProtectora INT UNSIGNED NULL,

    CONSTRAINT fk_punto_protectora
        FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 13. TEST DE COMPATIBILIDAD – PREGUNTAS
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_preguntas (
    idPregunta INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_test  ENUM('compatibilidad','conocimiento') NOT NULL,
    texto      VARCHAR(400) NOT NULL,
    orden      TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 14. TEST DE COMPATIBILIDAD – OPCIONES DE RESPUESTA
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_opciones (
    idOpcion    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idPregunta  INT UNSIGNED NOT NULL,
    texto       VARCHAR(300) NOT NULL,
    valor       VARCHAR(80),      -- clave para el algoritmo de compatibilidad
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,  -- para test de conocimiento

    CONSTRAINT fk_opcion_pregunta
        FOREIGN KEY (idPregunta) REFERENCES test_preguntas(idPregunta)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 15. RESULTADOS DE TEST
--    Solo para usuarios registrados (compatibilidad)
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_resultados (
    idResultado       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario         INT UNSIGNED NOT NULL,
    tipo_test         ENUM('compatibilidad','conocimiento') NOT NULL,
    respuestas_json   JSON,                              -- respuestas guardadas
    puntuacion        TINYINT UNSIGNED,                  -- para test de conocimiento
    fecha             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_res_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 16. MENSAJES DE COLABORA / CONTACTO
--    Formulario de la página colabora.html
-- ────────────────────────────────────────────────────────────
CREATE TABLE mensajes_contacto (
    idMensaje    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    email        VARCHAR(120) NOT NULL,
    asunto       VARCHAR(200),
    mensaje      TEXT         NOT NULL,
    tipo         ENUM('colaboracion','protectora','otro') NOT NULL DEFAULT 'otro',
    leido        TINYINT(1)   NOT NULL DEFAULT 0,
    fecha        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- ÍNDICES ADICIONALES (rendimiento en filtros frecuentes)
-- ────────────────────────────────────────────────────────────
CREATE INDEX idx_mascotas_especie        ON mascotas(especie);
CREATE INDEX idx_mascotas_urgencia       ON mascotas(urgencia);
CREATE INDEX idx_mascotas_estado         ON mascotas(estado_adopcion);
CREATE INDEX idx_mascotas_protectora     ON mascotas(idProtectora);
CREATE INDEX idx_mascotas_apadrinamiento ON mascotas(disponible_apadrinamiento);
CREATE INDEX idx_pub_usuario             ON publicaciones(idUsuario);
CREATE INDEX idx_com_publicacion         ON comentarios(idPublicacion);
CREATE INDEX idx_apad_estado             ON apadrinamientos(estado);
CREATE INDEX idx_don_estado              ON donaciones(estado);


-- ────────────────────────────────────────────────────────────
-- DATOS DE EJEMPLO – PROTECTORA
-- ────────────────────────────────────────────────────────────
INSERT INTO protectoras (nombre, descripcion, localidad, telefono, email, latitud, longitud, verificada)
VALUES
('Protectora Norte Mascotas', 'Asociación sin ánimo de lucro en Gijón', 'Gijón',    '984123456', 'info@nortemascotas.com', 43.5322, -5.6611, 1),
('Protectora de Asturias',    'Refugio central de la región',            'Oviedo',   '985234567', 'contacto@protectoraasturias.es', 43.3614, -5.8496, 1),
('Albergue de Animales',      'Albergue municipal de animales',          'Avilés',   '985345678', 'albergue@aviles.es', 43.5547, -5.9249, 1);


-- ────────────────────────────────────────────────────────────
-- DATOS DE EJEMPLO – USUARIO ADMIN
-- Contraseña: Admin1234! (hash bcrypt generado con password_hash())
-- ────────────────────────────────────────────────────────────
INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol)
VALUES ('Admin', 'PetFamily', 'admin@petfamily.es',
        '$2y$12$placeholderHashCambiarEnProduccion000000000000000000000000', 'admin');


-- ────────────────────────────────────────────────────────────
-- DATOS DE EJEMPLO – MASCOTAS (coinciden con las tarjetas del HTML)
-- ────────────────────────────────────────────────────────────
INSERT INTO mascotas
    (idProtectora, nombre, especie, raza, sexo, tamanyo, color, descripcion,
     estado_salud, urgencia, estado_adopcion, disponible_apadrinamiento,
     compatible_ninos, compatible_perros, compatible_gatos, apto_piso,
     vacunado, esterilizado, microchip, num_vistas, fecha_entrada)
VALUES
(1, 'Rody',   'perro', 'Cruce de Pastor Alemán', 'macho',  'mediano', 'Marrón y negro',
 'Rody es un perro mayor lleno de amor y tranquilidad.',
 'Bueno',  'urgente',  'disponible', 1,  1, 0, 1, 1, 1, 1, 1, 0, '2022-03-10'),
(2, 'Luna',   'gato',  'Común europeo',          'hembra', 'pequeño', 'Gris y blanco',
 'Luna es una gatita curiosa y cariñosa.',
 'Bueno',  'nuevo',    'disponible', 1,  1, 1, 0, 1, 1, 1, 1, 0, '2024-11-01'),
(1, 'Max',    'perro', 'Labrador',               'macho',  'grande',  'Dorado',
 'Max es un perro activo y muy sociable.',
 'Bueno',  'normal',   'disponible', 1,  1, 1, 1, 0, 1, 1, 1, 0, '2023-06-15'),
(3, 'Misu',   'gato',  'Siamés',                 'hembra', 'pequeño', 'Crema y marrón',
 'Misu busca un hogar tranquilo.',
 'Bueno',  'normal',   'disponible', 1,  0, 0, 1, 1, 1, 1, 1, 0, '2024-08-20'),
(1, 'Dexter', 'perro', 'Cruce Pastor Alemán',    'macho',  'mediano', 'Negro y marrón',
 'Dexter lleva muchos años esperando su familia.',
 'Bueno',  'urgente',  'disponible', 0,  1, 0, 1, 1, 1, 1, 1, 0, '2015-05-05'),
(2, 'Bosque', 'gato',  'Común europeo',          'hembra', 'pequeño', 'Atigrado',
 'Bosque necesita un hogar que conozca y acepte la leucemia felina.',
 'Leucemia felina+', 'normal', 'disponible', 1, 0, 0, 0, 1, 1, 1, 1, 0, '2024-12-01');


-- Fotos principales de las mascotas de ejemplo
INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden) VALUES
(1, 'img/mascotas/albergue-animales-dog.jpg',       1, 0),
(1, 'img/mascotas/nortemascotas-dog.jpg',            0, 1),
(1, 'img/mascotas/protectora-de-asturias-dog.jpg',   0, 2),
(2, 'img/mascotas/protectora-de-asturias-cat.jpg',   1, 0),
(3, 'img/mascotas/nortemascotas-dog.jpg',            1, 0),
(4, 'img/mascotas/albergue-animales-cat.jpg',        1, 0),
(5, 'img/mascotas/nortemascotas-dog.jpg',            1, 0),
(6, 'img/mascotas/protectora-de-asturias-cat.jpg',   1, 0);