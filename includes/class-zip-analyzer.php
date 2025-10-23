<?php
/**
 * ZIP Analyzer Class
 * 
 * Analyzes uploaded Lovable project ZIP files
 * Extracts project structure, components, and assets
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_ZIP_Analyzer {
    
    private $zip_path;
    private $extract_path;
    private $analysis_result;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->extract_path = wp_upload_dir()['basedir'] . '/lovable-temp/';
    }
    
    /**
     * Analyze uploaded ZIP file
     * 
     * @param string $zip_file_path Path to uploaded ZIP
     * @return array|WP_Error Analysis result or error
     */
    public function analyze($zip_file_path) {
        if (!file_exists($zip_file_path)) {
            return new WP_Error('file_not_found', __('ZIP file not found', 'lovable-exporter'));
        }
        
        $this->zip_path = $zip_file_path;
        
        // Extract ZIP
        $extracted = $this->extract_zip();
        if (is_wp_error($extracted)) {
            return $extracted;
        }
        
        // Analyze project structure
        $structure = $this->analyze_structure();
        
        // Detect pages
        $pages = $this->detect_pages();
        
        // Detect components
        $components = $this->detect_components();
        
        // Extract package.json info
        $package_info = $this->get_package_info();
        
        // Get project metadata
        $project_meta = $this->get_project_metadata();
        
        // Detect assets
        $assets = $this->detect_assets();
        
        $this->analysis_result = array(
            'project_name' => $project_meta['name'] ?? 'Lovable Project',
            'description' => $project_meta['description'] ?? '',
            'structure' => $structure,
            'pages' => $pages,
            'components' => $components,
            'package_info' => $package_info,
            'assets' => $assets,
            'extract_path' => $this->extract_path,
        );
        
        return $this->analysis_result;
    }
    
    /**
     * Extract ZIP file
     * 
     * @return bool|WP_Error Success or error
     */
    private function extract_zip() {
        // Create temp directory
        if (!file_exists($this->extract_path)) {
            wp_mkdir_p($this->extract_path);
        }
        
        // Use WordPress unzip function
        WP_Filesystem();
        global $wp_filesystem;
        
        $result = unzip_file($this->zip_path, $this->extract_path);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Check if files are in a subdirectory (common when creating ZIPs)
        $this->adjust_extract_path();
        
        return true;
    }
    
    /**
     * Adjust extract path if files are in a subdirectory
     */
    private function adjust_extract_path() {
        $files = scandir($this->extract_path);
        $dirs = array();
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $this->extract_path . $file;
            if (is_dir($file_path)) {
                $dirs[] = $file;
            }
        }
        
        // If there's only one directory and no files in root, use that directory
        if (count($dirs) === 1 && !$this->has_project_files($this->extract_path)) {
            $this->extract_path = $this->extract_path . $dirs[0] . '/';
        }
    }
    
    /**
     * Check if directory has project files
     * 
     * @param string $dir Directory to check
     * @return bool True if has project files
     */
    private function has_project_files($dir) {
        return file_exists($dir . 'package.json') || 
               is_dir($dir . 'src') || 
               file_exists($dir . 'index.html');
    }
    
    /**
     * Analyze project structure
     * 
     * @return array Structure information
     */
    private function analyze_structure() {
        $structure = array(
            'has_src' => is_dir($this->extract_path . 'src'),
            'has_public' => is_dir($this->extract_path . 'public'),
            'has_package_json' => file_exists($this->extract_path . 'package.json'),
            'has_index_html' => file_exists($this->extract_path . 'index.html'),
            'build_tool' => $this->detect_build_tool(),
        );
        
        return $structure;
    }
    
    /**
     * Detect build tool (Vite, Webpack, etc.)
     * 
     * @return string Build tool name
     */
    private function detect_build_tool() {
        if (file_exists($this->extract_path . 'vite.config.ts') || 
            file_exists($this->extract_path . 'vite.config.js')) {
            return 'vite';
        }
        
        if (file_exists($this->extract_path . 'webpack.config.js')) {
            return 'webpack';
        }
        
        if (file_exists($this->extract_path . 'next.config.js')) {
            return 'next';
        }
        
        return 'unknown';
    }
    
    /**
     * Detect pages in src/pages directory
     * 
     * @return array List of pages found
     */
    private function detect_pages() {
        $pages = array();
        $pages_dir = $this->extract_path . 'src/pages/';
        
        if (!is_dir($pages_dir)) {
            return $pages;
        }
        
        $files = scandir($pages_dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $pages_dir . $file;
            
            if (is_file($file_path) && (strpos($file, '.tsx') !== false || strpos($file, '.jsx') !== false)) {
                $page_name = str_replace(array('.tsx', '.jsx'), '', $file);
                
                $pages[] = array(
                    'name' => $page_name,
                    'file' => $file,
                    'path' => $file_path,
                    'content' => file_get_contents($file_path),
                    'size' => filesize($file_path),
                );
            }
        }
        
        return $pages;
    }
    
    /**
     * Detect components in src/components directory
     * 
     * @return array List of components found
     */
    private function detect_components() {
        $components = array();
        $components_dir = $this->extract_path . 'src/components/';
        
        if (!is_dir($components_dir)) {
            return $components;
        }
        
        // Recursive function to scan directory
        $this->scan_components_recursive($components_dir, $components);
        
        return $components;
    }
    
    /**
     * Recursively scan components directory
     * 
     * @param string $dir Directory to scan
     * @param array &$components Components array (by reference)
     */
    private function scan_components_recursive($dir, &$components) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $dir . $file;
            
            if (is_dir($file_path)) {
                $this->scan_components_recursive($file_path . '/', $components);
            } elseif (is_file($file_path) && (strpos($file, '.tsx') !== false || strpos($file, '.jsx') !== false)) {
                $component_name = str_replace(array('.tsx', '.jsx'), '', $file);
                
                $components[] = array(
                    'name' => $component_name,
                    'file' => $file,
                    'path' => $file_path,
                    'content' => file_get_contents($file_path),
                );
            }
        }
    }
    
    /**
     * Get package.json information
     * 
     * @return array Package information
     */
    private function get_package_info() {
        $package_file = $this->extract_path . 'package.json';
        
        if (!file_exists($package_file)) {
            return array();
        }
        
        $package_json = file_get_contents($package_file);
        $package_data = json_decode($package_json, true);
        
        if (!$package_data) {
            return array();
        }
        
        return array(
            'name' => $package_data['name'] ?? '',
            'version' => $package_data['version'] ?? '',
            'dependencies' => $package_data['dependencies'] ?? array(),
            'devDependencies' => $package_data['devDependencies'] ?? array(),
        );
    }
    
    /**
     * Get project metadata from project-structure.json
     * 
     * @return array Project metadata
     */
    private function get_project_metadata() {
        $meta_file = $this->extract_path . 'project-structure.json';
        
        if (!file_exists($meta_file)) {
            return array();
        }
        
        $meta_json = file_get_contents($meta_file);
        $meta_data = json_decode($meta_json, true);
        
        if (!$meta_data || !isset($meta_data['proyecto'])) {
            return array();
        }
        
        $proyecto = $meta_data['proyecto'];
        
        return array(
            'name' => $proyecto['nombre'] ?? '',
            'description' => $proyecto['descripcion'] ?? '',
            'objective' => $proyecto['objetivo'] ?? '',
            'target_audience' => $proyecto['publico_objetivo'] ?? '',
            'technologies' => $proyecto['tecnologias'] ?? array(),
            'design' => $proyecto['diseño'] ?? array(),
        );
    }
    
    /**
     * Detect assets (images, fonts, CSS)
     * 
     * @return array Assets information
     */
    private function detect_assets() {
        $assets = array(
            'images' => array(),
            'css' => array(),
            'fonts' => array(),
        );
        
        // Detect images in public and src/assets
        $image_dirs = array(
            $this->extract_path . 'public/',
            $this->extract_path . 'src/assets/',
        );
        
        foreach ($image_dirs as $dir) {
            if (is_dir($dir)) {
                $this->scan_assets_recursive($dir, $assets);
            }
        }
        
        // Detect CSS files
        $css_files = array(
            $this->extract_path . 'src/index.css',
            $this->extract_path . 'src/App.css',
        );
        
        foreach ($css_files as $css_file) {
            if (file_exists($css_file)) {
                $assets['css'][] = array(
                    'name' => basename($css_file),
                    'path' => $css_file,
                    'content' => file_get_contents($css_file),
                    'size' => filesize($css_file),
                );
            }
        }
        
        return $assets;
    }
    
    /**
     * Recursively scan for assets
     * 
     * @param string $dir Directory to scan
     * @param array &$assets Assets array (by reference)
     */
    private function scan_assets_recursive($dir, &$assets) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $dir . $file;
            
            if (is_dir($file_path)) {
                $this->scan_assets_recursive($file_path . '/', $assets);
            } elseif (is_file($file_path)) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                // Images
                if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'))) {
                    $assets['images'][] = array(
                        'name' => $file,
                        'path' => $file_path,
                        'size' => filesize($file_path),
                        'type' => $extension,
                    );
                }
                
                // Fonts
                if (in_array($extension, array('woff', 'woff2', 'ttf', 'otf', 'eot'))) {
                    $assets['fonts'][] = array(
                        'name' => $file,
                        'path' => $file_path,
                        'size' => filesize($file_path),
                        'type' => $extension,
                    );
                }
            }
        }
    }
    
    /**
     * Clean up extracted files
     */
    public function cleanup() {
        if (is_dir($this->extract_path)) {
            $this->delete_directory($this->extract_path);
        }
    }
    
    /**
     * Recursively delete directory
     * 
     * @param string $dir Directory to delete
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $dir . $file;
            
            if (is_dir($file_path)) {
                $this->delete_directory($file_path . '/');
            } else {
                unlink($file_path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Get analysis result
     * 
     * @return array Analysis result
     */
    public function get_result() {
        return $this->analysis_result;
    }
}
