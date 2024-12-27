<?php defined('ABSPATH') || exit; ?>

<div class="wrap macp-wrap">
    <h1>
        <img src="<?php echo plugins_url('assets/images/logo.png', MACP_PLUGIN_FILE); ?>" alt="Cache Plugin Logo" class="macp-logo">
        Advanced Cache Settings
    </h1>

    <div class="macp-dashboard-wrap">
        <!-- Status Card -->
        <div class="macp-card macp-status-card">
            <h2>Cache Status</h2>
            <div class="macp-status-indicator <?php echo $settings['enable_html_cache'] ? 'active' : 'inactive'; ?>">
                <?php echo $settings['enable_html_cache'] ? 'Cache Enabled' : 'Cache Disabled'; ?>
            </div>
            <button class="button button-primary macp-clear-cache">Clear Cache</button>
        </div>

        <!-- Settings Form -->
        <form method="post" action="" class="macp-settings-form">
            <?php wp_nonce_field('macp_save_settings_nonce'); ?>
            
            <!-- Cache Options -->
            <div class="macp-card">
                <h2>Cache Options</h2>
                
                <label class="macp-toggle">
                    <input type="checkbox" name="macp_enable_redis" value="1" <?php checked($settings['enable_redis'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Enable Redis Object Cache
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_enable_html_cache" value="1" <?php checked($settings['enable_html_cache'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Enable HTML Cache
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_enable_gzip" value="1" <?php checked($settings['enable_gzip'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Enable GZIP Compression
                </label>
            </div>

            <!-- Varnish Settings -->
            <?php 
            // Initialize Varnish Settings
            $varnish_settings = new MACP_Varnish_Settings();
            $varnish_settings->render_settings(); 
            ?>

            <!-- Optimization Options -->
            <div class="macp-card">
                <h2>Optimization Options</h2>
                
                <label class="macp-toggle">
                    <input type="checkbox" name="macp_minify_html" value="1" <?php checked($settings['minify_html'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Minify HTML
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_minify_css" value="1" <?php checked($settings['minify_css'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Minify CSS
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_minify_js" value="1" <?php checked($settings['minify_js'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Minify JavaScript
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_remove_unused_css" value="1" <?php checked($settings['remove_unused_css'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Remove Unused CSS
                </label>

                <label class="macp-toggle">
                    <input type="checkbox" name="macp_process_external_css" value="1" <?php checked($settings['process_external_css'], 1); ?>>
                    <span class="macp-toggle-slider"></span>
                    Process External CSS
                </label>

                <div class="notice notice-warning inline" style="margin-top: 15px;">
                    <p><strong>Note:</strong> Removing unused CSS is an experimental feature. Please test thoroughly on a staging site first. Some dynamic content or JavaScript-added classes might be affected.</p>
                </div>
            </div>

            <?php submit_button('Save Changes', 'primary', 'macp_save_settings'); ?>
        </form>
    </div>
</div>