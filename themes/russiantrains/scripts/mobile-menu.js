(function ($) {
    Drupal.behaviors.mobilemenu = {
        attach: function (context, settings) {

            // for menu on tablets and mobile devices
            var sub_level_opened_flag = 'sub-level-opened';
            var opened_flag = 'opened';

            $(context).find('.sub-level-menu-trigger').once('subLevelMenuOpen').click(function () {

                var $parent_li = $(this).closest('.menu__item');
                var $parent_ul = $(this).closest('.menu');

                if ($parent_li.hasClass(sub_level_opened_flag)) {
                    var click_to_close = true;
                }
                else {
                    click_to_close = false;
                }

                if (click_to_close) {
                    $parent_li.removeClass(sub_level_opened_flag);
                    $parent_li.find('.menu:first').removeClass(opened_flag);
                }
                else {
                    $parent_ul.children('li').removeClass(sub_level_opened_flag);
                    $parent_ul.children('li').children('ul').removeClass(opened_flag);
                    $parent_li.addClass(sub_level_opened_flag);
                    $parent_li.children('ul').addClass(opened_flag);
                }
            });
        }
    };
}(jQuery));
