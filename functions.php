<?php
/**
 * Grant Insight Perfect - Unified Functions File (Complete Version)
 *
 * 8つの個別PHPファイルを1つに完全統合した統合functions.phpファイル
 * 完全版 - 全機能を一切省略せずに統合
 *
 * @package Grant_Insight_Perfect
 * @version 6.2.2
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// テーマバージョン定数
define('GI_THEME_VERSION', '6.2.2');
define('GI_THEME_PREFIX', 'gi_');

// =============================================================================
// 1. THEME SETUP (OPTIMIZED)
// =============================================================================

/**
 * テーマバージョン定数（未定義の場合のみ）
 */
if (!defined('GI_THEME_VERSION')) {
    define('GI_THEME_VERSION', wp_get_theme()->get('Version'));
}

/**
 * defer属性追加関数（重複回避）
 */
if (!function_exists('gi_add_defer_attribute')) {
    function gi_add_defer_attribute($tag, $handle, $src) {
        $defer_scripts = array(
            'gi-main-js',
            'gi-frontend-js',
            'ai-chatbot-js'
        );
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        return $tag;
    }
}

/**
 * テーマセットアップ
 */
function gi_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('custom-background');
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    add_theme_support('menus');
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('automatic-feed-links');
    
    // 画像サイズ追加（CLS対策：固定サイズ）
    add_image_size('gi-card-thumb', 400, 300, true);
    add_image_size('gi-hero-thumb', 800, 600, true);
    add_image_size('gi-tool-logo', 120, 120, true);
    add_image_size('gi-banner', 1200, 400, true);
    add_image_size('gi-logo-sm', 80, 80, true);
    
    // 言語ファイル読み込み
    load_theme_textdomain('grant-insight', get_template_directory() . '/languages');
    
    // メニュー登録
    register_nav_menus(array(
        'primary' => 'メインメニュー',
        'footer' => 'フッターメニュー',
        'mobile' => 'モバイルメニュー'
    ));
}
add_action('after_setup_theme', 'gi_setup');

/**
 * コンテンツ幅設定
 */
function gi_content_width() {
    $GLOBALS['content_width'] = apply_filters('gi_content_width', 1200);
}
add_action('after_setup_theme', 'gi_content_width', 0);

/**
 * 重複スクリプト削除（パフォーマンス最適化）
 */
function gi_remove_duplicate_scripts() {
    $duplicate_scripts = array(
        'jquery-ui-core',
        'jquery-ui-widget', 
        'jquery-ui-mouse',
        'jquery-effects-core'
    );
    
    foreach ($duplicate_scripts as $script) {
        if (wp_script_is($script, 'registered') && wp_script_is($script, 'enqueued')) {
            wp_dequeue_script($script);
        }
    }
    
    global $wp_scripts;
    $fontawesome_count = 0;
    if (isset($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($script->src, 'font-awesome') !== false || strpos($script->src, 'fontawesome') !== false) {
                $fontawesome_count++;
                if ($fontawesome_count > 1) {
                    wp_dequeue_script($handle);
                }
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'gi_remove_duplicate_scripts', 100);

/**
 * スクリプト・スタイルの読み込み（最適化版）
 */
function gi_enqueue_scripts() {
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', array(), '3.7.1', true);
    wp_enqueue_script('jquery');
    
    wp_enqueue_style('gi-optimized-css', get_template_directory_uri() . '/assets/css/optimized.css', array(), GI_THEME_VERSION);
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap', array(), null);
    
    $inline_css = "
        .gi-logo-container { 
            width: 80px; 
            height: 80px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .gi-logo-container img { 
            max-width: 100%; 
            height: auto; 
            width: auto;
        }
        .mobile-menu-overlay { 
            pointer-events: auto !important; 
        }
        .mobile-menu-toggle { 
            pointer-events: auto !important; 
            z-index: 9999; 
        }
    ";
    wp_add_inline_style('gi-optimized-css', $inline_css);
    
    wp_enqueue_style('gi-style', get_stylesheet_uri(), array('gi-optimized-css'), GI_THEME_VERSION);
    wp_enqueue_script('gi-main-js', get_template_directory_uri() . '/assets/js/main-optimized.js', array('jquery'), GI_THEME_VERSION, true);
    wp_enqueue_script('gi-mobile-menu', get_template_directory_uri() . '/assets/js/mobile-menu.js', array('jquery'), GI_THEME_VERSION, true);
    
    if (is_page_template('page-ai-chat.php')) {
        wp_enqueue_script('ai-chatbot-js', get_template_directory_uri() . '/assets/js/ai-chatbot.js', array('jquery'), GI_THEME_VERSION, true);
        wp_localize_script('ai-chatbot-js', 'ai_chat_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_chat_action'),
            'strings' => array(
                'sending' => '送信中...',
                'error' => 'エラーが発生しました',
                'clear_confirm' => '会話履歴をクリアしてもよろしいですか？'
            )
        ));
    }
    
    wp_localize_script('gi-main-js', 'gi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'homeUrl' => home_url('/'),
        'themeUrl' => get_template_directory_uri(),
        'uploadsUrl' => wp_upload_dir()['baseurl'],
        'isAdmin' => current_user_can('administrator'),
        'userId' => get_current_user_id(),
        'version' => GI_THEME_VERSION,
        'debug' => WP_DEBUG,
        'strings' => array(
            'loading' => '読み込み中...',
            'error' => 'エラーが発生しました',
            'noResults' => '結果が見つかりませんでした',
            'confirm' => '実行してもよろしいですか？'
        )
    ));
    
    if (is_singular()) {
        wp_enqueue_script('comment-reply');
    }
    
    if (is_front_page()) {
        wp_enqueue_script('gi-frontend-js', get_template_directory_uri() . '/assets/js/front-page.js', array('gi-main-js'), GI_THEME_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'gi_enqueue_scripts');

/**
 * deferとasync属性の追加（重複チェック付き）
 */
function gi_script_attributes($tag, $handle, $src) {
    if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
        return $tag;
    }
    
    if (function_exists('gi_add_defer_attribute') && $handle !== 'gi-main-js') {
        return gi_add_defer_attribute($tag, $handle, $src);
    }
    
    $defer_scripts = array(
        'gi-main-js',
        'gi-frontend-js',
        'gi-mobile-menu',
        'ai-chatbot-js'
    );
    
    $async_scripts = array(
        'google-fonts'
    );
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace('<script ', '<script defer ', $tag);
    }
    
    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'gi_script_attributes', 10, 3);

/**
 * プリロード設定（CLS対策）
 */
function gi_add_preload_links() {
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700&display=swap"></noscript>';
    
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'gi-logo-sm');
        if ($logo_url) {
            echo '<link rel="preload" href="' . esc_url($logo_url) . '" as="image">';
        }
    }
}
add_action('wp_head', 'gi_add_preload_links', 1);

/**
 * ウィジェットエリア登録
 */
function gi_widgets_init() {
    register_sidebar(array(
        'name'          => 'メインサイドバー',
        'id'            => 'sidebar-main',
        'description'   => 'メインサイドバーエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 pb-2 border-b-2 border-emerald-500">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア1',
        'id'            => 'footer-1',
        'description'   => 'フッター左側エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-white font-semibold mb-4">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア2',
        'id'            => 'footer-2',
        'description'   => 'フッター中央エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-white font-semibold mb-4">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア3',
        'id'            => 'footer-3',
        'description'   => 'フッター右側エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-white font-semibold mb-4">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'gi_widgets_init');

/**
 * カスタマイザー設定
 */
function gi_customize_register($wp_customize) {
    $wp_customize->add_section('gi_colors', array(
        'title' => 'サイトカラー',
        'priority' => 30,
    ));
    
    $wp_customize->add_setting('gi_primary_color', array(
        'default' => '#059669',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_primary_color', array(
        'label' => 'プライマリカラー',
        'section' => 'gi_colors',
    )));
    
    $wp_customize->add_section('gi_performance', array(
        'title' => 'パフォーマンス設定',
        'priority' => 35,
    ));
    
    $wp_customize->add_setting('gi_lazy_loading', array(
        'default' => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    
    $wp_customize->add_control('gi_lazy_loading', array(
        'label' => 'Lazy Loading を有効にする',
        'section' => 'gi_performance',
        'type' => 'checkbox',
    ));
}
add_action('customize_register', 'gi_customize_register');

/**
 * セキュリティ強化
 */
function gi_security_enhancements() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    add_filter('xmlrpc_enabled', '__return_false');
}
add_action('init', 'gi_security_enhancements');

/**
 * パフォーマンス最適化
 */
function gi_performance_optimizations() {
    if (!function_exists('gi_remove_query_strings')) {
        function gi_remove_query_strings($src) {
            $parts = explode('?ver', $src);
            return $parts[0];
        }
    }
    add_filter('script_loader_src', 'gi_remove_query_strings', 15, 1);
    add_filter('style_loader_src', 'gi_remove_query_strings', 15, 1);
    
    add_filter('get_avatar', function($avatar) {
        return str_replace('src=', 'loading="lazy" src=', $avatar);
    });
    
    add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
        if (empty($attr['width']) || empty($attr['height'])) {
            $image_meta = wp_get_attachment_metadata($attachment->ID);
            if (is_array($image_meta) && isset($image_meta['width'], $image_meta['height'])) {
                $attr['width'] = $image_meta['width'];
                $attr['height'] = $image_meta['height'];
            }
        }
        return $attr;
    }, 10, 3);
    
    add_action('wp_head', function() {
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
    }, 1);
}
add_action('init', 'gi_performance_optimizations');

/**
 * 画像最適化フック
 */
function gi_optimize_images() {
    add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
        if (!is_admin() && !wp_is_json_request()) {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }, 10, 3);
    
    add_filter('get_custom_logo', function($html) {
        if (empty($html)) return $html;
        $html = str_replace('<img ', '<img loading="eager" ', $html);
        return $html;
    });
}
add_action('init', 'gi_optimize_images');

/**
 * モバイルメニュー修正用CSS追加
 */
function gi_mobile_menu_fix() {
    $css = "
    <style>
    .mobile-menu-overlay {
        pointer-events: auto !important;
        touch-action: auto !important;
    }
    
    .mobile-menu-toggle,
    .mobile-menu-toggle * {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    .mobile-menu-container {
        pointer-events: auto !important;
    }
    
    .mobile-menu-container a,
    .mobile-menu-container button,
    .mobile-menu-container input {
        pointer-events: auto !important;
    }
    
    .site-header {
        min-height: 80px;
    }
    
    .gi-logo-container {
        width: 80px !important;
        height: 80px !important;
    }
    </style>
    ";
    echo $css;
}
add_action('wp_head', 'gi_mobile_menu_fix', 999);

/**
 * 緊急時のCSS/JS修正用フック
 */
function gi_emergency_fixes() {
    if (isset($_GET['gi_safe_mode']) && $_GET['gi_safe_mode'] === '1') {
        remove_action('wp_enqueue_scripts', 'gi_enqueue_scripts');
        wp_enqueue_style('gi-safe-mode', get_template_directory_uri() . '/assets/css/safe-mode.css', array(), GI_THEME_VERSION);
    }
}
add_action('wp_head', 'gi_emergency_fixes', 1);

/**
 * 管理画面での設定パネル追加
 */
function gi_admin_menu() {
    add_theme_page(
        'Grant Insight 設定',
        'テーマ設定',
        'manage_options',
        'gi-settings',
        'gi_settings_page'
    );
}
add_action('admin_menu', 'gi_admin_menu');

/**
 * 設定ページのHTML
 */
function gi_settings_page() {
    ?>
    <div class="wrap">
        <h1>Grant Insight Perfect 設定</h1>
        <div class="notice notice-info">
            <p><strong>パフォーマンス最適化が適用されました</strong></p>
            <ul>
                <li>✅ JavaScript重複削除</li>
                <li>✅ CLS（レイアウトシフト）対策</li>
                <li>✅ モバイルメニュー修正</li>
                <li>✅ 画像最適化</li>
                <li>✅ セキュリティ強化</li>
            </ul>
        </div>
        
        <h2>緊急時対応</h2>
        <p>問題が発生した場合は、以下のURLでセーフモードを有効化できます：</p>
        <code><?php echo home_url('/?gi_safe_mode=1'); ?></code>
        
        <h2>パフォーマンステスト</h2>
        <p>以下のツールでサイトの速度をテストしてください：</p>
        <ul>
            <li><a href="https://pagespeed.web.dev/" target="_blank">Google PageSpeed Insights</a></li>
            <li><a href="https://gtmetrix.com/" target="_blank">GTmetrix</a></li>
            <li><a href="https://webpagetest.org/" target="_blank">WebPageTest</a></li>
        </ul>
        
        <h2>開発者向け情報</h2>
        <div class="notice notice-success">
            <p><strong>テーマエディターは利用可能です</strong></p>
            <p>「外観 > テーマエディター」からファイルの編集が可能です。</p>
            <p>本番環境では、セキュリティのためFTPやSSHでの編集を推奨します。</p>
        </div>
    </div>
    <?php
}

if (WP_DEBUG) {
    error_log('Grant Insight Perfect theme setup completed - Version: ' . GI_THEME_VERSION);
}

// =============================================================================
// 2. POST TYPES & TAXONOMIES
// =============================================================================

/**
 * カスタム投稿タイプ登録（完全版）
 */
function gi_register_post_types() {
    // 助成金投稿タイプ
    register_post_type('grant', array(
        'labels' => array(
            'name' => '助成金・補助金',
            'singular_name' => '助成金・補助金',
            'add_new' => '新規追加',
            'add_new_item' => '新しい助成金・補助金を追加',
            'edit_item' => '助成金・補助金を編集',
            'new_item' => '新しい助成金・補助金',
            'view_item' => '助成金・補助金を表示',
            'search_items' => '助成金・補助金を検索',
            'not_found' => '助成金・補助金が見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱に助成金・補助金はありません',
            'all_items' => 'すべての助成金・補助金',
            'menu_name' => '助成金・補助金'
        ),
        'description' => '助成金・補助金情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grants',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-money-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // ツール投稿タイプ
    register_post_type('tool', array(
        'labels' => array(
            'name' => 'ビジネスツール',
            'singular_name' => 'ビジネスツール',
            'add_new' => '新規追加',
            'add_new_item' => '新しいツールを追加',
            'edit_item' => 'ツールを編集',
            'new_item' => '新しいツール',
            'view_item' => 'ツールを表示',
            'search_items' => 'ツールを検索',
            'not_found' => 'ツールが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にツールはありません',
            'all_items' => 'すべてのツール',
            'menu_name' => 'ビジネスツール'
        ),
        'description' => 'ビジネスツール情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'tools',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-admin-tools',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // 成功事例投稿タイプ
    register_post_type('case_study', array(
        'labels' => array(
            'name' => '成功事例',
            'singular_name' => '成功事例',
            'add_new' => '新規追加',
            'add_new_item' => '新しい成功事例を追加',
            'edit_item' => '成功事例を編集',
            'new_item' => '新しい成功事例',
            'view_item' => '成功事例を表示',
            'search_items' => '成功事例を検索',
            'not_found' => '成功事例が見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱に成功事例はありません',
            'all_items' => 'すべての成功事例',
            'menu_name' => '成功事例'
        ),
        'description' => '成功事例情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'case-studies',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 7,
        'menu_icon' => 'dashicons-chart-line',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // ガイド投稿タイプ
    register_post_type('guide', array(
        'labels' => array(
            'name' => 'ガイド・解説',
            'singular_name' => 'ガイド・解説',
            'add_new' => '新規追加',
            'add_new_item' => '新しいガイドを追加',
            'edit_item' => 'ガイドを編集',
            'new_item' => '新しいガイド',
            'view_item' => 'ガイドを表示',
            'search_items' => 'ガイドを検索',
            'not_found' => 'ガイドが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にガイドはありません',
            'all_items' => 'すべてのガイド',
            'menu_name' => 'ガイド・解説'
        ),
        'description' => 'ガイド・解説情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'guides',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 8,
        'menu_icon' => 'dashicons-book-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // 申請のコツ投稿タイプ
    register_post_type('grant_tip', array(
        'labels' => array(
            'name' => '申請のコツ',
            'singular_name' => '申請のコツ',
            'add_new' => '新規追加',
            'add_new_item' => '新しいコツを追加',
            'edit_item' => 'コツを編集',
            'new_item' => '新しいコツ',
            'view_item' => 'コツを表示',
            'search_items' => 'コツを検索',
            'not_found' => 'コツが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にコツはありません',
            'all_items' => 'すべてのコツ',
            'menu_name' => '申請のコツ'
        ),
        'description' => '申請のコツ情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tips',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 9,
        'menu_icon' => 'dashicons-lightbulb',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
}
add_action('init', 'gi_register_post_types');

/**
 * カスタムタクソノミー登録（完全版・都道府県対応・修正版）
 */
function gi_register_taxonomies() {
    // 助成金カテゴリー
    register_taxonomy('grant_category', 'grant', array(
        'labels' => array(
            'name' => '助成金カテゴリー',
            'singular_name' => '助成金カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '助成金・補助金をカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
    
    // 都道府県タクソノミー
    register_taxonomy('grant_prefecture', 'grant', array(
        'labels' => array(
            'name' => '対象都道府県',
            'singular_name' => '都道府県',
            'search_items' => '都道府県を検索',
            'all_items' => 'すべての都道府県',
            'edit_item' => '都道府県を編集',
            'update_item' => '都道府県を更新',
            'add_new_item' => '新しい都道府県を追加',
            'new_item_name' => '新しい都道府県名'
        ),
        'description' => '助成金・補助金の対象都道府県を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'prefecture',
            'with_front' => false
        )
    ));
    
    // 助成金タグ
    register_taxonomy('grant_tag', 'grant', array(
        'labels' => array(
            'name' => '助成金タグ',
            'singular_name' => '助成金タグ',
            'search_items' => 'タグを検索',
            'all_items' => 'すべてのタグ',
            'edit_item' => 'タグを編集',
            'update_item' => 'タグを更新',
            'add_new_item' => '新しいタグを追加',
            'new_item_name' => '新しいタグ名'
        ),
        'description' => '助成金・補助金をタグで分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tag',
            'with_front' => false
        )
    ));
    
    // ツールカテゴリー
    register_taxonomy('tool_category', 'tool', array(
        'labels' => array(
            'name' => 'ツールカテゴリー',
            'singular_name' => 'ツールカテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => 'ビジネスツールをカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'tool-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
    
    // 成功事例カテゴリー
    register_taxonomy('case_study_category', 'case_study', array(
        'labels' => array(
            'name' => '成功事例カテゴリー',
            'singular_name' => '成功事例カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '成功事例をカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'case-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));

    // 【修正】申請のコツカテゴリー（不足していたタクソノミー）
    register_taxonomy('grant_tip_category', 'grant_tip', array(
        'labels' => array(
            'name' => '申請のコツカテゴリー',
            'singular_name' => '申請のコツカテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '申請のコツをカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tip-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
}
add_action('init', 'gi_register_taxonomies');

// =============================================================================
// 3. AJAX FUNCTIONS (Complete Version)
// =============================================================================

/**
 * 【完全修正版】AJAX - 助成金読み込み処理（グリッド表示修正版）
 */
function gi_ajax_load_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }

    $search = sanitize_text_field(urldecode($_POST['search'] ?? ''));
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true);
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true);
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true);
    $difficulty = json_decode(stripslashes($_POST['difficulty'] ?? '[]'), true);
    $success_rate = json_decode(stripslashes($_POST['success_rate'] ?? '[]'), true);
    
    if (!empty($categories)) {
        $categories = array_map('urldecode', $categories);
    }
    if (!empty($prefectures)) {
        $prefectures = array_map('urldecode', $prefectures);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('AJAX Load Grants - Decoded params:');
        error_log('Search: ' . $search);
        error_log('Categories: ' . print_r($categories, true));
        error_log('Prefectures: ' . print_r($prefectures, true));
    }
    
    if (!is_array($categories)) $categories = [];
    if (!is_array($prefectures)) $prefectures = [];
    if (!is_array($status)) $status = [];
    if (!is_array($difficulty)) $difficulty = [];
    if (!is_array($success_rate)) $success_rate = [];
    
    if (is_array($status)) {
        $status = array_map(function($s) { 
            return $s === 'active' ? 'open' : ($s === 'upcoming' ? 'upcoming' : $s); 
        }, $status);
    }
    
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = max(1, intval($_POST['page'] ?? 1));
    $posts_per_page = 12;

    $args = array(
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish'
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $tax_query = array('relation' => 'AND');
    if (!empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_category', 
            'field' => 'slug', 
            'terms' => $categories,
            'operator' => 'IN'
        );
    }
    if (!empty($prefectures)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_prefecture', 
            'field' => 'slug', 
            'terms' => $prefectures,
            'operator' => 'IN'
        );
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $meta_query = array('relation' => 'AND');

    if (!empty($status)) {
        $meta_query[] = array('key' => 'application_status', 'value' => $status, 'compare' => 'IN');
    }
    
    if (!empty($difficulty)) {
        $meta_query[] = array('key' => 'grant_difficulty', 'value' => $difficulty, 'compare' => 'IN');
    }
    
    if (!empty($success_rate)) {
        $rate_query = array('relation' => 'OR');
        if (in_array('high', $success_rate, true)) {
            $rate_query[] = array('key' => 'grant_success_rate', 'value' => 70, 'compare' => '>=', 'type' => 'NUMERIC');
        }
        if (in_array('medium', $success_rate, true)) {
            $rate_query[] = array('key' => 'grant_success_rate', 'value' => array(50, 69), 'compare' => 'BETWEEN', 'type' => 'NUMERIC');
        }
        if (in_array('low', $success_rate, true)) {
            $rate_query[] = array('key' => 'grant_success_rate', 'value' => 50, 'compare' => '<', 'type' => 'NUMERIC');
        }
        if(count($rate_query) > 1) {
            $meta_query[] = $rate_query;
        }
    }

    if (!empty($amount)) {
        switch ($amount) {
            case '0-100': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => 1000000, 'compare' => '<=', 'type' => 'NUMERIC'); 
                break;
            case '100-500': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => array(1000001, 5000000), 'compare' => 'BETWEEN', 'type' => 'NUMERIC'); 
                break;
            case '500-1000': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => array(5000001, 10000000), 'compare' => 'BETWEEN', 'type' => 'NUMERIC'); 
                break;
            case '1000-3000': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => array(10000001, 30000000), 'compare' => 'BETWEEN', 'type' => 'NUMERIC'); 
                break;
            case '3000+': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => 30000000, 'compare' => '>=', 'type' => 'NUMERIC'); 
                break;
            case '1000+': 
                $meta_query[] = array('key' => 'max_amount_numeric', 'value' => 10000000, 'compare' => '>=', 'type' => 'NUMERIC'); 
                break;
        }
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    switch ($sort) {
        case 'date_asc': 
            $args['orderby'] = 'date'; 
            $args['order'] = 'ASC'; 
            break;
        case 'amount_desc': 
            $args['orderby'] = 'meta_value_num'; 
            $args['meta_key'] = 'max_amount_numeric'; 
            $args['order'] = 'DESC'; 
            break;
        case 'amount_asc': 
            $args['orderby'] = 'meta_value_num'; 
            $args['meta_key'] = 'max_amount_numeric'; 
            $args['order'] = 'ASC'; 
            break;
        case 'deadline_asc': 
            $args['orderby'] = 'meta_value_num'; 
            $args['meta_key'] = 'deadline_date'; 
            $args['order'] = 'ASC'; 
            break;
        case 'success_rate_desc': 
            $args['orderby'] = 'meta_value_num'; 
            $args['meta_key'] = 'grant_success_rate'; 
            $args['order'] = 'DESC'; 
            break;
        case 'title_asc': 
            $args['orderby'] = 'title'; 
            $args['order'] = 'ASC'; 
            break;
        default: 
            $args['orderby'] = 'date'; 
            $args['order'] = 'DESC';
    }

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) {
        if ($view === 'grid') {
            echo '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">';
        } else {
            echo '<div class="space-y-4">';
        }
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $grant_terms = get_the_terms($post_id, 'grant_category');
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            
            $grant_data = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
                'main_category' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? $grant_terms[0]->name : '',
                'prefecture' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? $prefecture_terms[0]->name : '',
                'organization' => gi_safe_get_meta($post_id, 'organization', ''),
                'deadline' => function_exists('gi_get_formatted_deadline') ? gi_get_formatted_deadline($post_id) : gi_safe_get_meta($post_id, 'deadline_date', ''),
                'amount' => gi_safe_get_meta($post_id, 'max_amount', '-'),
                'amount_numeric' => gi_safe_get_meta($post_id, 'max_amount_numeric', 0),
                'deadline_timestamp' => gi_safe_get_meta($post_id, 'deadline_date', ''),
                'status' => function_exists('gi_map_application_status_ui') ? gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')) : gi_safe_get_meta($post_id, 'application_status', 'open'),
                'difficulty' => gi_safe_get_meta($post_id, 'grant_difficulty', ''),
                'success_rate' => gi_safe_get_meta($post_id, 'grant_success_rate', 0),
                'subsidy_rate' => gi_safe_get_meta($post_id, 'subsidy_rate', ''),
                'target_business' => gi_safe_get_meta($post_id, 'target_business', ''),
            );
            
            if ($view === 'grid') {
                echo gi_render_modern_grant_card($grant_data);
            } else {
                echo gi_render_modern_grant_list_card($grant_data);
            }
        }
        
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<div class="text-center py-12">';
        echo '<div class="text-gray-500 dark:text-gray-400">該当する助成金が見つかりませんでした。</div>';
        echo '</div>';
    }
    
    $html = ob_get_clean();

    $pagination_html = '';
    if ($query->max_num_pages > 1) {
        ob_start();
        echo '<div class="flex items-center justify-center space-x-2 mt-8">';
        
        if ($page > 1) {
            echo '<button class="pagination-btn px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="' . ($page - 1) . '">';
            echo '<i class="fas fa-chevron-left mr-1"></i>前へ';
            echo '</button>';
        }
        
        $start = max(1, $page - 2);
        $end = min($query->max_num_pages, $page + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active_class = ($i === $page) ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
            echo '<button class="pagination-btn px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg transition-colors ' . $active_class . '" data-page="' . $i . '">';
            echo $i;
            echo '</button>';
        }
        
        if ($page < $query->max_num_pages) {
            echo '<button class="pagination-btn px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="' . ($page + 1) . '">';
            echo '次へ<i class="fas fa-chevron-right ml-1"></i>';
            echo '</button>';
        }
        
        echo '</div>';
        $pagination_html = ob_get_clean();
    }

    wp_send_json_success(array(
        'html' => $html,
        'found_posts' => $query->found_posts,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_posts' => $query->found_posts,
            'posts_per_page' => $posts_per_page,
            'html' => $pagination_html
        ),
        'query_info' => compact('search', 'categories', 'prefectures', 'amount', 'status', 'difficulty', 'success_rate', 'sort'),
        'view' => $view
    ));
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

/**
 * モダンなカードデザイン生成関数（グリッド表示用・レスポンシブ対応）
 */
function gi_render_modern_grant_card($grant_data) {
    $post_id = $grant_data['id'];
    $title = esc_html($grant_data['title']);
    $permalink = esc_url($grant_data['permalink']);
    $excerpt = esc_html($grant_data['excerpt']);
    $organization = esc_html($grant_data['organization']);
    
    $amount = function_exists('gi_format_amount_with_unit') ? 
              gi_format_amount_with_unit($grant_data['amount_numeric'] ?: $grant_data['amount']) : 
              esc_html($grant_data['amount']);
    
    $deadline = esc_html($grant_data['deadline']);
    $status = esc_html($grant_data['status']);
    $prefecture = esc_html($grant_data['prefecture']);
    $category = esc_html($grant_data['main_category']);
    $success_rate = intval($grant_data['success_rate']);
    $difficulty = esc_html($grant_data['difficulty']);
    
    $status_bg = '';
    $status_bg_dark = '';
    $status_text = '';
    switch($status) {
        case '募集中':
        case 'active':
            $status_bg = 'bg-emerald-50';
            $status_bg_dark = 'dark:bg-emerald-900/30';
            $status_text = 'text-emerald-700 dark:text-emerald-400';
            break;
        case '準備中':
        case 'upcoming':
            $status_bg = 'bg-blue-50';
            $status_bg_dark = 'dark:bg-blue-900/30';
            $status_text = 'text-blue-700 dark:text-blue-400';
            break;
        case '終了':
        case 'closed':
            $status_bg = 'bg-gray-50';
            $status_bg_dark = 'dark:bg-gray-800';
            $status_text = 'text-gray-700 dark:text-gray-400';
            break;
        default:
            $status_bg = 'bg-gray-50';
            $status_bg_dark = 'dark:bg-gray-800';
            $status_text = 'text-gray-700 dark:text-gray-400';
    }
    
    $difficulty_display = '';
    switch($difficulty) {
        case 'easy':
            $difficulty_display = '<span class="text-green-600 dark:text-green-400">★</span>';
            break;
        case 'normal':
            $difficulty_display = '<span class="text-yellow-600 dark:text-yellow-400">★★</span>';
            break;
        case 'hard':
            $difficulty_display = '<span class="text-red-600 dark:text-red-400">★★★</span>';
            break;
        default:
            $difficulty_display = '<span class="text-gray-400 dark:text-gray-600">-</span>';
    }
    
    $success_color = '';
    if ($success_rate >= 70) {
        $success_color = 'text-green-600 dark:text-green-400';
    } elseif ($success_rate >= 50) {
        $success_color = 'text-yellow-600 dark:text-yellow-400';
    } else {
        $success_color = 'text-red-600 dark:text-red-400';
    }
    
    return <<<HTML
<div class="grant-card-modern w-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col">
        
        <div class="px-4 pt-4 pb-3">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {$status_bg} {$status_bg_dark} {$status_text}">
                    <span class="w-1.5 h-1.5 bg-current rounded-full mr-1.5"></span>
                    {$status}
                </span>
                <button class="favorite-btn text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors p-1" data-post-id="{$post_id}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </button>
            </div>
            
            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight line-clamp-2">
                <a href="{$permalink}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                    {$title}
                </a>
            </h3>
        </div>
        
        <div class="px-4 pb-3 flex-grow">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                    {$category}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    📍 {$prefecture}
                </span>
            </div>
            
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-lg p-3 mb-3 border border-emerald-100 dark:border-emerald-800">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">最大助成額</div>
                <div class="text-xl font-bold text-emerald-700 dark:text-emerald-400">
                    {$amount}
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                    <div class="text-gray-500 dark:text-gray-400 mb-0.5">締切</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate">{$deadline}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                    <div class="text-gray-500 dark:text-gray-400 mb-0.5">採択率</div>
                    <div class="font-medium {$success_color}">{$success_rate}%</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                    <div class="text-gray-500 dark:text-gray-400 mb-0.5">難易度</div>
                    <div class="font-medium">{$difficulty_display}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                    <div class="text-gray-500 dark:text-gray-400 mb-0.5">実施機関</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate" title="{$organization}">{$organization}</div>
                </div>
            </div>
        </div>
        
        <div class="px-4 pb-4 pt-3 border-t border-gray-100 dark:border-gray-700 mt-auto">
            <div class="flex items-center justify-between">
                <a href="{$permalink}" class="flex-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-center py-2 px-4 rounded-lg transition-all duration-200 text-sm font-medium shadow-sm hover:shadow">
                    詳細を見る
                </a>
                <button class="ml-2 p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors share-btn" data-url="{$permalink}" data-title="{$title}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 01-7.432 0"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
HTML;
}

/**
 * モダンなカードデザイン生成関数（リスト表示用・ダークモード対応）
 */
function gi_render_modern_grant_list_card($grant_data) {
    $post_id = $grant_data['id'];
    $title = esc_html($grant_data['title']);
    $permalink = esc_url($grant_data['permalink']);
    $excerpt = esc_html($grant_data['excerpt']);
    $organization = esc_html($grant_data['organization']);
    
    $amount = function_exists('gi_format_amount_with_unit') ? 
              gi_format_amount_with_unit($grant_data['amount_numeric'] ?: $grant_data['amount']) : 
              esc_html($grant_data['amount']);
    
    $deadline = esc_html($grant_data['deadline']);
    $status = esc_html($grant_data['status']);
    $prefecture = esc_html($grant_data['prefecture']);
    $category = esc_html($grant_data['main_category']);
    $success_rate = intval($grant_data['success_rate']);
    $difficulty = esc_html($grant_data['difficulty']);
    
    $status_bg = '';
    $status_bg_dark = '';
    $status_text = '';
    switch($status) {
        case '募集中':
        case 'active':
            $status_bg = 'bg-emerald-50';
            $status_bg_dark = 'dark:bg-emerald-900/30';
            $status_text = 'text-emerald-700 dark:text-emerald-400';
            break;
        case '準備中':
        case 'upcoming':
            $status_bg = 'bg-blue-50';
            $status_bg_dark = 'dark:bg-blue-900/30';
            $status_text = 'text-blue-700 dark:text-blue-400';
            break;
        case '終了':
        case 'closed':
            $status_bg = 'bg-gray-50';
            $status_bg_dark = 'dark:bg-gray-800';
            $status_text = 'text-gray-700 dark:text-gray-400';
            break;
        default:
            $status_bg = 'bg-gray-50';
            $status_bg_dark = 'dark:bg-gray-800';
            $status_text = 'text-gray-700 dark:text-gray-400';
    }
    
    $success_color_class = ($success_rate >= 70) ? 'text-green-600 dark:text-green-400' : 
                           (($success_rate >= 50) ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
    
    return <<<HTML
<div class="grant-list-modern bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden mb-4" style="height: 180px;">
    <div class="p-4 h-full flex">
        <div class="flex-grow pr-4" style="flex: 1 1 70%;">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {$status_bg} {$status_bg_dark} {$status_text}">
                        <span class="w-1.5 h-1.5 bg-current rounded-full mr-1.5"></span>
                        {$status}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                        {$category}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        📍 {$prefecture}
                    </span>
                </div>
            </div>
            
            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2 leading-tight" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                <a href="{$permalink}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                    {$title}
                </a>
            </h3>
            
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                {$excerpt}
            </p>
            
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    {$organization}
                </span>
                <span class="flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    締切: {$deadline}
                </span>
                <span class="flex items-center">
                    採択率: <span class="ml-1 font-semibold {$success_color_class}">{$success_rate}%</span>
                </span>
            </div>
        </div>
        
        <div class="flex flex-col items-end justify-between pl-4 border-l border-gray-100 dark:border-gray-700" style="flex: 0 0 200px;">
            <div class="text-right mb-3">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">最大助成額</div>
                <div class="text-xl font-bold text-emerald-700 dark:text-emerald-400">
                    {$amount}
                </div>
            </div>
            
            <div class="flex flex-col gap-2 w-full">
                <a href="{$permalink}" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-center py-2 px-4 rounded-lg transition-all duration-200 text-sm font-medium shadow-sm hover:shadow">
                    詳細を見る
                </a>
                <div class="flex gap-2">
                    <button type="button" 
                            class="flex-1 p-2 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors favorite-btn" 
                            data-post-id="{$post_id}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </button>
                    <button type="button" 
                            class="flex-1 p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors share-btn" 
                            data-url="{$permalink}" 
                            data-title="{$title}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 01-7.432 0"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
}

/**
 * AJAX - Search suggestions（ヘルパー関数使用）
 */
function gi_ajax_get_search_suggestions() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $query = sanitize_text_field($_POST['query'] ?? '');
    $suggestions = array();
    if ($query !== '') {
        $args = array(
            's' => $query,
            'post_type' => array('grant','tool','case_study','guide','grant_tip'),
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'fields' => 'ids'
        );
        $posts = get_posts($args);
        foreach ($posts as $pid) {
            $suggestions[] = array(
                'label' => get_the_title($pid),
                'value' => get_the_title($pid),
                'url' => function_exists('gi_safe_url') ? gi_safe_url(get_permalink($pid)) : get_permalink($pid),
                'type' => get_post_type($pid)
            );
        }
    }
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');

/**
 * AJAX - Advanced search（ヘルパー関数使用）
 */
function gi_ajax_advanced_search() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $keyword = sanitize_text_field($_POST['search_query'] ?? ($_POST['s'] ?? ''));
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? '');

    $tax_query = array('relation' => 'AND');
    if ($prefecture) {
        $tax_query[] = array('taxonomy'=>'grant_prefecture','field'=>'slug','terms'=>array($prefecture),'operator'=>'IN');
    }
    if ($category) {
        $tax_query[] = array('taxonomy'=>'grant_category','field'=>'slug','terms'=>array($category),'operator'=>'IN');
    }

    $meta_query = array('relation' => 'AND');
    if ($amount) {
        switch ($amount) {
            case '0-100':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>1000000,'compare'=>'<=','type'=>'NUMERIC');
                break;
            case '100-500':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(1000000,5000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '500-1000':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(5000000,10000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '1000+':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>10000000,'compare'=>'>=','type'=>'NUMERIC');
                break;
        }
    }
    if ($status) {
        $status = $status === 'active' ? 'open' : $status;
        $meta_query[] = array('key'=>'application_status','value'=>array($status),'compare'=>'IN');
    }

    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        's' => $keyword,
    );
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;
    if (count($meta_query) > 1) $args['meta_query'] = $meta_query;

    $q = new WP_Query($args);
    $html = '';
    if ($q->have_posts()) {
        ob_start();
        echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
        while ($q->have_posts()) { 
            $q->the_post();
            $post_id = get_the_ID();
            
            $grant_terms = get_the_terms($post_id, 'grant_category');
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            
            $grant_data = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
                'main_category' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? $grant_terms[0]->name : '',
                'prefecture' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? $prefecture_terms[0]->name : '',
                'organization' => gi_safe_get_meta($post_id, 'organization', ''),
                'deadline' => function_exists('gi_get_formatted_deadline') ? gi_get_formatted_deadline($post_id) : gi_safe_get_meta($post_id, 'deadline_date', ''),
                'amount' => gi_safe_get_meta($post_id, 'max_amount', '-'),
                'amount_numeric' => gi_safe_get_meta($post_id, 'max_amount_numeric', 0),
                'deadline_timestamp' => gi_safe_get_meta($post_id, 'deadline_date', ''),
                'status' => function_exists('gi_map_application_status_ui') ? gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')) : gi_safe_get_meta($post_id, 'application_status', 'open'),
                'difficulty' => gi_safe_get_meta($post_id, 'grant_difficulty', ''),
                'success_rate' => gi_safe_get_meta($post_id, 'grant_success_rate', 0),
                'subsidy_rate' => gi_safe_get_meta($post_id, 'subsidy_rate', ''),
                'target_business' => gi_safe_get_meta($post_id, 'target_business', ''),
            );
            
            echo gi_render_modern_grant_card($grant_data);
        }
        echo '</div>';
        $html = ob_get_clean();
        wp_reset_postdata();
    }
    wp_send_json_success(array(
        'html' => $html ?: '<p class="text-gray-500 dark:text-gray-400 text-center py-8">該当する助成金が見つかりませんでした。</p>',
        'count' => $q->found_posts
    ));
}
add_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
add_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');

/**
 * AJAX - Grant Insight top page search（ヘルパー関数使用）
 */
function gi_ajax_grant_insight_search() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    $keyword    = sanitize_text_field($_POST['keyword'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? '');
    $orderby    = sanitize_text_field($_POST['orderby'] ?? 'relevance');
    $category   = sanitize_text_field($_POST['category'] ?? '');
    $amount_min = isset($_POST['amount_min']) ? intval($_POST['amount_min']) : 0;
    $amount_max = isset($_POST['amount_max']) ? intval($_POST['amount_max']) : 0;
    $deadline   = sanitize_text_field($_POST['deadline'] ?? '');
    $page       = max(1, intval($_POST['page'] ?? 1));
    $per_page = 12;

    $post_types = array('grant','tool','case_study','guide','grant_tip');
    if (!empty($post_type)) {
        $post_types = array($post_type);
    }

    $args = array(
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        's'              => $keyword,
        'paged'          => $page,
        'posts_per_page' => $per_page,
    );

    switch ($orderby) {
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'modified':
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
            break;
        default:
            $args['orderby'] = 'relevance';
            $args['order']   = 'DESC';
            break;
    }

    $tax_query = array('relation' => 'AND');
    if (!empty($category)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field'    => 'term_id',
            'terms'    => array(intval($category)),
        );
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $meta_query = array('relation' => 'AND');
    if (in_array('grant', $post_types, true) || $post_type === 'grant') {
        if ($amount_min > 0 || $amount_max > 0) {
            $meta_query[] = array(
                'key'     => 'max_amount_numeric',
                'value'   => $amount_max > 0 && $amount_min > 0 ? array($amount_min, $amount_max) : ($amount_max > 0 ? $amount_max : $amount_min),
                'compare' => ($amount_min > 0 && $amount_max > 0) ? 'BETWEEN' : ($amount_max > 0 ? '<=' : '>='),
                'type'    => 'NUMERIC',
            );
        }

        if (!empty($deadline)) {
            $todayYmd = intval(current_time('Ymd'));
            $targetYmd = $todayYmd;
            switch ($deadline) {
                case '1month':
                    $targetYmd = intval(date('Ymd', strtotime('+1 month', current_time('timestamp'))));
                    break;
                case '3months':
                    $targetYmd = intval(date('Ymd', strtotime('+3 months', current_time('timestamp'))));
                    break;
                case '6months':
                    $targetYmd = intval(date('Ymd', strtotime('+6 months', current_time('timestamp'))));
                    break;
                case '1year':
                    $targetYmd = intval(date('Ymd', strtotime('+1 year', current_time('timestamp'))));
                    break;
            }
            $meta_query[] = array(
                'key'     => 'deadline_date',
                'value'   => array($todayYmd, $targetYmd),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        }
    }
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    $q = new WP_Query($args);

    $favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : array();
    $posts = array();
    if ($q->have_posts()) {
        while ($q->have_posts()) { 
            $q->the_post();
            $pid = get_the_ID();
            $ptype = get_post_type($pid);
            $amount_yen = ($ptype === 'grant') ? intval(gi_safe_get_meta($pid, 'max_amount_numeric', 0)) : 0;
            $deadline_date = ($ptype === 'grant') ? gi_safe_get_meta($pid, 'deadline_date', '') : '';

            $posts[] = array(
                'id'        => $pid,
                'title'     => get_the_title($pid),
                'excerpt'   => function_exists('gi_safe_excerpt') ? gi_safe_excerpt(get_the_excerpt($pid), 100) : wp_trim_words(get_the_excerpt($pid), 20),
                'permalink' => function_exists('gi_safe_url') ? gi_safe_url(get_permalink($pid)) : get_permalink($pid),
                'thumbnail' => get_the_post_thumbnail_url($pid, 'medium'),
                'date'      => function_exists('gi_safe_date_format') ? gi_safe_date_format(get_the_date('Y-m-d', $pid)) : get_the_date('Y-m-d', $pid),
                'post_type' => $ptype,
                'amount'    => $amount_yen,
                'amount_formatted' => function_exists('gi_format_amount_with_unit') ? gi_format_amount_with_unit($amount_yen) : number_format($amount_yen),
                'deadline'  => $deadline_date,
                'is_featured'=> false,
                'is_favorite'=> in_array($pid, $favorites, true),
            );
        }
        wp_reset_postdata();
    }

    $response = array(
        'posts' => $posts,
        'pagination' => array(
            'current_page' => $page,
            'total_pages'  => max(1, intval($q->max_num_pages)),
        ),
        'total' => intval($q->found_posts),
    );

    wp_send_json_success($response);
}
add_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
add_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');

/**
 * AJAX - Export search results as CSV（ヘルパー関数使用）
 */
function gi_ajax_grant_insight_export_results() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce') && !wp_verify_nonce($nonce, 'gi_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    $_POST['page'] = 1;
    $_POST['orderby'] = sanitize_text_field($_POST['orderby'] ?? 'date');

    $keyword   = sanitize_text_field($_POST['keyword'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? 'grant');
    $category  = sanitize_text_field($_POST['category'] ?? '');

    $args = array(
        'post_type'      => $post_type ? array($post_type) : array('grant'),
        'post_status'    => 'publish',
        's'              => $keyword,
        'posts_per_page' => 200, // cap export size
        'paged'          => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'grant_category',
                'field'    => 'term_id',
                'terms'    => array(intval($category)),
            )
        );
    }

    $q = new WP_Query($args);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="grant_search_results_' . date('Y-m-d') . '.csv"');
    $fp = fopen('php://output', 'w');
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

    fputcsv($fp, array('ID','Title','Permalink','Post Type','Date','Amount(yen)','Deadline','Organization'));
    if ($q->have_posts()) {
        while ($q->have_posts()) { 
            $q->the_post();
            $pid = get_the_ID();
            $ptype = get_post_type($pid);
            $amount_yen = ($ptype === 'grant') ? intval(gi_safe_get_meta($pid, 'max_amount_numeric', 0)) : 0;
            $deadline_date = ($ptype === 'grant') ? gi_safe_get_meta($pid, 'deadline_date', '') : '';
            $organization = ($ptype === 'grant') ? gi_safe_get_meta($pid, 'organization', '') : '';
            
            fputcsv($fp, array(
                $pid,
                get_the_title($pid),
                function_exists('gi_safe_url') ? gi_safe_url(get_permalink($pid)) : get_permalink($pid),
                $ptype,
                function_exists('gi_safe_date_format') ? gi_safe_date_format(get_the_date('Y-m-d', $pid)) : get_the_date('Y-m-d', $pid),
                function_exists('gi_format_amount_with_unit') ? gi_format_amount_with_unit($amount_yen) : number_format($amount_yen),
                function_exists('gi_safe_date_format') ? gi_safe_date_format($deadline_date, 'Y-m-d') : $deadline_date,
                $organization,
            ));
        }
        wp_reset_postdata();
    }
    fclose($fp);
    exit;
}
add_action('wp_ajax_grant_insight_export_results', 'gi_ajax_grant_insight_export_results');
add_action('wp_ajax_nopriv_grant_insight_export_results', 'gi_ajax_grant_insight_export_results');

/**
 * AJAX - Newsletter signup（ヘルパー関数使用）
 */
function gi_ajax_newsletter_signup() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $email = sanitize_email($_POST['email'] ?? '');
    if (!$email || !is_email($email)) {
        wp_send_json_error('メールアドレスが正しくありません');
    }
    $list = get_option('gi_newsletter_list', array());
    if (!is_array($list)) $list = array();
    if (!in_array($email, $list)) {
        $list[] = $email;
        update_option('gi_newsletter_list', $list);
    }
    wp_send_json_success(array(
        'message' => '登録しました',
        'email' => function_exists('gi_safe_escape') ? gi_safe_escape($email) : esc_html($email)
    ));
}
add_action('wp_ajax_newsletter_signup', 'gi_ajax_newsletter_signup');
add_action('wp_ajax_nopriv_newsletter_signup', 'gi_ajax_newsletter_signup');

/**
 * AJAX - Affiliate click tracking（ヘルパー関数使用）
 */
function gi_ajax_track_affiliate_click() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $url = function_exists('gi_safe_url') ? gi_safe_url($_POST['url'] ?? '') : esc_url($_POST['url'] ?? '');
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$url) wp_send_json_error('URLが無効です');
    
    $log = get_option('gi_affiliate_clicks', array());
    if (!is_array($log)) $log = array();
    $log[] = array(
        'time' => current_time('timestamp'), 
        'url' => $url, 
        'post_id' => $post_id, 
        'ip' => function_exists('gi_safe_escape') ? gi_safe_escape($_SERVER['REMOTE_ADDR'] ?? '') : esc_html($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => function_exists('gi_safe_escape') ? gi_safe_escape($_SERVER['HTTP_USER_AGENT'] ?? '') : esc_html($_SERVER['HTTP_USER_AGENT'] ?? '')
    );
    update_option('gi_affiliate_clicks', $log);
    wp_send_json_success(array('message' => 'ok'));
}
add_action('wp_ajax_track_affiliate_click', 'gi_ajax_track_affiliate_click');
add_action('wp_ajax_nopriv_track_affiliate_click', 'gi_ajax_track_affiliate_click');

/**
 * AJAX - Related grants (新カードデザイン対応・ヘルパー関数使用)
 */
function gi_ajax_get_related_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'get_related_grants_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $post_id = intval($_POST['post_id'] ?? 0);
    $category_name = sanitize_text_field($_POST['category'] ?? '');
    $prefecture_name = sanitize_text_field($_POST['prefecture'] ?? '');

    $tax_query = array('relation' => 'AND');
    if ($category_name) {
        $term = get_term_by('name', $category_name, 'grant_category');
        if ($term) {
            $tax_query[] = array('taxonomy'=>'grant_category','field'=>'term_id','terms'=>array($term->term_id));
        }
    }
    if ($prefecture_name) {
        $term = get_term_by('name', $prefecture_name, 'grant_prefecture');
        if ($term) {
            $tax_query[] = array('taxonomy'=>'grant_prefecture','field'=>'term_id','terms'=>array($term->term_id));
        }
    }

    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'post__not_in' => array($post_id),
    );
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;

    $q = new WP_Query($args);
    $html = '';
    if ($q->have_posts()) {
        ob_start();
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        while ($q->have_posts()) { 
            $q->the_post();
            $post_id = get_the_ID();
            
            $grant_terms = get_the_terms($post_id, 'grant_category');
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            
            $grant_data = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
                'main_category' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? $grant_terms[0]->name : '',
                'prefecture' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? $prefecture_terms[0]->name : '',
                'organization' => gi_safe_get_meta($post_id, 'organization', ''),
                'deadline' => function_exists('gi_get_formatted_deadline') ? gi_get_formatted_deadline($post_id) : gi_safe_get_meta($post_id, 'deadline_date', ''),
                'amount' => gi_safe_get_meta($post_id, 'max_amount', '-'),
                'amount_numeric' => gi_safe_get_meta($post_id, 'max_amount_numeric', 0),
                'deadline_timestamp' => gi_safe_get_meta($post_id, 'deadline_date', ''),
                'status' => function_exists('gi_map_application_status_ui') ? gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')) : gi_safe_get_meta($post_id, 'application_status', 'open'),
                'difficulty' => gi_safe_get_meta($post_id, 'grant_difficulty', ''),
                'success_rate' => gi_safe_get_meta($post_id, 'grant_success_rate', 0),
                'subsidy_rate' => gi_safe_get_meta($post_id, 'subsidy_rate', ''),
                'target_business' => gi_safe_get_meta($post_id, 'target_business', ''),
            );
            
            echo gi_render_modern_grant_card($grant_data);
        }
        echo '</div>';
        $html = ob_get_clean();
        wp_reset_postdata();
    }
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_get_related_grants', 'gi_ajax_get_related_grants');
add_action('wp_ajax_nopriv_get_related_grants', 'gi_ajax_get_related_grants');

/**
 * 【修正版】AJAX - お気に入り機能（新カードデザイン対応・ヘルパー関数使用）
 */
function gi_ajax_toggle_favorite() {
    $nonce_check1 = wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce');
    $nonce_check2 = wp_verify_nonce($_POST['nonce'] ?? '', 'grant_insight_search_nonce');
    
    if (!$nonce_check1 && !$nonce_check2) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();
    
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error('無効な投稿IDです');
    }
    
    if (!$user_id) {
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
            $icon_class = 'far';
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $icon_class = 'fas';
        }
        
        setcookie($cookie_name, implode(',', $favorites), time() + (86400 * 30), '/');
    } else {
        $favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites($user_id) : (get_user_meta($user_id, 'gi_favorites', true) ?: array());
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
            $icon_class = 'far';
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $icon_class = 'fas';
        }
        
        update_user_meta($user_id, 'gi_favorites', $favorites);
    }
    
    wp_send_json_success(array(
        'action' => $action,
        'post_id' => $post_id,
        'post_title' => function_exists('gi_safe_escape') ? gi_safe_escape(get_the_title($post_id)) : esc_html(get_the_title($post_id)),
        'count' => count($favorites),
        'is_favorite' => $action === 'added',
        'icon_class' => $icon_class,
        'message' => $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました'
    ));
}
add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_grant_insight_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_grant_insight_toggle_favorite', 'gi_ajax_toggle_favorite');

/**
 * AJAX - ビジネスツール読み込み処理（ダークモード対応）
 */
function gi_ajax_load_tools() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました。');
    }

    $search = sanitize_text_field($_POST['keyword'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $price_range = sanitize_text_field($_POST['price_range'] ?? '');
    $rating = sanitize_text_field($_POST['rating'] ?? '');
    $features = sanitize_text_field($_POST['features'] ?? '');
    $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'date');
    $sort_order = sanitize_text_field($_POST['sort_order'] ?? 'DESC');
    $posts_per_page = intval($_POST['posts_per_page'] ?? 12);
    $page = intval($_POST['page'] ?? 1);

    $args = array(
        'post_type' => 'tool',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'tool_category',
                'field' => 'slug',
                'terms' => $category,
            ),
        );
    }

    $meta_query = array('relation' => 'AND');
    
    if (!empty($price_range)) {
        switch ($price_range) {
            case 'free':
                $meta_query[] = array(
                    'key' => 'price_free',
                    'value' => '1',
                    'compare' => '='
                );
                break;
            case '0-5000':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => 5000,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                );
                break;
            case '5001-20000':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => array(5001, 20000),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
                break;
            case '20001':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => 20001,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                );
                break;
        }
    }

    if (!empty($rating)) {
        $meta_query[] = array(
            'key' => 'rating',
            'value' => floatval($rating),
            'compare' => '>=',
            'type' => 'DECIMAL'
        );
    }

    if (!empty($features)) {
        $meta_query[] = array(
            'key' => 'features',
            'value' => $features,
            'compare' => 'LIKE'
        );
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    switch ($sort_by) {
        case 'title':
            $args['orderby'] = 'title';
            break;
        case 'rating':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'rating';
            break;
        case 'price':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'price_monthly';
            break;
        case 'views':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'view_count';
            break;
        default:
            $args['orderby'] = 'date';
            break;
    }
    $args['order'] = $sort_order;

    $query = new WP_Query($args);
    $tools = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $tools[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => function_exists('gi_safe_url') ? gi_safe_url(get_permalink()) : get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => function_exists('gi_safe_excerpt') ? gi_safe_excerpt(get_the_excerpt(), 80) : wp_trim_words(get_the_excerpt(), 15),
                'rating' => gi_safe_get_meta($post_id, 'rating', '4.5'),
                'price' => gi_safe_get_meta($post_id, 'price_monthly', '無料'),
                'price_free' => gi_safe_get_meta($post_id, 'price_free', '0'),
            );
        }
    }
    wp_reset_postdata();

    ob_start();
    if (!empty($tools)) {
        echo '<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">';
        foreach ($tools as $tool) {
            $price_display = $tool['price_free'] === '1' ? '無料プランあり' : '¥' . (function_exists('gi_safe_number_format') ? gi_safe_number_format(intval($tool['price'])) : number_format(intval($tool['price']))) . '/月';
            if (!is_numeric($tool['price'])) {
                $price_display = function_exists('gi_safe_escape') ? gi_safe_escape($tool['price']) : esc_html($tool['price']);
            }
            ?>
            <div class="tool-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <?php if ($tool['thumbnail']) : ?>
                                <img src="<?php echo esc_url($tool['thumbnail']); ?>" alt="<?php echo function_exists('gi_safe_attr') ? gi_safe_attr($tool['title']) : esc_attr($tool['title']); ?>" class="w-full h-full object-cover rounded-xl">
                            <?php else : ?>
                                <i class="fas fa-tools text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1 text-yellow-500">
                            <?php 
                            $rating = floatval($tool['rating']);
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5;
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '⭐';
                            }
                            if ($half_star) {
                                echo '⭐';
                            }
                            ?>
                            <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">(<?php echo function_exists('gi_safe_escape') ? gi_safe_escape($tool['rating']) : esc_html($tool['rating']); ?>)</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        <a href="<?php echo esc_url($tool['permalink']); ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400"><?php echo function_exists('gi_safe_escape') ? gi_safe_escape($tool['title']) : esc_html($tool['title']); ?></a>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                        <?php echo $tool['excerpt']; ?>
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400 px-3 py-1 rounded-full font-medium">
                            <?php echo $price_display; ?>
                        </span>
                        <a href="<?php echo esc_url($tool['permalink']); ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold">
                            詳細を見る →
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-20">
                <div class="w-32 h-32 bg-gradient-to-r from-indigo-400 via-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-tools text-white text-4xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">該当するツールが見つかりませんでした</h3>
                <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto text-lg leading-relaxed">
                    検索条件を変更して再度お試しください。
                </p>
            </div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'stats' => array(
            'total_found' => $query->found_posts,
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
        ),
    ));
}
add_action('wp_ajax_gi_load_tools', 'gi_ajax_load_tools');
add_action('wp_ajax_nopriv_gi_load_tools', 'gi_ajax_load_tools');

/**
 * 【完全修正版】AJAX - 申請のコツ読み込み処理（ダークモード対応）
 */
function gi_ajax_load_grant_tips() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました。');
    }

    $args = array(
        'post_type'      => 'grant_tip',
        'posts_per_page' => 9,
        'paged'          => intval($_POST['page'] ?? 1),
        'post_status'    => 'publish',
    );

    if (!empty($_POST['s'])) {
        $args['s'] = sanitize_text_field($_POST['s']);
    }

    $tax_query = array();
    if (!empty($_POST['grant_tip_category'])) {
        $tax_query[] = array(
            'taxonomy' => 'grant_tip_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_POST['grant_tip_category']),
        );
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $meta_query = array();
    if (!empty($_POST['difficulty'])) {
        $meta_query[] = array(
            'key'   => 'difficulty',
            'value' => sanitize_text_field($_POST['difficulty']),
            'compare' => '='
        );
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'date_desc');
    if ($sort_by === 'popular') {
        $args['orderby'] = 'comment_count';
        $args['order']   = 'DESC';
    } else {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    $query = new WP_Query($args);

    ob_start();
    if ($query->have_posts()) {
        echo '<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            ?>
            <div class="tip-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover rounded-xl')); ?>
                            <?php else : ?>
                                <i class="fas fa-lightbulb text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 line-clamp-2">
                                <a href="<?php echo function_exists('gi_safe_url') ? gi_safe_url(get_permalink()) : esc_url(get_permalink()); ?>" class="hover:text-yellow-600 dark:hover:text-yellow-400"><?php echo function_exists('gi_safe_escape') ? gi_safe_escape(get_the_title()) : esc_html(get_the_title()); ?></a>
                            </h3>
                                                    </div>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                        <?php echo function_exists('gi_safe_excerpt') ? gi_safe_excerpt(get_the_excerpt(), 75) : wp_trim_words(get_the_excerpt(), 15); ?>
                    </p>
                    
                    <div class="flex items-center justify-between text-sm">
                        <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 px-3 py-1 rounded-full font-medium">
                            <?php echo function_exists('gi_safe_escape') ? gi_safe_escape(gi_safe_get_meta($post_id, 'difficulty', '初級')) : esc_html(gi_safe_get_meta($post_id, 'difficulty', '初級')); ?>
                        </span>
                        <a href="<?php echo function_exists('gi_safe_url') ? gi_safe_url(get_permalink()) : esc_url(get_permalink()); ?>" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300 font-semibold">
                            詳細を見る →
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-20">
                <div class="w-32 h-32 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-lightbulb text-white text-5xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">該当するコツが見つかりませんでした</h3>
                <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto text-lg leading-relaxed">
                    検索条件を変更して再度お試しください。
                </p>
            </div>';
    }
    $html = ob_get_clean();
    
    // 【完成】ページネーション生成
    ob_start();
    if ($query->max_num_pages > 1) {
        echo '<div class="pagination-container flex items-center justify-center space-x-2 mt-8">';
        
        $current_page = $args['paged'];
        $total_pages = $query->max_num_pages;
        
        // 前のページ
        if ($current_page > 1) {
            echo '<button class="pagination-btn px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="' . ($current_page - 1) . '">';
            echo '<i class="fas fa-chevron-left mr-1"></i>前へ';
            echo '</button>';
        }
        
        // ページ番号
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active_class = ($i === $current_page) ? 'bg-yellow-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
            echo '<button class="pagination-btn px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg transition-colors ' . $active_class . '" data-page="' . $i . '">';
            echo $i;
            echo '</button>';
        }
        
        // 次のページ
        if ($current_page < $total_pages) {
            echo '<button class="pagination-btn px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="' . ($current_page + 1) . '">';
            echo '次へ<i class="fas fa-chevron-right ml-1"></i>';
            echo '</button>';
        }
        
        echo '</div>';
    }
    $pagination = ob_get_clean();

    wp_reset_postdata();

    wp_send_json_success(array(
        'html' => $html,
        'pagination' => $pagination,
        'found_posts' => $query->found_posts
    ));
}
add_action('wp_ajax_gi_load_grant_tips', 'gi_ajax_load_grant_tips');
add_action('wp_ajax_nopriv_gi_load_grant_tips', 'gi_ajax_load_grant_tips');

/**
 * 【新機能】AJAX - カード統計情報取得
 */
function gi_ajax_get_card_statistics() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $total_grants = wp_count_posts('grant')->publish;
    
    $active_grants = get_posts(array(
        'post_type' => 'grant',
        'meta_query' => array(
            array(
                'key' => 'application_status',
                'value' => 'open',
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    ));
    
    $success_rates = get_posts(array(
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'grant_success_rate',
                'value' => 0,
                'compare' => '>'
            )
        )
    ));
    
    $avg_success_rate = 0;
    if (!empty($success_rates)) {
        $total_rate = 0;
        foreach ($success_rates as $grant_id) {
            $rate = intval(gi_safe_get_meta($grant_id, 'grant_success_rate', 0));
            $total_rate += $rate;
        }
        $avg_success_rate = round($total_rate / count($success_rates));
    }
    
    $amounts = get_posts(array(
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'max_amount_numeric',
                'value' => 0,
                'compare' => '>'
            )
        )
    ));
    
    $avg_amount = 0;
    if (!empty($amounts)) {
        $total_amount = 0;
        foreach ($amounts as $grant_id) {
            $amount = intval(gi_safe_get_meta($grant_id, 'max_amount_numeric', 0));
            $total_amount += $amount;
        }
        $avg_amount = round($total_amount / count($amounts));
    }
    
    wp_send_json_success(array(
        'total_grants' => $total_grants,
        'active_grants' => count($active_grants),
        'avg_success_rate' => $avg_success_rate,
        'avg_amount' => $avg_amount,
        'formatted_avg_amount' => function_exists('gi_format_amount_with_unit') ? gi_format_amount_with_unit($avg_amount) : number_format($avg_amount),
        'prefecture_count' => wp_count_terms(array('taxonomy' => 'grant_prefecture', 'hide_empty' => false))
    ));
}
add_action('wp_ajax_gi_get_card_statistics', 'gi_ajax_get_card_statistics');
add_action('wp_ajax_nopriv_gi_get_card_statistics', 'gi_ajax_get_card_statistics');

/**
 * 【新機能】AJAX - お気に入り一覧取得
 */
function gi_ajax_get_favorites() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $user_id = get_current_user_id();
    $favorites = array();
    
    if ($user_id) {
        $favorite_ids = get_user_meta($user_id, 'gi_favorites', true);
        $favorite_ids = $favorite_ids ?: array();
    } else {
        $cookie_name = 'gi_favorites';
        $favorite_ids = isset($_COOKIE[$cookie_name]) ? 
            array_filter(array_map('intval', explode(',', $_COOKIE[$cookie_name]))) : 
            array();
    }
    
    if (!empty($favorite_ids)) {
        $args = array(
            'post_type' => 'grant',
            'post__in' => $favorite_ids,
            'posts_per_page' => -1,
            'orderby' => 'post__in',
            'post_status' => 'publish'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $favorites[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'excerpt' => get_the_excerpt(),
                    'organization' => gi_safe_get_meta($post_id, 'organization', ''),
                    'amount' => function_exists('gi_format_amount_with_unit') ? 
                               gi_format_amount_with_unit(gi_safe_get_meta($post_id, 'max_amount_numeric', 0)) : 
                               number_format(gi_safe_get_meta($post_id, 'max_amount_numeric', 0)),
                    'deadline' => gi_safe_get_meta($post_id, 'deadline_date', ''),
                    'status' => gi_safe_get_meta($post_id, 'application_status', ''),
                    'added_date' => get_the_date('Y-m-d')
                );
            }
            wp_reset_postdata();
        }
    }
    
    wp_send_json_success(array(
        'favorites' => $favorites,
        'count' => count($favorites),
        'user_type' => $user_id ? 'logged_in' : 'guest'
    ));
}
add_action('wp_ajax_gi_get_favorites', 'gi_ajax_get_favorites');
add_action('wp_ajax_nopriv_gi_get_favorites', 'gi_ajax_get_favorites');

/**
 * 【修正】JavaScriptデバッグ情報出力（ヘルパー関数使用）
 */
function gi_add_debug_js() {
    if (is_page_template('archive-grant.php') || is_post_type_archive('grant') || is_page('grants')) {
        ?>
        <script>
        window.giDebug = {
            logAjaxCall: function(action, data, response) {
                console.group('Grant Insight AJAX Debug');
                console.log('Action:', action);
                console.log('Request Data:', data);
                console.log('Response:', response);
                console.groupEnd();
            },
            
            testGrantsExist: function() {
                fetch('<?php echo function_exists('gi_safe_url') ? gi_safe_url(admin_url('admin-ajax.php')) : esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'gi_debug_grants',
                        nonce: '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Grant Debug Info:', data);
                    if (data.success) {
                        console.log(`Total grants: ${data.data.total_grants.publish}`);
                        console.log(`Template exists: ${data.data.template_exists}`);
                        console.log('Recent grants:', data.data.recent_grants);
                        console.log('Helper functions:', data.data.helper_functions_available);
                    }
                })
                .catch(error => {
                    console.error('Debug test failed:', error);
                });
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('debug=1')) {
                console.log('Grant Insight Debug Mode Enabled');
                window.giDebug.testGrantsExist();
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'gi_add_debug_js');

/**
 * JavaScript用のAJAX設定出力
 */
function gi_ajax_javascript_config() {
    if (is_page_template('archive-grant.php') || is_post_type_archive('grant') || is_page('grants')) {
        ?>
        <script>
        window.giAjaxConfig = {
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>',
            debug: <?php echo WP_DEBUG ? 'true' : 'false'; ?>,
            version: '<?php echo wp_get_theme()->get('Version'); ?>',
            
            handleError: function(error, action) {
                console.error('Grant Insight AJAX Error:', error);
                if (this.debug) {
                    console.log('Action:', action);
                    console.log('Error details:', error);
                }
            },
            
            logSuccess: function(data, action) {
                if (this.debug) {
                    console.log('Grant Insight AJAX Success:', action, data);
                }
            }
        };
        </script>
        <?php
    }
}
add_action('wp_footer', 'gi_ajax_javascript_config');

// =============================================================================
// 4. HELPER FUNCTIONS
// =============================================================================

/**
 * 【修正】未定義関数の追加
 */

// 締切日のフォーマット関数
function gi_get_formatted_deadline($post_id) {
    $deadline = gi_safe_get_meta($post_id, 'deadline_date');
    if (!$deadline) {
        $deadline = gi_safe_get_meta($post_id, 'deadline');
    }
    
    if (!$deadline) {
        return '';
    }
    
    if (is_numeric($deadline)) {
        return date('Y年m月d日', intval($deadline));
    }
    
    $timestamp = strtotime($deadline);
    if ($timestamp !== false) {
        return date('Y年m月d日', $timestamp);
    }
    
    return $deadline;
}

/**
 * 【修正】メタフィールドの同期処理（ACF対応）
 */
function gi_sync_grant_meta_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;
    if (!current_user_can('edit_post', $post_id)) return;

    $amount_text = get_post_meta($post_id, 'max_amount', true);
    if (!$amount_text) {
        $amount_text = get_field('max_amount', $post_id);
    }
    
    if ($amount_text) {
        $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
        if ($amount_numeric) {
            update_post_meta($post_id, 'max_amount_numeric', intval($amount_numeric));
        }
    }

    $deadline = get_post_meta($post_id, 'deadline', true);
    if (!$deadline) {
        $deadline = get_field('deadline', $post_id);
    }
    
    if ($deadline) {
        if (is_numeric($deadline)) {
            update_post_meta($post_id, 'deadline_date', intval($deadline));
        } else {
            $deadline_numeric = strtotime($deadline);
            if ($deadline_numeric !== false) {
                update_post_meta($post_id, 'deadline_date', $deadline_numeric);
            }
        }
    }

    $status = get_post_meta($post_id, 'status', true);
    if (!$status) {
        $status = get_field('application_status', $post_id);
    }
    
    if ($status) {
        update_post_meta($post_id, 'application_status', $status);
    } else {
        update_post_meta($post_id, 'application_status', 'open');
    }

    $organization = get_field('organization', $post_id);
    if ($organization) {
        update_post_meta($post_id, 'organization', $organization);
    }
}
add_action('save_post', 'gi_sync_grant_meta_on_save', 20, 3);

/**
 * セキュリティ・ヘルパー関数群（強化版）
 */

// 安全なメタ取得
function gi_safe_get_meta($post_id, $key, $default = '') {
    if (!$post_id || !is_numeric($post_id)) {
        return $default;
    }
    
    $value = get_post_meta($post_id, $key, true);
    
    if (is_null($value) || $value === false || $value === '') {
        if (function_exists('get_field')) {
            $value = get_field($key, $post_id);
        }
    }
    
    if (is_null($value) || $value === false || $value === '') {
        return $default;
    }
    
    return $value;
}

// 安全な属性出力
function gi_safe_attr($value) {
    if (is_array($value)) {
        $value = implode(' ', $value);
    }
    return esc_attr($value);
}

// 安全なHTML出力
function gi_safe_escape($value) {
    if (is_array($value)) {
        return array_map('esc_html', $value);
    }
    return esc_html($value);
}

// 安全な数値フォーマット
function gi_safe_number_format($value, $decimals = 0) {
    if (!is_numeric($value)) {
        return '0';
    }
    $num = floatval($value);
    return number_format($num, $decimals);
}

// 安全な日付フォーマット
function gi_safe_date_format($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    
    if (is_numeric($date)) {
        return date($format, $date);
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

// 安全なパーセント表示
function gi_safe_percent_format($value, $decimals = 1) {
    if (!is_numeric($value)) {
        return '0%';
    }
    $num = floatval($value);
    return number_format($num, $decimals) . '%';
}

// 安全なURL出力
function gi_safe_url($url) {
    if (empty($url)) {
        return '';
    }
    return esc_url($url);
}

// 安全なJSON出力
function gi_safe_json($data) {
    return wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// 安全なテキスト切り取り
function gi_safe_excerpt($text, $length = 100, $more = '...') {
    if (mb_strlen($text) <= $length) {
        return esc_html($text);
    }
    
    $excerpt = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }
    
    return esc_html($excerpt . $more);
}

/**
 * 動的パス取得関数（完全版）
 */

// アセットURL取得
function gi_get_asset_url($path) {
    $path = ltrim($path, '/');
    return get_template_directory_uri() . '/' . $path;
}

// アップロードURL取得
function gi_get_upload_url($filename) {
    $upload_dir = wp_upload_dir();
    $filename = ltrim($filename, '/');
    return $upload_dir['baseurl'] . '/' . $filename;
}

// メディアURL取得（自動検出機能付き）
function gi_get_media_url($filename, $fallback = true) {
    if (empty($filename)) {
        return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
    }
    
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    $filename = str_replace([
        'http://keishi0804.xsrv.jp/wp-content/uploads/',
        'https://keishi0804.xsrv.jp/wp-content/uploads/',
        '/wp-content/uploads/'
    ], '', $filename);
    
    $filename = ltrim($filename, '/');
    
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $filename;
    
    if (file_exists($file_path)) {
        return $upload_dir['baseurl'] . '/' . $filename;
    }
    
    $current_year = date('Y');
    $current_month = date('m');
    
    $possible_paths = [
        $current_year . '/' . $current_month . '/' . $filename,
        $current_year . '/' . $filename,
        'uploads/' . $filename,
        'media/' . $filename
    ];
    
    foreach ($possible_paths as $path) {
        $full_path = $upload_dir['basedir'] . '/' . $path;
        if (file_exists($full_path)) {
            return $upload_dir['baseurl'] . '/' . $path;
        }
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/placeholder.jpg');
    }
    
    return '';
}

// 動画URL取得
function gi_get_video_url($filename, $fallback = true) {
    $url = gi_get_media_url($filename, false);
    
    if (!empty($url)) {
        return $url;
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/videos/placeholder.mp4');
    }
    
    return '';
}

// ロゴURL取得
function gi_get_logo_url($fallback = true) {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    $hero_logo = get_theme_mod('gi_hero_logo');
    if ($hero_logo) {
        return gi_get_media_url($hero_logo, false);
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/logo.png');
    }
    
    return '';
}

/**
 * 補助ヘルパー: 金額（円）を万円表示用に整形
 */
function gi_format_amount_man($amount_yen, $amount_text = '') {
    $yen = is_numeric($amount_yen) ? intval($amount_yen) : 0;
    if ($yen > 0) {
        return gi_safe_number_format(intval($yen / 10000));
    }
    if (!empty($amount_text)) {
        if (preg_match('/([0-9,]+)\s*万円/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
        if (preg_match('/([0-9,]+)/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
    }
    return '0';
}

/**
 * 金額フォーマット用ヘルパー関数（万円・億円表記）
 */
if (!function_exists('gi_format_amount_with_unit')) {
    function gi_format_amount_with_unit($amount) {
        if (empty($amount) || $amount === '-' || !is_numeric($amount)) {
            return '未定';
        }
        
        $amount_num = intval($amount);
        if ($amount_num >= 100000000) { // 1億円以上
            $oku = $amount_num / 100000000;
            if ($oku == floor($oku)) {
                return number_format($oku) . '億円';
            } else {
                return number_format($oku, 1) . '億円';
            }
        } elseif ($amount_num >= 10000) { // 1万円以上
            $man = $amount_num / 10000;
            if ($man == floor($man)) {
                return number_format($man) . '万円';
            } else {
                return number_format($man, 1) . '万円';
            }
        } else {
            return number_format($amount_num) . '円';
        }
    }
}

/**
 * 補助ヘルパー: ACFのapplication_statusをUI用にマッピング
 */
function gi_map_application_status_ui($app_status) {
    switch ($app_status) {
        case 'open':
            return 'active';
        case 'upcoming':
            return 'upcoming';
        case 'closed':
            return 'closed';
        default:
            return 'active';
    }
}

/**
 * お気に入り一覧取得
 */
function gi_get_user_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
    } else {
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        if (!is_array($favorites)) $favorites = array();
    }
    
    return array_map('intval', $favorites);
}

/**
 * 投稿カテゴリー取得
 */
function gi_get_post_categories($post_id) {
    $post_type = get_post_type($post_id);
    $taxonomy = $post_type . '_category';
    
    if (!taxonomy_exists($taxonomy)) {
        return array();
    }
    
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return array();
    }
    
    return array_map(function($term) {
        return array(
            'name' => $term->name,
            'slug' => $term->slug,
            'link' => get_term_link($term)
        );
    }, $terms);
}

/**
 * 都道府県名取得
 */
function gi_get_prefecture_name($prefecture_id) {
    $prefectures = array(
        1 => '北海道', 2 => '青森県', 3 => '岩手県', 4 => '宮城県', 5 => '秋田県',
        6 => '山形県', 7 => '福島県', 8 => '茨城県', 9 => '栃木県', 10 => '群馬県',
        11 => '埼玉県', 12 => '千葉県', 13 => '東京都', 14 => '神奈川県', 15 => '新潟県',
        16 => '富山県', 17 => '石川県', 18 => '福井県', 19 => '山梨県', 20 => '長野県',
        21 => '岐阜県', 22 => '静岡県', 23 => '愛知県', 24 => '三重県', 25 => '滋賀県',
        26 => '京都府', 27 => '大阪府', 28 => '兵庫県', 29 => '奈良県', 30 => '和歌山県',
        31 => '鳥取県', 32 => '島根県', 33 => '岡山県', 34 => '広島県', 35 => '山口県',
        36 => '徳島県', 37 => '香川県', 38 => '愛媛県', 39 => '高知県', 40 => '福岡県',
        41 => '佐賀県', 42 => '長崎県', 43 => '熊本県', 44 => '大分県', 45 => '宮崎県',
        46 => '鹿児島県', 47 => '沖縄県'
    );
    
    return isset($prefectures[$prefecture_id]) ? $prefectures[$prefecture_id] : '';
}

/**
 * 助成金カテゴリ名取得
 */
function gi_get_category_name($category_id) {
    $categories = array(
        'startup' => '起業・創業支援',
        'research' => '研究開発',
        'employment' => '雇用促進',
        'training' => '人材育成',
        'export' => '輸出促進',
        'digital' => 'デジタル化',
        'environment' => '環境・エネルギー',
        'regional' => '地域活性化'
    );
    
    return isset($categories[$category_id]) ? $categories[$category_id] : '';
}

/**
 * 助成金ステータス名取得
 */
function gi_get_status_name($status) {
    $statuses = array(
        'active' => '募集中',
        'upcoming' => '募集予定',
        'closed' => '募集終了',
        'suspended' => '一時停止'
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : '';
}

/**
 * 🚀 検索統計データ更新・キャッシュ機能
 */
function gi_update_search_stats_cache() {
    $stats = wp_cache_get('grant_search_stats', 'grant_insight');
    
    if (false === $stats) {
        $stats = array(
            'total_grants' => wp_count_posts('grant')->publish ?? 1247,
            'total_tools' => wp_count_posts('tool')->publish ?? 89,
            'total_cases' => wp_count_posts('case_study')->publish ?? 156,
            'total_guides' => wp_count_posts('guide')->publish ?? 234,
            'last_updated' => current_time('timestamp')
        );
        
        wp_cache_set('grant_search_stats', $stats, 'grant_insight', 3600);
        update_option('gi_search_stats_backup', $stats);
    }
    
    return $stats;
}

/**
 * 🚀 検索統計データ取得（フォールバック機能付き）
 */
function gi_get_search_stats() {
    $stats = gi_update_search_stats_cache();
    
    $defaults = array(
        'total_grants' => 1247,
        'total_tools' => 89,
        'total_cases' => 156,
        'total_guides' => 234,
        'last_updated' => current_time('timestamp')
    );
    
    return wp_parse_args($stats, $defaults);
}

/**
 * 検索パラメータのサニタイズ
 */
function gi_sanitize_search_params($params) {
    return array(
        'search' => sanitize_text_field($params['search'] ?? ''),
        'categories' => array_map('sanitize_text_field', (array)($params['categories'] ?? [])),
        'prefectures' => array_map('sanitize_text_field', (array)($params['prefectures'] ?? [])),
        'amount' => sanitize_text_field($params['amount'] ?? ''),
        'status' => array_map('sanitize_text_field', (array)($params['status'] ?? [])),
        'difficulty' => array_map('sanitize_text_field', (array)($params['difficulty'] ?? [])),
        'success_rate' => array_map('sanitize_text_field', (array)($params['success_rate'] ?? [])),
        'sort' => sanitize_text_field($params['sort'] ?? 'date_desc'),
        'view' => sanitize_text_field($params['view'] ?? 'grid'),
        'page' => absint($params['page'] ?? 1)
    );
}

/**
 * フィルター値の検証
 */
function gi_validate_filter_value($value, $type) {
    switch ($type) {
        case 'amount':
            $valid_amounts = array('0-100', '100-500', '500-1000', '1000-3000', '3000+', '1000+');
            return in_array($value, $valid_amounts) ? $value : '';
            
        case 'status':
            $valid_statuses = array('active', 'upcoming', 'closed', 'suspended');
            return in_array($value, $valid_statuses) ? $value : '';
            
        case 'difficulty':
            $valid_difficulties = array('easy', 'normal', 'hard');
            return in_array($value, $valid_difficulties) ? $value : '';
            
        case 'success_rate':
            $valid_rates = array('high', 'medium', 'low');
            return in_array($value, $valid_rates) ? $value : '';
            
        case 'sort':
            $valid_sorts = array('date_desc', 'date_asc', 'amount_desc', 'amount_asc', 
                               'deadline_asc', 'success_rate_desc', 'title_asc');
            return in_array($value, $valid_sorts) ? $value : 'date_desc';
            
        default:
            return sanitize_text_field($value);
    }
}

// =============================================================================
// 5. TEMPLATE TAGS (Complete Version)
// =============================================================================

/**
 * 【修正】カード表示関数（完全版）- 新しいデザインを使用
 */
function gi_render_grant_card($post_id, $view = 'grid') {
    if (!$post_id || !get_post($post_id)) {
        return '';
    }

    global $post;
    $original_post = $post;
    $post = get_post($post_id);
    setup_postdata($post);
    
    ob_start();
    include(get_template_directory() . '/grant-card-v4-enhanced.php');
    $output = ob_get_clean();
    
    $post = $original_post;
    if ($post) {
        setup_postdata($post);
    } else {
        wp_reset_postdata();
    }
    
    return $output;
}

/**
 * 【新機能】新しいカードデザインでのグリッド表示 ★修正版
 */
function gi_render_grant_card_grid_enhanced($grant) {
    ob_start();
    
    $grant_id = $grant['id'];
    $grant_amount = $grant['amount_numeric'] ?? gi_safe_get_meta($grant_id, 'max_amount_numeric', 0);
    $deadline_timestamp = $grant['deadline_timestamp'] ?? gi_safe_get_meta($grant_id, 'deadline_date', '');
    $grant_rate = gi_safe_get_meta($grant_id, 'subsidy_rate', '2/3');
    $grant_target = gi_safe_get_meta($grant_id, 'grant_target', '中小企業');
    $grant_difficulty = gi_safe_get_meta($grant_id, 'grant_difficulty', 'normal');
    $grant_success_rate = gi_safe_get_meta($grant_id, 'grant_success_rate', 65);
    $is_featured = gi_safe_get_meta($grant_id, 'is_featured', false);
    $views_count = gi_safe_get_meta($grant_id, 'views_count', mt_rand(100, 500));
    
    $days_remaining = 0;
    if ($deadline_timestamp) {
        $days_remaining = ceil(((int)$deadline_timestamp - time()) / (60 * 60 * 24));
    }
    
    $difficulty_config = [
        'easy'   => ['label' => '易しい', 'color' => 'green', 'stars' => 1],
        'normal' => ['label' => '普通',   'color' => 'blue', 'stars' => 2],
        'hard'   => ['label' => '難しい', 'color' => 'orange', 'stars' => 3],
        'expert' => ['label' => '専門的', 'color' => 'red', 'stars' => 4]
    ];
    $difficulty_info = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];
    
    $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : [];
    $is_favorite = in_array($grant_id, $user_favorites);
    ?>
    
    <article class="grant-card-enhanced group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-1 overflow-hidden" data-grant-id="<?php echo esc_attr($grant_id); ?>">
        
        <?php if ($is_featured): ?>
        <div class="absolute top-0 right-0 z-10">
            <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold py-2 px-4 rounded-bl-2xl shadow-lg">
                <i class="fas fa-star mr-1"></i>注目
            </div>
        </div>
        <?php endif; ?>
        
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-10 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative p-6 pb-4">
                <div class="flex flex-wrap gap-2 mb-3">
                    <?php if (!empty($grant['main_category'])): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        <i class="fas fa-folder mr-1"></i><?php echo esc_html($grant['main_category']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($grant['prefecture'])): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo esc_html($grant['prefecture']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors duration-300">
                    <a href="<?php echo esc_url($grant['permalink']); ?>"><?php echo esc_html($grant['title']); ?></a>
                </h3>
                
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center">
                            <i class="fas fa-chart-line text-green-500 mr-1"></i>
                            採択率 <strong class="text-green-600 ml-1"><?php echo esc_html($grant_success_rate); ?>%</strong>
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-eye text-gray-400 mr-1"></i>
                            <?php echo number_format($views_count); ?>回閲覧
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if ($deadline_timestamp && $days_remaining > 0): ?>
            <div class="px-6 pb-4">
                <?php 
                    $progress_percentage = max(0, min(100, (30 - $days_remaining) / 30 * 100));
                    $progress_color = $days_remaining <= 7 ? 'red' : ($days_remaining <= 14 ? 'yellow' : 'green');
                ?>
                <div class="flex justify-between items-center mb-1 text-xs">
                    <span class="text-gray-600">申請期限</span>
                    <span class="font-bold text-<?php echo esc_attr($progress_color); ?>-600">
                        残り<?php echo esc_html($days_remaining); ?>日
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-<?php echo esc_attr($progress_color); ?>-400 to-<?php echo esc_attr($progress_color); ?>-600 h-2 rounded-full transition-all duration-500" 
                         style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="px-6 pb-4">
            <div class="mb-4 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-200">
                <div class="text-sm text-gray-600 mb-1">最大支援額</div>
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-emerald-600">
                        <?php echo ($grant_amount > 0) ? number_format($grant_amount / 10000) : ($grant['amount'] ?? '-'); ?>
                    </span>
                    <?php if ($grant_amount > 0 || (isset($grant['amount']) && $grant['amount'] !== '-')): ?>
                    <span class="text-lg text-emerald-600 ml-1">万円</span>
                    <?php endif; ?>
                    <?php if ($grant_rate): ?>
                    <span class="ml-3 text-sm text-gray-600">
                        (補助率: <strong><?php echo esc_html($grant_rate); ?></strong>)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">申請難易度</span>
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <i class="fas fa-star text-<?php echo $i <= $difficulty_info['stars'] ? esc_attr($difficulty_info['color']) : 'gray'; ?>-400"></i>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm font-medium text-<?php echo esc_attr($difficulty_info['color']); ?>-600">
                            <?php echo esc_html($difficulty_info['label']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="text-xs text-gray-600 mb-1">対象事業者</div>
                <div class="text-sm font-medium text-gray-800"><?php echo esc_html($grant_target); ?></div>
            </div>
            
            <?php if (!empty($grant['excerpt'])): ?>
            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                <?php echo esc_html($grant['excerpt']); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <div class="px-6 pb-6">
            <div class="flex gap-3">
                <a href="<?php echo esc_url($grant['permalink']); ?>" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-xl">
                    <span>詳細を見る</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                </a>
                
                <button type="button" 
                        class="favorite-btn p-3 bg-gray-100 hover:bg-red-100 rounded-lg transition-all duration-300 group/fav"
                        data-post-id="<?php echo esc_attr($grant_id); ?>">
                    <i class="fa-heart text-gray-600 group-hover/fav:text-red-500 transition-colors duration-300 <?php echo $is_favorite ? 'fas' : 'far'; ?>"></i>
                </button>
                
                <button type="button" 
                        class="share-btn p-3 bg-gray-100 hover:bg-blue-100 rounded-lg transition-all duration-300 group/share"
                        data-url="<?php echo esc_url($grant['permalink']); ?>"
                        data-title="<?php echo esc_attr($grant['title']); ?>">
                    <i class="fas fa-share-alt text-gray-600 group-hover/share:text-blue-500 transition-colors duration-300"></i>
                </button>
            </div>
        </div>
        
        <div class="absolute inset-0 bg-gradient-to-t from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
    </article>
    
    <?php
    return ob_get_clean();
}

/**
 * ステータスバッジ取得（新デザイン対応）
 */
function gi_get_status_badge($status) {
    $badges = array(
        'active' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-green-400 to-green-600 text-white rounded-full shadow-md"><i class="fas fa-circle mr-1 animate-pulse"></i>募集中</span>',
        'upcoming' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-full shadow-md"><i class="fas fa-clock mr-1"></i>募集予定</span>',
        'closed' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-red-400 to-red-600 text-white rounded-full shadow-md"><i class="fas fa-times-circle mr-1"></i>募集終了</span>'
    );
    return $badges[$status] ?? $badges['active'];
}

/**
 * 複数カード表示関数（新デザイン対応）
 */
function gi_render_multiple_grants($post_ids, $view = 'grid', $columns = 3) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return '<div class="text-center py-12 text-gray-500">表示する助成金がありません。</div>';
    }

    $grid_classes = array(
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4'
    );

    ob_start();
    
    if ($view === 'grid') {
        $grid_class = $grid_classes[$columns] ?? $grid_classes[3];
        echo '<div class="grid ' . $grid_class . ' gap-8">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'grid');
        }
        
        echo '</div>';
    } else {
        echo '<div class="space-y-6">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'list');
        }
        
        echo '</div>';
    }
    
    return ob_get_clean();
}

// =============================================================================
// 6. ADMIN FUNCTIONS
// =============================================================================

/**
 * 管理画面用スクリプト
 */
function gi_admin_enqueue_scripts($hook) {
    wp_enqueue_style('gi-admin-style', get_template_directory_uri() . '/css/admin.css', array(), GI_THEME_VERSION);
    wp_enqueue_script('gi-admin-js', get_template_directory_uri() . '/js/admin.js', array('jquery'), GI_THEME_VERSION, true);
    
    wp_localize_script('gi-admin-js', 'giAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'gi_admin_enqueue_scripts');

/**
 * 管理画面カスタマイズ（強化版）
 */
function gi_admin_init() {
    add_action('admin_head', function() {
        echo '<style>
        .gi-admin-notice {
            border-left: 4px solid #10b981;
            background: #ecfdf5;
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .gi-admin-notice h3 {
            color: #047857;
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        .gi-admin-notice p {
            color: #065f46;
            margin: 0;
        }
        </style>';
    });
    
    add_filter('manage_grant_posts_columns', 'gi_add_grant_columns');
    add_action('manage_grant_posts_custom_column', 'gi_grant_column_content', 10, 2);
}
add_action('admin_init', 'gi_admin_init');

/**
 * 助成金一覧にカスタムカラムを追加
 */
function gi_add_grant_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['gi_prefecture'] = '都道府県';
            $new_columns['gi_amount'] = '金額';
            $new_columns['gi_organization'] = '実施組織';
            $new_columns['gi_status'] = 'ステータス';
        }
    }
    return $new_columns;
}

/**
 * カスタムカラムに内容を表示
 */
function gi_grant_column_content($column, $post_id) {
    switch ($column) {
        case 'gi_prefecture':
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                echo gi_safe_escape($prefecture_terms[0]->name);
            } else {
                echo '－';
            }
            break;
        case 'gi_amount':
            $amount = gi_safe_get_meta($post_id, 'max_amount');
            echo $amount ? gi_safe_escape($amount) . '万円' : '－';
            break;
        case 'gi_organization':
            echo gi_safe_escape(gi_safe_get_meta($post_id, 'organization', '－'));
            break;
        case 'gi_status':
            $status = gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open'));
            $status_labels = array(
                'active' => '<span style="color: #059669;">募集中</span>',
                'upcoming' => '<span style="color: #d97706;">募集予定</span>',
                'closed' => '<span style="color: #dc2626;">募集終了</span>'
            );
            echo $status_labels[$status] ?? $status;
            break;
    }
}

/**
 * 重要ニュース設定用カスタムフィールド追加
 */
function gi_add_news_importance_field() {
    add_meta_box(
        'gi_news_importance',
        '重要度設定',
        'gi_news_importance_callback',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'gi_add_news_importance_field');

/**
 * 重要ニュース用メタボックスのHTMLコールバック
 */
function gi_news_importance_callback($post) {
    wp_nonce_field('gi_news_importance_nonce', 'gi_news_importance_nonce');
    $value = get_post_meta($post->ID, 'is_important_news', true);
    ?>
    <label for="is_important_news">
        <input type="checkbox" name="is_important_news" id="is_important_news" value="1" <?php checked($value, '1'); ?> />
        重要なお知らせとして表示
    </label>
    <p class="description">チェックすると、ニュース一覧の上部に優先表示されます。</p>
    <?php
}

/**
 * 重要ニュース用メタボックスのデータ保存処理
 */
function gi_save_news_importance($post_id) {
    if (!isset($_POST['gi_news_importance_nonce']) || 
        !wp_verify_nonce($_POST['gi_news_importance_nonce'], 'gi_news_importance_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['is_important_news'])) {
        update_post_meta($post_id, 'is_important_news', '1');
    } else {
        delete_post_meta($post_id, 'is_important_news');
    }
}
add_action('save_post', 'gi_save_news_importance');

/**
 * 都道府県データ初期化メニューを管理画面に追加
 */
function gi_add_admin_menu() {
    add_management_page(
        '都道府県データ初期化',
        '都道府県データ初期化',
        'manage_options',
        'gi-prefecture-init',
        'gi_add_prefecture_init_button'
    );
}
add_action('admin_menu', 'gi_add_admin_menu');

/**
 * 都道府県データ初期化ページの表示内容
 */
function gi_add_prefecture_init_button() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['init_prefecture_data']) && isset($_POST['prefecture_nonce']) && wp_verify_nonce($_POST['prefecture_nonce'], 'init_prefecture')) {
        if (function_exists('gi_setup_prefecture_taxonomy_data')) {
            gi_setup_prefecture_taxonomy_data();
            echo '<div class="notice notice-success"><p>都道府県データを初期化しました。</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>エラー: 初期化関数が見つかりませんでした。</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h2>都道府県データ初期化</h2>
        <form method="post">
            <?php wp_nonce_field('init_prefecture', 'prefecture_nonce'); ?>
            <p>助成金の都道府県データとサンプルデータを初期化します。</p>
            <p class="description">この操作は既存の都道府県タクソノミーに不足しているデータを追加するもので、既存のデータを削除するものではありません。</p>
            <input type="submit" name="init_prefecture_data" class="button button-primary" value="都道府県データを初期化" />
        </form>
    </div>
    <?php
}

// =============================================================================
// 7. ACF SETUP
// =============================================================================

// ACFプラグインが存在しない場合は、以降の処理を中断
if (!function_exists('acf_add_local_field_group')) {
    // ACF設定をスキップ
} else {

/**
 * ACF Local JSON の設定
 */
add_filter('acf/settings/save_json', function($path) {
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!file_exists($theme_path)) {
        wp_mkdir_p($theme_path);
    }
    return $theme_path;
});

add_filter('acf/settings/load_json', function($paths) {
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!in_array($theme_path, $paths, true)) {
        $paths[] = $theme_path;
    }
    return $paths;
});

/**
 * PHPによるフィールドグループの登録
 */
add_action('acf/init', function() {
    $json_file = get_stylesheet_directory() . '/acf-fields.json';
    if (!file_exists($json_file)) {
        return;
    }

    $raw_data = file_get_contents($json_file);
    if (!$raw_data) {
        return;
    }

    $json_data = json_decode($raw_data, true);
    if (!is_array($json_data)) {
        return;
    }

    $groups = $json_data['groups'] ?? [];
    foreach ($groups as $group) {
        if (!empty($group['key']) && !empty($group['title']) && !empty($group['fields']) && !empty($group['location'])) {
            acf_add_local_field_group($group);
        }
    }
});

/**
 * ACFフィールドとタクソノミーの同期
 */
function gi_sync_grant_prefectures_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;

    $meta_values = array();
    $candidates = array('prefecture', 'prefectures', 'grant_prefecture');
    foreach ($candidates as $key) {
        $val = gi_safe_get_meta($post_id, $key, '');
        if (!empty($val)) {
            $meta_values = is_array($val) ? $val : preg_split('/[,|]/u', $val);
            break;
        }
    }
    if (empty($meta_values)) return;

    $term_ids = array();
    foreach ($meta_values as $raw) {
        $name = trim(wp_strip_all_tags($raw));
        if ($name === '') continue;
        
        $term = get_term_by('name', $name, 'grant_prefecture');
        if (!$term) {
            $term = get_term_by('slug', sanitize_title($name), 'grant_prefecture');
        }
        
        if ($term && !is_wp_error($term)) {
            $term_ids[] = intval($term->term_id);
        }
    }
    
    if (!empty($term_ids)) {
        wp_set_post_terms($post_id, $term_ids, 'grant_prefecture', false);
    }
}
add_action('save_post', 'gi_sync_grant_prefectures_on_save', 20, 3);

} // ACF存在チェック終了

// =============================================================================
// 8. INITIAL SETUP (Complete Enhanced Edition)
// =============================================================================

/**
 * テーマ有効化時に実行されるメインのセットアップ関数
 */
function gi_theme_activation_setup() {
    gi_insert_default_prefectures();
    gi_insert_default_categories();
    gi_insert_tool_categories();
    gi_insert_grant_tip_categories();
    gi_insert_sample_grants_with_prefectures();
    gi_insert_sample_tools();
    gi_insert_sample_grant_tips();
    flush_rewrite_rules();
    update_option('gi_initial_setup_completed', current_time('timestamp'));
}
add_action('after_switch_theme', 'gi_theme_activation_setup');

/**
 * デフォルト都道府県データの挿入
 */
function gi_insert_default_prefectures() {
    $prefectures = array(
        '全国対応', '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );

    foreach ($prefectures as $prefecture) {
        if (!term_exists($prefecture, 'grant_prefecture')) {
            wp_insert_term($prefecture, 'grant_prefecture');
        }
    }
}

/**
 * デフォルトカテゴリーデータの挿入
 */
function gi_insert_default_categories() {
    $grant_categories = array(
        'IT・デジタル化支援',
        '設備投資・機械導入',
        '人材育成・教育訓練',
        '研究開発・技術革新',
        '省エネ・環境対策',
        '事業承継・M&A',
        '海外展開・輸出促進',
        '創業・起業支援',
        '販路開拓・マーケティング',
        '働き方改革・労働環境',
        '観光・地域振興',
        '農業・林業・水産業',
        '製造業・ものづくり',
        'サービス業・小売業',
        'コロナ対策・事業継続',
        '女性・若者・シニア支援',
        '障がい者雇用支援',
        '知的財産・特許',
        'BCP・リスク管理',
        'その他・汎用'
    );

    foreach ($grant_categories as $category) {
        if (!term_exists($category, 'grant_category')) {
            wp_insert_term($category, 'grant_category');
        }
    }
}

/**
 * ツール用カテゴリーデータの挿入
 */
function gi_insert_tool_categories() {
    $tool_categories = array(
        'プロジェクト管理',
        'コミュニケーション',
        'マーケティング・CRM',
        '会計・経理',
        '人事・労務',
        'デザイン・クリエイティブ',
        '開発・プログラミング',
        'データ分析・BI',
        'セキュリティ',
        'クラウドストレージ',
        'タスク・時間管理',
        'eコマース・EC',
        '在庫・物流管理',
        '営業・セールス',
        'カスタマーサポート',
        'その他・汎用ツール'
    );

    foreach ($tool_categories as $category) {
        if (!term_exists($category, 'tool_category')) {
            wp_insert_term($category, 'tool_category');
        }
    }
}

/**
 * 申請のコツ用カテゴリーデータの挿入
 */
function gi_insert_grant_tip_categories() {
    $tip_categories = array(
        '申請書作成のコツ',
        '事業計画書の書き方',
        '審査対策・面接準備',
        '必要書類の準備',
        '申請スケジュール管理',
        'よくある失敗例',
        '成功のポイント',
        '補助金の種類・選び方',
        '採択後の手続き',
        '実績報告・検査対応'
    );

    foreach ($tip_categories as $category) {
        if (!term_exists($category, 'grant_tip_category')) {
            wp_insert_term($category, 'grant_tip_category');
        }
    }
}

/**
 * ★修正版 サンプル助成金データの投入（都道府県・新項目付き）
 */
function gi_insert_sample_grants_with_prefectures() {
    $sample_grants = [
        [
            'title' => '【サンプル】IT導入補助金2025',
            'content' => 'ITツールの導入により生産性向上を図る中小企業・小規模事業者等を支援する補助金制度です。業務効率化・売上向上に資するITツール導入費用の一部を補助します。',
            'prefecture' => '全国対応',
            'amount' => 4500000,
            'category' => 'IT・デジタル化支援',
            'difficulty' => 'normal',
            'success_rate' => 75,
            'subsidy_rate' => '1/2以内',
            'target' => '中小企業・小規模事業者',
            'organization' => '独立行政法人中小企業基盤整備機構',
            'deadline_days' => 90,
            'is_featured' => true
        ],
        [
            'title' => '【サンプル】東京都中小企業DX推進補助金',
            'content' => '都内中小企業のデジタルトランスフォーメーション（DX）推進を支援する東京都独自の補助金制度です。AI・IoT・クラウド導入等を幅広く対象としています。',
            'prefecture' => '東京都',
            'amount' => 3000000,
            'category' => 'IT・デジタル化支援',
            'difficulty' => 'easy',
            'success_rate' => 85,
            'subsidy_rate' => '2/3以内',
            'target' => '都内に事業所を持つ中小企業',
            'organization' => '東京都産業労働局',
            'deadline_days' => 60,
            'is_featured' => false
        ],
        [
            'title' => '【サンプル】大阪府ものづくり補助金',
            'content' => '大阪府内の製造業者が行う新製品・サービス開発や生産プロセスの改善等に要する設備投資等を支援する補助金制度です。',
            'prefecture' => '大阪府',
            'amount' => 10000000,
            'category' => '製造業・ものづくり',
            'difficulty' => 'hard',
            'success_rate' => 60,
            'subsidy_rate' => '1/2、2/3',
            'target' => '大阪府内の製造業者',
            'organization' => '大阪府商工労働部',
            'deadline_days' => 120,
            'is_featured' => true
        ],
        [
            'title' => '【サンプル】愛知県創業支援補助金',
            'content' => '愛知県内で新たに創業する方や創業間もない事業者を対象とした創業支援補助金です。店舗改装費、設備購入費、広告宣伝費等を支援します。',
            'prefecture' => '愛知県',
            'amount' => 2000000,
            'category' => '創業・起業支援',
            'difficulty' => 'normal',
            'success_rate' => 70,
            'subsidy_rate' => '1/2以内',
            'target' => '愛知県内で創業する個人・法人',
            'organization' => '愛知県産業労働部',
            'deadline_days' => 75,
            'is_featured' => false
        ],
        [
            'title' => '【サンプル】福岡県雇用促進助成金',
            'content' => '福岡県内の事業者が正社員の新規雇用や人材育成・研修を実施する際の費用を支援する助成金制度です。雇用の安定と人材確保を促進します。',
            'prefecture' => '福岡県',
            'amount' => 1500000,
            'category' => '人材育成・教育訓練',
            'difficulty' => 'easy',
            'success_rate' => 80,
            'subsidy_rate' => '2/3以内',
            'target' => '福岡県内の中小企業',
            'organization' => '福岡県商工部',
            'deadline_days' => 45,
            'is_featured' => false
        ],
        [
            'title' => '【サンプル】環境対策設備導入補助金',
            'content' => '省エネルギー設備や再生可能エネルギー設備の導入により、CO2削減に取り組む事業者を支援する全国対象の補助金制度です。',
            'prefecture' => '全国対応',
            'amount' => 8000000,
            'category' => '省エネ・環境対策',
            'difficulty' => 'normal',
            'success_rate' => 65,
            'subsidy_rate' => '1/3以内',
            'target' => '中小企業・個人事業主',
            'organization' => '経済産業省',
            'deadline_days' => 100,
            'is_featured' => true
        ]
    ];
    
    foreach ($sample_grants as $grant_data) {
        if (!get_page_by_title($grant_data['title'], OBJECT, 'grant')) {
            
            $deadline_timestamp = strtotime('+' . $grant_data['deadline_days'] . ' days');
            
            $post_id = wp_insert_post([
                'post_title'   => $grant_data['title'],
                'post_content' => $grant_data['content'],
                'post_excerpt' => wp_trim_words($grant_data['content'], 20),
                'post_type'    => 'grant',
                'post_status'  => 'publish',
                'meta_input'   => [
                    'max_amount'         => number_format($grant_data['amount'] / 10000) . '万円',
                    'max_amount_numeric' => $grant_data['amount'],
                    'deadline_date'      => $deadline_timestamp,
                    'organization'       => $grant_data['organization'],
                    'application_status' => 'open',
                    'grant_difficulty'   => $grant_data['difficulty'],
                    'grant_success_rate' => $grant_data['success_rate'],
                    'subsidy_rate'       => $grant_data['subsidy_rate'],
                    'grant_target'       => $grant_data['target'],
                    'is_featured'        => $grant_data['is_featured'],
                    'views_count'        => rand(150, 800),
                    'application_period' => date('Y年n月j日', $deadline_timestamp - (86400 * $grant_data['deadline_days'])) . ' ～ ' . date('Y年n月j日', $deadline_timestamp),
                    'eligible_expenses'  => '設備費、システム導入費、コンサルティング費等',
                    'application_method' => 'オンライン申請',
                    'contact_info'       => $grant_data['organization'] . ' 補助金担当窓口',
                    'required_documents' => '申請書、事業計画書、見積書、会社概要等'
                ]
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                wp_set_object_terms($post_id, $grant_data['prefecture'], 'grant_prefecture');
                wp_set_object_terms($post_id, $grant_data['category'], 'grant_category');
                gi_set_sample_thumbnail($post_id, 'grant');
            }
        }
    }
}

/**
 * サンプルツールデータの投入
 */
function gi_insert_sample_tools() {
    $sample_tools = [
        [
            'title' => '【サンプル】Slack - チームコミュニケーションツール',
            'content' => 'チーム内のコミュニケーションを効率化するビジネスチャットツール。プロジェクト管理や外部サービス連携機能も豊富です。',
            'category' => 'コミュニケーション',
            'price_monthly' => 850,
            'price_free' => 1,
            'rating' => 4.7,
            'features' => 'リアルタイムメッセージング、ファイル共有、外部連携',
            'url' => 'https://slack.com',
            'company' => 'Slack Technologies'
        ],
        [
            'title' => '【サンプル】Trello - プロジェクト管理ツール',
            'content' => 'カンバン方式でタスクを視覚的に管理できるプロジェクト管理ツール。直感的な操作でチーム全体の進捗を把握できます。',
            'category' => 'プロジェクト管理',
            'price_monthly' => 0,
            'price_free' => 1,
            'rating' => 4.5,
            'features' => 'カンバンボード、タスク管理、チーム共有',
            'url' => 'https://trello.com',
            'company' => 'Atlassian'
        ],
        [
            'title' => '【サンプル】HubSpot CRM - 顧客管理システム',
            'content' => '営業・マーケティング・カスタマーサービスを統合したCRMプラットフォーム。顧客情報の一元管理が可能です。',
            'category' => 'マーケティング・CRM',
            'price_monthly' => 5400,
            'price_free' => 1,
            'rating' => 4.3,
            'features' => '顧客管理、営業パイプライン、メール配信',
            'url' => 'https://hubspot.com',
            'company' => 'HubSpot'
        ],
        [
            'title' => '【サンプル】freee会計 - クラウド会計ソフト',
            'content' => '中小企業向けのクラウド会計ソフト。簿記知識がなくても簡単に会計業務を行えます。確定申告にも対応。',
            'category' => '会計・経理',
            'price_monthly' => 2680,
            'price_free' => 0,
            'rating' => 4.2,
            'features' => '自動仕訳、確定申告、請求書作成',
            'url' => 'https://freee.co.jp',
            'company' => 'freee株式会社'
        ],
        [
            'title' => '【サンプル】Figma - デザインツール',
            'content' => 'Webブラウザで動作するUIデザインツール。リアルタイム共同編集機能により、チームでのデザイン制作が効率化されます。',
            'category' => 'デザイン・クリエイティブ',
            'price_monthly' => 1500,
            'price_free' => 1,
            'rating' => 4.8,
            'features' => 'UI/UXデザイン、プロトタイピング、リアルタイム共同編集',
            'url' => 'https://figma.com',
            'company' => 'Figma Inc.'
        ]
    ];
    
    foreach ($sample_tools as $tool_data) {
        if (!get_page_by_title($tool_data['title'], OBJECT, 'tool')) {
            $post_id = wp_insert_post([
                'post_title'   => $tool_data['title'],
                'post_content' => $tool_data['content'],
                'post_excerpt' => wp_trim_words($tool_data['content'], 15),
                'post_type'    => 'tool',
                'post_status'  => 'publish',
                'meta_input'   => [
                    'price_monthly' => $tool_data['price_monthly'],
                    'price_free'    => $tool_data['price_free'],
                    'rating'        => $tool_data['rating'],
                    'features'      => $tool_data['features'],
                    'tool_url'      => $tool_data['url'],
                    'company'       => $tool_data['company'],
                    'view_count'    => rand(200, 1000)
                ]
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                wp_set_object_terms($post_id, $tool_data['category'], 'tool_category');
                gi_set_sample_thumbnail($post_id, 'tool');
            }
        }
    }
}

/**
 * サンプル申請のコツデータの投入
 */
function gi_insert_sample_grant_tips() {
    $sample_tips = [
        [
            'title' => '【サンプル】採択率を上げる事業計画書の書き方',
            'content' => '助成金の採択率を上げるための事業計画書作成のポイントを解説します。審査員に伝わりやすい構成や表現方法、数値の根拠の示し方など、具体的なテクニックをお教えします。',
            'category' => '事業計画書の書き方',
            'difficulty' => '中級',
            'reading_time' => 8
        ],
        [
            'title' => '【サンプル】申請書作成で絶対に避けるべき5つの失敗',
            'content' => '助成金申請でよくある失敗パターンを5つピックアップしました。これらを避けることで、書類不備による不採択を防げます。実際の失敗事例も交えて解説します。',
            'category' => 'よくある失敗例',
            'difficulty' => '初級',
            'reading_time' => 5
        ],
        [
            'title' => '【サンプル】必要書類を効率よく準備する方法',
            'content' => '助成金申請に必要な書類は多岐にわたります。効率よく準備するためのチェックリストや、書類作成の優先順位、外部専門家に依頼すべき書類の見極め方を説明します。',
            'category' => '必要書類の準備',
            'difficulty' => '初級',
            'reading_time' => 6
        ],
        [
            'title' => '【サンプル】面接・プレゼンテーション対策完全ガイド',
            'content' => '助成金によっては面接やプレゼンテーションが必要な場合があります。審査員への効果的なアピール方法、想定される質問への回答例、当日の服装や持ち物まで詳しく解説します。',
            'category' => '審査対策・面接準備',
            'difficulty' => '上級',
            'reading_time' => 12
        ],
        [
            'title' => '【サンプル】採択後の手続きで注意すべきポイント',
            'content' => '助成金に採択された後も重要な手続きが続きます。交付申請、事業実施、実績報告まで、各段階での注意点や必要書類、スケジュール管理のコツを説明します。',
            'category' => '採択後の手続き',
            'difficulty' => '中級',
            'reading_time' => 10
        ]
    ];
    
    foreach ($sample_tips as $tip_data) {
        if (!get_page_by_title($tip_data['title'], OBJECT, 'grant_tip')) {
            $post_id = wp_insert_post([
                'post_title'   => $tip_data['title'],
                'post_content' => $tip_data['content'] . "\n\n" . gi_generate_sample_tip_content($tip_data['category']),
                'post_excerpt' => wp_trim_words($tip_data['content'], 25),
                'post_type'    => 'grant_tip',
                'post_status'  => 'publish',
                'meta_input'   => [
                    'difficulty'     => $tip_data['difficulty'],
                    'reading_time'   => $tip_data['reading_time'],
                    'view_count'     => rand(100, 600),
                    'usefulness_rating' => rand(40, 50) / 10
                ]
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                wp_set_object_terms($post_id, $tip_data['category'], 'grant_tip_category');
                gi_set_sample_thumbnail($post_id, 'grant_tip');
            }
        }
    }
}

/**
 * サンプルのコンテンツ内容を生成する関数
 */
function gi_generate_sample_tip_content($category) {
    $content_templates = [
        '事業計画書の書き方' => "
## 1. 現状分析を明確に記載する
事業の現状を客観的に分析し、課題を明確に示します。

## 2. 数値目標を具体的に設定する
売上高、利益率、雇用創出数など、具体的な数値目標を設定しましょう。

## 3. 実現可能性を論理的に説明する
目標達成のための具体的な手順とスケジュールを示します。

## 4. 市場調査結果を活用する
業界動向や競合分析の結果を計画書に反映させます。

## 5. 収支計画を詳細に作成する
投資回収計画を含めた詳細な収支予測を作成します。",

        'よくある失敗例' => "
## 失敗例1: 申請締切間際の準備
締切直前では十分な準備ができません。余裕を持ったスケジュールを立てましょう。

## 失敗例2: 書類の不備・記載漏れ
チェックリストを作成し、第三者による確認を行いましょう。

## 失敗例3: 事業計画の根拠不足
数値や計画には必ず根拠を示し、実現可能性を説明しましょう。

## 失敗例4: 助成金制度の理解不足
制度の趣旨や要件を十分理解してから申請しましょう。

## 失敗例5: アフターフォローの軽視
採択後の手続きも重要です。事前に確認しておきましょう。",

        '必要書類の準備' => "
## 基本書類の準備
- 申請書（各助成金指定の様式）
- 事業計画書
- 収支予算書
- 会社概要・パンフレット

## 財務関連書類
- 決算書（直近2～3期分）
- 納税証明書
- 資金調達計画書

## 事業関連書類
- 見積書・カタログ
- 契約書・仕様書
- 市場調査資料

## その他必要に応じて
- 許認可証明書
- 従業員名簿
- 組織図",

        '審査対策・面接準備' => "
## 面接での基本マナー
- 時間厳守で会場に到着
- 適切な服装（ビジネススーツ推奨）
- 明確で簡潔な話し方

## よく聞かれる質問への準備
- 事業の独自性・新規性
- 市場での競争優位性
- 具体的な実施スケジュール
- 投資回収の見通し

## プレゼンテーション資料
- 視覚的で分かりやすいスライド
- 制限時間内での構成
- 想定質問への回答準備

## 当日の持ち物
- 申請書類一式
- 名刺
- 会社案内・パンフレット
- 補足資料",

        '採択後の手続き' => "
## 交付決定後の手続き
1. 交付決定通知書の受領
2. 事業実施計画の詳細化
3. 実施スケジュールの確定

## 事業実施中の注意点
- 計画変更時の事前相談
- 証拠書類の適切な保管
- 定期的な進捗報告

## 実績報告の準備
- 事業完了報告書の作成
- 収支実績書の準備
- 成果物・証拠書類の整理

## 事後管理
- 事業効果の継続測定
- 改善点の把握と対応
- 次回申請への活用"
    ];
    
    return $content_templates[$category] ?? "サンプルコンテンツです。実際の内容に置き換えてご利用ください。";
}

/**
 * サンプル画像を投稿にセットする関数
 */
function gi_set_sample_thumbnail($post_id, $post_type) {
    $placeholder_images = [
        'grant' => 'https://via.placeholder.com/400x300/3B82F6/FFFFFF?text=Grant',
        'tool' => 'https://via.placeholder.com/400x300/10B981/FFFFFF?text=Tool',
        'grant_tip' => 'https://via.placeholder.com/400x300/F59E0B/FFFFFF?text=Tips'
    ];
    
    $image_url = $placeholder_images[$post_type] ?? $placeholder_images['grant'];
    update_post_meta($post_id, 'sample_thumbnail_url', $image_url);
}

/**
 * 【追加】カスタムフィールドの初期データ作成支援関数
 */
function gi_ensure_grant_meta_fields($post_id) {
    $required_fields = array(
        'grant_difficulty' => 'normal',
        'grant_success_rate' => rand(45, 85),
        'subsidy_rate' => '2/3',
        'grant_target' => '中小企業',
        'is_featured' => false,
        'views_count' => rand(50, 500),
        'application_method' => 'オンライン申請',
        'eligible_expenses' => '設備費、人件費等',
        'contact_info' => '担当窓口まで'
    );
    
    foreach ($required_fields as $field => $default_value) {
        $current_value = get_post_meta($post_id, $field, true);
        if (empty($current_value) && $current_value !== '0' && $current_value !== 0) {
            update_post_meta($post_id, $field, $default_value);
        }
    }
}

/**
 * 【追加】助成金投稿保存時に自動でメタフィールドを補完
 */
function gi_auto_populate_grant_meta($post_id, $post, $update) {
    if ($post->post_type !== 'grant') {
        return;
    }
    gi_ensure_grant_meta_fields($post_id);
}
add_action('save_post', 'gi_auto_populate_grant_meta', 10, 3);

/**
 * 【追加】既存の助成金投稿に一括でメタフィールドを追加する関数
 */
function gi_bulk_update_grant_meta() {
    if (!current_user_can('manage_options')) {
        return 0;
    }
    
    $grants = get_posts(array(
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $updated_count = 0;
    foreach ($grants as $grant) {
        gi_ensure_grant_meta_fields($grant->ID);
        $updated_count++;
    }
    
    return $updated_count;
}

/**
 * 【追加】管理画面用：メタフィールド一括更新ボタン
 */
function gi_add_grant_meta_update_button() {
    if (isset($_GET['update_grant_meta']) && $_GET['update_grant_meta'] === '1') {
        if (wp_verify_nonce($_GET['_wpnonce'], 'update_grant_meta')) {
            $count = gi_bulk_update_grant_meta();
            add_action('admin_notices', function() use ($count) {
                echo '<div class="notice notice-success"><p>' . $count . '件の助成金にメタフィールドを追加しました。</p></div>';
            });
        }
    }
}
add_action('admin_init', 'gi_add_grant_meta_update_button');

/**
 * 【追加】セットアップ完了状況を確認する関数
 */
function gi_check_setup_status() {
    $status = array(
        'setup_completed' => get_option('gi_initial_setup_completed', false),
        'grants_count' => wp_count_posts('grant')->publish,
        'tools_count' => wp_count_posts('tool')->publish,
        'tips_count' => wp_count_posts('grant_tip')->publish,
        'prefectures_count' => wp_count_terms('grant_prefecture'),
        'categories_count' => wp_count_terms('grant_category')
    );
    
    return $status;
}

/**
 * 【追加】管理画面にセットアップ状況を表示
 */
function gi_add_setup_status_dashboard() {
    if (current_user_can('manage_options')) {
        $status = gi_check_setup_status();
        
        add_action('admin_notices', function() use ($status) {
            if ($status['setup_completed']) {
                $setup_date = date('Y年n月j日 H:i', $status['setup_completed']);
                echo '<div class="notice notice-success"><p>';
                echo '<strong>Grant Insight Perfect セットアップ完了</strong><br>';
                echo "完了日時: {$setup_date}<br>";
                echo "助成金: {$status['grants_count']}件、ツール: {$status['tools_count']}件、コツ: {$status['tips_count']}件";
                echo '</p></div>';
            }
        });
    }
}
add_action('admin_init', 'gi_add_setup_status_dashboard');

/**
 * テーマの動作確認用デバッグ関数
 */
function gi_debug_theme_status() {
    if (!current_user_can('administrator') || !defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $debug_info = array(
        'version' => GI_THEME_VERSION ?? '1.0.0',
        'setup_status' => gi_check_setup_status(),
        'post_types_exist' => array(
            'grant' => post_type_exists('grant'),
            'tool' => post_type_exists('tool'),
            'grant_tip' => post_type_exists('grant_tip')
        ),
        'taxonomies_exist' => array(
            'grant_category' => taxonomy_exists('grant_category'),
            'grant_prefecture' => taxonomy_exists('grant_prefecture'),
            'tool_category' => taxonomy_exists('tool_category'),
            'grant_tip_category' => taxonomy_exists('grant_tip_category')
        ),
        'functions_exist' => array(
            'gi_safe_get_meta' => function_exists('gi_safe_get_meta'),
            'gi_render_grant_card' => function_exists('gi_render_grant_card'),
        ),
        'ajax_actions_exist' => array(
            'gi_load_grants' => has_action('wp_ajax_gi_load_grants'),
            'gi_toggle_favorite' => has_action('wp_ajax_gi_toggle_favorite')
        )
    );
    
    error_log('Grant Insight Debug Status: ' . print_r($debug_info, true));
}
add_action('init', 'gi_debug_theme_status', 999);

/**
 * テーマ無効化時のクリーンアップ（オプション）
 */
function gi_theme_deactivation_cleanup() {
    if (defined('GI_DELETE_DATA_ON_DEACTIVATION') && GI_DELETE_DATA_ON_DEACTIVATION) {
        $sample_posts = get_posts(array(
            'post_type' => array('grant', 'tool', 'grant_tip'),
            'posts_per_page' => -1,
            'post_status' => 'any',
            's' => '【サンプル】'
        ));
        
        foreach ($sample_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        delete_option('gi_initial_setup_completed');
        delete_option('gi_newsletter_list');
        delete_option('gi_affiliate_clicks');
    }
}

/**
 * アップグレード処理用の関数
 */
function gi_theme_upgrade_check() {
    $current_version = get_option('gi_theme_version', '0.0.0');
    $theme_version = GI_THEME_VERSION ?? '1.0.0';
    
    if (version_compare($current_version, $theme_version, '<')) {
        gi_theme_upgrade_process($current_version, $theme_version);
        update_option('gi_theme_version', $theme_version);
    }
}
add_action('init', 'gi_theme_upgrade_check');

/**
 * アップグレード処理の実装
 */
function gi_theme_upgrade_process($old_version, $new_version) {
    if (version_compare($old_version, '1.0.0', '<')) {
        gi_bulk_update_grant_meta();
    }
    
    gi_insert_tool_categories();
    gi_insert_grant_tip_categories();
    
    error_log("Grant Insight Theme upgraded from {$old_version} to {$new_version}");
}

/**
 * 【追加】管理画面用のセットアップ再実行ボタン
 */
function gi_add_admin_setup_tools() {
    if (current_user_can('manage_options')) {
        add_action('admin_menu', function() {
            add_management_page(
                'Grant Insight セットアップ',
                'Grant Insight セットアップ',
                'manage_options',
                'gi-setup',
                'gi_admin_setup_page'
            );
        });
    }
}
add_action('admin_init', 'gi_add_admin_setup_tools');

/**
 * 管理画面のセットアップページ
 */
function gi_admin_setup_page() {
    if (isset($_POST['run_setup'])) {
        if (wp_verify_nonce($_POST['_wpnonce'], 'gi_run_setup')) {
            gi_theme_activation_setup();
            echo '<div class="notice notice-success"><p>セットアップを実行しました。</p></div>';
        }
    }
    
    $status = gi_check_setup_status();
    ?>
    <div class="wrap">
        <h1>Grant Insight Perfect セットアップ</h1>
        
        <div class="card">
            <h2>セットアップ状況</h2>
            <table class="widefat">
                <tr><td>セットアップ完了</td><td><?php echo $status['setup_completed'] ? '完了' : '未完了'; ?></td></tr>
                <tr><td>助成金投稿数</td><td><?php echo $status['grants_count']; ?>件</td></tr>
                <tr><td>ツール投稿数</td><td><?php echo $status['tools_count']; ?>件</td></tr>
                <tr><td>コツ投稿数</td><td><?php echo $status['tips_count']; ?>件</td></tr>
                <tr><td>都道府県数</td><td><?php echo $status['prefectures_count']; ?>個</td></tr>
                <tr><td>カテゴリー数</td><td><?php echo $status['categories_count']; ?>個</td></tr>
            </table>
        </div>
        
        <div class="card">
            <h2>セットアップの実行</h2>
            <form method="post">
                <?php wp_nonce_field('gi_run_setup'); ?>
                <p>初期データの投入やサンプル投稿の作成を行います。</p>
                <button type="submit" name="run_setup" class="button button-primary">セットアップを実行</button>
            </form>
        </div>
        
        <div class="card">
            <h2>メタフィールドの一括更新</h2>
            <p>既存の助成金投稿に新しいメタフィールドを追加します。</p>
            <a href="<?php echo wp_nonce_url(admin_url('tools.php?page=gi-setup&update_grant_meta=1'), 'update_grant_meta'); ?>" 
               class="button">メタフィールドを更新</a>
        </div>
    </div>
    <?php
}

// =============================================================================
// 追加機能・最適化・統合検索システム
// =============================================================================

/**
 * 統合検索システムのアセット読み込み
 */
function gi_enqueue_unified_search_assets() {
    wp_enqueue_script(
        'gi-unified-search',
        get_template_directory_uri() . '/assets/js/unified-search.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'gi-unified-search-style',
        get_template_directory_uri() . '/assets/css/unified-search.css',
        array(),
        '1.0.0'
    );
    
    wp_localize_script('gi-unified-search', 'giSearchConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'grantsUrl' => home_url('/grants/'),
        'homeUrl' => home_url('/'),
        'debug' => WP_DEBUG,
        'isGrantsPage' => is_page('grants') || is_post_type_archive('grant'),
        'currentPage' => array(
            'type' => get_post_type(),
            'id' => get_the_ID(),
            'is_archive' => is_archive(),
            'is_single' => is_single()
        )
    ));
}
add_action('wp_enqueue_scripts', 'gi_enqueue_unified_search_assets', 20);

/**
 * AJAXエンドポイントの権限設定
 */
function gi_ajax_permissions() {
    add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');
    add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
    add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
    add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
    add_action('wp_ajax_nopriv_gi_get_search_suggestions', 'gi_ajax_get_search_suggestions');
    add_action('wp_ajax_gi_get_search_suggestions', 'gi_ajax_get_search_suggestions');
}
add_action('init', 'gi_ajax_permissions');

/**
 * 助成金一覧ページのリライトルール
 */
function gi_add_rewrite_rules() {
    add_rewrite_rule(
        '^grants/?$',
        'index.php?post_type=grant',
        'top'
    );
    
    add_rewrite_rule(
        '^grants/search/([^/]+)/?$',
        'index.php?post_type=grant&search=$matches[1]',
        'top'
    );
}
add_action('init', 'gi_add_rewrite_rules');

/**
 * パーマリンクのフラッシュ（テーマ有効化時）
 */
function gi_flush_rewrite_rules() {
    gi_add_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'gi_flush_rewrite_rules');

/**
 * 検索結果のハイライト機能
 */
function gi_highlight_search_terms($text, $search_terms) {
    if (empty($search_terms)) return $text;
    
    $terms = explode(' ', $search_terms);
    foreach ($terms as $term) {
        if (strlen($term) > 2) {
            $text = preg_replace(
                '/(' . preg_quote($term, '/') . ')/iu',
                '<mark class="search-highlight">$1</mark>',
                $text
            );
        }
    }
    return $text;
}

/**
 * 検索履歴の保存
 */
function gi_save_search_history() {
    if (!isset($_POST['search_term'])) return;
    
    $search_term = sanitize_text_field($_POST['search_term']);
    $user_id = get_current_user_id();
    
    if ($user_id) {
        $history = get_user_meta($user_id, 'search_history', true) ?: array();
        array_unshift($history, array(
            'term' => $search_term,
            'date' => current_time('mysql')
        ));
        $history = array_slice($history, 0, 10);
        update_user_meta($user_id, 'search_history', $history);
    } else {
        setcookie('gi_search_history', json_encode($search_term), time() + DAY_IN_SECONDS * 30, '/');
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_gi_save_search_history', 'gi_save_search_history');
add_action('wp_ajax_nopriv_gi_save_search_history', 'gi_save_search_history');

/**
 * テーマの最終初期化
 */
function gi_final_init() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Grant Insight Theme v6.2.2: Complete unified functions.php loaded successfully');
    }
}
add_action('wp_loaded', 'gi_final_init', 999);

/**
 * クリーンアップ処理
 */
function gi_theme_cleanup() {
    delete_option('gi_login_attempts');
    wp_cache_flush();
}
add_action('switch_theme', 'gi_theme_cleanup');

/**
 * スクリプトにdefer属性を追加
 */
function gi_add_defer_attribute_to_scripts($tag, $handle, $src) {
    if (is_admin() || strpos($src, 'wp-includes/js/') !== false) {
        return $tag;
    }

    if (strpos($tag, 'defer') === false && strpos($tag, 'async') === false) {
        return str_replace('<script', '<script defer', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'gi_add_defer_attribute_to_scripts', 10, 3);

if (WP_DEBUG) {
    error_log('Grant Insight Perfect unified functions.php loaded - All 8 files integrated successfully');
}

/* テーマの統合完了 */
?>
