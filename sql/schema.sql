-- Bache Detection System - MySQL (XAMPP / phpMyAdmin)
-- Usuario por defecto en XAMPP: root sin contraseña
-- Importar este archivo en phpMyAdmin o ejecutar en la consola MySQL

CREATE DATABASE IF NOT EXISTS pothole_detection
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pothole_detection;

-- Tabla users
CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  password VARCHAR(191) NOT NULL,
  name VARCHAR(191) NOT NULL,
  role VARCHAR(191) NOT NULL DEFAULT 'usuario',
  createdAt DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updatedAt DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  UNIQUE KEY users_email_key (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla reportes
CREATE TABLE IF NOT EXISTS reportes (
  id INT NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(191) NOT NULL,
  descripcion TEXT NOT NULL,
  ubicacion VARCHAR(191) NOT NULL,
  estado VARCHAR(191) NOT NULL DEFAULT 'pendiente',
  prioridad VARCHAR(191) NOT NULL DEFAULT 'media',
  fecha DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updatedAt DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  userId INT NOT NULL,
  PRIMARY KEY (id),
  KEY reportes_userId_idx (userId),
  CONSTRAINT reportes_userId_fkey
    FOREIGN KEY (userId) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla analisis_imagenes
CREATE TABLE IF NOT EXISTS analisis_imagenes (
  id INT NOT NULL AUTO_INCREMENT,
  nombreArchivo VARCHAR(191) NOT NULL,
  ruta VARCHAR(191) NOT NULL,
  tipoSevidad VARCHAR(191) NULL,
  porcentajeConfianza DOUBLE NULL,
  resultadoAnalisis JSON NULL,
  estadoProcesamiento VARCHAR(191) NOT NULL DEFAULT 'pendiente',
  mensajeError VARCHAR(191) NULL,
  fecha DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  updatedAt DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  reporteId INT NOT NULL,
  usuarioId INT NOT NULL,
  PRIMARY KEY (id),
  KEY analisis_imagenes_reporteId_idx (reporteId),
  KEY analisis_imagenes_usuarioId_idx (usuarioId),
  CONSTRAINT analisis_imagenes_reporteId_fkey
    FOREIGN KEY (reporteId) REFERENCES reportes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT analisis_imagenes_usuarioId_fkey
    FOREIGN KEY (usuarioId) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de migraciones de Prisma (opcional, para usar "prisma migrate" después)
CREATE TABLE IF NOT EXISTS _prisma_migrations (
  id VARCHAR(36) NOT NULL,
  checksum VARCHAR(64) NOT NULL,
  finished_at DATETIME(3) NULL,
  migration_name VARCHAR(255) NOT NULL,
  logs TEXT NULL,
  rolled_back_at DATETIME(3) NULL,
  started_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  applied_steps_count INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
