-- Sucursales de cada supermercado y el radio dentro del cual compiten por
-- una compra (ver App\Domain\PoliticaCobertura).
CREATE TABLE sucursales (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    supermercado_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    latitud DECIMAL(9, 6) NOT NULL,
    longitud DECIMAL(9, 6) NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_sucursales_supermercado FOREIGN KEY (supermercado_id) REFERENCES supermercados (usuario_id) ON DELETE CASCADE,
    INDEX idx_sucursales_supermercado (supermercado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE zonas_cobertura (
    sucursal_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    radio_min DECIMAL(6, 2) NOT NULL,
    radio_max DECIMAL(6, 2) NOT NULL,
    CONSTRAINT fk_zonas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
