(function ($) {

    Drupal.align_dropdown_menu = Drupal.align_dropdown_menu ? Drupal.align_dropdown_menu : {};
    Drupal.align_dropdown_menu.menuMinLeft = 0;
    Drupal.align_dropdown_menu.menuMaxRight = 0;

    Drupal.align_dropdown_menu.updateMenuBoundsLimit = function() {
        Drupal.align_dropdown_menu.menuMinLeft = $('.pr-header__header-first').offset().left;
        Drupal.align_dropdown_menu.menuMaxRight = Drupal.align_dropdown_menu.menuMinLeft + $('.top-header').outerWidth();
    }

    Drupal.align_dropdown_menu.updateMenuPanelPosition = function($item) {
        var $second_level_menu = $item.find('.menu-level-2');
        if ($second_level_menu.length) {
            $second_level_menu.css('display', 'flex');
            var menu_left = - $second_level_menu.outerWidth()/2 + $item.outerWidth()/2;
            var menu_left_offset = $second_level_menu.offset().left + menu_left;

            if (menu_left_offset + $second_level_menu.outerWidth() > Drupal.align_dropdown_menu.menuMaxRight) {
                var right_distance = Drupal.align_dropdown_menu.menuMaxRight - $second_level_menu.offset().left;
                menu_left = - ($second_level_menu.outerWidth() - right_distance);
                var menu_right = - (Drupal.align_dropdown_menu.menuMaxRight - ($item.offset().left + $item.outerWidth()));
                $second_level_menu.css('right', menu_right);
            }
            else {
                if (menu_left_offset < Drupal.align_dropdown_menu.menuMinLeft) {
                    var left_distance = Drupal.align_dropdown_menu.menuMinLeft - menu_left_offset;
                    menu_left = menu_left + left_distance;
                }
                $second_level_menu.css('right', '');
            }
            $second_level_menu.css('left', menu_left);
            $second_level_menu.css('display', '');
        }
    }

    Drupal.behaviors.align_dropdown_menu = {
        attach: function (context, settings) {;
            setTimeout(function() {
                Drupal.align_dropdown_menu.updateMenuBoundsLimit();
                $(context).find('.pr-header__header-second .menu-level-1 > li').once('updateMenuPosition').each(function (index, elem) {
                    Drupal.align_dropdown_menu.updateMenuPanelPosition($(elem));
                });
            }, 0);

            $(window).resize(function(){
                setTimeout(function() {
                    Drupal.align_dropdown_menu.updateMenuBoundsLimit();
                    $(context).find('.pr-header__header-second .menu-level-1 > li').once('updateMenuPosition').each(function (index, elem) {
                        Drupal.align_dropdown_menu.updateMenuPanelPosition($(elem));
                    });
                }, 0);
            });
        }
    };
}(jQuery));
