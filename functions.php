<?php
/**
 * Oishi AI Theme Functions
 */

// Enqueue theme stylesheet
function oishi_ai_enqueue_styles() {
    $style_path = get_stylesheet_directory() . '/style.css';
    $style_ver = file_exists($style_path) ? (string) filemtime($style_path) : '1.0.0';
    wp_enqueue_style('oishi-ai-style', get_stylesheet_uri(), array(), $style_ver);
}
add_action('wp_enqueue_scripts', 'oishi_ai_enqueue_styles');

// Theme support
function oishi_ai_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'oishi_ai_setup');

// Custom excerpt length
function oishi_ai_excerpt_length($length) {
    return 40;
}
add_filter('excerpt_length', 'oishi_ai_excerpt_length');

// Remove "[...]" from excerpt
function oishi_ai_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'oishi_ai_excerpt_more');

// Remove unnecessary head items for performance
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_head', 'wp_site_icon', 99);
remove_action('wp_print_styles', 'print_emoji_styles');

// Force a theme-managed site icon so search and browser tabs use the brand logo.
function oishi_ai_site_icon_links() {
    $template_dir = get_template_directory();
    $template_uri = get_template_directory_uri();

    $icon_512_ver = file_exists($template_dir . '/site-icon.png') ? (string) filemtime($template_dir . '/site-icon.png') : '1';
    $icon_192_ver = file_exists($template_dir . '/site-icon-192.png') ? (string) filemtime($template_dir . '/site-icon-192.png') : '1';
    $apple_ver    = file_exists($template_dir . '/apple-touch-icon.png') ? (string) filemtime($template_dir . '/apple-touch-icon.png') : '1';

    $icon_512 = esc_url($template_uri . '/site-icon.png?v=' . $icon_512_ver);
    $icon_192 = esc_url($template_uri . '/site-icon-192.png?v=' . $icon_192_ver);
    $apple    = esc_url($template_uri . '/apple-touch-icon.png?v=' . $apple_ver);

    echo '<link rel="icon" type="image/png" sizes="512x512" href="' . $icon_512 . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . $icon_192 . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . $apple . '">' . "\n";
}
add_action('wp_head', 'oishi_ai_site_icon_links', 1);

// Disable block library CSS (not needed for this theme)
function oishi_ai_dequeue_block_styles() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('wp-img-auto-sizes-contain');
}
add_action('wp_enqueue_scripts', 'oishi_ai_dequeue_block_styles', 100);

// SMTP configuration (credentials in wp-config.php)
function oishi_ai_smtp_setup($phpmailer) {
    if (!defined('OISHI_SMTP_PASSWORD')) {
        return;
    }
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 587;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->Username   = 'info@oishillc.jp';
    $phpmailer->Password   = OISHI_SMTP_PASSWORD;
    $phpmailer->From       = 'info@oishillc.jp';
    $phpmailer->FromName   = 'AI Lab OISHI';
}
add_action('phpmailer_init', 'oishi_ai_smtp_setup');

// Contact form handler
function oishi_ai_handle_contact() {
    $redirect_base = home_url('/contact/');

    if (!isset($_POST['_oishi_nonce']) || !wp_verify_nonce($_POST['_oishi_nonce'], 'oishi_contact_nonce')) {
        wp_safe_redirect($redirect_base . '?' . http_build_query(['contact' => 'error', 'msg' => '不正なリクエストです。']));
        exit;
    }

    $name    = sanitize_text_field($_POST['contact_name'] ?? '');
    $email   = sanitize_email($_POST['contact_email'] ?? '');
    $message = sanitize_textarea_field($_POST['contact_message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        wp_safe_redirect($redirect_base . '?' . http_build_query(['contact' => 'error', 'msg' => 'すべての項目を入力してください。']));
        exit;
    }

    if (!is_email($email)) {
        wp_safe_redirect($redirect_base . '?' . http_build_query(['contact' => 'error', 'msg' => '有効なメールアドレスを入力してください。']));
        exit;
    }

    $to      = 'info@oishillc.jp';
    $subject = '【お問い合わせ】' . $name . ' 様';
    $body    = "お名前: {$name}\nメールアドレス: {$email}\n\n{$message}";
    $headers = [
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);

    if ($sent) {
        wp_safe_redirect($redirect_base . '?contact=success');
    } else {
        wp_safe_redirect($redirect_base . '?' . http_build_query(['contact' => 'error', 'msg' => '送信に失敗しました。時間をおいて再度お試しください。']));
    }
    exit;
}
add_action('admin_post_nopriv_oishi_contact', 'oishi_ai_handle_contact');
add_action('admin_post_oishi_contact', 'oishi_ai_handle_contact');
