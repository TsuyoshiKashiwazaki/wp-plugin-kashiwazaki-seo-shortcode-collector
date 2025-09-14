(function () {
    // ページ上のすべてのカルーセルを初期化する関数
    function initializeAllCarousels() {
        var carousels = document.querySelectorAll('.ksc-carousel');
        carousels.forEach(function (carousel) {
            initializeSingleCarousel(carousel);
        });
    }

    // 個々のカルーセルを初期化する関数
    function initializeSingleCarousel(carousel) {
        var inner = carousel.querySelector('.ksc-carousel-inner');
        var items = carousel.querySelectorAll('.ksc-carousel-inner > .ksc-item');
        var prevBtn = carousel.querySelector('.ksc-carousel-prev');
        var nextBtn = carousel.querySelector('.ksc-carousel-next');

        if (!inner || !prevBtn || !nextBtn || items.length === 0) {
            if (prevBtn && nextBtn) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
            return; // 必要な要素がなければ何もしない
        }

        var currentIndex = 0;
        var totalItems = items.length;

        // レスポンシブ対応のアイテム表示数を計算
        function getItemsPerView() {
            var windowWidth = window.innerWidth;
            var effectiveCols = parseInt(carousel.dataset.effectiveCols) || 3;
            var mobileBreakpoint = parseInt(carousel.dataset.mobileBreakpoint) || 768;
            var tabletBreakpoint = parseInt(carousel.dataset.tabletBreakpoint) || 1024;

            // モバイルブレイクポイント以下では1列表示
            if (windowWidth <= mobileBreakpoint) {
                return 1;
            }
            // タブレットブレイクポイント以下では段階的に列数を減らす
            if (windowWidth <= tabletBreakpoint) {
                if (effectiveCols >= 5) {
                    return 3; // 5列以上 → 3列
                } else if (effectiveCols >= 3) {
                    return 2; // 3-4列 → 2列
                }
                // 1-2列はそのまま維持
                return effectiveCols;
            }
            // それ以外では設定された列数を使用
            return effectiveCols;
        }

        var itemsPerView = getItemsPerView();
        var maxIndex = Math.max(0, totalItems - itemsPerView);

        var loop = carousel.dataset.loop === 'true';
        var autoplay = carousel.dataset.autoplay === 'true';
        var interval = parseInt(carousel.dataset.interval) || 3000;
        var autoplayTimer = null;

        function updateCarousel() {
            // レスポンシブ対応でアイテム表示数を再計算
            itemsPerView = getItemsPerView();
            maxIndex = Math.max(0, totalItems - itemsPerView);

            // 現在のインデックスが最大値を超えている場合は調整
            if (currentIndex > maxIndex) {
                currentIndex = maxIndex;
            }

            var mobileBreakpoint = parseInt(carousel.dataset.mobileBreakpoint) || 768;
            var offset;
            if (window.innerWidth <= mobileBreakpoint) {
                // モバイルサイズ: より精密な計算
                if (items.length > 0) {
                    // カルーセルコンテナの実際の幅を基準に計算
                    var containerWidth = carousel.offsetWidth;
                    offset = currentIndex * containerWidth;
                    
                } else {
                    offset = 0;
                }
            } else {
                // デスクトップ・タブレットサイズ: 従来の計算方法
                var itemWidth = items[0].offsetWidth;
                var gap = 20;
                offset = currentIndex * (itemWidth + gap);
            }
            
            inner.style.transform = 'translateX(-' + offset + 'px)';

            // ボタンの表示/非表示ロジックを簡素化・確実に
            if (loop && totalItems > itemsPerView) {
                prevBtn.style.display = 'block';
                nextBtn.style.display = 'block';
            } else {
                prevBtn.style.display = currentIndex > 0 ? 'block' : 'none';
                nextBtn.style.display = currentIndex < maxIndex ? 'block' : 'none';
            }
        }

        function goToNext() {
            if (currentIndex < maxIndex) {
                currentIndex++;
            } else if (loop) {
                currentIndex = 0;
            }
            updateCarousel();
        }

        function goToPrev() {
            if (currentIndex > 0) {
                currentIndex--;
            } else if (loop) {
                currentIndex = maxIndex;
            }
            updateCarousel();
        }

        function startAutoplay() {
            if (!autoplay || totalItems <= itemsPerView) return;
            stopAutoplay(); // 既存のタイマーをクリア
            autoplayTimer = setInterval(goToNext, interval);
        }

        function stopAutoplay() {
            clearInterval(autoplayTimer);
        }

        prevBtn.addEventListener('click', function () {
            stopAutoplay();
            goToPrev();
            startAutoplay();
        });

        nextBtn.addEventListener('click', function () {
            stopAutoplay();
            goToNext();
            startAutoplay();
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);

        // 初期化
        updateCarousel();
        startAutoplay();

        // 初期化後に少し遅延して再度更新（レイアウト確定後）
        setTimeout(function () {
            updateCarousel();
        }, 100);

        // さらに長い遅延で最終的な位置調整（画像読み込み完了後）
        setTimeout(function () {
            // 現在のインデックスを一度リセットして再計算
            var currentIdx = currentIndex;
            currentIndex = 0;
            updateCarousel();
            // 元の位置に戻す
            if (currentIdx > 0) {
                currentIndex = currentIdx;
                updateCarousel();
            }
        }, 500);

        // ウィンドウリサイズ時の再計算
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // リサイズ時の処理を改善
                var prevItemsPerView = itemsPerView;
                itemsPerView = getItemsPerView();
                
                // 表示アイテム数が変わった場合のみインデックスを調整
                if (prevItemsPerView !== itemsPerView) {
                    // 現在のインデックスを維持しつつ、新しい最大値に収める
                    maxIndex = Math.max(0, totalItems - itemsPerView);
                    if (currentIndex > maxIndex) {
                        currentIndex = maxIndex;
                    }
                }
                
                updateCarousel();
            }, 250);
        });
    }

    // ページの初回読み込み時にすべてのカルーセルを初期化
    document.addEventListener('DOMContentLoaded', function () {
        initializeAllCarousels();
    });

    // グローバルに公開
    window.kscInitCarousels = initializeAllCarousels;

})();
