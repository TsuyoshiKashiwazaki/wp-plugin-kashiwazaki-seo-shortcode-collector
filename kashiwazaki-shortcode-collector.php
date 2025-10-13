<?php
/**
 * Plugin Name: Kashiwazaki Shortcode Collector
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: 指定した投稿タイプやカテゴリをショートコードで一括で呼び出すプラグイン
 * Version: 1.0.1
 * Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI: https://www.tsuyoshikashiwazaki.jp/profile/
 * Text Domain: kashiwazaki-shortcode-collector
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KSC_VERSION', '1.0.1');
define('KSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KSC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSC_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Kashiwazaki_Shortcode_Collector {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once KSC_PLUGIN_DIR . 'includes/class-admin.php';
        require_once KSC_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once KSC_PLUGIN_DIR . 'includes/class-display.php';
        require_once KSC_PLUGIN_DIR . 'includes/class-wizard.php';
    }

    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'ensure_jquery_loaded'), 1);
        add_filter('plugin_action_links_' . KSC_PLUGIN_BASENAME, array($this, 'add_action_links'));

        if (is_admin()) {
            new KSC_Admin();
            new KSC_Wizard();
        }

        new KSC_Shortcode();
    }

    public function init() {
        $this->register_styles();

        // 最優先でjQueryを読み込む
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script('jquery');
        }, -1); // 最も高い優先度

        // wp_headでもjQueryを読み込む（さらに確実性を高める）
        add_action('wp_head', function() {
            if (!wp_script_is('jquery', 'enqueued')) {
                wp_enqueue_script('jquery');
            }
        }, 0);
    }

    public function ensure_jquery_loaded() {
        // jQueryが確実に読み込まれるようにする（複数の方法で）
        if (!is_admin()) {
            // まず既存のjQuery登録をクリア（競合防止）
            wp_deregister_script('jquery');
            wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), array(), '3.6.0', false);

            // WordPressのデフォルトjQueryを確実に読み込み
            wp_enqueue_script('jquery');

            // jQuery Migrateも読み込み（互換性のため）
            wp_enqueue_script('jquery-migrate');

            // jQuery UIも読み込み（必要な場合）
            wp_enqueue_script('jquery-ui-core');


        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('kashiwazaki-shortcode-collector', false, dirname(KSC_PLUGIN_BASENAME) . '/languages');
    }

    private function register_styles() {
        if (!is_admin()) {
            wp_register_style('ksc-styles', KSC_PLUGIN_URL . 'assets/css/ksc-styles.css', array(), KSC_VERSION);
            wp_register_script('ksc-carousel', KSC_PLUGIN_URL . 'assets/js/ksc-carousel.js', array('jquery'), KSC_VERSION, true);
            wp_register_script('ksc-pagination', KSC_PLUGIN_URL . 'assets/js/ksc-pagination.js', array('jquery'), KSC_VERSION, true);

            // ページネーションが有効な場合、jQueryを確実に読み込む
            wp_enqueue_script('jquery');
        }
    }

    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=kashiwazaki-shortcode-collector') . '">' . __('設定', 'kashiwazaki-shortcode-collector') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

function ksc_activate() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ksc_activate');

function ksc_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ksc_deactivate');

Kashiwazaki_Shortcode_Collector::get_instance();
