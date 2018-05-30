;
(function($) {

    $.fn.tab = function(options) {
        // 将defaults 和 options 参数合并到{}
        var opts = $.extend({}, $.fn.tab.defaults, options);

        return this.each(function() {
            var obj = $(this);
            $(obj).find('.tab_header').eq(0).find('li').on('click', function() {
                $(obj).find('.tab_header').eq(0).find('li').removeClass('active');
                $(this).addClass('active');
                if (obj[0].className == 'main-tab') {
                    $(".self-name li").removeClass('active');
                    $(".self-name li").eq($(this).index()).addClass('active');
                }
                $(obj).find('.tab_content').eq(0).children('.tab_content_item').hide();
                $(obj).find('.tab_content').eq(0).children('.tab_content_item').eq($(this).index()).show();
            })
        });
        // each end
    }

    //定义默认
    $.fn.tab.defaults = {

    };

})(jQuery);

$(function() {
    $(".main-tab").tab();
    $(".sub-tab").tab();
    $(".self-name li").click(function() {
        $(".self-name li").removeClass('active');
        $(this).addClass('active');

        $(".main-tab").find('.tab_header').eq(0).find('li').removeClass('active');
        $(".main-tab").find('.tab_header').eq(0).find('li').eq($(this).index()).addClass('active');

        $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').hide();
        $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').eq($(this).index()).show();
    })

    //订单中心
    $(".order-center li").on("click", function() {
        $(".order-center li").removeClass('active');
        $(this).addClass('active');
    })
})