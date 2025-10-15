# SIPUV - Sistema de Facturación Electrónica

Sistema de facturación electrónica desarrollado con Laravel + Vue.js

## 🏢 Empresa
**FULLSYS TECNOLOGÍA SANTA MARTA**

## 👨‍💻 Desarrollador
- **Nombre:** Fulvio Leonardo Badillo Cáceres
- **Celular:** +57 302 548 0682
- **Email:** fullsyssantamarta@gmail.com

## 📋 Descripción
Sistema completo de facturación electrónica basado en Laravel y Vue.js con arquitectura dockerizada. Incluye:
- Facturación electrónica DIAN (Colombia)
- Gestión de inventarios
- Punto de venta (POS)
- Reportes y estadísticas
- Multi-tenant (multi-empresa)

## 🛠️ Tecnologías
- **Backend:** Laravel 5.8+ (PHP 7.2+)
- **Frontend:** Vue.js 2.x
- **Base de datos:** MariaDB 10.5
- **Servidor web:** Nginx
- **Contenedores:** Docker + Docker Compose

## 🐳 Contenedores
- `fpm_app`: Contenedor PHP-FPM (Laravel)
- `nginx_app`: Servidor web Nginx
- `scheduling_app`: Programador de tareas (cron)
- `mariadb`: Base de datos MariaDB
- `proxy`: Nginx reverse proxy

## 📦 Instalación
Este proyecto se ejecuta usando Docker Compose junto con los otros servicios del ecosistema SIPUV.
