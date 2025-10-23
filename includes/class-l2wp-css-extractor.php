<?php
/**
 * CSS Extractor Class
 * 
 * Extracts and processes CSS from Lovable projects
 * Handles Tailwind CSS, custom CSS, and color variables
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_CSS_Extractor {
    
    private $css_content;
    private $extracted_data;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->extracted_data = array(
            'colors' => array(),
            'fonts' => array(),
            'custom_css' => '',
            'tailwind_config' => array(),
        );
    }
    
    /**
     * Extract CSS from analyzed project
     * 
     * @param array $analysis_result Result from ZIP Analyzer
     * @return array Extracted CSS data
     */
    public function extract($analysis_result) {
        $assets = $analysis_result['assets'] ?? array();
        $css_files = $assets['css'] ?? array();
        
        // Combine all CSS
        $combined_css = '';
        foreach ($css_files as $css_file) {
            $combined_css .= $css_file['content'] . "\n\n";
        }
        
        $this->css_content = $combined_css;
        
        // Extract colors
        $this->extract_colors();
        
        // Extract fonts
        $this->extract_fonts();
        
        // Extract Tailwind config from project metadata
        if (isset($analysis_result['design'])) {
            $this->extract_tailwind_config($analysis_result);
        }
        
        // Store custom CSS (cleaned)
        $this->extracted_data['custom_css'] = $this->clean_css($combined_css);
        
        return $this->extracted_data;
    }
    
    /**
     * Extract color variables from CSS
     */
    private function extract_colors() {
        // Extract CSS custom properties (--variable: value)
        preg_match_all('/--([\w-]+):\s*([^;]+);/', $this->css_content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $var_name = $match[1];
            $value = trim($match[2]);
            
            // Only store color-related variables
            if (strpos($var_name, 'color') !== false || 
                strpos($var_name, 'background') !== false ||
                strpos($var_name, 'foreground') !== false ||
                strpos($var_name, 'primary') !== false ||
                strpos($var_name, 'secondary') !== false ||
                strpos($var_name, 'accent') !== false) {
                
                $this->extracted_data['colors'][$var_name] = $value;
            }
        }
        
        // Also extract from :root or @layer base
        preg_match('/:root\s*{([^}]+)}/', $this->css_content, $root_match);
        if (isset($root_match[1])) {
            preg_match_all('/--([\w-]+):\s*([^;]+);/', $root_match[1], $root_vars, PREG_SET_ORDER);
            
            foreach ($root_vars as $var) {
                $this->extracted_data['colors'][$var[1]] = trim($var[2]);
            }
        }
    }
    
    /**
     * Extract font information from CSS
     */
    private function extract_fonts() {
        // Extract @import for fonts
        preg_match_all('/@import\s+url\([\'"]?([^\'"()]+)[\'"]?\);/', $this->css_content, $imports);
        
        if (isset($imports[1])) {
            foreach ($imports[1] as $import_url) {
                if (strpos($import_url, 'fonts.googleapis.com') !== false) {
                    // Extract font family name
                    preg_match('/family=([^:&]+)/', $import_url, $font_match);
                    
                    if (isset($font_match[1])) {
                        $font_name = str_replace('+', ' ', urldecode($font_match[1]));
                        
                        $this->extracted_data['fonts'][] = array(
                            'name' => $font_name,
                            'url' => $import_url,
                            'source' => 'google',
                        );
                    }
                }
            }
        }
        
        // Extract font-family declarations
        preg_match_all('/font-family:\s*([^;]+);/', $this->css_content, $font_families);
        
        if (isset($font_families[1])) {
            foreach ($font_families[1] as $family) {
                $family = trim($family, '\'"');
                $family = explode(',', $family)[0]; // Get first font only
                $family = trim($family, '\'"');
                
                // Check if not already in fonts array
                $exists = false;
                foreach ($this->extracted_data['fonts'] as $font) {
                    if ($font['name'] === $family) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists && $family !== 'sans-serif' && $family !== 'serif' && $family !== 'monospace') {
                    $this->extracted_data['fonts'][] = array(
                        'name' => $family,
                        'url' => '',
                        'source' => 'custom',
                    );
                }
            }
        }
    }
    
    /**
     * Extract Tailwind config from project metadata
     * 
     * @param array $analysis_result Analysis result
     */
    private function extract_tailwind_config($analysis_result) {
        $design = $analysis_result['design'] ?? array();
        
        // Extract colors from design config
        if (isset($design['colores'])) {
            $this->extracted_data['tailwind_config']['colors'] = $design['colores'];
        }
        
        // Extract typography
        if (isset($design['tipografia'])) {
            $this->extracted_data['tailwind_config']['typography'] = $design['tipografia'];
        }
    }
    
    /**
     * Clean CSS for WordPress/Elementor compatibility
     * 
     * @param string $css Raw CSS
     * @return string Cleaned CSS
     */
    private function clean_css($css) {
        // Remove @tailwind directives
        $css = preg_replace('/@tailwind\s+[^;]+;/', '', $css);
        
        // Remove @apply directives (Tailwind specific)
        $css = preg_replace('/@apply\s+[^;]+;/', '', $css);
        
        // Remove @layer directives but keep content
        $css = preg_replace('/@layer\s+[\w\s,]+\s*{/', '', $css);
        
        // Remove empty rules
        $css = preg_replace('/[^{}]+{\s*}/', '', $css);
        
        // Minify (optional)
        // $css = preg_replace('/\s+/', ' ', $css);
        
        return trim($css);
    }
    
    /**
     * Generate Elementor-compatible CSS
     * 
     * @return string CSS ready for Elementor
     */
    public function generate_elementor_css() {
        $css = '';
        
        // Add color variables as Elementor custom CSS
        if (!empty($this->extracted_data['colors'])) {
            $css .= ":root {\n";
            foreach ($this->extracted_data['colors'] as $var_name => $value) {
                $css .= "  --{$var_name}: {$value};\n";
            }
            $css .= "}\n\n";
        }
        
        // Add custom CSS
        $css .= $this->extracted_data['custom_css'];
        
        return $css;
    }
    
    /**
     * Get color palette for Elementor
     * 
     * @return array Color palette
     */
    public function get_color_palette() {
        $palette = array();
        
        // Convert extracted colors to Elementor format
        foreach ($this->extracted_data['colors'] as $var_name => $value) {
            // Convert HSL to RGB if needed
            $rgb_value = $this->hsl_to_rgb($value);
            
            $palette[] = array(
                'id' => sanitize_key($var_name),
                'label' => ucwords(str_replace('-', ' ', $var_name)),
                'color' => $rgb_value,
            );
        }
        
        return $palette;
    }
    
    /**
     * Convert HSL color to RGB hex
     * 
     * @param string $hsl HSL color value
     * @return string RGB hex color
     */
    private function hsl_to_rgb($hsl) {
        // If already RGB/hex, return as is
        if (strpos($hsl, '#') === 0 || strpos($hsl, 'rgb') === 0) {
            return $hsl;
        }
        
        // Parse HSL (e.g., "142 50% 45%" or "hsl(142, 50%, 45%)")
        preg_match('/(\d+)\s*,?\s*(\d+)%\s*,?\s*(\d+)%/', $hsl, $matches);
        
        if (count($matches) < 4) {
            return '#000000'; // Default fallback
        }
        
        $h = (int)$matches[1] / 360;
        $s = (int)$matches[2] / 100;
        $l = (int)$matches[3] / 100;
        
        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            
            $r = $this->hue_to_rgb($p, $q, $h + 1/3);
            $g = $this->hue_to_rgb($p, $q, $h);
            $b = $this->hue_to_rgb($p, $q, $h - 1/3);
        }
        
        return sprintf('#%02x%02x%02x', round($r * 255), round($g * 255), round($b * 255));
    }
    
    /**
     * Helper for HSL to RGB conversion
     */
    private function hue_to_rgb($p, $q, $t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
    
    /**
     * Get extracted data
     * 
     * @return array Extracted CSS data
     */
    public function get_extracted_data() {
        return $this->extracted_data;
    }
}
