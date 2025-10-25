<?php
/**
 * ZIP Validator Class
 * 
 * Validates Lovable project ZIP files before processing
 * 
 * @package Lovable_To_WordPress
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class L2WP_ZIP_Validator
 */
class L2WP_ZIP_Validator {
    
    /**
     * Maximum ZIP file size (in bytes)
     * Default: 50MB
     *
     * @var int
     */
    private $max_size = 52428800;
    
    /**
     * Required files/directories in Lovable project
     *
     * @var array
     */
    private $required_structure = array(
        'src/',
        'public/',
        'package.json',
    );
    
    /**
     * Allowed file extensions
     *
     * @var array
     */
    private $allowed_extensions = array(
        'js', 'jsx', 'ts', 'tsx',
        'json', 'css', 'scss', 'sass',
        'html', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp',
        'md', 'txt', 'yml', 'yaml',
    );
    
    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = array();
    
    /**
     * Validation warnings
     *
     * @var array
     */
    private $warnings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Allow filtering of max size
        $this->max_size = apply_filters('l2wp_max_zip_size', $this->max_size);
        
        // Allow filtering of required structure
        $this->required_structure = apply_filters('l2wp_required_zip_structure', $this->required_structure);
        
        // Allow filtering of allowed extensions
        $this->allowed_extensions = apply_filters('l2wp_allowed_file_extensions', $this->allowed_extensions);
    }
    
    /**
     * Validate uploaded ZIP file
     *
     * @param array $file Uploaded file data from $_FILES
     * @return bool|WP_Error True if valid, WP_Error on failure
     */
    public function validate($file) {
        $this->errors = array();
        $this->warnings = array();
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('no_file', __('No file was uploaded', 'lovable-to-wordpress'));
        }
        
        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', $this->get_upload_error_message($file['error']));
        }
        
        // Validate file size
        if (!$this->validate_size($file)) {
            return new WP_Error('file_too_large', sprintf(
                __('File size exceeds maximum allowed size of %s', 'lovable-to-wordpress'),
                size_format($this->max_size)
            ));
        }
        
        // Validate file type
        if (!$this->validate_type($file)) {
            return new WP_Error('invalid_type', __('File must be a ZIP archive', 'lovable-to-wordpress'));
        }
        
        // Validate ZIP structure
        if (!$this->validate_structure($file['tmp_name'])) {
            return new WP_Error('invalid_structure', sprintf(
                __('ZIP file does not contain a valid Lovable project structure. Errors: %s', 'lovable-to-wordpress'),
                implode(', ', $this->errors)
            ));
        }
        
        // Check for suspicious files
        $this->check_security($file['tmp_name']);
        
        // Return true if no critical errors
        if (empty($this->errors)) {
            return true;
        }
        
        return new WP_Error('validation_failed', implode(', ', $this->errors));
    }
    
    /**
     * Validate file size
     *
     * @param array $file File data
     * @return bool
     */
    private function validate_size($file) {
        if (!isset($file['size'])) {
            return false;
        }
        
        return $file['size'] <= $this->max_size;
    }
    
    /**
     * Validate file type
     *
     * @param array $file File data
     * @return bool
     */
    private function validate_type($file) {
        // Check file extension
        $filename = $file['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($extension !== 'zip') {
            return false;
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = array(
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
        );
        
        return in_array($mime, $allowed_mimes, true);
    }
    
    /**
     * Validate ZIP structure
     *
     * @param string $zip_path Path to ZIP file
     * @return bool
     */
    private function validate_structure($zip_path) {
        $zip = new ZipArchive();
        
        if ($zip->open($zip_path) !== true) {
            $this->errors[] = __('Could not open ZIP file', 'lovable-to-wordpress');
            return false;
        }
        
        $found_structure = array();
        
        // Check for required files/directories
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Check against required structure
            foreach ($this->required_structure as $required) {
                if (strpos($filename, $required) !== false) {
                    $found_structure[$required] = true;
                }
            }
            
            // Validate file extensions
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!empty($extension) && !in_array($extension, $this->allowed_extensions, true)) {
                $this->warnings[] = sprintf(__('Unexpected file extension: %s', 'lovable-to-wordpress'), $extension);
            }
        }
        
        $zip->close();
        
        // Check if all required structure was found
        $missing = array();
        foreach ($this->required_structure as $required) {
            if (!isset($found_structure[$required])) {
                $missing[] = $required;
            }
        }
        
        if (!empty($missing)) {
            $this->errors[] = sprintf(
                __('Missing required files/directories: %s', 'lovable-to-wordpress'),
                implode(', ', $missing)
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Check for security issues
     *
     * @param string $zip_path Path to ZIP file
     * @return void
     */
    private function check_security($zip_path) {
        $zip = new ZipArchive();
        
        if ($zip->open($zip_path) !== true) {
            return;
        }
        
        $dangerous_patterns = array(
            '.php',
            '.exe',
            '.sh',
            '.bat',
            '.cmd',
            '../', // Directory traversal
        );
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            foreach ($dangerous_patterns as $pattern) {
                if (strpos($filename, $pattern) !== false) {
                    $this->warnings[] = sprintf(
                        __('Potentially dangerous file detected: %s', 'lovable-to-wordpress'),
                        $filename
                    );
                }
            }
        }
        
        $zip->close();
    }
    
    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function get_upload_error_message($error_code) {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => __('File exceeds upload_max_filesize directive in php.ini', 'lovable-to-wordpress'),
            UPLOAD_ERR_FORM_SIZE => __('File exceeds MAX_FILE_SIZE directive in HTML form', 'lovable-to-wordpress'),
            UPLOAD_ERR_PARTIAL => __('File was only partially uploaded', 'lovable-to-wordpress'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded', 'lovable-to-wordpress'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder', 'lovable-to-wordpress'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'lovable-to-wordpress'),
            UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload', 'lovable-to-wordpress'),
        );
        
        return $messages[$error_code] ?? __('Unknown upload error', 'lovable-to-wordpress');
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Get validation warnings
     *
     * @return array
     */
    public function get_warnings() {
        return $this->warnings;
    }
}
