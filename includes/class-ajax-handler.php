<?php
/**
 * AJAX handler class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Firedraft_Reports_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_firedraft_generate_report', array($this, 'handle_generate_report'));
        add_action('wp_ajax_firedraft_clear_report', array($this, 'handle_clear_report'));
    }

    /**
     * Handle generate report AJAX request
     */
    public function handle_generate_report() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'firedraft_generate_report')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'firedraft-reports'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'firedraft-reports'));
        }

        $template = sanitize_text_field($_POST['template'] ?? 'website_handover_report');
        
        // Get valid templates
        $admin = new Firedraft_Reports_Admin();
        $valid_templates = array_keys($admin->get_report_templates());
        
        // Validate template
        if (!in_array($template, $valid_templates)) {
            wp_send_json_error(__('Invalid report template selected.', 'firedraft-reports'));
        }

        // Collect site data
        $data_collector = new Firedraft_Reports_Data_Collector();
        $site_data = $data_collector->collect_site_data();

        $handover_data = [
            'section' => $template,
            'json_data' => $site_data
        ];

        // Make request to report generation service
        $response = wp_remote_post(
            'https://wp-handover.onrender.com/generate',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode($handover_data),
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(__('Error connecting to report service: ', 'firedraft-reports') . $response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wp_send_json_error(__('Report service returned error code: ', 'firedraft-reports') . $response_code);
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid response from report service.', 'firedraft-reports'));
            return;
        }

        $body = $data['result'] ?? '';

        if (empty($body)) {
            wp_send_json_error(__('No content received from report service.', 'firedraft-reports'));
            return;
        }

        // Save the report data and selected template
        update_option('firedraft_report_data', $body);
        update_option('firedraft_selected_template', $template);

        wp_send_json_success([
            'message' => __('Report generated successfully!', 'firedraft-reports'),
            'reload' => true
        ]);
    }

    /**
     * Handle clear report AJAX request
     */
    public function handle_clear_report() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'firedraft_clear_report')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'firedraft-reports'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'firedraft-reports'));
        }

        // Clear the saved data
        delete_option('firedraft_report_data');
        delete_option('firedraft_selected_template');
        
        wp_send_json_success([
            'message' => __('Report content cleared successfully!', 'firedraft-reports'),
            'reload' => true
        ]);
    }
}
