-- Direcciones de entrega de un cliente. `cliente_id` referencia el id del
-- usuario con rol 'cliente' (ver App\Domain\Direccion).
CREATE TABLE direcciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    calle VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    ciudad VARCHAR(255) NOT NULL,
    cp VARCHAR(20) NOT NULL,
    latitud DECIMAL(9, 6) NOT NULL,
    longitud DECIMAL(9, 6) NOT NULL,
    es_default BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_direcciones_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    INDEX idx_direcciones_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
