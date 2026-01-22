# ChileHalal Mobile API - Documentación Técnica

Este plugin transforma WordPress en un **Backend Headless** para la aplicación móvil ChileHalal, gestionando productos, usuarios y autenticación segura mediante **JWT**.

## 🛠️ Instalación

1. Vaya al panel de administrador de WordPress (wp-admin).
2. Diríjase a **Plugins** -> **Añadir plugin** -> **Subir plugin**.
3. Asegúrese de que la carpeta `vendor` esté incluida (si no, ejecute `composer install` localmente antes de subir).
4. Suba el archivo `chilehalal-api.zip` y dele en **Activar**.
5. **Importante:** Ve a *Ajustes > Enlaces permanentes* y haz clic en "Guardar cambios" para refrescar las rutas de la API.

---

## 📡 Endpoints de la API

La URL base para todas las consultas es: `https://tu-dominio.com/wp-json/chilehalal/v1`.

| Método | Endpoint | Descripción | Requisito |
| --- | --- | --- | --- |
| **GET** | `/products` | Lista de productos con paginación (16 por página), búsqueda y categorías. | Público |
| **POST** | `/products` | Crea un nuevo producto. (Validación estricta de marca para Partners). | **Token JWT** |
| **GET** | `/scan/{barcode}` | Busca los detalles de un producto específico mediante su código de barras. | Público |
| **POST** | `/auth/register` | Registra un nuevo usuario en la aplicación. | Público |
| **POST** | `/auth/login` | Autentica al usuario y devuelve un Token JWT. | Público |
| **GET** | `/user/me` | Retorna el perfil, rol y marcas asignadas del usuario autenticado. | **Token JWT** |

---

## 👥 Roles y Permisos (ACL)

El sistema implementa una lógica de control de acceso basada en roles:

| Rol | Permisos | Descripción |
| --- | --- | --- |
| **User** | Lectura | Solo puede ver productos y escanear. (Rol por defecto al registrarse). |
| **Partner** | Gestión Limitada | Puede crear, editar y eliminar productos **solo de sus marcas asignadas**. |
| **Editor** | Gestión Global | Puede gestionar productos de cualquier marca. |
| **Owner** | Super Admin | Acceso total al sistema. |

---

## 🗄️ Estructura de Datos (Base de Datos)

El plugin utiliza la arquitectura nativa de WordPress basada en **Custom Post Types (CPT)** y **Taxonomías**.

### 1. Entidad: Productos (`ch_product`)

Se utiliza para catalogar los artículos escaneables por la aplicación.

* **Taxonomía:** `ch_product_category` (Categorías del producto, ej: Lácteos, Carnes).

| Campo (Meta Key) | Tipo | Descripción |
| --- | --- | --- |
| `_ch_barcode` | `string` | Código de barras único del producto (EAN/UPC). |
| `_ch_is_halal` | `string` | Estado: `yes` (Certificado), `no` (Haram), `doubt` (Dudoso). |
| `_ch_brand` | `string` | Marca o fabricante del producto (Crucial para permisos de Partner). |
| `_ch_description` | `text` | Ingredientes y detalles técnicos adicionales. |
| `_thumbnail_id` | `int` | ID de la imagen destacada en la biblioteca de medios. |

### 2. Entidad: Usuarios App (`ch_app_user`)

Gestión de usuarios registrados específicamente para el ecosistema móvil.

| Campo (Meta Key) | Tipo | Descripción |
| --- | --- | --- |
| `_ch_user_email` | `string` | Correo electrónico de acceso (debe ser único). |
| `_ch_user_pass_hash` | `string` | Contraseña cifrada mediante `wp_hash_password`. |
| `_ch_user_status` | `string` | Estado de cuenta: `active`, `banned` o `pending`. |
| `_ch_user_role` | `string` | Nivel de permisos: `user`, `partner`, `editor`, `owner`. |
| `_ch_user_company` | `string` | (Solo Partner) Nombre de la empresa o razón social. |
| `_ch_user_brands` | `array` | (Solo Partner) Lista de marcas que el usuario puede gestionar. |
| `_ch_user_phone` | `string` | Número de contacto del usuario. |

---

## 🔒 Seguridad y Autenticación

### Generación de Clave Secreta

Al activar el plugin, se genera automáticamente una clave criptográfica de 32 bytes en la base de datos (`ch_jwt_secret_db`). Para entornos de producción, se recomienda definirla en el archivo `wp-config.php`:

```php
define( 'CH_JWT_SECRET', 'tu_clave_secreta_personalizada' );

```

### Validación de Token

Para los endpoints protegidos, se debe enviar el token en la cabecera HTTP:
`Authorization: Bearer <TU_TOKEN_JWT>`.

El token expira automáticamente tras **7 días** de su emisión.
