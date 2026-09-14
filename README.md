# Changuito — Backend

API de la plataforma **Changuito**, que permite armar listas de compras, comparar precios entre supermercados, optimizar dónde conviene comprar y gestionar el pedido hasta la entrega.

## Enlaces del proyecto

- **Documentación:** https://paw-2026-changuito-docs.vercel.app/
- **Wireframes (Figma):** https://www.figma.com/design/ZeL7MqjxUKq20VJu6yR9Us/Changuito
- **Repositorios del TP Integrador:**
  - [Frontend Cliente](https://github.com/PAW-2026-DP/PAW-2026-Changuito-fn)
  - [Frontend Backoffice](https://github.com/PAW-2026-DP/PAW-2026-Changuito-Backoffice-fn)
  - [Frontend Riders](https://github.com/PAW-2026-DP/PAW-2026-Changuito-Riders-fn)
  - [Backend](https://github.com/PAW-2026-DP/PAW-2026-Changuito-bn) (este repositorio)
  - [Documentación](https://github.com/PAW-2026-DP/PAW-2026-Changuito-Docs)

## Alcance funcional

- Usuarios y roles: cliente, supermercado, repartidor y administrador.
- Catálogo, precios, stock e historial de modificaciones.
- Listas de compras y carrito.
- Motor de optimización de compra en uno o dos comercios.
- Generación y seguimiento de pedidos, con su ciclo de estados.
- Endpoints para los paneles de administración, supermercado y repartidor.

La arquitectura completa (C4 de cuatro niveles), el modelo de objetos y el diagrama entidad-relación están en la [documentación del proyecto](https://paw-2026-changuito-docs.vercel.app/).

---

## Requisitos

| Componente | Versión mínima | Notas |
| :---- | :---- | :---- |
| PHP | 8.1 | Extensiones requeridas: `pdo_mysql`, `mbstring`, `json`, `openssl` |
| MySQL o MariaDB | MySQL 8.0 / MariaDB 10.6 | Se usan stored procedures para el optimizador |
| Servidor web | Apache 2.4 con `mod_rewrite`, o `php -S` para desarrollo | |
| Git | cualquiera | |

Para desarrollo local, un paquete tipo **XAMPP**, **Laragon** o **MAMP** cubre PHP, Apache y MariaDB de una sola vez.

> **TODO equipo:** confirmar la versión de PHP que vamos a fijar y si vamos a usar Composer. Si no usamos dependencias, conviene decirlo explícito acá.

---

## Puesta en marcha local

1. **Clonar el repositorio**

   ```bash
   git clone https://github.com/PAW-2026-DP/PAW-2026-Changuito-bn.git
   cd PAW-2026-Changuito-bn
   ```

2. **Crear la base de datos**

   ```sql
   CREATE DATABASE changuito CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'changuito_app'@'localhost' IDENTIFIED BY '<contraseña>';
   GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON changuito.* TO 'changuito_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

   El usuario de la aplicación se crea **sin privilegios administrativos** a propósito: no tiene `DROP` ni `GRANT`.

3. **Importar el esquema y los datos de prueba**

   ```bash
   mysql -u root -p changuito < src/database/schema.sql
   mysql -u root -p changuito < src/database/seed.sql
   ```

4. **Configurar las credenciales**

   Copiar el archivo de ejemplo y completarlo con los datos locales:

   ```bash
   cp config/config.example.php config/config.php
   ```

   `config/config.php` está en `.gitignore` y **nunca se commitea**. El repositorio es público por consigna, así que cualquier credencial subida queda expuesta de forma permanente en el historial de Git.

5. **Levantar el servidor**

   Con el servidor embebido de PHP:

   ```bash
   php -S localhost:8000 -t src/public
   ```

   O configurando un VirtualHost de Apache cuyo `DocumentRoot` apunte a `src/public`.

6. **Verificar**

   ```bash
   curl http://localhost:8000/api/health
   ```

> **TODO equipo:** los archivos `schema.sql`, `seed.sql` y `config.example.php` todavía no existen en el repositorio. Estos pasos quedan definidos acá y se completan en la tercera entrega, junto con la implementación.

---

## Deployment

El proyecto no requiere un runtime persistente, así que puede desplegarse en cualquier hosting compartido con soporte PHP y MySQL, en un VPS, o en un equipo propio expuesto con DNS dinámico o ngrok (alternativas que la consigna admite explícitamente).

**Pasos:**

1. Subir el código al servidor (por Git o por transferencia de archivos).
2. Apuntar el `DocumentRoot` del dominio a `src/public`. **El resto del repositorio debe quedar fuera del árbol servido**: si `src/` o `config/` son accesibles por URL, el código y las credenciales quedan descargables.
3. Crear la base de datos e importar el esquema.
4. Cargar las credenciales por variables de entorno o en `config/config.php` con permisos `600`.
5. Habilitar HTTPS con redirección de 80 a 443.
6. Desactivar `display_errors` y dirigir los errores al log del servidor.
7. Verificar que el motor de base de datos **no** escuche en una interfaz pública.

**Checklist previo a publicar:**

- [ ] `config/config.php` no está en el repositorio ni en el historial
- [ ] `display_errors = Off` en producción
- [ ] El `DocumentRoot` apunta sólo a `src/public`
- [ ] HTTPS activo y forzado
- [ ] El usuario de base de datos no es root
- [ ] Backup de la base configurado y restauración probada

---

## Estructura del repositorio

```text
PAW-2026-Changuito-bn/
├── docs/          Decisiones y documentación técnica del backend
├── src/
│   ├── database/  Esquema, migraciones y stored procedures
│   ├── public/    Único directorio servido por el servidor web
│   └── routes/    Punto de entrada y ruteo hacia los controllers
└── test/          Pruebas
```

## Estado actual

Base documental y estructura inicial. La implementación (PHP, SQL y stored procedures) corresponde a la tercera entrega.
