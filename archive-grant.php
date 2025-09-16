<?php
/**
 * Grant Archive Template - Ultimate Enhanced Edition
 * Grant Insight Perfect - 統合検索システム究極版
 * 
 * @version 26.0-ultimate-enhanced
 * @package Grant_Insight_Perfect
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// URLパラメータから検索条件を取得（統合検索システムと連携）
$search_params = array(
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'category' => sanitize_text_field($_GET['category'] ?? ''),
    'prefecture' => sanitize_text_field($_GET['prefecture'] ?? ''),
    'amount' => sanitize_text_field($_GET['amount'] ?? ''),
    'status' => sanitize_text_field($_GET['status'] ?? ''),
    'difficulty' => sanitize_text_field($_GET['difficulty'] ?? ''),
    'success_rate' => sanitize_text_field($_GET['success_rate'] ?? ''),
    'orderby' => sanitize_text_field($_GET['orderby'] ?? 'date_desc'),
    'view' => sanitize_text_field($_GET['view'] ?? 'grid'),
    'page' => max(1, intval($_GET['page'] ?? 1))
);

// 統計データ取得（キャッシュ対応）
$stats_cache_key = 'gi_archive_stats_' . date('YmdH');
$stats = get_transient($stats_cache_key);

if (false === $stats) {
    $stats = array(
        'total_grants' => wp_count_posts('grant')->publish,
        'active_grants' => 0,
        'upcoming_grants' => 0,
        'closed_grants' => 0,
        'prefecture_count' => 0,
        'category_count' => 0,
        'avg_amount' => 0,
        'avg_success_rate' => 0
    );
    
    // アクティブな助成金をカウント
    $active_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'application_status',
                'value' => 'open',
                'compare' => '='
            )
        )
    ));
    $stats['active_grants'] = $active_query->found_posts;
    wp_reset_postdata();
    
    // 募集予定の助成金をカウント
    $upcoming_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'application_status',
                'value' => 'upcoming',
                'compare' => '='
            )
        )
    ));
    $stats['upcoming_grants'] = $upcoming_query->found_posts;
    wp_reset_postdata();
    
    // 終了した助成金をカウント
    $stats['closed_grants'] = $stats['total_grants'] - $stats['active_grants'] - $stats['upcoming_grants'];
    
    // 都道府県数を取得
    $prefecture_terms = get_terms(array(
        'taxonomy' => 'grant_prefecture',
        'hide_empty' => false
    ));
    $stats['prefecture_count'] = !is_wp_error($prefecture_terms) ? count($prefecture_terms) : 47;
    
    // カテゴリ数を取得
    $category_terms = get_terms(array(
        'taxonomy' => 'grant_category',
        'hide_empty' => false
    ));
    $stats['category_count'] = !is_wp_error($category_terms) ? count($category_terms) : 0;
    
    // 平均金額と採択率
    global $wpdb;
    $avg_amount = $wpdb->get_var("
        SELECT AVG(CAST(meta_value AS UNSIGNED)) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'max_amount_numeric' 
        AND meta_value != '' AND meta_value > 0
    ");
    $stats['avg_amount'] = round($avg_amount / 10000) ?: 0; // 万円単位
    
    $avg_success = $wpdb->get_var("
        SELECT AVG(CAST(meta_value AS UNSIGNED)) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'grant_success_rate' 
        AND meta_value != ''
    ");
    $stats['avg_success_rate'] = round($avg_success ?: 65);
    
    set_transient($stats_cache_key, $stats, HOUR_IN_SECONDS);
}

// カテゴリと都道府県の取得（日本語表示用）
$grant_categories = get_terms(array(
    'taxonomy' => 'grant_category',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC'
));

$grant_prefectures = get_terms(array(
    'taxonomy' => 'grant_prefecture',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
));

// 人気の助成金を取得（ビュー数ベース）
$popular_grants = get_posts(array(
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'meta_key' => 'grant_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_query' => array(
        array(
            'key' => 'application_status',
            'value' => 'open',
            'compare' => '='
        )
    )
));

get_header(); 
?>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Chart.js for statistics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- 🎯 統合検索対応アーカイブページ Ultimate -->
<div id="grant-archive-page" class="grant-archive-ultimate" data-search-params='<?php echo json_encode($search_params); ?>'>
    
    <!-- 📊 ヒーローセクション（改良版） -->
    <section class="hero-section-enhanced relative overflow-hidden">
        <!-- アニメーション背景 -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-indigo-900 dark:to-purple-900">
            <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-gradient-to-br from-blue-400 to-indigo-400 rounded-full filter blur-3xl opacity-20 animate-float"></div>
            <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-gradient-to-br from-purple-400 to-pink-400 rounded-full filter blur-3xl opacity-20 animate-float-delayed"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-br from-emerald-400 to-cyan-400 rounded-full filter blur-3xl opacity-10 animate-pulse-slow"></div>
        </div>
        
        <div class="container mx-auto px-4 py-16 md:py-20 relative z-10">
            <div class="text-center max-w-5xl mx-auto">
                <!-- アニメーションバッジ -->
                <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full text-sm font-bold mb-6 shadow-lg animate-bounce-slow">
                    <i class="fas fa-database animate-pulse"></i>
                    <span>Grant Database System</span>
                    <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs">v26.0</span>
                </div>
                
                <!-- タイトル -->
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    <span class="block">助成金・補助金</span>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 animate-gradient">
                        完全データベース
                    </span>
                </h1>
                
                <!-- 説明文 -->
                <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-10 leading-relaxed">
                    <?php if (!empty($search_params['search'])): ?>
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 rounded-lg shadow-md">
                            <i class="fas fa-search text-indigo-600"></i>
                            「<span class="font-bold text-gray-900 dark:text-white"><?php echo esc_html($search_params['search']); ?></span>」の検索結果
                        </span>
                    <?php else: ?>
                        全国<?php echo number_format($stats['total_grants']); ?>件以上の助成金から、
                        AIがあなたのビジネスに最適な支援制度をご提案
                    <?php endif; ?>
                </p>
                
                <!-- 統計情報（改良版） -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 max-w-6xl mx-auto">
                    <!-- 総助成金数 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-blue-500 to-blue-600">
                            <i class="fas fa-database text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['total_grants']; ?>">0</div>
                            <div class="stat-label">総数</div>
                        </div>
                        <div class="stat-trend up">
                            <i class="fas fa-arrow-up"></i> 12%
                        </div>
                    </div>
                    
                    <!-- 募集中 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-green-500 to-green-600">
                            <i class="fas fa-check-circle text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['active_grants']; ?>">0</div>
                            <div class="stat-label">募集中</div>
                        </div>
                        <div class="stat-trend up">
                            <i class="fas fa-arrow-up"></i> 8%
                        </div>
                    </div>
                    
                    <!-- 募集予定 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-yellow-500 to-orange-500">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['upcoming_grants']; ?>">0</div>
                            <div class="stat-label">予定</div>
                        </div>
                        <div class="stat-trend stable">
                            <i class="fas fa-minus"></i> 0%
                        </div>
                    </div>
                    
                    <!-- 終了 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-gray-500 to-gray-600">
                            <i class="fas fa-times-circle text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['closed_grants']; ?>">0</div>
                            <div class="stat-label">終了</div>
                        </div>
                        <div class="stat-trend down">
                            <i class="fas fa-arrow-down"></i> 5%
                        </div>
                    </div>
                    
                    <!-- 対象地域 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-purple-500 to-purple-600">
                            <i class="fas fa-map-marked-alt text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['prefecture_count']; ?>">0</div>
                            <div class="stat-label">地域</div>
                        </div>
                    </div>
                    
                    <!-- カテゴリ -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-indigo-500 to-indigo-600">
                            <i class="fas fa-folder text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['category_count']; ?>">0</div>
                            <div class="stat-label">分野</div>
                        </div>
                    </div>
                    
                    <!-- 平均金額 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-pink-500 to-rose-500">
                            <i class="fas fa-yen-sign text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['avg_amount']; ?>">0</div>
                            <div class="stat-label">万円(平均)</div>
                        </div>
                    </div>
                    
                    <!-- 採択率 -->
                    <div class="stat-card-ultimate group">
                        <div class="stat-icon-wrapper bg-gradient-to-br from-teal-500 to-cyan-500">
                            <i class="fas fa-percentage text-white"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-count="<?php echo $stats['avg_success_rate']; ?>">0</div>
                            <div class="stat-label">%(採択率)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 🔍 検索・フィルターバー（改良版） -->
    <section class="search-filter-bar-enhanced sticky top-0 z-40 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md shadow-lg border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-4 py-4">
            <!-- メイン検索バー -->
            <div class="search-container-enhanced">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- 検索入力 -->
                    <div class="flex-1">
                        <div class="search-input-group">
                            <div class="search-input-wrapper-ultimate">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="grant-search" 
                                    class="search-input-ultimate"
                                    placeholder="キーワード、業種、地域などで検索..."
                                    value="<?php echo esc_attr($search_params['search']); ?>"
                                    autocomplete="off"
                                >
                                <div class="search-actions">
                                    <button 
                                        id="search-clear" 
                                        class="search-action-btn <?php echo empty($search_params['search']) ? 'hidden' : ''; ?>"
                                        title="クリア"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button 
                                        id="voice-search" 
                                        class="search-action-btn"
                                        title="音声検索"
                                    >
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                    <button 
                                        id="ai-search" 
                                        class="search-action-btn"
                                        title="AI検索"
                                    >
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- 検索サジェスト -->
                            <div id="search-suggestions" class="search-suggestions-ultimate hidden">
                                <!-- 動的に生成 -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- 検索ボタン -->
                    <button 
                        id="search-btn" 
                        class="search-button-ultimate"
                    >
                        <span class="btn-content">
                            <i class="fas fa-search mr-2"></i>
                            検索
                        </span>
                        <span class="btn-loading hidden">
                            <i class="fas fa-spinner animate-spin mr-2"></i>
                            検索中
                        </span>
                    </button>
                </div>

                <!-- クイックフィルター（改良版） -->
                <div class="quick-filters-enhanced mt-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            <i class="fas fa-filter mr-1"></i>
                            クイック:
                        </span>
                        <button 
                            class="quick-filter-pill <?php echo empty($search_params['status']) ? 'active' : ''; ?>"
                            data-filter="all"
                        >
                            <i class="fas fa-globe mr-1"></i>
                            すべて
                            <span class="filter-count"><?php echo number_format($stats['total_grants']); ?></span>
                        </button>
                        <button 
                            class="quick-filter-pill <?php echo $search_params['status'] === 'active' ? 'active' : ''; ?>"
                            data-filter="active"
                        >
                            <span class="status-dot active"></span>
                            募集中
                            <span class="filter-count"><?php echo number_format($stats['active_grants']); ?></span>
                        </button>
                        <button 
                            class="quick-filter-pill <?php echo $search_params['status'] === 'upcoming' ? 'active' : ''; ?>"
                            data-filter="upcoming"
                        >
                            <span class="status-dot upcoming"></span>
                            募集予定
                            <span class="filter-count"><?php echo number_format($stats['upcoming_grants']); ?></span>
                        </button>
                        <button 
                            class="quick-filter-pill"
                            data-filter="high-amount"
                        >
                            <i class="fas fa-coins mr-1 text-yellow-500"></i>
                            高額補助
                        </button>
                        <button 
                            class="quick-filter-pill"
                            data-filter="high-rate"
                        >
                            <i class="fas fa-chart-line mr-1 text-green-500"></i>
                            高採択率
                        </button>
                        <button 
                            class="quick-filter-pill"
                            data-filter="easy"
                        >
                            <i class="fas fa-star mr-1 text-blue-500"></i>
                            申請簡単
                        </button>
                        <button 
                            class="quick-filter-pill"
                            data-filter="popular"
                        >
                            <i class="fas fa-fire mr-1 text-red-500"></i>
                            人気
                        </button>
                    </div>
                </div>

                <!-- コントロールバー -->
                <div class="control-bar-enhanced mt-4">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- ソート -->
                            <div class="control-group">
                                <label class="control-label">
                                    <i class="fas fa-sort"></i>
                                    並び順:
                                </label>
                                <select 
                                    id="sort-order" 
                                    class="control-select"
                                >
                                    <option value="date_desc" <?php selected($search_params['orderby'], 'date_desc'); ?>>新着順</option>
                                    <option value="amount_desc" <?php selected($search_params['orderby'], 'amount_desc'); ?>>金額が高い順</option>
                                    <option value="deadline_asc" <?php selected($search_params['orderby'], 'deadline_asc'); ?>>締切が近い順</option>
                                    <option value="success_rate_desc" <?php selected($search_params['orderby'], 'success_rate_desc'); ?>>採択率順</option>
                                    <option value="popularity" <?php selected($search_params['orderby'], 'popularity'); ?>>人気順</option>
                                </select>
                            </div>

                            <!-- 詳細フィルター -->
                            <button 
                                id="filter-toggle" 
                                class="filter-toggle-btn"
                            >
                                <i class="fas fa-sliders-h mr-2"></i>
                                詳細フィルター
                                <span id="filter-count" class="filter-badge hidden">0</span>
                            </button>

                            <!-- AI推薦 -->
                            <button 
                                id="ai-recommend" 
                                class="ai-recommend-btn"
                            >
                                <i class="fas fa-robot mr-2"></i>
                                AI推薦
                            </button>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- 表示件数 -->
                            <div class="control-group">
                                <label class="control-label">表示:</label>
                                <select id="per-page" class="control-select">
                                    <option value="12">12件</option>
                                    <option value="24">24件</option>
                                    <option value="48">48件</option>
                                </select>
                            </div>

                            <!-- 表示切替 -->
                            <div class="view-switcher">
                                <button 
                                    id="grid-view" 
                                    class="view-btn <?php echo $search_params['view'] === 'grid' ? 'active' : ''; ?>"
                                    data-view="grid"
                                    title="グリッド表示"
                                >
                                    <i class="fas fa-th"></i>
                                </button>
                                <button 
                                    id="list-view" 
                                    class="view-btn <?php echo $search_params['view'] === 'list' ? 'active' : ''; ?>"
                                    data-view="list"
                                    title="リスト表示"
                                >
                                    <i class="fas fa-list"></i>
                                </button>
                                <button 
                                    id="card-view" 
                                    class="view-btn <?php echo $search_params['view'] === 'card' ? 'active' : ''; ?>"
                                    data-view="card"
                                    title="カード表示"
                                >
                                    <i class="fas fa-id-card"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 📋 メインコンテンツエリア -->
    <section class="main-content-enhanced bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- サイドバーフィルター（改良版） -->
                <aside id="filter-sidebar" class="lg:w-80 hidden lg:block">
                    <div class="filter-container-ultimate">
                        <!-- フィルターヘッダー -->
                        <div class="filter-header">
                            <h3 class="filter-title">
                                <i class="fas fa-sliders-h mr-2 text-indigo-600"></i>
                                詳細フィルター
                            </h3>
                            <button id="reset-all-filters" class="reset-filters-btn">
                                <i class="fas fa-undo mr-1"></i>
                                リセット
                            </button>
                        </div>

                        <!-- カテゴリフィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="categories">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-folder mr-2"></i>
                                    カテゴリ
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="categories-content" class="filter-section-content">
                                <div class="filter-search mb-3">
                                    <input 
                                        type="text" 
                                        placeholder="カテゴリを検索..." 
                                        class="filter-search-input"
                                        data-filter="categories"
                                    >
                                </div>
                                <div class="filter-items max-h-60 overflow-y-auto">
                                    <?php if (!empty($grant_categories) && !is_wp_error($grant_categories)): ?>
                                        <?php foreach ($grant_categories as $category): ?>
                                            <label class="filter-item">
                                                <input 
                                                    type="checkbox" 
                                                    name="category[]" 
                                                    value="<?php echo esc_attr($category->slug); ?>"
                                                    data-label="<?php echo esc_attr($category->name); ?>"
                                                    class="filter-checkbox category-checkbox"
                                                    <?php checked($search_params['category'], $category->slug); ?>
                                                >
                                                <span class="filter-item-content">
                                                    <span class="filter-item-label">
                                                        <?php echo esc_html($category->name); ?>
                                                    </span>
                                                    <span class="filter-item-count">
                                                        <?php echo $category->count; ?>
                                                    </span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 地域フィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="prefectures">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-map-marked-alt mr-2"></i>
                                    対象地域
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="prefectures-content" class="filter-section-content">
                                <div class="filter-search mb-3">
                                    <input 
                                        type="text" 
                                        placeholder="地域を検索..." 
                                        class="filter-search-input"
                                        data-filter="prefectures"
                                    >
                                </div>
                                <div class="region-tabs mb-3">
                                    <button class="region-tab active" data-region="all">全国</button>
                                    <button class="region-tab" data-region="hokkaido-tohoku">北海道・東北</button>
                                    <button class="region-tab" data-region="kanto">関東</button>
                                    <button class="region-tab" data-region="chubu">中部</button>
                                    <button class="region-tab" data-region="kinki">近畿</button>
                                    <button class="region-tab" data-region="chugoku-shikoku">中国・四国</button>
                                    <button class="region-tab" data-region="kyushu">九州・沖縄</button>
                                </div>
                                <div class="filter-items max-h-60 overflow-y-auto">
                                    <?php if (!empty($grant_prefectures) && !is_wp_error($grant_prefectures)): ?>
                                        <?php foreach ($grant_prefectures as $prefecture): ?>
                                            <label class="filter-item" data-region="<?php echo esc_attr($prefecture->slug); ?>">
                                                <input 
                                                    type="checkbox" 
                                                    name="prefecture[]" 
                                                    value="<?php echo esc_attr($prefecture->slug); ?>"
                                                    data-label="<?php echo esc_attr($prefecture->name); ?>"
                                                    class="filter-checkbox prefecture-checkbox"
                                                    <?php checked($search_params['prefecture'], $prefecture->slug); ?>
                                                >
                                                <span class="filter-item-content">
                                                    <span class="filter-item-label">
                                                        <?php echo esc_html($prefecture->name); ?>
                                                    </span>
                                                    <span class="filter-item-count">
                                                        <?php echo $prefecture->count; ?>
                                                    </span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 金額フィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="amount">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-yen-sign mr-2"></i>
                                    助成金額
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="amount-content" class="filter-section-content">
                                <div class="amount-slider mb-4">
                                    <input 
                                        type="range" 
                                        id="amount-range" 
                                        min="0" 
                                        max="5000" 
                                        value="0" 
                                        class="w-full"
                                    >
                                    <div class="amount-display">
                                        <span id="amount-min">0</span>万円 〜 <span id="amount-max">5000</span>万円
                                    </div>
                                </div>
                                <div class="filter-items">
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="" class="filter-radio" <?php checked($search_params['amount'], ''); ?>>
                                        <span class="filter-item-label">すべて</span>
                                    </label>
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="0-100" class="filter-radio" <?php checked($search_params['amount'], '0-100'); ?>>
                                        <span class="filter-item-label">〜100万円</span>
                                    </label>
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="100-500" class="filter-radio" <?php checked($search_params['amount'], '100-500'); ?>>
                                        <span class="filter-item-label">100〜500万円</span>
                                    </label>
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="500-1000" class="filter-radio" <?php checked($search_params['amount'], '500-1000'); ?>>
                                        <span class="filter-item-label">500〜1000万円</span>
                                    </label>
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="1000-3000" class="filter-radio" <?php checked($search_params['amount'], '1000-3000'); ?>>
                                        <span class="filter-item-label">1000〜3000万円</span>
                                    </label>
                                    <label class="filter-item">
                                        <input type="radio" name="amount" value="3000+" class="filter-radio" <?php checked($search_params['amount'], '3000+'); ?>>
                                        <span class="filter-item-label">3000万円〜</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ステータスフィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="status">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    募集状況
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="status-content" class="filter-section-content">
                                <div class="filter-items">
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="status[]" 
                                            value="active" 
                                            class="filter-checkbox status-checkbox"
                                            <?php checked($search_params['status'], 'active'); ?>
                                        >
                                        <span class="filter-item-content">
                                            <span class="status-indicator active"></span>
                                            <span class="filter-item-label">募集中</span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="status[]" 
                                            value="upcoming" 
                                            class="filter-checkbox status-checkbox"
                                            <?php checked($search_params['status'], 'upcoming'); ?>
                                        >
                                        <span class="filter-item-content">
                                            <span class="status-indicator upcoming"></span>
                                            <span class="filter-item-label">募集予定</span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="status[]" 
                                            value="closed" 
                                            class="filter-checkbox status-checkbox"
                                            <?php checked($search_params['status'], 'closed'); ?>
                                        >
                                        <span class="filter-item-content">
                                            <span class="status-indicator closed"></span>
                                            <span class="filter-item-label">募集終了</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 採択率フィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="success-rate">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-percentage mr-2"></i>
                                    採択率
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="success-rate-content" class="filter-section-content">
                                <div class="filter-items">
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="success_rate[]" 
                                            value="high" 
                                            class="filter-checkbox success-rate-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <i class="fas fa-star text-yellow-500"></i>
                                                高採択率（70%以上）
                                            </span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="success_rate[]" 
                                            value="medium" 
                                            class="filter-checkbox success-rate-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <i class="fas fa-star-half-alt text-yellow-500"></i>
                                                中採択率（50-69%）
                                            </span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="success_rate[]" 
                                            value="low" 
                                            class="filter-checkbox success-rate-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <i class="far fa-star text-gray-400"></i>
                                                低採択率（50%未満）
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 難易度フィルター -->
                        <div class="filter-section">
                            <div class="filter-section-header" data-toggle="difficulty">
                                <h4 class="filter-section-title">
                                    <i class="fas fa-graduation-cap mr-2"></i>
                                    申請難易度
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div id="difficulty-content" class="filter-section-content">
                                <div class="filter-items">
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="difficulty[]" 
                                            value="easy" 
                                            class="filter-checkbox difficulty-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <span class="difficulty-badge easy">簡単</span>
                                                初心者向け
                                            </span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="difficulty[]" 
                                            value="normal" 
                                            class="filter-checkbox difficulty-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <span class="difficulty-badge normal">普通</span>
                                                標準的
                                            </span>
                                        </span>
                                    </label>
                                    <label class="filter-item">
                                        <input 
                                            type="checkbox" 
                                            name="difficulty[]" 
                                            value="hard" 
                                            class="filter-checkbox difficulty-checkbox"
                                        >
                                        <span class="filter-item-content">
                                            <span class="filter-item-label">
                                                <span class="difficulty-badge hard">難しい</span>
                                                専門知識必要
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- フィルターアクション -->
                        <div class="filter-actions">
                            <button 
                                id="apply-filters" 
                                class="apply-filters-btn"
                            >
                                <i class="fas fa-check mr-2"></i>
                                フィルター適用
                            </button>
                            <button 
                                id="save-filters" 
                                class="save-filters-btn"
                            >
                                <i class="fas fa-bookmark mr-2"></i>
                                条件を保存
                            </button>
                        </div>

                        <!-- 保存した検索条件 -->
                        <div id="saved-filters" class="saved-filters mt-6 hidden">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-bookmark mr-2"></i>
                                保存した条件
                            </h4>
                            <div id="saved-filters-list" class="space-y-2">
                                <!-- 動的に生成 -->
                            </div>
                        </div>
                    </div>

                    <!-- 人気の助成金 -->
                    <?php if (!empty($popular_grants)): ?>
                    <div class="popular-grants-widget mt-6">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <i class="fas fa-fire text-orange-500 mr-2"></i>
                                人気の助成金
                            </h3>
                        </div>
                        <div class="widget-content">
                            <?php foreach ($popular_grants as $index => $grant): ?>
                                <a href="<?php echo get_permalink($grant->ID); ?>" class="popular-grant-item">
                                    <span class="rank-badge"><?php echo $index + 1; ?></span>
                                    <div class="grant-info">
                                        <h4 class="grant-title"><?php echo esc_html($grant->post_title); ?></h4>
                                        <div class="grant-meta">
                                            <span class="amount">
                                                <i class="fas fa-yen-sign"></i>
                                                <?php echo esc_html(get_post_meta($grant->ID, 'max_amount', true)); ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </aside>

                <!-- メインコンテンツ -->
                <main class="flex-1">
                    <!-- 結果ヘッダー（改良版） -->
                    <div class="results-header-ultimate">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h2 id="results-count" class="results-title">
                                    <span class="count-number">0</span>件の助成金
                                </h2>
                                <p id="results-description" class="results-description">
                                    <?php if (!empty($search_params['search'])): ?>
                                        「<?php echo esc_html($search_params['search']); ?>」の検索結果
                                    <?php else: ?>
                                        すべての助成金を表示中
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="results-actions">
                                <button id="export-results" class="export-btn">
                                    <i class="fas fa-download mr-2"></i>
                                    エクスポート
                                </button>
                                <button id="share-results" class="share-btn">
                                    <i class="fas fa-share-alt mr-2"></i>
                                    共有
                                </button>
                            </div>
                        </div>
                        
                        <!-- ローディングインジケーター -->
                        <div id="loading-indicator" class="loading-indicator hidden">
                            <div class="loading-spinner"></div>
                            <span>データを読み込み中...</span>
                        </div>
                    </div>

                    <!-- アクティブフィルター表示（改良版） -->
                    <div id="active-filters" class="active-filters-ultimate hidden">
                        <div class="active-filters-header">
                            <h3 class="active-filters-title">
                                <i class="fas fa-filter mr-2"></i>
                                適用中のフィルター
                            </h3>
                            <button id="clear-all-filters" class="clear-all-btn">
                                すべてクリア
                            </button>
                        </div>
                        <div id="active-filter-tags" class="active-filter-tags">
                            <!-- フィルタータグが動的に生成される -->
                        </div>
                    </div>

                    <!-- 助成金リスト -->
                    <div id="grants-container" class="grants-container-ultimate">
                        <div id="grants-display" class="grants-grid-ultimate">
                            <!-- 初期ローディング表示 -->
                            <div class="initial-loading">
                                <div class="loading-card">
                                    <div class="skeleton skeleton-image"></div>
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-text"></div>
                                    <div class="skeleton skeleton-text w-3/4"></div>
                                </div>
                                <div class="loading-card">
                                    <div class="skeleton skeleton-image"></div>
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-text"></div>
                                    <div class="skeleton skeleton-text w-3/4"></div>
                                </div>
                                <div class="loading-card">
                                    <div class="skeleton skeleton-image"></div>
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-text"></div>
                                    <div class="skeleton skeleton-text w-3/4"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ページネーション（改良版） -->
                    <div id="pagination-container" class="pagination-ultimate mt-8">
                        <!-- ページネーションが動的に生成される -->
                    </div>

                    <!-- 結果なし表示（改良版） -->
                    <div id="no-results" class="no-results-ultimate hidden">
                        <div class="no-results-content">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="no-results-title">
                                該当する助成金が見つかりませんでした
                            </h3>
                            <p class="no-results-description">
                                検索条件を変更して再度お試しください
                            </p>
                            <div class="no-results-suggestions">
                                <h4 class="suggestions-title">検索のヒント:</h4>
                                <ul class="suggestions-list">
                                    <li>キーワードを変更してみる</li>
                                    <li>フィルターを減らしてみる</li>
                                    <li>地域を「全国」に変更してみる</li>
                                </ul>
                            </div>
                            <button 
                                id="reset-search" 
                                class="reset-search-btn"
                            >
                                <i class="fas fa-undo mr-2"></i>
                                検索条件をリセット
                            </button>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- AI推薦モーダル -->
    <div id="ai-recommend-modal" class="modal-overlay hidden">
        <div class="modal-content-ultimate">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-robot mr-2 text-indigo-600"></i>
                    AI推薦システム
                </h3>
                <button class="modal-close" data-modal="ai-recommend">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-4">あなたのビジネス情報を入力すると、AIが最適な助成金を推薦します。</p>
                <form id="ai-recommend-form">
                    <div class="form-group mb-4">
                        <label class="form-label">業種</label>
                        <select class="form-select" name="industry">
                            <option value="">選択してください</option>
                            <option value="it">IT・情報通信</option>
                            <option value="manufacturing">製造業</option>
                            <option value="retail">小売・卸売</option>
                            <option value="service">サービス業</option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label">従業員数</label>
                        <select class="form-select" name="employees">
                            <option value="">選択してください</option>
                            <option value="1-10">1-10人</option>
                            <option value="11-50">11-50人</option>
                            <option value="51-100">51-100人</option>
                            <option value="101+">101人以上</option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label">年商</label>
                        <select class="form-select" name="revenue">
                            <option value="">選択してください</option>
                            <option value="0-1000">〜1000万円</option>
                            <option value="1000-5000">1000〜5000万円</option>
                            <option value="5000-10000">5000万〜1億円</option>
                            <option value="10000+">1億円以上</option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label">目的</label>
                        <select class="form-select" name="purpose">
                            <option value="">選択してください</option>
                            <option value="equipment">設備投資</option>
                            <option value="development">研究開発</option>
                            <option value="marketing">販路開拓</option>
                            <option value="employment">雇用拡大</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modal-cancel-btn" data-modal="ai-recommend">
                    キャンセル
                </button>
                <button id="get-ai-recommendations" class="modal-submit-btn">
                    <i class="fas fa-magic mr-2"></i>
                    推薦を取得
                </button>
            </div>
        </div>
    </div>

    <!-- モバイル用フィルターモーダル -->
    <div id="mobile-filter-modal" class="mobile-filter-modal hidden">
        <div class="mobile-filter-overlay"></div>
        <div class="mobile-filter-panel">
            <div class="mobile-filter-header">
                <h3 class="mobile-filter-title">フィルター</h3>
                <button id="close-mobile-filter" class="mobile-filter-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mobile-filter-content">
                <!-- モバイル用フィルター内容（デスクトップと同じ） -->
            </div>
            <div class="mobile-filter-footer">
                <button id="apply-filters-mobile" class="mobile-apply-btn">
                    適用
                </button>
                <button id="clear-filters-mobile" class="mobile-clear-btn">
                    リセット
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 🚀 統合検索システム連携JavaScript（究極版） -->
<script>
// グローバル設定
window.giSearchConfig = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>',
    isUserLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
    currentUserId: <?php echo get_current_user_id(); ?>
};

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    console.log('📄 Grant Archive Ultimate - 初期化開始');
    console.log('AJAX URL:', window.giSearchConfig.ajaxUrl);
    console.log('Nonce:', window.giSearchConfig.nonce);

    // 初期パラメータを取得
    const archiveElement = document.getElementById('grant-archive-page');
    const initialParams = archiveElement ? JSON.parse(archiveElement.dataset.searchParams) : {};

    // アーカイブページ管理システム
    const ArchiveManager = {
        // 状態管理
        state: {
            isLoading: false,
            currentView: initialParams.view || 'grid',
            currentPage: initialParams.page || 1,
            totalPages: 1,
            totalResults: 0,
            filters: {
                search: initialParams.search || '',
                categories: initialParams.category ? [initialParams.category] : [],
                prefectures: initialParams.prefecture ? [initialParams.prefecture] : [],
                amount: initialParams.amount || '',
                status: initialParams.status ? [initialParams.status] : [],
                difficulty: initialParams.difficulty ? [initialParams.difficulty] : [],
                success_rate: initialParams.success_rate ? [initialParams.success_rate] : [],
                sort: initialParams.orderby || 'date_desc'
            },
            savedFilters: JSON.parse(localStorage.getItem('gi_saved_filters') || '[]')
        },

        // 初期化
        init() {
            console.log('🔄 アーカイブマネージャー初期化');
            
            this.bindEvents();
            this.initAnimations();
            this.loadSavedFilters();
            
            // 統合検索システムとの連携
            this.connectToUnifiedSystem();
            
            // 初期検索実行
            setTimeout(() => {
                this.executeSearch();
            }, 500);
        },

        // 統合検索システムとの連携
        connectToUnifiedSystem() {
            if (window.GISearchManager) {
                console.log('✅ 統合検索システムと連携成功');
                
                // パラメータ同期
                window.GISearchManager.currentParams = {
                    search: this.state.filters.search,
                    categories: this.state.filters.categories,
                    prefectures: this.state.filters.prefectures,
                    amount: this.state.filters.amount,
                    status: this.state.filters.status,
                    difficulty: this.state.filters.difficulty,
                    success_rate: this.state.filters.success_rate,
                    sort: this.state.filters.sort,
                    view: this.state.currentView,
                    page: this.state.currentPage
                };
            } else {
                console.warn('⚠️ 統合検索システムが見つかりません');
            }
        },

        // イベントバインディング
        bindEvents() {
            const self = this;

            // 検索ボタン
            document.getElementById('search-btn')?.addEventListener('click', () => {
                self.handleSearch();
            });

            // 検索入力
            const searchInput = document.getElementById('grant-search');
            if (searchInput) {
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        self.handleSearch();
                    }
                });

                searchInput.addEventListener('input', (e) => {
                    self.handleSearchInput(e);
                });
            }

            // 検索クリア
            document.getElementById('search-clear')?.addEventListener('click', () => {
                self.clearSearch();
            });

            // 音声検索
            document.getElementById('voice-search')?.addEventListener('click', () => {
                self.startVoiceSearch();
            });

            // AI検索
            document.getElementById('ai-search')?.addEventListener('click', () => {
                self.showAISearch();
            });

            // クイックフィルター
            document.querySelectorAll('.quick-filter-pill').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    self.applyQuickFilter(e.currentTarget.dataset.filter);
                });
            });

            // ソート変更
            document.getElementById('sort-order')?.addEventListener('change', (e) => {
                self.state.filters.sort = e.target.value;
                self.executeSearch();
            });

            // 表示件数変更
            document.getElementById('per-page')?.addEventListener('change', (e) => {
                self.executeSearch();
            });

            // ビュー切り替え
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    self.switchView(e.currentTarget.dataset.view);
                });
            });

            // フィルタートグル
            document.getElementById('filter-toggle')?.addEventListener('click', () => {
                self.toggleFilterSidebar();
            });

            // AI推薦
            document.getElementById('ai-recommend')?.addEventListener('click', () => {
                self.showAIRecommend();
            });

            // フィルター適用
            document.getElementById('apply-filters')?.addEventListener('click', () => {
                self.applyFilters();
            });

            // フィルターリセット
            document.getElementById('reset-all-filters')?.addEventListener('click', () => {
                self.resetAllFilters();
            });

            // フィルター保存
            document.getElementById('save-filters')?.addEventListener('click', () => {
                self.saveCurrentFilters();
            });

            // フィルターセクショントグル
            document.querySelectorAll('.filter-section-header').forEach(header => {
                header.addEventListener('click', (e) => {
                    self.toggleFilterSection(e.currentTarget.dataset.toggle);
                });
            });

            // フィルター検索
            document.querySelectorAll('.filter-search-input').forEach(input => {
                input.addEventListener('input', (e) => {
                    self.filterFilterItems(e.target.dataset.filter, e.target.value);
                });
            });

            // 地域タブ
            document.querySelectorAll('.region-tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    self.filterByRegion(e.currentTarget.dataset.region);
                });
            });

            // エクスポート
            document.getElementById('export-results')?.addEventListener('click', () => {
                self.exportResults();
            });

            // 共有
            document.getElementById('share-results')?.addEventListener('click', () => {
                self.shareResults();
            });

            // リセット検索
            document.getElementById('reset-search')?.addEventListener('click', () => {
                self.resetSearch();
            });

            // モーダル関連
            document.querySelectorAll('.modal-close, .modal-cancel-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    self.closeModal(e.currentTarget.dataset.modal);
                });
            });

            // AI推薦取得
            document.getElementById('get-ai-recommendations')?.addEventListener('click', () => {
                self.getAIRecommendations();
            });

            // モバイルフィルター
            document.getElementById('close-mobile-filter')?.addEventListener('click', () => {
                self.closeMobileFilter();
            });
        },

        // 検索処理
        handleSearch() {
            const searchInput = document.getElementById('grant-search');
            if (searchInput) {
                this.state.filters.search = searchInput.value.trim();
                this.state.currentPage = 1;
                this.executeSearch();
            }
        },

        // 検索入力処理
        handleSearchInput(e) {
            const value = e.target.value.trim();
            const clearBtn = document.getElementById('search-clear');
            
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !value);
            }

            // サジェスト表示
            if (value.length >= 2) {
                this.showSearchSuggestions(value);
            } else {
                this.hideSearchSuggestions();
            }
        },

        // 検索実行
        async executeSearch() {
            if (this.state.isLoading) return;

            console.log('🔍 検索実行開始', {
                filters: this.state.filters,
                page: this.state.currentPage,
                view: this.state.currentView
            });

            this.state.isLoading = true;
            this.showLoading(true);

            try {
                const params = new URLSearchParams({
                    action: 'gi_load_grants',
                    nonce: window.giSearchConfig.nonce,
                    search: this.state.filters.search,
                    categories: JSON.stringify(this.state.filters.categories),
                    prefectures: JSON.stringify(this.state.filters.prefectures),
                    amount: this.state.filters.amount,
                    status: JSON.stringify(this.state.filters.status),
                    difficulty: JSON.stringify(this.state.filters.difficulty),
                    success_rate: JSON.stringify(this.state.filters.success_rate),
                    sort: this.state.filters.sort,
                    view: this.state.currentView,
                    page: this.state.currentPage
                });

                console.log('📡 AJAXリクエスト送信:', {
                    url: window.giSearchConfig.ajaxUrl,
                    params: params.toString()
                });

                const response = await fetch(window.giSearchConfig.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('📥 AJAXレスポンス受信:', data);

                if (data.success) {
                    this.displayResults(data.data);
                    this.updateURL();
                    this.updateActiveFilters();
                } else {
                    console.error('❌ AJAXエラー:', data.data);
                    this.showError(data.data || '検索に失敗しました');
                }
            } catch (error) {
                console.error('❌ 検索エラー:', error);
                this.showError('検索中にエラーが発生しました: ' + error.message);
            } finally {
                this.state.isLoading = false;
                this.showLoading(false);
            }
        },

        // 結果表示
        displayResults(data) {
            const container = document.getElementById('grants-display');
            if (!container) return;

            // 結果数更新
            this.state.totalResults = data.found_posts || 0;
            this.state.totalPages = data.pagination?.total_pages || 1;

            const countElement = document.querySelector('#results-count .count-number');
            if (countElement) {
                this.animateNumber(countElement, this.state.totalResults);
            }

            // 結果表示
            if (data.grants && data.grants.length > 0) {
                // ビューに応じたクラス設定
                container.className = this.state.currentView === 'grid' ? 'grants-grid-ultimate' : 
                                     this.state.currentView === 'list' ? 'grants-list-ultimate' : 
                                     'grants-card-ultimate';

                // HTML挿入
                let html = '';
                data.grants.forEach((grant, index) => {
                    const cardHtml = grant.html;
                    // アニメーション用のラッパー追加
                    html += `<div class="grant-item-wrapper" style="animation-delay: ${index * 0.05}s">${cardHtml}</div>`;
                });

                container.innerHTML = html;

                // カードイベント初期化
                this.initializeCardEvents();

                // 結果なし非表示
                document.getElementById('no-results')?.classList.add('hidden');
            } else {
                // 結果なし表示
                container.innerHTML = '';
                document.getElementById('no-results')?.classList.remove('hidden');
            }

            // ページネーション更新
            this.updatePagination(data.pagination);
        },

        // カードイベント初期化
        initializeCardEvents() {
            // お気に入りボタン
            document.querySelectorAll('.favorite-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    await this.toggleFavorite(btn.dataset.postId, btn);
                });
            });

            // シェアボタン
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.shareGrant(btn.dataset.url, btn.dataset.title);
                });
            });
        },

        // お気に入り切り替え
        async toggleFavorite(postId, button) {
            try {
                const response = await fetch(window.giSearchConfig.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'gi_toggle_favorite',
                        nonce: window.giSearchConfig.nonce,
                        post_id: postId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // アイコン更新
                    const svg = button.querySelector('svg');
                    if (svg) {
                        if (data.data.is_favorite) {
                            svg.setAttribute('fill', 'currentColor');
                            button.classList.add('text-red-500');
                            button.classList.remove('text-gray-400');
                        } else {
                            svg.setAttribute('fill', 'none');
                            button.classList.remove('text-red-500');
                            button.classList.add('text-gray-400');
                        }
                    }
                    
                    this.showNotification(data.data.message, 'success');
                }
            } catch (error) {
                console.error('お気に入りエラー:', error);
                this.showNotification('お気に入りの切り替えに失敗しました', 'error');
            }
        },

        // ページネーション更新
        updatePagination(pagination) {
            const container = document.getElementById('pagination-container');
            if (!container || !pagination) return;

            if (pagination.html) {
                container.innerHTML = pagination.html;
                
                // ページネーションイベント
                container.querySelectorAll('.pagination-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(btn.dataset.page);
                        if (page && page !== this.state.currentPage) {
                            this.state.currentPage = page;
                            this.executeSearch();
                            
                            // ページトップへスクロール
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    });
                });
            }
        },

        // クイックフィルター適用
        applyQuickFilter(filter) {
            // 全フィルターボタンのアクティブ状態をリセット
            document.querySelectorAll('.quick-filter-pill').forEach(btn => {
                btn.classList.remove('active');
            });

            // クリックされたボタンをアクティブに
            document.querySelector(`.quick-filter-pill[data-filter="${filter}"]`)?.classList.add('active');

            // フィルターリセット
            this.state.filters = {
                search: this.state.filters.search, // 検索キーワードは保持
                categories: [],
                prefectures: [],
                amount: '',
                status: [],
                difficulty: [],
                success_rate: [],
                sort: this.state.filters.sort
            };

            // 特定のフィルター適用
            switch(filter) {
                case 'all':
                    // すべて表示（リセット済み）
                    break;
                case 'active':
                    this.state.filters.status = ['active'];
                    break;
                case 'upcoming':
                    this.state.filters.status = ['upcoming'];
                    break;
                case 'high-amount':
                    this.state.filters.amount = '1000+';
                    break;
                case 'high-rate':
                    this.state.filters.success_rate = ['high'];
                    break;
                case 'easy':
                    this.state.filters.difficulty = ['easy'];
                    break;
                case 'popular':
                    this.state.filters.sort = 'popularity';
                    break;
            }

            this.state.currentPage = 1;
            this.executeSearch();
        },

        // フィルター適用
        applyFilters() {
            // カテゴリ
            this.state.filters.categories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
                .map(cb => cb.value);

            // 都道府県
            this.state.filters.prefectures = Array.from(document.querySelectorAll('.prefecture-checkbox:checked'))
                .map(cb => cb.value);

            // 金額
            const amountRadio = document.querySelector('input[name="amount"]:checked');
            this.state.filters.amount = amountRadio ? amountRadio.value : '';

            // ステータス
            this.state.filters.status = Array.from(document.querySelectorAll('.status-checkbox:checked'))
                .map(cb => cb.value);

            // 採択率
            this.state.filters.success_rate = Array.from(document.querySelectorAll('.success-rate-checkbox:checked'))
                .map(cb => cb.value);

            // 難易度
            this.state.filters.difficulty = Array.from(document.querySelectorAll('.difficulty-checkbox:checked'))
                .map(cb => cb.value);

            this.state.currentPage = 1;
            this.executeSearch();
        },

        // アクティブフィルター更新
        updateActiveFilters() {
            const container = document.getElementById('active-filters');
            const tagsContainer = document.getElementById('active-filter-tags');
            const filterCount = document.getElementById('filter-count');

            if (!container || !tagsContainer) return;

            const hasFilters = this.state.filters.search ||
                              this.state.filters.categories.length > 0 ||
                              this.state.filters.prefectures.length > 0 ||
                              this.state.filters.amount ||
                              this.state.filters.status.length > 0 ||
                              this.state.filters.success_rate.length > 0 ||
                              this.state.filters.difficulty.length > 0;

            if (hasFilters) {
                container.classList.remove('hidden');
                
                let tagsHTML = '';
                
                // 検索キーワード
                if (this.state.filters.search) {
                    tagsHTML += this.createFilterTag('search', this.state.filters.search, 'fas fa-search');
                }

                // カテゴリ
                this.state.filters.categories.forEach(cat => {
                    const label = document.querySelector(`.category-checkbox[value="${cat}"]`)?.dataset.label || cat;
                    tagsHTML += this.createFilterTag('category', label, 'fas fa-folder', cat);
                });

                // 都道府県
                this.state.filters.prefectures.forEach(pref => {
                    const label = document.querySelector(`.prefecture-checkbox[value="${pref}"]`)?.dataset.label || pref;
                    tagsHTML += this.createFilterTag('prefecture', label, 'fas fa-map-marker-alt', pref);
                });

                // 金額
                if (this.state.filters.amount) {
                    const amountLabels = {
                        '0-100': '〜100万円',
                        '100-500': '100〜500万円',
                        '500-1000': '500〜1000万円',
                        '1000-3000': '1000〜3000万円',
                        '3000+': '3000万円〜'
                    };
                    tagsHTML += this.createFilterTag('amount', amountLabels[this.state.filters.amount] || this.state.filters.amount, 'fas fa-yen-sign');
                }

                tagsContainer.innerHTML = tagsHTML;

                // フィルターカウント更新
                if (filterCount) {
                    const count = this.state.filters.categories.length +
                                 this.state.filters.prefectures.length +
                                 (this.state.filters.amount ? 1 : 0) +
                                 this.state.filters.status.length +
                                 this.state.filters.success_rate.length +
                                 this.state.filters.difficulty.length;
                    
                    if (count > 0) {
                        filterCount.textContent = count;
                        filterCount.classList.remove('hidden');
                    } else {
                        filterCount.classList.add('hidden');
                    }
                }
            } else {
                container.classList.add('hidden');
            }
        },

        // フィルタータグ作成
        createFilterTag(type, label, icon, value = '') {
            return `
                <span class="filter-tag">
                    <i class="${icon} mr-1"></i>
                    ${label}
                    <button onclick="ArchiveManager.removeFilter('${type}', '${value}')" class="filter-tag-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            `;
        },

        // フィルター削除
        removeFilter(type, value) {
            switch(type) {
                case 'search':
                    this.state.filters.search = '';
                    document.getElementById('grant-search').value = '';
                    document.getElementById('search-clear')?.classList.add('hidden');
                    break;
                case 'category':
                    this.state.filters.categories = this.state.filters.categories.filter(c => c !== value);
                    break;
                case 'prefecture':
                    this.state.filters.prefectures = this.state.filters.prefectures.filter(p => p !== value);
                    break;
                case 'amount':
                    this.state.filters.amount = '';
                    break;
                case 'status':
                    this.state.filters.status = this.state.filters.status.filter(s => s !== value);
                    break;
                case 'success_rate':
                    this.state.filters.success_rate = this.state.filters.success_rate.filter(r => r !== value);
                    break;
                case 'difficulty':
                    this.state.filters.difficulty = this.state.filters.difficulty.filter(d => d !== value);
                    break;
            }

            this.updateFilterUI();
            this.state.currentPage = 1;
            this.executeSearch();
        },

        // URL更新
        updateURL() {
            const params = new URLSearchParams();
            
            if (this.state.filters.search) params.set('search', this.state.filters.search);
            if (this.state.filters.categories.length > 0) params.set('category', this.state.filters.categories[0]);
            if (this.state.filters.prefectures.length > 0) params.set('prefecture', this.state.filters.prefectures[0]);
            if (this.state.filters.amount) params.set('amount', this.state.filters.amount);
            if (this.state.filters.status.length > 0) params.set('status', this.state.filters.status[0]);
            if (this.state.filters.sort !== 'date_desc') params.set('orderby', this.state.filters.sort);
            if (this.state.currentView !== 'grid') params.set('view', this.state.currentView);
            if (this.state.currentPage > 1) params.set('page', this.state.currentPage);
            
            const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newURL);
        },

        // アニメーション初期化
        initAnimations() {
            // 統計カードのカウントアップ
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const statValues = entry.target.querySelectorAll('.stat-value[data-count]');
                        statValues.forEach(stat => {
                            const target = parseInt(stat.dataset.count);
                            this.animateNumber(stat, target);
                        });
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            const statsSection = document.querySelector('.grid.grid-cols-2.md\\:grid-cols-4.lg\\:grid-cols-8');
            if (statsSection) {
                observer.observe(statsSection);
            }
        },

        // 数値アニメーション
        animateNumber(element, target) {
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        },

        // ローディング表示
        showLoading(show) {
            const indicator = document.getElementById('loading-indicator');
            const container = document.getElementById('grants-display');
            
            if (indicator) {
                indicator.classList.toggle('hidden', !show);
            }
            
            if (container && show) {
                container.style.opacity = '0.5';
                container.style.pointerEvents = 'none';
            } else if (container) {
                container.style.opacity = '1';
                container.style.pointerEvents = '';
            }
        },

        // 通知表示
        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        // エラー表示
        showError(message) {
            this.showNotification(message, 'error');
        },

        // その他のメソッド...
        clearSearch() {
            document.getElementById('grant-search').value = '';
            document.getElementById('search-clear')?.classList.add('hidden');
            this.state.filters.search = '';
            this.executeSearch();
        },

        resetAllFilters() {
            this.state.filters = {
                search: '',
                categories: [],
                prefectures: [],
                amount: '',
                status: [],
                difficulty: [],
                success_rate: [],
                sort: 'date_desc'
            };
            
            // UI更新
            document.getElementById('grant-search').value = '';
            document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.filter-radio').forEach(rb => rb.checked = rb.value === '');
            
            this.state.currentPage = 1;
            this.executeSearch();
        },

        updateFilterUI() {
            // チェックボックス更新
            document.querySelectorAll('.category-checkbox').forEach(cb => {
                cb.checked = this.state.filters.categories.includes(cb.value);
            });
            
            document.querySelectorAll('.prefecture-checkbox').forEach(cb => {
                cb.checked = this.state.filters.prefectures.includes(cb.value);
            });
            
            document.querySelectorAll('.status-checkbox').forEach(cb => {
                cb.checked = this.state.filters.status.includes(cb.value);
            });
        },

        // ローディング表示管理
        showLoading(show) {
            const loadingEl = document.getElementById('loading-indicator');
            const grantsDisplay = document.getElementById('grants-display');
            
            if (loadingEl) {
                if (show) {
                    loadingEl.classList.remove('hidden');
                    loadingEl.innerHTML = `
                        <div class="loading-spinner"></div>
                        <span>データを読み込み中...</span>
                    `;
                } else {
                    loadingEl.classList.add('hidden');
                }
            }

            // 初期ローディング表示を削除
            if (!show && grantsDisplay) {
                const initialLoading = grantsDisplay.querySelector('.initial-loading');
                if (initialLoading) {
                    initialLoading.remove();
                }
            }
        },

        // エラー表示
        showError(message) {
            const container = document.getElementById('grants-display');
            if (container) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="text-red-500 dark:text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle text-4xl"></i>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 text-lg font-semibold mb-2">
                            エラーが発生しました
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">
                            ${message}
                        </div>
                        <button onclick="window.ArchiveManager.executeSearch()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            再試行
                        </button>
                    </div>
                `;
            }
        },

        // URL更新
        updateURL() {
            const params = new URLSearchParams();
            
            if (this.state.filters.search) params.set('search', this.state.filters.search);
            if (this.state.filters.categories.length > 0) params.set('category', this.state.filters.categories[0]);
            if (this.state.filters.prefectures.length > 0) params.set('prefecture', this.state.filters.prefectures[0]);
            if (this.state.filters.amount) params.set('amount', this.state.filters.amount);
            if (this.state.filters.status.length > 0) params.set('status', this.state.filters.status[0]);
            if (this.state.filters.difficulty.length > 0) params.set('difficulty', this.state.filters.difficulty[0]);
            if (this.state.filters.success_rate.length > 0) params.set('success_rate', this.state.filters.success_rate[0]);
            if (this.state.filters.sort !== 'date_desc') params.set('orderby', this.state.filters.sort);
            if (this.state.currentView !== 'grid') params.set('view', this.state.currentView);
            if (this.state.currentPage > 1) params.set('page', this.state.currentPage);
            
            const queryString = params.toString();
            const newURL = window.location.pathname + (queryString ? '?' + queryString : '');
            
            window.history.pushState({}, '', newURL);
        },

        // アクティブフィルター更新
        updateActiveFilters() {
            const activeFiltersEl = document.getElementById('active-filters');
            const tagsContainer = document.getElementById('active-filter-tags');
            
            if (!activeFiltersEl || !tagsContainer) return;
            
            const hasFilters = 
                this.state.filters.search ||
                this.state.filters.categories.length > 0 ||
                this.state.filters.prefectures.length > 0 ||
                this.state.filters.amount ||
                this.state.filters.status.length > 0 ||
                this.state.filters.difficulty.length > 0 ||
                this.state.filters.success_rate.length > 0;
            
            if (hasFilters) {
                activeFiltersEl.classList.remove('hidden');
                
                let tagsHTML = '';
                
                if (this.state.filters.search) {
                    tagsHTML += `<span class="filter-tag">検索: ${this.state.filters.search}<button class="filter-tag-remove" data-filter="search"><i class="fas fa-times"></i></button></span>`;
                }
                
                this.state.filters.categories.forEach(cat => {
                    tagsHTML += `<span class="filter-tag">カテゴリ: ${cat}<button class="filter-tag-remove" data-filter="category" data-value="${cat}"><i class="fas fa-times"></i></button></span>`;
                });
                
                this.state.filters.prefectures.forEach(pref => {
                    tagsHTML += `<span class="filter-tag">都道府県: ${pref}<button class="filter-tag-remove" data-filter="prefecture" data-value="${pref}"><i class="fas fa-times"></i></button></span>`;
                });
                
                tagsContainer.innerHTML = tagsHTML;
                
                // フィルター削除ボタンのイベント
                tagsContainer.querySelectorAll('.filter-tag-remove').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.removeFilter(btn.dataset.filter, btn.dataset.value);
                    });
                });
                
            } else {
                activeFiltersEl.classList.add('hidden');
            }
        },

        // フィルター削除
        removeFilter(type, value) {
            switch(type) {
                case 'search':
                    this.state.filters.search = '';
                    document.getElementById('grant-search').value = '';
                    break;
                case 'category':
                    this.state.filters.categories = this.state.filters.categories.filter(c => c !== value);
                    break;
                case 'prefecture':
                    this.state.filters.prefectures = this.state.filters.prefectures.filter(p => p !== value);
                    break;
            }
            
            this.state.currentPage = 1;
            this.executeSearch();
        },

        // ページネーション更新
        updatePagination(paginationData) {
            const container = document.getElementById('pagination-container');
            if (container && paginationData && paginationData.html) {
                container.innerHTML = paginationData.html;
                
                // ページボタンのイベントバインド
                container.querySelectorAll('.pagination-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.state.currentPage = parseInt(btn.dataset.page);
                        this.executeSearch();
                        // ページ移動時にスクロールトップ
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                });
            }
        },

        // 数値アニメーション
        animateNumber(element, target) {
            const duration = 1000;
            const start = parseInt(element.textContent) || 0;
            const increment = (target - start) / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.round(current).toLocaleString();
            }, 16);
        },

        // その他の必要な関数
        clearSearch() {
            document.getElementById('grant-search').value = '';
            this.state.filters.search = '';
            this.executeSearch();
        },

        startVoiceSearch() {
            alert('音声検索機能は現在開発中です');
        },

        showAISearch() {
            alert('AI検索機能は現在開発中です');
        },

        applyQuickFilter(filter) {
            // クイックフィルターの適用
            switch(filter) {
                case 'active':
                    this.state.filters.status = ['open'];
                    break;
                case 'high-amount':
                    this.state.filters.amount = '5000';
                    break;
                case 'easy':
                    this.state.filters.difficulty = ['easy'];
                    break;
            }
            this.executeSearch();
        },

        switchView(view) {
            this.state.currentView = view;
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            this.executeSearch();
        },

        toggleFilterSidebar() {
            const sidebar = document.getElementById('filter-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
            }
        },

        showAIRecommend() {
            const modal = document.getElementById('ai-recommend-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        },

        applyFilters() {
            // フィルターの収集と適用
            this.state.filters.categories = [];
            this.state.filters.prefectures = [];
            this.state.filters.status = [];
            this.state.filters.difficulty = [];
            this.state.filters.success_rate = [];

            document.querySelectorAll('.category-checkbox:checked').forEach(cb => {
                this.state.filters.categories.push(cb.value);
            });

            document.querySelectorAll('.prefecture-checkbox:checked').forEach(cb => {
                this.state.filters.prefectures.push(cb.value);
            });

            document.querySelectorAll('.status-checkbox:checked').forEach(cb => {
                this.state.filters.status.push(cb.value);
            });

            document.querySelectorAll('.difficulty-checkbox:checked').forEach(cb => {
                this.state.filters.difficulty.push(cb.value);
            });

            document.querySelectorAll('.success-checkbox:checked').forEach(cb => {
                this.state.filters.success_rate.push(cb.value);
            });

            const amountRadio = document.querySelector('input[name="amount"]:checked');
            if (amountRadio) {
                this.state.filters.amount = amountRadio.value;
            }

            this.state.currentPage = 1;
            this.executeSearch();
        },

        saveCurrentFilters() {
            const filterName = prompt('検索条件の名前を入力してください:');
            if (filterName) {
                const savedFilter = {
                    name: filterName,
                    filters: { ...this.state.filters },
                    date: new Date().toISOString()
                };
                this.state.savedFilters.push(savedFilter);
                localStorage.setItem('gi_saved_filters', JSON.stringify(this.state.savedFilters));
                this.loadSavedFilters();
            }
        },

        loadSavedFilters() {
            const container = document.getElementById('saved-filters-list');
            if (container && this.state.savedFilters.length > 0) {
                let html = '';
                this.state.savedFilters.forEach((filter, index) => {
                    html += `
                        <div class="saved-filter-item">
                            <button class="apply-saved-filter" data-index="${index}">
                                ${filter.name}
                            </button>
                            <button class="delete-saved-filter" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                });
                container.innerHTML = html;
                document.getElementById('saved-filters').classList.remove('hidden');
            }
        },

        toggleFilterSection(section) {
            const content = document.getElementById(section + '-content');
            const header = document.querySelector(`[data-toggle="${section}"]`);
            if (content && header) {
                content.classList.toggle('hidden');
                header.classList.toggle('collapsed');
            }
        },

        filterFilterItems(filterType, searchValue) {
            const items = document.querySelectorAll(`.${filterType}-item`);
            items.forEach(item => {
                const label = item.querySelector('.filter-item-label').textContent.toLowerCase();
                item.style.display = label.includes(searchValue.toLowerCase()) ? '' : 'none';
            });
        },

        filterByRegion(region) {
            // 地域フィルタリング
            console.log('地域フィルタリング:', region);
        },

        exportResults() {
            alert('エクスポート機能は現在開発中です');
        },

        shareResults() {
            if (navigator.share) {
                navigator.share({
                    title: '助成金検索結果',
                    text: `${this.state.totalResults}件の助成金が見つかりました`,
                    url: window.location.href
                });
            } else {
                alert('共有URLをコピーしました');
                navigator.clipboard.writeText(window.location.href);
            }
        },

        resetSearch() {
            this.resetAllFilters();
        },

        closeModal(modalId) {
            const modal = document.getElementById(modalId + '-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        },

        getAIRecommendations() {
            // AI推薦取得
            alert('AI推薦機能は現在開発中です');
            this.closeModal('ai-recommend');
        },

        closeMobileFilter() {
            const modal = document.getElementById('mobile-filter-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        },

        showSearchSuggestions(value) {
            // 検索サジェスト表示
            console.log('検索サジェスト:', value);
        },

        hideSearchSuggestions() {
            // 検索サジェスト非表示
        },

        shareGrant(url, title) {
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url);
                alert('URLをコピーしました');
            }
        },

        initAnimations() {
            // アニメーション初期化
            console.log('アニメーション初期化');
        }
    };

    // グローバルに公開
    window.ArchiveManager = ArchiveManager;

    // 初期化実行
    ArchiveManager.init();

    console.log('✅ Grant Archive Ultimate 初期化完了');
});
</script>

<!-- 追加CSS（究極版） -->
<style>
/* アニメーション */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-30px); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; }
    50% { opacity: 0.2; }
}

@keyframes gradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 8s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

/* 統計カード究極版 */
.stat-card-ultimate {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.stat-card-ultimate::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s;
}

.stat-card-ultimate:hover::before {
    transform: scaleX(1);
}

.stat-card-ultimate:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.stat-trend {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    font-size: 0.625rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-trend.up { color: #10b981; }
.stat-trend.down { color: #ef4444; }
.stat-trend.stable { color: #6b7280; }

/* 検索バー究極版 */
.search-input-wrapper-ultimate {
    position: relative;
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid transparent;
    border-radius: 1.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
    background-image: linear-gradient(white, white), 
                      linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-origin: border-box;
    background-clip: padding-box, border-box;
}

.search-input-wrapper-ultimate:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}

.search-input-ultimate {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: none;
    background: transparent;
    font-size: 1rem;
    color: #1f2937;
    outline: none;
}

.search-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-right: 1rem;
}

.search-action-btn {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 0.5rem;
    color: #6b7280;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.search-action-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: scale(1.1);
}

.search-button-ultimate {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 1.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
}

.search-button-ultimate:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(102, 126, 234, 0.5);
}

/* クイックフィルター改良版 */
.quick-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.quick-filter-pill:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.quick-filter-pill.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
}

.filter-count {
    padding: 0.125rem 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    display: inline-block;
}

.status-dot.active {
    background: #10b981;
    animation: pulse 2s infinite;
}

.status-dot.upcoming {
    background: #f59e0b;
}

/* フィルターコンテナ究極版 */
.filter-container-ultimate {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    position: sticky;
    top: 5rem;
}

.filter-section {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 1.5rem;
}

.filter-section:last-child {
    border-bottom: none;
}

.filter-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: background 0.2s;
}

.filter-section-header:hover {
    background: #f3f4f6;
}

.filter-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #4b5563;
    display: flex;
    align-items: center;
}

.toggle-icon {
    transition: transform 0.3s;
    color: #9ca3af;
}

.filter-section-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.filter-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.2s;
}

.filter-item:hover {
    background: #f9fafb;
}

.filter-item-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 1;
    margin-left: 0.75rem;
}

.filter-item-label {
    font-size: 0.875rem;
    color: #4b5563;
}

.filter-item-count {
    font-size: 0.75rem;
    color: #9ca3af;
    padding: 0.125rem 0.5rem;
    background: #f3f4f6;
    border-radius: 9999px;
}

/* 結果ヘッダー究極版 */
.results-header-ultimate {
    background: white;
    border-radius: 1.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.results-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1f2937;
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.count-number {
    color: #667eea;
    font-size: 2.25rem;
}

.results-description {
    font-size: 1rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

/* アクティブフィルター究極版 */
.active-filters-ultimate {
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border: 1px solid #bfdbfe;
    border-radius: 1rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 9999px;
    font-size: 0.875rem;
    color: #4b5563;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin: 0.25rem;
}

.filter-tag-remove {
    margin-left: 0.5rem;
    color: #9ca3af;
    cursor: pointer;
    transition: color 0.2s;
}

.filter-tag-remove:hover {
    color: #ef4444;
}

/* グリッドレイアウト究極版 */
.grants-grid-ultimate {
    display: grid !important;
    gap: 1.5rem !important;
    grid-template-columns: repeat(1, 1fr) !important;
}

@media (min-width: 640px) {
    .grants-grid-ultimate {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (min-width: 1024px) {
    .grants-grid-ultimate {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (min-width: 1536px) {
    .grants-grid-ultimate {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}

.grant-item-wrapper {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ローディング */
.loading-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border-radius: 0.75rem;
    margin-top: 1rem;
}

.loading-spinner {
    width: 2rem;
    height: 2rem;
    border: 3px solid rgba(102, 126, 234, 0.3);
    border-radius: 50%;
    border-top-color: #667eea;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* スケルトンローディング */
.skeleton {
    background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-image {
    height: 200px;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
}

.skeleton-title {
    height: 1.5rem;
    border-radius: 0.25rem;
    margin-bottom: 0.75rem;
}

.skeleton-text {
    height: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
}

/* 通知 */
.notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    transform: translateX(calc(100% + 2rem));
    transition: transform 0.3s;
    z-index: 9999;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.notification-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

/* ダークモード対応 */
@media (prefers-color-scheme: dark) {
    .stat-card-ultimate,
    .filter-container-ultimate,
    .results-header-ultimate {
        background: #1f2937;
    }
    
    .search-input-wrapper-ultimate {
        background-image: linear-gradient(#1f2937, #1f2937), 
                          linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .search-input-ultimate {
        color: white;
    }
    
    .quick-filter-pill {
        background: #1f2937;
        border-color: #374151;
        color: #d1d5db;
    }
}
</style>

<?php get_footer(); ?>