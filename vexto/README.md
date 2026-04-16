# VEXTO 2.5 - Plataforma de Bienes Raíces

Una plataforma moderna y minimalista para la compra, venta y alquiler de propiedades con características avanzadas de administración.

## Características Principales

### 🎨 Diseño Moderno
- Interfaz minimalista con colores blanco y negro
- Tema claro y oscuro automático
- Diseño responsivo para todos los dispositivos
- Estilos CSS modernos con transiciones suaves

### 🏠 Gestión de Propiedades
- Publicar, editar y eliminar propiedades
- Galería de imágenes con zoom y navegación
- Búsqueda y filtrado avanzado
- Favoritos y reseñas

### 👥 Sistema de Usuarios
- Registro e inicio de sesión mejorado
- Recuperación de contraseña por email
- Perfiles de usuario y empresa
- Verificación de usuarios

### 💬 Comunicación
- Sistema de mensajes entre usuarios
- Citas para ver propiedades
- Feedback de usuarios
- Notificaciones

### 🛡️ Panel de Administración
- Acceso restringido para administradores
- Registro de auditoría completo
- Gestión de feedback
- Estadísticas de la plataforma
- Monitoreo de movimientos de usuarios

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache, Nginx, etc.)
- Extensiones PHP: PDO, MySQL

## Instalación

### 1. Configuración de la Base de Datos

```bash
# Crear la base de datos
mysql -u root -p < config/database.sql

# O ejecutar el script de configuración del admin
php config/admin_setup.php
```

### 2. Configuración del Entorno

Copia el archivo `.env.example` a `.env` y actualiza las variables:

```bash
cp .env.example .env
```

Edita `.env` con tus credenciales de base de datos:

```
DB_HOST=localhost
DB_NAME=vexto_db
DB_USER=root
DB_PASS=
APP_ENV=development
```

### 3. Permisos de Carpetas

Asegúrate de que las siguientes carpetas tengan permisos de escritura:

```bash
chmod 755 uploads/
chmod 755 publicaciones/
chmod 755 logs/
```

### 4. Acceso a la Aplicación

- **URL Principal**: `http://localhost/vexto/`
- **Panel de Admin**: `http://localhost/vexto/views/admin_dashboard.php`

## Credenciales de Acceso

### Administrador

- **Email**: admin@vexto.com
- **Contraseña**: 569246

### Usuario de Ejemplo

- **Email**: melvyn@vexto.com
- **Contraseña**: (configurar en el registro)

## Estructura del Proyecto

```
vexto/
├── assets/
│   ├── css/
│   │   ├── modern.css          # Estilos minimalistas modernos
│   │   ├── auth_modern.css     # Estilos de autenticación
│   │   └── gallery.css         # Estilos de galería de imágenes
│   └── js/
│       ├── theme.js            # Gestor de temas claro/oscuro
│       └── image-gallery.js    # Galería de imágenes con zoom
├── config/
│   ├── constants.php           # Constantes de la aplicación
│   ├── database.sql            # Esquema de la base de datos
│   ├── db.php                  # Conexión a la base de datos
│   └── admin_setup.php         # Script de configuración del admin
├── core/
│   ├── User.php                # Clase de usuarios
│   ├── Property.php            # Clase de propiedades
│   ├── Feedback.php            # Clase de feedback
│   ├── AuditLog.php            # Clase de auditoría
│   ├── PasswordReset.php       # Clase de recuperación de contraseña
│   └── helpers.php             # Funciones de utilidad
├── public/
│   ├── index.php               # Página de inicio (antigua)
│   ├── index_modern.php        # Página de inicio mejorada
│   ├── auth.php                # Controlador de autenticación
│   ├── forgot_password.php     # Página de recuperación de contraseña
│   └── reset_password.php      # Controlador de restablecimiento
├── views/
│   ├── dashboard.php           # Panel principal
│   ├── admin_dashboard.php     # Panel de administración
│   ├── feedback.php            # Página de feedback
│   ├── property_details.php    # Detalles de propiedad
│   ├── publish.php             # Publicar propiedad
│   └── ...
├── includes/
│   └── header.php              # Encabezado compartido
├── publicaciones/              # Imágenes de propiedades
├── uploads/                    # Cargas de usuarios
├── logs/                       # Archivos de registro
└── README.md                   # Este archivo
```

## Características Principales

### 🎯 Autenticación Mejorada

- Registro con validación de email
- Inicio de sesión seguro
- Recuperación de contraseña por email
- Soporte para usuarios individuales y empresas

### 🖼️ Galería de Imágenes

- Zoom en imágenes de propiedades
- Navegación con flechas y teclado
- Lightbox responsivo
- Soporte para múltiples imágenes

### 📊 Panel de Administración

Acceso restringido solo para administradores:

- **Resumen**: Estadísticas generales del sistema
- **Auditoría**: Registro completo de todos los movimientos
- **Feedback**: Gestión de comentarios de usuarios
- **Usuarios**: Listado y gestión de usuarios
- **Propiedades**: Listado y gestión de propiedades

### 💬 Sistema de Feedback

Los usuarios pueden enviar:
- Sugerencias de mejora
- Reportes de problemas
- Elogios

El administrador puede:
- Ver todos los feedback
- Cambiar el estado (nuevo, en revisión, resuelto, rechazado)
- Responder a los usuarios

### 📋 Auditoría de Movimientos

Se registran automáticamente:
- Creación de propiedades
- Edición de propiedades
- Eliminación de propiedades
- Cambios de perfil
- Inicio de sesión
- Cambios de tema
- Y más...

## Uso

### Para Usuarios Normales

1. Regístrate en la plataforma
2. Completa tu perfil
3. Publica propiedades (máximo 3)
4. Busca y guarda favoritos
5. Contacta con vendedores
6. Envía feedback sobre tu experiencia

### Para Empresas

1. Regístrate como empresa
2. Completa información de la empresa
3. Publica propiedades (máximo 20)
4. Gestiona tus propiedades
5. Responde consultas de clientes

### Para Administradores

1. Accede con credenciales de admin
2. Monitorea la actividad de la plataforma
3. Revisa el feedback de usuarios
4. Gestiona usuarios y propiedades
5. Analiza estadísticas

## Seguridad

- Contraseñas hasheadas con bcrypt
- Validación de entrada en todos los formularios
- Protección CSRF
- Sanitización de datos
- Registros de auditoría
- Tokens de recuperación de contraseña con expiración

## Temas

La plataforma soporta automáticamente:
- **Tema Claro**: Fondo blanco, texto negro
- **Tema Oscuro**: Fondo negro, texto blanco

El tema se detecta automáticamente según las preferencias del sistema operativo y se puede cambiar manualmente.

## Desarrollo

### Agregar Nuevas Funcionalidades

1. Crea una nueva clase en `core/` si es necesario
2. Crea la vista en `views/`
3. Registra los movimientos en `AuditLog`
4. Prueba la funcionalidad
5. Actualiza la documentación

### Registrar Movimientos en Auditoría

```php
$auditLog = new AuditLog($pdo);
$auditLog->log(
    $userId,
    'create_property',
    'property',
    $propertyId,
    null,
    ['titulo' => $titulo, 'precio' => $precio]
);
```

## Solución de Problemas

### Error de conexión a base de datos

Verifica que:
- MySQL está ejecutándose
- Las credenciales en `.env` son correctas
- La base de datos existe

### Las imágenes no se cargan

Verifica que:
- Las carpetas `uploads/` y `publicaciones/` existen
- Tienen permisos de lectura/escritura
- Los archivos están en el lugar correcto

### El tema no cambia

Verifica que:
- JavaScript está habilitado
- El archivo `theme.js` se carga correctamente
- localStorage está disponible

## Soporte

Para reportar problemas o sugerencias, usa el sistema de feedback integrado en la plataforma.

## Licencia

VEXTO © 2024. Todos los derechos reservados.

## Versión

**VEXTO 2.5** - Edición Mejorada con Diseño Minimalista Moderno
