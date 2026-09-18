#!/bin/sh
set -e

# Se ejecuta una sola vez, la primera vez que se crea el volumen de datos
# (ver docker-entrypoint-initdb.d de la imagen oficial de MariaDB). Crea la
# base que usan los tests de Integration (ConnectionFactory::forTests()),
# separada de la de desarrollo para no pisar datos entre sí. Va en .sh y no
# en .sql porque el entrypoint solo expande variables de entorno en los
# scripts de shell.
mariadb -u root -p"${MARIADB_ROOT_PASSWORD}" <<-SQL
    CREATE DATABASE IF NOT EXISTS changuito_test;
    GRANT ALL PRIVILEGES ON changuito_test.* TO '${MARIADB_USER}'@'%';
    FLUSH PRIVILEGES;
SQL
