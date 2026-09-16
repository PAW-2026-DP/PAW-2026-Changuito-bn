# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Imagen de nginx para el deploy. En desarrollo nginx ve public/ por el bind
# mount de docker-compose.yml; en la VM no hay codigo montado, asi que los
# archivos estaticos y el front controller tienen que viajar dentro de la
# imagen. Por eso se copia public/ aca y no se comparte un volumen con el
# contenedor PHP (un volumen persiste archivos viejos entre deploys).
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public/ /var/www/html/public/
