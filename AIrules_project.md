# Reglas del Proyecto: Lovable to WordPress

## 1. Propósito del Documento

Este documento contiene las reglas y configuraciones **específicas del proyecto Lovable to WordPress**.

### Jerarquía de Documentación

```
📄 best_practices.md (NIVEL 1: GLOBAL)
   ↓ principios universales de desarrollo
   ↓
📄 IArules_wp_standards.md (NIVEL 2: FRAMEWORK WordPress)
   ↓ estándares aplicables a CUALQUIER proyecto WordPress
   ↓
📄 AIrules_project.md (NIVEL 3: PROYECTO - ESTE DOCUMENTO)
   ↓ configuración específica de Lovable to WordPress
```

**IMPORTANTE:** Este proyecto hereda y extiende:

1. [best_practices.md](.claude/commands/best_practices.md) - Principios universales
2. [IArules_wp_standards.md](.claude/commands/IArules_wp_standards.md) - Estándares WordPress
3. **Este documento** - Configuración específica del proyecto Lovable to WordPress

---

## 2. Información del Proyecto

### 2.1 Descripción

**Lovable to WordPress** es un plugin de WordPress que exporta diseños creados en Lovable a WordPress con soporte completo para Elementor. Soporta animaciones, contenido dinámico y tipos de post personalizados.

### 2.2 Identificadores del Proyecto

- **Nombre del proyecto:** Lovable to WordPress
- **Nombre del plugin:** lovable-to-wordpress
- **Prefijo de código:** `l2wp_`
- **Text domain:** `lovable-to-wordpress`
- **Namespace PHP:** `LovableToWordPress`
- **GitHub:** https://github.com/[usuario]/lovable-to-wordpress
- **Versión actual:** 1.0.0

### 2.3 Stack Tecnológico

#### Backend
- **PHP:** 8.0+ (mínimo requerido por WordPress)
- **WordPress:** 5.8+ (con soporte para Elementor)
- **Base de datos:** MySQL 5.7+ / MariaDB 10.3+
- **Composer:** ^2.0

#### Frontend
- **JavaScript:** ES6+ (moderno)
- **Elementor:** 3.0+ (para compatibilidad)
- **REST API:** WordPress REST API v2

#### Desarrollo
- **Git:** Control de versiones
- **WP-CLI:** Administración de WordPress
- **Local by Flywheel / Docker:** Entorno de desarrollo

---

## 3. Estructura del Proyecto

### 3.1 Organización de Carpetas

```
lovable-to-wordpress/
├── .github/
│   └── workflows/              # GitHub Actions CI/CD
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── admin/                  # Funcionalidad WP Admin
│   │   └── class-l2wp-admin.php
│   ├── frontend/               # Funcionalidad pública
│   │   └── class-l2wp-frontend.php
│   ├── api/                    # REST API endpoints
│   │   └── class-l2wp-api.php
│   ├── classes/                # Clases principales
│   │   ├── class-l2wp-exporter.php
│   │   ├── class-l2wp-importer.php
│   │   ├── class-l2wp-mapper.php
│   │   └── class-l2wp-autoloader.php
│   └── functions/              # Helper functions
│       └── l2wp-helpers.php
├── elementor/                  # Integración Elementor
│   └── class-l2wp-elementor.php
├── templates/                  # Template files
│   └── admin/
├── mapper.json                 # Mapeo de componentes
├── plugin-mappings.json        # Mapeo de plugins
├── lovable-to-wordpress.php    # Archivo principal
├── uninstall.php              # Cleanup en desinstalación
├── README.md
├── CHANGELOG.md
├── INSTALL.md
└── AIrules_project.md          # Este archivo
```

### 3.2 Convenciones de Nombres de Archivos

#### PHP
- **Plugin principal:** `lovable-to-wordpress.php`
- **Clases:** `class-l2wp-{nombre}.php` (ej: `class-l2wp-exporter.php`)
- **Templates:** `template-{nombre}.php`
- **Partials:** `partial-{componente}.php`

#### JSON
- **Mapeo:** `{nombre}-mappings.json` (ej: `plugin-mappings.json`)

#### Archivos de test
- **Tests:** `test-{funcionalidad}.php` (ej: `test-exporter.php`)

---

## 4. Convenciones de Nomenclatura

### 4.1 PHP

#### Funciones
```php
// ✅ Correcto - usar prefijo l2wp_
function l2wp_export_design( $design_id ) { }
function l2wp_map_component( $component ) { }
function l2wp_register_custom_post_type() { }

// ❌ Incorrecto - sin prefijo
function export_design( $design_id ) { }
```

#### Clases
```php
// ✅ Correcto - usar prefijo L2WP_
class L2WP_Exporter { }
class L2WP_Elementor_Mapper { }
class L2WP_REST_Controller { }

// ❌ Incorrecto - sin prefijo
class Exporter { }
class ElementorMapper { }
```

#### Constantes
```php
// ✅ Correcto - usar prefijo L2WP_
define( 'L2WP_VERSION', '1.0.0' );
define( 'L2WP_PLUGIN_DIR', __DIR__ );
define( 'L2WP_MIN_WP_VERSION', '5.8' );

// ❌ Incorrecto - sin prefijo
define( 'VERSION', '1.0.0' );
```

#### Variables
```php
// ✅ snake_case para variables locales
$design_data = array();
$component_id = 123;
$is_valid = true;
```

#### Hooks (Actions y Filters)
```php
// ✅ Correcto - usar prefijo l2wp_
do_action( 'l2wp_after_export' );
apply_filters( 'l2wp_component_data', $data );
add_action( 'init', 'l2wp_register_post_types' );

// ❌ Incorrecto - sin prefijo
do_action( 'after_export' );
apply_filters( 'component_data', $data );
```

### 4.2 JSON

#### Estructura de mappings
```json
{
  "version": "1.0.0",
  "elementor_elements": {
    "lovable_component_name": {
      "elementor_widget": "heading",
      "mapping": {
        "text": "title",
        "style": "style"
      }
    }
  }
}
```

---

## 5. Internacionalización (i18n)

### 5.1 Text Domain

**Text domain obligatorio:** `'lovable-to-wordpress'`

```php
// ✅ Siempre usar 'lovable-to-wordpress' como text domain
__( 'Export Lovable Design', 'lovable-to-wordpress' );
_e( 'Design exported successfully', 'lovable-to-wordpress' );
esc_html__( 'Settings', 'lovable-to-wordpress' );

// ❌ Nunca usar otro text domain
__( 'Export Design', 'lovable-exporter' );
__( 'Export Design' ); // Falta text domain
```

### 5.2 Generación de Archivos de Traducción

```bash
# Generar archivo .pot
wp i18n make-pot . languages/lovable-to-wordpress.pot

# Ubicación de archivos de traducción
languages/
├── lovable-to-wordpress.pot    # Plantilla
├── lovable-to-wordpress-es_ES.po
├── lovable-to-wordpress-es_ES.mo
└── lovable-to-wordpress-fr_FR.po
```

---

## 6. Funcionalidad Principal

### 6.1 Flujo de Exportación

```
1. Usuario selecciona diseño en Lovable
2. Plugin recibe datos del diseño (JSON)
3. Mapper traduce componentes a Elementor
4. Valida compatibilidad
5. Crea post en WordPress
6. Asigna widgets de Elementor
7. Aplica estilos y animaciones
8. Guarda post como borrador/publicado
```

### 6.2 Mapeo de Componentes

El mapeo se define en `plugin-mappings.json`:

```json
{
  "lovable_component": "elementor_widget",
  "text": "text",
  "button": "button",
  "image": "image",
  "container": "container"
}
```

### 6.3 REST API Endpoints

**Todos los endpoints usan prefijo `l2wp/v1`:**

```
POST   /wp-json/l2wp/v1/export     - Exportar diseño
GET    /wp-json/l2wp/v1/designs    - Listar diseños
GET    /wp-json/l2wp/v1/designs/:id - Obtener diseño
PUT    /wp-json/l2wp/v1/designs/:id - Actualizar diseño
DELETE /wp-json/l2wp/v1/designs/:id - Eliminar diseño
```

---

## 7. Características Soportadas

### 7.1 Componentes Lovable Soportados

- [ ] Headings (h1-h6)
- [ ] Paragraphs
- [ ] Buttons
- [ ] Images
- [ ] Containers
- [ ] Forms
- [ ] Lists
- [ ] Cards
- [ ] Custom components

### 7.2 Integraciones

- **Elementor:** Soporte completo (3.0+)
- **Elementor Pro:** Animaciones avanzadas
- **WooCommerce:** Integración de productos
- **ACF:** Campos personalizados

### 7.3 Características de Exportación

- ✅ Mapeo automático de componentes
- ✅ Preservación de estilos
- ✅ Animaciones
- ✅ Contenido dinámico
- ✅ Tipos de post personalizados
- ✅ Meta campos

---

## 8. Testing

### 8.1 Archivos de Test

```
test-exporter.php      # Tests de exportación
test-mapper.php        # Tests de mapeo
test-elementor.php     # Tests de integración Elementor
test-admin.php         # Tests de admin
```

### 8.2 Ejecución de Tests

```bash
# Con WP-CLI
wp plugin test lovable-to-wordpress

# Manual
php test-exporter.php
```

---

## 9. Versionado y Releases

### 9.1 Archivos que Requieren Actualización de Versión

Al cambiar la versión del proyecto, actualizar en:

1. **lovable-to-wordpress.php** - Plugin header `Version:`
2. **lovable-to-wordpress.php** - Constante `L2WP_VERSION`
3. **README.md** - Sección de versión
4. **CHANGELOG.md** - Agregar entrada de cambios
5. **package.json** - Si aplica

### 9.2 Proceso de Release

```bash
# 1. Actualizar archivos de versión
# (en archivos mencionados arriba)

# 2. Commit de cambios
git add .
git commit -m "chore: bump version to 1.0.0"

# 3. Crear tag
git tag -a v1.0.0 -m "Release version 1.0.0"

# 4. Push con tags
git push origin main --tags
```

---

## 10. Configuración de Desarrollo

### 10.1 Entorno Local

**Requisitos:**
- PHP 8.0+
- WordPress 5.8+
- MySQL 5.7+ / MariaDB 10.3+
- Elementor 3.0+
- WP-CLI (recomendado)
- Git

**Setup inicial:**

```bash
# 1. Clonar repositorio
git clone https://github.com/yourusername/lovable-to-wordpress.git
cd lovable-to-wordpress

# 2. Activar plugin en WordPress
wp plugin activate lovable-to-wordpress

# 3. Verificar instalación
wp plugin status lovable-to-wordpress
```

### 10.2 Configuración de PHPCS

**phpcs.xml:**

```xml
<?xml version="1.0"?>
<ruleset name="Lovable to WordPress">
    <description>WordPress Coding Standards</description>

    <file>.</file>

    <!-- Exclude patterns -->
    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    <exclude-pattern>tests/*</exclude-pattern>

    <!-- Use WordPress Coding Standards -->
    <rule ref="WordPress">
        <exclude name="WordPress.Files.FileName"/>
    </rule>

    <!-- Prefixes -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="l2wp"/>
                <element value="L2WP"/>
                <element value="LovableToWordPress"/>
            </property>
        </properties>
    </rule>

    <!-- Text domain -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="lovable-to-wordpress"/>
            </property>
        </properties>
    </rule>
</ruleset>
```

---

## 11. Deployment y Hosting

### 11.1 Requisitos de Hosting

- PHP 8.0 o superior
- WordPress 5.8 o superior
- Soporte para REST API habilitado
- Escritura en directorio de plugins
- Soporte para CORS (si es necesario)

### 11.2 Instalación en Producción

```bash
# 1. Descargar plugin
# Desde GitHub Releases o WordPress.org

# 2. Subir a wp-content/plugins/
scp -r lovable-to-wordpress/ usuario@servidor:/var/www/html/wp-content/plugins/

# 3. Activar plugin
wp plugin activate lovable-to-wordpress --allow-root

# 4. Verificar
wp plugin status lovable-to-wordpress --allow-root
```

---

## 12. Checklist de Calidad del Proyecto

Además de los checklists en [best_practices.md](.claude/commands/best_practices.md) y [IArules_wp_standards.md](.claude/commands/IArules_wp_standards.md), verificar:

### Específico de Lovable to WordPress

- [ ] Prefijo `l2wp_` usado consistentemente en todo el código
- [ ] Text domain `'lovable-to-wordpress'` en todas las strings traducibles
- [ ] Constantes del proyecto definidas (L2WP_VERSION, L2WP_PLUGIN_DIR, etc.)
- [ ] Mapeo de componentes actualizado en plugin-mappings.json
- [ ] Compatibilidad con Elementor probada
- [ ] Validación de datos JSON de Lovable
- [ ] Manejo de errores en exportación
- [ ] REST API endpoints protegidos con nonces/capabilities
- [ ] Tests de exportación pasan
- [ ] Compatibilidad con WordPress 5.8+ verificada
- [ ] Compatibilidad con PHP 8.0+ verificada
- [ ] CHANGELOG.md actualizado
- [ ] README.md refleja funcionalidad actual
- [ ] No hay código comentado/de test en producción

---

## 13. Contacto y Recursos

### 13.1 Documentación

- **README.md** - Guía de instalación y uso
- **INSTALL.md** - Instrucciones detalladas
- **CHANGELOG.md** - Registro de cambios
- **GitHub Issues** - Reporte de bugs y features

### 13.2 Referencias Útiles

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [Elementor Developer Docs](https://developers.elementor.com/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WP-CLI Handbook](https://wp-cli.org/)

---

## 14. Conclusión

Este documento define la configuración específica del proyecto Lovable to WordPress.

**Recordatorio:** Seguir los tres niveles de documentación:

1. [best_practices.md](.claude/commands/best_practices.md) - Principios universales
2. [IArules_wp_standards.md](.claude/commands/IArules_wp_standards.md) - Estándares WordPress
3. **AIrules_project.md** (este documento) - Configuración Lovable to WordPress

Cualquier código generado debe cumplir con los tres niveles de reglas.

---

**Versión**: 1.0.0
**Última actualización**: 2025-10-23
**Proyecto:** Lovable to WordPress
**GitHub:** https://github.com/[usuario]/lovable-to-wordpress
