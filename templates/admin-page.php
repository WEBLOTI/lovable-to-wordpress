<?php
/**
 * Admin Page v2 - ZIP Import with Plugin Selector
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle clear/reset
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    delete_transient('lovable_analysis_' . get_current_user_id());
    delete_transient('lovable_detections_' . get_current_user_id());
    wp_redirect(admin_url('admin.php?page=lovable-to-wordpress'));
    exit;
}

// Handle ZIP upload and analysis
$analysis_result = null;
$detections = null;

if (isset($_POST['lovable_analyze_zip']) && isset($_FILES['lovable_zip'])) {
    check_admin_referer('lovable_zip_upload');
    
    $zip_file = $_FILES['lovable_zip'];
    
    if ($zip_file['error'] === UPLOAD_ERR_OK) {
        // Analyze ZIP
        $analyzer = new Lovable_ZIP_Analyzer();
        $analysis_result = $analyzer->analyze($zip_file['tmp_name']);
        
        if (!is_wp_error($analysis_result)) {
            // Detect components
            $detector = new Lovable_Component_Detector();
            $detections = $detector->detect($analysis_result);
            
            // Store in session for next step
            set_transient('lovable_analysis_' . get_current_user_id(), $analysis_result, HOUR_IN_SECONDS);
            set_transient('lovable_detections_' . get_current_user_id(), $detections, HOUR_IN_SECONDS);
        }
    }
}

// Get stored analysis if available
if (!$analysis_result) {
    $analysis_result = get_transient('lovable_analysis_' . get_current_user_id());
    $detections = get_transient('lovable_detections_' . get_current_user_id());
}
?>

<div class="wrap lovable-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?> <span class="lovable-version">v2.0</span></h1>
    
    <?php if (!$analysis_result): ?>
        <!-- Step 1: Upload ZIP -->
        <div class="lovable-upload-section">
            <div class="lovable-hero-card">
                <h2>🚀 <?php _e('Import Lovable Project', 'lovable-to-wordpress'); ?></h2>
                <p class="description">
                    <?php _e('Upload your complete Lovable project ZIP file. The system will automatically detect functionalities and recommend plugins.', 'lovable-to-wordpress'); ?>
                </p>
                
                <form method="post" enctype="multipart/form-data" class="lovable-upload-form">
                    <?php wp_nonce_field('lovable_zip_upload'); ?>
                    
                    <div class="lovable-file-input-wrapper">
                        <input type="file" name="lovable_zip" id="lovable_zip" accept=".zip" required>
                        <label for="lovable_zip" class="lovable-file-label">
                            <span class="dashicons dashicons-upload"></span>
                            <span class="lovable-file-text"><?php _e('Choose ZIP file or drag here', 'lovable-to-wordpress'); ?></span>
                        </label>
                    </div>
                    
                    <button type="submit" name="lovable_analyze_zip" class="button button-primary button-hero">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Analyze Project', 'lovable-to-wordpress'); ?>
                    </button>
                </form>
            </div>
            
            <div class="lovable-info-grid">
                <div class="lovable-info-card">
                    <h3>📦 <?php _e('What to Upload', 'lovable-to-wordpress'); ?></h3>
                    <ul>
                        <li><?php _e('Complete Lovable project from GitHub', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Include src/, public/, package.json', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Export from: git clone + zip', 'lovable-to-wordpress'); ?></li>
                    </ul>
                </div>
                
                <div class="lovable-info-card">
                    <h3>🔍 <?php _e('What Gets Detected', 'lovable-to-wordpress'); ?></h3>
                    <ul>
                        <li><?php _e('Forms → Plugin recommendations', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Modals → Popup solutions', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Filters → Search plugins', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('CPTs → Field management', 'lovable-to-wordpress'); ?></li>
                    </ul>
                </div>
                
                <div class="lovable-info-card">
                    <h3>✨ <?php _e('What Gets Preserved', 'lovable-to-wordpress'); ?></h3>
                    <ul>
                        <li><?php _e('85-90% of styles automatically', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Tailwind colors & fonts', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('Flexbox/Grid structure', 'lovable-to-wordpress'); ?></li>
                        <li><?php _e('100% editable in Elementor', 'lovable-to-wordpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Step 2: Analysis Results & Plugin Selection -->
        <div class="lovable-analysis-section">
            
            <!-- Project Summary -->
            <div class="lovable-card lovable-summary-card">
                <h2>📊 <?php _e('Project Analysis', 'lovable-to-wordpress'); ?></h2>
                
                <div class="lovable-stats-grid">
                    <div class="lovable-stat">
                        <span class="lovable-stat-number"><?php echo count($analysis_result['pages'] ?? []); ?></span>
                        <span class="lovable-stat-label"><?php _e('Pages Found', 'lovable-to-wordpress'); ?></span>
                    </div>
                    <div class="lovable-stat">
                        <span class="lovable-stat-number"><?php echo count($analysis_result['components'] ?? []); ?></span>
                        <span class="lovable-stat-label"><?php _e('Components', 'lovable-to-wordpress'); ?></span>
                    </div>
                    <div class="lovable-stat">
                        <span class="lovable-stat-number"><?php echo count($detections ?? []); ?></span>
                        <span class="lovable-stat-label"><?php _e('Functionalities', 'lovable-to-wordpress'); ?></span>
                    </div>
                    <div class="lovable-stat">
                        <span class="lovable-stat-number"><?php echo count($analysis_result['assets']['images'] ?? []); ?></span>
                        <span class="lovable-stat-label"><?php _e('Images', 'lovable-to-wordpress'); ?></span>
                    </div>
                </div>
                
                <h3><?php _e('Project:', 'lovable-to-wordpress'); ?> <strong><?php echo esc_html($analysis_result['project_name']); ?></strong></h3>
                <?php if (!empty($analysis_result['description'])): ?>
                    <p><?php echo esc_html($analysis_result['description']); ?></p>
                <?php endif; ?>
                
                <details class="lovable-details">
                    <summary><?php _e('View Detected Pages', 'lovable-to-wordpress'); ?></summary>
                    <ul>
                        <?php foreach ($analysis_result['pages'] ?? [] as $page): ?>
                            <li><code><?php echo esc_html($page['name']); ?></code> (<?php echo size_format($page['size']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </div>
            
            <!-- Plugin Recommendations -->
            <?php if (!empty($detections)): ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="lovable-import-form">
                    <?php wp_nonce_field('lovable_import_with_plugins'); ?>
                    <input type="hidden" name="action" value="lovable_import_project">
                    
                    <h2 class="lovable-section-title">🔌 <?php _e('Plugin Recommendations', 'lovable-to-wordpress'); ?></h2>
                    <p class="description"><?php _e('Select which plugins to use for each detected functionality. You can choose between multiple options or skip.', 'lovable-to-wordpress'); ?></p>
                    
                    <?php 
                    $recommender = new Lovable_Plugin_Recommender();
                    
                    foreach ($detections as $functionality_key => $detection): 
                        $solutions = $recommender->get_solutions_for($functionality_key);
                        if (empty($solutions)) continue;
                    ?>
                        
                        <div class="lovable-card lovable-functionality-card">
                            <h3>
                                <?php echo esc_html($detection['name']); ?>
                                <span class="lovable-badge"><?php echo $detection['count']; ?> <?php _e('found', 'lovable-to-wordpress'); ?></span>
                            </h3>
                            
                            <div class="lovable-plugin-options">
                                <?php foreach ($solutions as $solution): ?>
                                    <label class="lovable-plugin-option <?php echo $solution['installed'] ? 'installed' : ''; ?>">
                                        <input type="radio" 
                                               name="plugin_choice[<?php echo esc_attr($functionality_key); ?>]" 
                                               value="<?php echo esc_attr($solution['slug']); ?>"
                                               <?php checked($solution['compatibility'], max(array_column($solutions, 'compatibility'))); ?>>
                                        
                                        <div class="lovable-plugin-card">
                                            <div class="lovable-plugin-header">
                                                <strong><?php echo esc_html($solution['name']); ?></strong>
                                                <span class="lovable-compatibility"><?php echo $solution['compatibility']; ?>%</span>
                                            </div>
                                            
                                            <div class="lovable-plugin-meta">
                                                <span class="lovable-plugin-provider"><?php echo esc_html($solution['provider']); ?></span>
                                                <?php if ($solution['free']): ?>
                                                    <span class="lovable-badge-free">FREE</span>
                                                <?php else: ?>
                                                    <span class="lovable-badge-premium">PREMIUM</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <ul class="lovable-plugin-features">
                                                <?php foreach (array_slice($solution['features'], 0, 3) as $feature): ?>
                                                    <li><?php echo esc_html($feature); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            
                                            <div class="lovable-plugin-status">
                                                <?php if ($solution['installed']): ?>
                                                    <?php if ($solution['active']): ?>
                                                        <span class="lovable-status-active">✓ <?php _e('Active', 'lovable-to-wordpress'); ?></span>
                                                    <?php else: ?>
                                                        <span class="lovable-status-inactive">○ <?php _e('Installed (Inactive)', 'lovable-to-wordpress'); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="lovable-status-not-installed"><?php _e('Will be installed', 'lovable-to-wordpress'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                                
                                <label class="lovable-plugin-option">
                                    <input type="radio" 
                                           name="plugin_choice[<?php echo esc_attr($functionality_key); ?>]" 
                                           value="skip">
                                    <div class="lovable-plugin-card lovable-skip-option">
                                        <strong><?php _e('Skip / Manual Setup', 'lovable-to-wordpress'); ?></strong>
                                        <p><?php _e('Configure later manually', 'lovable-to-wordpress'); ?></p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                    <div class="lovable-card lovable-import-options">
                        <h3><?php _e('Import Options', 'lovable-to-wordpress'); ?></h3>
                        
                        <label>
                            <input type="checkbox" name="install_plugins" value="1" checked>
                            <strong><?php _e('Automatically install selected plugins', 'lovable-to-wordpress'); ?></strong>
                            <p class="description"><?php _e('Download and activate plugins from WordPress.org', 'lovable-to-wordpress'); ?></p>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="import_assets" value="1" checked>
                            <strong><?php _e('Import images and assets', 'lovable-to-wordpress'); ?></strong>
                            <p class="description"><?php _e('Upload images to WordPress media library', 'lovable-to-wordpress'); ?></p>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="apply_css" value="1" checked>
                            <strong><?php _e('Apply Lovable CSS styles', 'lovable-to-wordpress'); ?></strong>
                            <p class="description"><?php _e('Inject Tailwind colors and custom CSS', 'lovable-to-wordpress'); ?></p>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="remember_preferences" value="1">
                            <strong><?php _e('Remember my plugin choices', 'lovable-to-wordpress'); ?></strong>
                            <p class="description"><?php _e('Use these plugins automatically for future imports', 'lovable-to-wordpress'); ?></p>
                        </label>
                    </div>
                    
                    <div class="lovable-actions">
                        <button type="submit" class="button button-primary button-hero">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Import to WordPress', 'lovable-to-wordpress'); ?>
                        </button>
                        
                        <a href="?page=lovable-to-wordpress" class="button button-secondary">
                            <?php _e('Start Over', 'lovable-to-wordpress'); ?>
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php _e('No functionalities detected. You can still import the project without plugin recommendations.', 'lovable-to-wordpress'); ?></p>
                </div>
            <?php endif; ?>
            
        </div>
    <?php endif; ?>
    
</div>

<style>
.lovable-admin-wrap {
    margin: 20px 20px 0 0;
}

.lovable-version {
    font-size: 14px;
    color: #666;
    font-weight: normal;
}

.lovable-hero-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.lovable-hero-card h2 {
    color: white;
    margin: 0 0 10px 0;
    font-size: 32px;
}

.lovable-hero-card .description {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 30px;
}

.lovable-upload-form {
    max-width: 500px;
    margin: 0 auto;
}

.lovable-file-input-wrapper {
    position: relative;
    margin-bottom: 20px;
}

.lovable-file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.lovable-file-label {
    display: block;
    padding: 40px 20px;
    background: rgba(255,255,255,0.2);
    border: 2px dashed rgba(255,255,255,0.5);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.lovable-file-label:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.8);
}

.lovable-file-label .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    display: block;
    margin: 0 auto 10px;
}

.lovable-file-text {
    display: block;
    font-size: 16px;
}

.lovable-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.lovable-info-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.lovable-info-card h3 {
    margin-top: 0;
    color: #667eea;
}

.lovable-info-card ul {
    margin: 0;
    padding-left: 20px;
}

.lovable-info-card li {
    margin-bottom: 8px;
}

.lovable-card {
    background: white;
    padding: 25px;
    margin: 20px 0;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.lovable-summary-card {
    border-left: 4px solid #667eea;
}

.lovable-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.lovable-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.lovable-stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}

.lovable-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.lovable-details summary {
    cursor: pointer;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-top: 15px;
}

.lovable-details ul {
    margin-top: 10px;
    padding-left: 30px;
}

.lovable-section-title {
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
    margin-top: 30px;
}

.lovable-functionality-card {
    border-left: 4px solid #4caf50;
}

.lovable-badge {
    background: #4caf50;
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: normal;
    margin-left: 10px;
}

.lovable-plugin-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.lovable-plugin-option {
    cursor: pointer;
    display: block;
}

.lovable-plugin-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.lovable-plugin-card {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.3s;
    height: 100%;
}

.lovable-plugin-option input[type="radio"]:checked + .lovable-plugin-card {
    border-color: #667eea;
    background: #f8f9ff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.lovable-plugin-card:hover {
    border-color: #667eea;
}

.lovable-plugin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.lovable-compatibility {
    background: #4caf50;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
}

.lovable-plugin-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
    font-size: 12px;
    color: #666;
}

.lovable-badge-free {
    background: #4caf50;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
}

.lovable-badge-premium {
    background: #ff9800;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
}

.lovable-plugin-features {
    list-style: none;
    margin: 10px 0;
    padding: 0;
    font-size: 13px;
}

.lovable-plugin-features li:before {
    content: "✓ ";
    color: #4caf50;
    font-weight: bold;
}

.lovable-plugin-status {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-size: 12px;
}

.lovable-status-active {
    color: #4caf50;
    font-weight: bold;
}

.lovable-status-inactive {
    color: #ff9800;
}

.lovable-status-not-installed {
    color: #666;
}

.lovable-plugin-option.installed .lovable-plugin-card {
    background: #f0f9f0;
}

.lovable-skip-option {
    background: #f8f9fa !important;
    text-align: center;
}

.lovable-import-options label {
    display: block;
    padding: 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.lovable-import-options input[type="checkbox"] {
    margin-right: 10px;
}

.lovable-actions {
    text-align: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.lovable-actions .button {
    margin: 0 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // File input enhancement
    $('#lovable_zip').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('.lovable-file-text').text(fileName);
        }
    });
    
    // Form validation
    $('#lovable-import-form').on('submit', function(e) {
        var checkedCount = $('input[type="radio"]:checked').length;
        var totalFunctionalities = $('.lovable-functionality-card').length;
        
        if (checkedCount < totalFunctionalities) {
            if (!confirm('<?php _e('Some functionalities have no plugin selected. Continue anyway?', 'lovable-to-wordpress'); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
