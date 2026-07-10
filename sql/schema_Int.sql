-- Pothole Scan — MySQL (XAMPP / InfinityFree / phpMyAdmin)
-- Importar en phpMyAdmin o ejecutar install.php

CREATE TABLE IF NOT EXISTS vias (
    id CHAR(36) NOT NULL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    ciudad VARCHAR(120) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'avenida',
    longitud_km DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    descripcion TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vias_ciudad (ciudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detecciones (
    id CHAR(36) NOT NULL PRIMARY KEY,
    via_id CHAR(36) NULL,
    image_url VARCHAR(500) NOT NULL,
    annotated_url VARCHAR(500) NULL,
    severidad ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    confianza DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    cantidad_baches INT UNSIGNED NOT NULL DEFAULT 0,
    analisis_ia TEXT NULL,
    ubicacion VARCHAR(255) NULL,
    fecha_deteccion DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detecciones_via FOREIGN KEY (via_id) REFERENCES vias(id) ON DELETE SET NULL,
    INDEX idx_detecciones_fecha (fecha_deteccion),
    INDEX idx_detecciones_severidad (severidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
