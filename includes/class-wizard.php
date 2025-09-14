<?php
if (!defined('ABSPATH')) {
    exit;
}

class KSC_Wizard {
    public function __construct() {
        add_action('wp_ajax_ksc_get_categories', array($this, 'ajax_get_categories'));
        add_action('wp_ajax_ksc_calculate_layout', array($this, 'ajax_calculate_layout'));
        add_action('wp_ajax_ksc_generate_shortcode', array($this, 'ajax_generate_shortcode'));
        add_action('wp_ajax_ksc_preview_shortcode', array($this, 'ajax_preview_shortcode'));
    }

        public function ajax_get_categories() {
        check_ajax_referer('ksc_wizard_nonce', 'nonce');

        $post_type = sanitize_text_field($_POST['post_type']);
        $categories = array();

        if ($post_type === 'post') {
            $cats = get_categories(array('hide_empty' => false));
            foreach ($cats as $cat) {
                $categories[] = array(
                    'slug' => $cat->slug,
                    'name' => $cat->name,
                    'count' => $cat->count,
                    'taxonomy' => 'category'
                );
            }
        } else if ($post_type === 'page') {
            $pages = get_pages(array(
                'parent' => 0,
                'post_status' => 'publish'
            ));

            if (!empty($pages)) {
                foreach ($pages as $page) {
                    $child_count = count(get_pages(array(
                        'parent' => $page->ID,
                        'post_status' => 'publish'
                    )));

                    $categories[] = array(
                        'slug' => 'page-' . $page->ID,  // IDベースのスラッグに変更
                        'name' => $page->post_title,
                        'count' => $child_count,
                        'taxonomy' => 'page_parent',
                        'page_id' => $page->ID,
                        'original_slug' => $page->post_name
                    );
                }
            } else {
                $categories[] = array(
                    'slug' => '',
                    'name' => '親ページがありません',
                    'count' => 0,
                    'taxonomy' => 'none'
                );
            }
        } else {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && !in_array($taxonomy->name, array('post_format'))) {
                    $terms = get_terms(array(
                        'taxonomy' => $taxonomy->name,
                        'hide_empty' => false
                    ));

                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $term) {
                            $categories[] = array(
                                'slug' => $term->slug,
                                'name' => $term->name . ' (' . $taxonomy->label . ')',
                                'count' => $term->count,
                                'taxonomy' => $taxonomy->name
                            );
                        }
                    }
                }
            }

            if (empty($categories)) {
                $categories[] = array(
                    'slug' => '',
                    'name' => 'この投稿タイプにはカテゴリがありません',
                    'count' => 0,
                    'taxonomy' => 'none'
                );
            }
        }

        wp_send_json_success($categories);
    }

    /**
     * 件数に基づく最適なレイアウトを計算する
     */
    public function ajax_calculate_layout() {
        check_ajax_referer('ksc_wizard_nonce', 'nonce');

        $post_type = sanitize_text_field($_POST['post_type']);
        $category = isset($_POST['category']) ? sanitize_text_field(urldecode($_POST['category'])) : '';
        $design = sanitize_text_field($_POST['design']);

        // 投稿件数を取得
        $post_count = $this->get_post_count($post_type, $category);

        // レイアウト計算
        $layout_info = $this->calculate_optimal_layout($post_count, $design);

        wp_send_json_success($layout_info);
    }

    /**
     * 投稿件数を取得
     */
    private function get_post_count($post_type, $category) {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        if (!empty($category)) {
            if (strpos($category, 'page-') === 0) {
                // ページの親子関係の場合
                $page_id = intval(str_replace('page-', '', $category));
                if ($page_id > 0) {
                    $args['post_parent'] = $page_id;
                }
            } else {
                // 通常のタクソノミーの場合
                $taxonomy = $this->get_taxonomy_for_category($post_type, $category);
                if ($taxonomy) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => $taxonomy,
                            'field' => 'slug',
                            'terms' => $category
                        )
                    );
                }
            }
        }

        $query = new WP_Query($args);
        $count = $query->found_posts;


        return $count;
    }

    /**
     * カテゴリに対応するタクソノミーを取得
     */
    private function get_taxonomy_for_category($post_type, $category) {
        if ($post_type === 'post') {
            return 'category';
        } else if ($post_type === 'page') {
            return 'page_parent';
        } else {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && !in_array($taxonomy->name, array('post_format'))) {
                    $terms = get_terms(array(
                        'taxonomy' => $taxonomy->name,
                        'hide_empty' => false
                    ));
                    if (!is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            if ($term->slug === $category) {
                                return $taxonomy->name;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * 最適なレイアウトを計算
     */
    private function calculate_optimal_layout($post_count, $design) {
        $result = array(
            'post_count' => $post_count,
            'suggested_cols' => 3,
            'suggested_rows' => 2,
            'max_cols' => 6,
            'max_rows' => 10,
            'available_cols' => array(),
            'available_rows' => array(),
            'message' => ''
        );

        if ($post_count === 0) {
            $result['message'] = '投稿が見つかりませんでした。';
            return $result;
        }

        // 利用可能な列数を計算
        $result['available_cols'] = array();
        if ($design === 'list') {
            // リスト表示では列数は常に1
            $result['available_cols'] = array(1);
        } else {
            // グリッド・カルーセル表示では投稿件数に基づいて計算
            for ($cols = 1; $cols <= min(6, $post_count); $cols++) {
                $result['available_cols'][] = $cols;
            }
        }

        // 最適な列数を提案（投稿件数の平方根に近い値）
        $optimal_cols = max(1, min(6, round(sqrt($post_count))));
        $result['suggested_cols'] = $optimal_cols;

        // 列数に基づく利用可能な行数を計算
        $result['available_rows'] = array();
        if ($design === 'grid') {
            for ($rows = 1; $rows <= min(10, ceil($post_count / $optimal_cols)); $rows++) {
                $result['available_rows'][] = $rows;
            }
            $result['suggested_rows'] = min(3, ceil($post_count / $optimal_cols));
        } else if ($design === 'list') {
            // リスト表示では投稿件数まで行数を設定可能
            for ($rows = 1; $rows <= min(10, $post_count); $rows++) {
                $result['available_rows'][] = $rows;
            }
            $result['suggested_rows'] = min(3, $post_count);
        }



        return $result;
    }

    public function ajax_generate_shortcode() {
        check_ajax_referer('ksc_wizard_nonce', 'nonce');

        $params = array();



        $post_type = sanitize_text_field($_POST['post_type']);
        // URLエンコードされた値をデコードしてからサニタイズ
        $category = isset($_POST['category']) ? sanitize_text_field(urldecode($_POST['category'])) : '';
        $cols = isset($_POST['cols']) ? intval($_POST['cols']) : 3;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 2;
        $design = isset($_POST['design']) ? sanitize_text_field($_POST['design']) : 'grid';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '';
        $color = $color ?: '#333333';
        $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '_self';
        $description_length = isset($_POST['description_length']) ? intval($_POST['description_length']) : 20;
        $autoplay = isset($_POST['autoplay']) ? sanitize_text_field($_POST['autoplay']) : 'false';
        $loop = isset($_POST['loop']) ? sanitize_text_field($_POST['loop']) : 'true';
        $interval = isset($_POST['interval']) ? max(1000, intval($_POST['interval'])) : 3000;

        // 表示オプション
        $show_title = isset($_POST['show_title']) ? sanitize_text_field($_POST['show_title']) : 'true';
        $title_tag = isset($_POST['title_tag']) ? sanitize_text_field($_POST['title_tag']) : 'h2';
        $show_date = isset($_POST['show_date']) ? sanitize_text_field($_POST['show_date']) : 'true';
        $show_author = isset($_POST['show_author']) ? sanitize_text_field($_POST['show_author']) : 'false';
        $show_excerpt = isset($_POST['show_excerpt']) ? sanitize_text_field($_POST['show_excerpt']) : 'true';
        $show_category = isset($_POST['show_category']) ? sanitize_text_field($_POST['show_category']) : 'false';
        $show_tags = isset($_POST['show_tags']) ? sanitize_text_field($_POST['show_tags']) : 'false';
        $show_read_more = isset($_POST['show_read_more']) ? sanitize_text_field($_POST['show_read_more']) : 'false';
        $read_more_text = isset($_POST['read_more_text']) ? sanitize_text_field($_POST['read_more_text']) : '続きを読む';
        $date_format = isset($_POST['date_format']) ? sanitize_text_field($_POST['date_format']) : 'Y.m.d';

        // サムネイル設定
        $show_thumbnail = isset($_POST['show_thumbnail']) ? sanitize_text_field($_POST['show_thumbnail']) : 'true';
        $thumbnail_position = isset($_POST['thumbnail_position']) ? sanitize_text_field($_POST['thumbnail_position']) : 'top';
        $thumbnail_size = isset($_POST['thumbnail_size']) ? sanitize_text_field($_POST['thumbnail_size']) : 'full';

        // ページネーション設定
        $pagination = isset($_POST['pagination']) ? sanitize_text_field($_POST['pagination']) : 'false';
        $pagination_type = isset($_POST['pagination_type']) ? sanitize_text_field($_POST['pagination_type']) : 'numbers';
        $pagination_position = isset($_POST['pagination_position']) ? sanitize_text_field($_POST['pagination_position']) : 'both';

        // ソート設定
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';

        $params[] = 'post_type="' . $post_type . '"';

        if (!empty($category)) {
            $params[] = 'category="' . $category . '"';
        }

                // デザインに応じて必要なパラメータのみ追加
        if ($design === 'list') {
            $params[] = 'cols="1"';
            $params[] = 'rows="' . $rows . '"';
        } else if ($design === 'grid') {
            $params[] = 'cols="' . $cols . '"';
            $params[] = 'rows="' . $rows . '"';
        } else if ($design === 'carousel') {
            $params[] = 'cols="' . $cols . '"';
            // カルーセルはrowsを出力しない
        }

        $params[] = 'design="' . $design . '"';
        $params[] = 'color="' . $color . '"';
        $params[] = 'target="' . $target . '"';
        $params[] = 'description_length="' . $description_length . '"';

        // 表示オプション（デフォルトと異なる場合のみ追加）
        if ($show_title !== 'true') {
            $params[] = 'show_title="' . $show_title . '"';
        }
        if ($title_tag !== 'h2') {
            $params[] = 'title_tag="' . $title_tag . '"';
        }
        if ($show_date !== 'true') {
            $params[] = 'show_date="' . $show_date . '"';
        }
        if ($show_author !== 'false') {
            $params[] = 'show_author="' . $show_author . '"';
        }
        if ($show_excerpt !== 'true') {
            $params[] = 'show_excerpt="' . $show_excerpt . '"';
        }
        if ($show_category !== 'false') {
            $params[] = 'show_category="' . $show_category . '"';
        }
        if ($show_tags !== 'false') {
            $params[] = 'show_tags="' . $show_tags . '"';
        }
        if ($show_read_more !== 'false') {
            $params[] = 'show_read_more="' . $show_read_more . '"';
        }
        if ($read_more_text !== '続きを読む') {
            $params[] = 'read_more_text="' . $read_more_text . '"';
        }
        if ($date_format !== 'Y.m.d') {
            $params[] = 'date_format="' . $date_format . '"';
        }

        // サムネイル設定（デフォルトと異なる場合のみ追加）
        if ($show_thumbnail !== 'true') {
            $params[] = 'show_thumbnail="' . $show_thumbnail . '"';
        }
        if ($thumbnail_position !== 'top') {
            $params[] = 'thumbnail_position="' . $thumbnail_position . '"';
        }
        if ($thumbnail_size !== 'full') {
            $params[] = 'thumbnail_size="' . $thumbnail_size . '"';
        }

        // ソート設定（デフォルトと異なる場合のみ追加）
        if ($orderby !== 'date') {
            $params[] = 'orderby="' . $orderby . '"';
        }
        if ($order !== 'DESC') {
            $params[] = 'order="' . $order . '"';
        }

        // ページネーション設定（デフォルトと異なる場合のみ追加）
        if ($pagination !== 'false') {
            $params[] = 'pagination="' . $pagination . '"';
        }
        if ($pagination_type !== 'numbers') {
            $params[] = 'pagination_type="' . $pagination_type . '"';
        }
        if ($pagination_position !== 'both') {
            $params[] = 'pagination_position="' . $pagination_position . '"';
        }

        // カルーセル専用パラメータ
        if ($design === 'carousel') {
            $params[] = 'autoplay="' . $autoplay . '"';
            $params[] = 'loop="' . $loop . '"';
            $params[] = 'interval="' . $interval . '"';
        }

        $shortcode = '[ksc_posts ' . implode(' ', $params) . ']';

        wp_send_json_success(array(
            'shortcode' => $shortcode
        ));
    }

            public function ajax_preview_shortcode() {
        check_ajax_referer('ksc_wizard_nonce', 'nonce');

        $shortcode = stripslashes(sanitize_text_field($_POST['shortcode']));
        $shortcode_content = do_shortcode($shortcode);

        // エスケープされた場合とされていない場合の両方をチェック
        $has_carousel = strpos($shortcode, 'design="carousel"') !== false ||
                       strpos($shortcode, 'design=\"carousel\"') !== false ||
                       strpos($_POST['shortcode'], 'design="carousel"') !== false;

        ob_start();
        ?>
        <div class="ksc-preview-wrapper" style="
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        ">
            <style>
                /* プレビュー専用のスタイル */
                .ksc-preview-wrapper * {
                    box-sizing: border-box;
                }
                .ksc-preview-wrapper img {
                    max-width: 100%;
                    height: auto;
                }
                <?php echo file_get_contents(KSC_PLUGIN_DIR . 'assets/css/ksc-styles.css'); ?>

                /* 管理画面のスタイルをリセット */
                .ksc-preview-wrapper .ksc-grid,
                .ksc-preview-wrapper .ksc-list,
                .ksc-preview-wrapper .ksc-carousel {
                    font-size: 14px;
                    line-height: 1.5;
                }
                .ksc-preview-wrapper a {
                    text-decoration: none;
                }
                .ksc-preview-wrapper h3 {
                    margin: 0;
                    padding: 0;
                    font-size: 18px;
                    font-weight: 600;
                }
                .ksc-preview-wrapper p {
                    margin: 0;
                    padding: 0;
                }

                /* プレビュー用のレスポンシブ対応 */
                @media (max-width: 768px) {
                    .ksc-preview-wrapper .ksc-cols-3,
                    .ksc-preview-wrapper .ksc-cols-4,
                    .ksc-preview-wrapper .ksc-cols-5,
                    .ksc-preview-wrapper .ksc-cols-6 {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                }

                @media (max-width: 480px) {
                    .ksc-preview-wrapper .ksc-cols-2,
                    .ksc-preview-wrapper .ksc-cols-3,
                    .ksc-preview-wrapper .ksc-cols-4,
                    .ksc-preview-wrapper .ksc-cols-5,
                    .ksc-preview-wrapper .ksc-cols-6 {
                        grid-template-columns: 1fr !important;
                    }
                }
            </style>

            <?php echo $shortcode_content; ?>

            <?php if ($has_carousel): ?>
            <script>
                (function() {
                    // カルーセル初期化を遅延実行
                    setTimeout(function() {
                        <?php echo file_get_contents(KSC_PLUGIN_DIR . 'assets/js/ksc-carousel.js'); ?>
                    }, 100);
                })();
            </script>
            <?php endif; ?>
        </div>
        <?php
        $preview = ob_get_clean();

        wp_send_json_success(array(
            'preview' => $preview,

        ));
    }

    public function render_wizard() {
        $post_types = get_post_types(array('public' => true), 'objects');

        /**
         * テストケース:
         * 1. 投稿 + カテゴリ選択 + グリッド
         * 2. 固定ページ + 親ページ選択 + リスト
         * 3. カスタム投稿タイプ + タクソノミー + カルーセル
         * 4. 各デザインパターンでのcols/rows設定確認
         * 5. カラー、ターゲット、説明文長の設定確認
         */
        ?>
        <div class="ksc-wizard">
            <h2>ショートコード作成ウィザード</h2>
            <p>以下の項目を設定して、簡単にショートコードを作成できます。</p>

            <!-- ステップインジケーター -->
            <div class="ksc-step-indicator" style="display: flex !important; align-items: center !important; justify-content: center !important; margin: 30px 0 40px 0 !important; padding: 20px !important; background: #f8f9fa !important; border-radius: 8px !important; border: 1px solid #e9ecef !important; width: 100% !important; box-sizing: border-box !important;">
                <div class="ksc-step-item ksc-step-active" data-step="1" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #0073aa !important; color: white !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">1</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #0073aa !important; text-align: center !important; min-width: 80px !important; font-weight: 600 !important;">投稿タイプ</div>
                </div>
                <div class="ksc-step-connector" style="flex: 1 !important; height: 2px !important; background: #ddd !important; margin: 0 10px !important; position: relative !important; top: -16px !important; z-index: 1 !important;"></div>
                <div class="ksc-step-item" data-step="2" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #ddd !important; color: #666 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">2</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #666 !important; text-align: center !important; min-width: 80px !important;">カテゴリ選択</div>
                </div>
                <div class="ksc-step-connector" style="flex: 1 !important; height: 2px !important; background: #ddd !important; margin: 0 10px !important; position: relative !important; top: -16px !important; z-index: 1 !important;"></div>
                <div class="ksc-step-item" data-step="3" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #ddd !important; color: #666 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">3</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #666 !important; text-align: center !important; min-width: 80px !important;">デザイン</div>
                </div>
                <div class="ksc-step-connector" style="flex: 1 !important; height: 2px !important; background: #ddd !important; margin: 0 10px !important; position: relative !important; top: -16px !important; z-index: 1 !important;"></div>
                <div class="ksc-step-item" data-step="4" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #ddd !important; color: #666 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">4</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #666 !important; text-align: center !important; min-width: 80px !important;">レイアウト設定</div>
                </div>
                <div class="ksc-step-connector" style="flex: 1 !important; height: 2px !important; background: #ddd !important; margin: 0 10px !important; position: relative !important; top: -16px !important; z-index: 1 !important;"></div>
                <div class="ksc-step-item" data-step="5" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #ddd !important; color: #666 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">5</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #666 !important; text-align: center !important; min-width: 80px !important;">表示内容設定</div>
                </div>
                <div class="ksc-step-connector" style="flex: 1 !important; height: 2px !important; background: #ddd !important; margin: 0 10px !important; position: relative !important; top: -16px !important; z-index: 1 !important;"></div>
                <div class="ksc-step-item" data-step="6" style="display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; z-index: 2 !important;">
                    <div class="ksc-step-number" style="width: 40px !important; height: 40px !important; border-radius: 50% !important; background: #ddd !important; color: #666 !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 16px !important; margin-bottom: 8px !important;">6</div>
                    <div class="ksc-step-label" style="font-size: 12px !important; color: #666 !important; text-align: center !important; min-width: 80px !important;">ソート・ページネーション</div>
                </div>
            </div>

            <form id="ksc-wizard-form">
                <?php wp_nonce_field('ksc_wizard_nonce', 'ksc_wizard_nonce'); ?>

                <div class="ksc-wizard-step ksc-wizard-step-1">
                    <h3>ステップ1: 投稿タイプを選択</h3>
                    <p>表示したい投稿の種類を選んでください。</p>
                    <p><small style="color: #666;">
                        標準の投稿・ページに加えて、カスタム投稿タイプ（商品、イベント、ポートフォリオなど）も選択できます。<br>
                        [カスタム]と表示されているものがカスタム投稿タイプです。
                    </small></p>
                    <select name="post_type" id="ksc-post-type" required>
                        <option value="">投稿タイプを選択してください</option>
                        <?php foreach ($post_types as $post_type):
                            $is_custom = !in_array($post_type->name, array('post', 'page', 'attachment'));
                            $custom_label = $is_custom ? ' [カスタム]' : '';
                            $post_count = wp_count_posts($post_type->name);
                            $total_posts = isset($post_count->publish) ? $post_count->publish : 0;
                        ?>
                            <option value="<?php echo esc_attr($post_type->name); ?>">
                                <?php echo esc_html($post_type->label); ?> (<?php echo esc_html($post_type->name); ?>)<?php echo $custom_label; ?> - <?php echo $total_posts; ?>件
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                                <div class="ksc-wizard-step ksc-wizard-step-2" style="display: none;">
                    <h3>ステップ2: カテゴリ・分類を選択（任意）</h3>
                    <p>特定のカテゴリや分類の投稿のみを表示したい場合は選択してください。投稿タイプによって利用可能な分類が変わります。</p>
                    <p><small style="color: #666;">
                        ・投稿: カテゴリ<br>
                        ・ページ: 親ページ<br>
                        ・カスタム投稿タイプ: 独自の分類（タクソノミー）
                    </small></p>
                    <select name="category" id="ksc-category">
                        <option value="">すべて</option>
                    </select>
                </div>

                <div class="ksc-wizard-step ksc-wizard-step-3" style="display: none;">
                    <h3>ステップ3: デザイン設定</h3>
                    <p>表示スタイルとカラーを選択してください。</p>

                    <div class="ksc-form-row">
                        <label for="ksc-design">デザインパターン:</label>
                        <select name="design" id="ksc-design">
                            <option value="grid" selected>グリッド表示</option>
                            <option value="list">リスト表示</option>
                            <option value="carousel">カルーセル表示</option>
                        </select>
                    </div>

                    <div class="ksc-form-row">
                        <label for="ksc-color">テキストカラー:</label>
                        <input type="color" name="color" id="ksc-color" value="#333333">
                    </div>
                </div>

                <div class="ksc-wizard-step ksc-wizard-step-4" id="ksc-grid-settings" style="display: none;">
                    <h3>ステップ4: 表示設定</h3>
                    <p><span id="ksc-settings-description">グリッド表示の列数と行数を設定してください。</span></p>

                    <!-- 件数情報 -->
                    <div id="ksc-layout-info" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 15px 0; color: #0073aa;">📊 投稿件数</h4>
                        <div id="ksc-post-count-info">
                            <p style="margin: 0; font-weight: 600; color: #333;">投稿件数を確認中...</p>
                        </div>
                    </div>

                    <div class="ksc-form-row">
                        <label for="ksc-cols">横に表示する件数:</label>
                        <select name="cols" id="ksc-cols">
                            <option value="1">1件</option>
                            <option value="2">2件</option>
                            <option value="3" selected>3件</option>
                            <option value="4">4件</option>
                            <option value="5">5件</option>
                            <option value="6">6件</option>
                        </select>
                    </div>

                    <div class="ksc-form-row" id="ksc-rows-setting">
                        <label for="ksc-rows">行数:</label>
                        <select name="rows" id="ksc-rows">
                            <option value="1">1行</option>
                            <option value="2" selected>2行</option>
                            <option value="3">3行</option>
                            <option value="4">4行</option>
                            <option value="5">5行</option>
                        </select>
                    </div>

                    <!-- レイアウト計算結果の表示 -->
                    <div id="ksc-layout-calculation" style="background: #e8f5e8; border: 1px solid #46b450; border-radius: 8px; padding: 15px; margin-top: 20px; display: none;">
                        <h5 style="margin: 0 0 10px 0; color: #2d5a2d;">🧮 レイアウト計算結果</h5>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                            <div>
                                <strong>選択された設定:</strong><br>
                                列数: <span id="ksc-selected-cols">3</span> × 行数: <span id="ksc-selected-rows">2</span><br>
                                表示件数: <span id="ksc-display-count">6</span>件
                            </div>
                            <div>
                                <strong>利用可能な件数:</strong><br>
                                総投稿件数: <span id="ksc-total-count">0</span>件<br>
                                残り件数: <span id="ksc-remaining-count">0</span>件
                            </div>
                        </div>
                    </div>
                </div>

                                <div class="ksc-wizard-step ksc-wizard-step-5" style="display: none;">
                    <h3>ステップ5: 表示内容設定</h3>
                    <p>表示する情報を設定してください。</p>

                    <div class="ksc-display-options">
                        <!-- 1. 主要コンテンツ（最重要項目をまとめて） -->
                        <div class="ksc-setting-group">
                            <h4>📝 主要コンテンツ</h4>
                            <div class="ksc-options-grid">
                                <label class="ksc-option-item ksc-main-toggle">
                                    <input type="checkbox" name="show_title" id="ksc-show-title" checked>
                                    <span>タイトル</span>
                                </label>
                                <label class="ksc-option-item ksc-main-toggle">
                                    <input type="checkbox" name="show_thumbnail" id="ksc-show-thumbnail" checked>
                                    <span>サムネイル画像</span>
                                </label>
                                <label class="ksc-option-item">
                                    <input type="checkbox" name="show_excerpt" id="ksc-show-excerpt" checked>
                                    <span>抜粋文</span>
                                </label>
                                <label class="ksc-option-item ksc-main-toggle">
                                    <input type="checkbox" name="show_read_more" id="ksc-show-read-more">
                                    <span>続きを読むリンク</span>
                                </label>
                            </div>

                            <!-- タイトルタグ設定 -->
                            <div class="ksc-sub-options" id="ksc-title-options" style="margin-top: 10px;">
                                <div class="ksc-sub-row">
                                    <label for="ksc-title-tag">タイトルタグ:</label>
                                    <select name="title_tag" id="ksc-title-tag" style="width: 80px;">
                                        <option value="h1">H1</option>
                                        <option value="h2" selected>H2</option>
                                        <option value="h3">H3</option>
                                        <option value="h4">H4</option>
                                        <option value="h5">H5</option>
                                        <option value="h6">H6</option>
                                        <option value="div">DIV</option>
                                    </select>
                                </div>
                            </div>

                            <!-- サムネイル詳細設定 -->
                            <div class="ksc-sub-options" id="ksc-thumbnail-options" style="margin-top: 10px;">
                                <div class="ksc-sub-row">
                                    <label for="ksc-thumbnail-position">画像位置:</label>
                                    <select name="thumbnail_position" id="ksc-thumbnail-position" style="width: 80px;">
                                        <option value="top">上</option>
                                        <option value="left">左</option>
                                        <option value="right">右</option>
                                    </select>

                                    <label for="ksc-thumbnail-size" style="margin-left: 15px;">サイズ:</label>
                                    <select name="thumbnail_size" id="ksc-thumbnail-size" style="width: 100px;">
                                        <option value="thumbnail">小</option>
                                        <option value="medium" selected>中</option>
                                        <option value="large">大</option>
                                        <option value="full">フル</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 続きを読むテキスト設定 -->
                            <div class="ksc-sub-options" id="ksc-read-more-options" style="display: none; margin-top: 10px;">
                                <div class="ksc-sub-row">
                                    <label for="ksc-read-more-text">リンクテキスト:</label>
                                    <input type="text" name="read_more_text" id="ksc-read-more-text" value="続きを読む" style="width: 150px;">
                                </div>
                            </div>
                        </div>

                        <!-- 2. 投稿情報（日付・カテゴリなど） -->
                        <div class="ksc-setting-group">
                            <h4>📊 投稿情報</h4>
                            <div class="ksc-options-grid">
                                <label class="ksc-option-item ksc-main-toggle">
                                    <input type="checkbox" name="show_date" id="ksc-show-date" checked>
                                    <span>日付</span>
                                </label>
                                <label class="ksc-option-item">
                                    <input type="checkbox" name="show_category" id="ksc-show-category">
                                    <span>カテゴリ</span>
                                </label>
                                <label class="ksc-option-item">
                                    <input type="checkbox" name="show_author" id="ksc-show-author">
                                    <span>投稿者</span>
                                </label>

                                <label class="ksc-option-item">
                                    <input type="checkbox" name="show_category" id="ksc-show-category">
                                    <span>カテゴリ・分類</span>
                                </label>

                                <label class="ksc-option-item">
                                    <input type="checkbox" name="show_tags" id="ksc-show-tags">
                                    <span>タグ</span>
                                </label>
                            </div>

                            <!-- 日付フォーマット設定 -->
                            <div class="ksc-sub-options" id="ksc-date-options" style="margin-top: 10px;">
                                <div class="ksc-sub-row">
                                    <label for="ksc-date-format">日付形式:</label>
                                    <select name="date_format" id="ksc-date-format" style="width: 150px;">
                                        <option value="Y.m.d" selected>2024.01.15</option>
                                        <option value="Y年m月d日">2024年1月15日</option>
                                        <option value="Y/m/d">2024/01/15</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 3. ソート設定（シンプルに） -->
                        <div class="ksc-setting-group" style="margin-top: 20px;">
                            <h4>🔄 並び順</h4>

                            <div class="ksc-form-row" style="display: flex; gap: 15px;">
                                <div style="flex: 1;">
                                    <label for="ksc-orderby">基準:</label>
                                    <select name="orderby" id="ksc-orderby" style="width: 100%;">
                                        <option value="date" selected>投稿日</option>
                                        <option value="title">タイトル</option>
                                        <option value="modified">更新日</option>
                                        <option value="rand">ランダム</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label for="ksc-order">順序:</label>
                                    <select name="order" id="ksc-order" style="width: 100%;">
                                        <option value="DESC" selected>新→古</option>
                                        <option value="ASC">古→新</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 4. ページネーション（オプション） -->
                        <div class="ksc-setting-group" style="margin-top: 20px;">
                            <h4>📄 ページネーション</h4>
                            <label class="ksc-option-item ksc-main-toggle">
                                <input type="checkbox" name="pagination" id="ksc-pagination">
                                <span>ページ分割を有効化</span>
                            </label>
                            <small style="color: #666;">※カルーセルでは使用不可</small>

                            <div class="ksc-sub-options" id="ksc-pagination-options" style="display: none; margin-top: 10px;">
                                <div class="ksc-form-row">
                                    <label for="ksc-pagination-type">表示形式:</label>
                                    <select name="pagination_type" id="ksc-pagination-type" style="width: 150px;">
                                        <option value="numbers" selected>数字 (1 2 3)</option>
                                        <option value="arrows">矢印 (前へ 次へ)</option>
                                    </select>
                                    <label for="ksc-pagination-position" style="margin-left: 15px;">位置:</label>
                                    <select name="pagination_position" id="ksc-pagination-position" style="width: 100px;">
                                        <option value="both" selected>上下</option>
                                        <option value="top">上</option>
                                        <option value="bottom">下</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ksc-wizard-step ksc-wizard-step-6" style="display: none;">
                    <h3>ステップ6: 詳細設定</h3>
                    <p>リンクの動作と説明文の長さを設定してください。</p>

                    <div class="ksc-form-row">
                        <label for="ksc-target">リンクターゲット:</label>
                        <select name="target" id="ksc-target">
                            <option value="_self" selected>同じウィンドウで開く</option>
                            <option value="_blank">新しいウィンドウで開く</option>
                        </select>
                    </div>

                    <div class="ksc-form-row">
                        <label for="ksc-description-length">説明文の長さ（単語数）:</label>
                        <input type="number" name="description_length" id="ksc-description-length" value="150" min="5" max="500">
                    </div>

                    <div id="ksc-carousel-settings" style="display: none;">
                        <h4>カルーセル設定</h4>

                        <div class="ksc-form-row">
                            <label for="ksc-autoplay">自動再生:</label>
                            <select name="autoplay" id="ksc-autoplay">
                                <option value="false" selected>オフ</option>
                                <option value="true">オン</option>
                            </select>
                        </div>

                        <div class="ksc-form-row">
                            <label for="ksc-loop">無限ループ:</label>
                            <select name="loop" id="ksc-loop">
                                <option value="true" selected>オン</option>
                                <option value="false">オフ</option>
                            </select>
                        </div>

                        <div class="ksc-form-row">
                            <label for="ksc-interval">切り替え間隔（ミリ秒）:</label>
                            <input type="number" name="interval" id="ksc-interval" value="3000" min="1000" max="10000" step="500">
                        </div>
                    </div>
                </div>

                <div class="ksc-wizard-navigation">
                    <button type="button" id="ksc-wizard-prev">前へ</button>
                    <button type="button" id="ksc-wizard-next">次へ</button>
                    <button type="button" id="ksc-wizard-generate" style="display: none;">ショートコード生成</button>
                </div>
            </form>

                        <div id="ksc-wizard-result" style="display: none;">
                <!-- コード切り替えタブ -->
                <div class="ksc-code-tabs" style="margin-bottom: 20px;">
                    <label style="margin-right: 20px; font-weight: bold;">
                        <input type="radio" name="code-type" value="shortcode" checked>
                        ショートコード
                    </label>
                    <label style="font-weight: bold;">
                        <input type="radio" name="code-type" value="php">
                        PHPコード（テンプレート用）
                    </label>
                </div>

                <!-- ショートコード表示エリア -->
                <div id="ksc-shortcode-section">
                    <h3>生成されたショートコード</h3>
                    <p style="color: #666; font-size: 12px; margin-bottom: 10px;">
                        ※ 投稿や固定ページのエディタで使用できます
                    </p>
                    <div class="ksc-shortcode-output">
                        <textarea id="ksc-generated-shortcode" readonly></textarea>
                        <button type="button" id="ksc-copy-shortcode">コピー</button>
                    </div>
                </div>

                <!-- PHPコード表示エリア -->
                <div id="ksc-php-section" style="display: none;">
                    <h3>PHPコード（テンプレート用）</h3>
                    <p style="color: #666; font-size: 12px; margin-bottom: 10px;">
                        ※ archive.php、category.php、カスタムテンプレートなどで使用できます
                    </p>
                    <div class="ksc-shortcode-output">
                        <textarea id="ksc-generated-php" readonly style="height: 400px;"></textarea>
                        <button type="button" id="ksc-copy-php">PHPコードをコピー</button>
                    </div>
                </div>

                <h3>プレビュー</h3>
                <p style="color: #666; font-size: 12px; margin-bottom: 10px;">
                    ※ 実際の表示はテーマのスタイルによって異なる場合があります
                </p>
                <div id="ksc-preview-container">
                    <div id="ksc-preview-content"></div>
                </div>

                <div class="ksc-result-buttons">
                    <button type="button" id="ksc-wizard-back">← 設定を変更する</button>
                <button type="button" id="ksc-wizard-reset">新しいショートコードを作成</button>
                </div>
            </div>
        </div>
        <?php
    }
}
