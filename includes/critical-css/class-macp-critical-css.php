<?php
/**
 * Handles Critical CSS generation and management
 */
class MACP_Critical_CSS {
    private $process;
    private $settings_manager;
    private $filesystem;
    private $critical_css_path;
    private $items = [];

    public function __construct(MACP_Settings_Manager $settings_manager) {
        $this->settings_manager = $settings_manager;
        $this->critical_css_path = WP_CONTENT_DIR . '/cache/macp/critical-css/';
        $this->filesystem = new MACP_Filesystem();
        $this->process = new MACP_Critical_CSS_Generation($this);
        
        $this->init();
    }

    private function init() {
        // Create critical CSS directory if it doesn't exist
        if (!file_exists($this->critical_css_path)) {
            wp_mkdir_p($this->critical_css_path);
        }

        // Initialize default items
        $this->items['front_page'] = [
            'type' => 'front_page',
            'url' => home_url('/'),
            'path' => 'front_page.css',
            'check' => 0
        ];

        // Add AJAX handlers
        add_action('wp_ajax_macp_generate_critical_css', [$this, 'ajax_generate_critical_css']);
        add_action('wp_ajax_macp_clear_critical_css', [$this, 'ajax_clear_critical_css']);
    }

    public function ajax_generate_critical_css() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (get_transient('macp_critical_css_generation_running')) {
            wp_send_json_error('Generation already in progress');
        }

        set_transient('macp_critical_css_generation_running', true, HOUR_IN_SECONDS);

        try {
            $this->generate_critical_css();
            wp_send_json_success('Critical CSS generation started');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clear_critical_css() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            $this->clear_critical_css();
            wp_send_json_success('Critical CSS cache cleared');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function generate_critical_css() {
        $this->set_items();
        
        foreach ($this->items as $item) {
            $this->process->push_to_queue($item);
        }

        $this->process->save()->dispatch();
    }

    private function set_items() {
        // Add blog page if using static front page
        if ('page' === get_option('show_on_front') && !empty(get_option('page_for_posts'))) {
            $this->items['home'] = [
                'type' => 'home',
                'url' => get_permalink(get_option('page_for_posts')),
                'path' => 'home.css',
                'check' => 0
            ];
        }

        // Add post types
        $post_types = get_post_types(['public' => true], 'objects');
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type->name,
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);

            if (!empty($posts)) {
                $this->items[$post_type->name] = [
                    'type' => $post_type->name,
                    'url' => get_permalink($posts[0]->ID),
                    'path' => "{$post_type->name}.css",
                    'check' => 0
                ];
            }
        }

        // Add taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy->name,
                'number' => 1,
                'hide_empty' => true
            ]);

            if (!empty($terms) && !is_wp_error($terms)) {
                $this->items[$taxonomy->name] = [
                    'type' => $taxonomy->name,
                    'url' => get_term_link($terms[0]),
                    'path' => "{$taxonomy->name}.css",
                    'check' => 0
                ];
            }
        }
    }

    public function clear_critical_css() {
        $files = glob($this->critical_css_path . '*.css');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function get_critical_css($url = '') {
        if (empty($url)) {
            $url = $this->get_current_url();
        }

        $file = $this->get_critical_css_file($url);
        if ($file && file_exists($file)) {
            return file_get_contents($file);
        }

        return '';
    }

    private function get_critical_css_file($url) {
        $key = md5($url);
        return $this->critical_css_path . $key . '.css';
    }

    private function get_current_url() {
        return home_url($_SERVER['REQUEST_URI']);
    }
}