(function ($) {
    Drupal.behaviors.mainMinHeight = {
        attach: function (context) {
            
            $page = $('.page');
            $main = $('#main-content');

            if($page.height() <= $(window).height()) {

                var totalHeight = 0;

                $page.children().each(function(){
                    if($(this).attr('id') !== $main.attr('id')) {
                        totalHeight += $(this).innerHeight();
                    }
                    $main.css('min-height', $(window).height() - totalHeight);
                });
            }
        }
    };
})(jQuery);