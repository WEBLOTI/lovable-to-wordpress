<?php
/**
 * Component Detector Class
 * 
 * Detects functionalities in Lovable project components
 * (Forms, Modals, Filters, CPTs, etc.)
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Component_Detector {
    
    private $mappings;
    private $detections;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_mappings();
        $this->detections = array();
    }
    
    /**
     * Load plugin mappings
     */
    private function load_mappings() {
        $mappings_file = LOVABLE_EXPORTER_DIR . 'plugin-mappings.json';
        
        if (!file_exists($mappings_file)) {
            $this->mappings = array();
            return;
        }
        
        $json = file_get_contents($mappings_file);
        $this->mappings = json_decode($json, true);
    }
    
    /**
     * Detect functionalities in analyzed project
     * 
     * @param array $analysis_result Result from ZIP Analyzer
     * @return array Detected functionalities
     */
    public function detect($analysis_result) {
        $this->detections = array();
        
        // Get all pages and components content
        $all_files = array_merge(
            $analysis_result['pages'] ?? array(),
            $analysis_result['components'] ?? array()
        );
        
        // Also check package.json dependencies
        $package_info = $analysis_result['package_info'] ?? array();
        $dependencies = array_merge(
            $package_info['dependencies'] ?? array(),
            $package_info['devDependencies'] ?? array()
        );
        
        // Detect each functionality
        foreach ($this->mappings['functionality_mappings'] as $key => $mapping) {
            $detected = $this->detect_functionality($key, $mapping, $all_files, $dependencies);
            
            if ($detected) {
                $this->detections[$key] = $detected;
            }
        }
        
        return $this->detections;
    }
    
    /**
     * Detect specific functionality
     * 
     * @param string $key Functionality key
     * @param array $mapping Functionality mapping
     * @param array $files Files to scan
     * @param array $dependencies Package dependencies
     * @return array|false Detection result or false
     */
    private function detect_functionality($key, $mapping, $files, $dependencies) {
        $patterns = $mapping['detector_patterns'];
        $occurrences = array();
        $count = 0;
        
        // Check in files
        foreach ($files as $file) {
            $content = $file['content'] ?? '';
            $file_name = $file['name'] ?? $file['file'] ?? '';
            
            foreach ($patterns as $pattern) {
                // Check if pattern exists in content
                if (stripos($content, $pattern) !== false) {
                    $count++;
                    
                    $occurrences[] = array(
                        'file' => $file_name,
                        'pattern' => $pattern,
                        'context' => $this->get_context_around_match($content, $pattern),
                    );
                }
            }
        }
        
        // Check in dependencies
        foreach ($dependencies as $dep_name => $version) {
            foreach ($patterns as $pattern) {
                if (stripos($dep_name, $pattern) !== false) {
                    $count++;
                    $occurrences[] = array(
                        'dependency' => $dep_name,
                        'pattern' => $pattern,
                    );
                }
            }
        }
        
        if ($count > 0) {
            return array(
                'name' => $mapping['name'],
                'count' => $count,
                'occurrences' => $occurrences,
                'recommended_solutions' => $mapping['recommended_solutions'],
            );
        }
        
        return false;
    }
    
    /**
     * Get context around a pattern match
     * 
     * @param string $content File content
     * @param string $pattern Pattern that was matched
     * @return string Context snippet
     */
    private function get_context_around_match($content, $pattern) {
        $pos = stripos($content, $pattern);
        
        if ($pos === false) {
            return '';
        }
        
        // Get 100 characters before and after
        $start = max(0, $pos - 100);
        $length = min(200, strlen($content) - $start);
        
        $context = substr($content, $start, $length);
        
        // Clean up
        $context = trim($context);
        
        // Truncate if too long
        if (strlen($context) > 200) {
            $context = substr($context, 0, 197) . '...';
        }
        
        return $context;
    }
    
    /**
     * Get detection summary
     * 
     * @return array Summary of detections
     */
    public function get_summary() {
        $summary = array(
            'total_functionalities' => count($this->detections),
            'functionalities' => array(),
        );
        
        foreach ($this->detections as $key => $detection) {
            $summary['functionalities'][$key] = array(
                'name' => $detection['name'],
                'count' => $detection['count'],
                'solutions_available' => count($detection['recommended_solutions']),
            );
        }
        
        return $summary;
    }
    
    /**
     * Get detections
     * 
     * @return array All detections
     */
    public function get_detections() {
        return $this->detections;
    }
    
    /**
     * Get detection for specific functionality
     * 
     * @param string $key Functionality key
     * @return array|null Detection or null
     */
    public function get_detection($key) {
        return $this->detections[$key] ?? null;
    }
}
