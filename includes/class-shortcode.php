<?php
if (!defined('ABSPATH')) {
    exit;
}

class KSC_Shortcode {
    public function __construct() {
        add_shortcode('ksc_posts', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_type' => 'post',
            'category' => '',
            'cols' => 3,
            'rows' => 2,
            'design' => 'grid',
            'color' => '#333333',
            'target' => '_self',
            'description_length' => 150,
            'autoplay' => 'false',
            'loop' => 'true',
            'interval' => 3000,
            // 表示オプション
            'show_title' => 'true',
            'title_tag' => 'h2',
            'show_date' => 'true',
            'show_author' => 'false',
            'show_excerpt' => 'true',
            'show_category' => 'false',
            'show_tags' => 'false',
            'show_read_more' => 'false',
            'read_more_text' => '続きを読む',
            'date_format' => 'Y.m.d',
            // サムネイル設定
            'show_thumbnail' => 'true',
            'thumbnail_position' => 'top',
            'thumbnail_size' => 'full',
            // ページネーション
            'pagination' => 'false',
            'pagination_type' => 'numbers',
            'pagination_position' => 'both',
            // ソート設定
            'orderby' => 'date',
            'order' => 'DESC',
            // レスポンシブ設定
            'mobile_breakpoint' => '',
            'tablet_breakpoint' => ''
        ), $atts);

        // デザインに応じてデフォルト値を調整
        if ($atts['design'] === 'list') {
            $atts['cols'] = 1;
        } else if ($atts['design'] === 'carousel') {
            // カルーセルの場合、rowsは無視される
            $atts['rows'] = 1; // 内部的には1として扱う
        }

        wp_enqueue_style('ksc-styles');

        // jQueryを最優先で読み込む
        wp_enqueue_script('jquery');



        if ($atts['design'] === 'carousel') {
            wp_enqueue_script('ksc-carousel');
        }

        if ($atts['pagination'] === 'true' && $atts['design'] !== 'carousel') {
            wp_enqueue_script('ksc-pagination');
        }

        $display = new KSC_Display();
        return $display->render($atts);
    }
}
