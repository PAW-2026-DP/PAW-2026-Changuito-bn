-- Cuentas de acceso y sus tokens. El rol vive como columna de `usuarios`
-- (ver App\Domain\Rol): las tablas satélite solo existen donde el rol tiene
-- datos propios, como `supermercados`.
CREATE TABLE usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'supermercado', 'repartidor', 'administrador') NOT NULL,
    fecha_alta DATETIME NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE supermercados (
    usuario_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    razon_social VARCHAR(255) NOT NULL,
    cuit VARCHAR(20) NOT NULL UNIQUE,
    CONSTRAINT fk_supermercados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Solo se guarda el sha256 del token, nunca el valor plano que ve el cliente.
CREATE TABLE tokens_acceso (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    emitido_en DATETIME NOT NULL,
    expira_en DATETIME NOT NULL,
    revocado_en DATETIME NULL,
    CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
