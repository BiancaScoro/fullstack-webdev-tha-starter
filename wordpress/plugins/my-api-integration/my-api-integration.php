<?php
/*
Plugin Name: CI API Integration
Description: Fetches and caches data from CodeIgniter API and exposes REST endpoints for Gatsby.
Version: 1.1
Author: Your Name
*/
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
}

if (!defined('ABSPATH')) exit;

/**
 * Fetch data from CodeIgniter API with caching and error handling
 *
 * @param string $endpoint Full URL of the API endpoint
 * @param int $cache_time Cache duration in seconds
 * @return array
 */
function ci_fetch_data($endpoint, $cache_time = HOUR_IN_SECONDS) {
    $transient_key = 'ci_cache_' . md5($endpoint);

    // Try cached data first
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        return $cached;
    }

    // Fetch data from API
    $response = wp_remote_get($endpoint, ['timeout' => 15]);
    if (is_wp_error($response)) {
        return [
            'error' => true,
            'message' => 'Unable to reach CodeIgniter API.'
        ];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for invalid JSON
    if (!is_array($data)) {
        return [
            'error' => true,
            'message' => 'Invalid API response. Make sure the CI API returns JSON.'
        ];
    }

    // Cache the data
    set_transient($transient_key, $data, $cache_time);
    return $data;
}

/**
 * Shortcode: Display CI users
 */
function ci_display_users() {
    $users = ci_fetch_data("http://codeigniter_app:80/index.php/users");

    if (isset($users['error'])) {
        return '<p>Error: ' . esc_html($users['message']) . '</p>';
    }

    return '<pre>' . esc_html(print_r($users, true)) . '</pre>';
}
add_shortcode('ci_users', 'ci_display_users');

/**
 * Shortcode: Display CI products
 */
function ci_display_products() {
    $products = ci_fetch_data("http://codeigniter_app:80/index.php/products");

    if (isset($products['error'])) {
        return '<p>Error: ' . esc_html($products['message']) . '</p>';
    }

    return '<pre>' . esc_html(print_r($products, true)) . '</pre>';
}
add_shortcode('ci_products', 'ci_display_products');

/**
 * REST API endpoints for Gatsby
 */
add_action('rest_api_init', function () {
    register_rest_route('ci-api/v1', '/users', [
        'methods' => 'GET',
        'callback' => function() {
            $users = ci_fetch_data("http://localhost:8020/index.php/users");

            if (isset($users['error'])) {
                return [
                    'success' => false,
                    'message' => $users['message']
                ];
            }

            // Enhance users data for Gatsby
            $enhanced = [];
            foreach ($users as $user) {
                $enhanced[] = [
                    'id'    => $user['id'] ?? null,
                    'name'  => $user['name'] ?? null,
                    'email' => $user['email'] ?? null
                ];
            }

            return [
                'success' => true,
                'data'    => $enhanced
            ];
        }
    ]);

    register_rest_route('ci-api/v1', '/products', [
        'methods' => 'GET',
        'callback' => function() {
            $products = ci_fetch_data("http://localhost:8020/index.php/products");

            if (isset($products['error'])) {
                return [
                    'success' => false,
                    'message' => $products['message']
                ];
            }

            // Enhance products data for Gatsby
            $enhanced = [];
            foreach ($products as $p) {
                $enhanced[] = [
                    'id'    => $p['id'] ?? null,
                    'title' => $p['title'] ?? $p['name'] ?? null,
                    'price' => $p['price'] ?? null
                ];
            }

            return [
                'success' => true,
                'data'    => $enhanced
            ];
        }
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('my-api/v1', '/user/(?P<id>\d+)/products', [
        'methods'  => 'GET',
        'callback' => 'my_api_get_user_products',
        'permission_callback' => '__return_true',
    ]);
});

function my_api_get_user_products($request) {
    $user_id = intval($request['id']);
    $ci_endpoint = "http://codeigniter_app:80/index.php/user/{$user_id}/products";

    $data = ci_fetch_data($ci_endpoint);

    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => $data['message']
        ];
    }

    // Filter only published products if CI provides a 'status' field
    $published = array_filter($data, function($p) {
        return isset($p['status']) && strtolower($p['status']) === 'published';
    });

    return [
        'success' => true,
        'user_id' => $user_id,
        'data'    => array_values($published)
    ];
}


