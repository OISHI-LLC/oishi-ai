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

// Remove WordPress defaults this theme does not use.
function oishi_ai_cleanup_core_output() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_head', 'wp_site_icon', 99);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);

    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
    remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    remove_action('wp_footer', 'wp_global_styles_render_svg_filters', 1);
}
add_action('after_setup_theme', 'oishi_ai_cleanup_core_output', 20);

// Build icon URLs once so frontend/login/admin stay in sync.
function oishi_ai_get_site_icon_urls() {
    $template_dir = get_template_directory();
    $template_uri = get_template_directory_uri();
    $root_favicon = esc_url(home_url('/favicon.ico'));

    $icon_512_ver = file_exists($template_dir . '/site-icon.png') ? (string) filemtime($template_dir . '/site-icon.png') : '1';
    $icon_192_ver = file_exists($template_dir . '/site-icon-192.png') ? (string) filemtime($template_dir . '/site-icon-192.png') : '1';
    $icon_32_ver  = file_exists($template_dir . '/favicon-32x32.png') ? (string) filemtime($template_dir . '/favicon-32x32.png') : '1';
    $icon_16_ver  = file_exists($template_dir . '/favicon-16x16.png') ? (string) filemtime($template_dir . '/favicon-16x16.png') : '1';
    $apple_ver    = file_exists($template_dir . '/apple-touch-icon.png') ? (string) filemtime($template_dir . '/apple-touch-icon.png') : '1';

    return array(
        'root'     => $root_favicon,
        'icon_512' => esc_url($template_uri . '/site-icon.png?v=' . $icon_512_ver),
        'icon_192' => esc_url($template_uri . '/site-icon-192.png?v=' . $icon_192_ver),
        'icon_32'  => esc_url($template_uri . '/favicon-32x32.png?v=' . $icon_32_ver),
        'icon_16'  => esc_url($template_uri . '/favicon-16x16.png?v=' . $icon_16_ver),
        'apple'    => esc_url($template_uri . '/apple-touch-icon.png?v=' . $apple_ver),
    );
}

// Force a theme-managed site icon so tabs show brand icon in frontend/admin/login.
function oishi_ai_print_site_icon_links() {
    $icons = oishi_ai_get_site_icon_urls();
    echo '<link rel="icon" href="' . $icons['root'] . '" sizes="any">' . "\n";
    echo '<link rel="shortcut icon" href="' . $icons['root'] . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . $icons['icon_32'] . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . $icons['icon_16'] . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="512x512" href="' . $icons['icon_512'] . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . $icons['icon_192'] . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . $icons['apple'] . '">' . "\n";
}
add_action('wp_head', 'oishi_ai_print_site_icon_links', 1);
add_action('admin_head', 'oishi_ai_print_site_icon_links', 1);
add_action('login_head', 'oishi_ai_print_site_icon_links', 1);

// Serve /favicon.ico directly with 200 so crawlers see a stable icon URL.
function oishi_ai_serve_favicon_request() {
    $request_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($request_uri !== '/favicon.ico') {
        return;
    }

    $favicon_path = get_template_directory() . '/favicon.ico';
    if (!file_exists($favicon_path)) {
        status_header(404);
        exit;
    }

    $favicon_mtime = filemtime($favicon_path);
    $favicon_size  = filesize($favicon_path);
    $etag          = '"' . md5((string) $favicon_mtime . ':' . (string) $favicon_size) . '"';
    $last_modified = gmdate('D, d M Y H:i:s', $favicon_mtime) . ' GMT';

    if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
        (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $last_modified)) {
        status_header(304);
        header('ETag: ' . $etag);
        header('Last-Modified: ' . $last_modified);
        exit;
    }

    header('Content-Type: image/x-icon');
    header('Content-Length: ' . (string) $favicon_size);
    header('Cache-Control: public, max-age=86400');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . $last_modified);
    readfile($favicon_path);
    exit;
}
add_action('template_redirect', 'oishi_ai_serve_favicon_request', 1);

// Disable block library CSS (not needed for this theme)
function oishi_ai_dequeue_block_styles() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('wp-img-auto-sizes-contain');
}
add_action('wp_enqueue_scripts', 'oishi_ai_dequeue_block_styles', 100);

function oishi_ai_get_logo_image_data() {
    $template_dir = get_template_directory();
    $template_uri = get_template_directory_uri();

    $logo_56_path  = $template_dir . '/logo-56.png';
    $logo_112_path = $template_dir . '/logo-112.png';
    $logo_512_path = $template_dir . '/logo.png';

    $logo_56_ver  = file_exists($logo_56_path) ? (string) filemtime($logo_56_path) : '1';
    $logo_112_ver = file_exists($logo_112_path) ? (string) filemtime($logo_112_path) : '1';
    $logo_512_ver = file_exists($logo_512_path) ? (string) filemtime($logo_512_path) : '1';

    $src_56  = esc_url($template_uri . '/logo-56.png?v=' . $logo_56_ver);
    $src_112 = esc_url($template_uri . '/logo-112.png?v=' . $logo_112_ver);
    $src_512 = esc_url($template_uri . '/logo.png?v=' . $logo_512_ver);

    return array(
        'src'     => $src_56,
        'srcset'  => $src_56 . ' 56w, ' . $src_112 . ' 112w, ' . $src_512 . ' 512w',
        'sizes'   => '28px',
        'width'   => 28,
        'height'  => 28,
        'primary' => $src_512,
    );
}

function oishi_ai_get_logo_image_html($class = 'site-logo-img') {
    $logo = oishi_ai_get_logo_image_data();
    return sprintf(
        '<img src="%1$s" srcset="%2$s" sizes="%3$s" width="%4$d" height="%5$d" loading="eager" decoding="async" fetchpriority="high" alt="AI Lab OISHIロゴ" class="%6$s">',
        $logo['src'],
        esc_attr($logo['srcset']),
        esc_attr($logo['sizes']),
        (int) $logo['width'],
        (int) $logo['height'],
        esc_attr($class)
    );
}

function oishi_ai_local_file_to_url($file_path) {
    $normalized_path = wp_normalize_path($file_path);
    $theme_dir       = wp_normalize_path(get_template_directory());

    if (strpos($normalized_path, $theme_dir . '/') === 0) {
        $relative = ltrim(substr($normalized_path, strlen($theme_dir)), '/');
        return trailingslashit(get_template_directory_uri()) . $relative;
    }

    $uploads = wp_upload_dir();
    $basedir = wp_normalize_path($uploads['basedir'] ?? '');

    if ($basedir !== '' && strpos($normalized_path, $basedir . '/') === 0) {
        $relative = ltrim(substr($normalized_path, strlen($basedir)), '/');
        return trailingslashit($uploads['baseurl']) . $relative;
    }

    return '';
}

function oishi_ai_get_local_file_path_from_url($url) {
    if (!is_string($url) || $url === '') {
        return '';
    }

    $parsed = wp_parse_url($url);
    if ($parsed === false) {
        return '';
    }

    $path = $parsed['path'] ?? '';
    if ($path === '') {
        return '';
    }

    $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $url_host  = strtolower((string) ($parsed['host'] ?? ''));

    $normalized_site_host = preg_replace('/^www\./', '', $site_host);
    $normalized_url_host  = preg_replace('/^www\./', '', $url_host);

    if ($normalized_url_host !== '' && $normalized_site_host !== $normalized_url_host) {
        return '';
    }

    $path      = wp_normalize_path($path);
    $theme_dir = wp_normalize_path(get_template_directory());
    $theme_rel = '/wp-content/themes/' . basename($theme_dir) . '/';

    if (strpos($path, $theme_rel) !== false) {
        $relative  = ltrim(substr($path, strpos($path, $theme_rel) + strlen($theme_rel)), '/');
        $candidate = realpath($theme_dir . '/' . $relative);
        $real_root = realpath($theme_dir);
        if ($candidate && $real_root) {
            $candidate_norm = wp_normalize_path($candidate);
            $root_norm      = wp_normalize_path($real_root);
            if (strpos($candidate_norm, $root_norm . '/') === 0 && is_file($candidate_norm)) {
                return $candidate_norm;
            }
        }
    }

    $uploads      = wp_upload_dir();
    $uploads_base = wp_normalize_path($uploads['basedir'] ?? '');
    $uploads_path = wp_normalize_path((string) wp_parse_url($uploads['baseurl'] ?? '', PHP_URL_PATH));

    if ($uploads_base !== '' && $uploads_path !== '' && strpos($path, $uploads_path . '/') === 0) {
        $relative  = ltrim(substr($path, strlen($uploads_path)), '/');
        $candidate = realpath($uploads_base . '/' . $relative);
        $real_root = realpath($uploads_base);
        if ($candidate && $real_root) {
            $candidate_norm = wp_normalize_path($candidate);
            $root_norm      = wp_normalize_path($real_root);
            if (strpos($candidate_norm, $root_norm . '/') === 0 && is_file($candidate_norm)) {
                return $candidate_norm;
            }
        }
    }

    return '';
}

function oishi_ai_get_image_dimensions_from_url($url) {
    $file_path = oishi_ai_get_local_file_path_from_url($url);
    if ($file_path === '' || !is_file($file_path)) {
        return array('width' => 0, 'height' => 0);
    }

    $size = @getimagesize($file_path);
    if (!$size || empty($size[0]) || empty($size[1])) {
        return array('width' => 0, 'height' => 0);
    }

    return array(
        'width'  => (int) $size[0],
        'height' => (int) $size[1],
    );
}

function oishi_ai_build_local_srcset($src_url) {
    $file_path = oishi_ai_get_local_file_path_from_url($src_url);
    if ($file_path === '') {
        return '';
    }

    $info = pathinfo($file_path);
    if (empty($info['dirname']) || empty($info['filename']) || empty($info['extension'])) {
        return '';
    }

    $extension  = strtolower($info['extension']);
    $base_path  = $info['dirname'] . '/' . $info['filename'];
    $candidates = array($file_path);
    $suffixes   = array('-56', '-112', '-192', '-320', '-480', '-640', '-768', '-800', '-1024', '-1200');

    foreach ($suffixes as $suffix) {
        $candidate = $base_path . $suffix . '.' . $extension;
        if (is_file($candidate)) {
            $candidates[] = $candidate;
        }
    }

    $entries = array();
    foreach ($candidates as $candidate) {
        $size = @getimagesize($candidate);
        if (!$size || empty($size[0])) {
            continue;
        }

        $width = (int) $size[0];
        $url   = oishi_ai_local_file_to_url($candidate);

        if ($width > 0 && $url !== '') {
            $entries[$width] = esc_url($url) . ' ' . $width . 'w';
        }
    }

    ksort($entries, SORT_NUMERIC);

    if (count($entries) < 2) {
        return '';
    }

    return implode(', ', $entries);
}

function oishi_ai_extract_first_image_url($content) {
    if (!is_string($content) || stripos($content, '<img') === false) {
        return '';
    }

    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
        return esc_url_raw($matches[1]);
    }

    return '';
}

function oishi_ai_get_attachment_id_from_url($image_url) {
    if (!is_string($image_url) || $image_url === '') {
        return 0;
    }

    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id > 0) {
        return (int) $attachment_id;
    }

    $parsed_url = wp_parse_url($image_url);
    if ($parsed_url === false || empty($parsed_url['host']) || empty($parsed_url['path'])) {
        return 0;
    }

    $site_host            = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $normalized_site_host = preg_replace('/^www\./', '', $site_host);
    $normalized_url_host  = preg_replace('/^www\./', '', strtolower((string) $parsed_url['host']));

    if ($normalized_site_host !== $normalized_url_host || $site_host === '') {
        return 0;
    }

    $rebuilt_url = (isset($parsed_url['scheme']) ? $parsed_url['scheme'] : 'https') . '://' . $site_host . $parsed_url['path'];
    if (!empty($parsed_url['query'])) {
        $rebuilt_url .= '?' . $parsed_url['query'];
    }

    $attachment_id = attachment_url_to_postid($rebuilt_url);
    return $attachment_id > 0 ? (int) $attachment_id : 0;
}

function oishi_ai_canonicalize_site_url($url) {
    if (!is_string($url) || $url === '') {
        return '';
    }

    $parsed = wp_parse_url($url);
    if ($parsed === false) {
        return '';
    }

    if (empty($parsed['host'])) {
        return esc_url_raw($url);
    }

    $site_scheme = (string) wp_parse_url(home_url('/'), PHP_URL_SCHEME);
    $site_host   = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $url_host    = strtolower((string) $parsed['host']);

    $normalized_site_host = preg_replace('/^www\./', '', $site_host);
    $normalized_url_host  = preg_replace('/^www\./', '', $url_host);

    if ($normalized_site_host !== $normalized_url_host || $site_host === '') {
        return esc_url_raw($url);
    }

    $scheme = $site_scheme !== '' ? $site_scheme : ((string) ($parsed['scheme'] ?? 'https'));
    $path   = (string) ($parsed['path'] ?? '');
    $query  = (string) ($parsed['query'] ?? '');

    $rebuilt = $scheme . '://' . $site_host . $path;
    if ($query !== '') {
        $rebuilt .= '?' . $query;
    }

    return esc_url_raw($rebuilt);
}

function oishi_ai_get_attachment_srcset_by_url($image_url) {
    if (!is_string($image_url) || $image_url === '') {
        return '';
    }

    $attachment_id = oishi_ai_get_attachment_id_from_url($image_url);
    if ($attachment_id <= 0) {
        return '';
    }

    $srcset = wp_get_attachment_image_srcset($attachment_id, 'full');
    return is_string($srcset) ? trim($srcset) : '';
}

function oishi_ai_get_attachment_dimensions_by_url($image_url) {
    if (!is_string($image_url) || $image_url === '') {
        return array('width' => 0, 'height' => 0);
    }

    $attachment_id = oishi_ai_get_attachment_id_from_url($image_url);
    if ($attachment_id <= 0) {
        return array('width' => 0, 'height' => 0);
    }

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (!is_array($metadata)) {
        return array('width' => 0, 'height' => 0);
    }

    $width  = isset($metadata['width']) ? (int) $metadata['width'] : 0;
    $height = isset($metadata['height']) ? (int) $metadata['height'] : 0;

    return array('width' => $width, 'height' => $height);
}

function oishi_ai_get_context_description() {
    if (is_singular()) {
        $description = get_the_excerpt();
        if ($description === '') {
            $description = wp_strip_all_tags((string) get_post_field('post_content', (int) get_queried_object_id()));
        }
        return wp_trim_words(trim(preg_replace('/\s+/u', ' ', $description)), 40, '...');
    }

    if (is_home() || is_archive()) {
        return 'AI Lab OISHI - AIコンサルティングに関する知見やトレンドを発信しています。';
    }

    return 'AI Lab OISHI - 小規模事業者から大企業まで、AI導入を戦略から実装までワンストップで支援します。';
}

function oishi_ai_get_context_image_data() {
    $fallback_logo = oishi_ai_get_logo_image_data();
    $image_url     = $fallback_logo['primary'];

    if (is_singular('post')) {
        $post_id = (int) get_queried_object_id();

        if (has_post_thumbnail($post_id)) {
            $thumbnail = get_the_post_thumbnail_url($post_id, 'full');
            if (is_string($thumbnail) && $thumbnail !== '') {
                $image_url = $thumbnail;
            }
        } else {
            $content_image = oishi_ai_extract_first_image_url((string) get_post_field('post_content', $post_id));
            if ($content_image !== '') {
                $image_url = $content_image;
            }
        }
    }

    $image_url  = oishi_ai_canonicalize_site_url($image_url);
    $dimensions = oishi_ai_get_image_dimensions_from_url($image_url);
    if ($dimensions['width'] <= 0 || $dimensions['height'] <= 0) {
        $dimensions = array('width' => 512, 'height' => 512);
    }

    return array(
        'url'    => esc_url($image_url),
        'width'  => (int) $dimensions['width'],
        'height' => (int) $dimensions['height'],
    );
}

function oishi_ai_print_social_meta_tags() {
    if (is_admin() || is_feed() || is_robots() || is_trackback()) {
        return;
    }

    $request_path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $page_url     = is_singular() ? get_permalink() : home_url($request_path !== '' ? $request_path : '/');
    $title        = wp_get_document_title();
    $description  = oishi_ai_get_context_description();
    $image        = oishi_ai_get_context_image_data();
    $og_type      = is_singular('post') ? 'article' : 'website';

    echo '<meta property="og:locale" content="ja_JP">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
    echo '<meta property="og:site_name" content="AI Lab OISHI">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($page_url) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
    echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
    echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
}
add_action('wp_head', 'oishi_ai_print_social_meta_tags', 3);

function oishi_ai_print_article_json_ld() {
    if (!is_singular('post')) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ($post_id <= 0) {
        return;
    }

    $logo  = oishi_ai_get_logo_image_data();
    $image = oishi_ai_get_context_image_data();

    $data = array(
        '@context'          => 'https://schema.org',
        '@type'             => 'BlogPosting',
        'headline'          => wp_strip_all_tags(get_the_title($post_id)),
        'description'       => oishi_ai_get_context_description(),
        'datePublished'     => get_the_date(DATE_W3C, $post_id),
        'dateModified'      => get_the_modified_date(DATE_W3C, $post_id),
        'mainEntityOfPage'  => array(
            '@type' => 'WebPage',
            '@id'   => get_permalink($post_id),
        ),
        'author'            => array(
            '@type' => 'Organization',
            'name'  => 'AI Lab OISHI',
        ),
        'publisher'         => array(
            '@type' => 'Organization',
            'name'  => 'AI Lab OISHI',
            'logo'  => array(
                '@type' => 'ImageObject',
                'url'   => esc_url($logo['primary']),
            ),
        ),
        'image'             => array(
            '@type'  => 'ImageObject',
            'url'    => esc_url($image['url']),
            'width'  => (int) $image['width'],
            'height' => (int) $image['height'],
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'oishi_ai_print_article_json_ld', 20);

function oishi_ai_optimize_content_images($content) {
    if (!is_singular('post') || stripos($content, '<img') === false || !class_exists('DOMDocument')) {
        return $content;
    }

    $previous_use_errors = libxml_use_internal_errors(true);
    $dom                 = new DOMDocument();
    $loaded              = $dom->loadHTML(
        '<?xml encoding="utf-8" ?><div id="oishi-content-root">' . $content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previous_use_errors);
        return $content;
    }

    $root = $dom->getElementById('oishi-content-root');
    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previous_use_errors);
        return $content;
    }

    $fallback_alt = wp_strip_all_tags(get_the_title((int) get_queried_object_id())) . ' の図解';
    $images       = $root->getElementsByTagName('img');

    foreach ($images as $image) {
        $src = trim((string) $image->getAttribute('src'));
        if ($src === '') {
            continue;
        }

        if ($image->getAttribute('loading') === '') {
            $image->setAttribute('loading', 'lazy');
        }
        if ($image->getAttribute('decoding') === '') {
            $image->setAttribute('decoding', 'async');
        }
        if ($image->getAttribute('fetchpriority') === '') {
            $image->setAttribute('fetchpriority', 'low');
        }
        if (trim((string) $image->getAttribute('alt')) === '') {
            $image->setAttribute('alt', $fallback_alt);
        }

        if ($image->getAttribute('width') === '' || $image->getAttribute('height') === '') {
            $size = oishi_ai_get_image_dimensions_from_url($src);
            if ($size['width'] <= 0 || $size['height'] <= 0) {
                $size = oishi_ai_get_attachment_dimensions_by_url($src);
            }
            if ($size['width'] > 0 && $size['height'] > 0) {
                if ($image->getAttribute('width') === '') {
                    $image->setAttribute('width', (string) $size['width']);
                }
                if ($image->getAttribute('height') === '') {
                    $image->setAttribute('height', (string) $size['height']);
                }
            }
        }

        if ($image->getAttribute('srcset') === '') {
            $srcset = oishi_ai_build_local_srcset($src);
            if ($srcset === '') {
                $srcset = oishi_ai_get_attachment_srcset_by_url($src);
            }
            if ($srcset === '') {
                $current_width = (int) $image->getAttribute('width');
                if ($current_width > 0) {
                    $srcset = esc_url($src) . ' ' . $current_width . 'w';
                }
            }
            if ($srcset !== '') {
                $image->setAttribute('srcset', $srcset);
                if ($image->getAttribute('sizes') === '') {
                    $image->setAttribute('sizes', '(max-width: 768px) 100vw, 768px');
                }
            }
        }
    }

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $dom->saveHTML($child);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous_use_errors);

    return $output;
}
add_filter('the_content', 'oishi_ai_optimize_content_images', 20);

function oishi_ai_collect_post_image_urls($post) {
    $post_content = (string) get_post_field('post_content', $post);
    $images       = array();

    if (has_post_thumbnail($post)) {
        $thumbnail = get_the_post_thumbnail_url($post, 'full');
        if (is_string($thumbnail) && $thumbnail !== '') {
            $images[] = oishi_ai_canonicalize_site_url(esc_url_raw($thumbnail));
        }
    }

    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post_content, $matches)) {
        foreach ($matches[1] as $src) {
            $clean_src = esc_url_raw($src);
            if ($clean_src !== '') {
                $images[] = oishi_ai_canonicalize_site_url($clean_src);
            }
        }
    }

    return array_values(array_unique($images));
}

function oishi_ai_serve_image_sitemap() {
    $request_uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($request_uri !== '/image-sitemap.xml') {
        return;
    }

    $posts = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'numberposts'    => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'suppress_filters' => false,
    ));

    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    foreach ($posts as $post) {
        $post_url = get_permalink($post);
        if (!is_string($post_url) || $post_url === '') {
            continue;
        }

        $images = oishi_ai_collect_post_image_urls($post);
        if (empty($images)) {
            continue;
        }

        echo "  <url>\n";
        echo '    <loc>' . esc_xml($post_url) . "</loc>\n";
        foreach ($images as $image_url) {
            echo "    <image:image>\n";
            echo '      <image:loc>' . esc_xml($image_url) . "</image:loc>\n";
            echo "    </image:image>\n";
        }
        echo "  </url>\n";
    }

    echo "</urlset>\n";
    exit;
}
add_action('template_redirect', 'oishi_ai_serve_image_sitemap', 0);

function oishi_ai_add_image_sitemap_to_robots($output, $public) {
    if ((int) $public !== 1) {
        return $output;
    }

    $line = 'Sitemap: ' . esc_url_raw(home_url('/image-sitemap.xml'));
    if (strpos($output, $line) !== false) {
        return $output;
    }

    return rtrim($output) . "\n" . $line . "\n";
}
add_filter('robots_txt', 'oishi_ai_add_image_sitemap_to_robots', 10, 2);

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

// ===== Performance: preconnect hints =====
function oishi_ai_preconnect_hints() {
    echo '<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://www.google-analytics.com" crossorigin>' . "\n";
}
add_action('wp_head', 'oishi_ai_preconnect_hints', 0);

// ===== Performance: hero image preload (front page only) =====
function oishi_ai_preload_hero_image() {
    if (!is_front_page()) {
        return;
    }
    $uri = get_template_directory_uri();
    $imagesrcset = esc_attr(
        $uri . '/hero-bg-480.webp 480w, ' .
        $uri . '/hero-bg-800.webp 800w, ' .
        $uri . '/hero-bg-1200.webp 1200w, ' .
        $uri . '/hero-bg-1600.webp 1600w'
    );
    echo '<link rel="preload" as="image" imagesrcset="' . $imagesrcset . '" imagesizes="100vw" fetchpriority="high">' . "\n";
}
add_action('wp_head', 'oishi_ai_preload_hero_image', 1);

// ===== Performance: critical CSS inline + async full CSS =====
function oishi_ai_critical_css() {
    $critical_path = get_template_directory() . '/critical.css';
    if (!file_exists($critical_path)) {
        return;
    }

    // Dequeue the normal style.css
    wp_dequeue_style('oishi-ai-style');
    wp_deregister_style('oishi-ai-style');

    // Inline critical CSS
    echo '<style>' . file_get_contents($critical_path) . '</style>' . "\n";

    // Async load full CSS
    $style_ver = (string) filemtime(get_stylesheet_directory() . '/style.css');
    $full_css_url = esc_url(get_stylesheet_uri() . '?ver=' . $style_ver);
    echo '<link rel="preload" href="' . $full_css_url . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
    echo '<noscript><link rel="stylesheet" href="' . $full_css_url . '"></noscript>' . "\n";
}
add_action('wp_enqueue_scripts', 'oishi_ai_critical_css', 200);

// ===== Google Analytics 4 (loaded in footer for performance) =====
function oishi_ai_ga4_tracking() {
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MLKGERC3FF"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-MLKGERC3FF');
    </script>
    <?php
}
add_action('wp_footer', 'oishi_ai_ga4_tracking');
