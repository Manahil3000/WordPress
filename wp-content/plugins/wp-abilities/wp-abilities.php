<?php
/**
 * Plugin Name: WP Abilities
 * Description: Provides a REST API endpoint for ability categories.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit; // security

function wp_abilities_get_categories() {
    $categories = [
        [
            'slug' => 'site',
            'label' => 'Site Category',
            'description' => 'A site-related category',
            'meta' => [],
            '_links' => [
                'self' => get_rest_url(null, '/wp-abilities/v1/categories/site')
            ]
        ],
        [
            'slug' => 'admin',
            'label' => 'Admin Category',
            'description' => 'Admin-related category',
            'meta' => [],
            '_links' => [
                'self' => get_rest_url(null, '/wp-abilities/v1/categories/admin')
            ]
        ]
    ];
    return $categories;
}



// Properly register REST route
add_action('rest_api_init', function() {
    register_rest_route('wp-abilities/v1', '/categories', [
        'methods' => 'GET',
        'callback' => 'wp_abilities_get_categories',
        'permission_callback' => '__return_true', // <- must be here
    ]);
});
