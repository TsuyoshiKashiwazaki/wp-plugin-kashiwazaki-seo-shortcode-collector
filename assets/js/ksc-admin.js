jQuery(document).ready(function ($) {
    $('.ksc-show-all').on('click', function () {
        var targetId = $(this).data('target');
        $('#' + targetId + ' .ksc-hidden').slideDown();
        $(this).fadeOut();
    });
});

// その他の管理画面用の関数をここに追加可能
