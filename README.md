# Kashiwazaki SEO Shortcode Collector

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Version](https://img.shields.io/badge/version-1.0.3-blue.svg)

指定した投稿タイプやカテゴリをショートコードで一括で呼び出すWordPressプラグイン

## 主な機能

- **3つのデザインパターン** - グリッド、リスト、カルーセルから選択可能
- **ビジュアルウィザード** - コード不要でショートコードを生成
- **完全レスポンシブ** - すべてのデバイスで最適な表示
- **高度なカスタマイズ** - 列数、行数、色、配置を細かく制御
- **ページネーション** - 大量の投稿を複数ページに分割（AJAX対応）
- **SEO最適化** - タイトルタグを自由に選択（h1〜h6、div）
- **投稿タイプ対応** - 標準投稿、固定ページ、カスタム投稿タイプ

---

## インストール

1. プラグインファイルを `/wp-content/plugins/kashiwazaki-shortcode-collector` ディレクトリにアップロード
2. WordPressの「プラグイン」メニューからプラグインを有効化
3. 管理画面の「Kashiwazaki SEO Shortcode Collector」メニューから設定を確認

---

## 基本的な使い方

### ウィザードを使う（推奨）

管理画面 → Kashiwazaki SEO Shortcode Collector → 「ショートコード作成ウィザード」タブ

6ステップで簡単にショートコードを作成できます：
1. 投稿タイプを選択
2. カテゴリ・分類を選択（任意）
3. デザインパターンと色を設定
4. レイアウト（列数・行数）を設定
5. 表示内容を設定
6. 詳細設定（リンクターゲット等）

### 手動でショートコードを書く

```
[ksc_posts post_type="post" cols="3" rows="2"]
```

---

## デザインパターン

### グリッド表示

```
[ksc_posts post_type="post" design="grid" cols="3" rows="2"]
```

カード形式で投稿を格子状に並べます。

### リスト表示

```
[ksc_posts post_type="post" design="list" rows="5"]
```

縦一列でシンプルに投稿を並べます。サムネイルは表示されません。

### カルーセル表示

```
[ksc_posts post_type="post" design="carousel" cols="3" autoplay="true" interval="3000"]
```

左右にスライドする形式で投稿を表示します。

---

## パラメータ一覧

### 基本パラメータ

| パラメータ | 説明 | デフォルト値 | 選択肢 |
|-----------|------|-------------|--------|
| `post_type` | 投稿タイプ | `post` | post, page, カスタム投稿タイプ名 |
| `category` | カテゴリ・分類スラッグ | （空） | カテゴリスラッグ |
| `design` | デザインパターン | `grid` | grid, list, carousel |
| `cols` | 列数（横に並べる数） | `3` | 1〜6 |
| `rows` | 行数 | `2` | 1〜10 |
| `color` | テキストカラー | `#333333` | #000000形式 |
| `target` | リンクターゲット | `_self` | _self, _blank |
| `description_length` | 抜粋文の長さ（単語数） | `150` | 数値 |

### 表示内容パラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `show_title` | タイトルを表示 | `true` |
| `title_tag` | タイトルのHTMLタグ | `h2` |
| `show_date` | 投稿日を表示 | `true` |
| `show_modified` | 更新日を表示 | `false` |
| `show_author` | 投稿者を表示 | `false` |
| `show_excerpt` | 抜粋文を表示 | `true` |
| `show_category` | カテゴリを表示 | `false` |
| `show_tags` | タグを表示 | `false` |
| `show_read_more` | 続きを読むリンクを表示 | `false` |
| `read_more_text` | 続きを読むリンクのテキスト | `続きを読む` |
| `date_format` | 日付の表示形式 | `Y.m.d` |

**title_tagの選択肢**: h1, h2, h3, h4, h5, h6, div

**date_formatの例**:
- `Y.m.d` → 2024.01.15
- `Y年m月d日` → 2024年1月15日
- `Y/m/d` → 2024/01/15

### サムネイル（アイキャッチ画像）パラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `show_thumbnail` | サムネイルを表示 | `true` |
| `thumbnail_position` | サムネイルの位置 | `top` |
| `thumbnail_size` | サムネイルのサイズ | `full` |

**thumbnail_positionの選択肢**: top（上）, left（左）, right（右）

**thumbnail_sizeの選択肢**:
- `thumbnail` - サムネイル（小）
- `medium` - 中サイズ
- `large` - 大サイズ
- `full` - フルサイズ（オリジナル）

### ソートパラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `orderby` | 並び順の基準 | `date` |
| `order` | 昇順/降順 | `DESC` |

**orderbyの選択肢**:
- `date` - 投稿日
- `title` - タイトル
- `modified` - 更新日
- `rand` - ランダム

**orderの選択肢**:
- `DESC` - 降順（新しい順 / Z→A）
- `ASC` - 昇順（古い順 / A→Z）

### ページネーションパラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `pagination` | ページネーションを有効化 | `false` |
| `pagination_type` | ページネーションタイプ | `numbers` |
| `pagination_position` | ページネーションの表示位置 | `both` |

**pagination_typeの選択肢**:
- `numbers` - 数字（1 2 3 4 5）
- `arrows` - 矢印（前へ / 次へ）

**pagination_positionの選択肢**:
- `top` - 上部のみ
- `bottom` - 下部のみ
- `both` - 上下両方

### カルーセル専用パラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `autoplay` | 自動再生 | `false` |
| `loop` | 無限ループ | `true` |
| `interval` | 自動再生の間隔（ミリ秒） | `3000` |

### レスポンシブパラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `mobile_breakpoint` | モバイル表示に切り替える幅（px） | `768` |
| `tablet_breakpoint` | タブレット表示に切り替える幅（px） | `1024` |

管理画面でグローバル設定も可能です。

---

## 使用例

### 基本的なグリッド表示

```
[ksc_posts post_type="post" category="news" cols="3" rows="2"]
```

### サムネイルを左に配置

```
[ksc_posts post_type="post" thumbnail_position="left" thumbnail_size="medium"]
```

### 詳細情報付きの表示

```
[ksc_posts post_type="post" show_author="true" show_category="true" show_tags="true" show_read_more="true"]
```

### ページネーション付きリスト

```
[ksc_posts post_type="post" design="list" rows="10" pagination="true" pagination_type="numbers"]
```

### 自動再生カルーセル

```
[ksc_posts post_type="post" design="carousel" cols="3" autoplay="true" loop="true" interval="4000"]
```

### カスタム投稿タイプの表示

```
[ksc_posts post_type="product" category="electronics" design="grid" cols="4"]
```

### タイトル順で並び替え

```
[ksc_posts post_type="post" orderby="title" order="ASC"]
```

### ランダム表示

```
[ksc_posts post_type="post" orderby="rand" cols="3"]
```

---

## カテゴリの指定方法

投稿タイプによってカテゴリの指定方法が異なります：

### 投稿（post）の場合

カテゴリスラッグを指定します。

```
[ksc_posts post_type="post" category="news"]
```

### 固定ページ（page）の場合

親ページのIDを `page-ID` 形式で指定します。

```
[ksc_posts post_type="page" category="page-123"]
```

### カスタム投稿タイプの場合

タクソノミー（分類）のスラッグを指定します。

```
[ksc_posts post_type="product" category="electronics"]
```

---

## レスポンシブ対応

### 自動レスポンシブ

デフォルトで以下の動作をします：

- **モバイル（768px以下）**: 1列表示
- **タブレット（769px〜1024px）**: 列数を段階的に削減
  - 6列 → 3列
  - 5列 → 3列
  - 4列 → 3列
  - 3列 → 2列
- **デスクトップ（1025px以上）**: 指定した列数で表示

### ブレイクポイントのカスタマイズ

管理画面の「レスポンシブ設定」でグローバル設定を変更できます。

個別のショートコードで上書きも可能：

```
[ksc_posts post_type="post" mobile_breakpoint="600" tablet_breakpoint="900"]
```

---

## PHPテンプレートでの使用

テーマのテンプレートファイル（archive.php、category.php など）で直接使用できます：

```php
<?php echo do_shortcode('[ksc_posts post_type="post" cols="3" rows="3"]'); ?>
```

ウィザードでは「PHPコード」タブでテンプレート用のコードも生成されます。

---

## 技術仕様

### システム要件

- WordPress 5.0以上
- PHP 7.2以上
- jQuery（WordPressに含まれる）

### 対応ブラウザ

- Chrome（最新版）
- Firefox（最新版）
- Safari（最新版）
- Edge（最新版）

### ファイル構成

```
kashiwazaki-shortcode-collector/
├── kashiwazaki-shortcode-collector.php  # メインプラグインファイル
├── includes/
│   ├── class-admin.php      # 管理画面
│   ├── class-wizard.php     # ウィザード機能
│   ├── class-shortcode.php  # ショートコード処理
│   └── class-display.php    # 表示レンダリング
├── assets/
│   ├── css/
│   │   ├── ksc-styles.css   # フロントエンドスタイル
│   │   ├── ksc-admin.css    # 管理画面スタイル
│   │   └── ksc-wizard.css   # ウィザードスタイル
│   └── js/
│       ├── ksc-carousel.js  # カルーセル機能
│       ├── ksc-pagination.js # ページネーション機能
│       ├── ksc-admin.js     # 管理画面スクリプト
│       └── ksc-wizard.js    # ウィザードスクリプト
└── languages/               # 翻訳ファイル
```

---

## 更新履歴

### Version 1.0.3 - 2025-12-29

- Changed: サムネイルサイズの選択肢をWordPress標準の名称に変更（フルサイズ、大サイズ、中サイズ、サムネイル）
- Changed: サムネイルサイズのデフォルトを「フルサイズ」に変更

### Version 1.0.2 - 2025-12-15

- Fixed: ページネーション切替時に更新日が表示されなくなる問題を修正

### Version 1.0.1 - 2025-12-14

- Added: 更新日表示機能を追加（`show_modified`パラメータ）
- Fixed: ウィザードで生成されるPHPコードのカルーセル表示で最後の1つが空白になる問題を修正

### Version 1.0.0 - 2025-09-14

- 初回リリース
- グリッド、リスト、カルーセルの3つのデザインパターンを実装
- ビジュアルウィザード機能を追加
- レスポンシブデザイン対応
- ページネーション機能を追加

---

## ライセンス

GPL-2.0-or-later

## 開発者

**開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)
**ウェブサイト**: https://www.tsuyoshikashiwazaki.jp/

---

**Keywords**: WordPress, Plugin, Shortcode, Post Display, Grid Layout, Carousel, List View, Custom Post Type, Pagination
