<?php defined('ABSPATH') || exit; ?>

<div class="macp-card">
    <h2>JavaScript Optimization</h2>
    
    <div class="macp-optimization-section">
        <!-- Defer JavaScript -->
        <div class="macp-option-group">
            <label class="macp-toggle">
                <input type="checkbox" name="macp_enable_js_defer" value="1" <?php checked(get_option('macp_enable_js_defer', 0), 1); ?>>
                <span class="macp-toggle-slider"></span>
                Load JavaScript deferred
            </label>
            
            <div class="macp-exclusion-section" id="deferred-scripts-section">
                <h3>Deferred Scripts</h3>
                <p class="description">Enter script handles to be deferred (one per line). These scripts will load with the defer attribute.</p>
                <textarea name="macp_deferred_scripts" rows="5" class="large-text code"><?php 
                    echo esc_textarea(implode("\n", get_option('macp_deferred_scripts', ['jquery-core', 'jquery-migrate']))); 
                ?></textarea>
            </div>
        </div>

        <!-- Delay JavaScript -->
        <div class="macp-option-group" style="margin-top: 20px;">
            <label class="macp-toggle">
                <input type="checkbox" name="macp_enable_js_delay" value="1" <?php checked(get_option('macp_enable_js_delay', 0), 1); ?>>
                <span class="macp-toggle-slider"></span>
                Delay JavaScript execution
            </label>
            
            <div class="macp-exclusion-section" id="excluded-scripts-section">
                <h3>Excluded JavaScript Files</h3>
                <p class="description">Enter one URL or keyword per line. Scripts containing these strings will not be delayed or deferred.</p>
                <textarea name="macp_excluded_scripts" rows="5" class="large-text code"><?php 
                    echo esc_textarea(implode("\n", get_option('macp_excluded_scripts', []))); 
                ?></textarea>
            </div>
        </div>
    </div>
</div>