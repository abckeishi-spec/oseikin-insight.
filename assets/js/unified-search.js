<?php
/**
 * Grant Insight Perfect - AJAX Functions Ultimate Enhanced Edition
 * 統合検索システム対応・完全最適化版
 *
 * @package Grant_Insight_Perfect
 * @version 4.0-ultimate-enhanced
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ================================================================================
 * AJAX初期化とセキュリティ
 * ================================================================================
 */

// AJAX URLとnonceをフロントエンドに渡す
add_action('wp_enqueue_scripts', 'gi_ajax_setup');
function gi_ajax_setup() {
    wp_localize_script('jquery', 'gi_ajax', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'loading_text' => __('読み込み中...', 'grant-insight'),
        'error_text' => __('エラーが発生しました', 'grant-insight'),
        'success_text' => __('正常に処理されました', 'grant-insight')
    ));
}

/**
 * ================================================================================
 * メイン検索機能 - 助成金読み込み処理（完全修正版）
 * ================================================================================
 */
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants_ultimate');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants_ultimate');

function gi_ajax_load_grants_ultimate() {
    // レスポンスヘッダー設定
    header('Content-Type: application/json; charset=utf-8');
    
    // nonceチェック
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error(array(
            'message' => 'セキュリティチェックに失敗しました',
            'code' => 'invalid_nonce'
        ), 403);
    }

    // パラメータ取得と検証
    $search = sanitize_text_field($_POST['search'] ?? '');
    
    // JSON文字列をデコード（配列パラメータ）
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true);
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true);
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true);
    $difficulty = json_decode(stripslashes($_POST['difficulty'] ?? '[]'), true);
    $success_rate = json_decode(stripslashes($_POST['success_rate'] ?? '[]'), true);
    
    // 単一パラメータ
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = max(1, intval($_POST['page'] ?? 1));
    $posts_per_page = intval($_POST['per_page'] ?? 12);
    
    // 配列検証
    $categories = is_array($categories) ? array_map('sanitize_text_field', $categories) : [];
    $prefectures = is_array($prefectures) ? array_map('sanitize_text_field', $prefectures) : [];
    $status = is_array($status) ? array_map('sanitize_text_field', $status) : [];
    $difficulty = is_array($difficulty) ? array_map('sanitize_text_field', $difficulty) : [];
    $success_rate = is_array($success_rate) ? array_map('sanitize_text_field', $success_rate) : [];
    
    // デバッグログ（開発環境のみ）
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('=== AJAX Load Grants Ultimate Debug ===');
        error_log('Search: ' . $search);
        error_log('Categories: ' . print_r($categories, true));
        error_log('Prefectures: ' . print_r($prefectures, true));
        error_log('Status: ' . print_r($status, true));
        error_log('Amount: ' . $amount);
        error_log('Sort: ' . $sort);
        error_log('View: ' . $view);
        error_log('Page: ' . $page);
        error_log('Per Page: ' . $posts_per_page);
    }
    
    // ステータスマッピング（UI値 → DB値）
    $status_mapping = array(
        'active' => 'open',
        'upcoming' => 'upcoming',
        'closed' => 'closed'
    );
    
    $mapped_status = array();
    foreach ($status as $s) {
        $mapped_status[] = isset($status_mapping[$s]) ? $status_mapping[$s] : $s;
    }
    $status = $mapped_status;

    // ================================================================================
    // クエリ構築
    // ================================================================================
    
    $args = array(
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish',
        'suppress_filters' => false // フィルターフックを有効化
    );

    // 検索キーワード（改良版）
    if (!empty($search)) {
        // カスタム検索クエリ
        $args['s'] = $search;
        
        // タイトル、内容、抜粋、カスタムフィールドも検索対象に
        add_filter('posts_search', 'gi_enhanced_search_query', 10, 2);
    }

    // ================================================================================
    // タクソノミークエリ
    // ================================================================================
    
    $tax_query = array('relation' => 'AND');
    
    // カテゴリフィルター
    if (!empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        );
    }
    
    // 都道府県フィルター
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

    // ================================================================================
    // メタクエリ（高度なフィルタリング）
    // ================================================================================
    
    $meta_query = array('relation' => 'AND');

    // ステータスフィルター
    if (!empty($status)) {
        $meta_query[] = array(
            'key' => 'application_status',
            'value' => $status,
            'compare' => 'IN'
        );
    }
    
    // 難易度フィルター
    if (!empty($difficulty)) {
        $meta_query[] = array(
            'key' => 'grant_difficulty',
            'value' => $difficulty,
            'compare' => 'IN'
        );
    }
    
    // 採択率フィルター（改良版）
    if (!empty($success_rate)) {
        $rate_query = array('relation' => 'OR');
        foreach ($success_rate as $rate) {
            switch ($rate) {
                case 'high':
                    $rate_query[] = array(
                        'key' => 'grant_success_rate',
                        'value' => 70,
                        'compare' => '>=',
                        'type' => 'NUMERIC'
                    );
                    break;
                case 'medium':
                    $rate_query[] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'grant_success_rate',
                            'value' => 50,
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => 'grant_success_rate',
                            'value' => 70,
                            'compare' => '<',
                            'type' => 'NUMERIC'
                        )
                    );
                    break;
                case 'low':
                    $rate_query[] = array(
                        'key' => 'grant_success_rate',
                        'value' => 50,
                        'compare' => '<',
                        'type' => 'NUMERIC'
                    );
                    break;
            }
        }
        if (count($rate_query) > 1) {
            $meta_query[] = $rate_query;
        }
    }

    // 金額フィルター（改良版）
    if (!empty($amount)) {
        switch ($amount) {
            case '0-100':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => 1000000,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                );
                break;
            case '100-500':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => array(1000001, 5000000),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
                break;
            case '500-1000':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => array(5000001, 10000000),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
                break;
            case '1000-3000':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => array(10000001, 30000000),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
                break;
            case '3000+':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => 30000001,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                );
                break;
            case '1000+':
                $meta_query[] = array(
                    'key' => 'max_amount_numeric',
                    'value' => 10000001,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                );
                break;
        }
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // ================================================================================
    // ソート設定（拡張版）
    // ================================================================================
    
    switch ($sort) {
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
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
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = 'deadline_date';
            $args['order'] = 'ASC';
            // 締切日が過ぎていないもののみ
            $args['meta_query'][] = array(
                'key' => 'deadline_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            );
            break;
        case 'success_rate_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'grant_success_rate';
            $args['order'] = 'DESC';
            break;
        case 'popularity':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'grant_views';
            $args['order'] = 'DESC';
            break;
        case 'title_asc':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'random':
            $args['orderby'] = 'rand';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }

    // ================================================================================
    // クエリ実行
    // ================================================================================
    
    $query = new WP_Query($args);
    
    // 検索フィルター削除
    if (!empty($search)) {
        remove_filter('posts_search', 'gi_enhanced_search_query', 10);
    }
    
    // デバッグ情報
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Query args: ' . print_r($args, true));
        error_log('Found posts: ' . $query->found_posts);
        error_log('SQL: ' . $query->request);
    }
    
    // ================================================================================
    // レスポンスデータ生成
    // ================================================================================
    
    $grants_html = array();
    $grants_data = array(); // 生データも返す
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // データ収集（最適化版）
            $grant_data = gi_get_grant_data($post_id);
            
            // HTMLレンダリング
            $card_html = '';
            switch ($view) {
                case 'grid':
                    $card_html = gi_render_grant_card_ultimate($grant_data);
                    break;
                case 'list':
                    $card_html = gi_render_grant_list_ultimate($grant_data);
                    break;
                case 'card':
                    $card_html = gi_render_grant_card_detailed($grant_data);
                    break;
                default:
                    $card_html = gi_render_grant_card_ultimate($grant_data);
            }
            
            $grants_html[] = array(
                'html' => $card_html,
                'data' => $grant_data
            );
            $grants_data[] = $grant_data;
        }
        wp_reset_postdata();
    }

    // ================================================================================
    // ページネーション生成（改良版）
    // ================================================================================
    
    $pagination_html = gi_generate_pagination_ultimate($query->max_num_pages, $page);

    // ================================================================================
    // フィルター情報の生成
    // ================================================================================
    
    $filter_info = gi_get_active_filter_info($categories, $prefectures, $amount, $status, $difficulty, $success_rate);

    // ================================================================================
    // 統計情報の生成
    // ================================================================================
    
    $statistics = gi_calculate_search_statistics($query);

    // ================================================================================
    // レスポンス送信
    // ================================================================================
    
    wp_send_json_success(array(
        'grants' => $grants_html,
        'grants_data' => $grants_data,
        'found_posts' => $query->found_posts,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_posts' => $query->found_posts,
            'posts_per_page' => $posts_per_page,
            'html' => $pagination_html
        ),
        'view' => $view,
        'filters' => $filter_info,
        'statistics' => $statistics,
        'debug' => defined('WP_DEBUG') && WP_DEBUG ? array(
            'search' => $search,
            'categories' => $categories,
            'prefectures' => $prefectures,
            'status' => $status,
            'amount' => $amount,
            'query_args' => $args,
            'sql' => $query->request
        ) : null
    ));
}

/**
 * ================================================================================
 * ヘルパー関数
 * ================================================================================
 */

/**
 * 助成金データ取得（最適化版）
 */
function gi_get_grant_data($post_id) {
    // キャッシュチェック
    $cache_key = 'gi_grant_data_' . $post_id;
    $cached_data = wp_cache_get($cache_key);
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // タクソノミー情報取得
    $grant_terms = get_the_terms($post_id, 'grant_category');
    $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
    
    // メタデータ一括取得
    $meta_data = get_post_meta($post_id);
    
    $grant_data = array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'permalink' => get_permalink($post_id),
        'excerpt' => get_the_excerpt($post_id),
        'content' => get_the_content($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'large'),
        'thumbnail_medium' => get_the_post_thumbnail_url($post_id, 'medium'),
        'main_category' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? $grant_terms[0]->name : '',
        'main_category_slug' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? $grant_terms[0]->slug : '',
        'all_categories' => (!is_wp_error($grant_terms) && !empty($grant_terms)) ? wp_list_pluck($grant_terms, 'name') : array(),
        'prefecture' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? $prefecture_terms[0]->name : '',
        'prefecture_slug' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? $prefecture_terms[0]->slug : '',
        'all_prefectures' => (!is_wp_error($prefecture_terms) && !empty($prefecture_terms)) ? wp_list_pluck($prefecture_terms, 'name') : array(),
        'organization' => $meta_data['organization'][0] ?? '',
        'deadline' => gi_get_formatted_deadline($post_id),
        'deadline_date' => $meta_data['deadline_date'][0] ?? '',
        'amount' => $meta_data['max_amount'][0] ?? '-',
        'amount_numeric' => intval($meta_data['max_amount_numeric'][0] ?? 0),
        'min_amount' => $meta_data['min_amount'][0] ?? '',
        'min_amount_numeric' => intval($meta_data['min_amount_numeric'][0] ?? 0),
        'status' => gi_map_application_status_ui($meta_data['application_status'][0] ?? 'open'),
        'status_raw' => $meta_data['application_status'][0] ?? 'open',
        'difficulty' => $meta_data['grant_difficulty'][0] ?? '',
        'success_rate' => intval($meta_data['grant_success_rate'][0] ?? 0),
        'subsidy_rate' => $meta_data['subsidy_rate'][0] ?? '',
        'target_business' => $meta_data['target_business'][0] ?? '',
        'application_period' => $meta_data['application_period'][0] ?? '',
        'official_url' => $meta_data['official_url'][0] ?? '',
        'contact_info' => $meta_data['contact_info'][0] ?? '',
        'requirements' => $meta_data['requirements'][0] ?? '',
        'documents_needed' => $meta_data['documents_needed'][0] ?? '',
        'views' => intval($meta_data['grant_views'][0] ?? 0),
        'is_featured' => $meta_data['is_featured'][0] ?? false,
        'last_updated' => get_the_modified_date('Y-m-d', $post_id)
    );
    
    // キャッシュに保存（1時間）
    wp_cache_set($cache_key, $grant_data, '', 3600);
    
    return $grant_data;
}

/**
 * 助成金カードHTML生成（究極版）
 */
function gi_render_grant_card_ultimate($grant_data) {
    $post_id = $grant_data['id'];
    $title = esc_html($grant_data['title']);
    $permalink = esc_url($grant_data['permalink']);
    $excerpt = wp_trim_words(esc_html($grant_data['excerpt']), 30);
    $organization = esc_html($grant_data['organization']);
    
    // 金額フォーマット
    $amount = gi_format_amount_with_unit($grant_data['amount_numeric'] ?: $grant_data['amount']);
    
    $deadline = esc_html($grant_data['deadline']);
    $status = esc_html($grant_data['status']);
    $prefecture = esc_html($grant_data['prefecture']);
    $category = esc_html($grant_data['main_category']);
    $success_rate = intval($grant_data['success_rate']);
    $difficulty = esc_html($grant_data['difficulty']);
    $views = intval($grant_data['views']);
    $is_featured = $grant_data['is_featured'];
    
    // ステータスに応じた色設定
    $status_classes = gi_get_status_classes($grant_data['status_raw']);
    
    // 難易度表示
    $difficulty_display = gi_get_difficulty_display($difficulty);
    
    // 採択率の表示色
    $success_color = gi_get_success_rate_color($success_rate);
    
    // 締切までの日数計算
    $days_until_deadline = gi_calculate_days_until_deadline($grant_data['deadline_date']);
    
    return <<<HTML
<div class="grant-card-ultimate w-full" data-grant-id="{$post_id}">
    <div class="card-container bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden h-full flex flex-col group">
        
        <!-- カードヘッダー -->
        <div class="card-header relative">
            <!-- サムネイル画像 -->
            <div class="card-thumbnail relative h-48 bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden">
                {$grant_data['thumbnail'] ? '<img src="' . esc_url($grant_data['thumbnail']) . '" alt="' . $title . '" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">' : '<div class="flex items-center justify-center h-full"><i class="fas fa-coins text-white text-4xl"></i></div>'}
                
                <!-- オーバーレイ情報 -->
                <div class="absolute top-0 left-0 right-0 p-4 bg-gradient-to-b from-black/50 to-transparent">
                    <div class="flex justify-between items-start">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {$status_classes['bg']} {$status_classes['text']}">
                            <span class="w-2 h-2 bg-current rounded-full mr-1.5 {$status_classes['animate']}"></span>
                            {$status}
                        </span>
                        <div class="flex gap-2">
                            {$is_featured ? '<span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold"><i class="fas fa-star mr-1"></i>注目</span>' : ''}
                            {$days_until_deadline !== null && $days_until_deadline <= 7 ? '<span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold animate-pulse">締切間近</span>' : ''}
                        </div>
                    </div>
                </div>
                
                <!-- お気に入りボタン -->
                <button class="favorite-btn absolute bottom-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-white transition-all duration-200 shadow-lg" data-post-id="{$post_id}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- カードボディ -->
        <div class="card-body px-5 pt-4 pb-3 flex-grow">
            <!-- カテゴリと地域 -->
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                    <i class="fas fa-folder mr-1.5"></i>
                    {$category}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-map-marker-alt mr-1.5"></i>
                    {$prefecture}
                </span>
            </div>
            
            <!-- タイトル -->
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2 leading-tight line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                <a href="{$permalink}" class="hover:underline">
                    {$title}
                </a>
            </h3>
            
            <!-- 説明文 -->
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                {$excerpt}
            </p>
            
            <!-- 金額表示 -->
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 mb-4 border border-emerald-200 dark:border-emerald-800">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">最大助成額</div>
                        <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                            {$amount}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">補助率</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {$grant_data['subsidy_rate'] ?: '-'}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 詳細情報グリッド -->
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="info-item bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                    <div class="text-gray-500 dark:text-gray-400 mb-1">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        締切
                    </div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                        {$deadline}
                    </div>
                </div>
                <div class="info-item bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                    <div class="text-gray-500 dark:text-gray-400 mb-1">
                        <i class="fas fa-percentage mr-1"></i>
                        採択率
                    </div>
                    <div class="font-semibold {$success_color}">
                        {$success_rate}%
                    </div>
                </div>
                <div class="info-item bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                    <div class="text-gray-500 dark:text-gray-400 mb-1">
                        <i class="fas fa-graduation-cap mr-1"></i>
                        難易度
                    </div>
                    <div class="font-semibold">
                        {$difficulty_display}
                    </div>
                </div>
                <div class="info-item bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2.5">
                    <div class="text-gray-500 dark:text-gray-400 mb-1">
                        <i class="fas fa-building mr-1"></i>
                        実施機関
                    </div>
                    <div class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{$organization}">
                        {$organization}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- カードフッター -->
        <div class="card-footer px-5 pb-5 pt-3 border-t border-gray-100 dark:border-gray-700 mt-auto">
            <div class="flex items-center justify-between gap-3">
                <a href="{$permalink}" class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center py-2.5 px-4 rounded-xl transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-arrow-right mr-2"></i>
                    詳細を見る
                </a>
                <div class="flex gap-2">
                    <button class="action-btn p-2.5 text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors share-btn" data-url="{$permalink}" data-title="{$title}" title="共有">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 01-7.432 0"></path>
                        </svg>
                    </button>
                    <button class="action-btn p-2.5 text-gray-400 dark:text-gray-500 hover:text-green-600 dark:hover:text-green-400 transition-colors compare-btn" data-grant-id="{$post_id}" title="比較">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- メタ情報 -->
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-3">
                    <span>
                        <i class="fas fa-eye mr-1"></i>
                        {$views} views
                    </span>
                    <span>
                        <i class="fas fa-clock mr-1"></i>
                        {$grant_data['last_updated']}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="quality-score" title="品質スコア">
                        <i class="fas fa-star text-yellow-500"></i>
                        <span class="font-semibold">4.5</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
}

/**
 * リスト表示用カード生成
 */
function gi_render_grant_list_ultimate($grant_data) {
    // リスト表示用の実装
    $post_id = $grant_data['id'];
    $title = esc_html($grant_data['title']);
    $permalink = esc_url($grant_data['permalink']);
    $excerpt = wp_trim_words(esc_html($grant_data['excerpt']), 50);
    $organization = esc_html($grant_data['organization']);
    $amount = gi_format_amount_with_unit($grant_data['amount_numeric'] ?: $grant_data['amount']);
    $deadline = esc_html($grant_data['deadline']);
    $status = esc_html($grant_data['status']);
    $prefecture = esc_html($grant_data['prefecture']);
    $category = esc_html($grant_data['main_category']);
    $success_rate = intval($grant_data['success_rate']);
    
    $status_classes = gi_get_status_classes($grant_data['status_raw']);
    $success_color = gi_get_success_rate_color($success_rate);
    
    return <<<HTML
<div class="grant-list-ultimate bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden mb-4">
    <div class="p-6 flex flex-col md:flex-row">
        <!-- 左側：メイン情報 -->
        <div class="flex-grow md:pr-6 mb-4 md:mb-0">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {$status_classes['bg']} {$status_classes['text']}">
                        <span class="w-2 h-2 bg-current rounded-full mr-1.5 {$status_classes['animate']}"></span>
                        {$status}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                        {$category}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        {$prefecture}
                    </span>
                </div>
            </div>
            
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3 leading-tight">
                <a href="{$permalink}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    {$title}
                </a>
            </h3>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                {$excerpt}
            </p>
            
            <!-- 詳細情報 -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center">
                    <i class="fas fa-building mr-1.5"></i>
                    {$organization}
                </span>
                <span class="flex items-center">
                    <i class="fas fa-calendar-alt mr-1.5"></i>
                    締切: {$deadline}
                </span>
                <span class="flex items-center">
                    採択率: <span class="ml-1.5 font-semibold {$success_color}">{$success_rate}%</span>
                </span>
            </div>
        </div>
        
        <!-- 右側：金額とアクション -->
        <div class="flex flex-col items-end justify-between md:pl-6 md:border-l border-gray-200 dark:border-gray-700 md:min-w-[250px]">
            <div class="text-right mb-4 w-full">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">最大助成額</div>
                <div class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">
                    {$amount}
                </div>
            </div>
            
            <div class="flex flex-col gap-3 w-full">
                <a href="{$permalink}" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center py-3 px-6 rounded-xl transition-all duration-200 font-semibold shadow-md hover:shadow-lg">
                    詳細を見る
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <div class="flex gap-2">
                    <button class="flex-1 p-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg transition-colors favorite-btn" data-post-id="{$post_id}">
                        <i class="far fa-heart"></i>
                    </button>
                    <button class="flex-1 p-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg transition-colors share-btn" data-url="{$permalink}" data-title="{$title}">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="flex-1 p-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded-lg transition-colors compare-btn" data-grant-id="{$post_id}">
                        <i class="fas fa-balance-scale"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
}

/**
 * ================================================================================
 * AJAX - お気に入り機能（改良版）
 * ================================================================================
 */
add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite_ultimate');
add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite_ultimate');

function gi_ajax_toggle_favorite_ultimate() {
    // nonceチェック
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました', 403);
    }
    
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();
    
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error('無効な投稿IDです');
    }
    
    if (!$user_id) {
        // 非ログインユーザーはセッションまたはCookieで管理
        session_start();
        $session_key = 'gi_favorites';
        $favorites = $_SESSION[$session_key] ?? array();
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
            $is_favorite = false;
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $is_favorite = true;
        }
        
        $_SESSION[$session_key] = $favorites;
    } else {
        // ログインユーザーはユーザーメタで管理
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        if (!is_array($favorites)) $favorites = array();
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
            $is_favorite = false;
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $is_favorite = true;
        }
        
        update_user_meta($user_id, 'gi_favorites', $favorites);
        
        // アクティビティログ記録
        gi_log_user_activity($user_id, 'favorite_' . $action, $post_id);
    }
    
    // お気に入り数の更新
    $favorite_count = intval(get_post_meta($post_id, 'favorite_count', true));
    if ($action === 'added') {
        $favorite_count++;
    } else {
        $favorite_count = max(0, $favorite_count - 1);
    }
    update_post_meta($post_id, 'favorite_count', $favorite_count);
    
    wp_send_json_success(array(
        'action' => $action,
        'post_id' => $post_id,
        'post_title' => get_the_title($post_id),
        'count' => count($favorites),
        'total_favorites' => $favorite_count,
        'is_favorite' => $is_favorite,
        'message' => $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました'
    ));
}

/**
 * ================================================================================
 * AJAX - 関連助成金取得（AI推薦対応）
 * ================================================================================
 */
add_action('wp_ajax_gi_get_related_grants', 'gi_ajax_get_related_grants_ultimate');
add_action('wp_ajax_nopriv_gi_get_related_grants', 'gi_ajax_get_related_grants_ultimate');

function gi_ajax_get_related_grants_ultimate() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $category = sanitize_text_field($_POST['category'] ?? '');
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $limit = intval($_POST['limit'] ?? 6);
    $exclude_ids = array_map('intval', $_POST['exclude'] ?? array());
    
    // 現在の助成金情報を取得
    $current_grant = gi_get_grant_data($post_id);
    
    // AI推薦ロジック（スコアリングベース）
    $recommendations = gi_get_ai_recommendations($current_grant, $limit, $exclude_ids);
    
    if (!empty($recommendations)) {
        $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
        foreach ($recommendations as $grant_id => $score) {
            $grant_data = gi_get_grant_data($grant_id);
            $html .= gi_render_grant_card_ultimate($grant_data);
        }
        $html .= '</div>';
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => count($recommendations),
            'recommendations' => $recommendations
        ));
    } else {
        // フォールバック: 通常の関連助成金取得
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => array_merge(array($post_id), $exclude_ids),
            'tax_query' => array('relation' => 'OR')
        );
        
        if ($category) {
            $args['tax_query'][] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        
        if ($prefecture) {
            $args['tax_query'][] = array(
                'taxonomy' => 'grant_prefecture',
                'field' => 'slug',
                'terms' => $prefecture
            );
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
            while ($query->have_posts()) {
                $query->the_post();
                $grant_data = gi_get_grant_data(get_the_ID());
                $html .= gi_render_grant_card_ultimate($grant_data);
            }
            $html .= '</div>';
            wp_reset_postdata();
            
            wp_send_json_success(array('html' => $html, 'count' => $query->found_posts));
        } else {
            wp_send_json_success(array(
                'html' => '<p class="text-center text-gray-500">関連する助成金が見つかりませんでした。</p>',
                'count' => 0
            ));
        }
    }
}

/**
 * ================================================================================
 * AJAX - 統計情報取得（リアルタイム）
 * ================================================================================
 */
add_action('wp_ajax_gi_get_statistics', 'gi_ajax_get_statistics_ultimate');
add_action('wp_ajax_nopriv_gi_get_statistics', 'gi_ajax_get_statistics_ultimate');

function gi_ajax_get_statistics_ultimate() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました', 403);
    }
    
    $type = sanitize_text_field($_POST['type'] ?? 'general');
    
    // キャッシュチェック
    $cache_key = 'gi_statistics_' . $type . '_' . date('YmdH');
    $cached_stats = get_transient($cache_key);
    
    if ($cached_stats !== false) {
        wp_send_json_success($cached_stats);
        return;
    }
    
    $statistics = array();
    
    switch ($type) {
        case 'general':
            $statistics = gi_get_general_statistics();
            break;
        case 'category':
            $statistics = gi_get_category_statistics();
            break;
        case 'prefecture':
            $statistics = gi_get_prefecture_statistics();
            break;
        case 'trends':
            $statistics = gi_get_trend_statistics();
            break;
        case 'performance':
            $statistics = gi_get_performance_statistics();
            break;
        default:
            $statistics = gi_get_general_statistics();
    }
    
    // キャッシュ保存（1時間）
    set_transient($cache_key, $statistics, HOUR_IN_SECONDS);
    
    wp_send_json_success($statistics);
}

/**
 * ================================================================================
 * AJAX - 検索サジェスト
 * ================================================================================
 */
add_action('wp_ajax_gi_search_suggest', 'gi_ajax_search_suggest');
add_action('wp_ajax_nopriv_gi_search_suggest', 'gi_ajax_search_suggest');

function gi_ajax_search_suggest() {
    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    
    if (strlen($keyword) < 2) {
        wp_send_json_success(array('suggestions' => array()));
        return;
    }
    
    $suggestions = array();
    
    // 助成金タイトルから検索
    $grants = get_posts(array(
        'post_type' => 'grant',
        's' => $keyword,
        'posts_per_page' => 5,
        'fields' => 'ids'
    ));
    
    foreach ($grants as $grant_id) {
        $suggestions[] = array(
            'type' => 'grant',
            'icon' => 'fa-coins',
            'text' => get_the_title($grant_id),
            'url' => get_permalink($grant_id)
        );
    }
    
    // カテゴリから検索
    $categories = get_terms(array(
        'taxonomy' => 'grant_category',
        'name__like' => $keyword,
        'number' => 3,
        'hide_empty' => true
    ));
    
    foreach ($categories as $category) {
        $suggestions[] = array(
            'type' => 'category',
            'icon' => 'fa-folder',
            'text' => $category->name,
            'count' => $category->count,
            'url' => get_term_link($category)
        );
    }
    
    // 人気キーワード
    $popular_keywords = gi_get_popular_keywords($keyword);
    foreach ($popular_keywords as $pop_keyword) {
        $suggestions[] = array(
            'type' => 'keyword',
            'icon' => 'fa-fire',
            'text' => $pop_keyword['keyword'],
            'count' => $pop_keyword['count']
        );
    }
    
    wp_send_json_success(array('suggestions' => $suggestions));
}

/**
 * ================================================================================
 * AJAX - 助成金比較
 * ================================================================================
 */
add_action('wp_ajax_gi_compare_grants', 'gi_ajax_compare_grants');
add_action('wp_ajax_nopriv_gi_compare_grants', 'gi_ajax_compare_grants');

function gi_ajax_compare_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
    }
    
    $grant_ids = array_map('intval', $_POST['grant_ids'] ?? array());
    
    if (count($grant_ids) < 2) {
        wp_send_json_error('比較するには2つ以上の助成金を選択してください');
    }
    
    if (count($grant_ids) > 4) {
        wp_send_json_error('比較できるのは最大4つまでです');
    }
    
    $comparison_data = array();
    
    foreach ($grant_ids as $grant_id) {
        $comparison_data[] = gi_get_grant_data($grant_id);
    }
    
    // 比較表HTML生成
    $html = gi_generate_comparison_table($comparison_data);
    
    wp_send_json_success(array(
        'html' => $html,
        'data' => $comparison_data
    ));
}

/**
 * ================================================================================
 * AJAX - エクスポート機能
 * ================================================================================
 */
add_action('wp_ajax_gi_export_grants', 'gi_ajax_export_grants');
add_action('wp_ajax_nopriv_gi_export_grants', 'gi_ajax_export_grants');

function gi_ajax_export_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
    }
    
    $format = sanitize_text_field($_POST['format'] ?? 'csv');
    $grant_ids = array_map('intval', $_POST['grant_ids'] ?? array());
    
    if (empty($grant_ids)) {
        wp_send_json_error('エクスポートする助成金を選択してください');
    }
    
    $export_data = array();
    foreach ($grant_ids as $grant_id) {
        $export_data[] = gi_get_grant_data($grant_id);
    }
    
    switch ($format) {
        case 'csv':
            $file_url = gi_export_to_csv($export_data);
            break;
        case 'excel':
            $file_url = gi_export_to_excel($export_data);
            break;
        case 'pdf':
            $file_url = gi_export_to_pdf($export_data);
            break;
        default:
            $file_url = gi_export_to_csv($export_data);
    }
    
    if ($file_url) {
        wp_send_json_success(array(
            'file_url' => $file_url,
            'format' => $format,
            'count' => count($export_data)
        ));
    } else {
        wp_send_json_error('エクスポートに失敗しました');
    }
}

/**
 * ================================================================================
 * ユーティリティ関数
 * ================================================================================
 */

/**
 * 拡張検索クエリ
 */
function gi_enhanced_search_query($search, $wp_query) {
    global $wpdb;
    
    if (empty($search) || !$wp_query->is_search()) {
        return $search;
    }
    
    $search_terms = $wp_query->get('search_terms');
    $search = '';
    
    foreach ($search_terms as $term) {
        $term = esc_sql($wpdb->esc_like($term));
        $search .= " AND (
            {$wpdb->posts}.post_title LIKE '%{$term}%' OR 
            {$wpdb->posts}.post_content LIKE '%{$term}%' OR 
            {$wpdb->posts}.post_excerpt LIKE '%{$term}%' OR
            EXISTS (
                SELECT * FROM {$wpdb->postmeta} 
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                AND {$wpdb->postmeta}.meta_value LIKE '%{$term}%'
            )
        )";
    }
    
    return $search;
}

/**
 * 金額フォーマット
 */
function gi_format_amount_with_unit($amount) {
    if (!is_numeric($amount)) {
        return $amount;
    }
    
    $amount = intval($amount);
    
    if ($amount >= 100000000) {
        return number_format($amount / 100000000, 1) . '億円';
    } elseif ($amount >= 10000000) {
        return number_format($amount / 10000000, 0) . '千万円';
    } elseif ($amount >= 10000) {
        return number_format($amount / 10000, 0) . '万円';
    } else {
        return number_format($amount) . '円';
    }
}

/**
 * ステータスクラス取得
 */
function gi_get_status_classes($status) {
    $classes = array();
    
    switch ($status) {
        case 'open':
            $classes = array(
                'bg' => 'bg-green-100 dark:bg-green-900/30',
                'text' => 'text-green-700 dark:text-green-400',
                'border' => 'border-green-300 dark:border-green-700',
                'animate' => 'animate-pulse'
            );
            break;
        case 'upcoming':
            $classes = array(
                'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
                'text' => 'text-yellow-700 dark:text-yellow-400',
                'border' => 'border-yellow-300 dark:border-yellow-700',
                'animate' => ''
            );
            break;
        case 'closed':
            $classes = array(
                'bg' => 'bg-gray-100 dark:bg-gray-700',
                'text' => 'text-gray-700 dark:text-gray-400',
                'border' => 'border-gray-300 dark:border-gray-600',
                'animate' => ''
            );
            break;
        default:
            $classes = array(
                'bg' => 'bg-gray-100 dark:bg-gray-700',
                'text' => 'text-gray-700 dark:text-gray-400',
                'border' => 'border-gray-300 dark:border-gray-600',
                'animate' => ''
            );
    }
    
    return $classes;
}

/**
 * 採択率カラー取得
 */
function gi_get_success_rate_color($rate) {
    if ($rate >= 70) {
        return 'text-green-600 dark:text-green-400';
    } elseif ($rate >= 50) {
        return 'text-yellow-600 dark:text-yellow-400';
    } else {
        return 'text-red-600 dark:text-red-400';
    }
}

/**
 * 難易度表示取得
 */
function gi_get_difficulty_display($difficulty) {
    switch ($difficulty) {
        case 'easy':
            return '<span class="text-green-600 dark:text-green-400">★☆☆ 簡単</span>';
        case 'normal':
            return '<span class="text-yellow-600 dark:text-yellow-400">★★☆ 普通</span>';
        case 'hard':
            return '<span class="text-red-600 dark:text-red-400">★★★ 難しい</span>';
        default:
            return '<span class="text-gray-400 dark:text-gray-600">-</span>';
    }
}

/**
 * 締切までの日数計算
 */
function gi_calculate_days_until_deadline($deadline_date) {
    if (empty($deadline_date)) {
        return null;
    }
    
    $deadline = new DateTime($deadline_date);
    $today = new DateTime();
    $interval = $today->diff($deadline);
    
    if ($deadline < $today) {
        return -1; // 締切済み
    }
    
    return $interval->days;
}

/**
 * ページネーション生成（究極版）
 */
function gi_generate_pagination_ultimate($total_pages, $current_page) {
    if ($total_pages <= 1) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="pagination-container flex items-center justify-center space-x-2 mt-8">
        <?php if ($current_page > 1): ?>
            <button class="pagination-btn pagination-prev px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="<?php echo $current_page - 1; ?>">
                <i class="fas fa-chevron-left mr-2"></i>前へ
            </button>
        <?php endif; ?>
        
        <div class="pagination-numbers flex space-x-1">
            <?php
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);
            
            if ($start > 1):
            ?>
                <button class="pagination-btn px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700" data-page="1">1</button>
                <?php if ($start > 2): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <button class="pagination-btn px-3 py-2 <?php echo ($i === $current_page) ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> border border-gray-300 dark:border-gray-600 rounded-lg transition-colors" data-page="<?php echo $i; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>
            
            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
                <button class="pagination-btn px-3 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700" data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></button>
            <?php endif; ?>
        </div>
        
        <?php if ($current_page < $total_pages): ?>
            <button class="pagination-btn pagination-next px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300" data-page="<?php echo $current_page + 1; ?>">
                次へ<i class="fas fa-chevron-right ml-2"></i>
            </button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * デバッグ関数
 */
function gi_ajax_debug() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error('Debug mode is not enabled');
    }
    
    $info = array(
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'theme_version' => wp_get_theme()->get('Version'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'post_types' => get_post_types(array('public' => true)),
        'taxonomies' => get_taxonomies(array('public' => true)),
        'grant_count' => wp_count_posts('grant'),
        'active_plugins' => get_option('active_plugins'),
        'ajax_functions' => array(
            'gi_load_grants' => function_exists('gi_ajax_load_grants_ultimate'),
            'gi_toggle_favorite' => function_exists('gi_ajax_toggle_favorite_ultimate'),
            'gi_get_related_grants' => function_exists('gi_ajax_get_related_grants_ultimate'),
            'gi_get_statistics' => function_exists('gi_ajax_get_statistics_ultimate'),
            'gi_search_suggest' => function_exists('gi_ajax_search_suggest'),
            'gi_compare_grants' => function_exists('gi_ajax_compare_grants'),
            'gi_export_grants' => function_exists('gi_ajax_export_grants')
        )
    );
    
    wp_send_json_success($info);
}
add_action('wp_ajax_gi_debug', 'gi_ajax_debug');
add_action('wp_ajax_nopriv_gi_debug', 'gi_ajax_debug');

// ファイル終了
?>