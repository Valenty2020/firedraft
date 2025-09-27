<?php
/**
 * Data collector class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Firedraft_Reports_Data_Collector {

    /**
     * Collect comprehensive site data
     */
    public function collect_site_data() {
        global $wpdb;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $theme = wp_get_theme();
        $plugins = [];
        $seo_data = [];
        $performance_data = [];
        $security_data = [];
        $caching_data = [];
        $ecommerce_data = [];
        $forms_data = [];
        $accessibility_data = [];

        // Collect plugin data and categorize them
        foreach (get_plugins() as $path => $data) {
            $plugin_info = [
                'name'    => $data['Name'],
                'version' => $data['Version'],
                'status'  => is_plugin_active($path) ? 'active' : 'inactive',
                'slug'    => dirname($path)
            ];
            
            $plugins[] = $plugin_info;
            
            // Categorize plugins and extract specific data
            if (is_plugin_active($path)) {
                $this->categorize_and_extract_plugin_data($plugin_info, $seo_data, $performance_data, $security_data, $caching_data, $ecommerce_data, $forms_data, $accessibility_data);
            }
        }

        // Enhanced data collection
        $seo_data = array_merge($seo_data, $this->collect_seo_data());
        $performance_data = array_merge($performance_data, $this->collect_performance_data());
        $security_data = array_merge($security_data, $this->collect_security_data());
        $caching_data = array_merge($caching_data, $this->collect_caching_data());
        $ecommerce_data = array_merge($ecommerce_data, $this->collect_ecommerce_data());
        $forms_data = array_merge($forms_data, $this->collect_forms_data());
        $accessibility_data = array_merge($accessibility_data, $this->collect_accessibility_data());

        return [
            'site' => [
                'name'        => get_bloginfo('name'),
                'tagline'     => get_bloginfo('description'),
                'url'         => home_url(),
                'admin_email' => get_bloginfo('admin_email'),
            ],
            'wordpress' => [
                'version'  => get_bloginfo('version'),
                'language' => get_bloginfo('language'),
                'multisite' => is_multisite(),
            ],
            'theme' => [
                'name'          => $theme->get('Name'),
                'version'       => $theme->get('Version'),
                'author'        => $theme->get('Author'),
                'is_child_theme'=> is_child_theme(),
                'parent_theme'  => is_child_theme() ? $theme->get('Template') : null,
            ],
            'plugins' => $plugins,
            'content' => [
                'pages' => (int) wp_count_posts('page')->publish,
                'posts' => (int) wp_count_posts('post')->publish,
                'media_items' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment'"),
                'comments' => (int) wp_count_comments()->approved,
            ],
            'users' => count_users(),
            'server' => [
                'php_version'    => phpversion(),
                'mysql_version'  => $wpdb->db_version(),
                'web_server'     => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit'   => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ],
            'seo' => $seo_data,
            'performance' => $performance_data,
            'security' => $security_data,
            'caching' => $caching_data,
            'ecommerce' => $ecommerce_data,
            'forms' => $forms_data,
            'accessibility' => $accessibility_data,
        ];
    }

    /**
     * Categorize plugins and extract specific data
     */
    private function categorize_and_extract_plugin_data($plugin, &$seo_data, &$performance_data, &$security_data, &$caching_data, &$ecommerce_data, &$forms_data, &$accessibility_data) {
        $plugin_slug = strtolower($plugin['slug']);
        $plugin_name = strtolower($plugin['name']);

        // SEO Plugins
        $seo_plugins = ['yoast', 'all-in-one-seo', 'rank-math', 'seopress', 'seo-framework', 'smartcrawl', 'squirrly', 'wp-meta-seo', 'slim-seo'];
        foreach ($seo_plugins as $seo_plugin) {
            if (strpos($plugin_slug, $seo_plugin) !== false || strpos($plugin_name, $seo_plugin) !== false) {
                $seo_data['plugins'][] = $plugin;
                break;
            }
        }

        // Performance Plugins  
        $performance_plugins = ['wp-rocket', 'w3-total-cache', 'wp-super-cache', 'litespeed', 'autoptimize', 'perfmatters', 'asset-cleanup', 'wp-fastest-cache', 'hummingbird'];
        foreach ($performance_plugins as $perf_plugin) {
            if (strpos($plugin_slug, $perf_plugin) !== false || strpos($plugin_name, $perf_plugin) !== false) {
                $performance_data['plugins'][] = $plugin;
                break;
            }
        }

        // Security Plugins
        $security_plugins = ['wordfence', 'ithemes-security', 'sucuri', 'all-in-one-wp-security', 'shield', 'cerber', 'malcare', 'jetpack', 'bulletproof'];
        foreach ($security_plugins as $sec_plugin) {
            if (strpos($plugin_slug, $sec_plugin) !== false || strpos($plugin_name, $sec_plugin) !== false) {
                $security_data['plugins'][] = $plugin;
                break;
            }
        }

        // Caching Plugins
        $caching_plugins = ['cache', 'rocket', 'litespeed', 'w3-total', 'super-cache', 'breeze', 'hummingbird', 'swift-performance'];
        foreach ($caching_plugins as $cache_plugin) {
            if (strpos($plugin_slug, $cache_plugin) !== false || strpos($plugin_name, $cache_plugin) !== false) {
                $caching_data['plugins'][] = $plugin;
                break;
            }
        }

        // E-commerce Plugins
        $ecommerce_plugins = ['woocommerce', 'easy-digital-downloads', 'wp-easycart', 'ecwid', 'bigcommerce', 'cartflows', 'memberpress', 'restrict-content'];
        foreach ($ecommerce_plugins as $ecom_plugin) {
            if (strpos($plugin_slug, $ecom_plugin) !== false || strpos($plugin_name, $ecom_plugin) !== false) {
                $ecommerce_data['plugins'][] = $plugin;
                break;
            }
        }

        // Forms Plugins
        $forms_plugins = ['contact-form-7', 'wpforms', 'gravity-forms', 'ninja-forms', 'forminator', 'caldera-forms', 'formidable', 'fluent-forms', 'contact-form', 'elementor-pro', 'mailchimp', 'convertkit', 'mailpoet', 'constant-contact'];
        foreach ($forms_plugins as $forms_plugin) {
            if (strpos($plugin_slug, $forms_plugin) !== false || strpos($plugin_name, $forms_plugin) !== false) {
                $forms_data['plugins'][] = $plugin;
                break;
            }
        }

        // Accessibility Plugins
        $accessibility_plugins = ['wp-accessibility', 'userway', 'accessibe', 'one-click-accessibility', 'wp-ada-compliance', 'accessibility-checker', 'equalize-digital', 'wp-accessibility-helper'];
        foreach ($accessibility_plugins as $access_plugin) {
            if (strpos($plugin_slug, $access_plugin) !== false || strpos($plugin_name, $access_plugin) !== false) {
                $accessibility_data['plugins'][] = $plugin;
                break;
            }
        }
    }

    /**
     * Collect SEO specific data
     */
    private function collect_seo_data() {
        $seo_data = [
            'plugins' => [],
            'sitemap_present' => false,
            'robots_txt_present' => false,
            'ssl_enabled' => is_ssl(),
            'meta_data' => [],
        ];

        // Check for sitemap
        $sitemap_urls = [
            home_url('/sitemap.xml'),
            home_url('/sitemap_index.xml'),
            home_url('/wp-sitemap.xml')
        ];

        foreach ($sitemap_urls as $sitemap_url) {
            $response = wp_remote_head($sitemap_url, ['timeout' => 10]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $seo_data['sitemap_present'] = true;
                $seo_data['sitemap_url'] = $sitemap_url;
                break;
            }
        }

        // Check robots.txt
        $robots_response = wp_remote_head(home_url('/robots.txt'), ['timeout' => 10]);
        if (!is_wp_error($robots_response) && wp_remote_retrieve_response_code($robots_response) === 200) {
            $seo_data['robots_txt_present'] = true;
        }

        return $seo_data;
    }

    /**
     * Collect Performance specific data
     */
    private function collect_performance_data() {
        return [
            'plugins' => [],
            'image_optimization' => $this->check_image_optimization(),
            'minification' => $this->check_minification(),
            'compression' => $this->check_compression(),
            'cdn_usage' => $this->detect_cdn_usage(),
        ];
    }

    /**
     * Collect Security specific data  
     */
    private function collect_security_data() {
        return [
            'plugins' => [],
            'ssl_enabled' => is_ssl(),
            'wp_version_hidden' => $this->check_wp_version_hidden(),
            'admin_user_exists' => username_exists('admin') ? true : false,
            'file_permissions' => $this->check_file_permissions(),
        ];
    }

    /**
     * Collect Caching specific data
     */
    private function collect_caching_data() {
        return [
            'plugins' => [],
            'page_caching' => $this->check_page_caching(),
            'object_caching' => $this->check_object_caching(),
        ];
    }

    /**
     * Collect E-commerce specific data
     */
    private function collect_ecommerce_data() {
        $ecommerce_data = ['plugins' => []];

        // WooCommerce specific data
        if (class_exists('WooCommerce')) {
            global $wpdb;
            
            $ecommerce_data['platform'] = 'WooCommerce';
            $ecommerce_data['version'] = WC()->version;
            
            // Product counts
            $product_counts = wp_count_posts('product');
            $ecommerce_data['products'] = [
                'total' => $product_counts->publish,
                'draft' => $product_counts->draft,
                'private' => $product_counts->private
            ];

            // Payment methods
            $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            $ecommerce_data['payment_methods'] = array_keys($payment_gateways);
        }

        // Easy Digital Downloads
        if (class_exists('Easy_Digital_Downloads')) {
            $ecommerce_data['platform'] = 'Easy Digital Downloads';
            $download_counts = wp_count_posts('download');
            $ecommerce_data['downloads'] = [
                'total' => $download_counts->publish,
                'draft' => $download_counts->draft
            ];
        }

        return $ecommerce_data;
    }

    /**
     * Collect Forms specific data
     */
    private function collect_forms_data() {
        global $wpdb;
        
        $forms_data = ['plugins' => []];

        // Contact Form 7
        if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            $cf7_forms = get_posts(['post_type' => 'wpcf7_contact_form', 'numberposts' => -1]);
            $forms_data['contact_form_7'] = [
                'total_forms' => count($cf7_forms),
                'forms' => array_map(function($form) {
                    return ['id' => $form->ID, 'title' => $form->post_title];
                }, $cf7_forms)
            ];

            // Get submission data from Flamingo if available
            if (is_plugin_active('flamingo/flamingo.php')) {
                $submissions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'flamingo_inbound'");
                $forms_data['contact_form_7']['total_submissions'] = (int) $submissions;
            }
        }

        // WPForms
        if (is_plugin_active('wpforms-lite/wpforms.php') || is_plugin_active('wpforms/wpforms.php')) {
            $wpforms = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'wpforms' AND post_status = 'publish'");
            $forms_data['wpforms'] = [
                'total_forms' => count($wpforms),
                'forms' => array_map(function($form) {
                    return ['id' => $form->ID, 'title' => $form->post_title];
                }, $wpforms)
            ];

            // Get entries if WPForms Pro
            $entries_table = $wpdb->prefix . 'wpforms_entries';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$entries_table}'") == $entries_table) {
                $entries = $wpdb->get_var("SELECT COUNT(*) FROM {$entries_table}");
                $forms_data['wpforms']['total_entries'] = (int) $entries;
            }
        }

        // Gravity Forms
        if (class_exists('GFForms')) {
            $gf_forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rg_form WHERE is_active = 1");
            $forms_data['gravity_forms'] = [
                'total_forms' => count($gf_forms),
                'forms' => array_map(function($form) {
                    return ['id' => $form->id, 'title' => $form->title];
                }, $gf_forms)
            ];

            // Get entries
            $entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rg_lead WHERE status = 'active'");
            $forms_data['gravity_forms']['total_entries'] = (int) $entries;
        }

        return $forms_data;
    }

    /**
     * Collect Accessibility specific data
     */
    private function collect_accessibility_data() {
        $accessibility_data = ['plugins' => []];

        // Basic accessibility checks
        $accessibility_data['basic_checks'] = [
            'alt_text_missing' => $this->check_missing_alt_text(),
            'ssl_enabled' => is_ssl(),
            'theme_support' => current_theme_supports('accessibility')
        ];

        // WCAG compliance indicators
        $accessibility_data['wcag_compliance'] = [
            'level' => $this->estimate_wcag_level(),
            'manual_review_needed' => true
        ];

        return $accessibility_data;
    }

    // Helper methods for data collection
    private function check_image_optimization() {
        return is_plugin_active('wp-smushit/wp-smush.php') || 
               is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php') ||
               is_plugin_active('imagify/imagify.php');
    }

    private function check_minification() {
        return is_plugin_active('autoptimize/autoptimize.php') || 
               is_plugin_active('wp-rocket/wp-rocket.php');
    }

    private function check_compression() {
        return function_exists('gzencode') && ini_get('zlib.output_compression');
    }

    private function detect_cdn_usage() {
        $headers = get_headers(home_url(), 1);
        $cdn_patterns = ['cloudflare', 'cloudfront', 'maxcdn', 'keycdn'];
        
        if (is_array($headers)) {
            foreach ($headers as $header => $value) {
                foreach ($cdn_patterns as $pattern) {
                    if (stripos($header . $value, $pattern) !== false) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function check_wp_version_hidden() {
        return !has_action('wp_head', 'wp_generator');
    }

    private function check_file_permissions() {
        if (file_exists(ABSPATH . 'wp-config.php')) {
            $wp_config_perms = substr(sprintf('%o', fileperms(ABSPATH . 'wp-config.php')), -4);
            return [
                'wp_config' => $wp_config_perms,
                'secure' => in_array($wp_config_perms, ['0644', '0640', '0600'])
            ];
        }
        return ['wp_config' => 'unknown', 'secure' => false];
    }

    private function check_page_caching() {
        return is_plugin_active('wp-rocket/wp-rocket.php') || 
               is_plugin_active('w3-total-cache/w3-total-cache.php') ||
               is_plugin_active('wp-super-cache/wp-cache.php');
    }

    private function check_object_caching() {
        return wp_using_ext_object_cache();
    }

    private function check_missing_alt_text() {
        global $wpdb;
        $images_without_alt = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} p 
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
            AND p.ID NOT IN (
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_wp_attachment_image_alt' 
                AND meta_value != ''
            )
        ");
        
        $total_images = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");

        return [
            'missing_alt_count' => (int) $images_without_alt,
            'total_images' => (int) $total_images,
            'percentage_missing' => $total_images > 0 ? round(($images_without_alt / $total_images) * 100, 2) : 0
        ];
    }

    private function estimate_wcag_level() {
        $score = 0;
        
        // Check basic requirements
        if (is_ssl()) $score += 25;
        if (is_plugin_active('wp-accessibility/wp-accessibility.php')) $score += 25;
        if (current_theme_supports('accessibility')) $score += 25;
        
        $alt_text_data = $this->check_missing_alt_text();
        if ($alt_text_data['percentage_missing'] < 10) $score += 25;

        if ($score >= 75) return 'AA (Estimated)';
        if ($score >= 50) return 'A (Estimated)';
        return 'Below A (Needs Improvement)';
    }
}