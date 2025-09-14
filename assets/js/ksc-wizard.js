jQuery(document).ready(function ($) {
    var currentStep = 1;
    var totalSteps = 6;
    var skipGridSettings = false;

    $('#ksc-post-type').on('change', function () {
        var postType = $(this).val();

        if (postType && postType.trim() !== '') {
            updateStep2Description(postType);
            loadCategories(postType);

            // 投稿タイプ変更時にレイアウト情報をリセット
            resetLayoutInfo();

            // ステップ2の準備は完了したが、自動では進まない
            // ユーザーが「次へ」ボタンを押すまで待機

            // 「次へ」ボタンを有効化
            $('#ksc-wizard-next').prop('disabled', false);
        } else {
            // 投稿タイプが選択されていない場合は「次へ」ボタンを無効化
            $('#ksc-wizard-next').prop('disabled', true);
        }

        // ナビゲーションボタンの状態を更新
        updateNavigationButtons();
    });

    $('#ksc-design').on('change', function () {
        var design = $(this).val();
        updateStepFlow(design);

        // ページネーション設定の表示制御
        if (design === 'carousel') {
            $('#ksc-pagination-settings').hide();
        } else {
            $('#ksc-pagination-settings').show();
        }

        // サムネイル設定の表示制御（リスト表示では非表示）
        if (design === 'list') {
            $('#ksc-thumbnail-settings').hide();
            $('#ksc-show-thumbnail').prop('checked', false);
            $('#ksc-thumbnail-options').hide();
        } else {
            $('#ksc-thumbnail-settings').show();
        }

        // デザイン変更時にレイアウトを再計算
        setTimeout(function () {
            calculateLayout();
            // 行数の選択肢も更新
            updateAvailableRows();
        }, 100);
    });

    // カテゴリ選択時の処理
    $(document).on('change', '#ksc-category', function () {
        // カテゴリ選択時にレイアウトを再計算
        calculateLayout();
    });

    // 列数選択時の処理
    $(document).on('change', '#ksc-cols', function () {
        updateAvailableRows();
        updateLayoutCalculation();
    });

    // 行数選択時の処理
    $(document).on('change', '#ksc-rows', function () {
        var design = $('#ksc-design').val() || 'grid';
        // リスト表示時以外はレイアウト計算結果を更新
        if (design !== 'list') {
            updateLayoutCalculation();
        }
    });



    $('#ksc-wizard-next').on('click', function () {
        if (currentStep < totalSteps) {
            var nextStep = getNextStep(currentStep);
            if (nextStep) {
                currentStep = nextStep;
                showStep(currentStep);
            }
        }
    });

    $('#ksc-wizard-prev').on('click', function () {
        if (currentStep > 1) {
            var prevStep = getPrevStep(currentStep);
            if (prevStep) {
                currentStep = prevStep;
                showStep(currentStep);
            }
        }
    });

    $('#ksc-wizard-generate').on('click', function () {
        generateShortcode();
    });

    $('#ksc-copy-shortcode').on('click', function () {
        var shortcode = $('#ksc-generated-shortcode');
        shortcode.select();
        document.execCommand('copy');

        var button = $(this);
        var originalText = button.text();
        button.text('コピーしました！');
        setTimeout(function () {
            button.text(originalText);
        }, 2000);
    });

    $('#ksc-copy-php').on('click', function () {
        var phpCode = $('#ksc-generated-php');
        phpCode.select();
        document.execCommand('copy');

        var button = $(this);
        var originalText = button.text();
        button.text('PHPコードをコピーしました！');
        setTimeout(function () {
            button.text(originalText);
        }, 2000);
    });

    // タイトル表示チェックボックスの制御
    $(document).on('change', '#ksc-show-title', function() {
        if ($(this).is(':checked')) {
            $('#ksc-title-options').show();
        } else {
            $('#ksc-title-options').hide();
        }
    });

    // コードタイプ切り替え
    $(document).on('change', 'input[name="code-type"]', function() {
        var codeType = $(this).val();
        if (codeType === 'shortcode') {
            $('#ksc-shortcode-section').show();
            $('#ksc-php-section').hide();
        } else {
            $('#ksc-shortcode-section').hide();
            $('#ksc-php-section').show();
        }
    });

    $('#ksc-wizard-reset').on('click', function () {
        resetWizard();
    });

    // 設定を変更するボタン
    $(document).on('click', '#ksc-wizard-back', function () {
        $('#ksc-wizard-result').hide();
        $('#ksc-wizard-form').show();
        // 最終ステップに戻る
        showStep(totalSteps);
    });

    // ページネーション設定の表示制御
    $(document).on('change', '#ksc-pagination', function () {
        if ($(this).is(':checked')) {
            $('#ksc-pagination-options').show();
        } else {
            $('#ksc-pagination-options').hide();
        }
    });

    // ソート設定の表示制御と連動
    $(document).on('change', '#ksc-orderby', function () {
        var orderby = $(this).val();
        var orderSelect = $('#ksc-order');
        var orderOptions = orderSelect.find('option');

        if (orderby === 'rand') {
            // ランダムの場合は順序を無効化
            orderSelect.prop('disabled', true);
            orderSelect.val('ASC');
            orderSelect.parent().css('opacity', '0.5');
        } else {
            // 順序を有効化
            orderSelect.prop('disabled', false);
            orderSelect.parent().css('opacity', '1');

            // 基準に応じて順序のラベルを変更
            if (orderby === 'date' || orderby === 'modified') {
                orderOptions.eq(0).text('新→古');
                orderOptions.eq(1).text('古→新');
                orderSelect.val('DESC'); // 日付系はデフォルトで新しい順
            } else if (orderby === 'title') {
                orderOptions.eq(0).text('Z→A');
                orderOptions.eq(1).text('A→Z');
                orderSelect.val('ASC'); // タイトルはデフォルトでA-Z順
            } else if (orderby === 'comment_count') {
                orderOptions.eq(0).text('多→少');
                orderOptions.eq(1).text('少→多');
                orderSelect.val('DESC'); // コメント数はデフォルトで多い順
            } else if (orderby === 'menu_order') {
                orderOptions.eq(0).text('降順');
                orderOptions.eq(1).text('昇順');
                orderSelect.val('ASC'); // メニュー順序はデフォルトで昇順
            }
        }
    });

    // ウィザード開くたびに初期化
    $(document).on('tb_unload', '#TB_window', function() {
        // モーダルが閉じられたときの処理
    });

    // ウィザードが開かれたときに初期化
    if ($('#ksc-wizard-modal').length) {
        setTimeout(function() {
            $('#ksc-orderby').trigger('change');
        }, 500);
    }

    function updateStep2Description(postType) {
        var step2Element = $('.ksc-wizard-step-2');
        var title = step2Element.find('h3');
        var description = step2Element.find('p');

        if (postType === 'page') {
            title.text('ステップ2: 親ページを選択（任意）');
            description.text('特定の親ページの子ページのみを表示したい場合は選択してください。');
        } else if (postType === 'post') {
            title.text('ステップ2: カテゴリを選択（任意）');
            description.text('特定のカテゴリの投稿のみを表示したい場合は選択してください。');
        } else {
            title.text('ステップ2: 分類を選択（任意）');
            description.text('特定の分類の投稿のみを表示したい場合は選択してください。投稿タイプによって利用可能な分類が変わります。');
        }
    }

    function updateStepFlow(design) {
        skipGridSettings = false; // 全てのデザインで設定ステップを表示

        var description = '';
        if (design === 'grid') {
            description = 'グリッド表示の列数と行数を設定してください。';
            $('#ksc-rows-setting').show();
            $('#ksc-cols').parent().show();
        } else if (design === 'carousel') {
            description = 'カルーセル表示で一度に表示する件数を設定してください。';
            $('#ksc-rows-setting').hide();
            $('#ksc-cols').parent().show();
        } else if (design === 'list') {
            description = 'リスト表示の行数を設定してください。';
            $('#ksc-rows-setting').show();
            $('#ksc-cols').parent().hide(); // 列数選択は非表示
        }

        $('#ksc-settings-description').text(description);

        // カルーセル設定の表示制御
        if (design === 'carousel') {
            $('#ksc-carousel-settings').show();
        } else {
            $('#ksc-carousel-settings').hide();
        }

        if (currentStep === 3) {
            updateNavigationButtons();
        }
    }

    function getNextStep(current) {
        if (current === 3 && skipGridSettings) {
            return 5; // ステップ4をスキップして5へ
        }
        return current + 1;
    }

    function getPrevStep(current) {
        if (current === 5 && skipGridSettings) {
            return 3; // ステップ4をスキップして3へ
        }
        return current - 1;
    }

    function showStep(step) {
        $('.ksc-wizard-step').hide();
        $('.ksc-wizard-step-' + step).show();

        updateNavigationButtons();
        updateStepIndicator(step);
        currentStep = step;

        // ステップ5（表示内容設定）に到達したときにソート設定を初期化
        if (step === 5) {
            setTimeout(function() {
                $('#ksc-orderby').trigger('change');
            }, 100);
        }
    }

    function updateNavigationButtons() {
        var isLastStep = currentStep === totalSteps;
        var nextStep = getNextStep(currentStep);
        var hasNext = nextStep && nextStep <= totalSteps;

        var showPrev = currentStep > 1;
        var showNext = hasNext && !isLastStep;
        var showGenerate = isLastStep;

        // ステップ1では投稿タイプが選択されているかチェック
        if (currentStep === 1) {
            var postType = $('#ksc-post-type').val();
            showNext = showNext && postType && postType.trim() !== '';

        }

        // アニメーションなしで即座に表示/非表示を切り替え
        if (showPrev) {
            $('#ksc-wizard-prev').show();
        } else {
            $('#ksc-wizard-prev').hide();
        }

        if (showNext) {
            $('#ksc-wizard-next').show();
        } else {
            $('#ksc-wizard-next').hide();
        }

        if (showGenerate) {
            $('#ksc-wizard-generate').show();
        } else {
            $('#ksc-wizard-generate').hide();
        }

        // ボタンの有効/無効状態も制御
        $('#ksc-wizard-next').prop('disabled', !showNext);
    }

    function updateStepIndicator(step) {
        // 全てのステップから状態をリセット
        $('.ksc-step-item').removeClass('ksc-step-active ksc-step-completed');

        // 全ステップをデフォルト状態に（インラインスタイルで強制）
        $('.ksc-step-item').each(function () {
            $(this).find('.ksc-step-number').css({
                'background': '#ddd',
                'color': '#666'
            });
            $(this).find('.ksc-step-label').css({
                'color': '#666',
                'font-weight': 'normal'
            });
        });

        // 完了したステップを緑色に
        for (var i = 1; i < step; i++) {
            var $completedItem = $('.ksc-step-item[data-step="' + i + '"]');
            $completedItem.addClass('ksc-step-completed');
            $completedItem.find('.ksc-step-number').css({
                'background': '#46b450',
                'color': 'white'
            });
            $completedItem.find('.ksc-step-label').css({
                'color': '#46b450'
            });

            // 接続線も緑色に
            $completedItem.next('.ksc-step-connector').css('background', '#46b450');
        }

        // 現在のステップをアクティブに（青色）
        var $activeItem = $('.ksc-step-item[data-step="' + step + '"]');
        $activeItem.addClass('ksc-step-active');
        $activeItem.find('.ksc-step-number').css({
            'background': '#0073aa',
            'color': 'white',
            'box-shadow': '0 0 0 4px rgba(0, 115, 170, 0.2)'
        });
        $activeItem.find('.ksc-step-label').css({
            'color': '#0073aa',
            'font-weight': '600'
        });


    }

    function loadCategories(postType) {
        var ajax_url = typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof ksc_ajax !== 'undefined' ? ksc_ajax.ajax_url : '');
        var nonce_value = $('#ksc_wizard_nonce').val() || (typeof ksc_ajax !== 'undefined' ? ksc_ajax.nonce : '');

        var data = {
            action: 'ksc_get_categories',
            post_type: postType,
            nonce: nonce_value
        };

        $('#ksc-category').html('<option value="">読み込み中...</option>');

        $.post(ajax_url, data, function (response) {
            if (response.success) {
                var postType = $('#ksc-post-type').val();
                var options = '<option value="">すべて</option>';
                var hasCategories = false;

                $.each(response.data, function (index, category) {
                    if (category.taxonomy === 'none') {
                        options = '<option value="" disabled>' + category.name + '</option>';
                    } else {
                        hasCategories = true;
                        var displayText = '';

                        if (category.taxonomy === 'page_parent') {
                            displayText = category.count > 0 ?
                                category.name + ' (' + category.count + '子ページ)' :
                                category.name + ' (子ページなし)';
                        } else {
                            displayText = category.count > 0 ?
                                category.name + ' (' + category.count + '件)' :
                                category.name;
                        }

                        options += '<option value="' + category.slug + '" data-taxonomy="' + category.taxonomy + '">' + displayText + '</option>';
                    }
                });

                $('#ksc-category').html(options);

                if (!hasCategories) {
                    $('#ksc-category').prop('disabled', true);
                } else {
                    $('#ksc-category').prop('disabled', false);
                }

                // カテゴリ読み込み完了後にレイアウト計算を実行
                setTimeout(function () {
                    calculateLayout();
                }, 200);
            }
        });
    }

    function generateShortcode() {
        var design = $('#ksc-design').val() || 'grid';
        var cols = 3;
        var rows = null;

        if (design === 'grid' || design === 'carousel') {
            cols = parseInt($('#ksc-cols').val()) || 3;
        }

        if (design === 'grid' || design === 'list') {
            rows = parseInt($('#ksc-rows').val()) || 2;
        }

        // カテゴリの値を確実に取得
        var categoryValue = $('#ksc-category').val();

        var nonce_value = $('#ksc_wizard_nonce').val() || (typeof ksc_ajax !== 'undefined' ? ksc_ajax.nonce : '');

        var formData = {
            action: 'ksc_generate_shortcode',
            post_type: $('#ksc-post-type').val() || 'post',
            category: categoryValue || '',
            cols: cols,
            rows: rows,
            design: design,
            color: $('#ksc-color').val() || '#333333',
            target: $('#ksc-target').val() || '_self',
            description_length: parseInt($('#ksc-description-length').val()) || 150,
            nonce: nonce_value
        };

        // 表示オプション設定を追加
        formData.show_title = $('#ksc-show-title').is(':checked') ? 'true' : 'false';
        formData.title_tag = $('#ksc-title-tag').val() || 'h2';
        formData.show_date = $('#ksc-show-date').is(':checked') ? 'true' : 'false';
        formData.show_author = $('#ksc-show-author').is(':checked') ? 'true' : 'false';
        formData.show_excerpt = $('#ksc-show-excerpt').is(':checked') ? 'true' : 'false';
        formData.show_category = $('#ksc-show-category').is(':checked') ? 'true' : 'false';
        formData.show_tags = $('#ksc-show-tags').is(':checked') ? 'true' : 'false';
        formData.show_read_more = $('#ksc-show-read-more').is(':checked') ? 'true' : 'false';
        formData.read_more_text = $('#ksc-read-more-text').val() || '続きを読む';
        formData.date_format = $('#ksc-date-format').val() || 'Y.m.d';

        // サムネイル設定を追加（リスト表示の場合は強制的に無効化）
        if (design === 'list') {
            formData.show_thumbnail = 'false';
            formData.thumbnail_position = 'top';
            formData.thumbnail_size = 'medium';
        } else {
            formData.show_thumbnail = $('#ksc-show-thumbnail').is(':checked') ? 'true' : 'false';
            formData.thumbnail_position = $('#ksc-thumbnail-position').val() || 'top';
            formData.thumbnail_size = $('#ksc-thumbnail-size').val() || 'medium';
        }

        // ソート設定を追加
        formData.orderby = $('#ksc-orderby').val() || 'date';
        formData.order = $('#ksc-order').val() || 'DESC';

        // ページネーション設定を追加
        formData.pagination = $('#ksc-pagination').is(':checked') ? 'true' : 'false';
        formData.pagination_type = $('#ksc-pagination-type').val() || 'numbers';
        formData.pagination_position = $('#ksc-pagination-position').val() || 'both';



        // カルーセル設定を追加
        if (design === 'carousel') {
            formData.autoplay = $('#ksc-autoplay').val() || 'false';
            formData.loop = $('#ksc-loop').val() || 'true';
            formData.interval = parseInt($('#ksc-interval').val()) || 3000;
        }



        // ajaxurlが定義されていない場合はksc_ajaxを使用
        var ajax_url = typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof ksc_ajax !== 'undefined' ? ksc_ajax.ajax_url : '');

        if (!ajax_url) {
            return;
        }

        $.post(ajax_url, formData, function (response) {
            if (response.success) {





                $('#ksc-generated-shortcode').val(response.data.shortcode);
                
                // PHPコードを生成（WP_Queryを使用）
                var phpCode = generatePHPCode(formData);
                $('#ksc-generated-php').val(phpCode);
                
                // 強制的に値を設定してみる
                setTimeout(function() {
                    var textarea = document.getElementById('ksc-generated-php');
                    if (textarea) {
                        textarea.value = phpCode;
                    }
                }, 100);
                
                previewShortcode(response.data.shortcode);
                $('#ksc-wizard-form').hide();
                $('#ksc-wizard-result').show();
            }
        }).fail(function (xhr, status, error) {
            alert('エラーが発生しました。コンソールを確認してください。');
        });
    }

    function previewShortcode(shortcode) {
        var ajax_url = typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof ksc_ajax !== 'undefined' ? ksc_ajax.ajax_url : '');
        var nonce_value = $('#ksc_wizard_nonce').val() || (typeof ksc_ajax !== 'undefined' ? ksc_ajax.nonce : '');

        var data = {
            action: 'ksc_preview_shortcode',
            shortcode: shortcode,
            nonce: nonce_value
        };

        $('#ksc-preview-content').html('<div class="ksc-loading">プレビューを読み込み中</div>');

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $('#ksc-preview-content').html(response.data.preview);
                    
                    setTimeout(function () {
                        initializeCarouselsInPreview();
                    }, 100);
                } else {
                    $('#ksc-preview-content').html('<p>プレビューの読み込みに失敗しました。</p>');
                }
            },
            error: function(xhr, status, error) {
                
                // レスポンスがHTMLの場合でも表示を試みる
                if (xhr.responseText && xhr.responseText.indexOf('<') === 0) {
                    $('#ksc-preview-content').html(xhr.responseText);
                } else {
                    $('#ksc-preview-content').html('<p>プレビューの読み込みに失敗しました。</p>');
                }
            }
        });
    }

    function initializeCarouselsInPreview() {
        // 少し待ってからカルーセルを初期化（DOMが完全に準備されるのを待つ）
        setTimeout(function () {
            var carousels = $('#ksc-preview-content .ksc-carousel');

            carousels.each(function () {
                var carousel = $(this)[0];
                var inner = carousel.querySelector('.ksc-carousel-inner');
                var items = carousel.querySelectorAll('.ksc-item');
                var prevBtn = carousel.querySelector('.ksc-carousel-prev');
                var nextBtn = carousel.querySelector('.ksc-carousel-next');

                if (!inner || !prevBtn || !nextBtn || items.length === 0) {
                    return;
                }

                var currentIndex = 0;
                var matches = carousel.className.match(/ksc-cols-(\d+)/);
                var itemsPerView = matches ? parseInt(matches[1]) : 3;
                var maxIndex = Math.max(0, items.length - itemsPerView);



                function updateCarousel() {
                    var offset = currentIndex * (100 / itemsPerView);
                    inner.style.transform = 'translateX(-' + offset + '%)';
                    inner.style.transition = 'transform 0.3s ease';

                    prevBtn.style.display = currentIndex === 0 ? 'none' : 'block';
                    nextBtn.style.display = currentIndex >= maxIndex ? 'none' : 'block';
                }

                // 既存のイベントハンドラーを削除
                $(prevBtn).off('click.ksc').on('click.ksc', function (e) {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateCarousel();
                    }
                });

                $(nextBtn).off('click.ksc').on('click.ksc', function (e) {
                    e.preventDefault();
                    if (currentIndex < maxIndex) {
                        currentIndex++;
                        updateCarousel();
                    }
                });

                updateCarousel();
            });
        }, 200);
    }

    function resetWizard() {
        currentStep = 1;
        skipGridSettings = false;
        $('#ksc-wizard-form')[0].reset();
        $('#ksc-wizard-form').show();
        $('#ksc-wizard-result').hide();
        showStep(1);
        $('#ksc-category').html('<option value="">すべて</option>').prop('disabled', false);

        var step2Element = $('.ksc-wizard-step-2');
        step2Element.find('h3').text('ステップ2: カテゴリ・分類を選択（任意）');
        step2Element.find('p').text('特定のカテゴリや分類の投稿のみを表示したい場合は選択してください。投稿タイプによって利用可能な分類が変わります。');

        // ステップインジケーターも初期状態にリセット
        updateStepIndicator(1);

        // レイアウト情報をリセット
        resetLayoutInfo();
    }

    /**
     * レイアウト情報をリセット
     */
    function resetLayoutInfo() {
        $('#ksc-post-count-info').html('<p style="margin: 0; font-weight: 600; color: #333;">投稿件数を確認中...</p>');
        $('#ksc-layout-calculation').hide();
    }

    /**
     * レイアウトを計算
     */
    function calculateLayout() {
        var postType = $('#ksc-post-type').val();
        var category = $('#ksc-category').val();
        var design = $('#ksc-design').val();

        if (!postType) {
            resetLayoutInfo();
            return;
        }

        var ajax_url = typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof ksc_ajax !== 'undefined' ? ksc_ajax.ajax_url : '');
        var nonce_value = $('#ksc_wizard_nonce').val() || (typeof ksc_ajax !== 'undefined' ? ksc_ajax.nonce : '');

        var data = {
            action: 'ksc_calculate_layout',
            post_type: postType,
            category: category,
            design: design,
            nonce: nonce_value
        };

        $('#ksc-post-count-info').html('<p style="margin: 0; font-weight: 600; color: #333;">投稿件数を計算中...</p>');

        $.post(ajax_url, data, function (response) {
            if (response.success) {
                displayLayoutInfo(response.data);
            } else {
                $('#ksc-post-count-info').html('<p style="margin: 0; font-weight: 600; color: #d63638;">エラーが発生しました</p>');
            }
        }).fail(function () {
            $('#ksc-post-count-info').html('<p style="margin: 0; font-weight: 600; color: #d63638;">通信エラーが発生しました</p>');
        });
    }

    /**
     * レイアウト情報を表示
     */
    function displayLayoutInfo(layoutInfo) {
        var postCount = layoutInfo.post_count;
        var suggestedCols = layoutInfo.suggested_cols;
        var suggestedRows = layoutInfo.suggested_rows;


        // 投稿件数情報を更新
        $('#ksc-post-count-info').html(
            '<p style="margin: 0; font-weight: 600; color: #333;">投稿件数: ' + postCount + '件</p>'
        );

        // 利用可能な列数と行数を更新
        updateAvailableOptions(layoutInfo);

        // リスト表示時以外はレイアウト計算結果を表示
        var design = $('#ksc-design').val() || 'grid';
        if (design !== 'list') {
            updateLayoutCalculation();
        }
    }

    /**
     * 利用可能なオプションを更新
     */
    function updateAvailableOptions(layoutInfo) {
        var availableCols = layoutInfo.available_cols;
        var availableRows = layoutInfo.available_rows;
        var suggestedCols = layoutInfo.suggested_cols;
        var suggestedRows = layoutInfo.suggested_rows;
        var design = $('#ksc-design').val() || 'grid';


        // 列数の選択肢を更新
        var colsSelect = $('#ksc-cols');
        var currentCols = colsSelect.val();
        colsSelect.empty();

        if (design === 'list') {
            // リスト表示では列数は常に1
            colsSelect.append('<option value="1" selected>1件</option>');
            colsSelect.prop('disabled', true); // 列数選択を無効化
        } else {
            // グリッド・カルーセル表示では投稿件数に基づいて計算
            availableCols.forEach(function (cols) {
                var selected = cols == suggestedCols ? ' selected' : '';
                colsSelect.append('<option value="' + cols + '"' + selected + '>' + cols + '件</option>');
            });
            colsSelect.prop('disabled', false); // 列数選択を有効化
        }

        // 行数の選択肢を更新
        updateAvailableRows();

        // リスト表示時はレイアウト計算結果を非表示
        if (design === 'list') {
            $('#ksc-layout-calculation').hide();
        }
    }

    /**
     * 利用可能な行数を更新
     */
    function updateAvailableRows() {
        var selectedCols = parseInt($('#ksc-cols').val()) || 3;
        
        // 投稿件数を安全に取得
        var postCountText = $('#ksc-post-count-info p').text();
        var postCountMatch = postCountText.match(/\d+/);
        var postCount = postCountMatch ? parseInt(postCountMatch[0]) : 0;
        var design = $('#ksc-design').val() || 'grid';

        var maxRows;
        if (design === 'list') {
            // リスト表示では投稿件数まで行数を設定可能
            maxRows = postCount;
        } else {
            // グリッド表示では列数に基づいて計算
            maxRows = Math.ceil(postCount / selectedCols);
        }

        var rowsSelect = $('#ksc-rows');
        var currentRows = rowsSelect.val();
        rowsSelect.empty();

        for (var rows = 1; rows <= Math.min(10, maxRows); rows++) {
            var selected = rows == currentRows ? ' selected' : '';
            rowsSelect.append('<option value="' + rows + '"' + selected + '>' + rows + '行</option>');
        }

        // 現在の行数が利用可能な範囲外の場合は調整
        if (currentRows > maxRows) {
            rowsSelect.val(Math.min(currentRows, maxRows));
        }

    }

    /**
     * レイアウト計算結果を更新
     */
    function updateLayoutCalculation() {
        var design = $('#ksc-design').val() || 'grid';
        var selectedCols, selectedRows, displayCount;

        if (design === 'list') {
            // リスト表示では列数は常に1
            selectedCols = 1;
            selectedRows = parseInt($('#ksc-rows').val()) || 2;
            displayCount = selectedRows; // リストでは行数 = 表示件数
        } else {
            // グリッド・カルーセル表示では列数×行数
            selectedCols = parseInt($('#ksc-cols').val()) || 3;
            selectedRows = parseInt($('#ksc-rows').val()) || 2;
            displayCount = selectedCols * selectedRows;
        }

        var postCountText = $('#ksc-post-count-info p').text();
        var totalCount = parseInt(postCountText.match(/\d+/)[0]) || 0;
        var remainingCount = Math.max(0, totalCount - displayCount);

        $('#ksc-selected-cols').text(selectedCols);
        $('#ksc-selected-rows').text(selectedRows);
        $('#ksc-display-count').text(displayCount);
        $('#ksc-total-count').text(totalCount);
        $('#ksc-remaining-count').text(remainingCount);

        // レイアウト計算結果を表示
        $('#ksc-layout-calculation').show();

        // 残り件数に応じて色を変更
        if (remainingCount === 0) {
            $('#ksc-layout-calculation').css('background', '#fff3cd').css('border-color', '#ffc107');
        } else if (remainingCount < 0) {
            $('#ksc-layout-calculation').css('background', '#f8d7da').css('border-color', '#dc3545');
        } else {
            $('#ksc-layout-calculation').css('background', '#e8f5e8').css('border-color', '#46b450');
        }
    }



    showStep(1);

    // 初期化時にデザイン設定を適用
    var initialDesign = $('#ksc-design').val() || 'grid';
    updateStepFlow(initialDesign);

    // DOMの準備完了を待ってから初期化
    setTimeout(function () {
        // 初期状態でナビゲーションボタンを正しく設定
        updateNavigationButtons();

        // 初期状態では投稿タイプが選択されていないので「次へ」ボタンを無効化
        var initialPostType = $('#ksc-post-type').val();
        if (!initialPostType || initialPostType.trim() === '') {
            $('#ksc-wizard-next').prop('disabled', true);
        } else {
        }
    }, 100);

    // PHP\u30b3\u30fc\u30c9\u751f\u6210\u95a2\u6570
    function generatePHPCode(formData) {
        // NULLチェックとデフォルト値設定
        var cols = formData.cols || 3;
        var rows = formData.rows || 2;
        var postsPerPage = cols * rows;
        
        // デザインに応じた件数調整
        if (formData.design === 'carousel') {
            postsPerPage = cols * 3; // カルーセルは3ページ分
        } else if (formData.design === 'list') {
            postsPerPage = rows; // リストは行数のみ
        }
        
        // 共通のCSSとJavaScriptを読み込み
        var code = "<!-- KSC Plugin Assets -->\n";
        code += "<link rel=\"stylesheet\" href=\"<?php echo get_site_url(); ?>/wp-content/plugins/kashiwazaki-shortcode-collector/assets/css/ksc-styles.css?ver=1.0.0\">\n";
        if (formData.design === 'carousel') {
            code += "<script src=\"<?php echo get_site_url(); ?>/wp-content/plugins/kashiwazaki-shortcode-collector/assets/js/ksc-carousel.js?ver=1.0.0\"></script>\n";
        }
        code += "\n";
        code += "<?php\n";
        code += "$args = array(\n";
        code += "    'post_type' => '" + formData.post_type + "',\n";
        code += "    'posts_per_page' => " + postsPerPage + ",\n";
        code += "    'post_status' => 'publish',\n";
        
        // \u30ab\u30c6\u30b4\u30ea\u8a2d\u5b9a
        if (formData.category && formData.category !== '') {
            if (formData.category.indexOf('page-') === 0) {
                var pageId = formData.category.replace('page-', '');
                code += "    'post_parent' => " + pageId + ",\n";
            } else if (formData.post_type === 'post') {
                code += "    'category_name' => '" + formData.category + "',\n";
            } else {
                // \u30ab\u30b9\u30bf\u30e0\u6295\u7a3f\u30bf\u30a4\u30d7\u306e\u5834\u5408\u306f\u30bf\u30af\u30bd\u30ce\u30df\u30fc\u3092\u63a8\u6e2c
                code += "    // \u30bf\u30af\u30bd\u30ce\u30df\u30fc\u540d\u3092\u9069\u5207\u306b\u7f6e\u304d\u63db\u3048\u3066\u304f\u3060\u3055\u3044\n";
                code += "    'tax_query' => array(\n";
                code += "        array(\n";
                code += "            'taxonomy' => 'your_taxonomy', // \u30bf\u30af\u30bd\u30ce\u30df\u30fc\u540d\u3092\u6307\u5b9a\n";
                code += "            'field' => 'slug',\n";
                code += "            'terms' => '" + formData.category + "',\n";
                code += "        ),\n";
                code += "    ),\n";
            }
        }
        
        // \u30bd\u30fc\u30c8\u8a2d\u5b9a
        code += "    'orderby' => '" + formData.orderby + "',\n";
        code += "    'order' => '" + formData.order + "',\n";
        
        code += ");\n\n";
        code += "$query = new WP_Query($args);\n\n";
        code += "if ($query->have_posts()) : ?>\n";
        
        // \u30c7\u30b6\u30a4\u30f3\u306b\u5fdc\u3058\u305f\u30b3\u30f3\u30c6\u30ca
        if (formData.design === 'grid') {
            code += "    <div class=\"ksc-grid ksc-cols-" + formData.cols + "\">\n";
        } else if (formData.design === 'list') {
            code += "    <div class=\"ksc-list\">\n";
        } else if (formData.design === 'carousel') {
            code += "    <div class=\"ksc-carousel-wrapper\">\n";
            code += "        <div class=\"ksc-carousel ksc-cols-" + formData.cols + "\"\n";
            code += "               data-autoplay=\"" + formData.autoplay + "\"\n";
            code += "               data-loop=\"" + formData.loop + "\"\n";
            code += "               data-interval=\"" + formData.interval + "\"\n";
            code += "               style=\"--ksc-color: " + formData.color + ";\">\n";
            code += "            <div class=\"ksc-carousel-inner\">\n";
        }
        
        code += "        <?php while ($query->have_posts()) : $query->the_post(); ?>\n";
        
        // アイテムクラスの設定
        var itemClasses = "ksc-item ksc-item-" + formData.design;
        if (formData.show_thumbnail === 'true') {
            itemClasses += " ksc-thumbnail-" + (formData.thumbnail_position || 'top');
        }
        code += "            <div class=\"" + itemClasses + "\">\n";
        
        // サムネイル（topの場合）
        if (formData.show_thumbnail === 'true' && (!formData.thumbnail_position || formData.thumbnail_position === 'top')) {
            code += "                <?php if (has_post_thumbnail()) : ?>\n";
            code += "                    <a href=\"<?php the_permalink(); ?>\"";
            if (formData.target === '_blank') {
                code += " target=\"_blank\"";
            }
            code += " class=\"ksc-thumbnail ksc-thumbnail-top\">\n";
            code += "                        <img src=\"<?php echo get_the_post_thumbnail_url(get_the_ID(), '" + (formData.thumbnail_size || 'full') + "'); ?>\" alt=\"<?php the_title_attribute(); ?>\">\n";
            code += "                    </a>\n";
            code += "                <?php endif; ?>\n";
        }
        
        code += "                <div class=\"ksc-content\">\n";
        
        // \u30bf\u30a4\u30c8\u30eb
        code += "                    <h3 class=\"ksc-title\">\n";
        code += "                        <a href=\"<?php the_permalink(); ?>\"";
        if (formData.target === '_blank') {
            code += " target=\"_blank\"";
        }
        code += " class=\"ksc-title-link\" style=\"color: " + formData.color + ";\">\n";
        code += "                            <?php the_title(); ?>\n";
        code += "                        </a>\n";
        code += "                    </h3>\n";
        
        // \u30e1\u30bf\u60c5\u5831
        code += "                    <div class=\"ksc-meta\">\n";
        
        if (formData.show_date === 'true') {
            code += "                        <span class=\"ksc-date\"><?php echo get_the_date('" + formData.date_format + "'); ?></span>\n";
        }
        
        if (formData.show_author === 'true') {
            code += "                        <span class=\"ksc-author\"><?php the_author(); ?></span>\n";
        }
        
        if (formData.show_category === 'true') {
            code += "                        <span class=\"ksc-category\"><?php the_category(', '); ?></span>\n";
        }
        
        if (formData.show_tags === 'true') {
            code += "                        <span class=\"ksc-tags\"><?php the_tags('', ', '); ?></span>\n";
        }
        
        code += "                    </div>\n";
        
        // \u629c\u7c8b
        if (formData.show_excerpt === 'true') {
            code += "                    <div class=\"ksc-excerpt\">\n";
            code += "                        <?php echo wp_trim_words(get_the_excerpt(), " + formData.description_length + "); ?>\n";
            code += "                    </div>\n";
        }
        
        // \u7d9a\u304d\u3092\u8aad\u3080\u30ea\u30f3\u30af
        if (formData.show_read_more === 'true') {
            code += "                    <div class=\"ksc-read-more\">\n";
            code += "                        <a href=\"<?php the_permalink(); ?>\" class=\"ksc-read-more-link\"";
            if (formData.target === '_blank') {
                code += " target=\"_blank\"";
            }
            code += ">" + (formData.read_more_text || '続きを読む') + "</a>\n";
            code += "                    </div>\n";
        }
        
        code += "                </div>\n";
        code += "            </div>\n";
        code += "        <?php endwhile; ?>\n";
        
        // デザインに応じてコンテナを閉じる
        if (formData.design === 'carousel') {
            code += "            </div>\n"; // ksc-carousel-inner終了
            code += "            <button class=\"ksc-carousel-prev\">&lt;</button>\n";
            code += "            <button class=\"ksc-carousel-next\">&gt;</button>\n";
            code += "        </div>\n"; // ksc-carousel終了
            code += "    </div>\n"; // ksc-carousel-wrapper終了
        } else {
            code += "    </div>\n";
        }
        
        // \u30da\u30fc\u30b8\u30cd\u30fc\u30b7\u30e7\u30f3
        if (formData.pagination === 'true' && formData.design !== 'carousel') {
            code += "    <div class=\"ksc-pagination\">\n";
            code += "        <?php\n";
            code += "        echo paginate_links(array(\n";
            code += "            'total' => $query->max_num_pages,\n";
            code += "            'current' => max(1, get_query_var('paged')),\n";
            if (formData.pagination_type === 'arrows') {
                code += "            'prev_text' => '\u2039 \u524d\u3078',\n";
                code += "            'next_text' => '\u6b21\u3078 \u203a',\n";
            }
            code += "        ));\n";
            code += "        ?>\n";
            code += "    </div>\n";
        }
        
        code += "    <?php wp_reset_postdata(); ?>\n";
        
        // カルーセル用JavaScript（既存のファイルの関数を使用）
        if (formData.design === 'carousel') {
            code += "    <script>\n";
            code += "    // カルーセル初期化を遅延実行\n";
            code += "    document.addEventListener('DOMContentLoaded', function() {\n";
            code += "        setTimeout(function() {\n";
            code += "            if (typeof window.kscInitCarousels === 'function') {\n";
            code += "                window.kscInitCarousels();\n";
            code += "            } else {\n";
            code += "            }\n";
            code += "        }, 100);\n";
            code += "    });\n";
            code += "    </script>\n";
        }
        
        code += "<?php else : ?>\n";
        code += "    <p>\u6295\u7a3f\u304c\u898b\u3064\u304b\u308a\u307e\u305b\u3093\u3067\u3057\u305f\u3002</p>\n";
        code += "<?php endif; ?>\n\n";
        
        
        return code;
    }

    // \u6295\u7a3f\u30bf\u30a4\u30d7\u9078\u629e\u6642\u306e\u30ec\u30a4\u30a2\u30a6\u30c8\u8a08\u7b97\u306floadCategories\u5185\u3067\u5b9f\u884c\u3055\u308c\u308b\u305f\u3081\u524a\u9664

    // \u30b5\u30e0\u30cd\u30a4\u30eb\u8a2d\u5b9a\u306e\u8868\u793a\u5236\u5fa1
    $(document).on('change', '#ksc-show-thumbnail', function () {
        if ($(this).is(':checked')) {
            $('#ksc-thumbnail-options').show();
        } else {
            $('#ksc-thumbnail-options').hide();
        }
    });

    // 続きを読む設定の表示制御
    $(document).on('change', '#ksc-show-read-more', function () {
        if ($(this).is(':checked')) {
            $('#ksc-read-more-options').show();
        } else {
            $('#ksc-read-more-options').hide();
        }
    });

    // 日付設定の表示制御
    $(document).on('change', '#ksc-show-date', function () {
        if ($(this).is(':checked')) {
            $('#ksc-date-options').show();
        } else {
            $('#ksc-date-options').hide();
        }
    });
});
