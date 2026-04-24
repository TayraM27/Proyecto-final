-- ============================================================
--  PetFamily — Base de datos completa
--  Motor: MariaDB 10.4 / MySQL 8.0+
--  Charset: utf8mb4
--  Actualizada con todos los cambios aplicados en sesión
-- ============================================================

CREATE DATABASE IF NOT EXISTS petfamily
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE petfamily;

-- ────────────────────────────────────────────────────────────
-- 1. PROTECTORAS
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
    tipo_pagina    ENUM('web','red_social','otra','sin_pagina') NOT NULL DEFAULT 'sin_pagina',
    iban           VARCHAR(34),
    bizum          VARCHAR(9),
    teaming        VARCHAR(200),
    foto_logo      VARCHAR(255),
    latitud        DECIMAL(10,7),
    longitud       DECIMAL(10,7),
    verificada     TINYINT(1)    NOT NULL DEFAULT 0,
    activa         TINYINT(1)    NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 2. USUARIOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE usuarios (
    idUsuario      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(80)   NOT NULL,
    apellidos      VARCHAR(120),
    username       VARCHAR(80)   UNIQUE,
    email          VARCHAR(120)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255)  NOT NULL,
    localidad      VARCHAR(80),
    telefono       VARCHAR(20),
    foto_perfil    VARCHAR(255),
    google_id      VARCHAR(100)  UNIQUE,
    rol            ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    activo         TINYINT(1)    NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login   TIMESTAMP     NULL
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 3. MASCOTAS
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
    estado_salud      VARCHAR(120),
    urgencia          ENUM('normal','urgente','nuevo') NOT NULL DEFAULT 'normal',
    estado_adopcion   ENUM('disponible','en_proceso','adoptado','no_disponible') NOT NULL DEFAULT 'disponible',
    disponible_apadrinamiento TINYINT(1) NOT NULL DEFAULT 1,
    disponible_acogida        TINYINT(1) NOT NULL DEFAULT 1,
    compatible_ninos  TINYINT(1) NOT NULL DEFAULT 0,
    compatible_perros TINYINT(1) NOT NULL DEFAULT 0,
    compatible_gatos  TINYINT(1) NOT NULL DEFAULT 0,
    apto_piso         TINYINT(1) NOT NULL DEFAULT 0,
    vacunado          TINYINT(1) NOT NULL DEFAULT 0,
    esterilizado      TINYINT(1) NOT NULL DEFAULT 0,
    microchip         TINYINT(1) NOT NULL DEFAULT 0,
    desparasitado     TINYINT(1) NOT NULL DEFAULT 0,
    badge_extra       VARCHAR(120),
    edad_texto        VARCHAR(40),
    num_vistas        INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_entrada     DATE,
    fecha_publicacion TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activa            TINYINT(1)  NOT NULL DEFAULT 1,

    CONSTRAINT fk_mascota_protectora
        FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 4. FOTOS DE MASCOTAS
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
-- ────────────────────────────────────────────────────────────
CREATE TABLE solicitudes_adopcion (
    idSolicitud   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario     INT UNSIGNED NULL,
    idMascota     INT UNSIGNED NOT NULL,
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
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sol_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 7. APADRINAMIENTOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE apadrinamientos (
    idApadrinamiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario        INT UNSIGNED NOT NULL,
    idMascota        INT UNSIGNED NOT NULL,
    cuota            DECIMAL(6,2) NOT NULL,
    fecha_inicio     DATE         NOT NULL,
    fecha_fin        DATE         NULL,
    estado           ENUM('activo','cancelado','pausado') NOT NULL DEFAULT 'activo',
    nombre_pagador   VARCHAR(120),
    email_pagador    VARCHAR(120),
    referencia_pago  VARCHAR(100),

    CONSTRAINT fk_apad_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_apad_mascota
        FOREIGN KEY (idMascota) REFERENCES mascotas(idMascota)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 8. SEGUIMIENTOS DE APADRINAMIENTO
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
-- ────────────────────────────────────────────────────────────
CREATE TABLE donaciones (
    idDonacion      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario       INT UNSIGNED NULL,
    idProtectora    INT UNSIGNED NULL,
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
-- 10. FORO — PUBLICACIONES
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
    fijada        TINYINT(1)   NOT NULL DEFAULT 0,
    activa        TINYINT(1)   NOT NULL DEFAULT 1,
    fecha         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pub_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 11. FORO — COMENTARIOS
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
-- ────────────────────────────────────────────────────────────
CREATE TABLE puntos_mapa (
    idPunto      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    categoria    ENUM('protectora','veterinaria','tienda','donacion','refugio') NOT NULL,
    descripcion  TEXT,
    direccion    VARCHAR(200),
    localidad    VARCHAR(80),
    telefono     VARCHAR(20),
    email        VARCHAR(120),
    web          VARCHAR(200),
    latitud      DECIMAL(10,7) NOT NULL,
    longitud     DECIMAL(10,7) NOT NULL,
    activo       TINYINT(1)    NOT NULL DEFAULT 1,
    idProtectora INT UNSIGNED NULL,

    CONSTRAINT fk_punto_protectora
        FOREIGN KEY (idProtectora) REFERENCES protectoras(idProtectora)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 13. TEST — PREGUNTAS
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_preguntas (
    idPregunta INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_test  ENUM('compatibilidad','conocimiento') NOT NULL,
    texto      VARCHAR(400) NOT NULL,
    orden      TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 14. TEST — OPCIONES DE RESPUESTA
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_opciones (
    idOpcion    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idPregunta  INT UNSIGNED NOT NULL,
    texto       VARCHAR(300) NOT NULL,
    valor       VARCHAR(80),
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_opcion_pregunta
        FOREIGN KEY (idPregunta) REFERENCES test_preguntas(idPregunta)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 15. RESULTADOS DE TEST
-- ────────────────────────────────────────────────────────────
CREATE TABLE test_resultados (
    idResultado       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario         INT UNSIGNED NOT NULL,
    tipo_test         ENUM('compatibilidad','conocimiento') NOT NULL,
    respuestas_json   JSON,
    puntuacion        TINYINT UNSIGNED,
    fecha             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_res_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ────────────────────────────────────────────────────────────
-- 16. MENSAJES DE CONTACTO
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
-- ÍNDICES
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
-- DATOS — PROTECTORAS (con todos los campos actualizados)
-- ────────────────────────────────────────────────────────────
INSERT INTO protectoras
    (nombre, descripcion, direccion, localidad, telefono, email,
     web, tipo_pagina, iban, bizum, teaming,
     foto_logo, latitud, longitud, verificada, activa)
VALUES
(
    'Centro de Protección Animal de Gijón',
    'Instalación municipal gestionada por la Fundación Protectora de Asturias. Recogen animales perdidos o abandonados en Gijón, les dan atención veterinaria completa y los preparan para la adopción.',
    'Camino de Liervado S/n',
    'Gijón',
    '615 411 417',
    'cproteccionanimalgijon@gmail.com',
    'http://www.facebook.com/cpagijon',
    'red_social',
    NULL,
    NULL,
    NULL,
    'img/protectora/centro-proteccion-animales-gijon.jpg',
    43.5155000, -5.6940000,
    1, 1
),
(
    'Fundación Protectora de Asturias',
    'Fundada en 2012 como respuesta al abandono masivo. Trabajan con casas de acogida como base principal y tienen refugio propio en Siero.',
    'Siero, Asturias',
    'Siero',
    NULL,
    'info@protectoradeasturias.org',
    'http://www.protectoradeasturias.org',
    'web',
    'ES15 0081 5665 2400 0109 0516',
    NULL,
    'https://www.teaming.net/fundacionprotectoradeanimalesasturias',
    'img/protectora/protectora-de-asturias.jpg',
    43.3614000, -5.8496000,
    1, 1
),
(
    'Asociación Felina La Esperanza',
    'Asociación especializada exclusivamente en gatos, con especial atención a los casos más difíciles: felinos con enfermedades crónicas como la leucemia felina, gatos de edad avanzada o con necesidades especiales.',
    'Asturias',
    'Asturias',
    NULL,
    'asociacionfelinalaesperanza@gmail.com',
    'https://asociacionfelinalaesperanza.com/',
    'web',
    NULL,
    NULL,
    'https://www.teaming.net/asociacionfelinalaesperanza',
    'img/protectora/asociacion-felina-la-esperanza.jpg',
    43.5547000, -5.9249000,
    1, 1
),
(
    'MÁS QUE CHUCHOS',
    'Nacida en 1993, es una de las protectoras con más trayectoria de Asturias. Han conseguido cientos de adopciones responsables.',
    'Oviedo – Llanera',
    'Oviedo',
    NULL,
    'info@masquechuchos.org',
    'http://masquechuchos.org',
    'web',
    NULL,
    NULL,
    'https://www.teaming.net/masquechuchos/',
    'img/protectora/masquechuchos.jpg',
    43.3572000, -5.8602000,
    1, 1
),
(
    'Nortemascotas',
    'Asociación sin ánimo de lucro que lucha por los derechos de los animales desde Gijón. Trabajan con voluntarios y casas de acogida particulares, sin instalaciones propias.',
    'Gijón, Pumarín',
    'Gijón',
    '665 971 933',
    'Mariasol.ferreyra2014@gmail.com',
    'https://www.albergaria.es/protectoras/nortemascotas/',
    'otra',
    'ES39 0182 2800 1902 0163 9405',
    NULL,
    NULL,
    'img/protectora/nortemascota-dex.jpg',
    43.5322000, -5.6611000,
    1, 1
);


-- ────────────────────────────────────────────────────────────
-- DATOS — USUARIO ADMIN
-- contraseña: password
-- ────────────────────────────────────────────────────────────
INSERT INTO usuarios (nombre, apellidos, username, email, password_hash, rol)
VALUES (
    'Admin', 'PetFamily', 'admin', 'admin@petfamily.es',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);


-- ────────────────────────────────────────────────────────────
-- DATOS — MASCOTAS
-- ────────────────────────────────────────────────────────────
INSERT INTO mascotas
    (idProtectora, nombre, especie, raza, sexo, tamanyo, color, descripcion,
     estado_salud, urgencia, estado_adopcion,
     disponible_apadrinamiento, disponible_acogida,
     compatible_ninos, compatible_perros, compatible_gatos, apto_piso,
     vacunado, esterilizado, microchip, desparasitado,
     edad_texto, num_vistas, fecha_entrada)
VALUES
(
    1, 'Leia', 'perro', 'Pit bull', 'hembra', 'mediano', 'Marrón',
    'Pit bull de 14 años con artrosis. Muy sociable con personas y perros. Necesita licencia PPP.',
    'Artrosis', 'normal', 'disponible',
    1, 1,
    1, 1, 0, 1,
    1, 1, 1, 1,
    '14 años', 0, '2010-01-01'
),
(
    2, 'Haru', 'perro', 'Mestiza', 'hembra', 'grande', 'Marrón',
    'Perra de 7 años con mucha energía. Lleva 1 año esperando familia.',
    'Bueno', 'urgente', 'disponible',
    1, 1,
    1, 0, 0, 0,
    1, 1, 1, 1,
    '7 años', 0, '2024-01-01'
),
(
    3, 'Roy', 'gato', 'Europeo', 'macho', 'pequeño', 'Blanco y negro',
    'Muy cariñoso y mimoso. Se lleva fenomenal con niños y otros gatos.',
    'Bueno', 'nuevo', 'disponible',
    1, 1,
    1, 0, 1, 1,
    1, 1, 1, 1,
    '2 años', 0, '2023-07-26'
),
(
    4, 'Bambi', 'gato', 'Común europeo', 'macho', 'pequeño', 'Sin datos',
    'Gato tranquilo que busca un hogar donde sentirse seguro y querido.',
    'Bueno', 'normal', 'disponible',
    1, 1,
    0, 0, 0, 1,
    1, 1, 1, 1,
    NULL, 0, NULL
),
(
    5, 'Dexter', 'perro', 'Cruce Pastor Alemán', 'macho', 'mediano', 'Negro y marrón',
    'Lleva 9 años esperando. Fue maltratado y abandonado.',
    'Bueno', 'urgente', 'disponible',
    0, 1,
    1, 0, 1, 1,
    1, 1, 1, 1,
    '9 años', 0, '2015-05-05'
),
(
    2, 'Bosque', 'gato', 'Común europeo', 'hembra', 'pequeño', 'Atigrado',
    'Gatita de 8 meses positiva a leucemia felina. Necesita hogar sin otros animales.',
    'Leucemia felina+', 'normal', 'no_disponible',
    1, 0,
    0, 0, 0, 1,
    1, 1, 1, 1,
    '8 meses', 0, '2024-12-01'
);


-- ────────────────────────────────────────────────────────────
-- DATOS — FOTOS DE MASCOTAS
-- ────────────────────────────────────────────────────────────
INSERT INTO mascotas_fotos (idMascota, ruta, es_principal, orden) VALUES
(1, 'img/mascotas/leia-centro-proteccion-animales-gijon.jpg',  1, 0),
(1, 'img/mascotas/leai2-centro-proteccion-animales-gijon.jpg', 0, 1),
(2, 'img/mascotas/haru-protectora-de-asturias-dog.jpg',        1, 0),
(2, 'img/mascotas/haru2-protectora-de-asturias.jpg',           0, 1),
(2, 'img/mascotas/haru3-protectora-de-asturias.jpg',           0, 2),
(3, 'img/mascotas/roys-asociacion-felina-la-esperanza-cat.jpg',1, 0),
(3, 'img/mascotas/roy2-asociacion-felina-la-esperanza.jpg',    0, 1),
(3, 'img/mascotas/roy3-asociacion-felina-la-esperanza.jpg',    0, 2),
(4, 'img/mascotas/bambi-masquechuchos-cat.jpg',                1, 0),
(5, 'img/mascotas/dexter-nortemascotas-dog.jpg',               1, 0),
(5, 'img/mascotas/dexter2-nortemascotas-1461588001-ouIWI.jpg', 0, 1),
(6, 'img/mascotas/bosque-protectora-de-asturias-cat.jpg',      1, 0),
(6, 'img/mascotas/bosque2-protectora-de-asturias.jpg',         0, 1),
(6, 'img/mascotas/bosque3-protectora-de-asturias.jpg',         0, 2);
