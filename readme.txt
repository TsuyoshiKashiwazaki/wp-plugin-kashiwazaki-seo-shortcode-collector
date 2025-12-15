=== Kashiwazaki Shortcode Collector ===
Contributors: tsuyoshikashiwazaki
Donate link: https://tsuyoshikashiwazaki.jp/
Tags: shortcode, posts, display, grid, carousel
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

指定した投稿タイプやカテゴリをショートコードで一括で呼び出すプラグイン

== Description ==

Kashiwazaki Shortcode Collectorは、WordPressの投稿、ページ、カスタム投稿タイプを美しく表示するための強力なショートコードプラグインです。初心者から上級者まで、誰でも簡単に使える直感的なインターフェースを提供します。

**主な特徴**

* **ビジュアルウィザード** - コードを書かずにショートコードを生成できる初心者向けウィザード
* **3つのデザインパターン** - グリッド、リスト、カルーセルから選択可能
* **高度なカスタマイズ** - 列数、行数、色、サムネイル位置などを細かく設定
* **レスポンシブ対応** - モバイル、タブレット、デスクトップで最適な表示
* **ページネーション** - 大量の投稿を複数ページに分割表示
* **柔軟な表示設定** - タイトル、日付、著者、カテゴリ、タグ、抜粋の表示/非表示を個別に制御
* **SEO最適化** - タイトルタグ（h1〜h6、div）を選択可能
* **並び替え機能** - 日付、タイトル、ランダムなど多様な並び順に対応

**表示可能な要素**

* タイトル（h1〜h6タグまたはdivタグで選択可能）
* サムネイル画像（上下左右の配置選択可能）
* 投稿の抜粋（文字数指定可能）
* 投稿日（フォーマット指定可能）
* 投稿者名
* カテゴリー
* タグ
* 続きを読むリンク（テキスト変更可能）

**対応する投稿タイプ**

* 標準の投稿（post）
* 固定ページ（page）
* すべてのカスタム投稿タイプ

**使用例**

基本的な使い方：
`[ksc_posts post_type="post" cols="3" rows="2"]`

カテゴリを指定：
`[ksc_posts post_type="post" category="news" cols="4" rows="3" design="grid"]`

カルーセル表示：
`[ksc_posts post_type="portfolio" design="carousel" cols="3" autoplay="true" interval="3000"]`

ページネーション付き：
`[ksc_posts post_type="post" cols="3" rows="3" pagination="true" pagination_type="numbers"]`

== Installation ==

1. プラグインファイルを `/wp-content/plugins/kashiwazaki-shortcode-collector` ディレクトリにアップロード
2. WordPressの「プラグイン」メニューからプラグインを有効化
3. 管理画面の「Kashiwazaki Shortcode Collector」メニューから設定を確認

== Frequently Asked Questions ==

= 使用可能なデザインパターンは？ =

以下の3つのデザインパターンが利用可能です：
* grid - グリッド表示
* list - リスト表示
* carousel - カルーセル表示

= カスタム投稿タイプに対応していますか？ =

はい、公開されているすべての投稿タイプに対応しています。

== Screenshots ==

1. 管理画面の設定ページ
2. グリッド表示の例
3. リスト表示の例
4. カルーセル表示の例

== Changelog ==

= 1.0.2 =
* Fixed: Modified date display issue in pagination.

= 1.0.1 =
* Added: 更新日表示機能を追加（show_modifiedパラメータ）
* Fixed: ウィザードで生成されるPHPコードのカルーセル表示で最後の1つが空白になる問題を修正

= 1.0.0 =
* 初回リリース

== Upgrade Notice ==

= 1.0.1 =
更新日表示機能を追加し、カルーセル表示の不具合を修正しました。

= 1.0.0 =
初回リリース

== Author ==

作者: 柏崎剛
Webサイト: [SEO対策研究室](https://tsuyoshikashiwazaki.jp/)
