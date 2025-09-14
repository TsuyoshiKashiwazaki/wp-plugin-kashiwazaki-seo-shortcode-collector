<?php
if (!defined('ABSPATH')) {
    exit;
}

class KSC_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_breakpoint_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Kashiwazaki Shortcode Collector',
            'Kashiwazaki Shortcode Collector',
            'manage_options',
            'kashiwazaki-shortcode-collector',
            array($this, 'display_admin_page'),
            'dashicons-shortcode',
            100
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_kashiwazaki-shortcode-collector') {
            return;
        }

        $cache_buster = KSC_VERSION . '.' . time();
        wp_enqueue_script('ksc-admin', KSC_PLUGIN_URL . 'assets/js/ksc-admin.js', array('jquery'), $cache_buster, true);
        wp_enqueue_script('ksc-wizard', KSC_PLUGIN_URL . 'assets/js/ksc-wizard.js', array('jquery'), $cache_buster, true);
        wp_enqueue_style('ksc-admin', KSC_PLUGIN_URL . 'assets/css/ksc-admin.css', array(), $cache_buster);
        wp_enqueue_style('ksc-wizard', KSC_PLUGIN_URL . 'assets/css/ksc-wizard.css', array(), $cache_buster);

        wp_localize_script('ksc-wizard', 'ksc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ksc_wizard_nonce')
        ));
    }

    public function register_settings() {
        register_setting('ksc_settings', 'ksc_options');
    }

    public function handle_breakpoint_settings() {
        if (isset($_POST['ksc_save_breakpoints']) && check_admin_referer('ksc_breakpoint_settings', 'ksc_breakpoint_nonce')) {
            $mobile_breakpoint = isset($_POST['ksc_mobile_breakpoint']) ? intval($_POST['ksc_mobile_breakpoint']) : 768;
            $tablet_breakpoint = isset($_POST['ksc_tablet_breakpoint']) ? intval($_POST['ksc_tablet_breakpoint']) : 1024;
            
            // 値の範囲をチェック
            $mobile_breakpoint = max(320, min(1200, $mobile_breakpoint));
            $tablet_breakpoint = max(768, min(1400, $tablet_breakpoint));
            
            update_option('ksc_mobile_breakpoint', $mobile_breakpoint);
            update_option('ksc_tablet_breakpoint', $tablet_breakpoint);
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>ブレイクポイント設定を保存しました。</p></div>';
            });
        }
    }

        public function display_admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap">
            <h1>Kashiwazaki Shortcode Collector</h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=kashiwazaki-shortcode-collector&tab=wizard" class="nav-tab <?php echo $active_tab === 'wizard' ? 'nav-tab-active' : ''; ?>">
                    ショートコード作成ウィザード
                </a>
                <a href="?page=kashiwazaki-shortcode-collector&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    設定・使い方
                </a>
            </nav>

            <?php if ($active_tab === 'wizard'): ?>
                <?php $this->display_wizard_tab(); ?>
            <?php else: ?>
                <?php $this->display_settings_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function display_wizard_tab() {
        $wizard = new KSC_Wizard();
        $wizard->render_wizard();
    }

    private function display_settings_tab() {
        ?>
        <div class="ksc-admin-content">
            <h2>利用可能な投稿タイプ</h2>
            <div class="ksc-post-types">
                <?php $this->display_post_types(); ?>
            </div>

            <h2>利用可能なカテゴリ</h2>
            <div class="ksc-categories">
                <?php $this->display_categories(); ?>
            </div>

            <h2>レスポンシブ設定</h2>
            <div class="ksc-responsive-settings" style="background: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
                <p style="margin-bottom: 15px;"><strong>モバイルブレイクポイント：</strong></p>
                <p style="margin-bottom: 10px;">1列表示に切り替える画面幅を指定できます（デフォルト: 768px）</p>
                <form method="post" action="">
                    <?php wp_nonce_field('ksc_breakpoint_settings', 'ksc_breakpoint_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">モバイルブレイクポイント (px)</th>
                            <td>
                                <input type="number" name="ksc_mobile_breakpoint" value="<?php echo esc_attr(get_option('ksc_mobile_breakpoint', 768)); ?>" min="320" max="1200" style="width: 100px;">
                                <p class="description">この幅以下で1列表示になります（推奨: 768px）</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">タブレットブレイクポイント (px)</th>
                            <td>
                                <input type="number" name="ksc_tablet_breakpoint" value="<?php echo esc_attr(get_option('ksc_tablet_breakpoint', 1024)); ?>" min="768" max="1400" style="width: 100px;">
                                <p class="description">この幅以下で列数が段階的に減少します（推奨: 1024px）</p>
                                <ul style="margin-top: 5px; font-size: 12px; color: #666;">
                                    <li>• 6列設定 → 3列表示</li>
                                    <li>• 5列設定 → 3列表示</li>
                                    <li>• 4列設定 → 2列表示</li>
                                    <li>• 3列設定 → 2列表示</li>
                                    <li>• 1-2列設定 → そのまま維持</li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="ksc_save_breakpoints" class="button-primary" value="設定を保存">
                    </p>
                </form>
                
                <h4>ショートコードでの個別指定</h4>
                <p>ショートコードごとに個別のブレイクポイントを指定することもできます：</p>
                <code>[ksc_posts post_type="post" mobile_breakpoint="600" tablet_breakpoint="900"]</code>
            </div>

                            <h2>ショートコードの使い方</h2>
                                <p style="margin-bottom: 20px;">
                    <strong>初心者の方へ：</strong>
                    <a href="<?php echo admin_url('admin.php?page=kashiwazaki-shortcode-collector&tab=wizard'); ?>" class="ksc-wizard-link" style="background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-left: 10px;">📝 ショートコード作成ウィザードを使う</a>
                </p>
                <div class="ksc-usage">
                                        <p><strong>グリッド表示：</strong></p>
                    <code>[ksc_posts post_type="post" category="news" cols="3" rows="2" design="grid" color="#333333"]</code>

                    <p><strong>リスト表示：</strong></p>
                    <code>[ksc_posts post_type="post" category="news" rows="5" design="list" color="#333333"]</code>

                    <p><strong>カルーセル表示：</strong></p>
                    <code>[ksc_posts post_type="post" design="carousel" cols="3" autoplay="true" loop="true" interval="4000"]</code>

                    <h3>パラメータ一覧</h3>
                    <ul>
                        <li><strong>post_type</strong>: 投稿タイプ（post, page, カスタム投稿タイプなど）
                            <br><small>例: "post", "page", "product", "event" など</small>
                        </li>
                        <li><strong>category</strong>: カテゴリ・分類スラッグ
                            <br><small>投稿: カテゴリスラッグ / ページ: 親ページID / カスタム投稿: タクソノミースラッグ</small>
                        </li>
                        <li><strong>cols</strong>: 横に表示する件数（グリッド・カルーセル用、デフォルト: 3）</li>
                        <li><strong>rows</strong>: 行数（グリッド・リスト用、デフォルト: 2）</li>
                        <li><strong>design</strong>: デザインパターン（list, grid, carousel）</li>
                        <li><strong>color</strong>: カラー指定（#000000形式）</li>
                        <li><strong>target</strong>: リンクターゲット（_self, _blank）</li>
                        <li><strong>description_length</strong>: 説明文の長さ（単語数、デフォルト: 20）</li>
                        <li><strong>autoplay</strong>: 自動再生（true, false）※カルーセルのみ</li>
                        <li><strong>loop</strong>: 無限ループ（true, false）※カルーセルのみ</li>
                        <li><strong>interval</strong>: 切り替え間隔（ミリ秒、デフォルト: 3000）※カルーセルのみ</li>
                    </ul>

                    <h3>表示内容オプション</h3>
                    <ul>
                        <li><strong>show_date</strong>: 投稿日付を表示（true, false、デフォルト: true）</li>
                        <li><strong>show_author</strong>: 投稿者を表示（true, false、デフォルト: false）</li>
                        <li><strong>show_excerpt</strong>: 抜粋・説明文を表示（true, false、デフォルト: true）</li>
                        <li><strong>show_category</strong>: カテゴリ・分類を表示（true, false、デフォルト: false）</li>
                        <li><strong>show_tags</strong>: タグを表示（true, false、デフォルト: false）</li>
                        <li><strong>show_read_more</strong>: 「続きを読む」リンクを表示（true, false、デフォルト: false）</li>
                        <li><strong>read_more_text</strong>: 「続きを読む」リンクのテキスト（デフォルト: 続きを読む）</li>
                        <li><strong>date_format</strong>: 日付の表示形式（デフォルト: Y.m.d）
                            <br><small>例: Y.m.d（2024.01.15）、Y年m月d日（2024年1月15日）、Y/m/d（2024/01/15）</small>
                        </li>
                    </ul>

                    <h3>サムネイル（アイキャッチ）設定</h3>
                    <ul>
                        <li><strong>show_thumbnail</strong>: サムネイル（アイキャッチ画像）を表示（true, false、デフォルト: true）</li>
                        <li><strong>thumbnail_position</strong>: サムネイルの表示位置（デフォルト: top）
                            <br><small>top: 上部、left: 左、right: 右</small>
                        </li>
                        <li><strong>thumbnail_size</strong>: サムネイルのサイズ（デフォルト: medium）
                            <br><small>thumbnail: 150px、medium: 300px、medium_large: 768px、large: 1024px、full: フルサイズ</small>
                        </li>
                    </ul>
                    <p><strong>使用例:</strong></p>
                    <ul>
                        <li><code>[ksc_posts show_thumbnail="false"]</code> - サムネイルを非表示</li>
                        <li><code>[ksc_posts thumbnail_position="left" thumbnail_size="thumbnail"]</code> - 左配置、小サイズ</li>
                        <li><code>[ksc_posts thumbnail_position="right" thumbnail_size="large"]</code> - 右配置、大サイズ</li>
                    </ul>

                    <h3>ソート機能</h3>
                    <ul>
                        <li><strong>orderby</strong>: 並び順の基準（デフォルト: date）
                            <br><small>date: 投稿日、title: タイトル、modified: 更新日、menu_order: メニュー順序、rand: ランダム、comment_count: コメント数</small>
                        </li>
                        <li><strong>order</strong>: 並び順（DESC, ASC、デフォルト: DESC）
                            <br><small>DESC: 降順（新しい→古い / Z→A）、ASC: 昇順（古い→新しい / A→Z）</small>
                        </li>
                    </ul>

                    <h3>ページネーション機能</h3>
                    <ul>
                        <li><strong>pagination</strong>: ページネーションを有効にする（true, false、デフォルト: false）</li>
                        <li><strong>pagination_type</strong>: ページネーションタイプ（numbers, arrows、デフォルト: numbers）
                            <br><small>numbers: 数字型（1 2 3 4 5）、arrows: 矢印型（‹ 前へ | 次へ ›）</small>
                        </li>
                        <li><strong>pagination_position</strong>: ページネーションの表示位置（top, bottom, both、デフォルト: both）
                            <br><small>top: 上部のみ、bottom: 下部のみ、both: 上下両方</small>
                        </li>
                    </ul>

                    <h3>カスタム投稿タイプの使用例</h3>
                    <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                        <p><strong>例1: 商品（product）投稿タイプをカテゴリ別に表示</strong></p>
                        <code>[ksc_posts post_type="product" category="electronics" design="grid" cols="4"]</code>

                        <p style="margin-top: 15px;"><strong>例2: イベント（event）投稿タイプをリスト表示</strong></p>
                        <code>[ksc_posts post_type="event" design="list" rows="5"]</code>

                        <p style="margin-top: 15px;"><strong>例3: ポートフォリオ（portfolio）をカルーセル表示</strong></p>
                        <code>[ksc_posts post_type="portfolio" design="carousel" cols="3" autoplay="true"]</code>

                        <p style="margin-top: 15px;"><strong>例4: 投稿者・カテゴリ・タグ付きの詳細表示</strong></p>
                        <code>[ksc_posts post_type="post" show_author="true" show_category="true" show_tags="true" show_read_more="true"]</code>

                        <p style="margin-top: 15px;"><strong>例5: 日付形式をカスタマイズした表示</strong></p>
                        <code>[ksc_posts post_type="event" date_format="Y年m月d日" show_category="true" read_more_text="詳細を見る"]</code>

                        <p style="margin-top: 15px;"><strong>例6: ページネーション付きグリッド表示</strong></p>
                        <code>[ksc_posts post_type="post" cols="3" rows="2" pagination="true" pagination_type="numbers"]</code>

                        <p style="margin-top: 15px;"><strong>例7: 矢印タイプのページネーション</strong></p>
                        <code>[ksc_posts post_type="product" design="list" rows="5" pagination="true" pagination_type="arrows"]</code>

                        <p style="margin-top: 15px;"><strong>例8: タイトル昇順でソート</strong></p>
                        <code>[ksc_posts post_type="post" orderby="title" order="ASC" design="list"]</code>

                        <p style="margin-top: 15px;"><strong>例9: 更新日降順とランダム表示の組み合わせ</strong></p>
                        <code>[ksc_posts post_type="news" orderby="modified" order="DESC" cols="3"]</code><br>
                        <code>[ksc_posts post_type="featured" orderby="rand" design="carousel"]</code>
                    </div>
                </div>
        </div>
        <?php
    }

    private function display_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $count = 0;

        echo '<div class="ksc-items" id="post-types-list">';
        foreach ($post_types as $post_type) {
            $class = $count >= 3 ? 'ksc-hidden' : '';
            $post_count = wp_count_posts($post_type->name);
            $total_posts = isset($post_count->publish) ? $post_count->publish : 0;

            // カスタム投稿タイプかどうかを判定
            $is_custom = !in_array($post_type->name, array('post', 'page', 'attachment'));
            $custom_label = $is_custom ? ' <span style="color: #0073aa; font-size: 11px;">[カスタム]</span>' : '';

            echo '<div class="ksc-item ' . $class . '">';
            echo '<strong>' . esc_html($post_type->label) . '</strong> (' . esc_html($post_type->name) . ')' . $custom_label;
            echo '<br><small style="color: #666;">投稿数: ' . $total_posts . '件</small>';

            // カスタム投稿タイプの場合はタクソノミーも表示
            if ($is_custom) {
                $taxonomies = get_object_taxonomies($post_type->name, 'objects');
                $public_taxonomies = array();
                foreach ($taxonomies as $taxonomy) {
                    if ($taxonomy->public && !in_array($taxonomy->name, array('post_format'))) {
                        $public_taxonomies[] = $taxonomy->label;
                    }
                }
                if (!empty($public_taxonomies)) {
                    echo '<br><small style="color: #666;">利用可能な分類: ' . esc_html(implode(', ', $public_taxonomies)) . '</small>';
                }
            }
            echo '</div>';
            $count++;
        }
        echo '</div>';

        if ($count > 3) {
            echo '<button type="button" class="button ksc-show-all" data-target="post-types-list">すべて表示</button>';
        }
    }

    private function display_categories() {
        $categories = get_categories(array('hide_empty' => false));
        $count = 0;

        echo '<div class="ksc-items" id="categories-list">';
        foreach ($categories as $category) {
            $class = $count >= 3 ? 'ksc-hidden' : '';
            echo '<div class="ksc-item ' . $class . '">';
            echo '<strong>' . esc_html($category->name) . '</strong> (' . esc_html($category->slug) . ')';
            echo '</div>';
            $count++;
        }
        echo '</div>';

        if ($count > 3) {
            echo '<button type="button" class="button ksc-show-all" data-target="categories-list">すべて表示</button>';
        }
    }
}
