<?php
if (!defined('ABSPATH')) {
    exit;
}

class KSC_Display {
    private static $css_generated = false;

    private function generate_dynamic_css($atts) {
        // 一度だけCSSを生成
        if (self::$css_generated) {
            return '';
        }

        // ブレイクポイント設定を取得
        $mobile_breakpoint = !empty($atts['mobile_breakpoint']) ? intval($atts['mobile_breakpoint']) : get_option('ksc_mobile_breakpoint', 768);
        $tablet_breakpoint = !empty($atts['tablet_breakpoint']) ? intval($atts['tablet_breakpoint']) : get_option('ksc_tablet_breakpoint', 1024);

        // 動的CSSを生成
        $css = '<style id="ksc-dynamic-css">';

        // モバイルブレイクポイント
        $css .= '@media (max-width: ' . $mobile_breakpoint . 'px) {';
        $css .= '.ksc-grid, .ksc-pagination-content .ksc-grid, div.ksc-grid, .ksc-grid.ksc-cols-3, .ksc-grid.ksc-cols-4, .ksc-grid.ksc-cols-5, .ksc-grid.ksc-cols-6 { grid-template-columns: 1fr !important; }';
        $css .= '.ksc-carousel .ksc-item, .ksc-carousel.ksc-cols-2 .ksc-item, .ksc-carousel.ksc-cols-3 .ksc-item, .ksc-carousel.ksc-cols-4 .ksc-item, .ksc-carousel.ksc-cols-5 .ksc-item, .ksc-carousel.ksc-cols-6 .ksc-item { flex: 0 0 100%; }';
        $css .= '.ksc-carousel-inner { gap: 0; }';
        $css .= '}';

        // タブレットブレイクポイント（段階的な列数削減）
        if ($tablet_breakpoint > $mobile_breakpoint) {
            $css .= '@media (min-width: ' . ($mobile_breakpoint + 1) . 'px) and (max-width: ' . $tablet_breakpoint . 'px) {';
            // 3列 → 2列
            $css .= '.ksc-grid.ksc-cols-3 { grid-template-columns: repeat(2, 1fr) !important; }';
            $css .= '.ksc-carousel.ksc-cols-3 .ksc-item { flex: 0 0 calc((100% - 20px) / 2); }';
            // 4列 → 3列（元は2列だったが3列に変更）
            $css .= '.ksc-grid.ksc-cols-4 { grid-template-columns: repeat(3, 1fr) !important; }';
            $css .= '.ksc-carousel.ksc-cols-4 .ksc-item { flex: 0 0 calc((100% - 40px) / 3); }';
            // 5列 → 3列
            $css .= '.ksc-grid.ksc-cols-5 { grid-template-columns: repeat(3, 1fr) !important; }';
            $css .= '.ksc-carousel.ksc-cols-5 .ksc-item { flex: 0 0 calc((100% - 40px) / 3); }';
            // 6列 → 3列
            $css .= '.ksc-grid.ksc-cols-6 { grid-template-columns: repeat(3, 1fr) !important; }';
            $css .= '.ksc-carousel.ksc-cols-6 .ksc-item { flex: 0 0 calc((100% - 40px) / 3); }';
            // 1列と2列は変更なし（元の設定を維持）
            $css .= '}';
        }

        // デスクトップサイズで元の列数を確実に適用
        $css .= '@media (min-width: ' . ($tablet_breakpoint + 1) . 'px) {';
        $css .= '.ksc-grid.ksc-cols-4 { grid-template-columns: repeat(4, 1fr) !important; }';
        $css .= '.ksc-grid.ksc-cols-5 { grid-template-columns: repeat(5, 1fr) !important; }';
        $css .= '.ksc-grid.ksc-cols-6 { grid-template-columns: repeat(6, 1fr) !important; }';
        $css .= '}';

        $css .= '</style>';

        self::$css_generated = true;
        return $css;
    }
        public function render($atts) {
        // 動的CSSを生成して結果を保持
        $dynamic_css = $this->generate_dynamic_css($atts);
        // ページネーション設定の確認
        $pagination = isset($atts['pagination']) ? $atts['pagination'] === 'true' : false;

        // カルーセルの場合は必要な数だけ取得、ページネーション使用時は全件取得
        $posts_per_page = intval($atts['cols']) * intval($atts['rows']);
        if ($atts['design'] === 'carousel') {
            // カルーセルでも必要以上の投稿は取得しない
            $posts_per_page = -1; // 全件取得して後でフィルタリング
        } else if ($pagination) {
            // ページネーション使用時は全件取得
            $posts_per_page = -1;
        }

        $args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => $posts_per_page,
            'post_status' => 'publish',
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );

                if (!empty($atts['category'])) {
            if ($atts['post_type'] === 'post') {
                $args['category_name'] = $atts['category'];
            } else if ($atts['post_type'] === 'page') {
                // page-ID形式のスラッグからIDを抽出
                if (strpos($atts['category'], 'page-') === 0) {
                    $parent_id = intval(substr($atts['category'], 5));
                    if ($parent_id > 0) {
                        $args['post_parent'] = $parent_id;
                    }
                } else {
                    // 旧形式（スラッグ）のフォールバック
                    $parent_page = get_page_by_path($atts['category']);
                    if ($parent_page) {
                        $args['post_parent'] = $parent_page->ID;
                    }
                }
            } else {
                // カスタム投稿タイプのタクソノミー処理
                $taxonomies = get_object_taxonomies($atts['post_type'], 'names');
                if (!empty($taxonomies)) {
                    // post_formatなどの内部タクソノミーを除外
                    $public_taxonomies = array();
                    foreach ($taxonomies as $taxonomy) {
                        $taxonomy_obj = get_taxonomy($taxonomy);
                        if ($taxonomy_obj && $taxonomy_obj->public && !in_array($taxonomy, array('post_format'))) {
                            $public_taxonomies[] = $taxonomy;
                        }
                    }

                    $term = null;
                    // 最初にメインタクソノミーで検索
                    if (!empty($public_taxonomies)) {
                        $term = get_term_by('slug', $atts['category'], reset($public_taxonomies));
                    }

                    // 見つからない場合は他のタクソノミーでも検索
                    if (!$term) {
                        foreach ($public_taxonomies as $taxonomy) {
                            $term = get_term_by('slug', $atts['category'], $taxonomy);
                            if ($term) {
                                break;
                            }
                        }
                    }

                    if ($term) {
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => $term->taxonomy,
                                'field' => 'slug',
                                'terms' => $atts['category']
                            )
                        );
                    }
                }
            }
        }

                        $posts = get_posts($args);

        // デバッグログは本番では出力しない

        // カルーセルの場合、投稿数を調整して空白枠を防ぐ
        if ($atts['design'] === 'carousel' && count($posts) > 0) {
            $cols = intval($atts['cols']);
            $post_count = count($posts);

            // 投稿数がカラム数より少ない場合は、投稿数に合わせてカラム数を調整
            if ($post_count < $cols) {
                $atts['cols'] = $post_count;
            }

            $atts['_actual_post_count'] = $post_count;
        }

                // ページネーション設定（すでに上で設定済み）
        $pagination_type = isset($atts['pagination_type']) ? $atts['pagination_type'] : 'numbers';



        // プレビュー時の代替投稿取得
        $is_preview = defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'ksc_preview_shortcode';

        if (empty($posts) && $is_preview) {
            // プレビュー時で投稿が見つからない場合、最新の投稿を取得

            // 新しいクエリで最新の投稿を取得
            $preview_args = array(
                'post_type' => $atts['post_type'],
                'posts_per_page' => min(intval($atts['cols']) * intval($atts['rows']), 6),
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $posts = get_posts($preview_args);
        }

        if (empty($posts)) {
            return '<div class="ksc-no-posts"><p>投稿が見つかりませんでした。</p></div>';
        }

        ob_start();

        // 動的CSSを最初に出力
        if (!empty($dynamic_css)) {
            echo $dynamic_css;
        }

        // ページネーション対応の場合
        if ($pagination && $atts['design'] !== 'carousel') {
            $this->render_with_pagination($posts, $atts);
        } else {

            switch ($atts['design']) {
                case 'list':
                    $this->render_list($posts, $atts);
                    break;
                case 'carousel':
                    $this->render_carousel($posts, $atts);
                    break;
                case 'grid':
                default:
                    $this->render_grid($posts, $atts);
                    break;
            }
        }

        return ob_get_clean();
    }

    private function render_grid($posts, $atts) {
        $cols = intval($atts['cols']);
        $color = esc_attr($atts['color']);

        // ブレイクポイント設定を取得
        $mobile_breakpoint = !empty($atts['mobile_breakpoint']) ? intval($atts['mobile_breakpoint']) : get_option('ksc_mobile_breakpoint', 768);
        $tablet_breakpoint = !empty($atts['tablet_breakpoint']) ? intval($atts['tablet_breakpoint']) : get_option('ksc_tablet_breakpoint', 1024);

        echo '<div class="ksc-grid ksc-cols-' . $cols . '" data-mobile-breakpoint="' . $mobile_breakpoint . '" data-tablet-breakpoint="' . $tablet_breakpoint . '" style="--ksc-color: ' . $color . ';">';

        foreach ($posts as $post) {
            $this->render_post_item($post, 'grid', $atts);
        }

        echo '</div>';
    }

    private function render_list($posts, $atts) {
        $color = esc_attr($atts['color']);

        echo '<div class="ksc-list" style="--ksc-color: ' . $color . ';">';

        foreach ($posts as $post) {
            $this->render_post_item($post, 'list', $atts);
        }

        echo '</div>';
    }

        private function render_carousel($posts, $atts) {
        $cols = intval($atts['cols']);
        $color = esc_attr($atts['color']);
        $autoplay = $atts['autoplay'] === 'true' ? 'true' : 'false';
        $loop = $atts['loop'] === 'false' ? 'false' : 'true';
        $interval = max(1000, intval($atts['interval']));

        $actual_post_count = isset($atts['_actual_post_count']) ? $atts['_actual_post_count'] : count($posts);

        // 実際に表示するカラム数（投稿数が少ない場合は調整）
        $effective_cols = min($cols, $actual_post_count);

        // ブレイクポイント設定を取得（優先順位: ショートコード属性 > 管理画面設定 > デフォルト）
        $mobile_breakpoint = !empty($atts['mobile_breakpoint']) ? intval($atts['mobile_breakpoint']) : get_option('ksc_mobile_breakpoint', 768);
        $tablet_breakpoint = !empty($atts['tablet_breakpoint']) ? intval($atts['tablet_breakpoint']) : get_option('ksc_tablet_breakpoint', 1024);

        echo '<div class="ksc-carousel-wrapper">';
        echo '<div class="ksc-carousel ksc-cols-' . $cols . '"
                   data-autoplay="' . $autoplay . '"
                   data-loop="' . $loop . '"
                   data-interval="' . $interval . '"
                   data-post-count="' . $actual_post_count . '"
                   data-effective-cols="' . $effective_cols . '"
                   data-mobile-breakpoint="' . $mobile_breakpoint . '"
                   data-tablet-breakpoint="' . $tablet_breakpoint . '"
                   style="--ksc-color: ' . $color . '; --ksc-effective-cols: ' . $effective_cols . ';">';
        echo '<div class="ksc-carousel-inner">';

        foreach ($posts as $post) {
            $this->render_post_item($post, 'carousel', $atts);
        }

        echo '</div>';
        echo '<button class="ksc-carousel-prev">&lt;</button>';
        echo '<button class="ksc-carousel-next">&gt;</button>';
        echo '</div>';
        echo '</div>';
    }

    private function render_post_item($post, $design, $atts = array()) {
        $permalink = get_permalink($post->ID);
        $title = get_the_title($post->ID);
        $description_length = isset($atts['description_length']) ? intval($atts['description_length']) : 20;
        $target = isset($atts['target']) ? esc_attr($atts['target']) : '_self';

        // 表示オプションの設定
        $show_title = isset($atts['show_title']) ? $atts['show_title'] === 'true' : true;
        $show_date = isset($atts['show_date']) ? $atts['show_date'] === 'true' : true;
        $show_author = isset($atts['show_author']) ? $atts['show_author'] === 'true' : false;
        $show_excerpt = isset($atts['show_excerpt']) ? $atts['show_excerpt'] === 'true' : true;
        $show_category = isset($atts['show_category']) ? $atts['show_category'] === 'true' : false;
        $show_tags = isset($atts['show_tags']) ? $atts['show_tags'] === 'true' : false;
        $show_read_more = isset($atts['show_read_more']) ? $atts['show_read_more'] === 'true' : false;
        $read_more_text = isset($atts['read_more_text']) ? $atts['read_more_text'] : '続きを読む';
        $date_format = isset($atts['date_format']) ? $atts['date_format'] : 'Y.m.d';

        // サムネイル設定（リスト表示の場合は強制的に無効化）
        if ($design === 'list') {
            $show_thumbnail = false; // リスト表示では常にサムネイル非表示
        } else {
            $show_thumbnail = isset($atts['show_thumbnail']) ? $atts['show_thumbnail'] === 'true' : true;
        }
        // thumbnail_positionのデフォルト値を 'top' に設定
        $thumbnail_position = isset($atts['thumbnail_position']) && !empty($atts['thumbnail_position']) ? $atts['thumbnail_position'] : 'top';
        $thumbnail_size = isset($atts['thumbnail_size']) ? $atts['thumbnail_size'] : 'full';

        // サムネイルURL取得
        $thumbnail_url = $show_thumbnail ? get_the_post_thumbnail_url($post->ID, $thumbnail_size) : '';

        // アイテムのクラスにサムネイル位置を追加
        $item_classes = array('ksc-item', 'ksc-item-' . $design);
        if ($show_thumbnail) {
            // サムネイル位置クラスを常に追加
            $item_classes[] = 'ksc-thumbnail-' . $thumbnail_position;
            if (!$thumbnail_url) {
                // サムネイルが設定されていない場合はno-thumbnailクラスも追加
                $item_classes[] = 'ksc-no-thumbnail';
            }
        } else {
            // サムネイル表示がOFFの場合
            $item_classes[] = 'ksc-thumbnail-disabled';
        }

        echo '<div class="' . implode(' ', $item_classes) . '">';

        // サムネイル表示（位置によって分岐）
        if ($show_thumbnail && $thumbnail_url && $thumbnail_position === 'top') {
            echo '<a href="' . esc_url($permalink) . '" target="' . $target . '" class="ksc-thumbnail ksc-thumbnail-top">';
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($title) . '">';
            echo '</a>';
        }

        // 左右配置のサムネイル表示
        if ($show_thumbnail && $thumbnail_url && in_array($thumbnail_position, ['left', 'right'])) {
            echo '<a href="' . esc_url($permalink) . '" target="' . $target . '" class="ksc-thumbnail ksc-thumbnail-' . $thumbnail_position . '">';
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($title) . '">';
            echo '</a>';
        }

        echo '<div class="ksc-content">';

        // 日付をタイトルの前に表示
        if ($show_date) {
            $formatted_date = get_the_date($date_format, $post->ID);
            if (empty($formatted_date)) {
                // フォールバック：デフォルトフォーマットを使用
                $formatted_date = get_the_date('Y.m.d', $post->ID);
            }
            echo '<div class="ksc-date-top">' . esc_html($formatted_date) . '</div>';
        }

        // タイトルの表示
        if ($show_title) {
            $title_tag = isset($atts['title_tag']) ? $atts['title_tag'] : 'h2';
            // タグのバリデーション
            $allowed_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div');
            if (!in_array($title_tag, $allowed_tags)) {
                $title_tag = 'h2';
            }
            echo '<' . $title_tag . ' class="ksc-title"><a href="' . esc_url($permalink) . '" target="' . $target . '">' . esc_html($title) . '</a></' . $title_tag . '>';
        }

        // その他のメタ情報の表示（投稿者、カテゴリ）
        if ($show_author || $show_category) {
            echo '<div class="ksc-meta">';

            if ($show_category) {
                $this->render_post_categories($post, $atts);
            }

            if ($show_author) {
                $author_name = get_the_author_meta('display_name', $post->post_author);
                if (empty($author_name)) {
                    $author_name = get_the_author_meta('user_login', $post->post_author);
                }
                if (!empty($author_name)) {
                    echo '<span class="ksc-author">投稿者: ' . esc_html($author_name) . '</span>';
                }
            }

            echo '</div>';
        }

        if ($show_excerpt) {
            $excerpt = $this->get_post_excerpt($post, $description_length);
            if (!empty($excerpt)) {
                echo '<div class="ksc-excerpt">' . esc_html($excerpt) . '</div>';
            } else {

            }
        }

        if ($show_tags) {
            $this->render_post_tags($post, $atts);
        }

        if ($show_read_more) {
            echo '<div class="ksc-read-more">';
            echo '<a href="' . esc_url($permalink) . '" target="' . $target . '" class="ksc-read-more-link">' . esc_html($read_more_text) . '</a>';
            echo '</div>';
        }

        echo '</div>';

        echo '</div>';
    }

        private function render_post_categories($post, $atts) {
        $post_type = $post->post_type;

        if ($post_type === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                echo '<span class="ksc-categories">カテゴリ: ';
                $cat_names = array();
                foreach ($categories as $category) {
                    $cat_names[] = esc_html($category->name);
                }
                echo implode(', ', $cat_names);
                echo '</span>';
            }
        } else if ($post_type === 'page') {
            // ページの場合は親ページを表示
            if ($post->post_parent > 0) {
                $parent_page = get_post($post->post_parent);
                if ($parent_page) {
                    echo '<span class="ksc-categories">親ページ: ' . esc_html($parent_page->post_title) . '</span>';
                }
            }
        } else {
            // カスタム投稿タイプのタクソノミー
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && !in_array($taxonomy->name, array('post_format', 'post_tag'))) {
                    $terms = get_the_terms($post->ID, $taxonomy->name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<span class="ksc-taxonomy ksc-taxonomy-' . esc_attr($taxonomy->name) . '">';
                        echo esc_html($taxonomy->label) . ': ';
                        $term_names = array();
                        foreach ($terms as $term) {
                            $term_names[] = esc_html($term->name);
                        }
                        echo implode(', ', $term_names);
                        echo '</span>';
                        break; // 最初のタクソノミーのみ表示
                    }
                }
            }
        }
    }

        private function get_post_excerpt($post, $description_length) {
        // 手動設定された抜粋があれば優先
        if (!empty($post->post_excerpt)) {
            return wp_trim_words($post->post_excerpt, $description_length);
        }

        // 投稿本文から抜粋を生成
        $content = $post->post_content;

        // ショートコード、HTMLタグを除去
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);

        // 改行や余分な空白を統一
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        if (empty($content)) {
            return '';
        }

        // 指定された長さで抜粋作成
        $excerpt = wp_trim_words($content, $description_length);

        return $excerpt;
    }

        private function render_post_tags($post, $atts) {
        $post_type = $post->post_type;

        if ($post_type === 'post') {
            $tags = get_the_tags($post->ID);
            if (!empty($tags)) {
                echo '<div class="ksc-tags">';
                echo '<span class="ksc-tags-label">タグ: </span>';
                foreach ($tags as $tag) {
                    echo '<span class="ksc-tag">' . esc_html($tag->name) . '</span>';
                }
                echo '</div>';
            }
        } else if ($post_type === 'page') {
            // ページ投稿タイプにはデフォルトでタグ機能がないため何も表示しない
            // カスタムタクソノミーが追加されている場合のみ処理
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && $taxonomy->hierarchical === false && $taxonomy->name !== 'post_format') {
                    $terms = get_the_terms($post->ID, $taxonomy->name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<div class="ksc-tags ksc-taxonomy-' . esc_attr($taxonomy->name) . '">';
                        echo '<span class="ksc-tags-label">' . esc_html($taxonomy->label) . ': </span>';
                        foreach ($terms as $term) {
                            echo '<span class="ksc-tag">' . esc_html($term->name) . '</span>';
                        }
                        echo '</div>';
                        break; // 最初の非階層タクソノミーのみ表示
                    }
                }
            }
        } else {
            // カスタム投稿タイプの場合、タグ相当のタクソノミーを探す
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && $taxonomy->hierarchical === false && $taxonomy->name !== 'post_format') {
                    $terms = get_the_terms($post->ID, $taxonomy->name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<div class="ksc-tags ksc-taxonomy-' . esc_attr($taxonomy->name) . '">';
                        echo '<span class="ksc-tags-label">' . esc_html($taxonomy->label) . ': </span>';
                        foreach ($terms as $term) {
                            echo '<span class="ksc-tag">' . esc_html($term->name) . '</span>';
                        }
                        echo '</div>';
                        break; // 最初の非階層タクソノミーのみ表示
                    }
                }
            }
        }
    }

    private function render_with_pagination($posts, $atts) {
        $cols = intval($atts['cols']);
        $rows = intval($atts['rows']);
        $per_page = $cols * $rows;
        $total_posts = count($posts);
        $total_pages = ceil($total_posts / $per_page);
        $design = $atts['design'];
        $color = esc_attr($atts['color']);
        $pagination_type = $atts['pagination_type'];



        // ユニークID生成
        $unique_id = 'ksc-' . wp_generate_uuid4();
        $atts['unique_id'] = $unique_id;

        // JavaScriptに必要なデータを格納
        $pagination_data = array(
            'posts' => array_map(function($post) use ($atts) {
                // サムネイル設定を適用（リスト表示の場合は強制的に無効化）
                $thumbnail_size = isset($atts['thumbnail_size']) ? $atts['thumbnail_size'] : 'full';
                if ($atts['design'] === 'list') {
                    $show_thumbnail = false; // リスト表示では常にサムネイル非表示
                } else {
                    $show_thumbnail = isset($atts['show_thumbnail']) ? $atts['show_thumbnail'] === 'true' : true;
                }

                return array(
                    'id' => $post->ID,
                    'title' => get_the_title($post->ID),
                    'permalink' => get_permalink($post->ID),
                    'thumbnail' => $show_thumbnail ? get_the_post_thumbnail_url($post->ID, $thumbnail_size) : '',
                    'excerpt' => $this->get_post_excerpt($post, intval($atts['description_length'])),
                    'date' => get_the_date($atts['date_format'], $post->ID),
                    'author' => get_the_author_meta('display_name', $post->post_author) ?: get_the_author_meta('user_login', $post->post_author),
                    'categories' => $this->get_post_categories_data($post),
                    'tags' => $this->get_post_tags_data($post)
                );
            }, $posts),
            'atts' => $atts,
            'per_page' => $per_page,
            'total_pages' => $total_pages
        );

                echo '<div class="ksc-pagination-wrapper" id="' . esc_attr($unique_id) . '" data-pagination="' . esc_attr(json_encode($pagination_data)) . '">';

        // 位置設定を取得
        $pagination_position = isset($atts['pagination_position']) ? $atts['pagination_position'] : 'both';

        // 上部のページネーションコントロール（topまたはbothの場合のみ表示）
        if ($total_pages > 1 && ($pagination_position === 'top' || $pagination_position === 'both')) {
            echo '<div class="ksc-pagination-top">';
            $this->render_pagination_controls($total_pages, $pagination_type, $unique_id, 'top', true);
            echo '</div>';
        }

        // コンテンツ
        echo '<div class="ksc-pagination-content">';
        $first_page_posts = array_slice($posts, 0, $per_page);

        if ($design === 'list') {
            $this->render_list($first_page_posts, $atts);
        } else {
            $this->render_grid($first_page_posts, $atts);
        }
        echo '</div>';

        // 下部のページネーションコントロール（bottomまたはbothの場合のみ表示）
        if ($total_pages > 1 && ($pagination_position === 'bottom' || $pagination_position === 'both')) {
            echo '<div class="ksc-pagination-bottom">';
            $this->render_pagination_controls($total_pages, $pagination_type, $unique_id, 'bottom', true);
            echo '</div>';
        }

        echo '</div>';
    }

        private function render_pagination_controls($total_pages, $type, $unique_id, $position = 'bottom', $is_loading = false) {
        $position_class = 'ksc-pagination-' . $position;
        $loading_class = $is_loading ? ' ksc-pagination-loading' : '';
        echo '<div class="ksc-pagination-controls ' . $position_class . $loading_class . '">';

        if ($type === 'arrows') {
            echo '<button class="ksc-pagination-btn ksc-pagination-prev" data-page="prev" disabled>‹ 前へ</button>';
            echo '<span class="ksc-pagination-info">1 / ' . $total_pages . '</span>';
            echo '<button class="ksc-pagination-btn ksc-pagination-next" data-page="next"' . ($total_pages <= 1 ? ' disabled' : '') . '>次へ ›</button>';
        } else {
            // 数字ページネーション（大量ページの場合は省略表示）
            // 初期状態はすべて通常ボタン（JavaScriptで動的に管理）
            if ($total_pages <= 7) {
                // 7ページ以下は全て表示
                for ($i = 1; $i <= $total_pages; $i++) {
                    echo '<button class="ksc-pagination-btn" data-page="' . $i . '">' . $i . '</button>';
                }
            } else {
                // 8ページ以上は省略表示
                echo '<button class="ksc-pagination-btn" data-page="1">1</button>';
                echo '<button class="ksc-pagination-btn" data-page="2">2</button>';
                echo '<span class="ksc-pagination-dots">...</span>';
                echo '<button class="ksc-pagination-btn" data-page="' . ($total_pages - 1) . '">' . ($total_pages - 1) . '</button>';
                echo '<button class="ksc-pagination-btn" data-page="' . $total_pages . '">' . $total_pages . '</button>';
            }
        }

        echo '</div>';
    }

    private function get_post_categories_data($post) {
        $post_type = $post->post_type;

        if ($post_type === 'post') {
            $categories = get_the_category($post->ID);
            return !empty($categories) ? array_map(function($cat) { return $cat->name; }, $categories) : array();
        } else if ($post_type === 'page') {
            if ($post->post_parent > 0) {
                $parent_page = get_post($post->post_parent);
                return $parent_page ? array($parent_page->post_title) : array();
            }
        } else {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && !in_array($taxonomy->name, array('post_format', 'post_tag'))) {
                    $terms = get_the_terms($post->ID, $taxonomy->name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        return array_map(function($term) { return $term->name; }, $terms);
                    }
                }
            }
        }
        return array();
    }

    private function get_post_tags_data($post) {
        $post_type = $post->post_type;

        if ($post_type === 'post') {
            $tags = get_the_tags($post->ID);
            return !empty($tags) ? array_map(function($tag) { return $tag->name; }, $tags) : array();
        } else if ($post_type !== 'page') {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->public && $taxonomy->hierarchical === false && $taxonomy->name !== 'post_format') {
                    $terms = get_the_terms($post->ID, $taxonomy->name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        return array_map(function($term) { return $term->name; }, $terms);
                    }
                }
            }
        }
        return array();
    }
}
