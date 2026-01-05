# PROYECTA - Sistema de Gestión de Proyectos Ágil

![PROYECTA](https://img.shields.io/badge/Version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![License](https://img.shields.io/badge/License-MIT-green)

## 📋 Descripción

**PROYECTA** es un sistema completo de gestión de proyectos ágil desarrollado con PHP, MySQL, JavaScript y Tailwind CSS. Diseñado para equipos que necesitan una herramienta potente, moderna y fácil de usar para gestionar proyectos, tareas, equipos y seguimiento de tiempo.

## ✨ Características Principales

### 🎯 Gestión de Proyectos
- ✅ Creación y administración de proyectos
- ✅ Tableros Kanban interactivos
- ✅ Estadísticas y métricas en tiempo real
- ✅ Cronología de actividades del proyecto
- ✅ Asignación de miembros del equipo

### 📝 Gestión de Tareas Avanzada
- ✅ Creación rápida de tareas con modal
- ✅ Filtros avanzados (estado, prioridad, proyecto, búsqueda)
- ✅ Vistas múltiples (lista, tarjetas)
- ✅ Cambio rápido de estado
- ✅ Sistema de comentarios
- ✅ Subtareas/Checklist
- ✅ Registro de tiempo trabajado
- ✅ Historial completo de cambios
- ✅ Archivos adjuntos
- ✅ Etiquetas personalizables
- ✅ Prioridades y fechas límite

### 👥 Gestión de Equipos
- ✅ Roles de usuario (Admin, Miembro, Cliente)
- ✅ Asignación de proyectos a usuarios
- ✅ Perfiles de usuario personalizables
- ✅ Sistema de permisos granular

### 🔔 Sistema de Notificaciones
- ✅ Notificaciones en tiempo real
- ✅ Alertas de asignación de tareas
- ✅ Notificaciones de cambios en proyectos

### 📊 Dashboard y Reportes
- ✅ Dashboard personalizado por usuario
- ✅ Estadísticas de proyectos
- ✅ Métricas de productividad
- ✅ Gráficos visuales de progreso

### 🎨 Interfaz de Usuario
- ✅ Diseño moderno y responsivo
- ✅ Modo oscuro/claro
- ✅ Animaciones suaves
- ✅ Iconos Material Symbols
- ✅ Tailwind CSS para estilos

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 8.0+
- **Base de Datos:** MySQL 8.0+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Estilos:** Tailwind CSS
- **Iconos:** Material Symbols
- **Servidor:** Apache (XAMPP recomendado para desarrollo)

## 📦 Instalación

### 🚀 Instalación Rápida (Hosting Web)

**¿Quieres instalarlo en tu hosting? ¡Es muy fácil!**

1. **Descarga** el proyecto desde GitHub (botón "Code" → "Download ZIP")
2. **Sube** los archivos a tu hosting (vía cPanel o FTP)
3. **Crea** una base de datos MySQL en tu hosting
4. **Visita** `http://tu-dominio.com/install.php`
5. **Sigue** el asistente de instalación (5 pasos)
6. **¡Listo!** Ya puedes usar PROYECTA

📖 **[Ver Guía Completa de Instalación](INSTALLATION.md)** con capturas de pantalla y solución de problemas.

---

### 💻 Instalación Local (Desarrollo)

#### Requisitos Previos

- PHP 8.0 o superior
- MySQL 8.0 o MariaDB 10.5+
- Apache (o cualquier servidor web compatible con PHP)
- Composer (opcional, para futuras dependencias)

#### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/11carlosivan/proyecta.git
cd proyecta
```

2. **Configurar la base de datos**
```bash
# Crear la base de datos
mysql -u root -p
CREATE DATABASE proyecta_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

3. **Ejecutar el instalador web**
```
http://localhost/proyecta/install.php
```

O si prefieres hacerlo manualmente:

```bash
# Copiar el archivo de configuración de ejemplo
cp config/database.example.php config/database.php

# Editar config/database.php con tus credenciales
# Luego importar el esquema
mysql -u root -p proyecta_db < database/schema.sql
```

4. **Configurar permisos**
```bash
chmod 755 uploads/
chmod 755 config/
```

5. **Acceder a la aplicación**
```
http://localhost/proyecta
```

### Usuario por Defecto

Si usaste el instalador web, habrás creado tu propio usuario administrador.

Si importaste manualmente, puedes crear uno ejecutando:
```bash
php database/create_admin.php
```

## 📁 Estructura del Proyecto

```
PROYECTA/
├── api/                    # Endpoints de API
│   ├── tasks/             # APIs de tareas
│   ├── projects/          # APIs de proyectos
│   ├── users/             # APIs de usuarios
│   └── notifications/     # APIs de notificaciones
├── assets/                # Recursos estáticos
│   ├── css/              # Estilos personalizados
│   ├── js/               # JavaScript
│   └── images/           # Imágenes
├── config/               # Configuración
│   └── database.php      # Conexión a BD (no incluido en git)
├── database/             # Base de datos
│   ├── migrations/       # Migraciones SQL
│   └── schema.sql        # Esquema completo
├── includes/             # Archivos incluidos
│   ├── auth.php          # Autenticación
│   ├── functions.php     # Funciones auxiliares
│   ├── header.php        # Header común
│   └── sidebar.php       # Sidebar común
├── modules/              # Módulos principales
│   ├── dashboard/        # Dashboard
│   ├── projects/         # Gestión de proyectos
│   ├── tasks/            # Gestión de tareas
│   ├── kanban/           # Tablero Kanban
│   └── users/            # Gestión de usuarios
├── uploads/              # Archivos subidos (no en git)
├── .gitignore           # Archivos ignorados por git
├── index.php            # Página principal
├── login.php            # Login
└── README.md            # Este archivo
```

## 🚀 Uso

### Crear un Proyecto
1. Ir a "Proyectos" en el menú lateral
2. Clic en "Nuevo Proyecto"
3. Completar el formulario
4. Asignar miembros del equipo

### Crear una Tarea
1. Ir a "Mis Tareas"
2. Clic en "Nueva Tarea"
3. Completar información básica
4. Asignar a un miembro
5. Agregar checklist, comentarios, etc.

### Usar el Tablero Kanban
1. Ir a un proyecto
2. Clic en "Ver Kanban"
3. Arrastrar y soltar tareas entre columnas
4. Editar tareas con doble clic

## 🔧 Configuración Avanzada

### Personalizar Colores
Editar `assets/css/custom.css` para personalizar la paleta de colores.

### Agregar Nuevos Roles
Modificar `includes/auth.php` y agregar lógica de permisos.

### Configurar Notificaciones por Email
(Próximamente)

## 📝 Changelog

### Versión 1.0.0 (2026-01-04)
- ✅ Sistema completo de gestión de proyectos
- ✅ Gestión avanzada de tareas
- ✅ Tablero Kanban
- ✅ Sistema de comentarios
- ✅ Checklist de subtareas
- ✅ Registro de tiempo
- ✅ Historial de cambios
- ✅ Cronología de actividades
- ✅ Sistema de notificaciones
- ✅ Modo oscuro
- ✅ Diseño responsivo

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Tu Nombre**
- GitHub: [@TU_USUARIO](https://github.com/TU_USUARIO)
- Email: tu@email.com

## 🙏 Agradecimientos

- Material Symbols por los iconos
- Tailwind CSS por el framework de estilos
- La comunidad de PHP por las mejores prácticas

## 📞 Soporte

Si encuentras algún bug o tienes alguna sugerencia:
- Abre un [Issue](https://github.com/TU_USUARIO/PROYECTA/issues)
- Envía un email a soporte@proyecta.com

---

⭐ Si te gusta este proyecto, dale una estrella en GitHub!
