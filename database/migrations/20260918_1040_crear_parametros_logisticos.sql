-- Parámetros de la función de costo de envío P(n) = p0 + k·(n-1)^2.5 y del
-- radio con el que se ofrece un pedido a los repartidores. Los ajusta el
-- admin sin tocar código. Los montos van en centavos (ver App\Domain\Dinero).
CREATE TABLE parametros_logisticos (
    tamanio_envio ENUM('pequenio', 'mediano', 'grande') NOT NULL PRIMARY KEY,
    p0 BIGINT UNSIGNED NOT NULL,
    k BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coeficientes_distancia (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tamanio_envio ENUM('pequenio', 'mediano', 'grande') NOT NULL,
    distancia_min DECIMAL(6, 2) NOT NULL,
    distancia_max DECIMAL(6, 2) NOT NULL,
    coeficiente DECIMAL(6, 3) NOT NULL,
    INDEX idx_coeficientes_tamanio (tamanio_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fila única de configuración: `id` fijo en 1 para que no puedan existir dos.
CREATE TABLE parametros_reparto (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    radio_oferta_km DECIMAL(6, 2) NOT NULL,
    CONSTRAINT chk_parametros_reparto_fila_unica CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO parametros_reparto (id, radio_oferta_km) VALUES (1, 5.00);
