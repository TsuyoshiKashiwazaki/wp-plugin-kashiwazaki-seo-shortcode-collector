/**
 * KSC Pagination - jQuery完全対応バージョン
 * WordPress環境でjQueryが読み込まれていない場合の対策を含む
 */

// グローバル関数として定義
window.kscPaginationInit = function () {

    // jQueryの存在をチェック
    if (typeof jQuery === 'undefined') {
        return;
    }

    var $ = jQuery;

    $(document).ready(function () {
        // jQuery読み込み完了時にページネーションボタンを表示
        $('.ksc-pagination-controls').removeClass('ksc-pagination-loading');
        $('.ksc-pagination-btn').css({
            'visibility': 'visible',
            'pointer-events': 'auto'
        });

        $('.ksc-pagination-wrapper').each(function () {
            var wrapper = $(this);

            try {
                var paginationData = JSON.parse(wrapper.attr('data-pagination'));
                var currentPage = 1;
                var totalPages = paginationData.total_pages;
                var perPage = paginationData.per_page;
                var posts = paginationData.posts;
                var atts = paginationData.atts;

                // 初期状態でボタンの状態を更新（矢印型と数字型の両方）
                wrapper.find('.ksc-pagination-controls button').each(function () {
                    var button = $(this);
                    var dataPage = button.attr('data-page');

                    if (dataPage === 'prev') {
                        // 前へボタン：最初のページではdisabled
                        if (currentPage === 1) {
                            button.prop('disabled', true);
                        } else {
                            button.prop('disabled', false);
                        }
                    } else if (dataPage === 'next') {
                        // 次へボタン：最後のページではdisabled
                        if (currentPage === totalPages) {
                            button.prop('disabled', true);
                        } else {
                            button.prop('disabled', false);
                        }
                    } else {
                        // 数字型ページ番号ボタン
                        var pageNum = parseInt(dataPage);
                        if (pageNum === currentPage) {
                            // 現在のページはdisabled状態に
                            button.addClass('ksc-pagination-current').prop('disabled', true);
                        } else {
                            // 他のページは有効状態に
                            button.removeClass('ksc-pagination-current').prop('disabled', false);
                        }
                    }
                });

                // ページネーションボタンのクリックイベント
                wrapper.find('.ksc-pagination-btn').on('click', function (e) {
                    var button = $(this);
                    var targetPage = button.attr('data-page');

                    // disabled状態のボタンは処理しない
                    if (button.prop('disabled')) {
                        return false;
                    }



                    e.preventDefault();
                    e.stopPropagation();

                    var newPage = currentPage;

                    if (targetPage === 'prev') {
                        newPage = Math.max(1, currentPage - 1);
                    } else if (targetPage === 'next') {
                        newPage = Math.min(totalPages, currentPage + 1);
                    } else {
                        newPage = parseInt(targetPage);
                    }



                    if (newPage !== currentPage) {
                        loadPage(newPage);
                    }
                });

                function loadPage(pageNumber) {
                    // ローディング表示
                    wrapper.find('.ksc-pagination-content').addClass('ksc-loading');

                    var startIndex = (pageNumber - 1) * perPage;
                    var endIndex = startIndex + perPage;
                    var pagePosts = posts.slice(startIndex, endIndex);

                    // コンテンツを生成
                    var content = generatePageContent(pagePosts, atts);

                    // コンテンツ更新
                    wrapper.find('.ksc-pagination-content')
                        .fadeOut(200)
                        .promise()
                        .done(function () {
                            $(this).html(content).removeClass('ksc-loading');
                            $(this).fadeIn(200);
                        });

                    // ページネーションコントロールを更新
                    updatePaginationControls(pageNumber);
                    currentPage = pageNumber;

                    // すべてのボタン状態を更新（矢印型と数字型の両方）
                    wrapper.find('.ksc-pagination-controls button').each(function () {
                        var button = $(this);
                        var dataPage = button.attr('data-page');

                        if (dataPage === 'prev') {
                            // 前へボタン：最初のページではdisabled
                            if (pageNumber === 1) {
                                button.prop('disabled', true);
                            } else {
                                button.prop('disabled', false);
                            }
                        } else if (dataPage === 'next') {
                            // 次へボタン：最後のページではdisabled
                            if (pageNumber === totalPages) {
                                button.prop('disabled', true);
                            } else {
                                button.prop('disabled', false);
                            }
                        } else {
                            // 数字型ページ番号ボタン
                            var pageNum = parseInt(dataPage);
                            if (pageNum === pageNumber) {
                                // 現在のページはdisabled状態に
                                button.addClass('ksc-pagination-current').prop('disabled', true);
                            } else {
                                // 他のページは有効状態に
                                button.removeClass('ksc-pagination-current').prop('disabled', false);
                            }
                        }
                    });
                }

                function generatePageContent(pagePosts, atts) {
                    var design = atts.design;
                    var cols = atts.cols;
                    var color = atts.color;
                    var target = atts.target;
                    var descriptionLength = atts.description_length;
                    var showTitle = atts.show_title !== 'false';  // デフォルトはtrue
                    var showDate = atts.show_date === 'true';
                    var showAuthor = atts.show_author === 'true';
                    var showExcerpt = atts.show_excerpt === 'true';
                    var showCategory = atts.show_category === 'true';
                    var showTags = atts.show_tags === 'true';
                    var showReadMore = atts.show_read_more === 'true';
                    var readMoreText = atts.read_more_text || '続きを読む';
                    var titleTag = atts.title_tag || 'h2';

                    // タグのバリデーション
                    var allowedTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'];
                    if (allowedTags.indexOf(titleTag) === -1) {
                        titleTag = 'h2';
                    }

                    var html = '';

                    if (design === 'list') {
                        html += '<div class="ksc-list" style="--ksc-color: ' + color + ';">';
                    } else {
                        html += '<div class="ksc-grid ksc-cols-' + cols + '" style="--ksc-color: ' + color + ';">';
                    }

                    pagePosts.forEach(function (post) {
                        var itemClasses = 'ksc-item ksc-item-' + design;
                        if (atts.show_thumbnail === 'true') {
                            if (post.thumbnail) {
                                itemClasses += ' ksc-thumbnail-' + (atts.thumbnail_position || 'top');
                            } else {
                                itemClasses += ' ksc-no-thumbnail';
                            }
                        } else {
                            itemClasses += ' ksc-thumbnail-disabled';
                        }

                        html += '<div class="' + itemClasses + '">';

                        // サムネイル表示
                        if (atts.show_thumbnail === 'true' && post.thumbnail && (atts.thumbnail_position === 'top' || atts.thumbnail_position === 'left' || atts.thumbnail_position === 'right')) {
                            html += '<a href="' + post.permalink + '" target="' + target + '" class="ksc-thumbnail ksc-thumbnail-' + atts.thumbnail_position + '">';
                            html += '<img src="' + post.thumbnail + '" alt="' + post.title + '">';
                            html += '</a>';
                        }

                        html += '<div class="ksc-content">';

                        // 日付
                        if (showDate && post.date) {
                            html += '<div class="ksc-date-top">' + post.date + '</div>';
                        }

                        // タイトル
                        if (showTitle) {
                            html += '<' + titleTag + ' class="ksc-title"><a href="' + post.permalink + '" target="' + target + '">' + post.title + '</a></' + titleTag + '>';
                        }

                        // メタ情報
                        var metaItems = [];
                        if (showCategory && post.categories && post.categories.length > 0) {
                            metaItems.push('<span class="ksc-categories">カテゴリ: ' + post.categories.join(', ') + '</span>');
                        }
                        if (showAuthor && post.author) {
                            metaItems.push('<span class="ksc-author">投稿者: ' + post.author + '</span>');
                        }

                        if (metaItems.length > 0) {
                            html += '<div class="ksc-meta">' + metaItems.join('') + '</div>';
                        }

                        // 抜粋
                        if (showExcerpt && post.excerpt) {
                            html += '<div class="ksc-excerpt">' + post.excerpt + '</div>';
                        }

                        // タグ
                        if (showTags && post.tags && post.tags.length > 0) {
                            html += '<div class="ksc-tags"><span class="ksc-tags-label">タグ: </span>';
                            post.tags.forEach(function (tag) {
                                html += '<span class="ksc-tag">' + tag + '</span>';
                            });
                            html += '</div>';
                        }

                        // 続きを読む
                        if (showReadMore) {
                            html += '<div class="ksc-read-more">';
                            html += '<a href="' + post.permalink + '" target="' + target + '" class="ksc-read-more-link">' + readMoreText + '</a>';
                            html += '</div>';
                        }

                        html += '</div></div>';
                    });

                    html += '</div>';
                    return html;
                }

                function updatePaginationControls(pageNumber) {
                    var controls = wrapper.find('.ksc-pagination-controls');

                    if (atts.pagination_type === 'arrows') {
                        controls.find('.ksc-pagination-prev').prop('disabled', pageNumber <= 1);
                        controls.find('.ksc-pagination-next').prop('disabled', pageNumber >= totalPages);
                        controls.find('.ksc-pagination-info').text(pageNumber + ' / ' + totalPages);
                    } else {
                        controls.find('.ksc-pagination-btn').removeClass('ksc-pagination-current');
                        controls.find('.ksc-pagination-btn[data-page="' + pageNumber + '"]').addClass('ksc-pagination-current');
                        updatePaginationNumbers(pageNumber);
                    }
                }

                function updatePaginationNumbers(currentPage) {
                    if (totalPages <= 7) return;

                    var controls = wrapper.find('.ksc-pagination-controls');
                    controls.each(function () {
                        var control = $(this);
                        var numbersHtml = '';

                        if (currentPage <= 4) {
                            for (var i = 1; i <= 5; i++) {
                                numbersHtml += '<button class="ksc-pagination-btn' + (i === currentPage ? ' ksc-pagination-current' : '') + '" data-page="' + i + '">' + i + '</button>';
                            }
                            numbersHtml += '<span class="ksc-pagination-dots">...</span>';
                            numbersHtml += '<button class="ksc-pagination-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
                        } else if (currentPage >= totalPages - 3) {
                            numbersHtml += '<button class="ksc-pagination-btn" data-page="1">1</button>';
                            numbersHtml += '<span class="ksc-pagination-dots">...</span>';
                            for (var i = totalPages - 4; i <= totalPages; i++) {
                                numbersHtml += '<button class="ksc-pagination-btn' + (i === currentPage ? ' ksc-pagination-current' : '') + '" data-page="' + i + '">' + i + '</button>';
                            }
                        } else {
                            numbersHtml += '<button class="ksc-pagination-btn" data-page="1">1</button>';
                            numbersHtml += '<span class="ksc-pagination-dots">...</span>';
                            for (var i = currentPage - 1; i <= currentPage + 1; i++) {
                                numbersHtml += '<button class="ksc-pagination-btn' + (i === currentPage ? ' ksc-pagination-current' : '') + '" data-page="' + i + '">' + i + '</button>';
                            }
                            numbersHtml += '<span class="ksc-pagination-dots">...</span>';
                            numbersHtml += '<button class="ksc-pagination-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
                        }

                        control.html(numbersHtml);
                    });
                }

                function updateNumericButtonStates(currentPage) {
                    // すべてのページ番号ボタンのdisabled状態を更新
                    wrapper.find('.ksc-pagination-controls button[data-page]').each(function () {
                        var button = $(this);
                        var pageNum = parseInt(button.attr('data-page'));

                        if (pageNum === currentPage) {
                            // 現在のページはdisabled状態に
                            button.addClass('ksc-pagination-current').prop('disabled', true);
                        } else {
                            // 他のページは有効状態に
                            button.removeClass('ksc-pagination-current').prop('disabled', false);
                        }
                    });
                }

            } catch (error) {
                // エラー時は何もしない
            }
        });

        // ksb-custom-boxのpadding修正
        $('.ksb-custom-box').each(function () {
            var $this = $(this);
            $this.css({
                'padding': '0 !important',
                'padding-top': '0 !important',
                'padding-bottom': '0 !important',
                'padding-left': '0 !important',
                'padding-right': '0 !important'
            });

            var currentStyle = $this.attr('style') || '';
            var newStyle = currentStyle.replace(/padding[^;]*;?/gi, '').replace(/;;+/g, '').replace(/^;|;$/g, '');
            newStyle += ';padding: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important;';
            $this.attr('style', newStyle);
        });

        setTimeout(function () {
            $('.ksb-custom-box').each(function () {
                var $this = $(this);
                $this.css({
                    'padding': '0 !important',
                    'padding-top': '0 !important',
                    'padding-bottom': '0 !important',
                    'padding-left': '0 !important',
                    'padding-right': '0 !important'
                });
            });
        }, 500);

        // 初期化完了
    });
};

// jQuery読み込み待機と初期化
(function () {
    // 即時実行でjQueryチェック
    if (typeof jQuery !== 'undefined') {
        window.kscPaginationInit();
    } else {

        // 定期的にチェック
        var checkCount = 0;
        var checkInterval = setInterval(function () {
            checkCount++;

            if (typeof jQuery !== 'undefined') {
                clearInterval(checkInterval);
                window.kscPaginationInit();
            } else if (checkCount >= 10) { // 1秒後にタイムアウト
                clearInterval(checkInterval);

                // jQueryを動的に読み込み
                var script = document.createElement('script');
                script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
                script.onload = function () {
                    // 動的読み込み完了時にもボタンを表示
                    setTimeout(function () {
                        if (typeof jQuery !== 'undefined') {
                            var $ = jQuery;
                            $('.ksc-pagination-controls').removeClass('ksc-pagination-loading');
                            $('.ksc-pagination-btn').css({
                                'visibility': 'visible',
                                'pointer-events': 'auto'
                            });
                        }
                    }, 100);

                    window.kscPaginationInit();
                };
                script.onerror = function () {
                    // エラー時は何もしない
                };
                document.head.appendChild(script);
            }
        }, 20); // チェック間隔を20msにさらに短縮
    }
})();
