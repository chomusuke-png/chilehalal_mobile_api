# ChileHalal Mobile API

Este plugin para WordPress actúa como el **Backend Headless** para la aplicación móvil ChileHalal. Proporciona una interfaz de administración personalizada para gestionar productos y usuarios, y expone una API REST segura mediante **JWT (JSON Web Tokens)**.

## 🚀 Características principales

* **Custom Post Types (CPT):** Gestión aislada de `Productos` y `Usuarios`.
* **Arquitectura Modular:** Separación estricta de la estructura de archivos.
* **Seguridad JWT:** Autenticación robusta para el registro y login de usuarios mediante la librería `firebase/php-jwt`.
* **Zero Config Secret:** Generación automática de una clave secreta criptográfica al activar el plugin, con soporte para sobrescritura manual en `wp-config.php`.
* **Panel de Administración:** Dashboard personalizado con estadísticas rápidas de la base de datos de la App.

## 🛠️ Instalación

1. Sube la carpeta `chilehalal-api` al directorio `/wp-content/plugins/`.
2. Asegúrate de que la carpeta `vendor` esté incluida (si no, ejecuta `composer install` localmente antes de subir).
3. Activa el plugin desde el panel de **Plugins** en WordPress.
4. **Importante:** Ve a *Ajustes > Enlaces permanentes* y haz clic en "Guardar cambios" para refrescar las rutas de la API.

## 📡 Endpoints de la API

La base de la API es: `https://tu-dominio.com/wp-json/chilehalal/v1`

| Método | Endpoint | Descripción | Requisito |
| --- | --- | --- | --- |
| **GET** | `/scan/{barcode}` | Busca un producto por código de barras. | Público |
| **POST** | `/auth/register` | Registra un nuevo Usuario App. | Público |
| **POST** | `/auth/login` | Valida credenciales y devuelve un JWT. | Público |
| **GET** | `/user/me` | Obtiene la información del perfil actual. | **Token JWT** |

## 🔒 Configuración de Seguridad

El plugin genera automáticamente una clave secreta en la base de datos. Para mayor seguridad en entornos de producción, puedes definir la clave manualmente en tu `wp-config.php`:

```php
define( 'CH_JWT_SECRET', 'tu_clave_secreta_super_larga_aqui' );

```