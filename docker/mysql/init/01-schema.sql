-- ============================================
-- MISTERPLAN - GESTIÓN DE RESERVAS
-- MySQL 8.4
-- ============================================

SET NAMES utf8mb4;

-- ============================================
-- TABLA: reserva
-- ============================================

CREATE TABLE IF NOT EXISTS reserva (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nombre_huesped VARCHAR(150) NOT NULL,
    email_huesped VARCHAR(255) NOT NULL,
    nombre_alojamiento VARCHAR(200) NOT NULL,

    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,

    estado ENUM(
        'pendiente',
        'confirmada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',

    importe DECIMAL(10, 2) NOT NULL,

    notas TEXT NULL,

    fecha_creacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_reserva_fechas
        CHECK (fecha_salida > fecha_entrada),

    CONSTRAINT chk_reserva_importe
        CHECK (importe >= 0),

    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_fecha_entrada (fecha_entrada)

) ENGINE=InnoDB;

-- ============================================
-- TABLA: reserva_evento
-- ============================================

CREATE TABLE IF NOT EXISTS reserva_evento (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    reserva_id BIGINT UNSIGNED NOT NULL,

    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,

    fecha_creacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_evento_reserva_fecha (
        reserva_id,
        fecha_creacion
    ),

    CONSTRAINT fk_evento_reserva
        FOREIGN KEY (reserva_id)
        REFERENCES reserva(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB;