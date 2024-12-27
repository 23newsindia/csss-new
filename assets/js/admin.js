jQuery(document).ready(function($) {
    // Handle mobile CPCSS generation
    $('#macp-generate-mobile-cpcss').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);

        $button.prop('disabled', true)
               .text('Generating...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_generate_mobile_cpcss',
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('Generation Started!');
                    setTimeout(function() {
                        $button.text('Regenerate Mobile Critical CSS')
                              .prop('disabled', false);
                    }, 2000);
                }
            },
            error: function() {
                $button.text('Error!')
                      .prop('disabled', false);
            }
        });
    });

    // Handle mobile CPCSS toggle
    $('input[name="macp_async_css_mobile"]').on('change', function() {
        const $checkbox = $(this);
        const value = $checkbox.prop('checked') ? 1 : 0;

        $checkbox.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_toggle_mobile_cpcss',
                value: value,
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $checkbox.prop('checked', !value);
                }
            },
            error: function() {
                $checkbox.prop('checked', !value);
            },
            complete: function() {
                $checkbox.prop('disabled', false);
            }
        });
    });

    // Auto-save functionality for textareas
    let textareaTimeout;
    $('.macp-exclusion-section textarea').on('input', function() {
        const $textarea = $(this);
        clearTimeout(textareaTimeout);
        textareaTimeout = setTimeout(function() {
            const option = $textarea.attr('name');
            const value = $textarea.val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'macp_save_textarea',
                    option: option,
                    value: value,
                    nonce: macp_admin.nonce
                }
            });
        }, 1000); // Save after 1 second of no typing
    });

    // Handle toggle switches
    $('.macp-toggle input[type="checkbox"]').on('change', function() {
        const $checkbox = $(this);
        const option = $checkbox.attr('name');
        const value = $checkbox.prop('checked') ? 1 : 0;

        // Disable the checkbox while saving
        $checkbox.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_toggle_setting',
                option: option,
                value: value,
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update status indicator if this is the cache toggle
                    if (option === 'macp_enable_html_cache') {
                        $('.macp-status-indicator')
                            .toggleClass('active inactive')
                            .text(value ? 'Cache Enabled' : 'Cache Disabled');
                    }
                } else {
                    // Revert the checkbox if save failed
                    $checkbox.prop('checked', !value);
                }
            },
            error: function() {
                // Revert the checkbox on error
                $checkbox.prop('checked', !value);
            },
            complete: function() {
                // Re-enable the checkbox
                $checkbox.prop('disabled', false);
            }
        });
    });

    // Handle clear cache button
    $('.macp-clear-cache').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);

        $button.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_clear_cache',
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('Cache Cleared!');
                    setTimeout(function() {
                        $button.text('Clear Cache').prop('disabled', false);
                    }, 2000);
                }
            },
            error: function() {
                $button.text('Error!');
                setTimeout(function() {
                    $button.text('Clear Cache').prop('disabled', false);
                }, 2000);
            }
        });
    });
});