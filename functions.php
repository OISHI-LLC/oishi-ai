<?php
/**
 * Oishi AI Theme Functions
 */

// Enqueue theme stylesheet
function oishi_ai_enqueue_styles() {
    wp_enqueue_style('oishi-ai-style', get_stylesheet_uri(), array(), '1.0');
}
add_action('wp_enqueue_scripts', 'oishi_ai_enqueue_styles');

// Theme support
function oishi_ai_setup() {
    add_theme_support('title-tag');
    add_theme_support('html5', array('search-form', 'comment-form', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'oishi_ai_setup');

// Remove unnecessary head items for performance
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Disable block library CSS (not needed for this theme)
function oishi_ai_dequeue_block_styles() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('wp-img-auto-sizes-contain');
}
add_action('wp_enqueue_scripts', 'oishi_ai_dequeue_block_styles', 100);
