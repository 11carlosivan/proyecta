# Guía de Instalación en Hosting - PROYECTA

## 🚀 Instalación Rápida (Recomendada)

### Paso 1: Descargar el Proyecto

Ve a tu repositorio en GitHub y descarga el código:

**Opción A: Descargar ZIP**
1. Ve a https://github.com/11carlosivan/proyecta
2. Haz clic en el botón verde **"Code"**
3. Selecciona **"Download ZIP"**
4. Descarga el archivo `proyecta-main.zip`

**Opción B: Clonar con Git** (si tu hosting tiene acceso SSH)
```bash
git clone https://github.com/11carlosivan/proyecta.git
```

### Paso 2: Subir Archivos al Hosting

**Usando cPanel File Manager:**
1. Inicia sesión en tu cPanel
2. Ve a **"File Manager"**
3. Navega a `public_html` (o el directorio de tu dominio)
4. Haz clic en **"Upload"**
5. Sube el archivo ZIP
6. Haz clic derecho en el archivo ZIP → **"Extract"**
7. Elimina el archivo ZIP después de extraer

**Usando FTP (FileZilla, WinSCP, etc.):**
1. Conecta a tu servidor FTP
2. Navega a `public_html` o tu directorio web
3. Sube todos los archivos y carpetas del proyecto
4. Asegúrate de mantener la estructura de carpetas

### Paso 3: Configurar Permisos

Asegúrate de que estas carpetas tengan permisos de escritura (755 o 777):
```
uploads/
uploads/avatars/
uploads/attachments/
uploads/temp/
config/
```

**En cPanel:**
1. Haz clic derecho en la carpeta → **"Change Permissions"**
2. Marca: Read, Write, Execute para Owner
3. Marca: Read, Execute para Group y Public
4. Aplica cambios

### Paso 4: Crear Base de Datos MySQL

**En cPanel:**
1. Ve a **"MySQL Databases"**
2. Crea una nueva base de datos:
   - Nombre: `tu_usuario_proyecta` (ejemplo: `miusuario_proyecta`)
3. Crea un usuario MySQL:
   - Usuario: `tu_usuario_admin`
   - Contraseña: (genera una segura)
4. Asigna el usuario a la base de datos con **TODOS LOS PRIVILEGIOS**
5. **Anota estos datos:**
   - Host: `localhost` (generalmente)
   - Nombre de BD: `tu_usuario_proyecta`
   - Usuario: `tu_usuario_admin`
   - Contraseña: la que creaste

### Paso 5: Ejecutar el Instalador Web

1. Abre tu navegador
2. Ve a: `http://tu-dominio.com/proyecta/install.php`
   - O si está en la raíz: `http://tu-dominio.com/install.php`

3. **Sigue el asistente:**
   - **Paso 1:** Bienvenida → Clic en "Comenzar Instalación"
   - **Paso 2:** Configuración de BD
     - Host: `localhost`
     - Nombre de BD: `tu_usuario_proyecta`
     - Usuario: `tu_usuario_admin`
     - Contraseña: la que creaste
     - Clic en "Continuar"
   - **Paso 3:** Espera mientras se crean las tablas (automático)
   - **Paso 4:** Crea tu usuario administrador
     - Nombre completo
     - Email
     - Contraseña (mínimo 6 caracteres)
     - Clic en "Crear Administrador"
   - **Paso 5:** ¡Instalación completada!

### Paso 6: Seguridad Post-Instalación

**IMPORTANTE:** Por seguridad, elimina o renombra el instalador:

**Opción 1: Eliminar (Recomendado)**
```bash
# Via SSH
rm install.php

# O en cPanel File Manager:
# Haz clic derecho en install.php → Delete
```

**Opción 2: Renombrar**
```bash
# Via SSH
mv install.php install.php.disabled

# O en cPanel File Manager:
# Haz clic derecho en install.php → Rename → install.php.disabled
```

### Paso 7: Iniciar Sesión

1. Ve a: `http://tu-dominio.com/proyecta/login.php`
2. Ingresa las credenciales del administrador que creaste
3. ¡Listo! Ya puedes usar PROYECTA

---

## 🔧 Solución de Problemas Comunes

### Error: "No se puede conectar a la base de datos"
- ✅ Verifica que el host sea `localhost` (o el que te proporcionó tu hosting)
- ✅ Confirma que el usuario tenga TODOS los privilegios
- ✅ Revisa que la contraseña sea correcta
- ✅ Algunos hostings usan `127.0.0.1` en lugar de `localhost`

### Error: "Permission denied" al crear archivos
- ✅ Verifica permisos de las carpetas `uploads/` y `config/`
- ✅ Cambia permisos a 755 o 777 según tu hosting

### Error: "Call to undefined function PDO"
- ✅ Contacta a tu hosting para habilitar la extensión PDO de PHP
- ✅ Verifica que tu hosting use PHP 8.0 o superior

### La página se ve sin estilos
- ✅ Verifica que todos los archivos se hayan subido correctamente
- ✅ Revisa la carpeta `assets/` esté completa
- ✅ Limpia la caché del navegador (Ctrl + F5)

### No puedo subir archivos adjuntos
- ✅ Verifica permisos de `uploads/` (debe ser 755 o 777)
- ✅ Revisa el límite de `upload_max_filesize` en PHP (cPanel → PHP Settings)

---

## 📋 Requisitos del Hosting

### Mínimos:
- ✅ PHP 8.0 o superior
- ✅ MySQL 8.0 o MariaDB 10.5+
- ✅ Extensión PDO de PHP
- ✅ 50 MB de espacio en disco (mínimo)
- ✅ 128 MB de RAM (recomendado 256 MB)

### Recomendados:
- ✅ PHP 8.1 o superior
- ✅ MySQL 8.0+
- ✅ SSL/HTTPS habilitado
- ✅ 500 MB de espacio en disco
- ✅ Acceso SSH (opcional, pero útil)
- ✅ Cron jobs (para tareas programadas futuras)

---

## 🌐 Configuración de Dominio

### Si instalaste en un subdirectorio:
```
http://tu-dominio.com/proyecta/
```

### Si instalaste en la raíz:
```
http://tu-dominio.com/
```

### Para usar un subdominio:
1. Crea un subdominio en cPanel (ej: `proyecta.tu-dominio.com`)
2. Apunta el subdominio a la carpeta donde instalaste PROYECTA
3. Accede vía: `http://proyecta.tu-dominio.com`

---

## 🔐 Configuración SSL/HTTPS (Recomendado)

1. En cPanel, ve a **"SSL/TLS Status"**
2. Habilita SSL para tu dominio
3. O usa **Let's Encrypt** (gratis) si tu hosting lo soporta
4. Fuerza HTTPS agregando en `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📊 Verificación Post-Instalación

Después de instalar, verifica que todo funcione:

- ✅ Puedes iniciar sesión
- ✅ Puedes crear un proyecto
- ✅ Puedes crear una tarea
- ✅ Puedes subir archivos adjuntos
- ✅ Las notificaciones funcionan
- ✅ El tablero Kanban se muestra correctamente

---

## 🆘 Soporte

Si tienes problemas durante la instalación:

1. Revisa esta guía completa
2. Verifica los requisitos del sistema
3. Contacta al soporte de tu hosting para verificar:
   - Versión de PHP
   - Extensiones habilitadas
   - Permisos de archivos

---

## 📝 Notas Adicionales

### Backup Antes de Actualizar
Siempre haz backup de:
- Base de datos (exportar desde phpMyAdmin)
- Carpeta `uploads/`
- Archivo `config/database.php`

### Actualización Futura
Para actualizar a una nueva versión:
1. Haz backup completo
2. Descarga la nueva versión de GitHub
3. Reemplaza archivos (excepto `config/` y `uploads/`)
4. Ejecuta cualquier migración nueva si existe

---

¡Listo! Con estos pasos deberías tener PROYECTA funcionando en tu hosting sin problemas. 🚀
