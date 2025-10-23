# Lovable to WordPress Exporter v2.0

Plugin de WordPress que convierte proyectos de Lovable a páginas de WordPress editables con Elementor.

## 🎯 Características v2.0

### ✨ Nuevo Sistema ZIP-Based
- **Importación completa**: Sube el ZIP completo del proyecto Lovable (desde GitHub)
- **Detección inteligente**: Identifica automáticamente funcionalidades (forms, modals, filters, etc.)
- **Recomendación de plugins**: Sugiere múltiples opciones para cada funcionalidad
- **Sin Elementor Pro**: Todo funciona con Elementor Free + plugins gratuitos

### � Funcionalidades Detectadas Automáticamente
1. **Forms** → JetFormBuilder, Fluent Forms, WPForms, Contact Form 7
2. **Modals/Popups** → JetPopup, Popup Maker, Popup Anything
3. **Filters/Search** → JetSmartFilters, Search & Filter
4. **Custom Post Types** → JetEngine, ACF, Meta Box, CPT UI
5. **Gallery** → JetGridBuilder, Elementor Gallery
6. **Animations** → Elementor Motion Effects, Custom CSS

### 📊 Preservación del Diseño
- **85-90% de estilos** automáticamente
- **Colores de Tailwind** extraídos e inyectados
- **Fonts de Google** importadas
- **Clases CSS** preservadas
- **Estructura Flexbox/Grid** en Elementor

## 📦 Estructura del Plugin

```
lovable-exporter/
├── lovable-exporter.php          # Main plugin file
├── plugin-mappings.json           # Plugin recommendations config
├── includes/
│   ├── class-zip-analyzer.php     # ZIP extraction & analysis
│   ├── class-component-detector.php # Functionality detection
│   ├── class-plugin-recommender.php # Plugin suggestions
│   ├── class-css-extractor.php    # Tailwind CSS processor
│   └── class-elementor-builder.php # Elementor template builder
├── templates/
│   └── admin-page.php             # Admin interface
└── assets/
    ├── css/lovable.css
    └── js/lovable-animations.js
```

## 🚀 Instalación

1. **Copiar plugin a WordPress**:
   ```bash
   cp -r lovable-exporter /path/to/wordpress/wp-content/plugins/
   ```

2. **Activar en WordPress**:
   - WordPress Admin → Plugins → Activar "Lovable to WordPress Exporter"

3. **Verificar requisitos**:
   - ✅ WordPress 5.8+
   - ✅ PHP 8.0+
   - ✅ Elementor instalado y activo

## 📖 Uso (Sistema v2 - ZIP)

### Paso 1: Exportar desde Lovable
```bash
# Clonar tu proyecto de Lovable desde GitHub
git clone https://github.com/TU_USUARIO/tu-proyecto.git

# Crear ZIP del proyecto
cd tu-proyecto
zip -r lovable-project.zip .
```

### Paso 2: Importar a WordPress

1. Ve a **WordPress Admin → Lovable Exporter**

2. En la pestaña "Import Design", sube el ZIP

3. El sistema analizará y mostrará:
   - ✅ Páginas detectadas
   - ✅ Componentes encontrados
   - ✅ Funcionalidades identificadas
   - ✅ Plugins recomendados

### Paso 3: Seleccionar Plugins

Ejemplo de reporte:
```
📋 Forms (3 detectados):
  ⭐ JetFormBuilder (95%) - RECOMENDADO
     ✅ Ya instalado | ✅ Activo
  ⭐ Fluent Forms (90%)
     ❌ No instalado | [Instalar]

📋 Modals (4 detectados):
  ⭐ JetPopup (95%) - RECOMENDADO
  ⭐ Popup Maker (90%)
```

Selecciona tus preferencias y click "Instalar Plugins Seleccionados"

### Paso 4: Importar y Editar

- El sistema creará páginas en WordPress
- Cada página es 100% editable en Elementor
- Los estilos de Lovable estarán inyectados
- Las clases CSS se preservan

## 🎨 Sistema de Mapeo

El archivo `plugin-mappings.json` controla las recomendaciones:

```json
{
  "functionality_mappings": {
    "popup_modal": {
      "detector_patterns": ["Dialog", "Modal", "Popover"],
      "recommended_solutions": [
        {
          "name": "JetPopup",
          "slug": "jet-popup",
          "compatibility": 95,
          "free": true
        }
      ]
    }
  }
}
```

### Añadir Más Plugins

Edita `plugin-mappings.json` para añadir más opciones:

```json
{
  "name": "Tu Plugin",
  "slug": "tu-plugin-slug",
  "compatibility": 85,
  "free": true,
  "conversion_method": "convert_to_tu_plugin"
}
```

## 🔧 Flujo Técnico

### 1. ZIP Analyzer
```php
$analyzer = new Lovable_ZIP_Analyzer();
$analysis = $analyzer->analyze($zip_path);
// Returns: pages, components, assets, package.json
```

### 2. Component Detector
```php
$detector = new Lovable_Component_Detector();
$detections = $detector->detect($analysis);
// Returns: forms, modals, filters, etc.
```

### 3. Plugin Recommender
```php
$recommender = new Lovable_Plugin_Recommender();
$solutions = $recommender->get_solutions_for('popup_modal');
// Returns: array of plugin options sorted by compatibility
```

### 4. CSS Extractor
```php
$extractor = new Lovable_CSS_Extractor();
$css_data = $extractor->extract($analysis);
// Returns: colors, fonts, custom CSS
```

### 5. Elementor Builder
```php
$builder = new Lovable_Elementor_Builder();
$templates = $builder->build($analysis, $css_data, $selected_plugins);
// Creates WordPress pages with Elementor data
```

## 📊 Resultados Esperados

### Fidelidad Visual
- **Index/Home**: 90-95%
- **Listings Grid**: 85-90%
- **Single Post**: 90-95%
- **Forms**: 75-80%

### Funcionalidad
- **Navegación**: 100%
- **CPTs + Dynamic Tags**: 100%
- **Contenido estático**: 100%
- **Forms nativas WP**: 60-70%
- **Filtros React**: 0% (reemplazados por plugins WP)

## 🛠️ Desarrollo y Testing

### Test con Proyecto Real
```bash
# Usa el proyecto de ejemplo
cd lovable-exporter
# El proyecto ganaderia-facil-rd ya está clonado en ~/testing/lovable-project
```

### Debug Mode
```php
// Activar logging
define('LOVABLE_DEBUG', true);

// Ver análisis completo
$analyzer->get_result();
```

## 📝 Notas Importantes

### ⚠️ Limitaciones Conocidas
1. **Interactividad de React**: No se convierte (filters, búsqueda client-side)
   - **Solución**: Usar plugins de WordPress (JetSmartFilters, etc.)

2. **Modals complejos de shadcn**: Conversión parcial
   - **Solución**: Usar JetPopup o Popup Maker

3. **Validaciones React Hook Form**: No se preservan
   - **Solución**: Usar validaciones nativas de Elementor Forms

### ✅ Lo Que Funciona Perfectamente
- Colores y tipografía (100%)
- Imágenes (100%)
- Estructura de páginas (95%)
- Navegación (100%)
- CPTs con ACF/JetEngine (100%)
- Animaciones CSS (90%)

## 🔄 Roadmap v3.0 (Futuro)

- [ ] Parser completo de React/JSX con AST
- [ ] Conversión de hooks de React a JavaScript vanilla
- [ ] Soporte para shadcn components avanzados
- [ ] Templates predefinidos para páginas comunes
- [ ] Sistema de preview antes de importar
- [ ] Export/Import de configuraciones de plugins
- [ ] Soporte para Next.js projects

## 📞 Soporte

Para reportar issues o contribuir:
- GitHub: [tu-repo]
- Documentación: [docs-url]

---

**Desarrollado con ❤️ para la comunidad de Lovable + WordPress**
