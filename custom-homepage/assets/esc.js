(function ($) {
    'use strict';

    $(document).ready(function () {
        if ($(window).width() <= 600) {
            const $parentRegion = $('.ch-area-js');
            $parentRegion.click(function(event) {
                event.preventDefault();
                $parentRegion.each(function() {
                    $(this).removeClass('ch-active');
                    $(this).next().hide();
                });
                $(this).addClass('ch-active');
                    $(this).next().show();
            });
        }
    });
})(jQuery);
