# 🚀 Kashiwazaki Shortcode Collector

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.1--dev-orange.svg)](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-shortcode-collector/releases)

指定した投稿タイプやカテゴリをショートコードで一括で呼び出すWordPressプラグイン

> 🎯 **初心者から上級者まで、誰でも簡単に美しい投稿一覧を作成できる強力なショートコードプラグイン**

## 主な機能

- 🎨 **3つのデザインパターン** - グリッド、リスト、カルーセルから選択可能
- 🪄 **ビジュアルウィザード** - コード不要でショートコードを生成
- 📱 **完全レスポンシブ** - すべてのデバイスで最適な表示
- ⚙️ **高度なカスタマイズ** - 列数、行数、色、配置を細かく制御
- 📄 **ページネーション** - 大量の投稿を複数ページに分割
- 🔍 **SEO最適化** - タイトルタグを自由に選択（h1〜h6、div）
- 🎯 **投稿タイプ対応** - 標準投稿、固定ページ、カスタム投稿タイプ

## 🚀 クイックスタート

### インストール

1. プラグインファイルを `/wp-content/plugins/kashiwazaki-shortcode-collector` ディレクトリにアップロード
2. WordPressの「プラグイン」メニューからプラグインを有効化
3. 管理画面の「Kashiwazaki Shortcode Collector」メニューから設定を確認

### 基本的な使い方

```shortcode
[ksc_posts post_type="post" cols="3" rows="2"]
```

## 使い方

### デザインパターン

#### グリッド表示
```shortcode
[ksc_posts post_type="post" design="grid" cols="3" rows="3"]
```

#### リスト表示
```shortcode
[ksc_posts post_type="post" design="list" thumbnail_position="left"]
```

#### カルーセル表示
```shortcode
[ksc_posts post_type="post" design="carousel" cols="3" autoplay="true" interval="3000"]
```

### カテゴリ・タグでフィルタリング

```shortcode
[ksc_posts post_type="post" category="news,blog" tag="wordpress,plugin"]
```

### ページネーション

```shortcode
[ksc_posts post_type="post" cols="3" rows="3" pagination="true" pagination_type="numbers"]
```

### 表示要素のカスタマイズ

```shortcode
[ksc_posts
  post_type="post"
  show_title="true"
  show_date="true"
  show_author="true"
  show_category="true"
  show_excerpt="true"
  excerpt_length="100"
]
```

## パラメータ一覧

| パラメータ | 説明 | デフォルト値 | 選択肢 |
|-----------|------|-------------|--------|
| `post_type` | 投稿タイプ | `post` | post, page, カスタム投稿タイプ |
| `design` | デザインパターン | `grid` | grid, list, carousel |
| `cols` | 列数 | `3` | 1-6 |
| `rows` | 行数 | `3` | 1-10 |
| `category` | カテゴリスラッグ | - | カンマ区切りで複数指定可 |
| `tag` | タグスラッグ | - | カンマ区切りで複数指定可 |
| `orderby` | 並び順 | `date` | date, title, rand, menu_order |
| `order` | 昇順/降順 | `DESC` | ASC, DESC |
| `show_title` | タイトル表示 | `true` | true, false |
| `show_thumbnail` | サムネイル表示 | `true` | true, false |
| `show_date` | 日付表示 | `true` | true, false |
| `show_modified` | 更新日表示 | `false` | true, false |
| `show_author` | 著者表示 | `false` | true, false |
| `show_category` | カテゴリ表示 | `false` | true, false |
| `show_tag` | タグ表示 | `false` | true, false |
| `show_excerpt` | 抜粋表示 | `true` | true, false |
| `excerpt_length` | 抜粋文字数 | `100` | 数値 |
| `read_more_text` | 続きを読むテキスト | `続きを読む` | 任意のテキスト |
| `pagination` | ページネーション | `false` | true, false |
| `pagination_type` | ページネーションタイプ | `numbers` | numbers, prev_next, load_more |

### カルーセル専用パラメータ

| パラメータ | 説明 | デフォルト値 |
|-----------|------|-------------|
| `autoplay` | 自動再生 | `false` |
| `interval` | 自動再生間隔（ミリ秒） | `5000` |
| `loop` | ループ再生 | `true` |
| `nav` | ナビゲーション矢印 | `true` |
| `dots` | ドットインジケーター | `true` |

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

### 使用技術

- PHP（オブジェクト指向設計）
- JavaScript（jQuery）
- CSS3（Flexbox、Grid）
- WordPress API（Shortcode API、Settings API）

## 更新履歴

### Version 1.0.1 - 2025-10-13
- Added: 更新日表示機能を追加（`show_modified`パラメータ）
- Fixed: ウィザードで生成されるPHPコードのカルーセル表示で最後の1つが空白になる問題を修正

### Version 1.0.0 - 2025-09-14
- 初回リリース
- グリッド、リスト、カルーセルの3つのデザインパターンを実装
- ビジュアルウィザード機能を追加
- レスポンシブデザイン対応
- ページネーション機能を追加

## ライセンス

GPL-2.0-or-later

## サポート・開発者

**開発者**: 柏崎剛 (Tsuyoshi Kashiwazaki)
**ウェブサイト**: https://www.tsuyoshikashiwazaki.jp/
**サポート**: プラグインに関するご質問や不具合報告は、開発者ウェブサイトまでお問い合わせください。

## 🤝 貢献

バグレポートや機能リクエストは [Issues](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-shortcode-collector/issues) からお願いします。

プルリクエストも歓迎します：

1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/AmazingFeature`)
3. 変更をコミット (`git commit -m 'Add some AmazingFeature'`)
4. ブランチにプッシュ (`git push origin feature/AmazingFeature`)
5. プルリクエストを作成

## 📞 サポート

- **公式サイト**: https://www.tsuyoshikashiwazaki.jp/
- **お問い合わせ**: 開発者ウェブサイトのお問い合わせフォームから
- **Issues**: [GitHub Issues](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-shortcode-collector/issues)

---

<div align="center">

**🔍 Keywords**: WordPress, Plugin, Shortcode, Post Display, Grid Layout, Carousel, List View, Custom Post Type, Pagination

Made with ❤️ by [Tsuyoshi Kashiwazaki](https://github.com/TsuyoshiKashiwazaki)

</div>