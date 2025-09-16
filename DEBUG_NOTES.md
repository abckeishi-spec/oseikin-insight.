# 助成金検索システム デバッグノート

## 解決した問題

### 1. 「Loading data...」が表示され続ける問題

**原因:**
- AJAXハンドラー（`gi_ajax_load_grants`）で使用される補助関数が未定義だった
- JavaScriptのグローバル設定（`window.giSearchConfig`）が設定されていなかった
- JavaScriptの必要な関数（`showLoading`、`showError`など）が未実装だった

**解決策:**

#### 1. 必要な補助関数を追加（functions.php）:
```php
// 安全なメタデータ取得
function gi_safe_get_meta($post_id, $key, $default = '')

// ステータスの日本語変換
function gi_map_application_status_ui($status)

// 金額フォーマット
function gi_format_amount_with_unit($amount)

// 締切日フォーマット
function gi_get_formatted_deadline($post_id)

// その他の補助関数
```

#### 2. JavaScriptグローバル設定を追加（archive-grant.php）:
```javascript
window.giSearchConfig = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>',
    isUserLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
    currentUserId: <?php echo get_current_user_id(); ?>
};
```

#### 3. 不足していたJavaScript関数を実装:
- `showLoading()` - ローディング表示制御
- `showError()` - エラー表示
- `updateURL()` - URL更新
- `updateActiveFilters()` - アクティブフィルター表示
- `updatePagination()` - ページネーション更新
- その他のUI制御関数

#### 4. AJAXレスポンスの構造を改善:
```php
wp_send_json_success(array(
    'grants' => $grants,  // 各助成金データの配列
    'html' => $html,      // 表示用HTML
    'found_posts' => $query->found_posts,
    'pagination' => array(...),
    'query_info' => compact(...),
    'view' => $view
));
```

## デバッグ用コンソールログ

JavaScriptに以下のデバッグログを追加：
```javascript
console.log('🔍 検索実行開始', {filters, page, view});
console.log('📡 AJAXリクエスト送信:', {url, params});
console.log('📥 AJAXレスポンス受信:', data);
```

## テスト方法

### ローカルWordPress環境での確認:
1. WordPressをローカルにインストール
2. テーマフォルダ全体を `wp-content/themes/grant-insight-perfect/` に配置
3. 管理画面でテーマを有効化
4. カスタム投稿タイプ「grant」を作成
5. テストデータを投入
6. ブラウザの開発者ツールでコンソールログを確認

### デバッグモードの有効化:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 残りの確認事項

1. **カスタム投稿タイプの登録**: `grant`投稿タイプが正しく登録されているか確認
2. **タクソノミーの登録**: `grant_category`と`grant_prefecture`が登録されているか
3. **データベースのテストデータ**: 助成金データが存在するか
4. **AJAXエンドポイント**: `/wp-admin/admin-ajax.php`がアクセス可能か
5. **権限設定**: AJAXハンドラーがログインしていないユーザーでも動作するか

## 推奨される次のステップ

1. WordPress環境にテーマをアップロード
2. ブラウザの開発者ツールでネットワークタブを確認
3. AJAXリクエストが正しく送信されているか確認
4. レスポンスデータの構造を確認
5. エラーログ（`/wp-content/debug.log`）を確認

## 注意事項

- このテーマはWordPress環境でのみ動作します
- ACFプラグインが必要な可能性があります
- PHPバージョン7.4以上推奨
- JavaScriptはES6構文を使用しているため、モダンブラウザが必要