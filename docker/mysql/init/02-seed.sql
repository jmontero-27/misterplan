-- ============================================
-- DATOS DE EJEMPLO
-- ============================================

SET NAMES utf8mb4;

INSERT INTO reserva (
    nombre_huesped,
    email_huesped,
    nombre_alojamiento,
    fecha_entrada,
    fecha_salida,
    estado,
    importe,
    notas
) VALUES
(
    'Juan García',
    'juan.garcia@example.com',
    'Hotel Costa Azul',
    '2026-11-10',
    '2026-11-15',
    'pendiente',
    450.00,
    'Solicita habitación tranquila'
),
(
    'María López',
    'maria.lopez@example.com',
    'Apartamentos del Sol',
    '2026-11-12',
    '2026-11-18',
    'confirmada',
    720.50,
    NULL
),
(
    'Pedro Martínez',
    'pedro.martinez@example.com',
    'Hotel Sierra Nevada',
    '2026-12-01',
    '2026-12-05',
    'cancelada',
    380.00,
    'Cancelación solicitada por el huésped'
),
(
    'Laura Fernández',
    'laura.fernandez@example.com',
    'Casa Rural El Olivo',
    '2026-12-15',
    '2026-12-20',
    'pendiente',
    600.00,
    NULL
),
(
    'Carlos Ruiz',
    'carlos.ruiz@example.com',
    'Hotel Costa Azul',
    '2026-10-20',
    '2026-10-25',
    'confirmada',
    525.00,
    'Llegada prevista por la tarde'
);

-- ============================================
-- HISTORIAL DE EVENTOS
-- ============================================

INSERT INTO reserva_evento (
    reserva_id,
    tipo,
    descripcion
) VALUES
(1, 'creacion', 'Reserva creada en estado pendiente'),

(2, 'creacion', 'Reserva creada en estado pendiente'),
(2, 'confirmacion', 'Reserva confirmada'),

(3, 'creacion', 'Reserva creada en estado pendiente'),
(3, 'cancelacion', 'Reserva cancelada'),

(4, 'creacion', 'Reserva creada en estado pendiente'),

(5, 'creacion', 'Reserva creada en estado pendiente'),
(5, 'confirmacion', 'Reserva confirmada');

-- ============================================
-- RESERVAS ADICIONALES
-- IDs 6 a 20 al importar el seed en una BD vacía
-- ============================================

INSERT INTO reserva (
    nombre_huesped,
    email_huesped,
    nombre_alojamiento,
    fecha_entrada,
    fecha_salida,
    estado,
    importe,
    notas
) VALUES
(
    'Ana García',
    'ana.garcia@example.com',
    'Hotel Costa Azul',
    '2026-11-10',
    '2026-11-12',
    'confirmada',
    210.00,
    'Solicita cuna para un bebé'
),
(
    'Javier Sánchez',
    'javier.sanchez@example.com',
    'Casa Rural El Olivo',
    '2026-11-06',
    '2026-11-08',
    'pendiente',
    240.00,
    'Viaja con un perro; consultar disponibilidad'
),
(
    'Lucía Romero',
    'lucia.romero@example.com',
    'Apartamentos del Sol',
    '2026-11-15',
    '2026-11-22',
    'cancelada',
    805.00,
    'Cancelación por cambio de fechas del viaje'
),
(
    'Miguel Torres',
    'miguel.torres@example.com',
    'Hotel Sierra Nevada',
    '2026-12-04',
    '2026-12-08',
    'confirmada',
    560.00,
    'Solicita información sobre guardaesquís'
),
(
    'Elena Martín',
    'elena.martin@example.com',
    'Hostal Plaza Mayor',
    '2026-10-23',
    '2026-10-24',
    'pendiente',
    65.00,
    NULL
),
(
    'David Navarro',
    'david.navarro@example.com',
    'Hotel Mirador del Mar',
    '2026-11-01',
    '2026-11-04',
    'confirmada',
    375.90,
    'Solicita habitación con vistas al mar'
),
(
    'Sara López',
    'sara.lopez@example.com',
    'Casa Rural El Olivo',
    '2026-12-23',
    '2026-12-27',
    'pendiente',
    680.00,
    'Estancia familiar durante Navidad'
),
(
    'Pablo Jiménez',
    'pablo.jimenez@example.com',
    'Hostal Plaza Mayor',
    '2026-11-20',
    '2026-11-22',
    'cancelada',
    140.00,
    'Cancelación antes de confirmar la reserva'
),
(
    'Carmen Díaz',
    'carmen.diaz@example.com',
    'Apartamentos del Sol',
    '2026-12-28',
    '2027-01-03',
    'confirmada',
    1020.00,
    'Llegada prevista a las 20:00'
),
(
    'Juan Antonio Pérez',
    'juan.antonio.perez@example.com',
    'Hotel Sierra Nevada',
    '2027-01-08',
    '2027-01-10',
    'pendiente',
    290.00,
    NULL
),
(
    'Isabel Moreno',
    'isabel.moreno@example.com',
    'Hotel Mirador del Mar',
    '2026-10-16',
    '2026-10-19',
    'confirmada',
    345.00,
    'Solicita factura a nombre de empresa'
),
(
    'Álvaro Muñoz',
    'alvaro.munoz@example.com',
    'Hotel Costa Azul',
    '2026-11-27',
    '2026-11-30',
    'cancelada',
    285.50,
    'Cancelación por motivos personales'
),
(
    'Beatriz Sánchez',
    'beatriz.sanchez@example.com',
    'Hostal Plaza Mayor',
    '2026-12-05',
    '2026-12-07',
    'confirmada',
    180.00,
    'Solicita dos camas individuales'
),
(
    'Daniel Ortega',
    'daniel.ortega@example.com',
    'Apartamentos del Sol',
    '2027-01-15',
    '2027-01-29',
    'pendiente',
    1260.00,
    'Estancia de dos semanas por trabajo'
),
(
    'María del Carmen Ruiz',
    'maria.carmen.ruiz@example.com',
    'Casa Rural El Olivo',
    '2027-02-12',
    '2027-02-14',
    'cancelada',
    260.00,
    'Cancelación antes de confirmar la reserva'
);

-- ============================================
-- HISTORIAL DE LAS RESERVAS ADICIONALES
-- ============================================

INSERT INTO reserva_evento (
    reserva_id,
    tipo,
    descripcion
) VALUES
(6, 'creacion', 'Reserva creada en estado pendiente'),
(6, 'confirmacion', 'Reserva confirmada'),

(7, 'creacion', 'Reserva creada en estado pendiente'),

(8, 'creacion', 'Reserva creada en estado pendiente'),
(8, 'confirmacion', 'Reserva confirmada'),
(8, 'cancelacion', 'Reserva cancelada'),

(9, 'creacion', 'Reserva creada en estado pendiente'),
(9, 'confirmacion', 'Reserva confirmada'),

(10, 'creacion', 'Reserva creada en estado pendiente'),

(11, 'creacion', 'Reserva creada en estado pendiente'),
(11, 'confirmacion', 'Reserva confirmada'),

(12, 'creacion', 'Reserva creada en estado pendiente'),

(13, 'creacion', 'Reserva creada en estado pendiente'),
(13, 'cancelacion', 'Reserva cancelada'),

(14, 'creacion', 'Reserva creada en estado pendiente'),
(14, 'confirmacion', 'Reserva confirmada'),

(15, 'creacion', 'Reserva creada en estado pendiente'),

(16, 'creacion', 'Reserva creada en estado pendiente'),
(16, 'confirmacion', 'Reserva confirmada'),

(17, 'creacion', 'Reserva creada en estado pendiente'),
(17, 'confirmacion', 'Reserva confirmada'),
(17, 'cancelacion', 'Reserva cancelada'),

(18, 'creacion', 'Reserva creada en estado pendiente'),
(18, 'confirmacion', 'Reserva confirmada'),

(19, 'creacion', 'Reserva creada en estado pendiente'),

(20, 'creacion', 'Reserva creada en estado pendiente'),
(20, 'cancelacion', 'Reserva cancelada');