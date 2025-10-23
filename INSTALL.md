# Installation Guide - Lovable to WordPress Exporter

This guide will help you install and configure the Lovable to WordPress Exporter plugin.

## Prerequisites

Before installing, ensure you have:

- ✅ WordPress 5.8 or higher
- ✅ PHP 8.0 or higher
- ✅ Elementor (Free or Pro) installed and activated
- ✅ (Optional) ACF, JetEngine, Meta Box, or CPT UI for dynamic content

## Installation Methods

### Method 1: Direct Upload (Recommended)

1. **Prepare the Plugin**
   - Download or locate the `lovable-exporter` folder
   - If you have a ZIP file, extract it first

2. **Create ZIP File**
   ```bash
   cd /path/to/lovable-exporter
   cd ..
   zip -r lovable-exporter.zip lovable-exporter/
   ```

3. **Upload to WordPress**
   - Log in to your WordPress admin panel
   - Navigate to **Plugins > Add New**
   - Click the **Upload Plugin** button at the top
   - Click **Choose File** and select `lovable-exporter.zip`
   - Click **Install Now**
   - Wait for the upload and installation to complete
   - Click **Activate Plugin**

### Method 2: FTP/SFTP Upload

1. **Connect via FTP**
   - Use your preferred FTP client (FileZilla, Cyberduck, etc.)
   - Connect to your WordPress site

2. **Upload Plugin Folder**
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `lovable-exporter` folder
   - Ensure all files and subdirectories are uploaded

3. **Activate the Plugin**
   - Go to WordPress Admin > **Plugins**
   - Find "Lovable to WordPress Exporter"
   - Click **Activate**

### Method 3: WP-CLI (For Developers)

```bash
# Navigate to your WordPress installation
cd /path/to/wordpress

# Copy plugin to plugins directory
cp -r /path/to/lovable-exporter wp-content/plugins/

# Activate the plugin
wp plugin activate lovable-exporter

# Verify installation
wp plugin list
```

## Post-Installation Setup

### 1. Verify Installation

After activation, you should see:
- **Lovable Exporter** menu item in WordPress admin sidebar
- No error messages in the admin area
- Plugin listed under **Plugins** with version 1.0.0

### 2. Check System Requirements

Navigate to **Lovable Exporter > Help** to view:
- WordPress version
- PHP version
- Elementor status
- Active custom fields plugin

### 3. Configure Settings

Go to **Lovable Exporter > Settings**:

- ✅ **Enable Animations** - Keep animations active (recommended)
- ✅ **Lazy Loading** - Enable for better performance
- ✅ **Critical CSS** - Enable for faster page loads

Click **Save Changes**

### 4. Test the Plugin

1. Go to **Lovable Exporter**
2. In the **Export Design** tab, paste this test JSON:

```json
{
  "title": "Test Design",
  "type": "page",
  "sections": [
    {
      "layout": "boxed",
      "animation": "fadeInUp",
      "columns": [
        {
          "width": 100,
          "widgets": [
            {
              "type": "heading",
              "content": "Hello Lovable!",
              "tag": "h1",
              "animation": "fadeInDown"
            },
            {
              "type": "text",
              "content": "This is a test export."
            }
          ]
        }
      ]
    }
  ]
}
```

3. Click **Export to Elementor**
4. If successful, you'll be redirected with a success message
5. Click the **Edit in Elementor** link to verify

## Troubleshooting Installation

### Plugin Won't Activate

**Error**: "The plugin does not have a valid header"

**Solution**:
- Ensure the `lovable-exporter.php` file is in the root of the plugin folder
- Check that the folder structure is correct: `wp-content/plugins/lovable-exporter/lovable-exporter.php`

### Missing Dependencies

**Error**: "Fatal error: Class 'Lovable_API_Bridge' not found"

**Solution**:
- Verify all files were uploaded correctly
- Check the `includes/` directory contains all PHP class files
- Re-upload the plugin completely

### PHP Version Error

**Error**: "This plugin requires PHP 8.0 or higher"

**Solution**:
- Contact your hosting provider to upgrade PHP
- Or use WordPress Site Health to check available PHP versions

### Permissions Issues

**Error**: "Failed to create directory" or "Permission denied"

**Solution**:
```bash
# Set correct permissions (via SSH)
cd /path/to/wp-content/plugins
chmod 755 lovable-exporter
chmod 644 lovable-exporter/*.php
chmod 755 lovable-exporter/assets
chmod 644 lovable-exporter/assets/**/*
```

### Elementor Not Detected

**Warning**: "Elementor: Not Active"

**Solution**:
1. Install Elementor from **Plugins > Add New**
2. Search for "Elementor"
3. Install and activate
4. Refresh the Lovable Exporter page

## Updating the Plugin

### Manual Update

1. **Deactivate** the current version
2. **Delete** the old plugin folder via FTP
3. **Upload** the new version
4. **Activate** the plugin again

**Note**: Your settings and templates will be preserved as they're stored in the WordPress database.

### Automated Update (Future Feature)

Currently, the plugin must be updated manually. Future versions will support automatic updates via WordPress.org repository.

## Uninstalling

### Soft Uninstall (Recommended)

1. Go to **Plugins**
2. Find "Lovable to WordPress Exporter"
3. Click **Deactivate**
4. Templates and settings are preserved

### Complete Uninstall

1. Deactivate the plugin
2. Click **Delete**
3. This will remove:
   - Plugin files
   - Plugin settings
   - **Note**: Elementor templates created by Lovable will remain

### Manual Cleanup (If Needed)

```sql
-- Remove plugin options from database
DELETE FROM wp_options WHERE option_name LIKE 'lovable_%';

-- Optional: Remove Lovable templates
DELETE FROM wp_posts WHERE post_type = 'elementor_library' 
AND EXISTS (
  SELECT 1 FROM wp_postmeta 
  WHERE post_id = wp_posts.ID 
  AND meta_key = '_lovable_source' 
  AND meta_value = '1'
);
```

## Server Requirements

### Minimum Requirements

- **PHP**: 8.0+
- **MySQL**: 5.6+ or MariaDB 10.1+
- **WordPress**: 5.8+
- **Memory**: 128MB (256MB recommended)
- **Upload Max Filesize**: 64MB+

### Recommended Server Settings

Add to `php.ini` or `.htaccess`:

```ini
upload_max_filesize = 128M
post_max_size = 128M
max_execution_time = 300
memory_limit = 256M
```

### Apache .htaccess (Optional)

```apache
# Protect plugin files
<FilesMatch "\.(json|md)$">
  Order allow,deny
  Deny from all
</FilesMatch>

# Enable GZIP compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/css application/javascript
</IfModule>
```

## Development Installation

For developers who want to contribute:

```bash
# Clone repository (if using Git)
git clone https://github.com/yourusername/lovable-exporter.git

# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins

# Create symbolic link
ln -s /path/to/lovable-exporter lovable-exporter

# Activate via WP-CLI
wp plugin activate lovable-exporter

# Enable debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Multi-Site Installation

### Network Activation

1. Upload plugin to `/wp-content/plugins/`
2. Go to **Network Admin > Plugins**
3. Click **Network Activate**

### Per-Site Activation

1. Upload plugin to `/wp-content/plugins/`
2. Go to each site's **Plugins** page
3. Activate individually per site

## Docker Setup (Development)

```yaml
version: '3'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
    volumes:
      - ./lovable-exporter:/var/www/html/wp-content/plugins/lovable-exporter
```

```bash
docker-compose up -d
```

## Support & Resources

- **Documentation**: See README.md
- **Issues**: Report bugs on GitHub
- **Community**: WordPress Support Forums

## Next Steps

After successful installation:

1. ✅ Read the [README.md](README.md) for usage instructions
2. ✅ Check the **Field Mapper** tab to see detected plugins
3. ✅ Review available **REST API endpoints** in Help tab
4. ✅ Export your first Lovable design!

---

**Installation complete! Ready to start exporting Lovable designs to WordPress.**
