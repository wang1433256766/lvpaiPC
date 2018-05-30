;
(function($) {

    $.fn.tab = function(options) {
        // 将defaults 和 options 参数合并到{}
        var opts = $.extend({}, $.fn.tab.defaults, options);

        return this.each(function() {
            var obj = $(this);
            $(obj).find('.tab_header li').on('click', function() {
                $(obj).find('.tab_header li').removeClass('active');
                $(this).addClass('active');

                $(obj).find('.tab_content .tab_content_item').hide();
                $(obj).find('.tab_content .tab_content_item').eq($(this).index()).show();
            })
        });
        // each end
    }

    //定义默认
    $.fn.tab.defaults = {

    };

})(jQuery);

$(function() {
    $("#tab").tab();
})